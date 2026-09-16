@extends('layouts.app')

@section('content')
<div class="row justify-content-center py-4 py-md-5">
    <div class="col-12 col-md-10 col-lg-8 col-xl-6">
        <h1 class="display-6 text-center mb-4">{{ __('oauth.device.enter_code') }}</h1>

        <div class="card">
            <div class="card-body p-4">
                @if (session('status') === 'authorization-approved')
                    <div class="alert alert-success" role="status">{{ __('oauth.device.approved') }}</div>
                @elseif (session('status') === 'authorization-denied')
                    <div class="alert alert-secondary" role="status">{{ __('oauth.device.denied') }}</div>
                @endif

                <p>{{ __('oauth.device.enter_code_description') }}</p>

                <form method="GET" action="{{ route('passport.device.authorizations.authorize') }}">
                    <div class="mb-4">
                        <label class="form-label" for="user_code">{{ __('oauth.device.user_code') }}</label>
                        <input id="user_code" type="text" class="form-control @error('user_code') is-invalid @enderror" name="user_code" value="{{ old('user_code') }}" required autofocus autocapitalize="characters" autocomplete="one-time-code">

                        @error('user_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">{{ __('oauth.device.continue') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
