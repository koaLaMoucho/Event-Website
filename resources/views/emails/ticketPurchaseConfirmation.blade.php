<!-- ticketPurchaseConfirmation.blade.php -->

<p>Dear User,</p>

<p>Thank you for your purchase! Your ticket purchase was successful.</p>

<p>Details:</p>
<ul>
    <li>Ticket Name: {{ $ticketInstance->ticketType->event->name }}</li>
    <li>Ticket Description: {{ $ticketInstance->ticketType->event->description }}</li>
    <li>Ticket Price: {{ $ticketInstance->ticketType->price }}</li>
    <li>Event Start: {{ $ticketInstance->ticketType->event->start_timestamp }}</li>
    <li>Event End: {{ $ticketInstance->ticketType->event->end_timestamp }}</li>
    <p><img src="{{ $message->embed(storage_path('app/public/' . $ticketInstance->qr_code_path)) }}" alt="QR Code"></p>
</ul>

<p>Feel free to contact us if you have any questions.</p>

<p>Best regards,<br>
ShowsMe</p>

