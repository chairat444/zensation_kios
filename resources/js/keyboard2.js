import Keyboard from 'simple-keyboard';
import 'simple-keyboard/build/css/index.css';

const KioskKeyboard = {
    instance: null,
    currentInput: null,

    init: function(selector = '.kiosk-keyboard-input') {
        // ลบบรรทัด const Keyboard = window.SimpleKeyboard.default; ออกไปได้เลย
        // เพราะเราใช้ Keyboard ที่ import มาจากด้านบนแล้ว

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
                '{lang}': '🌐',
                '{shift}': '⇧',
                '{backspace}': '⌫',
                '{enter}': '↵',
                '{space}': ' '
            }
        });

        $(document).on('focus', selector, (e) => {
            this.currentInput = e.target;
            $('.simple-keyboard').show();
            this.instance.setInput(e.target.value);
        });
    },

    handleOnKeyPress: function(button) {
        let currentLayout = this.instance.options.layoutName;

        if (button === "{lang}") {
            this.instance.setOptions({
                layoutName: currentLayout.includes("thai") ? "default" : "thai"
            });
        }
        else if (button === "123") {
            this.instance.setOptions({
                layoutName: currentLayout.includes("thai") ? "thai-num" : "english-num"
            });
        }
        else if (button === "ABC" || button === "กขค") {
            this.instance.setOptions({
                layoutName: (button === "ABC") ? "default" : "thai"
            });
        }
        else if (button === "{shift}") {
            if (currentLayout === "default") this.instance.setOptions({ layoutName: "english-up" });
            else if (currentLayout === "english-up") this.instance.setOptions({ layoutName: "default" });
            else if (currentLayout === "thai") this.instance.setOptions({ layoutName: "thai-shift" });
            else if (currentLayout === "thai-shift") this.instance.setOptions({ layoutName: "thai" });
        }
        else if (button === "{enter}") {
            $('.simple-keyboard').hide();
            if(this.currentInput) this.currentInput.blur();
        }
    },

    updateButtonHighlight: function() {
        if (!this.instance) return;

        const layout = this.instance.options.layoutName;
        $('.hg-button').removeClass('active-highlight');

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
            this.currentInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }
};

export default KioskKeyboard;