@extends('layouts.app')  


@section('content')

<section id="payment">
    <form action="{{ route('payment') }}" method="POST" id="payment-form">
        @csrf

        <div class="form-group">
            <label for="amount">Amount (in cents):</label>
            <input type="text" name="amount" id="amount" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="text" name="email" id="email" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="card-element">Credit or debit card:</label>
            
        </div>

        <div id="card-errors" class="alert alert-danger" role="alert">

        
        </div>

        <button type="submit" class="btn btn-primary">Submit Payment</button>
    </form>
</section>

@endsection
