<?php

namespace Modules\Recruitment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class ApiHairstylistAttendanceController extends Controller
{
    /**
     * Menampilkan info clock in / clock out hari ini & informasi yg akan tampil saat akan clock in clock out
     * @return [type] [description]
     */
    public function getAttendance(Request $request)
    {
        // code...
    }

    /**
     * Menampilkan riwayat attendance & attendance requirement
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function attendanceHistories(Request $request)
    {
        // code...
    }

    /**
     * Clock in / Clock Out
     * @return [type] [description]
     */
    public function storeClock()
    {
        // code...
    }
}
