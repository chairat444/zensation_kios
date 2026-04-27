@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
@endpush

@section('content')
    <div class="swiper swiper-bg-fixed">
        <div class="swiper-wrapper">
            @foreach ([1, 2, 3, 4, 5] as $n)
                <div class="swiper-slide"><div class="bg-img" style="background-image:url('{{ asset("images/kiosk/$n.jpg") }}')"></div></div>
            @endforeach
        </div>
        <div class="bg-overlay"></div>
    </div>

    <div class="home-content">
        <div class="status-bar">
            <div class="status-dot"></div>
            <span class="text-white small fw-bold text-uppercase">Kiosk Online • Available 24/7</span>
        </div>

        <div class="welcome-header">
            <h1 class="welcome-title">WELCOME<br>HOME</h1>
            <p class="welcome-subtitle">Zensation The Residence</p>
        </div>

        <div class="kiosk-grid">
            <a href="{{ route('kiosk.checkin') }}" class="main-card card-checkin text-decoration-none">
                <i class="bi bi-person-vcard-fill"></i>
                <span>CHECK-IN</span>
                <div class="start-badge text-uppercase">Touch to Start</div>
            </a>

            <div class="main-card card-reserve" onclick="openQRModal()">
                <i class="bi bi-qr-code-scan"></i>
                <span>RESERVE</span>
                <p class="mb-0 opacity-75 mt-1 fw-bold">Instant Booking</p>
            </div>

            <div class="main-card card-checkout">
                <div class="d-flex align-items-center">
                    <i class="bi bi-key-fill me-3 checkout-key-icon"></i>
                    <span class="checkout-label">CHECK-OUT</span>
                </div>
                <i class="bi bi-chevron-right opacity-50 checkout-arrow-icon"></i>
            </div>
        </div>
    </div>

    <div id="customModal">
        <div class="custom-backdrop" onclick="closeQRModal()"></div>
        <div class="custom-modal-content">
            <h2 class="reserve-title">RESERVE NOW</h2>
            <p class="text-muted fs-5">Scan this QR code to book your room<br>directly on your mobile phone.</p>
            <div class="qr-box">
                <div class="scan-line"></div>
                <div id="qrcode_canvas"></div>
            </div>
            <button type="button" class="btn-huge-close" onclick="closeQRModal()">GOT IT, CLOSE</button>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="{{ asset('js/home-page.js') }}"></script>
@endpush