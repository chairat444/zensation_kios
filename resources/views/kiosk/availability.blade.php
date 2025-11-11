
@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
  :root{ --glass:#ffffff2e; --max:840px; }
  .hero{
    min-height:100vh; display:flex; align-items:center; justify-content:center;
    background: radial-gradient(1200px 400px at 50% 0, #dfefff 0%, #eef3ff 40%, #f7f9ff 70%, #ffffff 100%);
  }
  .card-glass{
    width:min(92vw,var(--max));
    border:1px solid rgba(255,255,255,.55);
    background:var(--glass); backdrop-filter: blur(12px);
    box-shadow: 0 24px 60px rgba(15,30,60,.18);
    border-radius:16px;
  }
  .title{
    font-weight:900; letter-spacing:.04em;
    text-transform:uppercase; color:#2b7ddd;
    text-shadow:0 6px 18px rgba(43,125,221,.25);
  }
  .btn-primary{ background:#2b7ddd; border:0; }
  .btn-primary:hover{ background:#2166bd; }
  .input-icon{ position:relative; }
  .input-icon i{
    position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#6b7a90;
  }
  .input-icon input, .input-icon select{ padding-left:38px; }
  .result-card{ border:1px solid #e9eef6; border-radius:14px; }
  .price{ font-weight:900; font-size:1.4rem; color:#0f6; }
  .spinner-overlay{
    position:fixed; inset:0; background:rgba(255,255,255,.6);
    display:none; align-items:center; justify-content:center; z-index:9999;
  }
</style>
@endpush

@section('content')
<div class="hero">
  <div class="card card-glass p-3 p-md-4">
    <div class="d-flex align-items-center gap-3 mb-3">
      <img src="{{ asset('images/zensationlogo.png') }}" style="height:38px">
      <h2 class="title m-0">Check Availability</h2>
    </div>

    {{-- Search form --}}
    <form class="row g-3" method="post" action="{{ route('kiosk.availability.search') }}" id="availForm">
      @csrf
      <div class="col-12 col-md-4 input-icon">
        <i class="bi bi-calendar-check"></i>
        <input type="text" class="form-control form-control-lg" id="checkin" name="checkin"
               placeholder="Check-in" value="{{ $data['checkin'] ?? '' }}" autocomplete="off">
      </div>
      <div class="col-12 col-md-4 input-icon">
        <i class="bi bi-calendar2-week"></i>
        <input type="text" class="form-control form-control-lg" id="checkout" name="checkout"
               placeholder="Check-out" value="{{ $data['checkout'] ?? '' }}" autocomplete="off">
      </div>
      <div class="col-6 col-md-2 input-icon">
        <i class="bi bi-person-fill"></i>
        <select class="form-select form-select-lg" name="adults" id="adults">
          @for($i=1;$i<=6;$i++)
            <option value="{{ $i }}" {{ (isset($data['adults']) && $data['adults']==$i)?'selected':'' }}>{{ $i }} Adult{{ $i>1?'s':'' }}</option>
          @endfor
        </select>
      </div>
      <div class="col-6 col-md-2 input-icon">
        <i class="bi bi-people-fill"></i>
        <select class="form-select form-select-lg" name="children" id="children">
          @for($i=0;$i<=6;$i++)
            <option value="{{ $i }}" {{ (isset($data['children']) && $data['children']==$i)?'selected':'' }}>{{ $i }} Child{{ $i>1?'ren':'' }}</option>
          @endfor
        </select>
      </div>
      <div class="col-6 col-md-2 input-icon">
        <i class="bi bi-door-open-fill"></i>
        <select class="form-select form-select-lg" name="rooms" id="rooms">
          @for($i=1;$i<=5;$i++)
            <option value="{{ $i }}" {{ (isset($data['rooms']) && $data['rooms']==$i)?'selected':'' }}>{{ $i }} Room{{ $i>1?'s':'' }}</option>
          @endfor
        </select>
      </div>
      <div class="col-12 col-md-10 d-grid d-md-flex gap-2">
        <button type="submit" class="btn btn-primary btn-lg px-4">
          <i class="bi bi-search"></i> Search
        </button>
        <a href="{{ route('kiosk.availability') }}" class="btn btn-outline-secondary btn-lg">Reset</a>
      </div>
    </form>

    {{-- Results --}}
    @isset($rooms)
      <hr class="my-4">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="mb-0 fw-bold">Available rooms</h5>
        <div class="text-muted small">
          {{ \Carbon\Carbon::parse($data['checkin'])->format('d M Y') }}
          –
          {{ \Carbon\Carbon::parse($data['checkout'])->format('d M Y') }}
          • {{ $data['adults'] }} adult{{ $data['adults']>1?'s':'' }}
          @if(($data['children']??0)>0)
            , {{ $data['children'] }} child{{ $data['children']>1?'ren':'' }}
          @endif
        </div>
      </div>

      <div class="row g-3">
        @forelse($rooms as $r)
          <div class="col-12">
            <div class="p-3 p-md-4 result-card d-flex flex-column flex-md-row align-items-md-center justify-content-between">
              <div class="d-flex flex-column">
                <div class="fw-bold fs-5">{{ $r['name'] }}</div>
                <div class="text-muted">{{ $r['desc'] }}</div>
                <div class="small text-secondary">Max {{ $r['max'] }} guests</div>
              </div>
              <div class="d-flex align-items-center gap-3 mt-3 mt-md-0">
                <div class="price">THB {{ number_format($r['price'],0) }}</div>
                <form action="{{ route('kiosk.booking') ?? '#' }}" method="get">
                  <button type="button" class="btn btn-success btn-lg" disabled>
                    Select
                  </button>
                </form>
              </div>
            </div>
          </div>
        @empty
          <div class="col-12">
            <div class="alert alert-warning">No rooms available for the selected dates.</div>
          </div>
        @endforelse
      </div>
    @endisset
  </div>
</div>

{{-- loading overlay --}}
<div class="spinner-overlay" id="loading">
  <div class="spinner-border text-primary" role="status" style="width:3rem;height:3rem"></div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
  // Date pickers
  const today = new Date();
  const fpIn  = flatpickr("#checkin", {
    dateFormat: "Y-m-d",
    minDate: today,
    defaultDate: "{{ $data['checkin'] ?? '' }}",
    onChange: (sel)=> fpOut.set('minDate', sel[0] || today)
  });
  const fpOut = flatpickr("#checkout", {
    dateFormat: "Y-m-d",
    minDate: "{{ $data['checkin'] ?? now()->toDateString() }}",
    defaultDate: "{{ $data['checkout'] ?? '' }}"
  });

  // Submit spinner
  const form = document.getElementById('availForm');
  form?.addEventListener('submit', ()=> document.getElementById('loading').style.display='flex');
</script>
@endpush
