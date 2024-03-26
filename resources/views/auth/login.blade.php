@extends('layouts.app')

@section('content')
<section class="auth-section container-fluid">
    <form class="auth-form" method="POST" action="{{ route('login') }}">
        <h2 class="text-center">Login</h2>
        <div class="mt-4"></div>
        {{ csrf_field() }}
        <div class="text-center"><label for="email">Email</label></div>
        <div class="my-input-group">
           
     
        <div class="icon-input">
            <i class="fas fa-envelope fa-lg"></i>
            <input id="email" type="email" placeholder="Type your email" name="email" value="{{ old('email') }}" required autofocus>
        </div>
      
            @if ($errors->has('email'))
                <span class="error">
                    {{ $errors->first('email') }}
                </span>
            @endif
        </div>
        <div class="text-center">
        <label for="password">Password</label>
        </div>
        <div class="my-input-group">
           
        <div class="icon-input">
            <i class="fas fa-lock fa-lg"></i>
            <input id="password" type="password" placeholder="Type your password" name="password" required>
        </div>
            @if ($errors->has('password'))
                <span class="error">
                    {{ $errors->first('password') }}
                </span>
            @endif
        </div>
        
        <div class="text-center">
            <button class="btn btn-primary btn-lg" type="submit">
                Login
            </button>
        </div>
        
        <p class="auth-message">
            Don't have an account? <a href="{{ route('register') }}" class="text-primary">Register here</a>.
        </p>
        <div class="text-center mt-3">
            <a href="{{ route('password.forgot') }}" class="btn btn-link" style="text-decoration: none;">
                Forgot your password?
            </a>
        </div>
    </form>
</section>
@endsection
