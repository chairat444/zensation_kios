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
                    <i class="bi bi-key-fill me-3" style="font-size: 3rem; color: #fff;"></i>
                    <span style="font-size: 1.5rem; font-weight: 800;">CHECK-OUT</span>
                </div>
                <i class="bi bi-chevron-right opacity-50" style="font-size: 2.5rem; color: #fff;"></i>
            </div>
        </div>
    </div>

    <div id="customModal">
        <div class="custom-backdrop" onclick="closeQRModal()"></div>
        <div class="custom-modal-content">
            <h2 style="font-size: 3.2rem; color: #1e3c72; font-weight: 900; letter-spacing: -2px;">RESERVE NOW</h2>
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
    <script>
        function openQRModal() {
            const modal = document.getElementById('customModal');
            modal.style.display = 'flex';
            const qrContainer = document.getElementById("qrcode_canvas");
            qrContainer.innerHTML = '';
            new QRCode(qrContainer, {
                text: "https://live.ipms247.com/booking/book-rooms-zensationtheresidence",
                width: 320, height: 320,
                colorDark : "#1e3c72", colorLight : "#f8f9fa",
                correctLevel : QRCode.CorrectLevel.H
            });
        }
        function closeQRModal() { document.getElementById('customModal').style.display = 'none'; }

        window.addEventListener('load', function() {
            if (window.Swiper) {
                new window.Swiper('.swiper', { loop: true, effect: 'fade', autoplay: { delay: 8000 }, speed: 3000 });
            }
        });
    </script>
@endpush