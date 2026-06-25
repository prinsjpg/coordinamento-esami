@extends('layouts.master')

@section('title', 'Nuovo insegnamento')
@section('heading', 'Nuovo insegnamento')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('struttura.index') }}">Struttura didattica</a></li>
    <li class="breadcrumb-item"><a href="{{ route('insegnamenti.index') }}">Insegnamenti</a></li>
    <li class="breadcrumb-item active" aria-current="page">Nuovo</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('insegnamenti.store') }}">
                @csrf
                @include('insegnamenti._form')
            </form>
        </div>
    </div>
@endsection
