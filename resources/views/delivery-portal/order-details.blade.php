@extends('layouts.delivery-portal')

@section('content')
    @livewire('delivery-portal.order-details', ['restaurant' => $restaurant, 'orderUuid' => $orderUuid])
@endsection
