<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Zensation Kiosk') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="{{ mix('css/app.css') }}">

    @stack('styles')
</head>
<body>
    <main class="vh-app">
        <header class="kiosk-header">
            <div class="logo-wrap">
                <img src="{{ asset('images/zensationlogo_blk.jpg') }}" alt="Logo" class="logo-img">
                <div class="brand-sub">Self-service Kiosk</div>
            </div>
            <div class="clock-wrap">
                <div class="time-display">
                    <span class="time-main js-hours">00</span>
                    <span class="time-main">:</span>
                    <span class="time-main js-minutes">00</span>
                    <div class="time-side-wrap">
                        <span class="time-seconds js-seconds">00</span>
                        <div class="time-period js-period">AM</div>
                    </div>
                </div>
                <div class="clock-date-lg js-date"></div>
            </div>
        </header>

        <section class="kiosk-body">
            @yield('content')
        </section>

        <footer class="kiosk-footer-container">
            <div class="helper-wrap">
                <div class="footer-message-row">
                    <i class="bi bi-chat-dots-fill"></i>
                    <span>Please approach the front desk staff for assistance.</span>
                </div>
                <div class="footer-contact-row">
                    <div class="contact-block">
                        <i class="bi bi-telephone-fill contact-icon"></i>
                        <div class="info-details">
                            <span class="label">LANDLINE</span>
                            <span class="value">(+66) 2-286-1216</span>
                        </div>
                    </div>
                    <div class="contact-block">
                        <i class="bi bi-phone-fill contact-icon"></i>
                        <div class="info-details">
                            <span class="label">MOBILE</span>
                            <span class="value">(+66) 8-8112-2001</span>
                        </div>
                    </div>
                    <div class="contact-block">
                        <i class="bi bi-clock-fill contact-icon"></i>
                        <div class="info-details">
                            <span class="label">HOURS</span>
                            <span class="value">OPEN 24/7</span>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </main>

    {{-- Glass Alert Modal --}}
    <div class="modal fade" id="kioskAlertModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content glass-alert-content shadow-lg">
                <div class="modal-body p-5 text-center">
                    {{-- ไอคอน --}}
                    <div id="kioskAlertIconWrapper" class="alert-icon-circle mb-4">
                        <i id="kioskAlertIcon" class="bi"></i>
                    </div>

                    {{-- หัวข้อ --}}
                    <h1 id="kioskAlertModalLabel" class="fw-bold text-dark mb-3" style="letter-spacing: -1px;"></h1>

                    {{-- เนื้อหา --}}
                    <div id="kioskAlertBody" class="fs-5 text-muted mb-5 px-3">
                        {{-- ข้อความจะถูกฉีดจาก JS --}}
                    </div>

                    {{-- ปุ่มกด --}}
                    <button type="button" class="kiosk-btn-main w-100 py-3 shadow-sm" data-bs-dismiss="modal">
                        OK, GOT IT
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ mix('js/app.js') }}"></script>
    <script>
        function updateClock() {
            const now = new Date();
            const h = now.getHours();
            const m = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');
            const hh = String(h % 12 || 12).padStart(2, '0');
            const ampm = h >= 12 ? 'PM' : 'AM';

            document.querySelectorAll('.js-hours').forEach(el => el.textContent = hh);
            document.querySelectorAll('.js-minutes').forEach(el => el.textContent = m);
            document.querySelectorAll('.js-seconds').forEach(el => el.textContent = s);
            document.querySelectorAll('.js-period').forEach(el => el.textContent = ampm);
            document.querySelectorAll('.js-date').forEach(el => el.textContent = now.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }));
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
    @stack('scripts')
</body>
</html>