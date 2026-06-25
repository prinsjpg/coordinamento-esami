@extends('layouts.master')

@section('title', 'Nuova sessione')
@section('heading', 'Nuova sessione')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('struttura.index') }}">Struttura didattica</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sessioni.index') }}">Sessioni</a></li>
    <li class="breadcrumb-item active" aria-current="page">Nuova</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('sessioni.store') }}" style="max-width: 36rem;">
                @csrf
                @include('sessioni._form')
            </form>
        </div>
    </div>
@endsection
