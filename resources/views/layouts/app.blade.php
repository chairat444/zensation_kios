<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Zensation Kiosk') }}</title>

    {{-- ตัวแปรสีหลักสำหรับ Kiosk --}}
    <style>
        :root {
            --kiosk-primary: #3b5998;
            --kiosk-secondary: #f0f2f5;
            --kiosk-text-dark: #1c1e21;
            --kiosk-text-light: #ffffff;
            --kiosk-glass-bg: rgba(255, 255, 255, 0.15);
            --kiosk-glass-border: rgba(255, 255, 255, 0.3);
            --kiosk-glass-shadow: rgba(0, 0, 0, 0.1);
            --kiosk-success: #38c172;
            --kiosk-warning: #f7b924;
            --kiosk-danger: #e3342f;
        }
    </style>

    {{-- 1. Core CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    {{-- *** เพิ่ม CSS ที่จำเป็น: Swiper และ Bootstrap Icons *** --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- ** CSS หลักของ Kiosk UI (รวมถึง Glassmorphism และ Fixed Layout) ** --}}
    {{-- ตรวจสอบให้แน่ใจว่าไฟล์นี้อยู่ใน public/css/home.css --}}
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">

    @stack('styles')
</head>

<body>
    <main class="min-vh-100 d-flex flex-column">
        {{-- ******************************************************* --}}
        {{-- ** 1. DYNAMIC BACKGROUND LAYER: SWIPER (Moves) ** --}}
        {{-- ******************************************************* --}}
        <div class="swiper">
            <div class="swiper-wrapper">
                {{-- ลูปรูปภาพพื้นหลัง --}}
                @foreach ([1, 2, 3, 4, 5] as $n)
                    <div class="swiper-slide">
                        {{-- Background Image: ใช้ asset() แต่จำเป็นต้องมีไฟล์รูปใน public/images/kiosk/ --}}
                        <div class="bg-img"
                            style="background-image:url('{{ asset("images/kiosk/$n.jpg") }}')"></div>
                        {{-- Overlay --}}
                        <div class="overlay"></div>
                    </div>
                @endforeach
            </div>
            <div class="swiper-pagination"></div>
        </div>

        {{-- ******************************************************* --}}
        {{-- ** 2. FIXED CONTENT OVERLAY (UI STAYS PUT) ** --}}
        {{-- ******************************************************* --}}
        <div class="fixed-content-overlay">
            <div class="vh-app">
                {{-- HEADER: Logo Top-Left & Clock Top-Right --}}
                <header class="kiosk-header">
                    <div class="logo-top-left">
                        <img src="{{ asset('images/zensationlogo_blk.jpg') }}" alt="Zensation Logo"
                            class="logo-img">
                        <div class="brand-sub">Self-service Kiosk</div>
                    </div>
                    <div class="clock-top-right">
                        <div class="time-display">
                            <span class="time-main js-hours">00</span>
                            <span class="time-main dots">:</span>
                            <span class="time-main js-minutes">00</span>

                            <div class="time-side-wrap">
                                <span class="time-seconds js-seconds">00</span>
                                <div class="time-period js-period">AM</div>
                            </div>
                        </div>
                        <div class="clock-date-lg js-date"></div>
                    </div>
                </header>

                {{-- MAIN CONTENT: @yield('content') will be centered here --}}
                <section class="kiosk-hero">
                    @yield('content')
                </section>

                {{-- FOOTER: Full Glassmorphism, 2-Row Layout --}}
                <div class="kiosk-footer-container">
                    <div class="helper-wrap">
                        <div class="footer-message-row">
                            <i class="bi bi-chat-dots-fill"></i>
                            Please approach the front desk staff for assistance.
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
                            <div class="contact-block hours-block">
                                <i class="bi bi-clock-fill contact-icon"></i>
                                <div class="info-details">
                                    <span class="label">HOURS</span>
                                    <span class="value">OPEN 24/7</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- END FIXED CONTENT OVERLAY --}}

    </main>

    {{-- 3. Core JS --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>

    <script>
        // Clock Update Logic
        function tick() {
            const now = new Date();
            const h = now.getHours();
            const m = now.getMinutes();
            const s = now.getSeconds();

            const period = h >= 12 ? 'PM' : 'AM';
            const hh = (h % 12) === 0 ? 12 : (h % 12);

            // Update Hours, Minutes, Seconds, Period
            document.querySelectorAll('.js-hours').forEach(el => el.textContent = String(hh).padStart(2, '0'));
            document.querySelectorAll('.js-minutes').forEach(el => el.textContent = String(m).padStart(2, '0'));
            document.querySelectorAll('.js-seconds').forEach(el => el.textContent = String(s).padStart(2, '0'));
            document.querySelectorAll('.js-period').forEach(el => el.textContent = period);

            const d = now.toLocaleDateString('en-US', {
                month: 'long',
                weekday: 'long',
                day: 'numeric'
            });
            document.querySelectorAll('.js-date').forEach(el => el.textContent = d);
        }
        setInterval(tick, 1000);
        tick();

        // Swiper Initialization
        new Swiper('.swiper', {
            loop: true,
            speed: 900,
            autoplay: {
                delay: 4500,
                disableOnInteraction: false
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true
            },
            effect: 'fade',
            fadeEffect: {
                crossFade: true
            }
        });
    </script>

    @stack('scripts')
</body>

</html>
