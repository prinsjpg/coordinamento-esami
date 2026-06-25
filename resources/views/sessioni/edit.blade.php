@extends('layouts.master')

@section('title', 'Modifica sessione')
@section('heading', 'Modifica sessione')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('struttura.index') }}">Struttura didattica</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sessioni.index') }}">Sessioni</a></li>
    <li class="breadcrumb-item active" aria-current="page">Modifica</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('sessioni.update', $sessione) }}" style="max-width: 36rem;">
                @csrf
                @method('PUT')
                @include('sessioni._form')
            </form>
        </div>
    </div>
@endsection
