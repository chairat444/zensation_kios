@extends('layouts.app')

@push('styles')
    {{-- โหลด CSS เฉพาะสำหรับหน้านี้ --}}
    <link rel="stylesheet" href="{{ asset('css/checkin.css') }}">
@endpush

@section('content')
    <div class="checkin-card text-center">

        {{-- *********************************************** --}}
        {{-- ********** STEP 1: Search Form ********** --}}
        {{-- *********************************************** --}}
        <div id="step1" class="step-content active">
            <h1 class="display-5 fw-bold mb-3">SELF CHECK-IN</h1>
            <p class="fs-5 opacity-75 mb-4">Please enter your Reservation Number or Voucher Number.</p>

            <div class="mb-3">
                <input type="text" id="reservationInput" class="form-control kiosk-input-lg"
                       placeholder="e.g., RES3612" maxlength="15">
            </div>

            {{-- พื้นที่แสดงข้อความ Error --}}
            <div id="searchMessage" class="alert alert-danger d-none" role="alert"></div>

            <div class="d-grid gap-3 mt-4">
                <button id="searchBtn" class="kiosk-btn btn-reserve">
                    <i class="bi bi-search"></i>
                    <span>Search Reservation</span>
                </button>
                <a href="{{ route('kiosk.home') }}" class="kiosk-btn btn-secondary-glass">
                    <i class="bi bi-house-door-fill"></i>
                    <span>Back to Main Menu</span>
                </a>
            </div>
        </div>

        {{-- *********************************************** --}}
        {{-- ********** STEP 2: Confirmation & Scan ********** --}}
        {{-- *********************************************** --}}
        <div id="step2" class="step-content">
            <h2 class="display-6 fw-bold mb-4">2. Confirm Details & Scan ID</h2>

            <div id="reservationDetails" class="details-box text-start mb-4">
                {{-- Reservation details will be loaded here by jQuery --}}
            </div>

            <div class="scan-box text-center p-4 mb-4" id="scanArea">
                <i class="bi bi-person-bounding-box"></i>
                <p class="fw-bold fs-5 mt-2">Please Scan Your ID Card / Passport</p>
                <div id="scanStatus" class="scan-status mt-2">
                    <span class="spinner-border spinner-border-sm me-2 animate-spin"></span>
                    Awaiting Scan...
                </div>
            </div>

            <div class="d-grid gap-3">
                <button id="checkInConfirmBtn" class="kiosk-btn btn-checkin" disabled>
                    <i class="bi bi-key-fill"></i>
                    <span>Check-in & Get Key</span>
                </button>
                <button id="backToSearchBtn" class="kiosk-btn btn-secondary-glass">
                    <i class="bi bi-arrow-left"></i>
                    <span>Start Over</span>
                </button>
            </div>
        </div>

        {{-- *********************************************** --}}
        {{-- ********** STEP 3: Check-in Complete ********** --}}
        {{-- *********************************************** --}}
        <div id="step3" class="step-content">
            <div class="success-box">
                <i class="bi bi-check-circle-fill mb-3"></i>
                <h1 class="display-5 fw-bold text-success mb-4">CHECK-IN SUCCESSFUL!</h1>

                <p class="fs-5 opacity-75 mb-2">Your Room is:</p>
                <p id="finalRoomNumber" class="room-number">#000</p>
                <p id="finalRoomType" class="fs-4 fw-bold opacity-90 mb-4">Superior Room</p>

                <p class="fs-5 fw-semibold mb-4">
                    Please Collect Your Room Key Below.
                </p>

                <div class="d-grid gap-3">
                    <button id="finishBtn" class="kiosk-btn btn-reserve w-100">
                        <i class="bi bi-door-open-fill"></i>
                        <span>Finish & Main Menu</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- (jQuery และ Bootstrap JS ถูกโหลดใน app_kiosk แล้ว) --}}

    <script>
        // ใช้ jQuery's document ready
        $(document).ready(function() {

            // --- Configuration (รับค่าจาก Controller) ---
            const HOTEL_ID = "{{ $hotelId ?? config('kiosk.hotel.hotel_id') }}";

            // --- API Routes (ใช้ route() helper ของ Laravel) ---
            // เราต้องสร้าง Route เหล่านี้ใน routes/web.php หรือ routes/api.php
            const SEARCH_API_URL = "{{ route('api.kiosk.search') }}";
            const CHECKIN_API_URL = "{{ route('api.kiosk.checkin.perform') }}";

            // --- CSRF Token Setup for all jQuery AJAX requests ---
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            let currentReservation = null;

            // --- Elements ---
            const $step1 = $('#step1');
            const $step2 = $('#step2');
            const $step3 = $('#step3');
            const $searchBtn = $('#searchBtn');
            const $searchMessage = $('#searchMessage');
            const $reservationInput = $('#reservationInput');
            const $checkInConfirmBtn = $('#checkInConfirmBtn');
            const $scanStatus = $('#scanStatus');

            // --- Helper Functions ---
            function showStep(stepNum) {
                $('.step-content').removeClass('active');
                $('#step'D + stepNum).addClass('active');
            }

            function displayMessage(type, message) {
                $searchMessage.text(message).removeClass('d-none alert-success alert-danger');
                if (type === 'error') {
                    $searchMessage.addClass('alert-danger');
                } else {
                    $searchMessage.addClass('alert-success');
                }
            }

            function setSearchButtonLoading(isLoading) {
                if (isLoading) {
                    $searchBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Searching...');
                } else {
                    $searchBtn.prop('disabled', false).html('<i class="bi bi-search"></i> <span>Search Reservation</span>');
                }
            }

            // **********************************************
            // ***** STEP 1: Search Reservation Logic *****
            // **********************************************
            $searchBtn.on('click', function() {
                const resId = $reservationInput.val().trim().toUpperCase();
                $searchMessage.addClass('d-none');

                if (!resId) {
                    displayMessage('error', 'Please enter a Reservation or Voucher Number.');
                    return;
                }

                setSearchButtonLoading(true);

                // --- START REAL API CALL ---
                $.ajax({
                    url: SEARCH_API_URL,
                    method: 'POST',
                    data: {
                        reservation_id: resId
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            currentReservation = response.data;
                            renderDetailsAndProceed(currentReservation);
                        } else {
                            displayMessage('error', response.message || 'Reservation not found.');
                            setSearchButtonLoading(false);
                        }
                    },
                    error: function(jqXHR) {
                        displayMessage('error', jqXHR.responseJSON?.message || 'Error connecting to the server.');
                        setSearchButtonLoading(false);
                    }
                });
                // --- END REAL API CALL ---
            });


            // **********************************************
            // ***** STEP 2: Display Details and Scan *****
            // **********************************************
            function renderDetailsAndProceed(data) {
                const checkinDate = new Date(data.check_in).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
                const checkoutDate = new Date(data.check_out).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });

                const detailsHtml = `
                    <div class="row g-3">
                        <div class="col-6"><span class="label">Res. ID:</span><br><span class="value">${data.reservation_id}</span></div>
                        <div class="col-6"><span class="label">Guest:</span><br><span class="value">${data.guest_name}</span></div>
                        <div class="col-6"><span class="label">Room Type:</span><br><span class="value">${data.room_name}</span></div>
                        <div class="col-6"><span class="label">Room No.:</span><br><span class="value text-success fw-bolder">${data.room_code || 'TBA'}</span></div>
                        <div class="col-6"><span class="label">Check-in:</span><br><span class="value">${checkinDate}</span></div>
                        <div class="col-6"><span class="label">Check-out:</span><br><span class="value">${checkoutDate}</span></div>
                    </div>
                `;
                $('#reservationDetails').html(detailsHtml);
                showStep(2);

                // Simulate ID Scan (3 seconds)
                $scanStatus.html('<span class="spinner-border spinner-border-sm me-2 animate-spin"></span> Awaiting Scan...').removeClass('complete');
                $checkInConfirmBtn.prop('disabled', true);

                setTimeout(() => {
                    // TODO: เรียก API "UpdateGuestDetails" ที่นี่ (ถ้ามี)
                    $scanStatus.html('<i class="bi bi-check-circle-fill"></i> ID Scan Completed!').addClass('complete');
                    $checkInConfirmBtn.prop('disabled', false);
                }, 3000);
            }

            // **********************************************
            // ***** STEP 2 -> 3: Check-in Confirmation *****
            // **********************************************
            $checkInConfirmBtn.on('click', function() {
                $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Checking In...');

                if (!currentReservation) return;

                // --- START REAL API CALL ---
                $.ajax({
                    url: CHECKIN_API_URL,
                    method: 'POST',
                    data: {
                        reservation_id: currentReservation.reservation_id,
                        room_code: currentReservation.room_code
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            // TODO: เรียก API "CreateKeyCard" ที่นี่ (ถ้ามี)
                            $('#finalRoomNumber').text(`#${response.data.room_code}`);
                            $('#finalRoomType').text(response.data.room_name);
                            showStep(3);
                        } else {
                            alert('Check-in failed: ' + (response.message || 'Unknown error'));
                            $checkInConfirmBtn.prop('disabled', false).html('<i class="bi bi-key-fill"></i> <span>Check-in & Get Key</span>');
                        }
                    },
                    error: function(jqXHR) {
                        alert('An error occurred during check-in: ' + (jqXHR.responseJSON?.message || 'Server error'));
                        $checkInConfirmBtn.prop('disabled', false).html('<i class="bi bi-key-fill"></i> <span>Check-in & Get Key</span>');
                    }
                });
                // --- END REAL API CALL ---
            });

            // **********************************************
            // ***** Navigation and Reset Logic *****
            // **********************************************
            $('#backToSearchBtn').on('click', function() {
                $reservationInput.val('');
                setSearchButtonLoading(false);
                showStep(1);
            });

            $('#finishBtn').on('click', function() {
                window.location.href = "{{ route('kiosk.home') }}";
            });

            // เริ่มต้นที่ Step 1
            showStep(1);
        });
    </script>
@endpush
