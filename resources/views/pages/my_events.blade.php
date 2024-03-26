<!-- resources/views/pages/my_events.blade.php -->
<script src={{ url('js/my_events_paginate.js') }} defer></script>

@extends('layouts.app')

@section('content')
<section class="my-events-container">
    @if ($events->count() > 0)
    <h1>My <span>Events</span></h1> 
    <section id="my-event-cards-section">
        @include('partials.my_event_cards')
    </section>
</section>
@else
<div class="my-event-card">
    <section class="warning-section">
        <i class="fa-solid fa-question" aria-label="Question" ></i>
        <p>You haven't created any events yet...</p>
        <a href="{{ route('create-event') }}" class="auth-link">Create your first event</a>
    </section>
</div>
@endif
@endsection