@extends('layouts.app')

@section('content')
<div class="row justify-content-center py-4 py-md-5">
    <div class="col-12 col-md-10 col-lg-8 col-xl-6">
        <h1 class="display-6 text-center mb-4">{{ __('oauth.device.approve_heading') }}</h1>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h2 class="h4 mb-0">{{ $client->name }}</h2>
            </div>
            <div class="card-body p-4">
                <p>{{ __('oauth.device.approve_description', ['client' => $client->name]) }}</p>

                <h3 class="h5 mt-4">{{ __('oauth.authorize.permissions') }}</h3>
                @if (count($scopes))
                    <ul class="mb-4">
                        @foreach ($scopes as $scope)
                            <li>{{ $scope->description ?: $scope->id }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted mb-4">{{ __('oauth.authorize.no_permissions') }}</p>
                @endif

                <div class="d-flex flex-column flex-sm-row gap-2">
                    <form method="POST" action="{{ route('passport.device.authorizations.approve') }}">
                        @csrf
                        <input type="hidden" name="auth_token" value="{{ $authToken }}">
                        <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                        @if ($request->filled('state'))
                            <input type="hidden" name="state" value="{{ $request->input('state') }}">
                        @endif
                        <button type="submit" class="btn btn-primary">{{ __('oauth.authorize.approve') }}</button>
                    </form>

                    <form method="POST" action="{{ route('passport.device.authorizations.deny') }}">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="auth_token" value="{{ $authToken }}">
                        <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                        @if ($request->filled('state'))
                            <input type="hidden" name="state" value="{{ $request->input('state') }}">
                        @endif
                        <button type="submit" class="btn btn-outline-secondary">{{ __('oauth.authorize.deny') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
