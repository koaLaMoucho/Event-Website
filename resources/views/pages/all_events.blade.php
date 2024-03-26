<script type="text/javascript" src={{ url('js/paginate.js') }} defer></script>

@extends('layouts.app')

@section('content')
    <h1>Featured <span>Events</span></h1>
    <section class="centered-section-fluid text-center mx-auto">
        <form class="row justify-content-center" method="GET" action="{{ route('search-events') }}">
            <div class="col-sm-8 col-md-8 mb-3">
                <input class="form-control search-bar" type="text" name="query" placeholder="Search events...">
            </div>
            <div class="col-sm-4 col-md-4 mb-3">
                <button class="btn btn-primary btn-block" type="submit">Search</button>
            </div>
        </form>
    </section>
    <section class="mt-4"></section>
    <section class="all-events-container" id="event-cards-section">
        @include('partials.event_cards')
    </section>
    
@endsection

