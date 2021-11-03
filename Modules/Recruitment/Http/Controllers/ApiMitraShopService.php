<?php

namespace Modules\Recruitment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;

use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductStockLog;
use Modules\ProductVariant\Entities\ProductVariantGroupDetail;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistSchedule;
use Modules\Recruitment\Entities\HairstylistScheduleDate;

use Modules\Transaction\Entities\HairstylistNotAvailable;
use Modules\Transaction\Entities\TransactionOutletService;
use Modules\Transaction\Entities\TransactionPaymentCash;
use Modules\Transaction\Entities\TransactionProductService;
use Modules\Transaction\Entities\TransactionProductServiceLog;

use Modules\Recruitment\Http\Requests\ScheduleCreateRequest;
use Modules\Recruitment\Http\Requests\DetailCustomerQueueRequest;

use Modules\Outlet\Entities\OutletBox;

use Modules\Transaction\Entities\TransactionProductServiceUse;
use Modules\UserRating\Entities\UserRatingLog;

use App\Lib\MyHelper;
use DB;
use DateTime;

class ApiMitraShopService extends Controller
{
    public function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->mitra = "Modules\Recruitment\Http\Controllers\ApiMitra";
        $this->trx = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->trx_outlet_service = "Modules\Transaction\Http\Controllers\ApiTransactionOutletService";
    }

    public function detailShopService(Request $request)
    {
    	$user = $request->user();

    	$trx = Transaction::where('transaction_receipt_number', $request->transaction_receipt_number)->first();
    	if (!$trx) {
    		return ['status' => 'fail', 'messages' => ['Transaksi tidak ditemukan']];
    	}

    	if ($trx->transaction_payment_status != 'Completed' && $trx->trasaction_payment_type != 'Cash') {
    		return ['status' => 'fail', 'messages' => ['Proses pembayaran belum selesai']];
    	}

    	$paymentCash = 0;
    	if ($trx->transaction_payment_status != 'Completed' && $trx->trasaction_payment_type == 'Cash') {
    		$paymentCash = 1;
    	}

    	$trx->load([
    		'user',
    		'transaction_products.product.photos',
    		'transaction_products' => function($q) {
    			$q->where('type', 'Product');
    		}
    	]);
    	$trxPayment = app($this->trx_outlet_service)->transactionPayment($trx);
    	$paymentMethod = null;
    	foreach ($trxPayment['payment'] as $val) {
    		$paymentMethod = $val['name'];
    		if (strtolower($val['name']) != 'balance') {
    			break;
    		}
    	}

    	$trxProduct = $trx->transaction_products;
        if(empty($trxProduct) || $trxProduct->isEmpty()){
            return ['status' => 'fail', 'messages' => ['Produk tidak ditemukan']];
        }

        $products = [];
        $subtotalProduct = 0;
        foreach ($trxProduct as $product){
        	$productPhoto = config('url.storage_url_api') . ($product['product']['photos'][0]['product_photo'] ?? 'img/product/item/default.png');
            $products[] = [
                'id_product' => $product['id_product'],
                'product_name' => $product['product']['product_name'],
				'qty' => $product['transaction_product_qty'],
				'price' => $product['transaction_product_price'],
				'subtotal' => $product['transaction_product_subtotal'],
				'photo' => $productPhoto
            ];
            $subtotalProduct += abs($product['transaction_product_subtotal']);
        }

    	$res = [
    		'transaction_receipt_number' => $trx['transaction_receipt_number'],
    		'transaction_date' => MyHelper::indonesian_date_v2(date('Y-m-d', strtotime($trx['transaction_date'])), 'j F Y'),
    		'name' => $trx['user']['name'],
    		'payment_method' => $paymentMethod,
    		'transaction_payment_status' => $trx['transaction_payment_status'],
    		'payment_cash' => $paymentCash,
    		'product_subtotal' => $subtotalProduct,
    		'products' => $products
    	];

    	return MyHelper::checkGet($res);
    }
}
