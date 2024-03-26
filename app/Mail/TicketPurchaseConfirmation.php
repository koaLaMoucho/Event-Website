<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\TicketInstance;

class TicketPurchaseConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    //public $mailData;
    public $ticketInstance;

    // A
    public function __construct(TicketInstance $ticketInstance)
    {
        $this->ticketInstance = $ticketInstance;
    }

    // B
    public function build()
    {
        return $this->subject('Ticket Purchase')
            ->view('emails.ticketPurchaseConfirmation')
            ->with([
                'ticketInstance' => $this->ticketInstance,
            ]);
    }
}
