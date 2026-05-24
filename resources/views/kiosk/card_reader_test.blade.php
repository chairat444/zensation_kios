@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/checkin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/checkin-page.css') }}">
@endpush

@section('content')
    <div class="checkin-page-wrapper">
        <div class="swiper swiper-bg-fixed">
            <div class="swiper-wrapper">
                <div class="swiper-slide">
                    <div class="bg-img checkin-bg-1"></div>
                </div>
            </div>
            <div class="bg-overlay"></div>
        </div>

        <div class="checkin-content animate-fade-down px-3 pb-5" style="max-width: 720px; margin: 0 auto;">
        <div class="text-center mb-4 checkin-top-shell py-3">
            <h1 class="welcome-title checkin-page-title mb-2">KEY CARD READER TEST</h1>
            <p class="text-white-50 small mb-0">อ่าน / เขียนบัตรผ่าน PMSif (00000E / 00000I / 00000B)</p>
        </div>

        <div class="card shadow-lg border-0 rounded-4 overflow-hidden mb-4">
            <div class="card-body p-4 text-start">
                <h2 class="h5 fw-bold mb-3">สถานะจาก config / .env</h2>
                <ul class="small mb-3">
                    <li><code>KIOSK_CARD_READER_URL</code> = <code class="user-select-all">{{ $readerUrl }}</code></li>
                    <li><code>KIOSK_CARD_WRITER_URL</code> = <code class="user-select-all">{{ $writerUrl }}</code></li>
                    <li><code>PMSIF_HOST</code> = <strong>{{ $encoderHost }}</strong></li>
                    <li><code>PMSIF_PORT</code> = <strong>{{ $encoderPort }}</strong></li>
                    <li><code>PMSIF_READ_CMD</code> = <code>{{ $readCommand }}</code></li>
                </ul>

                <hr class="my-4">

                <h2 class="h5 fw-bold mb-2">ทดสอบอ่านบัตร</h2>
                <p class="small text-muted mb-3">วางบัตรบน encoder แล้วกดปุ่ม — รัน <code>ILockInterfaceOffline.exe</code> และ <code>python scripts/pmsif_card_reader_server.py</code></p>

                <label class="form-label small fw-bold">Bridge URL (POST)</label>
                <input type="url" id="readerBridgeUrl" class="form-control form-control-lg mb-3"
                    value="{{ $readerUrl }}" autocomplete="off">

                <div class="d-grid gap-2">
                    <button type="button" id="readerTestBtn" class="btn btn-warning btn-lg fw-bold py-3">อ่านบัตรจากเครื่อง</button>
                    <a href="{{ route('kiosk.home') }}" class="btn btn-outline-secondary">กลับหน้าหลัก</a>
                </div>

                <h2 class="h5 fw-bold mt-4 mb-2">ผลลัพธ์</h2>
                <pre id="readerTestLog" class="bg-dark text-success small p-3 rounded-3 mb-3" style="min-height: 140px; white-space: pre-wrap;">วางบัตรแล้วกดปุ่ม…</pre>

                <div id="readerParsedCard" class="d-none">
                    <h3 class="h6 fw-bold mb-2">ข้อมูลที่แยกจากสตริงบัตร</h3>
                    <table class="table table-sm table-bordered bg-white mb-0">
                        <tbody>
                            <tr><th class="w-25">ห้อง (R)</th><td id="parsedRoom">—</td></tr>
                            <tr><th>ประเภท (T)</th><td id="parsedGuestType">—</td></tr>
                            <tr><th>ชื่อ (N)</th><td id="parsedGuestName">—</td></tr>
                            <tr><th>Check-in (D)</th><td id="parsedCheckIn">—</td></tr>
                            <tr><th>Check-out (O)</th><td id="parsedCheckOut">—</td></tr>
                            <tr><th>เครื่อง (M)</th><td id="parsedMachineId">—</td></tr>
                            <tr><th>Common door (C)</th><td id="parsedCommonDoors">—</td></tr>
                            <tr><th>ลิฟต์ (L)</th><td id="parsedElevators">—</td></tr>
                        </tbody>
                    </table>

                    <div id="writePreviewBox" class="mt-4 d-none">
                        <h3 class="h6 fw-bold mb-2">รูปแบบเขียน check-in (ประมาณจากข้อมูลที่อ่านได้)</h3>
                        <p class="small text-muted mb-2">คำสั่งที่ bridge จะส่งเมื่อกดเขียน check-in</p>
                        <pre id="writePreviewCmd" class="bg-dark text-warning small p-3 rounded-3 mb-2 user-select-all" style="white-space: pre-wrap;"></pre>
                        <p class="small text-muted mb-0" id="writePreviewNote"></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-lg border-0 rounded-4 overflow-hidden mb-4">
            <div class="card-body p-4 text-start">
                <h2 class="h5 fw-bold mb-2">เขียนบัตร</h2>
                <p class="small text-muted mb-3">วางบัตรบน encoder แล้วกดเขียน — ฟอร์มมีข้อมูลจำลองจากบัตรตัวอย่าง (ห้อง 203) หลังอ่านบัตรจะทับด้วยค่าจริง</p>

                <label class="form-label small fw-bold">Write URL (POST)</label>
                <input type="url" id="writerBridgeUrl" class="form-control form-control-lg mb-3"
                    value="{{ $writerUrl }}" autocomplete="off">

                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label small fw-bold">ห้อง (R)</label>
                        <input type="text" id="writeRoom" class="form-control" value="203">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">ประเภท (T)</label>
                        <input type="text" id="writeGuestType" class="form-control" value="04">
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Check-in (D) YmdHi</label>
                        <input type="text" id="writeCheckIn" class="form-control" value="202506140733">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Check-out (O) YmdHi</label>
                        <input type="text" id="writeCheckOut" class="form-control" value="202506211300">
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Common door (C) ไม่บังคับ</label>
                        <input type="text" id="writeCommonDoors" class="form-control" placeholder="">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">ลิฟต์ (L) ไม่บังคับ</label>
                        <input type="text" id="writeElevators" class="form-control" placeholder="">
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="button" id="writerCheckinBtn" class="btn btn-success btn-lg fw-bold py-3">เขียนบัตร check-in (00000I)</button>
                    <button type="button" id="writerCheckoutBtn" class="btn btn-outline-danger fw-bold py-2">เขียนบัตร checkout (00000B)</button>
                    <button type="button" id="writerVerifyBtn" class="btn btn-outline-primary fw-bold py-2">เขียนแล้วอ่านกลับ</button>
                </div>
            </div>
        </div>

        <div class="card border-0 rounded-4 bg-white bg-opacity-10 text-white">
            <div class="card-body p-4 text-start">
                <h2 class="h6 fw-bold text-uppercase mb-3">ตั้งค่า Python bridge</h2>
                <pre class="bg-dark text-light small p-3 rounded-3 mb-3 user-select-all">pip install flask
set PMSIF_HOST=192.168.1.84
set PMSIF_PORT=8000
set PMSIF_READ_CMD=00000E
set PMSIF_READ_WAIT=10
set PMSIF_HTTP_PORT=58002
python scripts\pmsif_card_reader_server.py</pre>
                <p class="small mb-0 text-white-50">
                    ถ้าได้ <code>no_response</code>: วางบัตรบน encoder ก่อนกดอ่าน · ตรวจสาย USB/COM · ทดสอบด้วย
                    <code>PMSif_TCP_Demo.exe</code> ที่ IP <code>192.168.1.84</code> พอร์ต <code>8000</code> ปุ่ม Read card
                </p>
            </div>
        </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const logEl = document.getElementById('readerTestLog');
            const btn = document.getElementById('readerTestBtn');
            const urlInput = document.getElementById('readerBridgeUrl');
            const writerUrlInput = document.getElementById('writerBridgeUrl');
            const writerCheckinBtn = document.getElementById('writerCheckinBtn');
            const writerCheckoutBtn = document.getElementById('writerCheckoutBtn');
            const writerVerifyBtn = document.getElementById('writerVerifyBtn');
            const parsedBox = document.getElementById('readerParsedCard');
            const writePreviewBox = document.getElementById('writePreviewBox');

            function safeHide(el) { if (el) el.classList.add('d-none'); }
            function safeShow(el) { if (el) el.classList.remove('d-none'); }

            function log(msg) {
                logEl.textContent = typeof msg === 'string' ? msg : JSON.stringify(msg, null, 2);
            }

            function formatYmdHi(ymdHi) {
                if (!ymdHi || ymdHi.length < 12) return ymdHi || '—';
                return ymdHi.slice(6, 8) + '/' + ymdHi.slice(4, 6) + '/' + ymdHi.slice(0, 4)
                    + ' ' + ymdHi.slice(8, 10) + ':' + ymdHi.slice(10, 12);
            }

            function showParsed(parsed, resBody) {
                if (!parsed || (!parsed.room && !parsed.is_empty && !parsed.is_checkout && !parsed.machine_id)) {
                    parsedBox.classList.add('d-none');
                    safeHide(writePreviewBox);
                    return;
                }
                parsedBox.classList.remove('d-none');
                document.getElementById('parsedRoom').textContent = parsed.is_empty ? '(การ์ดเปล่า / Nempty)'
                    : (parsed.is_checkout ? '(เช็คเอาท์)' : (parsed.room || '—'));
                document.getElementById('parsedGuestType').textContent = parsed.guest_type || '—';
                document.getElementById('parsedGuestName').textContent = parsed.guest_name || '—';
                document.getElementById('parsedCheckIn').textContent = formatYmdHi(parsed.check_in);
                document.getElementById('parsedCheckOut').textContent = formatYmdHi(parsed.check_out);
                document.getElementById('parsedMachineId').textContent = parsed.machine_id || '—';
                document.getElementById('parsedCommonDoors').textContent = parsed.common_doors || '—';
                document.getElementById('parsedElevators').textContent = parsed.elevators || '—';

                var writePreview = (resBody && resBody.write_preview) ? resBody.write_preview : null;
                if (writePreview && writePreviewBox) {
                    safeShow(writePreviewBox);
                    document.getElementById('writePreviewCmd').textContent = writePreview;
                    document.getElementById('writePreviewNote').textContent =
                        (resBody.write_fields_note || '') +
                        ' · M/VD/N จากการอ่านไม่ใส่ในคำสั่งเขียน';
                } else {
                    safeHide(writePreviewBox);
                }

                fillWriteForm(parsed);
            }

            function fillWriteForm(parsed) {
                if (!parsed || parsed.is_empty || parsed.is_checkout) return;
                if (parsed.room) document.getElementById('writeRoom').value = parsed.room;
                if (parsed.guest_type) document.getElementById('writeGuestType').value = parsed.guest_type;
                if (parsed.check_in) document.getElementById('writeCheckIn').value = parsed.check_in;
                if (parsed.check_out) document.getElementById('writeCheckOut').value = parsed.check_out;
                if (parsed.common_doors) document.getElementById('writeCommonDoors').value = parsed.common_doors;
                if (parsed.elevators) document.getElementById('writeElevators').value = parsed.elevators;
            }

            function fetchJson(url, options) {
                return fetch(url, options || { method: 'POST', mode: 'cors', cache: 'no-store' })
                    .then(function (r) {
                        return r.text().then(function (t) {
                            let j = null;
                            try { j = JSON.parse(t); } catch (e) { j = { raw: t, status: r.status }; }
                            return { body: j };
                        });
                    });
            }

            function readCard() {
                const url = (urlInput.value || '').trim();
                if (!url) { log('ใส่ Bridge URL ก่อน'); return Promise.resolve(); }
                btn.disabled = true;
                log('กำลังอ่านบัตร…');
                return fetchJson(url)
                    .then(function (res) {
                        log(res.body);
                        if (res.body && res.body.parsed) showParsed(res.body.parsed, res.body);
                        else { safeHide(parsedBox); safeHide(writePreviewBox); }
                    })
                    .catch(function (e) {
                        log('Error: ' + (e && e.message ? e.message : String(e)));
                        parsedBox.classList.add('d-none');
                        safeHide(writePreviewBox);
                    })
                    .finally(function () { btn.disabled = false; });
            }

            function writeCard(payload) {
                const url = (writerUrlInput.value || '').trim();
                if (!url) { log('ใส่ Write URL ก่อน'); return Promise.resolve(); }
                writerCheckinBtn.disabled = true;
                writerCheckoutBtn.disabled = true;
                writerVerifyBtn.disabled = true;
                log('กำลังเขียนบัตร… วางบัตรบน encoder');
                return fetchJson(url, {
                    method: 'POST',
                    mode: 'cors',
                    cache: 'no-store',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                })
                    .then(function (res) { log(res.body); return res.body; })
                    .catch(function (e) { log('Error: ' + (e && e.message ? e.message : String(e))); })
                    .finally(function () {
                        writerCheckinBtn.disabled = false;
                        writerCheckoutBtn.disabled = false;
                        writerVerifyBtn.disabled = false;
                    });
            }

            btn.addEventListener('click', readCard);

            writerCheckinBtn.addEventListener('click', function () {
                writeCard({
                    mode: 'checkin',
                    room: document.getElementById('writeRoom').value.trim(),
                    guest_type: document.getElementById('writeGuestType').value.trim(),
                    check_in: document.getElementById('writeCheckIn').value.trim(),
                    check_out: document.getElementById('writeCheckOut').value.trim(),
                    common_doors: document.getElementById('writeCommonDoors').value.trim() || null,
                    elevators: document.getElementById('writeElevators').value.trim() || null,
                });
            });

            writerCheckoutBtn.addEventListener('click', function () {
                if (!confirm('เขียนบัตร checkout (00000B)? วางบัตรบน encoder')) return;
                writeCard({ mode: 'checkout' });
            });

            writerVerifyBtn.addEventListener('click', function () {
                writeCard({
                    mode: 'checkin',
                    room: document.getElementById('writeRoom').value.trim(),
                    guest_type: document.getElementById('writeGuestType').value.trim(),
                    check_in: document.getElementById('writeCheckIn').value.trim(),
                    check_out: document.getElementById('writeCheckOut').value.trim(),
                    common_doors: document.getElementById('writeCommonDoors').value.trim() || null,
                    elevators: document.getElementById('writeElevators').value.trim() || null,
                }).then(function (body) {
                    if (body && body.ok) {
                        log('เขียนสำเร็จ — กำลังอ่านกลับ…');
                        return readCard();
                    }
                });
            });
        })();
    </script>
@endpush

