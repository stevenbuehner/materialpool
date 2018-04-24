@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">


            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <h1>Bundles</h1>

                    <div class="panel-body">


                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Bundle</th>
                                <th scope="col">Author</th>
                                <th scope="col">Installed Version</th>
                                <th scope="col">Contains</th>
                                <th scope="col">Update available</th>
                            </tr>
                            </thead>
                            <tbody>
							<?php
							/** @var \App\Models\Bundle $bundle */
							?>
                            @foreach($bundles as $bundle)
                                <tr>
                                    <th scope="row">{{$bundle->id}}</th>
                                    <td>{{$bundle->name}}</td>
                                    <td>{{$bundle->author}}</td>
                                    <td>@if(!empty($bundle->installed_version))
                                            {{$bundle->installed_version}}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{trans_choice('pool.file-count', $infos->get($bundle->uuid)['count_files'], ['value' => $infos->get($bundle->uuid)['count_files']])}} {{__('pool.and')}} {{trans_choice('pool.material-count', $infos->get($bundle->uuid)['count_materials'], ['value' => $infos->get($bundle->uuid)['count_materials']]) }}</td>

                                    <td>
                                        @if($infos->has($bundle->uuid))
                                            @if($bundle->installed_version === NULL)
                                                <a href="{{route('pool.bundles.update.init', ['bundle' => $bundle->id])}}"
                                                   class="btn-primary btn">{{__('pool.install-bundle-version', ['version' => $infos->get($bundle->uuid)['version']])}}</a>
                                            @elseif($infos->get($bundle->uuid)['version'] !== $bundle->installed_version)
                                                <a href="{{route('pool.bundles.update.init', ['bundle' => $bundle->id])}}"
                                                   class="btn-primary btn">{{__('pool.update-bundle-version', ['version' => $infos->get($bundle->uuid)['version']])}}</a>
                                            @else
                                                <span class="badge badge-success">{{__('pool.installed-bundle-version', ['version' => $bundle->uuid])}}</span>
                                            @endif
                                        @else
                                            {{__('pool.bundle-not-available-anymore')}}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
