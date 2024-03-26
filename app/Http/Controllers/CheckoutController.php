<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Support\Facades\Auth;


class CheckoutController extends Controller
{
    public function showCheckoutPage()
    {
        $user = Auth::user();
        $cart = session()->get('cart', []);

        $checkoutItems = [];
        $notifications = $user ? $user->notifications : [];

        foreach ($cart as $ticketTypeId => $quantity) {
            $ticketType = TicketType::find($ticketTypeId);
            $event = Event::find($ticketType->event_id); 
            $checkoutItems[] = [
                'ticketType' => $ticketType,
                'quantity' => $quantity,
                'eventName' => $event->name,
            ];
        }

        return view('pages.checkout', compact('checkoutItems', 'notifications'));
    }
}
