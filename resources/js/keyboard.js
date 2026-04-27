import Keyboard from 'simple-keyboard';
import 'simple-keyboard/build/css/index.css';

const KioskKeyboard = {
    instance: null,
    currentInput: null,
    language: "en",
    mode: "alpha", // alpha | shift | number

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
                    '{numbers} {lang} {space} {enter}'
                ],
                'english-up': [
                    '1 2 3 4 5 6 7 8 9 0',
                    'Q W E R T Y U I O P',
                    'A S D F G H J K L',
                    '{shift} Z X C V B N M {backspace}',
                    '{numbers} {lang} {space} {enter}'
                ],
                'english-num': [
                    '1 2 3 4 5 6 7 8 9 0',
                    '- / : ; ( ) ฿ & @ "',
                    '#+= . , ? ! \' {backspace}',
                    '{alpha_en} {lang} {space} {enter}'
                ],
                'thai': [
                    'ๅ / _ ภ ถ ุ ึ ค ต จ ข ช',
                    'ๆ ไ ำ พ ะ ั ี ร น ย บ ล',
                    'ฟ ห ก ด เ ้ ่ า ส ว ง ฃ',
                    '{shift} ผ ป แ อ ิ ี ึ ท ม ใ ฝ {backspace}',
                    '{numbers} {lang} {space} {enter}'
                ],
                'thai-shift': [
                    '+ ๑ ๒ ๓ ๔ ู ฿ ๕ ๖ ๗ ๘ ๙',
                    '๐ " ฎ ฑ ธ ํ ๊ ณ ฯ ญ ฐ ,',
                    'ฤ ฆ ฏ โ ฌ ็ ๋ ษ ศ ซ .',
                    '{shift} ( ) ฉ ฮ ฺ ์ ? ฒ ฬ ฦ {backspace}',
                    '{numbers} {lang} {space} {enter}'
                ],
                'thai-num': [
                    '1 2 3 4 5 6 7 8 9 0',
                    '- / : ; ( ) ฿ & @ "',
                    '#+= . , ? ! \' {backspace}',
                    '{alpha_th} {lang} {space} {enter}'
                ]
            },
            display: {
                '{numbers}': '123',
                '{alpha_en}': 'ABC',
                '{alpha_th}': 'กขค',
                '{lang}': 'TH/EN',
                '{shift}': '⇧',
                '{backspace}': '⌫',
                '{enter}': 'DONE ↵',
                '{space}': 'SPACE'
            }
        });

        // ตรวจจับการ Focus เพื่อ Set ค่า Input เริ่มต้นให้ Keyboard
        $(document).on('focus', selector, (e) => {
            this.currentInput = e.target;
            this.syncLayout();

            // Sync ค่าจาก Input เข้าสู่ Keyboard ทันทีที่จิ้ม
            this.instance.setOptions({
                inputName: e.target.id || 'default'
            });
            this.instance.setInput(e.target.value);
        });
    },

    handleOnKeyPress: function(button) {
        if (button === "{lang}") {
            this.language = this.language === "en" ? "th" : "en";
            this.mode = this.mode === "shift" ? "alpha" : this.mode;
            this.syncLayout();
            this.keepKeyboardOpen();
            return;
        }
        if (button === "{numbers}") {
            this.mode = "number";
            this.syncLayout();
            this.keepKeyboardOpen();
            return;
        }
        if (button === "{alpha_en}" || button === "{alpha_th}") {
            this.mode = "alpha";
            this.syncLayout();
            this.keepKeyboardOpen();
            return;
        }
        if (button === "{shift}") {
            if (this.mode === "number") {
                return;
            }
            this.mode = this.mode === "shift" ? "alpha" : "shift";
            this.syncLayout();
            this.keepKeyboardOpen();
            return;
        }
        if (button === "{enter}") {
            $(document).trigger('kiosk:keyboard-close');

            if (this.currentInput) {
                this.currentInput.blur(); // เอา focus ออกเพื่อให้คีย์บอร์ดไม่เด้งขึ้นมาใหม่ทันที
            }
        }
    },

    syncLayout: function() {
        if (!this.instance) return;

        let layoutName = "default";
        if (this.mode === "number") {
            layoutName = this.language === "th" ? "thai-num" : "english-num";
        } else if (this.mode === "shift") {
            layoutName = this.language === "th" ? "thai-shift" : "english-up";
        } else {
            layoutName = this.language === "th" ? "thai" : "default";
        }

        this.instance.setOptions({ layoutName });
    },

    keepKeyboardOpen: function() {
        $(document).trigger('kiosk:keyboard-keep-open', { input: this.currentInput });

        if (this.currentInput && document.activeElement !== this.currentInput) {
            this.currentInput.focus({ preventScroll: true });
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
            $('.hg-button[data-skbtn="{numbers}"]').addClass('active-highlight');
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