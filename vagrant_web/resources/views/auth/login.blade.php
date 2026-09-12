@extends('layouts.app')

@section('content')
<div class="row justify-content-center py-4 py-md-5">
    <div class="col-12 col-md-10 col-lg-8 col-xl-6">
        <h1 class="display-6 text-center mb-4">{{ config('app.name', 'Materialpool') }}</h1>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h2 class="h4 mb-0">Login</h2>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">E-Mail Address</label>
                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">

                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">

                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check mb-4">
                        <input id="remember" class="form-check-input" type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label" for="remember">Remember Me</label>
                    </div>

                    <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2">
                        <button type="submit" class="btn btn-primary">Login</button>

                        <a class="btn btn-link px-0 px-sm-2 text-start" href="{{ route('password.request') }}">
                            Forgot Your Password?
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
