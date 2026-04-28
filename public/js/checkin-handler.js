$(function () {
    let currentReservation = null;
    let wSocket = null;
    let scardData = null;
    let guestPhotoData = null;
    let guestCameraStream = null;
    let lastCheckinWizardStep = null;
    let mpFaceDetector = null;
    let guestFaceGuideTimer = null;
    let guestFaceAutoDone = false;
    let guestFaceStableStreak = 0;
    let guestFaceGuidePhase = 'idle';
    let guestFacePhaseAt = 0;
    /** Consecutive in-frame detections (200ms tick) before “lock”. */
    const FACE_STREAK_MIN = 9;
    const FACE_HOLD_MS = 1400;
    const FACE_COUNT_TICK_MS = 850;
    const FACE_GUIDE_TICK_MS = 180;
    const checkinApp = document.getElementById("checkinApp");

    function stopGuestCamera() {
        if (guestCameraStream) {
            guestCameraStream.getTracks().forEach(function (t) { t.stop(); });
            guestCameraStream = null;
        }
        const v = document.getElementById('guestCameraPreview');
        if (v) v.srcObject = null;
    }

    /** Prefer real webcams; skip TWAIN/scanner-style devices that register as videoinput. */
    function pickGuestCameraDeviceId(videoInputs) {
        if (!videoInputs || !videoInputs.length) return null;
        const labelOk = function (label) {
            const s = (label || '').toLowerCase();
            if (!s) return true;
            if (/(scanner|twain|document\s*camera|capture\s*source)/i.test(s)) return false;
            return true;
        };
        const scored = videoInputs.filter(function (d) { return labelOk(d.label); });
        const pool = scored.length ? scored : videoInputs;
        const prefer = function (re) {
            const hit = pool.find(function (d) { return re.test((d.label || '').toLowerCase()); });
            return hit ? hit.deviceId : null;
        };
        return (
            prefer(/logitech/) ||
            prefer(/webcam|hd\s*pro|c92\d|c93\d|streamcam|facetime/) ||
            (pool[0] && pool[0].deviceId) ||
            null
        );
    }

    async function openGuestCameraStream() {
        const baseVideo = { width: { ideal: 1280 }, height: { ideal: 720 } };
        let stream = await navigator.mediaDevices.getUserMedia({ video: baseVideo, audio: false });
        let devices = await navigator.mediaDevices.enumerateDevices();
        let idealId = pickGuestCameraDeviceId(devices.filter(function (d) { return d.kind === 'videoinput'; }));
        const currentId = stream.getVideoTracks()[0] && stream.getVideoTracks()[0].getSettings().deviceId;
        if (idealId && idealId !== currentId) {
            stream.getTracks().forEach(function (t) { t.stop(); });
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: Object.assign({ deviceId: { ideal: idealId } }, baseVideo),
                    audio: false
                });
            } catch (e) {
                stream = await navigator.mediaDevices.getUserMedia({ video: baseVideo, audio: false });
            }
        }
        return stream;
    }

    /** Wizard chip index: 2 = guest + ID + contact, 3 = photo + complete check-in (1 = search done). */
    function syncWizardNav(activeStep) {
        const $nav = $('#checkinWizardNav');
        if (!$nav.length) return;
        if (!activeStep || activeStep < 2) {
            $nav.addClass('d-none');
            return;
        }
        $nav.removeClass('d-none');
        $nav.find('.wizard-step').each(function () {
            const s = +$(this).data('wizard-step');
            $(this).removeClass('is-active is-done');
            if (s < activeStep) $(this).addClass('is-done');
            if (s === activeStep) $(this).addClass('is-active');
        });
    }

    function resetGuestFaceGuideState() {
        guestFaceAutoDone = false;
        guestFaceStableStreak = 0;
        guestFaceGuidePhase = 'idle';
        guestFacePhaseAt = 0;
    }

    function stopGuestFaceGuide() {
        if (guestFaceGuideTimer) {
            clearInterval(guestFaceGuideTimer);
            guestFaceGuideTimer = null;
        }
        resetGuestFaceGuideState();
        $('#faceGuideToast').removeClass('face-guide-toast--countdown').empty().addClass('d-none');
        $('.camera-guidance-silhouette').removeClass('is-ready');
    }

    /** Stricter fit to silhouette (smaller sweet spot, face size band). */
    function isFaceInGuideSilhouette(bbox, vw, vh) {
        const cx = bbox.originX + bbox.width / 2;
        const cy = bbox.originY + bbox.height / 2;
        const ecx = 0.5 * vw;
        const ecy = 0.39 * vh;
        const rx = 0.16 * vw;
        const ry = 0.29 * vh;
        const norm = Math.pow((cx - ecx) / rx, 2) + Math.pow((cy - ecy) / ry, 2);
        const wOk = bbox.width >= vw * 0.14 && bbox.width <= vw * 0.38;
        return norm <= 0.88 && wOk;
    }

    function scheduleGuestFaceGuide() {
        stopGuestFaceGuide();
        const video = document.getElementById('guestCameraPreview');
        if (!video || guestPhotoData || $(video).hasClass('d-none')) return;

        ensureMpFaceDetector().then(function (det) {
            if (!det || guestPhotoData) return;
            guestFaceGuideTimer = setInterval(function () {
                if (guestPhotoData || !$('#step3').hasClass('active')) return;
                const v = document.getElementById('guestCameraPreview');
                if (!v || v.readyState < 2 || !v.videoWidth || !mpFaceDetector) return;
                let dets;
                try {
                    dets = mpFaceDetector.detectForVideo(v, performance.now());
                } catch (e) {
                    return;
                }
                const list = dets && dets.detections ? dets.detections : [];
                const bb = list.length ? list[0].boundingBox : null;
                const vw = v.videoWidth;
                const vh = v.videoHeight;
                const aligned = bb && isFaceInGuideSilhouette(bb, vw, vh);

                if (!aligned) {
                    resetGuestFaceGuideState();
                    $('#faceGuideToast').removeClass('face-guide-toast--countdown').empty().addClass('d-none');
                    $('.camera-guidance-silhouette').removeClass('is-ready');
                    return;
                }

                const now = performance.now();
                guestFaceStableStreak += 1;

                if (guestFaceStableStreak < FACE_STREAK_MIN) {
                    const need = FACE_STREAK_MIN - guestFaceStableStreak;
                    const sec = Math.max(1, Math.ceil(need * FACE_GUIDE_TICK_MS / 1000));
                    $('#faceGuideToast').removeClass('face-guide-toast--countdown')
                        .html(
                            '<span class="face-toast-main">ค้างในตำแหน่งนี้</span>' +
                            '<span class="face-toast-sub">Hold position · ~' + sec + 's</span>'
                        )
                        .removeClass('d-none');
                    $('.camera-guidance-silhouette').removeClass('is-ready');
                    return;
                }

                if (guestFaceGuidePhase === 'idle') {
                    guestFaceGuidePhase = 'hold';
                    guestFacePhaseAt = now;
                }

                if (guestFaceGuidePhase === 'hold') {
                    $('.camera-guidance-silhouette').addClass('is-ready');
                    $('#faceGuideToast').removeClass('face-guide-toast--countdown')
                        .html(
                            '<span class="face-toast-main">นิ่งๆ…</span>' +
                            '<span class="face-toast-sub">Hold still — preparing capture</span>'
                        )
                        .removeClass('d-none');
                    if (now - guestFacePhaseAt >= FACE_HOLD_MS) {
                        guestFaceGuidePhase = 'count';
                        guestFacePhaseAt = now;
                    }
                    return;
                }

                if (guestFaceGuidePhase === 'count') {
                    const idx = Math.floor((now - guestFacePhaseAt) / FACE_COUNT_TICK_MS);
                    if (idx >= 3) {
                        if (!guestFaceAutoDone) {
                            guestFaceAutoDone = true;
                            captureGuestPhoto();
                        }
                        return;
                    }
                    const n = 3 - idx;
                    $('#faceGuideToast').addClass('face-guide-toast--countdown')
                        .html(
                            '<span class="face-count-num">' + n + '</span>' +
                            '<span class="face-toast-sub">Stay still</span>'
                        )
                        .removeClass('d-none');
                }
            }, FACE_GUIDE_TICK_MS);
        }).catch(function (e) {
            console.warn('Face guide unavailable (manual capture still works):', e);
        });
    }

    function ensureMpFaceDetector() {
        if (mpFaceDetector) return Promise.resolve(mpFaceDetector);
        return import('https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14/+esm').then(function (vision) {
            return vision.FilesetResolver.forVisionTasks(
                'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14/wasm'
            ).then(function (fileset) {
                return vision.FaceDetector.createFromOptions(fileset, {
                    baseOptions: {
                        modelAssetPath: 'https://storage.googleapis.com/mediapipe-models/face_detector/blaze_face_short_range/float16/latest/blaze_face_short_range.tflite',
                        delegate: 'CPU'
                    },
                    runningMode: 'VIDEO',
                    minDetectionConfidence: 0.62
                });
            });
        }).then(function (detector) {
            mpFaceDetector = detector;
            return mpFaceDetector;
        });
    }

    const KIOSK_CONFIG = {
        searchUrl: checkinApp?.dataset.searchUrl || "",
        checkinUrl: checkinApp?.dataset.checkinUrl || "",
        homeUrl: checkinApp?.dataset.homeUrl || "/",
        csrfToken: checkinApp?.dataset.csrfToken || "",
        cardDispenserEnabled: checkinApp?.dataset.cardDispenserEnabled === "1",
        cardDispenserUrl: checkinApp?.dataset.cardDispenserUrl || "",
    };

    function triggerCardDispenserAfterCheckin() {
        if (!KIOSK_CONFIG.cardDispenserEnabled || !KIOSK_CONFIG.cardDispenserUrl) return;
        const url = KIOSK_CONFIG.cardDispenserUrl.trim();
        if (!url) return;
        fetch(url, { method: "POST", mode: "cors", cache: "no-store" })
            .then(function (r) { return r.json().catch(function () { return null; }); })
            .then(function (j) {
                if (!j || !j.ok) {
                    console.warn("Card dispenser:", j && j.error ? j.error : "no response");
                }
            })
            .catch(function (e) {
                console.warn("Card dispenser request failed:", e);
            });
    }

    // --- ปรับแก้: URL ของ VB.NET Print Server (เปลี่ยนจาก ws เป็น http) ---
    const PRINT_SERVER_URL = "http://localhost:8081/print/";

    function setDeviceStatus(device, mode, text) {
        const map = {
            agent: '#deviceStatusAgent',
            passport: '#deviceStatusPassport'
        };
        const selector = map[device];
        if (!selector) return;
        const $chip = $(selector);
        if (!$chip.length) return;
        $chip.removeClass('is-online is-offline is-idle is-error')
            .addClass(`is-${mode}`);
        $chip.find('.status-label').text(text);
    }

    function updateGuestPhotoStatus(mode, text) {
        const $status = $('#guestPhotoStatus');
        if (!$status.length) return;
        $status.removeClass('pending ready error').addClass(mode).text(text);
    }

    function resetCameraPlaceholderCopy() {
        $('#cameraPlaceholderTitle').text('Camera unavailable');
        $('#cameraPlaceholderHint').addClass('d-none').text('');
    }

    function showGuestCameraError(badgeText, title, hintText) {
        updateGuestPhotoStatus('error', badgeText);
        $('#cameraPlaceholderTitle').text(title || 'Camera unavailable');
        const $hint = $('#cameraPlaceholderHint');
        if (hintText) {
            $hint.removeClass('d-none').text(hintText);
        } else {
            $hint.addClass('d-none').text('');
        }
    }

    async function startGuestCamera() {
        const video = document.getElementById('guestCameraPreview');
        const placeholder = document.getElementById('cameraPlaceholder');
        const photo = document.getElementById('guestPhotoPreview');
        const retakeBtn = document.getElementById('retakeGuestPhotoBtn');
        if (!video) return;

        if (guestCameraStream) {
            video.srcObject = guestCameraStream;
            if ($('#step3').hasClass('active') && !guestPhotoData) {
                scheduleGuestFaceGuide();
            }
            return;
        }

        resetCameraPlaceholderCopy();

        if (!window.isSecureContext) {
            $(video).addClass('d-none');
            $(photo).addClass('d-none');
            $(placeholder).removeClass('d-none');
            showGuestCameraError(
                'Camera blocked (page not secure)',
                'Insecure page',
                'Open this kiosk with https:// or with http://127.0.0.1/ or http://localhost/ only. Plain http:// plus a LAN IP (e.g. 192.168.x.x) cannot use the camera in Chrome.'
            );
            console.warn('Guest camera: insecure context', location.href);
            return;
        }

        try {
            guestCameraStream = await openGuestCameraStream();
            video.srcObject = guestCameraStream;
            $(video).removeClass('d-none');
            $(photo).addClass('d-none');
            $(placeholder).addClass('d-none');
            $(retakeBtn).addClass('d-none');
            resetCameraPlaceholderCopy();
            updateGuestPhotoStatus('pending', 'Ready to capture');
            if ($('#step3').hasClass('active') && !guestPhotoData) {
                scheduleGuestFaceGuide();
            }
        } catch (error) {
            const name = error && error.name;
            const msg = error && error.message;
            console.warn('Guest camera:', name, msg);
            $(video).addClass('d-none');
            $(photo).addClass('d-none');
            $(placeholder).removeClass('d-none');
            if (name === 'NotAllowedError') {
                showGuestCameraError(
                    'Permission denied',
                    'Camera permission blocked',
                    'Click the lock or tune icon left of the address bar → Permissions → set Camera to Allow. If it is already Allow, open chrome://settings/content/camera and remove this site from Blocked, then reload the page.'
                );
            } else if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
                showGuestCameraError('No camera', 'No camera found', 'Plug in the webcam and refresh, or check Device Manager in Windows.');
            } else if (name === 'SecurityError') {
                showGuestCameraError('Camera blocked', 'Security error', 'Use HTTPS or localhost. Mixed content or insecure embedding can block the camera.');
            } else {
                showGuestCameraError('Camera unavailable', 'Camera unavailable', msg || '');
            }
        }
    }

    function captureGuestPhoto() {
        stopGuestFaceGuide();
        const video = document.getElementById('guestCameraPreview');
        const canvas = document.getElementById('guestPhotoCanvas');
        const photo = document.getElementById('guestPhotoPreview');
        if (!video || !canvas || !photo || !video.videoWidth) {
            guestFaceAutoDone = false;
            updateGuestPhotoStatus('error', 'Camera not ready');
            if ($('#step3').hasClass('active') && !guestPhotoData) {
                scheduleGuestFaceGuide();
            }
            return;
        }

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        guestPhotoData = canvas.toDataURL('image/jpeg', 0.92);

        photo.src = guestPhotoData;
        $(photo).removeClass('d-none');
        $(video).addClass('d-none');
        $('#retakeGuestPhotoBtn').removeClass('d-none');
        updateGuestPhotoStatus('ready', 'Photo captured');
        checkInputs();
    }

    function resetGuestPhoto() {
        guestPhotoData = null;
        stopGuestFaceGuide();
        stopGuestCamera();
        $('#guestPhotoPreview').addClass('d-none').attr('src', '');
        $('#guestCameraPreview').removeClass('d-none');
        $('#retakeGuestPhotoBtn').addClass('d-none');
        updateGuestPhotoStatus('pending', 'Ready to capture');
        startGuestCamera().then(function () {
            if ($('#step3').hasClass('active')) scheduleGuestFaceGuide();
        });
        checkInputs();
    }

    // ==========================================
    // 1. CONNECTION MANAGER
    // ==========================================
    async function startConnections() {
        // 1. เชื่อมเครื่องอ่านบัตรประชาชนเดิม
        connectTDKW();

        // 2. เริ่มทำงานเครื่องสแกนพาสปอร์ต
        if (window.PassportScanner) {
            setDeviceStatus('passport', 'idle', 'Passport Scanner: Connecting...');
            PassportScanner.init({
                onDataReceived: (data) => {
                    putPassportToScreen(data);
                },
                onStatusChange: (isOnline) => {
                    setDeviceStatus('passport', isOnline ? 'online' : 'offline', isOnline ? 'Passport Scanner: Ready' : 'Passport Scanner: Offline');
                },
                onLog: (log, type) => {
                    // ถ้ามีฟังก์ชัน view.addLogEntry ให้เรียกใช้
                    if (typeof view !== "undefined" && view.addLogEntry) {
                        view.addLogEntry(log, type);
                    }
                },
                onLoading: (isVisible) => {
                    // ถ้ามีฟังก์ชัน view.displayLoadingMask ให้เรียกใช้
                    if (typeof view !== "undefined" && view.displayLoadingMask) {
                        view.displayLoadingMask(isVisible);
                    }
                }
            });
        }
    }

    function connectTDKW() {
        wSocket = new WebSocket("ws://127.0.0.1:14820/TDKWAgent");
        wSocket.onopen = function () {
            // Socket connected does not guarantee the USB reader is present.
            setDeviceStatus('agent', 'idle', 'Card Reader: Agent Connected (Checking Hardware...)');
            wSocket.send(JSON.stringify({
                Command: "SetAutoReadOptions",
                AutoRead: true, IDNumberRead: true, IDTextRead: true, IDPhotoRead: true, IDATextRead: true
            }));
        };
        wSocket.onmessage = (evt) => onGetMessage(evt.data);
        wSocket.onclose = () => {
            setDeviceStatus('agent', 'offline', 'Card Reader: Offline');
            setTimeout(connectTDKW, 5000);
        };
    }

    function onGetMessage(jsonString) {
        const msgObj = JSON.parse(jsonString);

        // --- ส่วนเดิม: จัดการสถานะการเสียบ/ถอดบัตร ---
        if (msgObj.Message === "CardStatusChangeE") {
            if (msgObj.Status === 1) { // เสียบบัตรแล้ว
                setDeviceStatus('agent', 'online', 'Card Reader: Card Detected');
                clearScreen();
                $('#LEDCardStatus').css({ 'background-color': '#c5a059', 'box-shadow': '0 0 10px #c5a059' });
                $('#lbCardStatus').text("CARD STATUS: PRESENT");
                $('#lbInstruction').text("Reading data, please do not remove your card.");
                $('#scanStatus').html('<span class="spinner-border spinner-border-sm me-2"></span> Reading Data...');
            }
            else if (msgObj.Status === -16) { // ดึงบัตรออก
                setDeviceStatus('agent', 'online', 'Card Reader: Ready (No Card)');
                clearScreen();
                $('#LEDCardStatus').css({ 'background-color': 'gray', 'box-shadow': 'none' });
                $('#lbCardStatus').text("CARD STATUS: ABSENT");
                $('#lbInstruction').text("Please insert your ID card or Passport.");
                $('#scanStatus').html('<span class="spinner-border spinner-border-sm me-2"></span> Awaiting Scan...');
                $('#nextToPrintBtn').prop('disabled', true).css('background-color', '#3e3e3e');
            }
            else {
                setDeviceStatus('agent', 'offline', 'Card Reader: Offline');
            }
        }

        // --- ส่วนเดิม: จัดการ Progress การอ่านข้อมูล ---
        if (msgObj.Message === "ReadingProgressE" && msgObj.Status === 0) {
            $('#progress_bar').css('width', msgObj.Progress + "%");
            if (msgObj.Progress > 0 && msgObj.Progress < 100) {
                $('#lbInstruction').text("Processing... " + msgObj.Progress + "%");
            }
        }

        // --- เมื่ออ่านข้อมูลเสร็จสมบูรณ์ ---
        if (msgObj.Message === "AutoReadIDCardE" || msgObj.Message === "ReadIDCardR") {
            if (msgObj.Status == 1 || msgObj.Status == 0) {
                putIDCardToScreen(msgObj.IDNumber, msgObj.IDText, msgObj.IDPhoto, msgObj.IDSText, msgObj.Status);
                $('#lbCardStatus').text("CARD STATUS: VERIFIED");
                $('#lbInstruction').text("Identity verified successfully.");
                $('#LEDCardStatus').css({
                    'background-color': '#50fa7b',
                    'box-shadow': '0 0 15px #50fa7b'
                });
                $('#scanStatus').addClass('complete')
                    .html('<i class="bi bi-check-circle-fill me-2"></i> VERIFIED');
                $('#scanPrompt').addClass('d-none');
                $('#cardPreview').removeClass('d-none').addClass('animate-fade-in');

            } else if (msgObj.Status == -1004) {
                setDeviceStatus('agent', 'error', 'Card Reader: Hardware Error');
                $('#lbCardStatus').text("READER ERROR");
                $('#lbInstruction').text("Hardware error. Please re-plug the reader.");
                $('#scanStatus').html('<span class="text-danger"><i class="bi bi-exclamation-octagon"></i> Hardware Error (-1004)</span>');
            }
        }
    }

function putIDCardToScreen(idNumber, idText, idPhoto, idSText, status) {
    const data = idText.split('#');
    const photoSrc = "data:image/jpeg;base64," + idPhoto;

    // เตรียมข้อมูลชื่อและที่อยู่ (Logic เดิมของคุณ)
    const firstNameTH = data[2] ? data[2].trim() : "";
    const lastNameTH = data[4] ? data[4].trim() : "";
    const thaiName = (firstNameTH + " " + lastNameTH).replace(/\s+/g, ' ').trim();
    const prefixEN = data[5] ? data[5].trim() : "";
    const firstNameEN = data[6] ? data[6].trim() : "";
    const lastNameEN = data[8] ? data[8].trim() : "";
    const englishName = (prefixEN + firstNameEN + " " + lastNameEN).replace(/\s+/g, ' ').trim();
    const fullAddress = `${data[9]||""} ${data[10]||""} ${data[14]||""} ${data[15]||""} ${data[16]||""}`.replace(/\s+/g, ' ').trim();
    const rawDob = data[18] || "";
    const dob = rawDob ? `${rawDob.substring(6, 8)}/${rawDob.substring(4, 6)}/${rawDob.substring(0, 4)}` : "-";
    const rawExpire = data[20] || "";
    const expireDate = rawExpire ? `${rawExpire.substring(6, 8)}/${rawExpire.substring(4, 6)}/${rawExpire.substring(0, 4)}` : "-";

    // --- แสดงผลฝั่ง ID Card ---
    $('#previewPassport').addClass('d-none');
    $('#previewIDCard').removeClass('d-none').hide().fadeIn().addClass('d-flex');
    $('#docTypeBadge').removeClass('bg-info').addClass('bg-primary').text('ID CARD');

    if (idPhoto) $('#img_IDPhoto_Standard').attr('src', photoSrc);
    $('#txt_ThaiName_Standard').html(thaiName);
    $('#txt_ENName_Standard').html(englishName);
    $('#txt_IDNumber_Standard').html("ID: " + idNumber);
    $('#txt_Address_Standard').html(fullAddress);
    $('#txt_DOB_Standard').html("Birth Date: " + dob);
    $('#txt_Expire_Standard').html("Expiry: " + expireDate);

    // ปรับสีสถานะเป็นเขียว (ID Card)
    $('#scanArea').css('background', 'linear-gradient(135deg, #155724 0%, #28a745 100%)');
    $('#scanStatus').html('<i class="bi bi-check-circle-fill text-success"></i> VERIFIED').addClass('complete text-success fw-bold');
    $('#scanPrompt').hide();

    scardData = {
        ID_Number: idNumber,
        ThaiName: thaiName,
        ENName: englishName,
        Photo: idPhoto,
        Address: fullAddress,
        DOB: dob,
        ExpireDate: expireDate,
        identity_type_id: "5417800000000000003"
    };

    if (typeof checkInputs === "function") checkInputs();
}

function putPassportToScreen(data) {
    const photoSrc = data.Photo.startsWith('data') ? data.Photo : "data:image/jpeg;base64," + data.Photo;

    // --- Use the same simple card layout as ID Card ---
    $('#previewPassport').addClass('d-none');
    $('#previewIDCard').removeClass('d-none').hide().fadeIn().addClass('d-flex');
    $('#docTypeBadge').removeClass('bg-primary').addClass('bg-info').text('PASSPORT');

    $('#img_IDPhoto_Standard').attr('src', photoSrc);
    $('#txt_ThaiName_Standard').html(data.ENName || "-");
    $('#txt_ENName_Standard').html((data.Raw?.Givenname || "") + " " + (data.Raw?.Familyname || ""));
    $('#txt_IDNumber_Standard').html("Passport No: " + (data.ID_Number || "-"));
    $('#txt_DOB_Standard').html("Birth Date: " + (data.DOB || "-"));
    $('#txt_Expire_Standard').html("Expiry: " + (data.ExpireDate || "-"));
    $('#txt_Address_Standard').html(data.Address || "-");

    // ปรับสีสถานะเป็นน้ำเงิน (Passport)
    $('#scanArea').css('background', 'linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%)');
    $('#scanStatusText').text('PASSPORT VERIFIED');
    $('#scanStatus').addClass('complete text-success fw-bold');
    $('#scanStatusSpinner').hide();
    $('#scanPrompt').hide();

    scardData = {
        ID_Number: data.ID_Number,
        prefix: "",
        fname: data.Raw ? data.Raw.Givenname : (data.ENName.split(' ')[0] || ""),
        lname: data.Raw ? data.Raw.Familyname : (data.ENName.split(' ')[1] || ""),
        birth_date: data.DOB,
        expire_date: data.ExpireDate,
        Address: data.Address,
        type: 'passport',
        identity_type_id: "5417800000000000001",
        Photo: data.Photo
    };

    if (typeof checkInputs === "function") checkInputs();
}

    function clearScreen() {
        $('#img_IDPhoto').attr('src', '');
        $('#txt_ThaiName, #txt_IDNumber, #txt_Address').html('');
        $('#cardPreview').addClass('d-none');
        $('#scanPrompt').removeClass('d-none').show();
        $('#progress_bar').css('width', '0%');
        $('#scanArea').css('background', '');
        $('#scanStatus').removeClass('complete text-success fw-bold').empty()
            .append($('<span>', { id: 'scanStatusSpinner', class: 'spinner-border spinner-border-sm me-2' }))
            .append($('<span>', { id: 'scanStatusText', text: 'Awaiting Identity Verification...' }));
        $('#nextToPrintBtn').prop('disabled', true);
        $('#wizardCombinedNextBtn').prop('disabled', true);
        guestPhotoData = null;
        stopGuestFaceGuide();
        stopGuestCamera();
        $('#guestPhotoPreview').addClass('d-none').attr('src', '');
        $('#guestCameraPreview').removeClass('d-none');
        $('#retakeGuestPhotoBtn').addClass('d-none');
        updateGuestPhotoStatus('pending', 'Waiting for capture');
    }

    // ==========================================
    // 3. NAVIGATION & SEARCH
    // ==========================================
    window.showStep = function (num) {
        const leavingPhoto = (lastCheckinWizardStep === 3 && num !== 3 && num !== 'Print' && num !== 'Keycard');
        if (leavingPhoto) {
            stopGuestFaceGuide();
            stopGuestCamera();
        }

        $('.step-card').hide().removeClass('active');
        let target;
        if (num === 'Print') target = '#stepPrint';
        else if (num === 'Keycard') target = '#stepKeycard';
        else if (num === 1 || num === 'Home') target = '#step1';
        else if (num === 2) target = '#step2';
        else if (num === 3) target = '#step3';
        else target = '#step1';

        $(target).fadeIn(400).addClass('active');

        if (num === 1 || num === 'Home') {
            $('#checkinWizardNav').addClass('d-none');
            lastCheckinWizardStep = null;
            $('#reservationInput').val('');
            currentReservation = null;
            scardData = null;
            clearScreen();
        } else if (num === 'Print' || num === 'Keycard') {
            $('#checkinWizardNav').addClass('d-none');
            lastCheckinWizardStep = null;
        } else if (num === 2 || num === 3) {
            syncWizardNav(num);
            lastCheckinWizardStep = num;
        }

        if (num === 2) {
            checkInputs();
        }

        if (num === 3) {
            stopGuestFaceGuide();
            if (guestPhotoData) {
                $('#guestPhotoPreview').attr('src', guestPhotoData).removeClass('d-none');
                $('#guestCameraPreview').addClass('d-none');
                $('#cameraPlaceholder').addClass('d-none');
                $('#retakeGuestPhotoBtn').removeClass('d-none');
                updateGuestPhotoStatus('ready', 'Photo captured');
            } else {
                $('#guestPhotoPreview').addClass('d-none').attr('src', '');
                $('#retakeGuestPhotoBtn').addClass('d-none');
                startGuestCamera().then(function () {
                    if ($('#step3').hasClass('active') && !guestPhotoData) {
                        scheduleGuestFaceGuide();
                    }
                });
            }
            checkInputs();
        }
    };

    $('#searchBtn').on('click', function () {
        const term = $('#reservationInput').val().trim();
        if (!term) return;
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> SEARCHING...');
        $.ajax({
            url: KIOSK_CONFIG.searchUrl,
            method: 'POST',
            data: { search: term },
            headers: { 'X-CSRF-TOKEN': KIOSK_CONFIG.csrfToken },
            success: function (res) {
                if (res.status === 'success') {
                    currentReservation = res.data;
                    renderDetails(res.data);
                    showStep(2);
                } else {
                    showPopup('warning', res.message || "Booking not found.");
                }
            },
            complete: () => $('#searchBtn').prop('disabled', false).text('SEARCH')
        });
    });

    function renderDetails(data) {
        $('#input_email').val(data.email || "");
        $('#input_phone').val(data.phone || "");
        $('#input_mobile').val(data.mobile || "");
        $('#reservationDetails').html(`
            <div class="reservation-summary text-center mb-3 border-bottom pb-2">
                <span class="summary-label">GUEST NAME</span>
                <h2 class="summary-guest-name">${data.guest_name}</h2>
                <span class="badge bg-primary mt-1 reservation-room-type">${data.room_type}</span>
            </div>
            <div class="row g-2 px-2 reservation-grid">
                <div class="col-6">
                    <span class="summary-meta-label">Check-in</span><br>
                    <b class="summary-meta-value">${data.arrival_date}</b>
                </div>
                <div class="col-6 text-end">
                    <span class="summary-meta-label">Check-out</span><br>
                    <b class="summary-meta-value">${data.departure_date}</b>
                </div>
                <div class="col-6">
                    <span class="summary-meta-label">Room</span><br>
                    <b class="summary-meta-value text-primary">#${data.room_name || '-'}</b>
                </div>
                <div class="col-6 text-end">
                    <span class="summary-meta-label">Booking ID</span><br>
                    <b class="summary-meta-value">${data.booking_id}</b>
                </div>
            </div>
        `);
        if (typeof checkInputs === "function") {
            checkInputs();
        }

        // printViaVBNet(data)
    }

    function checkInputs() {
        const email = $('#input_email').val().trim();
        const phone = $('#input_phone').val().trim();
        const mobile = $('#input_mobile').val().trim();
        const contactOk = !!(email && phone && mobile);
        const canGoPhoto = contactOk && !!scardData;
        $('#wizardCombinedNextBtn').prop('disabled', !canGoPhoto);
        const canCheckIn = canGoPhoto && !!guestPhotoData;
        $('#nextToPrintBtn').prop('disabled', !canCheckIn);
    }

    $('.kiosk-keyboard-input').on('input change', checkInputs);
    $('#captureGuestPhotoBtn').on('click', captureGuestPhoto);
    $('#retakeGuestPhotoBtn').on('click', resetGuestPhoto);

    // ==========================================
    // 4. CHECK-IN & PRINT
    // ==========================================
    $('#nextToPrintBtn').on('click', function () {
        if (!currentReservation) return;
        if (!scardData || !guestPhotoData) {
            showPopup('warning', 'Please verify your ID on the previous step and capture your guest photo.');
            return;
        }
        const email = $('#input_email').val().trim();
        const phone = $('#input_phone').val().trim();
        const mobile = $('#input_mobile').val().trim();
        if (!email || !phone || !mobile) {
            showPopup('error', "PLEASE COMPLETE CONTACT INFORMATION (EMAIL, PHONE, MOBILE) ON THE GUEST STEP.");
            return;
        }
        const emailReg = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailReg.test(email)) {
            showPopup('error', "INVALID EMAIL FORMAT. PLEASE CHECK AGAIN.");
            return;
        }
        // PassPort = "5417800000000000001";
        // IdCard = "5417800000000000003";

        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> PROCESSING...');
        const identity_image = (scardData && scardData.Photo) ? scardData.Photo : "";
        // console.log(identity_image)
        const payload = {
            booking_id: currentReservation.booking_id,
            guest_name: currentReservation.guest_name,
            identity_no: scardData ? (scardData.ID_Number || scardData.id_card || "") : (currentReservation.identity_no || ""),
            address: scardData ? (scardData.Address || scardData.nation || "") : (currentReservation.address || ""),
            birth_date: scardData ? scardData.DOB : (currentReservation.birth_date || ""),
            identity_expiry_date: scardData ? scardData.ExpireDate : "",
            email: email,
            phone: phone,
            mobile: mobile,
            identity_type_id: scardData ? scardData.identity_type_id : "5417800000000000003",
            identity_image: identity_image,
            guest_image: guestPhotoData || "",
            guest_signature: guestPhotoData || "",
            room_code: currentReservation.room_name
        };
        $.ajax({
            url: KIOSK_CONFIG.checkinUrl,
            method: 'POST',
            data: payload,
            headers: { 'X-CSRF-TOKEN': KIOSK_CONFIG.csrfToken },
            success: function (res) {
                const response = (typeof res === 'string') ? JSON.parse(res) : res;
                if (response.status === 'success') {
                    showStep('Print');
                    startPrintProgress(response.data);
                    triggerCardDispenserAfterCheckin();
                } else {
                    showPopup('error', response.message || "CHECK-IN ERROR");
                    $btn.prop('disabled', false).html('<span>COMPLETE CHECK-IN</span><i class="bi bi-house-check ms-2"></i>');
                }
            },
            error: function (xhr) {
                // กรณี Server ส่ง HTTP Status 400, 422 หรือ 500
                let errorMsg = "Network error.";

                try {
                    // พยายามอ่าน JSON จาก responseText ที่ส่งกลับมาพร้อม error
                    const errRes = JSON.parse(xhr.responseText);
                    errorMsg = errRes.message || "CHECK-IN ERROR";
                } catch (e) {
                    console.error("Could not parse error response", e);
                }

                showPopup('error', errorMsg);
                $btn.prop('disabled', false).html('<span>COMPLETE CHECK-IN</span><i class="bi bi-house-check ms-2"></i>');
            }
        });
    });

    function startPrintProgress(data) {
        let width = 0;
        const now = new Date();
        const printTime = now.toLocaleDateString('th-TH') + ' ' + now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
        const wifiUnavailable = data.wifi_status === 'unavailable';


        $('#view-voucher').text(currentReservation.voucher_no || "-");
        $('#view-booking').text(currentReservation.booking_id || '-');
        $('#view-guest').text(currentReservation.guest_name || '-');

        // ข้อมูลวันที่ (ถ้าไม่มีใน data ให้ดึงจาก currentReservation)
        $('#view-arrival').text(currentReservation.arrival_date || '-');
        $('#view-departure').text(currentReservation.departure_date || '-');

        $('#view-room-type').text(currentReservation.room_type || '-');
        $('#view-room').text(currentReservation.room_name || '-');

        // ข้อมูล WiFi
        $('#view-user').text(wifiUnavailable ? 'Please contact front desk' : (data.wifi_user || '-'));
        $('#view-pass').text(wifiUnavailable ? 'For WiFi assistance' : (data.wifi_pass || '-'));

        // เวลาที่ออกตั๋ว
        $('#view-time').text(printTime);

        // --- 2. ตั้งค่าสถานะเริ่มต้นของ UI ---
        $('#printProgressWrapper').show();
        $('#nextToKeycardArea').hide();
        $('#printProgress').css('width', '0%');

        // --- 3. สั่งพิมพ์ไปยัง VB.NET ทันที ---
        printViaVBNet(data);

        // --- 4. รัน Progress Bar จำลองสถานะการพิมพ์ ---
        const interval = setInterval(function () {
            width += 5;
            $('#printProgress').css('width', width + '%');

            if (width >= 100) {
                clearInterval(interval);


                $('#printProgressWrapper').fadeOut(300, function () {
                    $('#nextToKeycardArea').fadeIn();
                });
            }
        }, 100);
    }

    // ==========================================
    // 5. PRINT MANAGER (VB.NET HTTP)
    // ==========================================
    async function printViaVBNet(data) {
        try {
            const totalWidth = 32;
            const wifiUnavailable = data.wifi_status === 'unavailable';

            const alignRight = (label, value) => {
                const spaceCount = totalWidth - label.length - String(value).length;
                return label + ' '.repeat(Math.max(0, spaceCount)) + value;
            };

            const centerText = (text) => {
                const space = Math.max(0, Math.floor((totalWidth - text.length) / 2));
                return ' '.repeat(space) + text;
            };

            const now = new Date();
            const printTime = now.toLocaleDateString('th-TH') + ' ' + now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });

            // ดึงข้อมูลจาก Reservation Object (ปรับชื่อตัวแปรให้ตรงกับระบบของคุณ)
            const voucher_no = currentReservation ? currentReservation.voucher_no : "-";
            const resNo = currentReservation ? currentReservation.booking_id : "-";
            const arrivalDate = currentReservation ? currentReservation.arrival_date : "-";
            const departureDate = currentReservation ? currentReservation.departure_date : "-";
            const roomType = currentReservation ? currentReservation.room_type : "-";
            const roomNo = currentReservation ? currentReservation.room_name : "-";
            const guestName = currentReservation ? currentReservation.guest_name : "-";

            let printText = "";

            // ==========================================
            // 1. HEADER
            // ==========================================
            printText += centerText("ZENSATION THE RESIDENCE") + "\n";
            printText += "=============================\n";

            // ==========================================
            // 2. ข้อมูลการจอง (RESERVATION INFO)
            // ==========================================
            printText += alignRight("Voucher: ", voucher_no + " ") + "\n";
            printText += alignRight("Booking ID: ", resNo + " ") + "\n";
            printText += alignRight("Guest: ", guestName + " ") + "\n";
            printText += "--------------------------------\n";
            printText += alignRight("Arrival Date:  ", arrivalDate + " ") + "\n";
            printText += alignRight("Departure Date: ", departureDate + " ") + "\n";
            printText += "--------------------------------\n";
            printText += alignRight("Room Type:  ", roomType + " ") + "\n";
            printText += alignRight("Room Number: ", roomNo + " ") + "\n";
            printText += "=============================\n\n";

            // ==========================================
            // 3. WIFI CREDENTIALS (รหัสผ่าน)
            // ==========================================
            printText += centerText("● WIFI ACCESS ●") + "\n";
            if (wifiUnavailable) {
                printText += centerText("Please contact front desk") + "\n";
                printText += centerText("for WiFi credentials") + "\n";
            } else {
                printText += alignRight('  USERNAME:', (data.wifi_user || '-') + '  ') + '\n';
                printText += alignRight('  PASSWORD:', (data.wifi_pass || '-') + '  ') + '\n';
            }
            printText += "\n";
            printText += "=============================\n";

            // ==========================================
            // 4. CONTACT & FOOTER
            // ==========================================
            printText += centerText("CONTACT US") + "\n";
            printText += centerText("(+66) 2-286-1216") + "\n";
            printText += centerText("(+66) 8-8112-2001") + "\n";
            printText += "--------------------------------\n";

            printText += centerText("ISSUED BY KIOSK") + "\n";
            printText += centerText(printTime) + "\n";

            // ระยะตัดกระดาษ
            printText += "\n\n\n\n\n";

            await fetch(PRINT_SERVER_URL, {
                method: 'POST',
                mode: 'no-cors',
                body: printText
            });

        } catch (e) {
            console.error("Print Error:", e);
        }
    }

    $(document).on('click', '#goToKeycardBtn', function () {
        showStep('Keycard');
        setTimeout(() => { $('#dispenseStatus').fadeOut(400, () => $('#finishArea').fadeIn()); }, 4000);
    });

    $(document).on('click', '#backToSearchBtn', function () { showStep(1); });
    $(document).on('click', '#wizardCombinedNextBtn', function () {
        if (!$(this).prop('disabled')) showStep(3);
    });
    $(document).on('click', '#wizardPhotoBackBtn', function () { showStep(2); });

    $('.finish-btn, #finishBtn').on('click', () => window.location.href = KIOSK_CONFIG.homeUrl);

    startConnections();
});
