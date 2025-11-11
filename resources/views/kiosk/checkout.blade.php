@extends('layouts.app')
@section('content')
<div class="container py-4" style="max-width:560px;">
  <h2 class="fw-bold text-center mb-4">Check-out</h2>

  <form method="post" action="{{ route('kiosk.checkout') }}" class="card p-3 shadow-sm">
    @csrf
    <label class="form-label fw-semibold">Room no. or Confirmation no.</label>
    <input type="text" name="room_or_confirmation" class="form-control form-control-lg mb-4" placeholder="e.g. 508 or ZX12345" required>
    <button class="btn btn-warning text-white btn-lg">Lookup</button>
  </form>

  @if(session('ok'))
    <div class="alert alert-info mt-3">
      <div class="fw-bold">Status: {{ session('result.status') }}</div>
      <div class="small text-muted">Ref: {{ session('result.reference') }} • Guest: {{ session('result.guest') }}</div>
      <div class="small">Balance: THB {{ number_format(session('result.balance'), 2) }}</div>
    </div>
    <div class="d-grid mt-3 gap-2">
      <a href="#" class="btn btn-primary btn-lg">Proceed to payment</a>
      <a href="#" class="btn btn-success btn-lg">Confirm check-out</a>
    </div>
  @endif
</div>
@endsection
