@extends('layouts.app')

@section('content')
    <div class="auth-section container-fluid">
        <form class="auth-form"method="POST" action="{{ route('password.update') }}">
            <h2 class="text-center">{{ __('Change Password') }}</h2>
            <div class="mt-4"></div>
                @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        {{ session('status') }}
                    </div>
                @endif
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="form-group row">
                    <div class="text-center col-form-label"><label for="email">Email</label></div>

                    <div class="my-input-group">
                        <input id="email" placeholder="Type your email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" required autocomplete="email" autofocus>

                        @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>
                <div class="form-group row">
                    <label for="password" class="text-center col-form-label">{{ __('Password') }}</label>

                    <div class="my-input-group">
                        <input id="password" placeholder="Type your new password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">

                        @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>

                <div class="form-group row">
                    <label for="password-confirm" class="text-center col-form-label">{{ __('Confirm Password') }}</label>

                    <div class="my-input-group">
                        <input id="password-confirm" placeholder="Retype your new password" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                    </div>
                </div>

                <div class="form-group row mb-0">
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">
                            {{ __('Reset Password') }}
                        </button>
                    </div>

            </div>
        </form>
    </div>
@endsection
