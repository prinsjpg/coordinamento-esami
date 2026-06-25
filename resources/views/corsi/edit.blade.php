@extends('layouts.master')

@section('title', 'Modifica corso di studio')
@section('heading', 'Modifica corso di studio')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('struttura.index') }}">Struttura didattica</a></li>
    <li class="breadcrumb-item"><a href="{{ route('corsi.index') }}">Corsi di studio</a></li>
    <li class="breadcrumb-item active" aria-current="page">Modifica</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('corsi.update', $corso) }}" style="max-width: 32rem;">
                @csrf
                @method('PUT')
                @include('corsi._form')
            </form>
        </div>
    </div>
@endsection
