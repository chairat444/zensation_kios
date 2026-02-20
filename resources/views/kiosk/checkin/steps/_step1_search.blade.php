{{-- STEP 1: SEARCH --}}
<div id="step1" class="step-card active animate-fade-in">
    <div class="alert-icon-primary mb-4">
        <i class="bi bi-search"></i>
    </div>
    <h2 class="step-title">FIND YOUR BOOKING</h2>
    <p class="step-subtitle text-muted fs-4 mb-5">Enter Voucher, Name, or Booking ID</p>

    <div class="px-5">
        <div class="input-group-lg mb-4">
            <input type="text" id="reservationInput" value="RES5186"
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
