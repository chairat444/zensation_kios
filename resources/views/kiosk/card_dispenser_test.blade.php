@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/checkin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/checkin-page.css') }}">
@endpush

@section('content')
    {{-- ต้องมี wrapper เดียวกับหน้า check-in + checkin-content ที่มี position/z-index ไม่งั้น swiper fixed ทับเนื้อหา --}}
    <div class="checkin-page-wrapper">
        <div class="swiper swiper-bg-fixed">
            <div class="swiper-wrapper">
                <div class="swiper-slide">
                    <div class="bg-img checkin-bg-1"></div>
                </div>
            </div>
            <div class="bg-overlay"></div>
        </div>

        <div class="checkin-content animate-fade-down px-3 pb-5" style="max-width: 640px; margin: 0 auto;">
        <div class="text-center mb-4 checkin-top-shell py-3">
            <h1 class="welcome-title checkin-page-title mb-2">CARD DISPENSER TEST</h1>
            <p class="text-white-50 small mb-0">CRT-591-M001 · ทดสอบก่อนใช้กับหน้า check-in จริง</p>
        </div>

        <div class="card shadow-lg border-0 rounded-4 overflow-hidden mb-4">
            <div class="card-body p-4 text-start">
                <h2 class="h5 fw-bold mb-3">สถานะจาก .env ตอนนี้</h2>
                <ul class="small mb-3">
                    <li><code>KIOSK_CARD_DISPENSER_ENABLED</code> = <strong>{{ $dispenserEnabled ? 'true' : 'false' }}</strong>
                        (หน้า check-in จะยิงบริดจ์เมื่อเป็น true เท่านั้น)</li>
                    <li><code>KIOSK_CARD_DISPENSER_URL</code> = <code class="user-select-all">{{ $dispenserUrl }}</code></li>
                    <li><code>KIOSK_CARD_DISPENSER_ADDR</code> = <strong>{{ $dispenserAddr }}</strong> (DIP บนตัวเครื่อง 0x00–0x0F)</li>
                </ul>

                <hr class="my-4">

                <h2 class="h5 fw-bold mb-2">ทดสอบยิงบริดจ์</h2>
                <p class="small text-muted mb-3">แก้ URL ด้านล่างได้ชั่วคราว (ไม่ต้องแก้ .env) แล้วกดปุ่ม — ต้องรัน
                    <code>python scripts/crt591_dispenser_server.py</code> และตั้ง <code>CRT591_PORT</code> ให้ตรง COM จริง</p>

                <label class="form-label small fw-bold">Dispense URL (POST)</label>
                <input type="url" id="dispenserDispenseUrl" class="form-control form-control-lg mb-2"
                    value="{{ $dispenserUrl }}" autocomplete="off">
                <label class="form-label small fw-bold">Retract URL (POST)</label>
                <input type="url" id="dispenserRetractUrl" class="form-control form-control-lg mb-3"
                    value="{{ preg_replace('/\/dispense$/', '/retract', $dispenserUrl) }}" autocomplete="off">

                <div class="d-grid gap-2">
                    <button type="button" id="dispenserTestBtn" class="btn btn-warning btn-lg fw-bold py-3">
                        ทดสอบจ่ายบัตร 1 ครั้ง
                    </button>
                    <button type="button" id="dispenserRetractBtn" class="btn btn-info btn-lg fw-bold py-3">
                        ทดสอบรับบัตรกลับเข้า (Re-Insert)
                    </button>
                    <a href="{{ route('kiosk.home') }}" class="btn btn-outline-secondary">กลับหน้าหลัก</a>
                </div>

                <h2 class="h5 fw-bold mt-4 mb-2">ผลลัพธ์</h2>
                <pre id="dispenserTestLog" class="bg-dark text-success small p-3 rounded-3 mb-0" style="min-height: 120px; white-space: pre-wrap;">รอการทดสอบ…</pre>
            </div>
        </div>

        <div class="card border-0 rounded-4 bg-white bg-opacity-10 text-white">
            <div class="card-body p-4 text-start">
                <h2 class="h6 fw-bold text-uppercase mb-3">ตั้งค่า .env (Laravel)</h2>
                <p class="small mb-2">เพิ่มหรือแก้ในไฟล์ <code>.env</code> แล้วรัน <code>php artisan config:clear</code> (ถ้าใช้ cache config)</p>
                <pre class="bg-dark text-light small p-3 rounded-3 mb-3 user-select-all"># เปิดการเรียกบริดจ์หลัง check-in สำเร็จ (หน้า check-in)
KIOSK_CARD_DISPENSER_ENABLED=true
KIOSK_CARD_DISPENSER_URL=http://127.0.0.1:59101/dispense
KIOSK_CARD_DISPENSER_ADDR=0</pre>

                <h2 class="h6 fw-bold text-uppercase mb-2">ตั้งค่าเครื่องรันสคริปต์ Python (CMD / PowerShell)</h2>
                <pre class="bg-dark text-light small p-3 rounded-3 mb-0 user-select-all">pip install pyserial flask
set CRT591_PORT=COM3
set CRT591_BAUD=38400
set CRT591_ADDR=0
set CRT591_HTTP_PORT=59101
set CRT591_ALLOW_IN_WAIT=4.0
set CRT591_RETRACT_DELAY=0.5
python scripts\crt591_dispenser_server.py</pre>
                <p class="small mt-3 mb-0 text-white-50">ถ้าเบราว์เซอร์เปิดจาก <code>http://127.0.0.1</code> แต่บริดจ์อยู่ที่ <code>http://localhost</code> อาจถือว่าคนละ origin — ให้ใช้โฮสต์เดียวกันกับใน <code>KIOSK_CARD_DISPENSER_URL</code></p>
            </div>
        </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const logEl = document.getElementById('dispenserTestLog');
            const dispenseBtn = document.getElementById('dispenserTestBtn');
            const retractBtn = document.getElementById('dispenserRetractBtn');
            const dispenseUrlInput = document.getElementById('dispenserDispenseUrl');
            const retractUrlInput = document.getElementById('dispenserRetractUrl');

            function log(msg) {
                logEl.textContent = typeof msg === 'string' ? msg : JSON.stringify(msg, null, 2);
            }

            function callEndpoint(url, actionLabel) {
                if (!url) {
                    log('ใส่ URL ก่อน');
                    return;
                }

                dispenseBtn.disabled = true;
                retractBtn.disabled = true;
                log('กำลัง ' + actionLabel + ' ที่ ' + url + ' …');
                fetch(url, { method: 'POST', mode: 'cors', cache: 'no-store' })
                    .then(function (r) {
                        return r.text().then(function (t) {
                            let j = null;
                            try { j = JSON.parse(t); } catch (e) { j = { raw: t, status: r.status }; }
                            return { ok: r.ok, status: r.status, body: j };
                        });
                    })
                    .then(function (res) {
                        log(res);
                    })
                    .catch(function (e) {
                        log('Error: ' + (e && e.message ? e.message : String(e)));
                    })
                    .finally(function () {
                        dispenseBtn.disabled = false;
                        retractBtn.disabled = false;
                    });
            }

            dispenseBtn.addEventListener('click', function () {
                const url = (dispenseUrlInput.value || '').trim();
                callEndpoint(url, 'จ่ายบัตร');
            });

            retractBtn.addEventListener('click', function () {
                const url = (retractUrlInput.value || '').trim();
                callEndpoint(url, 'รับบัตรกลับเข้า');
            });
        })();
    </script>
@endpush
