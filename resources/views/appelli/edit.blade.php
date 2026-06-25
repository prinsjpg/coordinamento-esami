@extends('layouts.master')

@section('title', 'Modifica appello')
@section('heading', 'Modifica appello')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('appelli.index') }}">Appelli</a></li>
    <li class="breadcrumb-item active" aria-current="page">Modifica</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('appelli.update', $appello) }}">
                @csrf
                @method('PUT')
                @include('appelli._form')
            </form>
        </div>
    </div>
@endsection
