@extends('layouts.app')

@section('content')
<h1>My <span>Tickets</span></h1>
@if(count($ticketInstances) > 0)
    @foreach ($ticketInstances->groupBy('ticketType.event.name') as $eventName => $eventTickets)
    <div class="my-tickets-container">

        <div class="my-tickets-event">
        <div class="event-image" style="background-image: url('@if($eventTickets->first()->ticketType->event->images->isNotEmpty()){{ \App\Http\Controllers\FileController::get('event_image', $eventTickets->first()->ticketType->event->images->first()->event_image_id) }}@else{{ asset('media/event_image.jpg') }}@endif');"></div>
            <a href="{{ route('view-event', ['id' => $eventTickets->first()->ticketType->event->event_id]) }}" class="event-info">
                <h2 id="my-tickets-event-title">{{ $eventName }}</h2>
                <p id="my-tickets-event-local">{{ $eventTickets->first()->ticketType->event->location }}</p>
                <p id="my-tickets-event-Sdate">{!! $eventTickets->first()->ticketType->event->start_timestamp->format('H:i, F j') !!}<br></p>
            </a>

        </div>
        
        <div class="my-tickets-per-event">
            @foreach ($eventTickets as $index => $ticketInstance)
            <article class="ticket-instance">
                <div class="info_area">
                    <p id="tipo">{{ $ticketInstance->ticketType->name }}</p>
                    <p id="hora">{{ $ticketInstance->ticketType->event->start_timestamp->format('H:i') }}</p>
                    <p id="descri">{{ $ticketInstance->ticketType->description }}</p>
                    <p id="num">{{ $index + 1 }}</p>
                    <p id="ticket-logo">show<span>s</span>me</p>
                </div>
                <div class="line"></div>
                <div class="qr_area">
                    @if ($ticketInstance->qr_code_path)
                    {!! QrCode::size(100)->generate($ticketInstance->qr_code_path) !!}
                    @else
                    {!! QrCode::size(100)->generate(Request::input('id', $ticketInstance->ticket_instance_id)) !!}
                    @endif
                </div>
            </article>
            @endforeach
        </div>
        @if ($index > 0)
            <button class="my-tickets-btn my-tickets-btn-see-more">See more</button>
            <button class="my-tickets-btn my-tickets-btn-hidden" style="display: none;">Hide</button>
        @endif

    </div>

    @endforeach
@else
<a href="{{ url('/all-events') }}">
    <article class="ticket-instance">
        
            <div class="info_area text-center">
                <p id="tipo">Click here to buy tickets!</p>
                <p id="ticket-logo">show<span>s</span>me</p>
            </div>
        
        <div class="line"></div>
        <div class="qr_area">

        </div>
       
    </article>
    </a>


@endif
@endsection