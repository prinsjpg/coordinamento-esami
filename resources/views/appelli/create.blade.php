@extends('layouts.master')

@section('title', 'Nuovo appello')
@section('heading', 'Nuovo appello')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('appelli.index') }}">Appelli</a></li>
    <li class="breadcrumb-item active" aria-current="page">Nuovo</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('appelli.store') }}">
                @csrf
                @include('appelli._form')
            </form>
        </div>
    </div>
@endsection
