@extends('layouts.app')

@section('content')
<section class="auth-section container-fluid">
    <form class="auth-form" method="POST" action="{{ route('password.email')}}">
        <h2 class="text-center">Forgot Your Password?</h2>
        {{ csrf_field() }}

        <div class="text-center">
            <label for="email">Email</label>
        </div>
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
            <button class="btn btn-primary btn-lg" type="submit"  onclick="showSuccessAlert()">
                Send Password Reset Link
            </button>
        </div>
    </form>
</section>
@endsection
