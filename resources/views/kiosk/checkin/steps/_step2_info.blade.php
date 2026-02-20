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
