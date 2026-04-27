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
];
