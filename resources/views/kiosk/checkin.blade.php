
@extends('layouts.app')
@section('content')
<div class="container py-4" style="max-width:560px;">
  <h2 class="fw-bold text-center mb-4">Check-in</h2>

  <form method="post" action="{{ route('kiosk.checkin') }}" class="card p-3 shadow-sm">
    @csrf
    <label class="form-label fw-semibold">Confirmation No.</label>
    <input type="text" name="confirmation" class="form-control form-control-lg mb-3" placeholder="e.g. ZX12345" required>
    <label class="form-label fw-semibold">Last name</label>
    <input type="text" name="lastname" class="form-control form-control-lg mb-4" placeholder="e.g. Smith" required>
    <button class="btn btn-success btn-lg">Search reservation</button>
  </form>

  @if(session('ok'))
    <div class="alert alert-success mt-3">
      <div class="fw-bold">Reservation found</div>
      <div class="small text-muted">#{{ session('result.confirmation') }} • {{ session('result.guest') }}</div>
    </div>
    <div class="d-grid mt-3">
      <a href="#" class="btn btn-primary btn-lg">Continue</a>
    </div>
  @endif
</div>
@endsection
