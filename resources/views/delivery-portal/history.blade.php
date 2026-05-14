@extends('layouts.delivery-portal')

@section('content')
    @livewire('delivery-portal.delivery-history', ['restaurant' => $restaurant])
@endsection
