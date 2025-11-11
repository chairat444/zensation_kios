@extends('layouts.app')
@section('content')
<div class="container text-center py-5">
  <h2 class="fw-bold mb-3">Thank you for your booking</h2>
  @isset($ref)
    <p class="text-muted mb-1">Reference: <strong>{{ $ref }}</strong></p>
  @endisset
  <p class="mb-4">You can proceed to self check-in.</p>
  <a href="{{ route('kiosk.checkin') }}" class="btn btn-success btn-lg">Go to Check-in</a>
</div>
@endsection
