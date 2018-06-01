@extends('layouts.app')

@section('content')

    <h1>Material wurde zerstört :-)</h1>

    @include('bootstrap-elements.list-group.with-pill.group', ['items' => [
      __('pool.material.deleted') => $deletedMaterials,
      __('pool.resource.deleted') => $deletedResources,
      __('pool.resource.ignored') => $ignoredResources
      ]
    ])

    <a href="{{route('pool.material.index')}}" class="btn btn-link">Zurück zur Materialliste</a>

@endsection