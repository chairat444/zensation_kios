@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/checkin.css') }}">
    <style>
        /* Animation สำหรับหน้าพิมพ์ */
        .printer-animation { position: relative; display: inline-block; }
        .print-paper {
            width: 50px; height: 0; background: #eee;
            border: 1px solid #ddd; position: absolute;
            top: 70%; left: 50%; transform: translateX(-50%);
            animation: print-out 2s infinite;
        }
        @keyframes print-out {
            0% { height: 0; opacity: 1; }
            50% { height: 30px; opacity: 1; }
            80% { height: 30px; opacity: 0; }
            100% { height: 0; opacity: 0; }
        }
    </style>
@endpush

@section('content')
    <div class="checkin-page-wrapper">
        {{-- Background Slider --}}
        <div class="swiper swiper-bg-fixed">
            <div class="swiper-wrapper">
                <div class="swiper-slide">
                    <div class="bg-img" style="background-image:url('{{ asset('images/kiosk/1.jpg') }}')"></div>
                </div>
            </div>
            <div class="bg-overlay"></div>
        </div>

        <div class="checkin-content animate-fade-down">
            <div class="text-center mb-4">
                <h1 class="welcome-title" style="font-size: 3rem;">CHECK-IN</h1>
            </div>

            @include('kiosk.checkin.steps._step1_search')
            @include('kiosk.checkin.steps._step2_info')
            @include('kiosk.checkin.steps._step3_print')

            {{-- STEP 3: SUCCESS --}}
            <div id="step3" class="step-card animate-fade-in" style="display: none;">
                <div class="success-checkmark mb-4"><i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i></div>
                <h2 class="step-title">SUCCESSFUL!</h2>
                <div class="room-display-card my-4">
                    <span class="label">YOUR ROOM NUMBER</span>
                    <h2 id="finalRoomNumber" class="room-val">#000</h2>
                </div>
                <button id="finishBtn" class="kiosk-btn-main">FINISH & HOME</button>
            </div>
        </div>
    </div>
    <div class="simple-keyboard"></div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/rsvp/4.8.4/rsvp.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sha256/0.2.0/sha256.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2.4/qz-tray.min.js"></script>

    <script>
        // ประกาศตัวแปรคอนฟิกก่อนเรียกไฟล์ JS
        const KIOSK_CONFIG = {
            searchUrl: "{{ route('kiosk.search') }}",
            checkinUrl: "{{ route('kiosk.checkin') }}",
            homeUrl: "{{ route('kiosk.home') }}",
            csrfToken: "{{ csrf_token() }}"
        };
    </script>

    {{-- เรียกไฟล์ JS จากโฟลเดอร์ public --}}
    <script src="{{ asset('js/checkin-handler.js') }}"></script>
@endpush