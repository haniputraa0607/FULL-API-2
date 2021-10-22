<?php

namespace Modules\Transaction\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\Transaction;
use App\Lib\MyHelper;
use Modules\Transaction\Entities\ManualRefund;

class ApiTransactionOutletService extends Controller
{
    public function listOutletService(Request $request)
    {	
    	$list = Transaction::where('transaction_from', 'outlet-service')
    			->join('transaction_outlet_services','transactions.id_transaction', 'transaction_outlet_services.id_transaction')
	            ->join('users','transactions.id_user','=','users.id')
	            ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
	            ->leftJoin('transaction_products','transactions.id_transaction','=','transaction_products.id_transaction')
	            ->leftJoin('transaction_product_services','transactions.id_transaction','=','transaction_product_services.id_transaction')
	            ->leftJoin('products','products.id_product','=','transaction_products.id_product')
	            ->with('user')
	            ->groupBy('transactions.id_transaction');

        $countTotal = null;

        if ($request->rule) {
            $countTotal = $list->count();
            $this->filterList($list, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
            	'id_transaction',
                'transaction_date',
                'outlet_code',
                'transaction_receipt_number', 
                'name', 
                'phone',
                'transaction_grandtotal', 
                'transaction_payment_status', 
            ];

            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $list->orderBy($colname, $column['dir']);
                }
            }
        }
        $list->orderBy('transactions.id_transaction', $column['dir'] ?? 'DESC');

        if ($request->page) {
            $list = $list->paginate($request->length ?: 15);
            $list->each(function($item) {
                $item->images = array_map(function($item) {
                    return config('url.storage_url_api').$item;
                }, json_decode($item->images) ?? []);
            });
            $list = $list->toArray();
            if (is_null($countTotal)) {
                $countTotal = $list['total'];
            }
            // needed by datatables
            $list['recordsTotal'] = $countTotal;
            $list['recordsFiltered'] = $list['total'];
        } else {
            $list = $list->get();
        }
        return MyHelper::checkGet($list);
    }

    public function filterList($model, $rule, $operator = 'and')
    {
        $new_rule = [];
        $where    = $operator == 'and' ? 'where' : 'orWhere';
        foreach ($rule as $var) {
            $var1 = ['operator' => $var['operator'] ?? '=', 'parameter' => $var['parameter'] ?? null, 'hide' => $var['hide'] ?? false];
            if ($var1['operator'] == 'like') {
                $var1['parameter'] = '%' . $var1['parameter'] . '%';
            }
            $new_rule[$var['subject']][] = $var1;
        }
        $model->where(function($model2) use ($model, $where, $new_rule){
            $inner = [
            	'transaction_receipt_number', 
            	'order_id', 
            	'outlet_name',
            	'outlet_code',
            	'transaction_grandtotal',
            	'transaction_payment_status'
            ];
            foreach ($inner as $col_name) {
                if ($rules = $new_rule[$col_name] ?? false) {
                    foreach ($rules as $rul) {
                        $model2->$where($col_name, $rul['operator'], $rul['parameter']);
                    }
                }
            }

            $inner = ['name', 'phone', 'email'];
            foreach ($inner as $col_name) {
                if ($rules = $new_rule[$col_name] ?? false) {
                    foreach ($rules as $rul) {
                        $model2->$where('users.'.$col_name, $rul['operator'], $rul['parameter']);
                    }
                }
            }

            $inner = ['id_outlet'];
            foreach ($inner as $col_name) {
                if ($rules = $new_rule[$col_name] ?? false) {
                    foreach ($rules as $rul) {
                        $model2->$where('transactions.'.$col_name, $rul['operator'], $rul['parameter']);
                    }
                }
            }
        });

        if ($rules = $new_rule['transaction_date'] ?? false) {
            foreach ($rules as $rul) {
                $model->where(\DB::raw('DATE(transaction_date)'), $rul['operator'], $rul['parameter']);
            }
        }
    }
}
