@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="css/home.css?1">
@endpush

@section('content')
    {{-- Glass Card & Buttons --}}
    <div class="glass-wrap">
        <div class="glass">
            <div class="glass-title mb-3">
                <div class="brand-name">WELCOME TO ZENSATION</div>
            </div>
            <div class="action">
                {{-- ปุ่ม RESERVED เปิด Modal --}}
                <button type="button" class="kiosk-btn btn-reserve" data-bs-toggle="modal" data-bs-target="#qrModal">
                    <i class="bi bi-calendar-check"></i>
                    <span>RESERVED</span>
                </button>
                <a class="kiosk-btn btn-checkin" href="{{ route('kiosk.checkin') }}">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>CHECK-IN</span>
                </a>
                <a class="kiosk-btn btn-checkout" href="{{ route('kiosk.checkout') }}">
                    <i class="bi bi-door-open"></i>
                    <span>CHECK-OUT</span>
                </a>
            </div>
        </div>
    </div>

    {{-- *********************************************** --}}
    {{-- ********** MODAL สำหรับ QR CODE ********** --}}
    {{-- *********************************************** --}}
    <div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content-custom">
                {{-- ปรับ Modal Header ให้มีปุ่มปิด (X) --}}
                <div class="modal-header modal-header-custom">
                    <h5 class="modal-title" id="qrModalLabel">📱 Scan to Book Your Room 📱</h5>
                    {{-- ปุ่มปิด X ใช้คลาส btn-close ของ Bootstrap --}}
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="">Scan this QR code with your mobile device.</p>

                    {{-- พื้นที่สำหรับแสดง QR Code --}}
                    <div id="qrcode"></div>

                </div>
                <div class="modal-footer modal-footer-custom">
                    <a href="https://live.ipms247.com/booking/book-rooms-zensationtheresidence" target="_blank"
                        class="btn btn-primary kiosk-btn btn-direct-link">
                        <i class="bi bi-globe"></i>
                        Open Booking Page Manually
                    </a>
                    {{-- ปุ่มปิด Modal ด้านล่าง --}}
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Bootstrap JS (จำเป็นสำหรับการทำงานของ Modal) --}}

    {{-- ไลบรารีสำหรับสร้าง QR Code --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" crossorigin="anonymous"
        referrerpolicy="no-referrer"></script>

    <script>
        // กำหนด URL จองห้องพัก
        const BOOKING_URL = "https://live.ipms247.com/booking/book-rooms-zensationtheresidence";

        // **************************************************
        // ********** QR CODE GENERATION & MODAL **********
        // **************************************************

        // ตรวจสอบเมื่อ Modal เปิด
        document.addEventListener('DOMContentLoaded', () => {
            const qrModal = document.getElementById('qrModal');
            if (qrModal) {
                qrModal.addEventListener('shown.bs.modal', function() {
                    const qrcodeDiv = document.getElementById('qrcode');
                    // ล้าง QR Code เก่าก่อนสร้างใหม่ (ป้องกันการสร้างซ้ำ)
                    qrcodeDiv.innerHTML = '';

                    // สร้าง QR Code
                    new QRCode(qrcodeDiv, {
                        text: BOOKING_URL, // ใช้ URL จองห้องพัก
                        width: 256,
                        height: 256,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });
                });
            }
        });
    </script>
@endpush
