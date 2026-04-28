<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KioskController extends Controller
{
    public function availabilityForm()
    {
        return view('kiosk.availability', [
            'data' => [
                'checkin' => now()->toDateString(),
                'checkout' => now()->addDay()->toDateString(),
                'adults' => 2,
                'children' => 0,
                'rooms' => 1,
            ],
        ]);
    }

    public function availabilitySearch(Request $request)
    {
        $data = $request->validate([
            'checkin' => ['required', 'date'],
            'checkout' => ['required', 'date', 'after:checkin'],
            'adults' => ['required', 'integer', 'min:1', 'max:6'],
            'children' => ['nullable', 'integer', 'min:0', 'max:6'],
            'rooms' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        // Placeholder data until booking API integration is implemented.
        return view('kiosk.availability', [
            'data' => $data,
            'rooms' => [],
        ]);
    }


    public function showHome()
    {
        return view('kiosk.home');
    }

    public function showCheckin()
    {
        // ดึง hotel_code จากไฟล์ config/kiosk.php มาแสดงใน Log หรือใช้ในการเตรียมข้อมูล (ถ้ามี)
        $hotelCode = config('kiosk.hotel.hotel_code');

        Log::info("Kiosk Check-in page accessed. Using Hotel ID: {$hotelCode}");

        // สามารถส่งค่า $hotelCode ไปยัง view ได้ แต่เราเลือกที่จะให้ Blade/JS ดึงจาก config โดยตรง
        // เพื่อให้โค้ดใน Controller สะอาดที่สุด
        return view('kiosk.checkin');
    }

    /** Staff test page for CRT-591-M001 HTTP bridge (see scripts/crt591_dispenser_server.py). */
    public function showCardDispenserTest()
    {
        return view('kiosk.card_dispenser_test', [
            'dispenserEnabled' => (bool) config('kiosk.card_dispenser.enabled'),
            'dispenserUrl' => (string) config('kiosk.card_dispenser.http_url'),
            'dispenserAddr' => (int) config('kiosk.card_dispenser.unit_address'),
        ]);
    }

    public function showCheckout()
    {
        return view('kiosk.checkout');
    }

    // ดึงรายการมาเก็บไว้ก่อน
    public function searchWithLiveSync(Request $request)
    {
        $targetDate = $request->input('date', now()->format('Y-m-d'));
        $searchTerm = trim((string) $request->input('search', ''));

        if ($searchTerm === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Search term is required.',
            ], 422);
        }

        // 1. ดึงข้อมูลจาก API มาอัปเดตก่อน
        try {
            // 1. เตรียม Body ของ Request
            $body = [
                "RES_Request" => [
                    "Request_Type" => "ArrivalList",
                    "Authentication" => [
                        "HotelCode" => config('kiosk.hotel.hotel_code'),
                        "AuthCode" => config('kiosk.hotel.auth_code')
                    ],
                    "Date" => [
                        "from_date" => $targetDate,
                        "to_date" => $targetDate
                    ]
                ]
            ];

            // 2. ส่ง Request โดยใช้ตัวแปร $body
            $response = Http::post(config('kiosk.hotel.pms_url'), $body);

            // dd($body);


            if ($response->successful()) {
                $data = $response->json();
                $reservations = data_get($data, 'Reservations.Reservation', []);

                // ป้องกันกรณี API ส่งมาแค่ 1 record แล้วไม่ใช่ Array
                if (isset($reservations['BookingTran'])) {
                    $reservations = [$reservations];
                }

                foreach ($reservations as $res) {
                    $transactions = data_get($res, 'BookingTran', []);
                    if (!is_array($transactions)) {
                        $transactions = [];
                    }
                    if (isset($transactions['SubBookingId'])) {
                        $transactions = [$transactions];
                    }

                    foreach ($transactions as $tran) {
                        Booking::updateOrCreate(
                            [
                                'booking_id' => $tran['SubBookingId']
                            ],
                            [
                                'voucher_no'   => $tran['VoucherNo'],
                                'first_name'   => $tran['FirstName'] ?? $res['FirstName'],
                                'last_name'    => $tran['LastName'] ?? $res['LastName'],
                                'arrival_date' => $tran['Start'] ?? $targetDate,
                                'departure_date' => $tran['End'],
                                'arrival_time' => $tran['ArrivalTime'],
                                'departure_time' => $tran['DepartureTime'],
                                'room_name' => $tran['RoomName'],
                                'room_type_name' => $tran['RoomTypeName'],
                                'is_confirmed' => $tran['IsConfirmed'],
                                'country' => $tran['Country'],
                                'source' => $tran['Source'],
                                'mobile' => $tran['Mobile'],
                                'phone' => $tran['Phone'],
                                'address' => $tran['Address'],
                                'email' => $tran['Email'],
                                'raw_data'     => $tran
                            ]
                        );
                    }
                }
            }

        } catch (\Exception $e) {
            // กรณี API ล่ม ให้ข้ามไปค้นหาจากข้อมูลล่าสุดที่มีใน DB แทน
            Log::warning("IPMS API Error: {$e->getMessage()}");
        }

        // 2. ค้นหาข้อมูลจาก Database หลังจาก Sync เสร็จ
        $booking = Booking::where('arrival_date', $targetDate)
            ->where('is_confirmed','1')
            ->where(function ($query) use ($searchTerm) {
                $query->Where('voucher_no', $searchTerm)
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$searchTerm}%"])
                    ->orWhere('booking_id', $searchTerm);
            })
            ->first();

        if (!$booking) {
            return response()->json([
                'status' => 'error',
                'message' => 'We couldn\'t find your reservation. Please double-check your <b>Booking ID</b>, <b>Voucher</b>, or <b>Name</b>.<br><br><small class="text-muted">Need help? Please contact the front desk.</small>'
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'booking_id'      => $booking->booking_id,
                'guest_name'      => $booking->first_name . ' ' . $booking->last_name,
                'room_name'       => $booking->room_name,
                'room_type'       => $booking->room_type_name,
                'voucher_no'      => $booking->voucher_no,
                'email'           => $booking->email,
                'address'         => $booking->address,
                'phone'           => $booking->phone,
                'mobile'          => $booking->mobile,
                'arrival_date'    => Carbon::parse($booking->arrival_date)->format('d M Y'),
                'departure_date'  => Carbon::parse($booking->departure_date)->format('d M Y'),
                'check_in'        => Carbon::parse($booking->arrival_date)->setTimeFromTimeString($booking->arrival_time)->toIso8601String(),
                'check_out'       => Carbon::parse($booking->departure_date)->setTimeFromTimeString($booking->departure_time)->toIso8601String(),
            ]
        ]);
    }

    //ค้นหาข้อมูลการจองห้องพักจาก Reservation ID
    public function searchReservation(Request $request)
    {
        $booking_id = strtoupper($request->input('booking_id'));
        $hotelCode = config('kiosk.hotel.hotel_code');
        $authCode = config('kiosk.hotel.auth_code');
        $apiUrl = config('kiosk.hotel.reservation_url');

        if (empty($booking_id)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Reservation ID is required.',
            ], 400);
        }

        // สร้าง Payload ตามรูปแบบ JSON ที่ API ต้องการ
        $payload = [
            "request_type" => "get_reservation",
            "body" => [
                "hotel_code" => $hotelCode,
                "booking_id" => $booking_id
            ]
        ];


        try {
            // ส่งแบบ JSON ชัดเจน
            $response = Http::timeout(15)
                ->withHeaders([
                    "AUTH_CODE" => $authCode
                ])
                ->asJson()
                ->post($apiUrl, $payload);

            if ($response->successful() && $response->json('status') === 'success') {
                $data = $response->json('data', []);
                $booking = $data[0] ?? [];

                $reservationData = [
                    'booking_id' => $booking['booking_id'] ?? $booking_id,
                    'guest_name'     => $booking['guest_name']     ?? 'N/A',
                    'room_name'      => $booking['room_name']      ?? 'N/A',
                    'room_code'      => $booking['room_code']      ?? null, // ต้องมีสำหรับ Check-in
                    'check_in'       => $booking['check_in']       ?? null,
                    'check_out'      => $booking['check_out']      ?? null,
                ];


                Log::info("Kiosk Search Success via API: ID {$booking_id} found.");

                if (empty($reservationData['room_code'])) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Reservation found but room has not been allocated (Room Code missing).',
                    ], 409);
                }

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Reservation found.',
                    'data'    => $reservationData,
                ]);
            }

            $errorMessage = $response->json('message') ?? 'Reservation not found or API returned an error.';

            Log::warning("Kiosk Search API Failed for ID {$booking_id}: {$errorMessage}", [
                'response' => $response->body(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => $errorMessage,
            ], 404);
        } catch (\Exception $e) {
            Log::error("Kiosk Search Network Error for ID {$booking_id}: " . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Network error. Please try again later.',
            ], 500);
        }
    }

    public function guestCheckIn(Request $request)
    {
        // 1. Validate ข้อมูล
        $validated = $request->validate([
            'booking_id'       => 'required|string',
            'guest_name'       => 'required|string',
            'email'            => 'required|string',
            'address'          => 'required|string',
            'phone'            => 'nullable|string',
            'mobile'           => 'nullable|string',
            'identity_type_id' => 'required|string',
            'identity_no'      => 'required|string',
            'identity_image'   => 'required|string',
            'guest_image'      => 'required|string',
            'guest_signature'  => 'required|string',
            'room_code'        => 'required|string',
        ]);

        // 2. เตรียม Payload สำหรับ PMS
        $pmsPayload = [
            "RES_Request" => [
                "Request_Type" => "GuestCheckIn",
                "Authentication" => [
                    "HotelCode" => config('kiosk.hotel.hotel_code'),
                    "AuthCode"  => config('kiosk.hotel.auth_code')
                ],
                "Reservation" => [
                    [
                        "BookingId"      => $validated['booking_id'],
                        "GuestName"      => $validated['guest_name'],
                        "Email"          => $validated['email'],
                        "Address"        => $validated['address'],
                        "Phone"          => $validated['phone'] ?? "",
                        "Mobile"         => $validated['mobile'] ?? "",
                        "IdentityTypeID" => $validated['identity_type_id'],
                        "IdentityNo"     => $validated['identity_no'],
                        "IdentityImage"  => $this->cleanBase64($validated['identity_image']),
                        "GuestImage"     => $this->cleanBase64($validated['guest_image']),
                        "GuestSignature" => $this->cleanBase64($validated['guest_signature']),
                        "TaxationId"     => ""
                    ]
                ]
            ]
        ];

        try {
            $apiUrl = config('kiosk.hotel.checkin_url');
            $response = Http::timeout(60)->post($apiUrl, $pmsPayload);
            $body = $response->json();

            // 3. ถ้า Check-in PMS สำเร็จ -> จัดการ MikroTik ต่อ
            if (isset($body['Success'])) {
                $grCardNo = $body['Success']['GuestRegistrationCards'][0]['GRCardNo'] ?? null;

                // --- ส่วนจัดการ MikroTik WiFi ---
                $wifiUser = $validated['room_code'];
                $wifiPass = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
                $wifiProvisioned = $this->manageMikroTikWiFi($wifiUser, $wifiPass, $validated['guest_name']);

                // อัปเดต DB
                DB::table('kiosk_bookings')
                    ->updateOrInsert(
                        ['booking_id' => $validated['booking_id']],
                        [
                            'gr_card_no' => $grCardNo,
                            'updated_at' => now(),
                        ]
                    );

                return response()->json([
                    'status'  => 'success',
                    'title'   => 'CHECK-IN SUCCESS',
                    'message' => $body['Success']['SuccessMsg'],
                    'data'    => [
                        'room_code'  => $validated['room_code'],
                        'gr_card_no' => $grCardNo,
                        'wifi_user'  => $wifiProvisioned ? $wifiUser : null,
                        'wifi_pass'  => $wifiProvisioned ? $wifiPass : null,
                        'wifi_ssid'  => $wifiProvisioned ? '@Zensation-N' : null,
                        'wifi_status' => $wifiProvisioned ? 'ready' : 'unavailable',
                    ]
                ]);
            }

            // กรณี Error จาก PMS
            if (isset($body['Error'])) {
                return response()->json([
                    'status'  => 'error',
                    'title'   => 'CHECK-IN ERROR',
                    'message' => $body['Error'][0]['ErrorMessage'],
                    'code'    => $body['Error'][0]['ErrorCode'] ?? null
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error("PMS Check-in Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'System error: ' . $e->getMessage()], 500);
        }
    }


    /**
     * จัดการลบและสร้าง User ใน MikroTik
     */
    private function manageMikroTikWiFi($username, $password, $guestName)
    {
        try {
            $host = (string) config('kiosk.mikrotik.host', '');
            $user = (string) config('kiosk.mikrotik.user', '');
            $pass = (string) config('kiosk.mikrotik.pass', '');
            $port = (int) config('kiosk.mikrotik.port', 8728);

            // If MikroTik is not configured yet, skip WiFi provisioning gracefully.
            if ($host === '' || $user === '' || $pass === '') {
                Log::warning('MikroTik config missing. Skip WiFi provisioning.', [
                    'host_set' => $host !== '',
                    'user_set' => $user !== '',
                    'pass_set' => $pass !== '',
                ]);
                return false;
            }

            // ใช้ชื่อ Class เต็มเพื่อป้องกันปัญหา Autoload
            $client = new \RouterOS\Client([
                'host' => $host,
                'user' => $user,
                'pass' => $pass,
                'port' => $port,
                'timeout' => 10
            ]);

            // --- STEP 1: ลบ User เก่า (ถ้ามี) ---
            $queryPrint = new \RouterOS\Query('/ip/hotspot/user/print');
            $queryPrint->where('name', $username);
            $existing = $client->query($queryPrint)->read();

            if (!empty($existing)) {
                foreach ($existing as $item) {
                    if (isset($item['.id'])) {
                        $queryDel = new \RouterOS\Query('/ip/hotspot/user/remove');
                        $queryDel->equal('.id', $item['.id']);
                        $client->query($queryDel)->read();
                    }
                }
            }

            // --- STEP 2: สร้าง User ใหม่ ---
            $queryAdd = new \RouterOS\Query('/ip/hotspot/user/add');
            $queryAdd->equal('server', 'hotspot1'); // ตรวจสอบชื่อ server ใน MikroTik ให้ตรงกัน
            $queryAdd->equal('name', $username);
            $queryAdd->equal('password', $password);
            $queryAdd->equal('profile', 'Guest 1 Day 4 Device 80m/80m');
            $queryAdd->equal('comment', "Kiosk: " . $guestName);

            $response = $client->query($queryAdd)->read();

            Log::info("MikroTik WiFi created for Room $username", ['response' => $response]);
            return true;

        } catch (\Exception $e) {
            Log::error("MikroTik Connection Failed: " . $e->getMessage());
            // Do not fail the whole check-in flow if WiFi provisioning failed.
            return false;
        }
    }

    private function cleanBase64($base64String)
    {
        // ตัดส่วน "data:image/png;base64," ออกถ้ามี
        if (preg_match('/^data:image\/(\w+);base64,/', $base64String)) {
            $base64String = substr($base64String, strpos($base64String, ',') + 1);
        }
        return $base64String;
    }

}
