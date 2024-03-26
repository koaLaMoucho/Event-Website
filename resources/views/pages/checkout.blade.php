@extends('layouts.app')

@section('content')
    <section id="checkout">
        <h1><span>Checkout</span></h1>

        <form method="POST" action="{{ route('payment') }}">
            @csrf

            <div id="checkout-items-container" class="text-center">
                @php
                    $checkoutItems = collect($checkoutItems);
                @endphp

                @foreach ($checkoutItems->groupBy('ticketType.event.event_id') as $eventId => $eventCheckoutItems)
                    <div class="checkout-event">
                        <div class="my-tickets-container">

                            <div class="my-tickets-event">
                                @php
                                    $event = $eventCheckoutItems->first()['ticketType']->event;
                                    $eventImages = $event->images;
                                @endphp
                                <div class="event-image"
                                    style="background-image: url('@if($eventImages->isNotEmpty()){{ \App\Http\Controllers\FileController::get('event_image', $eventImages->first()->event_image_id) }}@else{{ asset('media/event_image.jpg') }}@endif');">
                                </div>

                                <a href="{{ route('view-event', ['id' => $event->event_id]) }}" class="event-info">
                                    <h2 id="my-tickets-event-title">{{ $event->name }}</h2>
                                    <p id="my-tickets-event-local">{{ $event->location }}</p>
                                    <p id="my-tickets-event-Sdate">{!! $event->start_timestamp->format('H:i, F j') !!}<br></p>
                                </a>
                            </div>
                            <div class="my-tickets-per-event">
                                @foreach ($eventCheckoutItems as $checkoutItem)
                                    <article class="checkout-item ticket-type"
                                        id="checkout-item-{{ $checkoutItem['ticketType']->ticket_type_id }}"
                                        data-max="{{ min($checkoutItem['ticketType']->person_buying_limit, $checkoutItem['ticketType']->stock) }}">

                                        <div class="ticket_first_area">

                                            <p id="ticket_type_name">{{ $checkoutItem['ticketType']->name }}</p>
                                            <p id="descri">{{ $checkoutItem['ticketType']->description }}</p>
                                            <p id="ticket_logo">show<span>s</span>me</p>
                                            <p id="ticket_start_date">{{ $checkoutItem['ticketType']->start_timestamp->format('H:i') }}</p>
                                            <p id="ticket_end_date">{{ $checkoutItem['ticketType']->end_timestamp->format('H:i') }}</p>
                                            <p id="ticket_price_{{ $checkoutItem['ticketType']->ticket_type_id }}"> {{ $checkoutItem['ticketType']->price }}€</p>
                                        </div>
                                        <div class="line"></div>
                                        <div class="ticket_second_area">
                                            <label class="quant" id="label{{ $checkoutItem['ticketType']->ticket_type_id }}"
                                                for="quantity_{{ $checkoutItem['ticketType']->ticket_type_id }}">Quantity:</label>
                                            <input class="quant" id="input{{ $checkoutItem['ticketType']->ticket_type_id }}"
                                                type="number" id="quantity_{{ $checkoutItem['ticketType']->ticket_type_id }}"
                                                name="quantity[{{ $checkoutItem['ticketType']->ticket_type_id }}]" min="0"
                                                max="{{ min($checkoutItem['ticketType']->person_buying_limit, $checkoutItem['ticketType']->stock) }}"
                                                value="{{ $checkoutItem['quantity'] }}">
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @guest
                <div id="checkout-section" class="auth-form" style="display: none;">
                    <div class="text-center"><label for="name">Name</label></div>
                    <div class="my-input-group">
                        <div class="icon-input">
                            <i class="fas fa-user" aria-label="User"></i>
                            <input id="name" type="text" placeholder="Type your name" name="name"
                                value="{{ old('name') }}" required autofocus>
                        </div>
                        @if ($errors->has('name'))
                            <span class="error">
                                {{ $errors->first('name') }}
                            </span>
                        @endif
                    </div>

                    <div class="text-center"><label for="email">E-mail</label></div>
                    <div class="my-input-group">
                        <div class="icon-input">
                            <i class="fas fa-envelope" aria-label="Envelope"></i>
                            <input id="email" type="email" placeholder="Type your email" name="email"
                                value="{{ old('email') }}" required>
                        </div>
                        @if ($errors->has('email'))
                            <span class="error">
                                {{ $errors->first('email') }}
                            </span>
                        @endif
                    </div>

                    <div class="text-center"><label for="phone">Phone Number</label></div>
                    <div class="my-input-group">
                        <div class="icon-input">
                            <i class="fas fa-phone" aria-label="Phone"></i>
                            <input id="phone" type="tel" placeholder="Type your phone number" name="phone_number"
                                value="{{ old('phone_number') }}" required pattern="[0-9]{9}">
                        </div>
                        @if ($errors->has('phone_number'))
                            <span class="error">
                                {{ $errors->first('phone_number') }}
                            </span>
                        @endif
                    </div>
                </div>
            @endguest

            @php
                $totalQuantity = $checkoutItems->sum('quantity');
            @endphp

            @if ($totalQuantity > 0)
                <br>
                <div class="d-flex justify-content-center">
                    @auth
                        <button type="submit" class="btn btn-success checkout-button" id="checkout-button">
                            <i class="fa-solid fa-credit-card" aria-label="Credit Card"></i> Process Checkout
                        </button>
                    @endauth
                    @guest
                        <button type="button" class="btn btn-success event-button" id="show-form"
                            onclick="toggleCheckoutSection()">
                            <i class="fa-solid fa-cart-shopping" aria-label="Shopping Cart"></i> Process Checkout
                        </button>
                        <button type="submit" class="btn btn-success checkout-button" id="checkout-button"
                            style="display: none;">
                            <i class="fa-solid fa-credit-card" aria-label="Credit Card"></i> Process Checkout
                        </button>
                    @endguest
                </div>
            @else
                <p class="text-center">Your cart is empty. Add items to proceed with checkout.</p>
            @endif
        </form>
    </section>
@endsection
