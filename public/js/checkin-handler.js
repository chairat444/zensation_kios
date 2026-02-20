$(function() {
    let currentReservation = null;
    const PRINTER_NAME = "Thermal_Printer"; // *** เปลี่ยนชื่อให้ตรงกับใน Windows/Mac ของคุณ ***

    // --- 1. QZ Tray Connection (ปรับให้ปลอดภัยขึ้น) ---
    async function startQzConnection() {
        if (typeof qz !== 'undefined') {
            try {
                if (!qz.websocket.isActive()) {
                    await qz.websocket.connect();
                    console.log("QZ Tray: Connected successfully");
                }
            } catch (e) {
                console.warn("QZ Tray: Not running. Simulation mode active.");
            }
        } else {
            console.error("QZ Tray: Library (qz-tray.js) not found!");
        }
    }

    // เรียกใช้งานทันทีเมื่อโหลดหน้า
    startQzConnection();

    // --- 2. ฟังก์ชันสั่งพิมพ์และตัดกระดาษ (ปรับให้ไม่บล็อกงานถ้าไม่มีเครื่อง) ---
    async function printAndCut(data) {
        // ถ้าไม่ได้ต่อเครื่อง หรือไม่ได้เปิดโปรแกรม ให้ข้ามไปเลย ไม่ต้อง Alert ให้ค้าง
        if (typeof qz === 'undefined' || !qz.websocket.isActive()) {
            console.log("Simulation Mode: Printer not connected. Data:", data);
            return false;
        }

        try {
            const config = qz.configs.create(PRINTER_NAME);
            const printData = [
                '\x1B' + '\x40',          // Initialize
                '\x1B' + '\x61' + '\x01', // Align Center
                '\x1B' + '\x21' + '\x30', // Double Height + Width
                'ZENSATION N\n',
                '\x1B' + '\x21' + '\x00', // Font Normal
                '--------------------------------\n',
                '\x1B' + '\x61' + '\x00', // Align Left
                'ROOM: ' + (data.room_code || currentReservation.room_name) + '\n',
                'USER: ' + data.wifi_user + '\n',
                'PASS: ' + data.wifi_pass + '\n',
                '--------------------------------\n',
                '\x1B' + '\x61' + '\x01', // Align Center
                'Enjoy your stay!\n',
                '\n\n\n\n',               // Feed 4 lines
                '\x1B' + '\x69'           // *** PAPER CUT (คำสั่งตัดกระดาษ) ***
            ];
            await qz.print(config, printData);
            return true;
        } catch (e) {
            console.error("Print Job Error:", e);
            return false;
        }
    }

    // --- 3. ฟังก์ชันหลักในการสลับหน้า ---
    window.showStep = function(num) {
        $('.step-card').hide().removeClass('active');
        let target = (num === 'Print') ? '#step3' :
                     (num === 'Keycard') ? '#step4' :
                     (typeof num === 'number') ? `#step${num}` : `#step${num.toLowerCase()}`;

        $(target).fadeIn(400).addClass('active');

        if (num === 1 || num === 'Home') {
            $('#reservationInput').val('');
            $('#nextToPrintBtn').prop('disabled', true).text('CONTINUE');
            currentReservation = null;
        }
    };

    // --- 4. Search Logic ---
    $('#searchBtn').on('click', function() {
        const term = $('#reservationInput').val().trim();
        if (!term) return;

        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> SEARCHING...');

        $.ajax({
            url: KIOSK_CONFIG.searchUrl,
            method: 'POST',
            data: { search: term },
            headers: { 'X-CSRF-TOKEN': KIOSK_CONFIG.csrfToken },
            success: function(res) {
                if (res.status === 'success') {
                    currentReservation = res.data;
                    renderDetails(res.data);
                    showStep(2);
                } else {
                    const warnMsg = res.message || "We couldn't find your booking. Please try again or contact staff.";
                    const warnTitle = res.title || "ATTENTION";

                    showPopup('warning', `<strong>${warnTitle}</strong><br>${warnMsg}`);
                }
            },
            complete: () => $('#searchBtn').prop('disabled', false).text('SEARCH')
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
                <div class="col-6"><span class="label d-block small text-muted">Check-in</span><span class="value fw-bold">${data.arrival_date}</span></div>
                <div class="col-6 text-end"><span class="label d-block small text-muted">Check-out</span><span class="value fw-bold">${data.departure_date}</span></div>
                <div class="col-6"><span class="label d-block small text-muted">Room</span><span class="value text-primary fw-black">#${data.room_name || '-'}</span></div>
                <div class="col-6 text-end"><span class="label d-block small text-muted">Booking ID</span><span class="value">${data.booking_id}</span></div>
            </div>
        `);
        setTimeout(() => {
            $('#scanStatus').html('<i class="bi bi-check-circle-fill text-success me-2"></i> SCANNED').addClass('complete text-success fw-bold');
            $('#nextToPrintBtn').prop('disabled', false);
        }, 1000);
    }

    // --- 5. Check-in & Trigger Print ---
    $('#nextToPrintBtn').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> PROCESSING...');

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

        $.ajax({
            url: KIOSK_CONFIG.checkinUrl,
            method: 'POST',
            data: payload,
            headers: { 'X-CSRF-TOKEN': KIOSK_CONFIG.csrfToken },
            success: function(res) {
                // ตรวจสอบว่า res เป็น Object หรือ String (บางครั้ง Server พ่น String กลับมา)
                const response = (typeof res === 'string') ? JSON.parse(res) : res;

                if (response.status === 'success') {
                    // 2. สลับหน้าและเริ่ม Progress พิมพ์
                    showStep('Print');
                    startPrintProgress(response.data);
                } else {
                    // ดึงข้อความจาก response.message ที่ส่งมาจาก Server
                    // กรณีนี้คือ "Guest has already checked in..."
                    const errorMsg = response.message || "Something went wrong. Please contact staff.";
                    const errorTitle = response.title || "CHECK-IN ERROR";

                    // แสดง Popup โดยใช้ Title และ Message จาก JSON
                    showPopup('error', `<strong>${errorTitle}</strong><br>${errorMsg}`);

                    // คืนค่าปุ่มให้กดใหม่ได้
                    $btn.prop('disabled', false).html('<span>CONTINUE</span>');
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = "Network error or Server issue. Please try again.";

                try {
                    // แงะ JSON ที่ Server ส่งมาพร้อมกับ HTTP Error
                    const response = JSON.parse(xhr.responseText);

                    // ดึง message จาก JSON ที่คุณส่งมาให้ดูด้านบน
                    if (response && response.message) {
                        errorMessage = response.message;
                    }
                } catch (e) {
                    console.error("Could not parse error response", e);
                }

                // แสดง Popup ด้วยข้อความจริงจาก Server
                showPopup('error', errorMessage);

                // คืนค่าปุ่มให้กดใหม่ได้
                $btn.prop('disabled', false).html('<span>CONTINUE</span>');
            }
        });
    });

    function startPrintProgress(data) {
        let width = 0;

        // 1. แสดงข้อมูลบน Virtual Ticket ทันที (ตรวจสอบ ID ให้ตรงกับ HTML)
        $('#view-room').text(data.room_code || currentReservation.room_name);
        $('#view-user').text(data.wifi_user);
        $('#view-pass').text(data.wifi_pass);

        // 2. เคลียร์ UI Progress Bar
        $('#printProgress').css('width', '0%');
        $('#printProgressWrapper').show();
        $('#nextToKeycardArea').hide();

        const interval = setInterval(async function() {
            width += 10;
            $('#printProgress').css('width', width + '%');

            if (width >= 100) {
                clearInterval(interval);

                // 3. สั่งพิมพ์ (ถ้าไม่มีเครื่องจะข้ามไปเองในระดับ milliseconds)
                await printAndCut(data);

                // 4. แสดงปุ่มไปหน้าถัดไป
                $('#printProgressWrapper').fadeOut(200, function() {
                    $('#nextToKeycardArea').fadeIn(400);
                });
            }
        }, 150);
    }

    // --- 6. Next Step (Step 4 Keycard) ---
    $(document).on('click', '#goToKeycardBtn', function() {
        showStep('Keycard');
        // จำลองการจ่ายบัตร
        setTimeout(() => {
            $('#dispenseStatus').fadeOut(400, () => $('#finishArea').fadeIn());
        }, 4000);
    });

    // ฟังก์ชันสำหรับปุ่ม Back (กลับไปหน้า Search)
    $(document).on('click', '#backToSearchBtn', function(e) {
        e.preventDefault();
        showStep(1);
        $('#reservationInput').val('').focus();
    });

    $('.finish-btn, #finishBtn').on('click', () => window.location.href = KIOSK_CONFIG.homeUrl);
});