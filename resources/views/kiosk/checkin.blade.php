@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/checkin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/checkin-page.css') }}">
    <link rel="stylesheet" href="{{ asset('css/checkin-step2.css') }}">
@endpush

@section('content')
    <div
        id="checkinApp"
        class="checkin-page-wrapper"
        data-search-url="{{ route('kiosk.search') }}"
        data-checkin-url="{{ route('kiosk.checkin') }}"
        data-home-url="{{ route('kiosk.home') }}"
        data-csrf-token="{{ csrf_token() }}"
        data-card-dispenser-enabled="{{ config('kiosk.card_dispenser.enabled') ? '1' : '0' }}"
        data-card-dispenser-url="{{ config('kiosk.card_dispenser.http_url') }}"
    >
        {{-- Background Slider --}}
        <div class="swiper swiper-bg-fixed">
            <div class="swiper-wrapper">
                <div class="swiper-slide">
                    <div class="bg-img checkin-bg-1"></div>
                </div>
            </div>
            <div class="bg-overlay"></div>
        </div>

        <div class="checkin-content animate-fade-down">
            <div class="text-center mb-3 checkin-top-shell">
                <h1 class="welcome-title checkin-page-title">CHECK-IN</h1>
                <div class="device-status-bar mt-3">
                    <div id="deviceStatusAgent" class="device-status-chip is-offline">
                        <span class="status-dot"></span>
                        <span class="status-label">Card Reader: Offline</span>
                    </div>
                    <div id="deviceStatusPassport" class="device-status-chip is-offline">
                        <span class="status-dot"></span>
                        <span class="status-label">Passport Scanner: Offline</span>
                    </div>
                </div>
            </div>

            <nav id="checkinWizardNav" class="checkin-wizard-nav d-none" aria-label="Check-in progress">
                <ol class="wizard-steps-list wizard-steps-list--three">
                    <li class="wizard-step" data-wizard-step="1"><span class="wizard-num">1</span><span class="wizard-lab">SEARCH</span></li>
                    <li class="wizard-step" data-wizard-step="2"><span class="wizard-num">2</span><span class="wizard-lab">YOUR STAY</span></li>
                    <li class="wizard-step" data-wizard-step="3"><span class="wizard-num">3</span><span class="wizard-lab">PHOTO</span></li>
                </ol>
            </nav>

            @include('kiosk.checkin.steps._step1_search')
            @include('kiosk.checkin.steps._step2_info')
            @include('kiosk.checkin.steps._step3_print')

            <div id="stepKeycard" class="step-card animate-fade-in step-hidden">
                <div class="text-center py-4 px-3">
                    <h2 class="step-title">KEYCARD</h2>
                    <p class="text-muted fs-5 mb-4">Please collect your keycard from the dispenser.</p>
                    <div id="dispenseStatus" class="py-4">
                        <div class="spinner-border text-primary mb-3" role="status"></div>
                        <p class="mb-0 fw-bold">Dispensing…</p>
                    </div>
                    <div id="finishArea" class="d-none">
                        <p class="text-success fw-bold mb-4">You are all set. Thank you for staying with us.</p>
                        <button type="button" class="kiosk-btn-main finish-btn">FINISH & HOME</button>
                    </div>
                </div>
            </div>

            <div id="stepSuccess" class="step-card animate-fade-in step-hidden">
                <div class="success-checkmark mb-4"><i class="bi bi-check-circle-fill text-success success-icon-lg"></i></div>
                <h2 class="step-title">SUCCESSFUL!</h2>
                <div class="room-display-card my-4">
                    <span class="label">YOUR ROOM NUMBER</span>
                    <h2 id="finalRoomNumber" class="room-val">#000</h2>
                </div>
                <button id="finishBtn" class="kiosk-btn-main finish-btn">FINISH & HOME</button>
            </div>
        </div>
    </div>
    <div class="simple-keyboard"></div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/rsvp/4.8.4/rsvp.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sha256/0.2.0/sha256.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2.4/qz-tray.min.js"></script>

    {{-- เรียกไฟล์ JS จากโฟลเดอร์ public --}}
    <script src="{{ asset('js/scan.js') }}"></script>
    <script src="{{ asset('js/passport-handler.js') }}"></script>
    <script src="{{ asset('js/checkin-handler.js') }}"></script>

@endpush
