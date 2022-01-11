<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Lib\MyHelper;

class MockAPI extends Controller
{
    public function mock(Request $request)
    {
        if (app()->environment('production')) {
            return abort(404);
        }
        $path = $request->getRequestUri();
        $method = strtoupper($request->getMethod());
        switch ($path) {
            case '/api/mitra/data-update-request':
                if ($method == 'GET') {
                    return MyHelper::checkGet(
                        [
                            'field_list' => [
                                [
                                    'text' => 'Nama',
                                    'value' => 'name',
                                ],
                                [
                                    'text' => 'Email',
                                    'value' => 'email',
                                ],
                                [
                                    'text' => 'Nomor Rekening',
                                    'value' => 'account_number',
                                ],
                            ]
                        ]
                    );
                } else {
                    $request->validate([
                        'field' => 'string|required',
                        'new_value' => 'string|required',
                        'notes' => 'string|sometimes|nullable',
                    ]);
                    return [
                        'status' => 'success',
                        'result' => [
                            'message' => 'Permintaan perubahan data berhasil dikirim'
                        ]
                    ];
                }
                break;
        }
        return [];
    }
}
