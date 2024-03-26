<?php 
namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\TicketType;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\Models\TicketOrder;
use App\Models\TicketInstance;
use App\Models\Notification; 
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException; 
use Illuminate\Support\Facades\Mail;
use App\Mail\TicketPurchaseConfirmation;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash; 
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Validator;



use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    public function view($id): View
    { 
        $event = Event::findOrFail($id);
        $user = Auth::user();
        
        $notifications = $user ? $user->notifications : [];

        return view('pages.event', compact('event', 'notifications'));
    }

    public function index(): View
    {
        $user = Auth::user();

        if ($user && $user->is_admin) {
            $events = Event::orderBy('start_timestamp', 'asc')->paginate(12);
        } else {
            $events = Event::where('private', false)
                            ->orderBy('start_timestamp', 'asc')
                            ->paginate(12);
        }
        
        $notifications = $user ? $user->notifications : [];


        return view('pages.all_events', compact('events', 'user', 'notifications'));
    }

    public function ajax_paginate(Request $request)
    {
        $user = Auth::user();

        if ($user && $user->is_admin) {
            $events = Event::orderBy('start_timestamp', 'asc')->paginate(12);
        } else {
            $events = Event::where('private', false)->orderBy('start_timestamp', 'asc')->paginate(12);
        }

        $notifications = $user ? $user->notifications : [];


        return view('partials.event_cards', compact('events', 'user', 'notifications'))->render();
    }

    public function myEvents(): View
    {
        if (Auth::check()) {
            $user = Auth::user();
            $events = Event::where('creator_id', Auth::user()->user_id)
            ->orderBy('start_timestamp', 'asc')
            ->paginate(4);
            $notifications = $user ? $user->notifications : [];
            return view('pages.my_events', compact('events', 'notifications'));
        } else {
            $events = paginate(4);
            return view('pages.my_events', compact('events'));
        }
        
    }

    public function myEvents_paginate(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $events = Event::where('creator_id', Auth::user()->user_id)
            ->orderBy('start_timestamp', 'asc')
            ->paginate(4);
            $notifications = $user ? $user->notifications : [];
            return view('partials.my_event_cards', compact('events', 'notifications'))->render();
        } else {
            $events = collect()->paginate(4);
            return view('partials.my_event_cards', compact('events'))->render();
        }
    }
    

    public function createEvent(Request $request)
    {
        $this->authorize('createEvent', Event::class);
    
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'required|string',
            'start_timestamp' => 'required|date|after_or_equal:now',
            'end_timestamp' => 'required|date|after:start_timestamp',
        ], [
            'start_timestamp.after_or_equal' => 'The start timestamp must be in the future.',
        ]);
    
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
    
        $event = new Event();
        $event->name = $request->input('name');
        $event->description = $request->input('description');
        $event->location = $request->input('location');
        $event->start_timestamp = $request->input('start_timestamp');
        $event->end_timestamp = $request->input('end_timestamp');
        $event->creator_id = Auth::user()->user_id; 
    
        $event->save();
    
        return redirect('/my-events');
    }
    

    public function updateEvent(Request $request, $id)
    {
        $request->validate([
            'edit_name' => 'required:edit_name|string|max:255',
            'edit_description' => 'nullable|string',
            'edit_location' => 'required|string',
            'edit_start_timestamp' => 'required|date',
            'edit_end_timestamp' => 'required|date|after:edit_start_timestamp',
           
        ], [
            'edit_end_timestamp.after' => 'The end timestamp must be a date after the start timestamp.',
            'edit_name.required' => 'Cannot have an empty name',
         
        ]);
    
        $event = Event::findOrFail($id);

        $this->authorize('updateEvent', $event); 
    
        $event->name = $request->input('edit_name');
        $event->description = $request->input('edit_description');
        $event->location = $request->input('edit_location');
        $event->start_timestamp = $request->input('edit_start_timestamp');
        $event->end_timestamp = $request->input('edit_end_timestamp');
    
        $event->save();
    
        return redirect()->route('view-event', ['id' => $id]);
    }

    public function deactivateEvent($eventId)
    {
        $event = Event::findOrFail($eventId);

        $event->private = true;
        $event->save();
        return response()->json(['message' => 'deactivated successfully']);
    }

    public function activateEvent(Request $request, $eventId)
    {
        $event = Event::findOrFail($eventId);

        $event->private = false;
        $event->save();
        return response()->json(['message' => 'activated successfully']);
    }

    public function createTicketType(Request $request, Event $event)
    {
        $this->authorize('updateEvent', $event);

        $request->validate([
            'ticket_name' => 'required|string|max:255',
            'ticket_stock' => 'required|integer|min:0',
            'ticket_description' => 'nullable|string',
            'ticket_person_limit' => 'required|integer|min:0',
            'ticket_price' => 'required|numeric|min:0',
            'ticket_start_timestamp' => 'required|date',
            'ticket_end_timestamp' => 'required|date|after:ticket_start_timestamp',
        ]);


        $ticketType = new TicketType();
        $ticketType->name = $request->input('ticket_name');
        $ticketType->stock = $request->input('ticket_stock');
        $ticketType->description = $request->input('ticket_description');
        $ticketType->person_buying_limit = $request->input('ticket_person_limit');
        $ticketType->price = $request->input('ticket_price');
        $ticketType->start_timestamp = $request->input('ticket_start_timestamp');
        $ticketType->end_timestamp = $request->input('ticket_end_timestamp');

        $ticketType->event()->associate($event);
        $ticketType->save();

        return response()->json(['message' => 'TicketType created successfully', 'ticketType' => $ticketType]);
    }

    public function showCreateEvent()
    {
        $user = Auth::user();
        $notifications = $user ? $user->notifications : [];
        return view('pages.create_event', compact('notifications'));
    }


    public function purchaseTickets(){
        $user = session('purchase_user');
        session()->forget('purchase_user');
        $quantities = session('purchase_quantities');
        session()->forget('purchase_quantities');
    
        if (empty(array_filter($quantities, function ($quantity) {
            return $quantity > 0;
        }))) {
            return redirect()->route('checkout')->with('error', 'Select at least one ticket type.');
        }
    
        $order = new TicketOrder();
        $order->timestamp = now();
        $order->buyer_id = $user->user_id;
        $order->save();
    
        foreach ($quantities as $ticketTypeId => $quantity) {
            if ($quantity > 0) {
                $ticketType = TicketType::findOrFail($ticketTypeId);
                $event = Event::findOrFail($ticketType->event_id);
    
                // Authorize the sale for each ticket type and corresponding event
                //$this->authorize('purchaseTickets', [$event, $ticketType]);
    
                for ($i = 0; $i < $quantity; $i++) {
                    $ticketInstance = new TicketInstance();
                    $ticketInstance->ticket_type_id = $ticketTypeId;
                    $ticketInstance->order_id = $order->order_id;
                    $qrCodePath = $this->generateQRCodePath($ticketInstance);
                    $ticketInstance->qr_code_path = $qrCodePath;
                    $ticketInstance->save();
                    Mail::to($user->email)->send(new TicketPurchaseConfirmation($ticketInstance));
                }
            }
        }


        session()->forget('cart');

        if (Auth::check() && $user->temporary) {
            $user->temporary = false;
            Auth::logout();
        }
        return redirect()->route('my-tickets')->with('success', 'Tickets purchased successfully.');
    }


    public function searchEvents(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $notifications = $user->notifications;

            $query = $request->input('query');

            $events = Event::whereRaw('tsvectors @@ to_tsquery(\'english\', ?)', [$query])
                ->orderByRaw('ts_rank(tsvectors, to_tsquery(\'english\', ?)) DESC', [$query])
                ->paginate(10);

            return view('pages.all_events', compact('events', 'notifications'));
        }
        else {

            $query = $request->input('query');

            $events = Event::whereRaw('tsvectors @@ to_tsquery(\'english\', ?)', [$query])
                ->orderByRaw('ts_rank(tsvectors, to_tsquery(\'english\', ?)) DESC', [$query])
                ->paginate(10);

            return view('pages.all_events', compact('events'));
        } 
    }  

    private function generateQRCodePath(TicketInstance $ticketInstance)
    {
        $qrCode = QrCode::format('png')
            ->size(300)
            ->generate(route('my-tickets', ['id' => $ticketInstance->id]));

        $filename = $ticketInstance->id . $ticketInstance->order_id . '_qrcode.png';
        $path = storage_path('app/public/qrcodes/' . $filename);

        Storage::disk('public')->put('qrcodes/' . $filename, $qrCode);

        return 'qrcodes/' . $filename;
    }

    public function show($eventId)
    {
        $user = Auth::user();
        $event = Event::findOrFail($eventId);
        $soldTickets = $event->soldTickets();
        $totalSoldTickets = $soldTickets->count();

        $notifications = $user ? $user->notifications : [];

        return view('pages.event', compact('event', 'soldTickets', 'notifications', 'totalSoldTickets'));
    }

    public function calculateAndDisplayRevenue($eventId)
    {
        $event = Event::findOrFail($eventId);
        $revenue = $event->calculateRevenue();

        return view('event.revenue', ['event' => $event, 'revenue' => $revenue]);
    }
    public function charts(Request $request, $eventId) {
        $event = Event::findOrFail($eventId);
    
        if ($request->has('type')) {
            $type = $request->type;
    
            if ($type == 'tickets_chart') {
                $chartData = $event->tickets_chart();
                return response()->json(['moucho' => $chartData]);
            } else if ($type == 'all_tickets_chart') {
                $chartData = $event->all_tickets_chart();
                return response()->json(['moucho' => $chartData]);
            } elseif ($type == 'distribution') {
                $pieChartData = $event->tickets_pie_chart();
                return response()->json(['moucho' => $pieChartData]);
            }
        } elseif ($request->has('canvaId')) {
            $subtype = $request->canvaId; 
            $canvas = $request->canva;  
            $pieChartsData = $event->per_sold_tickets_pie_chart($subtype);
            return response()->json(['moucho' => $pieChartsData, 'chart_id' => $subtype, 'canvas' => $canvas]);
        }
    
        return response()->json(['error' => 'Invalid chart type']);
    }  
}
?>