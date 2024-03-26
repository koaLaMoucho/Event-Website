<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\TicketPurchaseConfirmation;
use App\Models\TicketInstance;

class MailController extends Controller
{
    function send(Request $request, TicketInstance $ticketInstance) {

        /*$mailData = [
            'name' => $request->name,
            'email' => $request->email,
        ];*/

        $user = Auth::user();

        if ($user) {
            $mailData = [
                'name' => $request->name,
                'email' => $user->email,
            ];

            Mail::to($user->email)->send(new TicketPurchaseConfirmation($mailData, $ticketInstance));

            return redirect()->back()->with('success', 'Mail sent successfully to ' . $user->email);
        }
    }

}
