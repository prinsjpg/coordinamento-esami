@extends('layouts.master')

@section('title', 'Nuovo corso di studio')
@section('heading', 'Nuovo corso di studio')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('struttura.index') }}">Struttura didattica</a></li>
    <li class="breadcrumb-item"><a href="{{ route('corsi.index') }}">Corsi di studio</a></li>
    <li class="breadcrumb-item active" aria-current="page">Nuovo</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('corsi.store') }}" style="max-width: 32rem;">
                @csrf
                @include('corsi._form')
            </form>
        </div>
    </div>
@endsection
