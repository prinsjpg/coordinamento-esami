@extends('layouts.master')

@section('title', 'Modifica insegnamento')
@section('heading', 'Modifica insegnamento')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('struttura.index') }}">Struttura didattica</a></li>
    <li class="breadcrumb-item"><a href="{{ route('insegnamenti.index') }}">Insegnamenti</a></li>
    <li class="breadcrumb-item active" aria-current="page">Modifica</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('insegnamenti.update', $insegnamento) }}">
                @csrf
                @method('PUT')
                @include('insegnamenti._form')
            </form>
        </div>
    </div>
@endsection
