<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;


    protected $table = 'kiosk_bookings';


    protected $fillable = [
        'booking_id',
        'voucher_no',
        'first_name',
        'last_name',
        'arrival_date',
        'departure_date',
        'arrival_time',
        'departure_time',
        'room_name',
        'room_type_name',
        'is_confirmed',
        'country',
        'source',
        'mobile',
        'phone',
        'email',
        'address',
        'raw_data',
    ];


    protected $casts = [
        'arrival_date' => 'date',
        'departure_date' => 'date',
        'raw_data' => 'array',
    ];
}