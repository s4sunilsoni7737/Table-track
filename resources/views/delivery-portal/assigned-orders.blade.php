@extends('layouts.delivery-portal')

@section('content')
    @livewire('delivery-portal.assigned-orders', ['restaurant' => $restaurant])
@endsection
