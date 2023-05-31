@extends('layouts.app')


@section('content')
    <div class="absolute top-1/2 left-8 transform -translate-y-1/2 w-44 opacity-50 z-50">
        <img src="{{ asset('images/powered-by.png') }}" alt="">
    </div>
    <livewire:game :code="$code" />
@endsection