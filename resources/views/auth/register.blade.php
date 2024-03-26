@extends('layouts.app')

@section('content')
<section class="auth-section">
    <form class="auth-form" method="POST" action="{{ route('register') }}">
        <h2>Register</h2>
        {{ csrf_field() }}
        <div class="text-center"> <label for="name">Name</label></div>
        <div class="my-input-group">
        
            <div class="icon-input">
                <i class="fas fa-user"></i>
                <input id="name" type="text" placeholder="Type your name" name="name" value="{{ old('name') }}" required autofocus>
            </div>
            @if ($errors->has('name'))
                <span class="error">
                    {{ $errors->first('name') }}
                </span>
            @endif
        </div>
        <div class="text-center"> <label for="email">E-mail</label></div>
        <div class="my-input-group">
         
            <div class="icon-input">
                <i class="fas fa-envelope"></i>
                <input id="email" type="email" placeholder="Type your email" name="email" value="{{ old('email') }}" required>
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
                <i class="fas fa-phone"></i>
                <input id="phone" type="tel" placeholder="Type your phone number" name="phone_number" value="{{ old('phone_number') }}" required pattern="[0-9]{9}">
            </div>
            @if ($errors->has('phone'))
                <span class="error">
                    {{ $errors->first('phone') }}
                </span>
            @endif
        </div>
        <div class="text-center">
        <label for="password">Password</label>
        </div>
        <div class="my-input-group">
         
            <div class="icon-input">
                <i class="fas fa-lock"></i>
                <input id="password" type="password" placeholder="Type your password" name="password" required>
            </div>
            @if ($errors->has('password'))
                <span class="error">
                    {{ $errors->first('password') }}
                </span>
            @endif
        </div>
        <div class="text-center"> <label for="password-confirm">Confirm Password</label></div>
        <div class="my-input-group">
           
            <div class="icon-input">
                <i class="fas fa-lock"></i>
                <input id="password-confirm" type="password" placeholder="Confirm your password" name="password_confirmation" required>
            </div>
        </div>
        <div class="text-center">
        <button class="btn btn-primary" type="submit">
            Register
        </button>
        </div>
        <p class="auth-message">
            Already have an account? <a href="{{ route('login') }}" class="text-primary">Login here</a>.
        </p>
    </form>
</section>
@endsection
