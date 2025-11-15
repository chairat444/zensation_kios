<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller สำหรับจัดการหน้าจอ Kiosk Self-Service
 */
class KioskController extends Controller
{
    /**
     * แสดงหน้าหลักของ Kiosk (Home Screen)
     *
     * @return \Illuminate\View\View
     */
    public function showHome()
    {
        // ค่าต่างๆ เช่น BOOKING_URL จะถูกดึงใน Blade ด้วย config('kiosk.hotel.booking_url')
        return view('kiosk.home');
    }

    /**
     * แสดงหน้า Check-in Self-Service
     *
     * @return \Illuminate\View\View
     */
    public function showCheckin()
    {
        // ดึง HOTEL_ID จากไฟล์ config/kiosk.php มาแสดงใน Log หรือใช้ในการเตรียมข้อมูล (ถ้ามี)
        $hotelId = config('kiosk.hotel.hotel_id');

        Log::info("Kiosk Check-in page accessed. Using Hotel ID: {$hotelId}");

        // สามารถส่งค่า $hotelId ไปยัง view ได้ แต่เราเลือกที่จะให้ Blade/JS ดึงจาก config โดยตรง
        // เพื่อให้โค้ดใน Controller สะอาดที่สุด
        return view('kiosk.checkin');
    }

    /**
     * แสดงหน้า Check-out Self-Service
     *
     * @return \Illuminate\View\View
     */
    public function showCheckout()
    {
        return view('kiosk.checkout');
    }

    // ************************************************************
    // ********** ส่วนของ API สำหรับ Kiosk**********
    // ************************************************************

    /**
     * API: ค้นหาข้อมูลการจองห้องพักจาก Reservation ID
     */
    public function searchReservation(Request $request)
    {
        $reservationId = strtoupper($request->input('reservation_id'));
        $hotelId = config('kiosk.hotel.hotel_id');
        $apiUrl = 'https://live.ipms247.com/channelbookings/vacation_rental.php';

        if (!$reservationId) {
            return response()->json(['status' => 'error', 'message' => 'Reservation ID is required.'], 400);
        }

        // สร้าง Payload ตามรูปแบบ JSON ที่ API ต้องการ
        $payload = [
            'request_type' => 'get_reservation',
            'body' => [
                'hotel_id' => $hotelId,
                'reservation_id' => $reservationId,
            ],
        ];

        try {
            // ส่ง HTTP POST Request ไปยัง API ด้วย Payload
            $response = Http::timeout(15)->post($apiUrl, $payload);

            // ตรวจสอบว่าการเรียกสำเร็จ (HTTP 200) และ Response Status เป็น 'success'
            if ($response->successful() && $response->json('status') === 'success') {
                $data = $response->json('data');

                // จัดโครงสร้างข้อมูลที่จำเป็นสำหรับ Kiosk UI
                $reservationData = [
                    'reservation_id' => $data['reservation_id'] ?? $reservationId,
                    'guest_name' => $data['guest_name'] ?? 'N/A',
                    'room_name' => $data['room_name'] ?? 'N/A',
                    'room_code' => $data['room_code'] ?? null, // สำคัญ: ต้องมี Room Code เพื่อ Check-in
                    'check_in' => $data['check_in'] ?? null,
                    'check_out' => $data['check_out'] ?? null,
                ];

                Log::info("Kiosk Search Success via API: ID {$reservationId} found.");

                // ตรวจสอบ Room Code หากไม่มีถือว่าไม่สามารถ Check-in ได้
                if (empty($reservationData['room_code'])) {
                     return response()->json(['status' => 'error', 'message' => 'Reservation found but room has not been allocated (Room Code missing).'], 409);
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Reservation found.',
                    'data' => $reservationData
                ]);
            }

            // กรณี API ตอบกลับมาแต่มีสถานะเป็น 'error' หรือ 'failure'
            $errorMessage = $response->json('message') ?? 'Reservation not found or API returned an error.';
            Log::warning("Kiosk Search API Failed for ID {$reservationId}: {$errorMessage}", ['response' => $response->body()]);

            return response()->json(['status' => 'error', 'message' => $errorMessage], 404);

        } catch (\Exception $e) {
            // กรณีเกิดข้อผิดพลาดด้านเครือข่าย (Timeout, Connection Refused, etc.)
            Log::error("Kiosk Search Network Error for ID {$reservationId}: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Network error. Please try again later.'], 500);
        }
    }
}
