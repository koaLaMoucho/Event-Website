<script type="text/javascript" src={{ url('js/event.js') }} defer></script>
@extends('layouts.app')



@section('content')

<h1 id="event-name">{{ $event->name }}</h1>

@if(auth()->user() && auth()->user()->is_admin)
<button class="event-button {{ $event->private ? 'active' : '' }}" id="activate-button"
    data-id="{{ $event->event_id }}">{{ $event->private ? 'Activate Event' : 'Deactivate Event' }}</button>
@endif


<div id="tab_bar" class="container-fluid">
    <div class="d-flex justify-content-center text-center">
        <div class="btn-group" role="group" aria-label="Basic radio toggle button group">
            <input type="radio" class="btn-check" name="sectionToggle" id="eventOverview" autocomplete="off" checked
                data-section-id="event-overview">
            <label class="btn btn-outline-primary" for="eventOverview">Overview</label>
            <input type="radio" class="btn-check" name="sectionToggle" id="ticketTypes" autocomplete="off" checked
                data-section-id="ticket-types">
            <label class="btn btn-outline-primary" for="ticketTypes">Tickets</label>
            <input type="radio" class="btn-check" name="sectionToggle" id="eventComments" autocomplete="off"
                data-section-id="event-comments">
            <label class="btn btn-outline-primary" for="eventComments">Comments</label>
            @can('updateEvent', $event)
            <input type="radio" class="btn-check" name="sectionToggle" id="editEvent" autocomplete="off"
                data-section-id="edit-event">
            <label class="btn btn-outline-primary" for="editEvent">Edit</label>

            <input type="radio" class="btn-check" name="sectionToggle" id="createTicketType" autocomplete="off"
                data-section-id="create-ticket-type">
            <label class="btn btn-outline-primary" for="createTicketType">Create Tickets</label>
            <input type="radio" class="btn-check" name="sectionToggle" id="eventInfo" autocomplete="off"
                data-section-id="event-info">
            <label class="btn btn-outline-primary" for="eventInfo">Info</label>
            @endcan
        </div>
    </div>
</div>

<section id="event-overview" class="event-section">


    <section id="event-slider">
        <div class="swiper" id="event-swiper">
            <div class="swiper-wrapper">
                @if (count($event->images) === 0)
                <div id="swiper-slide" class="swiper-slide">
                    <figure>
                        <img src="{{ asset('media/event_image.jpg') }}" alt="Default Image">
                        <figcaption>
                            Image Test
                        </figcaption>
                    </figure>
                    <section class="overview-info">
                        <p id="description">{{ $event->description }}</p>
                        <section class="ratings-event">
                            <p id="average-rating"> {{ number_format($event->averageRating, 1) }} <span
                                    class="star-icon">★</span></p>
                        </section>
                        <p id="location"> {{ $event->location }}</p>
                        <p id="ticket_start_date">Start: {{ $event->start_timestamp->format('H:i d/m ') }}</p>
                        <p id="ticket_end_date">End: {{ $event->end_timestamp->format('H:i d/m') }}</p>
                    </section>
                </div>
                @else
                @foreach ($event->images as $image)
                <div id="swiper-slide" class="swiper-slide">
                    <figure>
                        <img src="{{ \App\Http\Controllers\FileController::get('event_image', $image->event_image_id) }}"
                            data-event-image-id="{{ $image->event_image_id }}" alt="Event Image">
                    </figure>
                    <section class="overview-info">
                        <p id="description">{{ $event->description }}</p>
                        <section class="ratings-event">
                            <p id="average-rating"> {{ number_format($event->averageRating, 1) }} <span
                                    class="star-icon">★</span></p>
                        </section>
                        <p id="location"> {{ $event->location }}</p>
                        <p id="ticket_start_date">Start: {{ $event->start_timestamp->format('H:i d/m ') }}</p>
                        <p id="ticket_end_date">End: {{ $event->end_timestamp->format('H:i d/m') }}</p>
                    </section>
                </div>
                @endforeach
                @endif
            </div>
            <div class="swiper-custom-nav">
                <i id="nav-left" aria-label="Left" class="fa-solid fa-circle-arrow-left"></i>
                <i id="nav-right" aria-label="Rigth" class="fa-solid fa-circle-arrow-right"></i>
            </div>
            <div class="swiper-custom-pagination"></div>
        </div>
    </section>
</section>

<section id="event-comments" class="event-section">
    <h2 class="text-center">Event <span>Comments</span></h2>

    <div class="comments-area">
        @if(auth()->user())
        @if($userRating = $event->userRating())
        <p id="yourRatingP" class="text-center">
            Your Rating: {{ $userRating->rating }}
            <span class="star-icon">★</span>
            <button class="btn btn-primary" onclick="showEditRatingForm()">Edit</button>
        </p>
        <div class="centered-form">
            <form id="editRatingForm" class="rate" method="POST"
                action="{{ route('editRating', ['eventId' => $event->event_id]) }}" style="display: none;">
                @csrf


                <input type="radio" name="rate" id="star5" value="5" {{ $userRating->rating == 5 ? 'checked' : '' }}>
                <label for="star5">5 stars</label>


                <input type="radio" name="rate" id="star4" value="4" {{ $userRating->rating == 4 ? 'checked' : '' }}>
                <label for="star4">4 stars</label>

                <input type="radio" name="rate" id="star3" value="3" {{ $userRating->rating == 3 ? 'checked' : '' }}>
                <label for="star3">3 stars</label>

                <input type="radio" name="rate" id="star2" value="2" {{ $userRating->rating == 2 ? 'checked' : '' }}>
                <label for="star2">2 stars</label>

                <input type="radio" name="rate" id="star1" value="1" {{ $userRating->rating == 1 ? 'checked' : '' }}>
                <label for="star1">1 star</label>

                <br>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
        @else
        <p class="text-center rate"> Give us your Rating: </p>
        <div class="centered-form">
            <form id="ratingForm" class="rate" method="POST"
                action="{{ route('submitRating', ['eventId' => $event->event_id]) }}">
                @csrf

                <input type="radio" name="rate" id="star5" value="5">
                <label for="star5">5 stars</label>


                <input type="radio" name="rate" id="star4" value="4">
                <label for="star4">4 stars</label>

                <input type="radio" name="rate" id="star3" value="3">
                <label for="star3">3 stars</label>

                <input type="radio" name="rate" id="star2" value="2">
                <label for="star2">2 stars</label>

                <input type="radio" name="rate" id="star1" value="1">
                <label for="star1">1 star</label>

                <div class="text-center">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
        @endif
        @endif


        <h2 class="text-primary text-center">Comments</h2>
        @if(auth()->check())
        <form id="newCommentForm" action="{{ route('submitComment') }}" method="post">
            @csrf
            <div class="comment new-comment">
                <textarea name="newCommentText" id="newCommentText" class="new-comment-textbox" rows="3"
                    placeholder="Write a new comment" required></textarea>

            </div>

            <input id="newCommentEventID" type="hidden" name="event_id" value="{{$event->event_id}}">
            <button onclick="addNewComment()" class="btn btn-primary" id="submit-comment-button">Submit Comment</button>
        </form>
        @endif
        <div id="app" data-base-url="{{ asset('') }}"></div>
        <div id="public-comments-section" class="commentsContainer">
            @foreach($event->comments->where('private', false) as $comment)
            <div class="comment" data-id="{{ $comment->comment_id }}">
                <div class="comment-icons-container">
                    <div class="photo-and-name">
                        @if($comment->author->profile_image != null)
                        <img id="profile-image-comment"
                            src="{{ \App\Http\Controllers\FileController::get('profile_image', $comment->author->user_id) }}"
                            alt="Profile Image">
                        @else
                        <img id="profile-image-comment" src="{{ asset('media/default_user.jpg') }}"
                            alt="Default Profile Image">
                        @endif
                        <p class="comment-author">{{ $comment->author->name }}</p>
                    </div>
                    <div>
                        @if(auth()->check())
                        @if((!$comment->isReported())&& (auth()->user()->user_id !== $comment->author->user_id))
                        <i class="fa-solid fa-flag" aria-label="Report Flag" onclick="showReportPopUp()"></i>
                        @endif
                        @if(auth()->user()->user_id === $comment->author->user_id)
                        <i class="fa-solid fa-pen-to-square" aria-label="Edit" onclick="showEditCommentModal()"></i>
                        @endif
                        @if(auth()->user() && auth()->user()->is_admin || auth()->user()->user_id ===
                        $comment->author->user_id)
                        <i class="fa-solid fa-trash-can" aria-label="Delete" onclick="confirmDeleteComment()"></i>
                        @endif
                        @endif
                    </div>
                </div>
                <p class="comment-text" id="commentText">{{ $comment->text }}</p>

                <form id="editCommentForm" style="display: none;">
                    <textarea id="editedCommentText" class="edit-comment-textbox" rows="3"
                        required>{{ $comment->text }}</textarea>
                    <button class="btn btn-primary" onclick="editComment()">Submit</button>
                    <button type="button" class="btn btn-danger" onclick="hideEditCommentModal()">Cancel</button>
                </form>

                <form id="confirmDeleteCommentForm" style="display: none;">
                    <p id="deleteCommentText" class="text-danger"> Are you sure you want to delete your comment?</p>
                    <button class="btn btn-danger" onclick="deleteComment()">Delete</button>
                    <button type="button" class="btn btn-primary" onclick="hideDeleteCommentModal()">Cancel</button>
                </form>

                <div class="comment-likes-section">
                    @if(auth()->check())
                    @if(auth()->user()->likes($comment->comment_id))
                    <i class="fas fa-thumbs-up fa-solid" aria-label="Dislike" id="liked" onclick="unlikeComment()"></i>
                    @else
                    <i class="far fa-thumbs-up fa-regular" aria-label="Like" id="unliked" onclick="likeComment()"></i>
                    @endif
                    @else
                    <i class="far fa-thumbs-up fa-regular" aria-label="Like" id="unliked"
                        onclick="redirectToLogin()"></i>
                    @endif
                    <p class="comment-likes">{{ $comment->likes }}</p>

                </div>

                <form id="confirmDeleteCommentForm" style="display: none;">
                    <p class="text-danger">Are you sure you want to delete your comment?</p>
                    <button class="btn btn-danger" onclick="deleteComment()">Delete</button>
                    <button type="button" class="btn btn-primary" onclick="hideDeleteCommentModal()">Cancel</button>
                </form>

            </div>
            @endforeach
        </div>

        <div class="pop-up-report">
            <div class="report-section">
                <h3>Why are you reporting?</h3>


                <form action="{{ route('submitReport') }}" method="post">
                    @csrf
                    <input type="hidden" name="comment_id" id="reportCommentId" value="0">
                    <textarea id="reportReason" class="report-textbox" name="reportReason" rows="4"
                        placeholder="Enter your reason here"></textarea>
                    <button type="submit" class="btn btn-primary">Submit Report</button>
                </form>
            </div>
        </div>

    </div>

    <div class="my-event-card">
            @if ($event->images->isNotEmpty())
                <div class="event-image" style="background-image: url('{{ \App\Http\Controllers\FileController::get('event_image', $event->images->first()->event_image_id) }}');"></div>
            @else
                <div class="event-image" style="background-image: url('{{ asset('media/event_image.jpg') }}');"></div>
            @endif        <a href="{{ route('view-event', ['id' => $event->event_id]) }}" class="my-event-info">
            <p id="my-event-card-local">{{ $event->location }}</p>
            <p id="my-event-card-name">{{ $event->name }}</p>
            <p id="my-event-card-date">{!! $event->start_timestamp->format('H:i, F j') !!}<br></p>
            <p id="average-rating"> {{ number_format($event->averageRating, 1) }} <span class="star-icon">★</span></p>
        </a>
    </div>
</section>

<section id="ticket-types" class="event-section">
    <h2 id="ticket_types_title" class="text-center">Ticket <span>Types</span></h2>
    @if(count($event->ticketTypes) > 0)
    <form method="POST" action="{{ url('/cart/'.$event->event_id)  }}">
        @csrf
        <br>
        <div id="ticket-types-container" class="text-center">
            @foreach ($event->ticketTypes as $ticketType)
            <article class="ticket-type" id="ticket-type-{{$ticketType->ticket_type_id}}"
                data-max="{{ min($ticketType->person_buying_limit, $ticketType->stock) }}">
                <div class="ticket_first_area">
                    <p id="ticket_type_name">{{ $ticketType->name }}</p>
                    <p id="event_description_{{ $ticketType->ticket_type_id }}"> {{ $ticketType->description}}</p>
                    <p id="ticket_logo">show<span>s</span>me</p>
                    <p id="ticket_start_date">Start: {{ $ticketType->start_timestamp->format('H:i d/m ') }}</p>
                    <p id="ticket_end_date">End: {{ $ticketType->end_timestamp->format('H:i d/m') }}</p>
                    <p id="ticket_price_{{ $ticketType->ticket_type_id }}"> {{ $ticketType->price }}€</p>
                </div>

                <div class="line"></div>

                <div class="ticket_second_area">
                    @if (auth()->user() && auth()->user()->user_id == $event->creator_id)
                    @csrf
                    <div class="ticket_second_area_stock">
                        <div class="input_stock numeric-input">
                            <p>Stock</p>
                            <button class="btn-decrement">-</button>
                            <input type="number" id="new_stock_{{ $ticketType->ticket_type_id }}" name="new_stock"
                                value="{{ $ticketType->stock }}" required>
                            <button class="btn-increment">+</button>
                        </div>
                        <button class="button-update-stock" onclick="updateStock({{ $ticketType->ticket_type_id }})"
                            form="purchaseForm">Update Stock</button>
                    </div>
                    @endif
                    @if ($ticketType->stock > 0)
                    <div class="ticket_second_area_quanti">
                        <label class="quant" id="label{{$ticketType->ticket_type_id}}"
                            for="quantity_{{ $ticketType->ticket_type_id }}">Quantity</label>

                        <div class="input_quanti numeric-input">
                            <button class="btn-increment">+</button>
                            <input class="quant" id="input{{$ticketType->ticket_type_id}}" type="number"
                                id="quantity_{{ $ticketType->ticket_type_id }}"
                                name="quantity[{{ $ticketType->ticket_type_id }}]" min="0"
                                max="{{ min($ticketType->person_buying_limit, $ticketType->stock) }}" value="0" required autofocus>
                            <button class="btn-decrement">-</button>
                        </div>

                    </div>
                    @endif
                </div>
            </article>
            @endforeach
        </div>
        <div class="d-flex justify-content-center">
            <button type="submit" class="btn btn-success event-button" id="buy-button">
                <i class="fa-solid fa-cart-shopping" aria-label="Shopping Cart"></i> Add To Cart
            </button>
        </div>
    </form>
    @else
    <h2 id="no_ticket_types">No Tickets available, come back later!</h2>
    @endif

    <div class="my-event-card">
        <div class="event-image"
            style="background-image: url('@if($event->images->isNotEmpty()){{ \App\Http\Controllers\FileController::get('event_image', $event->images->first()->event_image_id) }}@else{{ asset('media/event_image.jpg') }}@endif');">
        </div>
        <a href="{{ route('view-event', ['id' => $event->event_id]) }}" class="my-event-info">
            <p id="my-event-card-local">{{ $event->location }}</p>
            <p id="my-event-card-name">{{ $event->name }}</p>
            <p id="my-event-card-date">{!! $event->start_timestamp->format('H:i, F j') !!}<br></p>
            <p id="average-rating">{{ number_format($event->averageRating, 1) }} <span class="star-icon">★</span></p>
        </a>
    </div>

</section>


@can('updateEvent', $event)
<section id="edit-event" class="event-section">
    <h2>Edit <span>Event</span></h2>
    <section id="edit-event-images">
        @foreach ($event->images as $image)
        <div class="image-container">
            <img src="{{ \App\Http\Controllers\FileController::get('event_image', $image->event_image_id) }}"
                alt="Event Image">
            <i class="fa-solid fa-trash" data-event-image-id="{{ $image->event_image_id }}"
                onclick="deleteImage(this)"></i>
        </div>
        @endforeach

        <form id="upload-form" method="POST" action="/file/upload" enctype="multipart/form-data">
            @csrf
            <div class="image-container add-image" id="file-container">
                <label for="file-input" class="add-image-label">Click to add image</label>
                <input id="file-input" name="file" type="file" required>
                <input name="id" type="number" value="{{ $event->event_id }}" hidden>
                <input name="type" type="text" value="event_image" hidden>
            </div>
            <button type="submit" id="submit-btn">Submit</button>
        </form>
    </section>

    <article class="edit-or-create" id="create-event-form">
        <div id="create_name">
            <input type="text" id="edit_name" name="edit_name" value="{{ $event->name }}" required
                placeholder="Event name">
        </div>

        <div id="create_descr">
            <textarea id="edit_description" name="edit_description" placeholder="Description"
                required>{{ $event->description }}</textarea>
        </div>

        <div id="create_local">
            <input type="text" id="edit_location" name="edit_location" value="{{ $event->location }}"
                placeholder="Location" required>
        </div>

        <div id="create_sdate">
            <label for="edit_start_timestamp">Start Timestamp:</label>
            <input type="datetime-local" id="edit_start_timestamp" name="edit_start_timestamp"
                value="{{ $event->start_timestamp }}" required>
        </div>


        <div id="create_edate">
            <label for="edit_end_timestamp">End Timestamp:</label>
            <input type="datetime-local" id="edit_end_timestamp" name="edit_end_timestamp"
                value="{{ $event->end_timestamp }}" required>
        </div>

        @error('edit_end_timestamp')
        <span class="text-danger">{{ $message }}</span>
        @enderror
        <button class="button-update-event" onclick="updateEvent({{ $event->event_id }})">Update Event</button>
    </article>
</section>

<section id="create-ticket-type" class="event-section ">
    <h2>Create <span> TicketType </span> </h2>
    <article class="create-ticket-instance">
        @csrf
        <div class="first_area">
            <input type="text" id="ticket_name" name="ticket_name" placeholder="Ticket Name" required>

            <textarea id="ticket_description" name="ticket_description" placeholder="Description"></textarea>

            <p id="ticket_logo">show<span>s</span>me</p>

            <label for="ticket_start_timestamp" id="start_date_info">Start</label>
            <input type="datetime-local" id="ticket_start_timestamp" name="ticket_start_timestamp" required>

            <label for="ticket_end_timestamp" id="end_date_info">End</label>
            <input type="datetime-local" id="ticket_end_timestamp" name="ticket_end_timestamp" required>
        </div>


        <div class="line"></div>

        <div class="second_area">

            <div id="div_ticket_price">
                <label for="ticket_price">Price</label>
                <div class="numeric-input">
                    <button class="btn-increment">+</button>
                    <input type="number" id="ticket_price" name="ticket_price" placeholder="€" required>
                    <button class="btn-decrement">-</button>
                </div>
            </div>


            <div id="div_ticket_stock">
                <label for="ticket_stock">Stock</label>
                <div class="numeric-input">
                    <button class="btn-increment">+</button>
                    <input type="number" id="ticket_stock" name="ticket_stock" required>
                    <button class="btn-decrement">-</button>
                </div>
            </div>

            <div id="div_ticket_person_limit">
                <label for="ticket_person_limit">Max per Person</label>
                <div class="numeric-input">
                    <button class="btn-increment">+</button>

                    <input type="number" id="ticket_person_limit" name="ticket_person_limit" required>
                    <button class="btn-decrement">-</button>
                </div>
            </div>

        </div>
        @error('ticket_end_timestamp')
        <span class="text-danger">{{ $message }}</span>
        @enderror 

        <button type="button" class="btn btn-primary" onclick="createTicketType({{ $event->event_id }})">Create
            TicketType</button>
    </article>
</section>

<section class="event-section event-info-content" id="event-info">

    @if ($totalSoldTickets == 0)
    <h2 class="text-center no-tickets-stat">No tickets sold yet,come back later!</h2>
    @else
    <h2 id="hist">Histórico de Compras</h2>

    <table class="hist-compras">
        <thead>
            <tr>
                <th>Nº</th>
                <th>Data</th>
                <th>Bilhetes</th>
                <th>Valor Total</th>
                <th>Tipos</th>
            </tr>
        </thead>
        <tbody>
            @php
            $totalSoldTickets = 0;
            $purchaseNumber = 1;
            @endphp

            @foreach ($soldTickets->groupBy('order_id') as $orderTickets)
            @php
            $order = $orderTickets->first()->order;
            @endphp

            <tr class="see-all-buyed-row">
                <td>{{ $purchaseNumber }}</td>
                <td>{{ $order->timestamp->format('d/m/Y') }}</td>
                <td>{{ $orderTickets->count() }}</td>
                <td>{{ $order->totalPurchaseAmount }}€</td>
                <td>
                    <p>Details</p>
                    <div class="additional-info" style="display: none;">
                        <div class="div-compra-tipos">
                            @foreach ($orderTickets->groupBy('ticket_type_id') as $ticketType => $typeTickets)
                            @php
                            $ticketTypeName = $typeTickets->first()->ticketType->name;
                            $quantity = $typeTickets->count();
                            @endphp
                            <div>
                                <span>{{ $ticketTypeName }}:</span>
                                <span>{{ $quantity }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </td>
            </tr>


            @php
            $totalSoldTickets += $orderTickets->count();
            $purchaseNumber++;
            @endphp
            @endforeach
        </tbody>
    </table>

    <h2 id="title-charts">Stats</h2>


    <section id="event-charts" data-id="{{ $event->event_id }}">
        <div class="div_dif_tickets_chart">
            <canvas id="dif_tickets_chart"></canvas>
        </div>

        <div class="div_all_tickets_chart">
            <canvas id="all_tickets_chart"></canvas>
        </div>

        <div class="div_myPieChart">
            <canvas id="myPieChart"></canvas>
        </div>

        <div class="perc_sold_tickets_charts" id="">
            @foreach ($event->ticketTypes as $ticketType)
            <canvas id="{{ $ticketType->ticket_type_id }}"></canvas>
            @endforeach
        </div>

    </section>

    <div class="faturacao">
        <p id="revenue">Revenue</p>
        <p id="val_revenue"> {{ $event->calculateRevenue() }}€</p>
        <p id="tickets">Tickets</p>
        <p id="total_tickets"> {{ $totalSoldTickets }} </p>
    </div>

</section>

@endif
@endcan

@endsection