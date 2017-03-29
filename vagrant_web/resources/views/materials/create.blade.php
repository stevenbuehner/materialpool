@extends('layout')

@section('content')
    <h1>Erstelle ein neues Material</h1>

    <form method="POST" action="/bears">

        {{csrf_field()}}

        <div class="form-group">
            <label for="title">Bärname</label>
            <input type="text" class="form-control" id="title" placeholder="Titel" name="title" required>
        </div>



        <div class="form-group">
            <label for="baertype">Typ</label>
            <input type="baertype" class="form-control" id="baertype" placeholder="Typ" name="typ">
        </div>


        <div class="form-group">
            <label for="danger_level">Gefahrenlevel</label>
            <input type="number" class="form-control" id="danger_level" placeholder="Gefahrenlevel" name="danger_level">
        </div>


        <div class="form-group">
            <button type="submit" class="btn btn-primary">speichern</button>
        </div>

    </form>

    @include('layout.errors')



@endsection