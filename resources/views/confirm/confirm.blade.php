@extends('layouts.master')

@section('title', 'Aanmelding bevestigd')

@section('content')
    <h1>Aanmelding bevestigd</h1>
    <p class="notification success">
        Je aanmelding voor <?= $registration->longDate(); ?> is bevestigd.
    </p>
@endsection
