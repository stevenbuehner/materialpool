@extends('layouts.app')

@section('content')

    <div class="card-columns">
        @foreach($resources as $r)
            <div class="card">

                <div class="card-block">
                    <h3 class="card-title">{{ $r->type }}</h3>
                    <p class="card-text">{{ $r->local_path }}</p>
                    <a href="{{ URL::route('pool.resource.show', $r->id) }}/{{$r->id}}"
                       class="btn btn-primary">@lang('Open Resource')</a>
                    <div>
                        <small>{{ $r->created_at }}</small>
                    </div>

                </div>
            </div>
        @endforeach
    </div>

    {{ $resources->links('vendor.pagination.bootstrap-4') }}
@endsection