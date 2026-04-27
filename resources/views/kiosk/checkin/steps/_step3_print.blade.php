<div id="stepPrint" class="step-card animate-fade-in step-hidden">
    <div class="text-center py-5">
        <div class="printer-icon-wrapper mb-4 d-print-none">
            <i class="bi bi-printer text-primary animate-pulse printer-icon-lg"></i>
        </div>

        <h2 class="step-title d-print-none">PRINTING YOUR WIFI VOUCHER</h2>

        <div class="ticket-visual-wrapper my-4 animate-fade-in">
            <div class="virtual-ticket">
                <div class="ticket-header">
                    <h5 class="fw-bold mb-0">ZENSATION THE RESIDENCE</h5>
                    <div class="ticket-divider">================================</div>
                </div>

                <div class="ticket-section mb-2">
                    <div class="ticket-row"><span>Voucher:</span> <span id="view-voucher">-</span></div>
                    <div class="ticket-row"><span>Booking ID:</span> <span id="view-booking">-</span></div>
                    <div class="ticket-row"><span>Guest:</span> <span id="view-guest" class="text-truncate ticket-guest-name">-</span></div>
                    <div class="ticket-divider-thin">--------------------------------</div>
                    <div class="ticket-row d-flex justify-content-between">
                        <span>Arrival Date:</span> <span id="view-arrival">-</span>
                    </div>
                    <div class="ticket-row d-flex justify-content-between">
                        <span>Departure Date:</span> <span id="view-departure">-</span>
                    </div>
                    <div class="ticket-divider-thin">--------------------------------</div>
                    <div class="ticket-row"><span>Room Type:</span> <span id="view-room-type">-</span></div>
                    <div class="ticket-row"><span>Room Number:</span> <span id="view-room" class="fw-bold">-</span></div>
                    <div class="ticket-divider">================================</div>
                </div>

                <div class="ticket-body text-center mb-2">
                    <div class="fw-bold mb-1">● WIFI ACCESS ●</div>
                    <div class="d-flex justify-content-between">
                        <span>USERNAME:</span>
                        <span id="view-user" class="fw-bold text-primary">-</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>PASSWORD:</span>
                        <span id="view-pass" class="fw-bold text-primary">-</span>
                    </div>
                    <div class="ticket-divider mt-2">================================</div>
                </div>

                <div class="ticket-footer">
                    <div class="fw-bold">CONTACT US</div>
                    <div>(+66) 2-286-1216</div>
                    <div>(+66) 8-8112-2001</div>
                    <div class="ticket-divider-thin">--------------------------------</div>
                    <div class="small">ISSUED BY KIOSK</div>
                    <div class="small" id="view-time">-</div>
                </div>
                <div class="jagged-edge"></div>
            </div>
        </div>

        <div id="printProgressWrapper" class="progress mt-4 mx-auto d-print-none print-progress-wrapper">
            <div id="printProgress" class="progress-bar progress-bar-striped progress-bar-animated bg-success print-progress-bar"></div>
        </div>

        <div id="nextToKeycardArea" class="mt-5 d-print-none step-hidden">
            <button id="goToKeycardBtn" class="kiosk-btn-main w-100 py-3 shadow-lg btn-lg">
                <span>GO TO THE NEXT PAGE</span>
                <i class="bi bi-arrow-right-circle ms-2"></i>
            </button>
        </div>
    </div>
</div>

