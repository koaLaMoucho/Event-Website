<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TicketInstance;
use App\Models\TicketType;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Response;

class TicketController extends Controller
{

    public function updateTicketStock(Request $request, $ticketTypeId)
    {
        try {
            $ticketType = TicketType::findOrFail($ticketTypeId);
            $ticketType->stock = $request->input('new_stock_' . $ticketTypeId);
            $ticketType->save();

            return response()->json(['new_stock_' . $ticketTypeId => $ticketType->stock]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error updating stock: ' . $e->getMessage()], 500);
        }
    }

    public function myTickets(): View
    {
        $user = Auth::user();
        $notifications = $user->notifications;
        $ticketInstances = TicketInstance::with(['order', 'ticketType.event'])
            ->whereHas('order', function ($query) {
                $query->where('buyer_id', Auth::user()->user_id);
            })->get();

        return view('pages.my_tickets', compact('ticketInstances', 'notifications'));
    }

    
}
