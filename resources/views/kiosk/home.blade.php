@extends('layouts.app')

@push('styles')
    <!-- Swiper + Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="css/home.css">
@endpush

@section('content')
    <div class="vh-app">
        <section class="kiosk-hero">
            <div class="swiper">
                <div class="swiper-wrapper">
                    @foreach ([1, 2, 3, 4, 5] as $n)
                        <div class="swiper-slide">
                            <div class="bg-img" style="background-image:url('{{ asset("images/kiosk/$n.jpg") }}')"></div>
                            <div class="overlay"></div>

                            <div class="glass-wrap">
                                <div class="glass">
                                    {{-- โลโก้ด้านบน --}}
                                    <div class="logo-top text-center mb-2">
                                        <img src="{{ asset('images/zensationlogo_blk.jpg') }}" alt="Zensation Logo"
                                            class="logo-img">
                                    </div>

                                    {{-- ชื่อโรงแรมทางซ้าย --}}
                                    <div class="brand-text text-start mb-3">
                                        {{-- <div class="brand-name">Zensation The Residence</div> --}}
                                        <div class="brand-sub">Self-service Kiosk</div>
                                    </div>

                                    {{-- Clock --}}
                                    <div class="clock text-center">
                                        <div class="time-row">
                                            <span class="hours js-hours">00</span>
                                            <span class="dots">:</span>
                                            <span class="minutes js-minutes">00</span>
                                            <div class="side">
                                                <span class="period js-period">AM</span>
                                                <span class="seconds js-seconds">00</span>
                                            </div>
                                        </div>
                                        <div class="calendar js-date">Sep, Sunday 7</div>
                                    </div>


                                    {{-- ปุ่ม --}}
                                    <div class="action">
                                        <a class="kiosk-btn btn-reserve" href="{{ route('kiosk.availability') }}">
                                            <i class="bi bi-calendar-check"></i>
                                            <span>RESERVED</span>
                                        </a>
                                        <a class="kiosk-btn btn-checkin" href="{{ route('kiosk.checkin') }}">
                                            <i class="bi bi-box-arrow-in-right"></i>
                                            <span>WALK-IN</span>
                                        </a>
                                        <a class="kiosk-btn btn-checkout" href="{{ route('kiosk.checkout') }}">
                                            <i class="bi bi-door-open"></i>
                                            <span>CHECK-OUT</span>
                                        </a>
                                    </div>
                                </div>

                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="swiper-pagination"></div>
            </div>
        </section>

        <div class="helper">
            <div class="tile">Need help? Please contact the front desk.</div>
            <div class="tile">
                <i class="bi bi-telephone-fill"></i>
                <span> (+66) 2-286-1216 </span>
              </div>
              <div class="tile">
                <i class="bi bi-phone-fill"></i>
                <span> (+66) 8-8112-2001 </span>
              </div>
            <div class="tile">Open 24/7</div>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script>
        // อัปเดตทุก element ที่มีคลาส .js-time / .js-date (รองรับหลายสไลด์ + clone)
        function tick() {
            const now = new Date();

            const h = now.getHours();
            const m = now.getMinutes();
            const s = now.getSeconds();

            const period = h >= 12 ? 'PM' : 'AM';
            const hh = (h % 12) === 0 ? 12 : (h % 12);

            document.querySelectorAll('.js-hours').forEach(el => el.textContent = String(hh).padStart(2, '0'));
            document.querySelectorAll('.js-minutes').forEach(el => el.textContent = String(m).padStart(2, '0'));
            document.querySelectorAll('.js-seconds').forEach(el => el.textContent = String(s).padStart(2, '0'));
            document.querySelectorAll('.js-period').forEach(el => el.textContent = period);

            const d = now.toLocaleDateString('en-GB', {
                month: 'short',
                weekday: 'long',
                day: 'numeric'
            });
            document.querySelectorAll('.js-date').forEach(el => el.textContent = d);
        }
        setInterval(tick, 1000);
        tick();



        // Swiper
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
@endpush
