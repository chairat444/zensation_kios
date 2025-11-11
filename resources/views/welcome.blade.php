@extends('layouts.app')

@push('styles')
<style>
/* ===== Title glow + reflection ===== */
.kiosk-title{
  font-family:"Raleway",sans-serif;
  font-size: clamp(28px, 5vw, 64px);
  font-weight: 900;
  letter-spacing: .06em;
  color:#69a7ff;
  text-shadow: 0 4px 14px rgba(64,126,215,.45);
  -webkit-box-reflect: below 6px linear-gradient(transparent, rgba(0,0,0,.15));
  margin-top:24px;margin-bottom:18px;
}

/* ===== Digital Clock card (เหมือนเดิม) ===== */
.digital-clock{
  position:relative;
  width: clamp(320px, 70vw, 620px);
  margin: 0 auto 18px;
  padding: 18px 26px;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(255,255,255,.92), rgba(245,246,255,.85));
  box-shadow: 0 18px 35px rgba(14,21,37,.25), 0 4px 0 rgba(0,0,0,.06) inset;
}
.digital-clock::after{ /* halo */
  content:""; position:absolute; inset:-32px;
  background: radial-gradient(900px 200px at 50% -40px, rgba(121,168,255,.35), transparent 60%);
  z-index:-1; filter: blur(6px);
}

/* layout inside */
.time{ display:flex; align-items:center; justify-content:center; gap:8px; }
.hours,.minutes,.dots{
  line-height:1; font-weight:700; letter-spacing:.02em;
}
.hours,.minutes{
  font-size: clamp(56px, 10vw, 96px);
  background: linear-gradient(90deg,#66697f,#8c8cab);
  -webkit-text-fill-color: transparent; -webkit-background-clip: text;
}
.dots{ font-size: clamp(48px, 9vw, 84px); color:#6f7895; padding: 0 6px; }

.right-side{
  display:flex; flex-direction:column; align-items:flex-start; padding-left:6px;
}
.period{ font-size:16px; font-weight:600; color:#ff5e9a; margin-top:6px; }
.seconds{ font-size:14px; color:#6b7285; margin-top:12px; }

.calendar{
  text-align:center; margin-top:10px; font-weight:500;
  background: linear-gradient(90deg,#028fa8,#028fa8);
  -webkit-text-fill-color: transparent; -webkit-background-clip: text;
}

/* ===== Buttons ===== */
.kiosk-btn{
  width: 420px; max-width: 92%;
  height: 56px; font-size: 22px; font-weight: 700;
  border-radius: 10px; border:0; color:#fff;
  margin: 12px auto; display:block;
  box-shadow: 0 10px 18px rgba(0,0,0,.08);
}
.btn-walkin   { background:#2b7ddd; }
.btn-walkin:hover{ background:#1f66b6; }
.btn-reserved { background:#1e946d; }
.btn-reserved:hover{ background:#167a59; }
.btn-checkout { background:#e67b2e; }
.btn-checkout:hover{ background:#c76824; }

/* Logo */
.brand-logo{ max-width:260px; margin-top: 52px; }

.bg-anim img { position:absolute; z-index:1; }
.hotel { bottom:6vw; left:0; width:100%; z-index:2; }
.car1,.car2,.car3,.car4 { width:80px; z-index:3; }
.car1 { bottom:4%; animation:drive 15s linear infinite; }
.car2 { bottom:2%; animation:drive 10s linear infinite; }
.car3 { bottom:3%; animation:driveReverse 20s linear infinite; }
.car4 { bottom:1%; animation:driveReverse 12s linear infinite; }
.dog { height:4%; bottom:0; animation:drive 20s linear infinite; z-index:3; }
.cloud { top:-100px; width:800px; animation:cloudMove 50s linear infinite; }
.cloud2 { top:20px; width:600px; animation:cloudMove 65s linear infinite; }
.cloud3 { top:80px; width:700px; animation:cloudMove 70s linear infinite; }
.cloud4 { bottom:50%; width:500px; animation:cloudMove 60s linear infinite; }

@keyframes drive { from{transform:translateX(-200px)} to{transform:translateX(2200px)} }
@keyframes driveReverse { from{transform:translateX(2200px)} to{transform:translateX(-200px)} }
@keyframes cloudMove { from{transform:translateX(1900px)} to{transform:translateX(-900px)} }
</style>
@endpush

@section('content')
<div class="container text-center pb-5">
  <!-- Title -->
  <h1 class="kiosk-title text-uppercase">CHECK-IN / CHECK-OUT KIOSK</h1>

  <!-- Clock -->
  <div class="digital-clock">
    <div class="time">
      <span class="hours">00</span>
      <span class="dots">:</span>
      <span class="minutes">00</span>
      <div class="right-side">
        <span class="period">PM</span>
        <span class="seconds">00</span>
      </div>
    </div>
    <div class="calendar">
      <span class="month-name">Sep</span>, <span class="day-name">Sunday</span> <span class="day-number">7</span>
    </div>
  </div>

  <!-- Buttons -->
  <form action="{{ url('/walkin') }}" method="POST" onsubmit="return confirmWalkin(this);">
    @csrf
    <button type="submit" class="kiosk-btn btn-walkin">WALK–IN</button>
  </form>

  <form action="{{ url('/reserved') }}" method="POST" onsubmit="return confirmReserved(this);">
    @csrf
    <button type="submit" class="kiosk-btn btn-reserved">RESERVED</button>
  </form>

  <form action="{{ url('/checkout') }}" method="POST" onsubmit="return confirmCheckout(this);">
    @csrf
    <button type="submit" class="kiosk-btn btn-checkout">CHECK–OUT</button>
  </form>

  <!-- Logo -->
  <img src="{{ asset('images/zensationlogo.png') }}" class="brand-logo img-fluid" alt="logo">
</div>

{{-- พื้นหลังแอนิเมชัน --}}
<div class="bg-anim">
    <img src="{{ asset('images/hotel.png') }}" class="hotel" alt="">
    <img src="{{ asset('images/car1.png') }}" class="car1" alt="">
    <img src="{{ asset('images/car2.png') }}" class="car2" alt="">
    <img src="{{ asset('images/car3.png') }}" class="car3" alt="">
    <img src="{{ asset('images/car4.png') }}" class="car4" alt="">
    <img src="{{ asset('images/cloud.png') }}" class="cloud" alt="">
    <img src="{{ asset('images/cloud.png') }}" class="cloud2" alt="">
    <img src="{{ asset('images/cloud.png') }}" class="cloud3" alt="">
    <img src="{{ asset('images/cloud.png') }}" class="cloud4" alt="">
  </div>
@endsection

@push('scripts')
<script>
// ===== Clock =====
function pad(n){ return n<10 ? '0'+n : n; }
function updateClock(){
  const t = new Date();
  let h = t.getHours(), m = t.getMinutes(), s = t.getSeconds();
  const period = h >= 12 ? 'PM' : 'AM';
  $('.hours').text(pad(h));
  $('.minutes').text(pad(m));
  $('.seconds').text(pad(s));
  $('.period').text(period);

  const month = t.toLocaleString('en-GB',{month:'short'});
  const day   = t.toLocaleString('en-GB',{weekday:'long'});
  $('.month-name').text(month);
  $('.day-name').text(day);
  $('.day-number').text(t.getDate());
}
setInterval(updateClock,1000); updateClock();

// ===== Confirms =====
function confirmWalkin(f){
  Swal.fire({title:'Make a new reservation ?',text:"Book directly with us",icon:'info',
    showCancelButton:true,confirmButtonColor:'#2b7ddd',cancelButtonColor:'#6c757d',confirmButtonText:'Yes'
  }).then(r=>{ if(r.isConfirmed) f.submit(); }); return false;
}
function confirmReserved(f){
  Swal.fire({title:'Online Reserved ?',text:"Proceed with your online booking",icon:'info',
    showCancelButton:true,confirmButtonColor:'#1e946d',cancelButtonColor:'#6c757d',confirmButtonText:'Yes'
  }).then(r=>{ if(r.isConfirmed) f.submit(); }); return false;
}
function confirmCheckout(f){
  Swal.fire({title:'Confirm check-out ?',text:"Click Yes to continue",icon:'warning',
    showCancelButton:true,confirmButtonColor:'#e67b2e',cancelButtonColor:'#6c757d',confirmButtonText:'Yes'
  }).then(r=>{ if(r.isConfirmed) f.submit(); }); return false;
}
</script>
@endpush
