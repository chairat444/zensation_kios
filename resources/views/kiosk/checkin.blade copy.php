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

            {{-- STEP 1: SEARCH --}}
            <div id="step1" class="step-card active animate-fade-in">
                <div class="alert-icon-primary mb-4">
                    <i class="bi bi-search"></i>
                </div>
                <h2 class="step-title">FIND YOUR BOOKING</h2>
                <p class="step-subtitle text-muted fs-4 mb-5">Enter Voucher, Name, or Booking ID</p>

                <div class="px-5">
                    <div class="input-group-lg mb-4">
                        <input type="text" id="reservationInput" value="RES5161"
                            class="form-control kiosk-input-custom kiosk-keyboard-input" placeholder="Search here..."
                            autocomplete="off">
                    </div>
                    <button id="searchBtn" class="kiosk-btn-main w-100 mb-4">
                        <span>SEARCH RESERVATION</span>
                        <i class="bi bi-chevron-right" style="font-size: 2rem;"></i>
                    </button>
                    <div class="text-center">
                        <a href="{{ route('kiosk.home') }}" class="btn-link-back fs-4 text-decoration-none text-muted">
                            <i class="bi bi-arrow-left me-2"></i> Back to Main Menu
                        </a>
                    </div>
                </div>
            </div>

            {{-- STEP 2: INFO & SCAN --}}
            <div id="step2" class="step-card animate-fade-in" style="display: none;">
                <h2 class="step-title mb-4">CONFIRM YOUR STAY</h2>
                <div id="reservationDetails" class="details-box mb-4 text-start"></div>

                <div class="scan-area-modern mb-4" id="scanArea">
                    <div class="scan-line-animation"></div>
                    <i class="bi bi-person-vcard-fill mb-2 text-white" style="font-size: 4rem;"></i>
                    <p class="fw-bold mb-0 text-white">SCAN ID CARD / PASSPORT</p>
                    <div id="scanStatus" class="scan-badge-status mt-2">
                        <span class="spinner-border spinner-border-sm me-2"></span> Awaiting Scan...
                    </div>
                </div>

                <div class="d-flex gap-3 px-4">
                    <button id="backToSearchBtn" class="kiosk-btn-secondary w-50 py-3">BACK</button>
                    <button id="nextToPrintBtn" class="kiosk-btn-success w-50 py-3" disabled>
                        <span>CONTINUE</span>
                        <i class="bi bi-arrow-right-circle ms-2"></i>
                    </button>
                </div>
            </div>

            {{-- STEP 2.5: PRINTING WIFI COUPON --}}
            <div id="stepPrint" class="step-card animate-fade-in" style="display: none;">
                <div class="text-center py-5">
                    <div class="printer-animation mb-4">
                        <i class="bi bi-printer-fill text-primary" style="font-size: 5rem;"></i>
                        <div class="print-paper"></div>
                    </div>
                    <h2 class="step-title">PRINTING YOUR WIFI COUPON</h2>
                    <p class="fs-4 text-muted">Please wait a moment...</p>
                    <div class="progress mt-4 mx-auto" style="width: 80%; height: 15px; border-radius: 10px;">
                        <div id="printProgress" class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%"></div>
                    </div>
                </div>
            </div>

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
    <script>
        window.addEventListener('load', function() {
            if (!window.$) return;

            $(function() {
                const SEARCH_API_URL = "{{ route('kiosk.search') }}";
                const CHECKIN_API_URL = "{{ route('kiosk.checkin') }}";
                const HOME_URL = "{{ route('kiosk.home') }}";
                let currentReservation = null;

                // --- Step Navigation ---
                function showStep(num) {
                    $('.step-card').hide().removeClass('active');
                    let target = (typeof num === 'number') ? `#step${num}` : `#step${num}`;
                    $(target).fadeIn(400).addClass('active');
                    $('.simple-keyboard').fadeOut(200);

                    if (num === 1) {
                        $('#reservationInput').val('');
                        $('#nextToPrintBtn').prop('disabled', true).html('<span>CONTINUE</span><i class="bi bi-arrow-right-circle ms-2"></i>');
                        $('#scanStatus').html('<span class="spinner-border spinner-border-sm me-2"></span> Awaiting Scan...').removeClass('complete text-success fw-bold');
                    }
                }

                // --- Keyboard Focus Logic ---
                $('#reservationInput').on('focus', function() {
                    $('.simple-keyboard').fadeIn(300);
                });

                $(document).on('mousedown', function(e) {
                    if (!$(e.target).closest('#reservationInput, .simple-keyboard').length) {
                        $('.simple-keyboard').fadeOut(200);
                    }
                });

                // --- Search Action ---
                $('#searchBtn').on('click', function() {
                    const term = $('#reservationInput').val().trim();
                    if (!term) return showPopup('error', 'Please enter your Voucher, Name, or Booking ID.');

                    const $btn = $(this);
                    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> SEARCHING...');

                    $.ajax({
                        url: SEARCH_API_URL,
                        method: 'POST',
                        data: { search: term },
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        success: function(res) {
                            if (res.status === 'success') {
                                currentReservation = res.data;
                                renderDetails(res.data);
                                showStep(2);
                            } else {
                                showPopup('error', res.message);
                            }
                        },
                        complete: () => $btn.prop('disabled', false).html('<span>SEARCH RESERVATION</span><i class="bi bi-chevron-right ms-2"></i>')
                    });
                });

                function renderDetails(data) {
                    $('#reservationDetails').html(`
                        <div class="guest-info-summary text-center mb-4 border-bottom pb-3">
                            <span class="label" style="font-size: 0.9rem; color: #666; font-weight: 800; letter-spacing: 1px;">GUEST NAME</span>
                            <h2 class="fw-bold text-dark mb-0">${data.guest_name}</h2>
                            <span class="badge bg-primary mt-2 px-3">${data.room_type}</span>
                        </div>
                        <div class="row g-4 px-3">
                            <div class="col-6">
                                <div class="info-group">
                                    <span class="label d-block small text-muted fw-800">Check-in Date</span>
                                    <span class="value fs-5 fw-bold"><i class="bi bi-calendar-check me-2"></i>${data.arrival_date}</span>
                                </div>
                            </div>
                            <div class="col-6 text-end">
                                <div class="info-group">
                                    <span class="label d-block small text-muted fw-800">Check-out Date</span>
                                    <span class="value fs-5 fw-bold"><i class="bi bi-calendar-x me-2"></i>${data.departure_date}</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="info-group">
                                    <span class="label d-block small text-muted fw-800">Room Number</span>
                                    <span class="value fs-4 fw-black text-primary">#${data.room_name || '-'}</span>
                                </div>
                            </div>
                            <div class="col-6 text-end">
                                <div class="info-group">
                                    <span class="label d-block small text-muted fw-800">Booking ID</span>
                                    <span class="value fs-5 text-dark">${data.booking_id}</span>
                                </div>
                            </div>
                        </div>
                    `);

                    setTimeout(() => {
                        $('#scanStatus').html('<i class="bi bi-check-circle-fill text-success me-2"></i> SCANNED SUCCESSFULLY')
                                       .addClass('complete text-success fw-bold');
                        $('#nextToPrintBtn').prop('disabled', false);
                    }, 2000);
                }

                $('#nextToPrintBtn').on('click', function() {
                    const $btn = $(this);
                    const mockImage = "iVBORw0KGgoAAAANSUhEUgAAABMAAAAWCAIAAACt/zAoAAAAA3NCSVQICAjb4U/gAAAAEHRFWHRTb2Z0d2FyZQBTaHV0dGVyY4LQCQAAAFRJREFUOMtj/Pr1KwNZgImBXDBSdLLglz5268P0PY+fvf/1+ccfYnX+/fd/xt4nCw89I9m15x98xqMNn85Fh5+RGUK3nn8lU+e7r39G09CoTtrqBAB1MiHSwHyEmgAAAABJRU5ErkJggg==";

                    const payload = {
                        booking_id: currentReservation.booking_id,
                        guest_name: currentReservation.guest_name,
                        email: currentReservation.email || "guest@example.com",
                        address: currentReservation.address || "Bangkok, Thailand",
                        phone: currentReservation.phone || "02-000-0000",
                        mobile: currentReservation.mobile || "090-000-0000",
                        identity_type_id: "5417800000000000003",
                        identity_no: "1234567890123",
                        identity_image: mockImage,
                        guest_image: mockImage,
                        guest_signature: mockImage,
                        room_code: currentReservation.room_name
                    };

                    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> SENDING DATA...');

                    $.ajax({
                        url: CHECKIN_API_URL,
                        method: 'POST',
                        data: payload,
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        success: function(res) {
                            if (res.status === 'success') {
                                $('#finalRoomNumber').text(`#${res.data.room_code || currentReservation.room_name}`);
                                showStep('Print');
                                startPrintProgress(res.data);
                            } else {
                                showPopup('error', res.message);
                                $btn.prop('disabled', false).html('<span>CONTINUE</span><i class="bi bi-arrow-right-circle ms-2"></i>');
                            }
                        },
                        error: function(xhr) {
                            showPopup('error', 'System error occurred', 'CONNECTION ERROR');
                            $btn.prop('disabled', false).html('<span>CONTINUE</span><i class="bi bi-arrow-right-circle ms-2"></i>');
                        }
                    });
                });

                function startPrintProgress(data) {
                    let width = 0;
                    $('#printProgress').css('width', '0%');
                    const interval = setInterval(function() {
                        if (width >= 100) {
                            clearInterval(interval);
                            console.log("Printing Coupon for:", data);
                            setTimeout(() => showStep(3), 500);
                        } else {
                            width += 2;
                            $('#printProgress').css('width', width + '%');
                        }
                    }, 60);
                }

                $('#backToSearchBtn').on('click', () => showStep(1));
                $('#finishBtn').on('click', () => window.location.href = HOME_URL);
            });
        });
    </script>
@endpush