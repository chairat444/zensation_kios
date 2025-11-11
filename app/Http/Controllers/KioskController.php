<?php
// app/Http/Controllers/KioskController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class KioskController extends Controller
{
    public function availabilityForm()
    {
        return view('kiosk.availability');
    }

    public function availabilitySearch(Request $r)
    {
        $data = $r->validate([
            'checkin'   => ['required','date'],
            'checkout'  => ['required','date','after:checkin'],
            'adults'    => ['required','integer','min:1','max:6'],
            'children'  => ['nullable','integer','min:0','max:6'],
            'rooms'     => ['required','integer','min:1','max:5'],
        ]);

        // TODO: call eZee API here …
        // $resp = Http::post('https://api.ezeetechnosys.com/...', [...]);

        // mock results (ตัวอย่างรอเชื่อม API จริง)
        $rooms = [
            ['name'=>'Superior Room','max'=>2,'price'=>2780,'desc'=>'Queen bed • 24 m² • Wi-Fi'],
            ['name'=>'Deluxe Room','max'=>3,'price'=>3290,'desc'=>'King bed • 28 m² • Wi-Fi'],
            ['name'=>'Family Suite','max'=>4,'price'=>4590,'desc'=>'2 Bedrooms • 45 m² • Pantry'],
        ];

        return view('kiosk.availability', compact('rooms','data'));
    }
}
