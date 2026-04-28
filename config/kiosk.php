<?php

return [
    // ข้อมูลจำเพาะสำหรับระบบ Kiosk Self-Service
    'hotel' => [
        // ID โรงแรมสำหรับเชื่อมต่อกับระบบ eZee Technosys
        'hotel_code' => '54178',
        'auth_code' =>'5844373224d0af89d4-4524-11f0-a',
        'booking_url' => 'https://live.ipms247.com/booking/book-rooms-zensationtheresidence',
        'reservation_url' =>'https://live.ipms247.com/channelbookings/vacation_rental.php',
        'checkin_url' =>'https://live.ipms247.com/index.php/page/service.kioskconnectivity',
        'pms_url' =>'https://live.ipms247.com/pmsinterface/pms_connectivity.php',
    ],

    'mikrotik' => [
        'host' => env('MIKROTIK_HOST'),
        'user' => env('MIKROTIK_USER'),
        'pass' => env('MIKROTIK_PASS'),
        'port' => env('MIKROTIK_PORT', 8728),
    ],

    /*
     * CRT-591-M001 card dispenser: browser calls a small local HTTP service that
     * opens COM and sends frames (same pattern as VB print server on localhost).
     * See scripts/crt591_dispenser_server.py
     */
    'card_dispenser' => [
        'enabled' => env('KIOSK_CARD_DISPENSER_ENABLED', false),
        'http_url' => env('KIOSK_CARD_DISPENSER_URL', 'http://127.0.0.1:59101/dispense'),
        'unit_address' => (int) env('KIOSK_CARD_DISPENSER_ADDR', 0),
    ],
];
