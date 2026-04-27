<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ZENSATION Auto-Passport Scan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/passport.css') }}">
</head>

<body>

    <div id="status" class="status-badge offline">OFFLINE</div>

    <div class="scanner-window text-center shadow-lg">
        <div class="preview-circle">
            <h2 id="guideText">กรุณาวางพาสปอร์ตลงบนเครื่อง</h2>
            <img id="passportImg" src="">
        </div>

        <div class="data-box">
            <div>
                <div class="label">PASSPORT NO.</div>
                <div id="resNo" class="value">-</div>
            </div>
            <div class="border-start border-secondary ps-4">
                <div class="label">FULL NAME</div>
                <div id="resName" class="value">-</div>
            </div>
        </div>

        <div id="log">Initializing WebSocket...</div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../js/scan.js"></script>
    <script src="{{ asset('js/passport-page.js') }}"></script>

</body>

</html>
