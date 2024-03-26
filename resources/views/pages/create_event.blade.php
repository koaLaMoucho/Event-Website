<!-- resources/views/pages/create_event_form.blade.php -->

@extends('layouts.app')

@section('content')

@auth
<section id="create-event-content">
    <h2>Create <span>Event</span></h2>

    <div class="progress" id="progress-bar-container">
        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0"
            aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div>
    </div>


    <form method="POST" id="create-event-form" action="{{ url('/create-event') }}">
        @csrf

        <div id="create_name" class="form-group">
            <input type="text" class="form-control form-field" id="name" name="name" placeholder="Event name" required>
        </div>
        @error('name')
        <span class="text-danger">{{ $message }}</span>
        @enderror
        </div>

        <div id="create_descr" class="form-group">
            <textarea id="description" class="form-control form-field" placeholder="Description"
                name="description" required> </textarea>
            @error('description')
            <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <div id="create_local" class="form-group">
            <input type="text" class="form-control form-field" id="location" name="location" placeholder="Location"
                required>
            @error('location')
            <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <div id="create_sdate" class="form-group">
            <label for="start_timestamp" class="form-label mt-4">Start Timestamp:</label>
            <input id="ticket_start_timestamp" type="datetime-local" class="form-control form-field"
                name="start_timestamp" required min="{{ now()->format('Y-m-d\TH:i') }}">
            @error('start_timestamp')
            <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <div id="create_edate" class="form-group">
            <label for="end_timestamp" class="form-label mt-4">End Timestamp:</label>
            <input id="ticket_end_timestamp" type="datetime-local" class="form-control form-field" name="end_timestamp"
                required min="{{ now()->format('Y-m-d\TH:i') }}">
            @error('end_timestamp')
            <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <button class="btn btn-primary" type="submit">Create Event</button>

    </form>

    @else
    <div class="container">
        <div class="row">
            <section class="warning-section text-center">
                <i class="fa-solid fa-circle-exclamation fa-3x" aria-label="Circle"></i>
                <p class="text-sm">Junta-te a nós e cria os teus eventos!</p>
                <p class="text-sm">Deves fazer <a href="{{ route('login') }}" class="auth-link">login </a> primeiro.</p>
            </section>
        </div>
    </div>
</section>
@endauth
@endsection