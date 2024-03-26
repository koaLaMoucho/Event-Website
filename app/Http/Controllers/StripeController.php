<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Models\Event;
use App\Models\TicketType;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\Models\TicketOrder;
use App\Models\TicketInstance;
use Illuminate\Auth\Access\AuthorizationException; 
use App\Http\Controllers\EventController;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash; 


class StripeController extends Controller
{
    public function showPaymentForm()
    {
    
        return view('pages.payment');
    }


    public function processPayment(Request $request){
        
        if (Auth::guest()) {
            $user = $this->createTemporaryAccount($request);
        } 
        else{
            $user = Auth::user();
        }
        
        
        $quantities = $request->input('quantity', []);

        if (empty(array_filter($quantities, function ($quantity) {
            return $quantity > 0;
        }))) {
            return redirect()->route('checkout')->with('error', 'Select at least one ticket type.');
        }

        $lineItems = [];

        foreach ($quantities as $ticketTypeId => $quantity) {
            if ($quantity > 0) {
                $ticketType = TicketType::findOrFail($ticketTypeId);

                $minQuantity = min($ticketType->stock, $ticketType->person_buying_limit);

                if (is_numeric($quantity) && $quantity == (int) $quantity && $quantity > 0 && $quantity <= $minQuantity) {

                    $lineItems[] = [
                        "quantity" => $quantity,
                        "price_data" => [
                            "currency" => "eur",
                            "unit_amount" => $ticketType->price * 100, 
                            "product_data" => [
                                "name" => $ticketType->name,
                            ],
                        ],
                    ];
                }   
                else {
                    return redirect()->route('view-event', ['id' => $eventId])->with('error', 'Invalid quantity for ticket type');
                }
            }
        }

        session(['purchase_user' => $user]);
        session(['purchase_quantities' => $quantities]);
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $checkoutSession = Session::create([
            "mode" => "payment",
            "success_url" => url('/purchase-tickets/'),
            "cancel_url" => url('/checkout/'),
            "line_items" => $lineItems,
        ]);

        return redirect()->to($checkoutSession->url);
    } 

    public function addToCart(Request $request, $eventId){
        $quantities = $request->input('quantity', []);
        $cart = session()->get('cart', []);
    
        foreach ($quantities as $ticketTypeId => $quantity) {
            if ($quantity > 0) {
                $cart[$ticketTypeId] = $quantity;
            }
        }
    
        session()->put('cart', $cart);
        
        return redirect()->route('view-event', ['id' => $eventId])->with('success', 'Tickets successfully added to the cart!');
    }

    public function createTemporaryAccount(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email', 
            'phone_number' => 'required|string',
        ]);
    
        
        $user = User::where('email', $request->input('email'))->first();
    
        if (!$user) {
           
            $randomPassword = Str::random(12);
    
            $user = new User();
            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $user->phone_number = $request->input('phone_number');
            $user->password = Hash::make($randomPassword);
            $user->temporary = true;
            $user->save();
        }
        
        $user->temporary = true;
        Auth::login($user);

        return $user;
    
    }
}
