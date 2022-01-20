<?php

namespace Modules\Outlet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\Outlet;
use App\Lib\MyHelper;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Transaction\Entities\TransactionProductService;

class OutletDisplayController extends Controller
{
    /**
     * Display a listing of the queue.
     * @return Response
     */
    public function queue(Request $request)
    {
        $outlet = Outlet::where('outlet_code', $request->outlet_code)->with(['outlet_box' => function($query) {
            $query->where('outlet_box_status', 'Active');
        }])->first();
        if (!$outlet) {
            return abort(404);
        }
        $now = date('H:i:s');
        $currentShift = optional($outlet->outlet_time_shifts()
            ->where('shift_time_start', '<=', $now)
            ->where('shift_time_end', '>=', $now)
            ->join('outlet_schedules', function($join) {
                $join->on('outlet_schedules.id_outlet_schedule', 'outlet_time_shift.id_outlet_schedule')
                    ->where('day', MyHelper::indonesian_date_v2(time(), 'l'));
            })
            ->first())->shift;

        $outlet->outlet_box->transform(function($item) use ($currentShift) {
            $hairstylist = UserHairStylist::join('hairstylist_schedules', function ($join) {
                        $join->on('hairstylist_schedules.id_user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist');
                    })
                    ->join('hairstylist_schedule_dates', function ($join) use ($currentShift, $item) {
                        $join->on('hairstylist_schedule_dates.id_hairstylist_schedule', 'hairstylist_schedules.id_hairstylist_schedule')
                            ->where('shift', $currentShift)
                            ->whereDate('date', date('Y-m-d'))
                            ->where('id_outlet_box', $item->id_outlet_box);
                    })->first();

            if ($hairstylist) {
                $serviceInProgress = TransactionProductService::where('service_status', 'In Progress')
                    ->join('transactions', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
                    ->join('users', 'users.id', 'transactions.id_user')
                    ->where('id_user_hair_stylist', $hairstylist->id_user_hair_stylist)
                    ->first();

                $queue = TransactionProductService::select('users.name', 'schedule_time')->join('transactions', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
                        ->join('transaction_outlet_services', 'transaction_product_services.id_transaction', 'transaction_outlet_services.id_transaction')
                        ->join('transaction_products', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
                        ->join('products', 'transaction_products.id_product', 'products.id_product')
                        ->join('users', 'users.id', 'transactions.id_user')
                        ->where(function($q) {
                            $q->whereNull('service_status');
                            $q->orWhere('service_status', '!=', 'Completed');
                        })
                        ->where('transaction_product_services.id_user_hair_stylist', $hairstylist->id_user_hair_stylist)
                        ->where('transaction_payment_status', 'Completed')
                        ->where('transaction_payment_status', '!=', 'Cancelled')
                        ->where('id_transaction_product_service', '!=', optiona($serviceInProgress)->id_transaction_product_service)
                        // ->whereDate('schedule_date', date('Y-m-d'))
                        ->orderBy('schedule_date', 'asc')
                        ->orderBy('schedule_time', 'asc')
                        ->take(10)
                        ->get()
                        ->transform(function ($item) {
                            return [
                                'name' => $item->name,
                                'schedule_time' => MyHelper::adjustTimezone($item->schedule_time, $outlet['city']['province']['time_zone_utc'] ?? null, 'H:i')
                            ];
                        });
            } else {
                return null;
            }

            return [
                'box_name' => $item['outlet_box_name'],
                'hairstylist_name' => $hairstylist['fullname'] ?? null,
                'hairstylist_photo' => $hairstylist['user_hair_stylist_photo'] ?? null,
                'hairstylist_name' => $hairstylist['fullname'] ?? null,
                'current_customer' => $serviceInProgress['name'] ?? null,
                'queue' => $queue
            ];
        });
        $outlet_box = $outlet->outlet_box->filter()->values();
        $result = [
            'outlet_box' => $outlet_box,
            'hash' => md5(json_encode($outlet_box)),
        ];
        return MyHelper::checkGet($result);
    }

    public function status(Request $request)
    {
        $outlet = Outlet::where('outlet_code', $request->outlet_code)->first();
        if (!$outlet) {
            return abort(404);
        }
        $result = [
            'refresh' => true,
        ];
        return MyHelper::checkGet($result);
    }
}
