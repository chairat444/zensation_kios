import $ from 'jquery';
window.$ = window.jQuery = $;

import './bootstrap';
import * as bootstrap from 'bootstrap';
import Swiper from 'swiper/bundle';
import KioskKeyboard from './keyboard'; // สมมติว่าไฟล์นี้จัดการ SimpleKeyboard ภายใน
import Swal from 'sweetalert2';

window.bootstrap = bootstrap;
window.Swiper = Swiper;
window.KioskKeyboard = KioskKeyboard;
window.Swal = Swal;

$(document).ready(function () {
    const $keyboardElement = $('.simple-keyboard');
    const $content = $('.checkin-content');

    if ($('.kiosk-keyboard-input').length > 0) {
        KioskKeyboard.init('.kiosk-keyboard-input');

        $(document).on('focus', '.kiosk-keyboard-input', function() {
            $keyboardElement.addClass('show-kb');
            $content.css({
                'transform': 'translateY(-100px)', // ขยับขึ้นอีกนิดให้พ้นระยะคีย์บอร์ด
                'transition': 'transform 0.5s cubic-bezier(0.16, 1, 0.3, 1)'
            });
        });

        // ปรับการตรวจจับคลิกนอก ให้ฉลาดขึ้น
        $(document).on('mousedown', function(e) {
            const isInput = $(e.target).hasClass('kiosk-keyboard-input');
            // เช็คว่าจุดที่คลิกอยู่ภายในพื้นที่ .simple-keyboard หรือไม่
            const isKeyboard = $(e.target).closest('.simple-keyboard').length > 0;
            // เช็คว่าจุดที่คลิกเป็นปุ่มของคีย์บอร์ดหรือไม่ (hg-button)
            const isKey = $(e.target).hasClass('hg-button');

            // ถ้าไม่ใช่คลิกที่ Input และ ไม่ได้คลิกบนคีย์บอร์ด/ปุ่มคีย์บอร์ด ถึงจะปิด
            if (!isInput && !isKeyboard && !isKey) {
                $keyboardElement.removeClass('show-kb');
                $content.css('transform', 'translateY(0)');
            }
        });
    }

    // --- Global Popup Function ---
    window.showPopup = function (type, message, title = null) {
        const $modalElement = $('#kioskAlertModal');
        if ($modalElement.length === 0) return;

        // ปิดคีย์บอร์ดเมื่อ Popup มา
        $keyboardElement.removeClass('show-kb');
        $content.css('transform', 'translateY(0)');

        const $iconWrapper = $('#kioskAlertIconWrapper');
        const $icon = $('#kioskAlertIcon');

        $iconWrapper.removeClass('alert-icon-error alert-icon-success');

        if (type === 'error') {
            $iconWrapper.addClass('alert-icon-error');
            $icon.attr('class', 'bi bi-exclamation-triangle');
            $('#kioskAlertModalLabel').text(title || 'ATTENTION');
        } else {
            $iconWrapper.addClass('alert-icon-success');
            $icon.attr('class', 'bi bi-hand-thumbs-up');
            $('#kioskAlertModalLabel').text(title || 'COMPLETED');
        }

        $('#kioskAlertBody').html(message);
        let modalInstance = bootstrap.Modal.getOrCreateInstance($modalElement[0]);
        modalInstance.show();
    };
});