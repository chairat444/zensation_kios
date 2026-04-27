<!-- resources/views/kiosk/booking.blade.php -->
@extends('layouts.app')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/reserve.css') }}">
@endpush
@section('content')
<div class="container-fluid p-0 reserve-page-wrap">
    <iframe
      src="https://live.ipms247.com/booking/book-rooms-zensationtheresidence?source=kiosk"
      class="reserve-iframe"
      allowfullscreen>
    </iframe>
</div>
@endsection
