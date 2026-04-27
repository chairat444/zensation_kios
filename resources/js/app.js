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
    const KEYBOARD_GAP = 16;

    const getKeyboardHeight = () => {
        const h = $keyboardElement.outerHeight();
        return h && h > 0 ? h : 320;
    };

    const applyContentLift = (inputEl = null) => {
        const keyboardHeight = getKeyboardHeight();
        const baseLift = Math.max(55, Math.min(150, Math.round(keyboardHeight * 0.24)));
        let lift = baseLift;

        if (inputEl && inputEl.getBoundingClientRect) {
            const rect = inputEl.getBoundingClientRect();
            const keyboardTop = window.innerHeight - keyboardHeight - 20;
            const overlap = rect.bottom + KEYBOARD_GAP - keyboardTop;
            if (overlap > 0) {
                lift += overlap;
            }
        }

        // Keep top breathing space so content won't stick to header.
        const contentEl = $content.get(0);
        if (contentEl && contentEl.getBoundingClientRect) {
            const contentTop = contentEl.getBoundingClientRect().top;
            const minTopGap = 130;
            const projectedTop = contentTop - lift;
            if (projectedTop < minTopGap) {
                lift = Math.max(0, lift - (minTopGap - projectedTop));
            }
        }

        $content.css({
            transform: `translateY(-${Math.round(lift)}px)`,
            transition: 'transform 0.35s cubic-bezier(0.16, 1, 0.3, 1)'
        });
    };

    const openKeyboard = (inputEl = null) => {
        $keyboardElement.addClass('show-kb');
        $('body').addClass('keyboard-open');
        applyContentLift(inputEl);
        if (inputEl && typeof inputEl.scrollIntoView === 'function') {
            setTimeout(() => {
                inputEl.scrollIntoView({ block: 'center', behavior: 'smooth' });
            }, 80);
        }
    };

    const closeKeyboard = () => {
        $keyboardElement.removeClass('show-kb');
        $('body').removeClass('keyboard-open');
        $content.css('transform', 'translateY(0)');
    };

    if ($('.kiosk-keyboard-input').length > 0) {
        KioskKeyboard.init('.kiosk-keyboard-input');

        $(document).on('focus', '.kiosk-keyboard-input', function() {
            openKeyboard(this);
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
                closeKeyboard();
            }
        });

        // ให้ keyboard.js เรียกใช้เพื่อคงสถานะตอนสลับ layout (เหมือน iPhone)
        $(document).on('kiosk:keyboard-keep-open', (event, payload = {}) => {
            openKeyboard(payload.input || document.activeElement || null);
        });

        $(document).on('kiosk:keyboard-close', () => {
            closeKeyboard();
        });

        // Add tactile-like visual feedback for touchscreen keyboard taps
        $(document).on('touchstart mousedown', '.simple-keyboard .hg-button', function () {
            $(this).removeClass('kb-release');
            $(this).addClass('kb-pressed');
        });
        $(document).on('touchend touchcancel mouseup mouseleave', '.simple-keyboard .hg-button', function () {
            $(this).removeClass('kb-pressed');
            $(this).addClass('kb-release');
            setTimeout(() => $(this).removeClass('kb-release'), 240);
        });
    }

    // --- Global Popup Function ---
    window.showPopup = function (type, message, title = null) {
        const $modalElement = $('#kioskAlertModal');
        if ($modalElement.length === 0) return;

        // ปิดคีย์บอร์ดเมื่อ Popup มา
        closeKeyboard();

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