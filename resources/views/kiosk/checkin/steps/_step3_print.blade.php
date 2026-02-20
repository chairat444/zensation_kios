<div id="step3" class="step-card animate-fade-in" style="display: none;">
    <div class="text-center py-5">
        <div class="printer-icon-wrapper mb-4 d-print-none">
            <i class="bi bi-printer text-primary animate-pulse" style="font-size: 6rem;"></i>
        </div>

        <h2 class="step-title d-print-none">PRINTING YOUR WIFI VOUCHER</h2>

        <div class="ticket-visual-wrapper my-4 animate-fade-in">
            <div class="virtual-ticket">
                <div class="ticket-header">
                    <h5 class="fw-bold mb-0">ZENSATION</h5>
                    <small>WIFI ACCESS VOUCHER</small>
                </div>
                <div class="ticket-body">
                    <div class="d-flex justify-content-between mb-1">
                        <span>ROOM:</span>
                        <span id="view-room" class="fw-bold">-</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>USER:</span>
                        <span id="view-user" class="fw-bold">-</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>PASS:</span>
                        <span id="view-pass" class="fw-bold">-</span>
                    </div>
                </div>
                <div class="ticket-footer">
                    <p class="mb-0 small text-muted">Please keep this for your stay.</p>
                </div>
                <div class="jagged-edge"></div>
            </div>
        </div>

        <div id="printProgressWrapper" class="progress mt-4 mx-auto d-print-none" style="width: 70%; height: 25px; border-radius: 15px;">
            <div id="printProgress" class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 0%"></div>
        </div>

        <div id="nextToKeycardArea" class="mt-5 d-print-none" style="display: none;">
            <button id="goToKeycardBtn" class="kiosk-btn-main w-100 py-3 shadow-lg btn-lg">
                <span>GO TO THE NEXT PAGE</span>
                <i class="bi bi-arrow-right-circle ms-2"></i>
            </button>
        </div>
    </div>
</div>

<style>
    /* ตู้ตั๋วจิตจำลองบนหน้าจอ */
    .ticket-visual-wrapper {
        perspective: 1000px;
        display: flex;
        justify-content: center;
    }

    .virtual-ticket {
        background: #fff;
        width: 280px;
        padding: 20px 20px 10px 20px;
        color: #333;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        border-radius: 4px 4px 0 0;
        font-family: 'Courier New', Courier, monospace;
        position: relative;
        text-align: left;
        transform: rotateX(10deg);
        animation: ticketSlideDown 1s ease-out;
    }

    .ticket-header {
        text-align: center;
        border-bottom: 1px dashed #ccc;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }

    .ticket-body {
        font-size: 1.1rem;
        padding-bottom: 15px;
        border-bottom: 1px dashed #ccc;
    }

    .ticket-footer {
        padding-top: 10px;
        text-align: center;
        font-style: italic;
    }

    /* รอยหยักขอบกระดาษ */
    .jagged-edge {
        position: absolute;
        bottom: -10px;
        left: 0;
        width: 100%;
        height: 10px;
        background: radial-gradient(circle, transparent, transparent 50%, #fff 50%, #fff 100%) -5px -5px / 10px 10px repeat-x;
    }

    @keyframes ticketSlideDown {
        from { transform: translateY(-20px) rotateX(20deg); opacity: 0; }
        to { transform: translateY(0) rotateX(10deg); opacity: 1; }
    }

    /* ซ่อน Virtual Ticket ตอนสั่งพิมพ์จริง (ถ้ายังใช้ window.print) */
    @media print {
        .ticket-visual-wrapper { display: none !important; }
    }
    </style>