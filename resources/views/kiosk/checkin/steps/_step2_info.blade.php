{{-- Step 2: Guest + ID + contact (one page) | Step 3: Photo + complete check-in --}}

{{-- STEP 2: Summary, identity scan, and contact --}}
<div id="step2" class="step-card animate-fade-in step-hidden">
    <div id="reservationDetails" class="details-box mb-4 text-start"></div>

    <div class="scan-area-modern mb-4" id="scanArea">
        <div class="scan-line-animation"></div>

        <div class="status-pill-container mb-4">
            <div class="status-badge-wrapper">
                <div id="LEDCardStatus" class="status-dot-gold"></div>
                <span id="lbCardStatus" class="main-status-text">SYSTEM READY</span>
            </div>
            <div id="lbInstruction" class="sub-instruction-text">Please insert ID Card or Place Passport on scanner
            </div>
        </div>

        <div id="previewIDCard" class="row g-0 p-3 bg-white rounded-3 shadow-sm mx-auto mb-3 text-dark d-none preview-id-card">
            <div class="col-4 d-flex align-items-center justify-content-center">
                <div class="position-relative">
                    <img id="img_IDPhoto_Standard" src="" class="img-fluid rounded border shadow-sm id-photo-standard">
                    <span id="docTypeBadge" class="position-absolute bottom-0 start-50 translate-middle-x badge rounded-pill bg-primary id-card-badge">ID CARD</span>
                </div>
            </div>
            <div class="col-8 ps-3 text-start d-flex flex-column justify-content-center">
                <h5 id="txt_ThaiName_Standard" class="fw-bold mb-0 text-primary"></h5>
                <p id="txt_ENName_Standard" class="mb-1 text-muted small"></p>
                <p id="txt_IDNumber_Standard" class="mb-1 fw-bold text-dark small"></p>
                <div class="d-flex gap-2 mb-1">
                    <span id="txt_DOB_Standard" class="badge bg-light text-dark border fw-normal info-badge-sm"></span>
                    <span id="txt_Expire_Standard" class="badge bg-light text-danger border fw-normal info-badge-sm"></span>
                </div>
                <div class="border-top mt-1 pt-1">
                    <p id="txt_Address_Standard" class="mb-0 text-secondary address-standard"></p>
                </div>
            </div>
        </div>

        <div id="previewPassport" class="p-4 bg-white rounded-3 shadow-sm mx-auto mb-3 text-dark d-none preview-passport">
            <div class="text-center mb-3">
                <div class="position-relative d-inline-block">
                    <img id="img_PassportPhoto_Big" src="" class="img-fluid rounded border shadow-lg passport-photo-big">
                    <span class="position-absolute top-0 end-0 m-2 badge rounded-pill bg-info px-3 py-2 passport-badge">PASSPORT</span>
                </div>
            </div>
            <div class="text-center">
                <h3 id="txt_ENName_Passport" class="fw-bold text-primary mb-1 passport-name"></h3>
                <p id="txt_IDNumber_Passport" class="fw-bold text-dark mb-3 passport-id"></p>

                <div class="d-flex justify-content-center gap-3 mb-3">
                    <div class="p-2 border rounded bg-light min-w-100">
                        <small class="d-block text-muted">Birth Date</small>
                        <span id="txt_DOB_Passport" class="fw-bold"></span>
                    </div>
                    <div class="p-2 border rounded bg-light min-w-100">
                        <small class="d-block text-muted">Expiry Date</small>
                        <span id="txt_Expire_Passport" class="fw-bold text-danger"></span>
                    </div>
                    <div class="p-2 border rounded bg-light min-w-100">
                        <small class="d-block text-muted">Nationality</small>
                        <span id="txt_Nation_Passport" class="fw-bold"></span>
                    </div>
                </div>
            </div>
        </div>

        <div id="scanPrompt">
            <div class="d-flex justify-content-center gap-3 mb-2 text-white-50">
                <i class="bi bi-person-vcard-fill scan-prompt-icon"></i>
                <div class="vr scan-prompt-divider"></div>
                <i class="bi bi-passport scan-prompt-icon"></i>
            </div>
            <p class="fw-bold mb-0 text-white-50">PLACE DOCUMENT ON SCANNER</p>
        </div>

        <div class="progress mt-3 mx-auto scan-progress-wrap">
            <div id="progress_bar" class="progress-bar scan-progress-bar"></div>
        </div>

        <div id="scanStatus" class="scan-badge-status mt-3">
            <span id="scanStatusSpinner" class="spinner-border spinner-border-sm me-2"></span>
            <span id="scanStatusText">Awaiting Identity Verification...</span>
        </div>
    </div>

    <div id="contactInputArea" class="px-3 px-md-4 mb-4 text-start checkin-step2-contact">
        <div class="d-flex align-items-center mb-2">
            <i class="bi bi-person-lines-fill text-white me-2"></i>
            <label class="small text-white fw-bold mb-0 text-uppercase">Contact (Required before photo)</label>
        </div>
        <p class="small text-white-50 mb-3">We’ll use this for your stay only. You can edit if needed.</p>

        <div class="row g-3">
            <div class="col-12">
                <div class="form-floating">
                    <input type="email" id="input_email" autocomplete="off"
                        class="form-control kiosk-input kiosk-keyboard-input" placeholder="Email" required>
                    <label for="input_email" class="text-muted small">Email Address</label>
                </div>
            </div>
            <div class="col-6">
                <div class="form-floating">
                    <input type="tel" id="input_phone" autocomplete="off"
                        class="form-control kiosk-input kiosk-keyboard-input" placeholder="Phone" required>
                    <label for="input_phone" class="text-muted small">Phone Number</label>
                </div>
            </div>
            <div class="col-6">
                <div class="form-floating">
                    <input type="tel" id="input_mobile" autocomplete="off"
                        class="form-control kiosk-input kiosk-keyboard-input" placeholder="Mobile" required>
                    <label for="input_mobile" class="text-muted small">Mobile Number</label>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-3 px-4 mb-4">
        <button type="button" id="backToSearchBtn" class="kiosk-btn-secondary w-50 py-3">BACK</button>
        <button type="button" id="wizardCombinedNextBtn" class="kiosk-btn-success w-50 py-3" disabled>
            <span>CONTINUE TO PHOTO</span>
            <i class="bi bi-arrow-right-circle ms-2"></i>
        </button>
    </div>
</div>

{{-- STEP 3: Guest photo + complete check-in --}}
<div id="step3" class="step-card animate-fade-in step-hidden">
    <div class="guest-photo-layout">
        <div class="guest-photo-camera-block">
            <div class="camera-capture-card camera-capture-card--fullscreenish mb-0">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2 px-1">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-camera-fill text-dark"></i>
                        <label class="small text-dark fw-bold mb-0 text-uppercase">Guest photo &amp; check-in</label>
                    </div>
                    <span id="guestPhotoStatus" class="camera-status-badge pending">Waiting for capture</span>
                </div>

                <div class="camera-stage camera-stage--guided camera-stage--portrait">
                    <video id="guestCameraPreview" class="camera-preview" autoplay playsinline muted></video>
                    <img id="guestPhotoPreview" class="camera-preview d-none" alt="Guest photo preview">
                    <div id="cameraPlaceholder" class="camera-placeholder d-none">
                        <i class="bi bi-camera-video-off"></i>
                        <span id="cameraPlaceholderTitle">Camera unavailable</span>
                        <small id="cameraPlaceholderHint" class="camera-placeholder-hint d-none"></small>
                    </div>
                    <div class="camera-frame-overlay" aria-hidden="true">
                        <svg class="camera-guidance-svg" viewBox="0 0 100 140" preserveAspectRatio="xMidYMid slice">
                            <defs>
                                <mask id="cameraGuidanceMaskPerson">
                                    <rect width="100" height="140" fill="white" />
                                    <path fill="black" class="camera-person-cutout"
                                        d="M50 18
                                           C34 18 24 30 24 44
                                           C24 54 28 62 34 66
                                           L28 82 L22 104 L18 136
                                           L82 136 L78 104 L72 82 L66 66
                                           C72 62 76 54 76 44
                                           C76 30 66 18 50 18Z" />
                                </mask>
                            </defs>
                            <rect width="100" height="140" fill="rgba(250, 250, 252, 0.72)" mask="url(#cameraGuidanceMaskPerson)" />
                            <path fill="none" stroke="rgba(255,255,255,0.95)" stroke-width="1.1" vector-effect="non-scaling-stroke"
                                class="camera-guidance-silhouette"
                                d="M50 18
                                   C34 18 24 30 24 44
                                   C24 54 28 62 34 66
                                   L28 82 L22 104 L18 136
                                   L82 136 L78 104 L72 82 L66 66
                                   C72 62 76 54 76 44
                                   C76 30 66 18 50 18Z" />
                        </svg>
                    </div>
                    <div id="faceGuideToast" class="face-guide-toast d-none" role="status">นิ่งๆ…</div>
                </div>

                <canvas id="guestPhotoCanvas" class="d-none"></canvas>

                <div class="d-flex gap-2 mt-3 px-1">
                    <button type="button" id="captureGuestPhotoBtn" class="kiosk-btn-main camera-action-btn">
                        <i class="bi bi-camera"></i>
                        <span>CAPTURE PHOTO</span>
                    </button>
                    <button type="button" id="retakeGuestPhotoBtn" class="kiosk-btn-secondary camera-action-btn d-none">
                        <i class="bi bi-arrow-repeat"></i>
                        <span>RETAKE</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="guest-photo-hint-panel">
            <p class="guest-photo-hint-th mb-1">จัดหน้าให้อยู่กลางกรอบ แล้วรอจนกว่าจะนับ 3–2–1</p>
            <p class="guest-photo-hint-en text-muted small mb-2">Stay centered until the countdown finishes — then the photo is taken automatically.</p>
            <p class="guest-photo-hint-en text-muted small mb-0 fw-bold">เมื่อได้รูปแล้ว กด “COMPLETE CHECK-IN” เพื่อเข้าห้องพัก</p>
        </div>
    </div>

    <div class="d-flex gap-3 px-4 mb-4 mt-3">
        <button type="button" id="wizardPhotoBackBtn" class="kiosk-btn-secondary w-50 py-3">BACK</button>
        <button type="button" id="nextToPrintBtn" class="kiosk-btn-success w-50 py-3" disabled>
            <span>COMPLETE CHECK-IN</span>
            <i class="bi bi-house-check ms-2"></i>
        </button>
    </div>
</div>
