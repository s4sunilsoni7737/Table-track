@extends('layouts.app')

@section('content')
@livewire('order.orders', [
    'deliveryExecutiveId' => $customer->id,
    'deliveryExecutiveName' => $customer->name,
    'backUrl' => route('delivery-executives.index'),
], key('delivery-executive-orders-' . $customer->id))
@endsection
