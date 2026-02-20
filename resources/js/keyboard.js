import Keyboard from 'simple-keyboard';
import 'simple-keyboard/build/css/index.css';

const KioskKeyboard = {
    instance: null,
    currentInput: null,

    init: function(selector = '.kiosk-keyboard-input') {
        this.instance = new Keyboard({
            onChange: (input) => this.handleOnChange(input),
            onKeyPress: (button) => this.handleOnKeyPress(button),
            onRender: () => this.updateButtonHighlight(),
            layout: {
                'default': [
                    '1 2 3 4 5 6 7 8 9 0',
                    'q w e r t y u i o p',
                    'a s d f g h j k l',
                    '{shift} z x c v b n m {backspace}',
                    '123 {lang} {space} {enter}'
                ],
                'english-up': [
                    '1 2 3 4 5 6 7 8 9 0',
                    'Q W E R T Y U I O P',
                    'A S D F G H J K L',
                    '{shift} Z X C V B N M {backspace}',
                    '123 {lang} {space} {enter}'
                ],
                'english-num': [
                    '1 2 3 4 5 6 7 8 9 0',
                    '- / : ; ( ) ฿ & @ "',
                    '#+= . , ? ! \' {backspace}',
                    'ABC {lang} {space} {enter}'
                ],
                'thai': [
                    'ๅ / _ ภ ถ ุ ึ ค ต จ ข ช',
                    'ๆ ไ ำ พ ะ ั ี ร น ย บ ล',
                    'ฟ ห ก ด เ ้ ่ า ส ว ง ฃ',
                    '{shift} ผ ป แ อ ิ ี ึ ท ม ใ ฝ {backspace}',
                    '123 {lang} {space} {enter}'
                ],
                'thai-shift': [
                    '+ ๑ ๒ ๓ ๔ ู ฿ ๕ ๖ ๗ ๘ ๙',
                    '๐ " ฎ ฑ ธ ํ ๊ ณ ฯ ญ ฐ ,',
                    'ฤ ฆ ฏ โ ฌ ็ ๋ ษ ศ ซ .',
                    '{shift} ( ) ฉ ฮ ฺ ์ ? ฒ ฬ ฦ {backspace}',
                    '123 {lang} {space} {enter}'
                ],
                'thai-num': [
                    '1 2 3 4 5 6 7 8 9 0',
                    '- / : ; ( ) ฿ & @ "',
                    '#+= . , ? ! \' {backspace}',
                    'กขค {lang} {space} {enter}'
                ]
            },
            display: {
                '123': '123',
                'ABC': 'ABC',
                'กขค': 'กขค',
                '{lang}': '🌐 TH/EN',
                '{shift}': '⇧',
                '{backspace}': '⌫',
                '{enter}': 'DONE ↵',
                '{space}': 'SPACE'
            }
        });

        // ตรวจจับการ Focus เพื่อ Set ค่า Input เริ่มต้นให้ Keyboard
        $(document).on('focus', selector, (e) => {
            this.currentInput = e.target;

            // Sync ค่าจาก Input เข้าสู่ Keyboard ทันทีที่จิ้ม
            this.instance.setOptions({
                inputName: e.target.id || 'default'
            });
            this.instance.setInput(e.target.value);
        });
    },

    handleOnKeyPress: function(button) {
        let currentLayout = this.instance.options.layoutName || "default";

        // --- ส่วนสลับภาษา (กดแล้วเปลี่ยนทันที ไม่ปิดคีย์บอร์ด) ---
        if (button === "{lang}") {
            let nextLang = currentLayout.includes("thai") ? "default" : "thai";
            this.instance.setOptions({
                layoutName: nextLang
            });
            return; // ออกจากฟังก์ชันทันที เพื่อไม่ให้ไปโดน Logic อื่น
        }

        // --- ส่วนสลับเป็นตัวเลข (กดแล้วเปลี่ยนทันที ไม่ปิดคีย์บอร์ด) ---
        else if (button === "123") {
            this.instance.setOptions({
                layoutName: currentLayout.includes("thai") ? "thai-num" : "english-num"
            });
            return;
        }

        // --- ส่วนสลับกลับเป็นตัวอักษร (กดแล้วเปลี่ยนทันที ไม่ปิดคีย์บอร์ด) ---
        else if (button === "ABC" || button === "กขค") {
            this.instance.setOptions({
                layoutName: (button === "ABC") ? "default" : "thai"
            });
            return;
        }

        // --- ส่วน Shift (กดแล้วเปลี่ยนทันที ไม่ปิดคีย์บอร์ด) ---
        else if (button === "{shift}") {
            let nextLayout = "default";
            if (currentLayout === "default") nextLayout = "english-up";
            else if (currentLayout === "english-up") nextLayout = "default";
            else if (currentLayout === "thai") nextLayout = "thai-shift";
            else if (currentLayout === "thai-shift") nextLayout = "thai";

            this.instance.setOptions({ layoutName: nextLayout });
            return;
        }

        // --- ปุ่มเดียวที่สั่งปิดคือปุ่ม Enter / Done ---
        else if (button === "{enter}") {
            $('.simple-keyboard').removeClass('show-kb');
            $('.checkin-content').css('transform', 'translateY(0)');

            if (this.currentInput) {
                this.currentInput.blur(); // เอา focus ออกเพื่อให้คีย์บอร์ดไม่เด้งขึ้นมาใหม่ทันที
            }
        }
    },

    updateButtonHighlight: function() {
        if (!this.instance) return;
        const layout = this.instance.options.layoutName;

        // ลบ Highlight เดิม
        $('.hg-button').removeClass('active-highlight');

        // ใส่สี Highlight ให้ปุ่มที่กำลังใช้งาน (เช่น Shift ค้างไว้)
        if (layout === 'english-up' || layout === 'thai-shift') {
            $('.hg-button[data-skbtn="{shift}"]').addClass('active-highlight');
        }
        if (layout === 'english-num' || layout === 'thai-num') {
            $('.hg-button[data-skbtn="123"]').addClass('active-highlight');
        }
    },

    handleOnChange: function(input) {
        if (this.currentInput) {
            this.currentInput.value = input;
            // สำคัญ: ต้อง dispatchEvent เพื่อให้ระบบอื่น (เช่น Validation) รู้ว่าค่าเปลี่ยน
            this.currentInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }
};

export default KioskKeyboard;