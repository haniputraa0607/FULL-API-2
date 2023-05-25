<?php

namespace Modules\Transaction\Http\Controllers;

use App\Http\Models\Deal;
use App\Http\Models\TransactionProductModifier;
use Illuminate\Pagination\Paginator;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\TransactionPayment;
use App\Http\Models\TransactionPickupGoSend;
use App\Http\Models\TransactionPickupWehelpyou;
use App\Http\Models\Province;
use App\Http\Models\City;
use App\Http\Models\User;
use App\Http\Models\Courier;
use App\Http\Models\Product;
use App\Http\Models\ProductPrice;
use App\Http\Models\ProductModifierPrice;
use App\Http\Models\ProductModifierGlobalPrice;
use App\Http\Models\Setting;
use App\Http\Models\StockLog;
use App\Http\Models\UserAddress;
use App\Http\Models\ManualPayment;
use App\Http\Models\ManualPaymentMethod;
use App\Http\Models\ManualPaymentTutorial;
use App\Http\Models\TransactionPaymentManual;
use Modules\Transaction\Entities\TransactionProductService;
use App\Http\Models\TransactionPaymentOffline;
use App\Http\Models\TransactionPaymentBalance;
use Modules\Disburse\Entities\MDR;
use Modules\IPay88\Entities\TransactionPaymentIpay88;
use App\Http\Models\TransactionMultiplePayment;
use App\Http\Models\Outlet;
use App\Http\Models\LogPoint;
use App\Http\Models\LogBalance;
use App\Http\Models\TransactionShipment;
use App\Http\Models\TransactionPickup;
use App\Http\Models\TransactionPaymentMidtran;
use Modules\ProductVariant\Entities\ProductVariant;
use Modules\ProductVariant\Entities\TransactionProductVariant;
use Modules\ShopeePay\Entities\TransactionPaymentShopeePay;
use App\Http\Models\DealsUser;
use App\Http\Models\DealsPaymentMidtran;
use App\Http\Models\DealsPaymentManual;
use Modules\IPay88\Entities\DealsPaymentIpay88;
use Modules\ShopeePay\Entities\DealsPaymentShopeePay;
use App\Http\Models\UserTrxProduct;
use Modules\Brand\Entities\Brand;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\Transaction\Http\Requests\CallbackFromIcount;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\Subscription\Entities\SubscriptionUserVoucher;
use Modules\Transaction\Entities\LogInvalidTransaction;
use Modules\Transaction\Entities\TransactionBundlingProduct;
use Modules\Transaction\Entities\TransactionPaymentCash;
use Modules\Transaction\Http\Requests\RuleUpdate;

use Modules\Transaction\Http\Requests\TransactionDetail;
use Modules\Transaction\Http\Requests\TransactionHistory;
use Modules\Transaction\Http\Requests\TransactionFilter;
use Modules\Transaction\Http\Requests\TransactionNew;
use Modules\Transaction\Http\Requests\TransactionShipping;
use Modules\Transaction\Http\Requests\GetProvince;
use Modules\Transaction\Http\Requests\GetCity;
use Modules\Transaction\Http\Requests\GetSub;
use Modules\Transaction\Http\Requests\GetAddress;
use Modules\Transaction\Http\Requests\GetNearbyAddress;
use Modules\Transaction\Http\Requests\AddAddress;
use Modules\Transaction\Http\Requests\UpdateAddress;
use Modules\Transaction\Http\Requests\DeleteAddress;
use Modules\Transaction\Http\Requests\ManualPaymentCreate;
use Modules\Transaction\Http\Requests\ManualPaymentEdit;
use Modules\Transaction\Http\Requests\ManualPaymentUpdate;
use Modules\Transaction\Http\Requests\ManualPaymentDetail;
use Modules\Transaction\Http\Requests\ManualPaymentDelete;
use Modules\Transaction\Http\Requests\MethodSave;
use Modules\Transaction\Http\Requests\MethodDelete;
use Modules\Transaction\Http\Requests\ManualPaymentConfirm;
use Modules\Transaction\Http\Requests\ShippingGoSend;
use Modules\Transaction\Entities\TransactionBreakdown;
use Modules\ProductVariant\Entities\ProductVariantGroup;;
use Modules\ProductVariant\Entities\ProductVariantGroupSpecialPrice;
use Modules\Transaction\Entities\SharingManagementFee;
use Modules\Transaction\Entities\SharingManagementFeeTransaction;
use Modules\UserRating\Entities\UserRatingLog;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\BusinessDevelopment\Entities\Partner;
use App\Lib\MyHelper;
use App\Lib\GoSend;
use App\Lib\Midtrans;
use Modules\Xendit\Entities\TransactionPaymentXendit;
use Validator;
use Hash;
use DB;
use Mail;
use Image;
use App\Lib\Icount;
use Illuminate\Support\Facades\Log;
use Modules\Quest\Entities\Quest;
use Lcobucci\JWT\Parser;
use App\Http\Models\OauthAccessToken;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\Transaction\Http\Requests\Signature;
use Modules\Transaction\Http\Requests\SignatureLoan;
use Modules\Transaction\Http\Requests\SignatureCashAdvance;
use Modules\Franchise\Entities\PromoCampaign;
use Modules\PromoCampaign\Entities\TransactionPromo;
use Modules\Product\Entities\ProductIcount;
use Modules\Product\Entities\ProductProductIcount;

class ApiTransaction extends Controller
{
    public $saveImage = "img/transaction/manual-payment/";

    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->shopeepay      = 'Modules\ShopeePay\Http\Controllers\ShopeePayController';
        $this->xendit         = 'Modules\Xendit\Http\Controllers\XenditController';
        $this->trx_outlet_service = "Modules\Transaction\Http\Controllers\ApiTransactionOutletService";
        $this->trx = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->refund = "Modules\Transaction\Http\Controllers\ApiTransactionRefund";
        $this->home_service_status = [
            'Finding Hair Stylist' => ['code' => 1, 'text' => 'Mencari hair stylist'],
            'Get Hair Stylist' => ['code' => 2, 'text' => 'Dapat hair stylist'],
            'On The Way' => ['code' => 3, 'text' => 'Hair stylist menuju lokasi Anda'],
            'Arrived' => ['code' => 4, 'text' => 'Hair stylist tiba di lokasi Anda'],
            'Start Service' => ['code' => 5, 'text' => 'Mulai layanan'],
            'Completed' => ['code' => 6, 'text' => 'Selesai layanan'],
            'Cancelled' => ['code' => 7, 'text' => 'Tidak menemukan hair stylist']
        ];
    }

    public function transactionRule() {
        $settingTotal = Setting::where('key', 'transaction_grand_total_order')->first();
        $settingService = Setting::where('key', 'transaction_service_formula')->first();
        $settingServiceValue = Setting::where('key', 'service')->first();

        $settingDiscount = Setting::where('key', 'transaction_discount_formula')->first();
        $settingPercent = Setting::where('key', 'discount_percent')->first();
        $settingNom = Setting::where('key', 'discount_nominal')->first();

        $settingTax = Setting::where('key', 'transaction_tax_formula')->first();
        $settingTaxValue = Setting::where('key', 'tax')->first();

        $settingPoint = Setting::where('key', 'point_acquisition_formula')->first();
        $settingPointValue = Setting::where('key', 'point_conversion_value')->first();

        $settingCashback = Setting::where('key', 'cashback_acquisition_formula')->first();
        $settingCashbackValue = Setting::where('key', 'cashback_conversion_value')->first();
        $settingCashbackMax = Setting::where('key', 'cashback_maximum')->first();

        $settingOutlet = Setting::where('key', 'default_outlet')->first();

        $outlet = Outlet::get()->toArray();

        if (!$settingTotal || !$settingService || !$settingServiceValue || !$settingDiscount || !$settingTax || !$settingTaxValue || !$settingPoint || !$settingPointValue || !$settingCashback || !$settingCashbackValue || !$settingOutlet) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Data setting not found']
            ]);
        }

        $data = [
            'grand_total'   => explode(',', $settingTotal['value']),
            'service'       => [
                'data'  => explode(' ', $settingService['value']),
                'value' => $settingServiceValue['value']
            ],
            'discount'      => [
                'data'    => explode(' ', $settingDiscount['value']),
                'percent' => $settingPercent['value'],
                'nominal' => $settingNom['value'],
            ],
            'tax'       => [
                'data'  => explode(' ', $settingTax['value']),
                'value' => $settingTaxValue['value']
            ],
            'point'       => [
                'data'  => explode(' ', $settingPoint['value']),
                'value' => $settingPointValue['value']
            ],
            'cashback'       => [
                'data'  => explode(' ', $settingCashback['value']),
                'value' => $settingCashbackValue['value'],
                'max' => $settingCashbackMax['value'],
            ],
            'outlet'        => $outlet,
            'default_outlet' => $settingOutlet,
        ];

        return response()->json(MyHelper::checkGet($data));
    }

    public function transactionRuleUpdate(RuleUpdate $request) {
        $post = $request->json()->all();
        DB::beginTransaction();
        if ($post['key'] == 'grand_total') {
            $merge = implode(',', $post['item']);

            $save = Setting::where('key', 'transaction_grand_total_order')->first();
            if (!$save) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $save->value = $merge;
            $save->save();

            DB::commit();
            return response()->json(MyHelper::checkUpdate($save));
        } elseif ($post['key'] == 'service') {
            // return $post;
            $dataResult = [];

            array_push($dataResult, '(');

            if (isset($post['item'])) {
                foreach ($post['item'] as $key => $item) {
                    if ($item == 'resultsubtotal') {
                        $dataItem = 'subtotal';
                    } elseif ($item == 'resultservice') {
                        $dataItem = 'service';
                    } elseif ($item == 'resultdiscount') {
                        $dataItem = 'discount';
                    } elseif ($item == 'resultshipping') {
                        $dataItem = 'shipping';
                    } elseif ($item == 'resulttax') {
                        $dataItem = 'tax';
                    } elseif ($item == 'resultkali') {
                        $dataItem = '*';
                    } elseif ($item == 'resultbagi') {
                        $dataItem = '/';
                    } elseif ($item == 'resulttambah') {
                        $dataItem = '+';
                    } elseif ($item == 'resultkurang') {
                        $dataItem = '-';
                    } elseif ($item == 'resultkbuka') {
                        $dataItem = '(';
                    } elseif ($item == 'resultktutup') {
                        $dataItem = ')';
                    }

                    array_push($dataResult, $dataItem);
                }
            } else {
                array_push($dataResult, '');
            }
            array_push($dataResult, ')');
            array_push($dataResult, '*');
            array_push($dataResult, 'value');

            $join = implode(' ', $dataResult);

            $update = Setting::where('key', 'transaction_service_formula')->first();

            if (!$update) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $update->value = $join;
            $update->save();

            if (!$update) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting update failed']
                ]);
            }

            $updateService = Setting::where('key', 'service')->first();
            if (!$updateService) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $updateService->value = $post['value']/100;
            $updateService->save();
            if (!$updateService) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting update failed']
                ]);
            }

            DB::commit();
            return response()->json(MyHelper::checkUpdate($updateService));

        } elseif ($post['key'] == 'courier') {
            $dataResult = [];

            if (isset($post['item'])) {
                foreach ($post['item'] as $key => $item) {
                    if ($item == 'resultsubtotal') {
                        $dataItem = 'subtotal';
                    } elseif ($item == 'resultservice') {
                        $dataItem = 'service';
                    } elseif ($item == 'resultdiscount') {
                        $dataItem = 'discount';
                    } elseif ($item == 'resultshipping') {
                        $dataItem = 'shipping';
                    } elseif ($item == 'resulttax') {
                        $dataItem = 'tax';
                    } elseif ($item == 'resultkali') {
                        $dataItem = '*';
                    } elseif ($item == 'resultbagi') {
                        $dataItem = '/';
                    } elseif ($item == 'resulttambah') {
                        $dataItem = '+';
                    } elseif ($item == 'resultkurang') {
                        $dataItem = '-';
                    } elseif ($item == 'resultkbuka') {
                        $dataItem = '(';
                    } elseif ($item == 'resultktutup') {
                        $dataItem = ')';
                    }

                    array_push($dataResult, $dataItem);
                }
            } else {
                array_push($dataResult, '');
            }

            $join = implode(' ', $dataResult);

            $update = Setting::where('key', 'transaction_delivery_standard')->first();

            if (!$update) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $update->value = $join;
            $update->save();

            if (!$update) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting update failed']
                ]);
            }

            DB::commit();
            return response()->json(MyHelper::checkUpdate($update));

        } elseif ($post['key'] == 'delivery') {
            $updateMinValue = Setting::where('key', 'transaction_delivery_min_value')->first();
            $updateMaxDis = Setting::where('key', 'transaction_delivery_max_distance')->first();
            $updateDelPrice = Setting::where('key', 'transaction_delivery_price')->first();
            $updateDelPricing = Setting::where('key', 'transaction_delivery_pricing')->first();

            if (!$updateMinValue || !$updateMaxDis || !$updateDelPrice || !$updateDelPricing) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $updateMinValue->value = $post['min_value'];
            $updateMaxDis->value = $post['max_distance'];
            $updateDelPrice->value = $post['delivery_price'];
            $updateDelPricing->value = $post['delivery_pricing'];

            $updateMinValue->save();
            $updateMaxDis->save();
            $updateDelPrice->save();
            $updateDelPricing->save();

            if (!$updateMinValue || !$updateMaxDis || !$updateDelPrice || !$updateDelPricing) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting update failed']
                ]);
            }

            DB::commit();
            return response()->json(MyHelper::checkUpdate($updateMinValue));

        } elseif ($post['key'] == 'discount') {
            $dataResult = [];

            array_push($dataResult, '(');

            if (isset($post['item'])) {
                foreach ($post['item'] as $key => $item) {
                    if ($item == 'resultsubtotal') {
                        $dataItem = 'subtotal';
                    } elseif ($item == 'resultservice') {
                        $dataItem = 'service';
                    } elseif ($item == 'resultdiscount') {
                        $dataItem = 'discount';
                    } elseif ($item == 'resultshipping') {
                        $dataItem = 'shipping';
                    } elseif ($item == 'resulttax') {
                        $dataItem = 'tax';
                    } elseif ($item == 'resultkali') {
                        $dataItem = '*';
                    } elseif ($item == 'resultbagi') {
                        $dataItem = '/';
                    } elseif ($item == 'resulttambah') {
                        $dataItem = '+';
                    } elseif ($item == 'resultkurang') {
                        $dataItem = '-';
                    } elseif ($item == 'resultkbuka') {
                        $dataItem = '(';
                    } elseif ($item == 'resultktutup') {
                        $dataItem = ')';
                    }

                    array_push($dataResult, $dataItem);
                }
            } else {
                array_push($dataResult, '');
            }
            array_push($dataResult, ')');
            array_push($dataResult, '*');
            array_push($dataResult, 'value');

            $join = implode(' ', $dataResult);

            $update = Setting::where('key', 'transaction_discount_formula')->first();

            if (!$update) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $update->value = $join;
            $update->save();

            if (!$update) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting update failed']
                ]);
            }

            $checkPercent = Setting::where('key', 'discount_percent')->first();
            if (!$checkPercent) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $checkNominal = Setting::where('key', 'discount_nominal')->first();
            if (!$checkNominal) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $checkPercent->value = $post['percent'];
            $checkPercent->save();
            if (!$checkPercent) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }


            $checkNominal->value = $post['nominal'];
            $checkNominal->save();
            if (!$checkNominal) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            DB::commit();
            return response()->json(MyHelper::checkUpdate($update));

        } elseif ($post['key'] == 'tax') {
            // return $post;
            $dataResult = [];

            array_push($dataResult, '(');

            if (isset($post['item'])) {
                foreach ($post['item'] as $key => $item) {
                    if ($item == 'resultsubtotal') {
                        $dataItem = 'subtotal';
                    } elseif ($item == 'resultservice') {
                        $dataItem = 'service';
                    } elseif ($item == 'resultdiscount') {
                        $dataItem = 'discount';
                    } elseif ($item == 'resultshipping') {
                        $dataItem = 'shipping';
                    } elseif ($item == 'resulttax') {
                        $dataItem = 'tax';
                    } elseif ($item == 'resultkali') {
                        $dataItem = '*';
                    } elseif ($item == 'resultbagi') {
                        $dataItem = '/';
                    } elseif ($item == 'resulttambah') {
                        $dataItem = '+';
                    } elseif ($item == 'resultkurang') {
                        $dataItem = '-';
                    } elseif ($item == 'resultkbuka') {
                        $dataItem = '(';
                    } elseif ($item == 'resultktutup') {
                        $dataItem = ')';
                    }

                    array_push($dataResult, $dataItem);
                }
            } else {
                array_push($dataResult, '');
            }
            array_push($dataResult, ')');
            array_push($dataResult, '*');
            array_push($dataResult, 'value');

            $join = implode(' ', $dataResult);

            $update = Setting::where('key', 'transaction_tax_formula')->first();

            if (!$update) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $update->value = $join;
            $update->save();

            if (!$update) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting update failed']
                ]);
            }

            $updateTax = Setting::where('key', 'tax')->first();
            if (!$updateTax) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $updateTax->value = $post['value']/100;
            $updateTax->save();
            if (!$updateTax) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting update failed']
                ]);
            }

            DB::commit();
            return response()->json(MyHelper::checkUpdate($updateTax));

        } elseif ($post['key'] == 'point') {
            // return $post;
            $dataResult = [];

            array_push($dataResult, '(');

            if (isset($post['item'])) {
                foreach ($post['item'] as $key => $item) {
                    if ($item == 'resultsubtotal') {
                        $dataItem = 'subtotal';
                    } elseif ($item == 'resultservice') {
                        $dataItem = 'service';
                    } elseif ($item == 'resultdiscount') {
                        $dataItem = 'discount';
                    } elseif ($item == 'resultshipping') {
                        $dataItem = 'shipping';
                    } elseif ($item == 'resulttax') {
                        $dataItem = 'tax';
                    } elseif ($item == 'resultkali') {
                        $dataItem = '*';
                    } elseif ($item == 'resultbagi') {
                        $dataItem = '/';
                    } elseif ($item == 'resulttambah') {
                        $dataItem = '+';
                    } elseif ($item == 'resultkurang') {
                        $dataItem = '-';
                    } elseif ($item == 'resultkbuka') {
                        $dataItem = '(';
                    } elseif ($item == 'resultktutup') {
                        $dataItem = ')';
                    }

                    array_push($dataResult, $dataItem);
                }
            } else {
                array_push($dataResult, '');
            }
            array_push($dataResult, ')');
            array_push($dataResult, '*');
            array_push($dataResult, 'value');

            $join = implode(' ', $dataResult);

            $update = Setting::where('key', 'point_acquisition_formula')->first();

            if (!$update) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $update->value = $join;
            $update->save();

            if (!$update) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting update failed']
                ]);
            }

            $updatePoint = Setting::where('key', 'point_conversion_value')->first();
            if (!$updatePoint) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $updatePoint->value = $post['value'];
            $updatePoint->save();
            if (!$updatePoint) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting update failed']
                ]);
            }

            DB::commit();
            return response()->json(MyHelper::checkUpdate($updatePoint));

        } elseif ($post['key'] == 'cashback') {
            // return $post;
            $dataResult = [];

            array_push($dataResult, '(');

            if (isset($post['item'])) {
                foreach ($post['item'] as $key => $item) {
                    if ($item == 'resultsubtotal') {
                        $dataItem = 'subtotal';
                    } elseif ($item == 'resultservice') {
                        $dataItem = 'service';
                    } elseif ($item == 'resultdiscount') {
                        $dataItem = 'discount';
                    } elseif ($item == 'resultshipping') {
                        $dataItem = 'shipping';
                    } elseif ($item == 'resulttax') {
                        $dataItem = 'tax';
                    } elseif ($item == 'resultkali') {
                        $dataItem = '*';
                    } elseif ($item == 'resultbagi') {
                        $dataItem = '/';
                    } elseif ($item == 'resulttambah') {
                        $dataItem = '+';
                    } elseif ($item == 'resultkurang') {
                        $dataItem = '-';
                    } elseif ($item == 'resultkbuka') {
                        $dataItem = '(';
                    } elseif ($item == 'resultktutup') {
                        $dataItem = ')';
                    }

                    array_push($dataResult, $dataItem);
                }
            } else {
                array_push($dataResult, '');
            }
            array_push($dataResult, ')');
            array_push($dataResult, '*');
            array_push($dataResult, 'value');

            $join = implode(' ', $dataResult);

            $update = Setting::where('key', 'cashback_acquisition_formula')->first();

            if (!$update) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $update->value = $join;
            $update->save();

            if (!$update) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting update failed']
                ]);
            }

            $updateCashback = Setting::where('key', 'cashback_conversion_value')->first();
            if (!$updateCashback) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $updateCashback->value = $post['value']/100;
            $updateCashback->save();
            if (!$updateCashback) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting update failed']
                ]);
            }

            $updateCashbackMax = Setting::where('key', 'cashback_maximum')->first();
            if (!$updateCashbackMax) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $updateCashbackMax->value = $post['max'];
            $updateCashbackMax->save();
            if (!$updateCashbackMax) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting update failed']
                ]);
            }

            DB::commit();
            return response()->json(MyHelper::checkUpdate($updateCashback));

        } elseif ($post['key'] == 'outlet') {
            $update = Setting::where('key', 'default_outlet')->first();
            if (!$update) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting not found']
                ]);
            }

            $update->value = $post['value'];
            $update->save();

            if (!$update) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Data setting update failed']
                ]);
            }

            DB::commit();
            return response()->json(MyHelper::checkUpdate($update));
        }

    }

    public function internalCourier() {
        $setting = Setting::where('key', 'transaction_delivery_standard')->orWhere('key', 'transaction_delivery_min_value')->orWhere('key', 'transaction_delivery_max_distance')->orWhere('key', 'transaction_delivery_pricing')->orWhere('key', 'transaction_delivery_price')->get()->toArray();

        return response()->json(MyHelper::checkGet($setting));
    }

    public function manualPaymentList() {
        $list = ManualPayment::with('manual_payment_methods')->get()->toArray();

        return response()->json(MyHelper::checkGet($list));
    }

    public function manualPaymentCreate(ManualPaymentCreate $request) {
        $post = $request->json()->all();

        DB::beginTransaction();
        if (isset($post['manual_payment_logo'])) {
            $decoded = base64_decode($post['manual_payment_logo']);

            // cek extension
            $ext = MyHelper::checkExtensionImageBase64($decoded);

            // set picture name
            $pictName = mt_rand(0, 1000).''.time().''.$ext;

            // path
            $upload = $this->saveImage.$pictName;

            $img = Image::make($decoded);
            $img->save($upload);

            if ($img) {
                $data['manual_payment_logo'] = $upload;
            } else {
                DB::rollback();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['fail upload image']
                ]);
            }

            // $save = MyHelper::uploadPhotoStrict($post['manual_payment_logo'], $this->saveImage, 300, 300);

            // if (isset($save['status']) && $save['status'] == "success") {
            //     $data['manual_payment_logo'] = $save['path'];
            // }
            // else {
            //     DB::rollback();
            //     return response()->json([
            //         'status'   => 'fail',
            //         'messages' => ['fail upload image']
            //     ]);
            // }
        }

        if (isset($post['is_virtual_account'])) {
            $data['is_virtual_account'] = $post['is_virtual_account'];
        }

        if (isset($post['manual_payment_name'])) {
            $data['manual_payment_name'] = $post['manual_payment_name'];
        }

        if (isset($post['account_number'])) {
            $data['account_number'] = $post['account_number'];
        }

        if (isset($post['account_name'])) {
            $data['account_name'] = $post['account_name'];
        }

        $save = ManualPayment::create($data);

        if (!$save) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Create manual payment failed']
            ]);
        }

        DB::commit();
        return response()->json(MyHelper::checkCreate($save));
    }

    public function manualPaymentEdit(ManualPaymentEdit $request) {
        $id = $request->json('id');

        $list = ManualPayment::with('manual_payment_methods')->where('id_manual_payment', $id)->first();

        if (count($list['manual_payment_methods']) > 0) {
            $method = [];

            foreach ($list['manual_payment_methods'] as $value) {
                array_push($method, $value['payment_method_name']);
            }

            $list['method'] = implode(',', $method);
        }

        return response()->json(MyHelper::checkGet($list));
    }

    public function manualPaymentUpdate(ManualPaymentUpdate $request) {
        $post = $request->json()->all();

        DB::beginTransaction();
        if (isset($post['post']['manual_payment_logo'])) {
            $decoded = base64_decode($post['post']['manual_payment_logo']);

            // cek extension
            $ext = MyHelper::checkExtensionImageBase64($decoded);

            // set picture name
            $pictName = mt_rand(0, 1000).''.time().''.$ext;

            // path
            $upload = $this->saveImage.$pictName;

            $img = Image::make($decoded);
            $img->save($upload);

            if ($img) {
                $data['manual_payment_logo'] = $upload;
            } else {
                DB::rollback();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['fail upload image']
                ]);
            }
            // $save = MyHelper::uploadPhotoStrict($post['post']['manual_payment_logo'], $this->saveImage, 300, 300);

            // if (isset($save['status']) && $save['status'] == "success") {
            //     $data['manual_payment_logo'] = $save['path'];
            // }
            // else {
            //     DB::rollback();
            //     return response()->json([
            //         'status'   => 'fail',
            //         'messages' => ['fail upload image']
            //     ]);
            // }
        }

        if (isset($post['post']['is_virtual_account'])) {
            $data['is_virtual_account'] = $post['post']['is_virtual_account'];
        }

        if (isset($post['post']['manual_payment_name'])) {
            $data['manual_payment_name'] = $post['post']['manual_payment_name'];
        }

        if (isset($post['post']['account_number'])) {
            $data['account_number'] = $post['post']['account_number'];
        }

        if (isset($post['post']['account_name'])) {
            $data['account_name'] = $post['post']['account_name'];
        }

        $save = ManualPayment::where('id_manual_payment', $post['id'])->update($data);
        // return $save;
        if (!$save) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Update manual payment failed']
            ]);
        }

        // $old = explode(',', $post['post']['method_name_old']);
        // $new = explode(',', $post['post']['method_name_new']);
        // // return $old;
        // // return response()->json($old[0]);

        // foreach ($old as $key => $o) {
        //     if (!in_array($o, $new)) {
        //         $delete = ManualPaymentMethod::where('payment_method_name', $o)->delete();
        //         // return $delete;

        //         if (!$delete) {
        //             DB::rollback();
        //             return response()->json([
        //                 'status'    => 'fail',
        //                 'messages'  => ['Update manual payment failed1']
        //             ]);
        //         }
        //     }
        // }

        // foreach ($new as $row => $n) {
        //     if (!in_array($n, $old)) {
        //         $data = [
        //             'id_manual_payment' => $post['id'],
        //             'payment_method_name'   => $n
        //         ];

        //         $insert = ManualPaymentMethod::create($data);

        //         if (!$insert) {
        //             DB::rollback();
        //             return response()->json([
        //                 'status'    => 'fail',
        //                 'messages'  => ['Update manual payment failed']
        //             ]);
        //         }
        //     }
        // }

        DB::commit();
        return response()->json(MyHelper::checkCreate($save));
    }

    public function manualPaymentDetail(ManualPaymentDetail $request) {
        $id = $request->json('id');

        $detail = ManualPayment::with('manual_payment_methods.manual_payment_tutorials')->where('id_manual_payment', $id)->first();

        if (!empty($detail['manual_payment_methods'])) {
            $detail['old_id'] = implode(',', array_column($detail['manual_payment_methods']->toArray(), 'id_manual_payment_method'));
        }
        // return $detail;
        return response()->json(MyHelper::checkGet($detail));
    }

    public function manualPaymentDelete(ManualPaymentDelete $request) {
        $id = $request->json('id');
        $check = ManualPayment::with('manual_payment_methods.transaction_payment_manuals.transaction')->where('id_manual_payment', $id)->first();
        if (empty($check)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Delete manual payment failed']
            ]);
        }

        foreach ($check['manual_payment_methods'] as $key => $value) {
            if (count($value['transaction_payment_manuals']) > 0) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['This payment is already in use']
                ]);
            }
        }

        $check->delete();

        return response()->json(MyHelper::checkDelete($check));
    }

    public function manualPaymentMethod(MethodSave $request) {
        $post = $request->json()->all();
        // return $post;
        $check = explode(',', $post['old_id']);

        DB::beginTransaction();
        if (!isset($post['method_name'])) {
            $delete = ManualPaymentMethod::where('id_manual_payment', $post['id'])->delete();

            if (!$delete) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Failed']
                ]);
            }
        } else {
            foreach ($post['method_name'] as $key => $value) {
                $data = [
                    'id_manual_payment'   => $post['id'],
                    'payment_method_name' => $value,
                ];

                if (in_array($post['id_method'][$key], $check)) {
                    $method = ManualPaymentMethod::with('manual_payment_tutorials')->where('id_manual_payment_method', $post['id_method'][$key])->first();
                    $insert = $method->update($data);

                    if (count($method['manual_payment_tutorials']) > 0) {
                        $delete = ManualPaymentTutorial::where('id_manual_payment_method', $post['id_method'][$key])->delete();

                        if (!$delete) {
                            DB::rollback();
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => ['Failed']
                            ]);
                        }
                    }

                    $id = $method['id_manual_payment_method'];
                } else {
                    $insert = ManualPaymentMethod::create($data);
                    $id = $insert['id_manual_payment_method'];
                }

                if (!$insert) {
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Failed']
                    ]);
                }

                if (isset($post['tutorial_'.$key.''])) {
                    foreach ($post['tutorial_'.$key.''] as $row => $tutorial) {
                        $dataTutor = [
                            'id_manual_payment_method' => $id,
                            'payment_tutorial'         => $tutorial,
                            'payment_tutorial_no'      => $row+1
                        ];

                        $insert = ManualPaymentTutorial::create($dataTutor);

                        if (!$insert) {
                            DB::rollback();
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => ['Insert Failed']
                            ]);
                        }
                    }
                }
            }

            if (isset($post['old_id'])) {
                foreach ($check as $tes => $value) {
                    if (!in_array($value, $post['id_method'])) {
                        $delete = ManualPaymentMethod::where('id_manual_payment_method', $value)->delete();

                        if (!$delete) {
                            DB::rollback();
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => ['Insert Failed']
                            ]);
                        }
                    }
                }
            }
        }

        DB::commit();

        return response()->json([
            'status'    => 'success',
            'messages'  => ['Success']
        ]);
    }

    public function manualPaymentMethodDelete(MethodDelete $request) {
        $id = $request->json('id');

        $check = ManualPaymentTutorial::where('id_manual_payment_tutorial', $id)->first();

        if (empty($check)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Tutorial Not Found']
            ]);
        }

        $check->delete();

        return response()->json(MyHelper::checkDelete($check));

    }

    public function pointUser(Request $request) {
        $point = LogPoint::with('user')->paginate(10);
        return response()->json(MyHelper::checkGet($point));
    }

    public function pointUserFilter(Request $request) {
        $post = $request->json()->all();

        $conditions = [];
        $rule = '';
        $search = '';
        // return $post;
        $start = date('Y-m-d', strtotime($post['date_start']));
        $end = date('Y-m-d', strtotime($post['date_end']));
        $query = LogPoint::select('log_points.*',
            'users.*')
            ->leftJoin('users','log_points.id_user','=','users.id')
            ->where('log_points.created_at', '>=', $start)
            ->where('log_points.created_at', '<=', $end)
            ->orderBy('log_points.id_log_point', 'DESC')
            ->groupBy('log_points.id_log_point');
        // ->orderBy('transactions.id_transaction', 'DESC');

        // return response()->json($query->get());
        if (isset($post['conditions'])) {
            foreach ($post['conditions'] as $key => $con) {
                if(isset($con['subject'])){
                    $var = $con['subject'];
                    if ($post['rule'] == 'and') {
                        if ($con['operator'] == 'like') {
                            $query = $query->where($var, $con['operator'], '%'.$con['parameter'].'%');
                        } else {
                            $query = $query->where($var, $con['operator'], $con['parameter']);
                        }
                    } else {
                        if ($con['operator'] == 'like') {
                            $query = $query->orWhere($var, $con['operator'], '%'.$con['parameter'].'%');
                        } else {
                            $query = $query->orWhere($var, $con['operator'], $con['parameter']);
                        }
                    }
                }

            }

            $conditions = $post['conditions'];
            $rule       = $post['rule'];
            $search     = '1';
        }

        $akhir = $query->paginate(10);

        if ($akhir) {
            $result = [
                'status'     => 'success',
                'data'       => $akhir,
                'count'      => count($akhir),
                'conditions' => $conditions,
                'rule'       => $rule,
                'search'     => $search
            ];
        } else {
            $result = [
                'status'     => 'fail',
                'data'       => $akhir,
                'count'      => count($akhir),
                'conditions' => $conditions,
                'rule'       => $rule,
                'search'     => $search
            ];
        }

        return response()->json($result);
    }

    public function balanceUserFilter(Request $request) {
        $post = $request->json()->all();

        $conditions = [];
        $rule = '';
        $search = '';
        // return $post;
        $start = date('Y-m-d', strtotime($post['date_start']));
        $end = date('Y-m-d', strtotime($post['date_end']));
        $query = LogBalance::select(
            'log_balances.*',
            'users.name',
            'users.phone'
        )
            ->leftJoin('users','log_balances.id_user','=','users.id')
            ->where('log_balances.created_at', '>=', $start)
            ->where('log_balances.created_at', '<=', $end)
            ->orderBy('log_balances.id_log_balance', 'DESC')
            ->groupBy('log_balances.id_log_balance');
        // ->orderBy('transactions.id_transaction', 'DESC');

        // return response()->json($query->get());
        if (isset($post['conditions'])) {
            foreach ($post['conditions'] as $key => $con) {
                if(isset($con['subject'])){
                    if ($con['subject'] == 'balance') {
                        $var = 'log_balances.balance';
                    } else {
                        $var = $con['subject'];
                    }

                    if ($post['rule'] == 'and') {
                        if ($con['operator'] == 'like') {
                            $query = $query->where($var, $con['operator'], '%'.$con['parameter'].'%');
                        } else {
                            $query = $query->where($var, $con['operator'], $con['parameter']);
                        }
                    } else {
                        if ($con['operator'] == 'like') {
                            $query = $query->orWhere($var, $con['operator'], '%'.$con['parameter'].'%');
                        } else {
                            $query = $query->orWhere($var, $con['operator'], $con['parameter']);
                        }
                    }
                }

            }

            $conditions = $post['conditions'];
            $rule       = $post['rule'];
            $search     = '1';
        }

        $akhir = $query->paginate(10)->toArray();

        if ($akhir) {

            $akhir['data'] = $query->paginate(10)
                ->each(function($q){
                    $q->setAppends([
                        'get_reference'
                    ]);
                })
                ->toArray();

            $result = [
                'status'     => 'success',
                'data'       => $akhir,
                'count'      => count($akhir),
                'conditions' => $conditions,
                'rule'       => $rule,
                'search'     => $search
            ];
        } else {
            $result = [
                'status'     => 'fail',
                'data'       => $akhir,
                'count'      => count($akhir),
                'conditions' => $conditions,
                'rule'       => $rule,
                'search'     => $search
            ];
        }

        return response()->json($result);
    }

    public function balanceUser(Request $request) {

        $balance = LogBalance::with('user')
            ->orderBy('id_log_balance', 'desc')
            ->paginate(10)
            ->toArray();

        if ($balance) {
            $balance['data'] = LogBalance::with('user')
                ->orderBy('id_log_balance', 'desc')
                ->paginate(10)
                ->each(function($q){
                    $q->setAppends([
                        'get_reference'
                    ]);
                })
                ->toArray();
        }

        return response()->json(MyHelper::checkGet($balance));
    }

    public function manualPaymentListUnpay(Request $request) {
        $list = TransactionPaymentManual::with('transaction', 'manual_payment_method')->get()->toArray();
        return response()->json(MyHelper::checkGet($list));
    }

    public function transactionList($key){
        $start = date('Y-m-01 00:00:00');
        $end = date('Y-m-d 23:59:59');
        $delivery = false;
        if(strtolower($key) == 'delivery'){
            $key = 'pickup order';
            $delivery = true;
        }
        $list = Transaction::leftJoin('outlets','outlets.id_outlet','=','transactions.id_outlet')
            ->join('transaction_pickups','transaction_pickups.id_transaction','=','transactions.id_transaction')
            ->leftJoin('transaction_pickup_go_sends','transaction_pickups.id_transaction_pickup','=','transaction_pickup_go_sends.id_transaction_pickup')
            ->orderBy('transactions.id_transaction', 'DESC')->with('user', 'productTransaction.product.product_category')
            ->where('transactions.transaction_date', '>=', $start)->where('transactions.transaction_date', '<=', $end);
        if (strtolower($key) !== 'all') {
            $list->where('trasaction_type', ucwords($key));
            if($delivery){
                $list->where('pickup_by','<>','Customer');
            }else{
                $list->where('pickup_by','Customer');
            }
        }
        $list = $list->paginate(10);

        return response()->json(MyHelper::checkGet($list));
    }

    public function transactionFilter(TransactionFilter $request) {
        $post = $request->json()->all();
        // return $post;
        $conditions = [];
        $rule = '';
        $search = '';
        // return $post;
        $start = date('Y-m-d', strtotime($post['date_start']));
        $end = date('Y-m-d', strtotime($post['date_end']));
        $delivery = false;
        if(strtolower($post['key']) == 'delivery'){
            $post['key'] = 'pickup order';
            $delivery = true;
        }
        $query = Transaction::join('transaction_pickups','transaction_pickups.id_transaction','=','transactions.id_transaction')->select('transactions.*',
            'transaction_pickups.*',
            'transaction_pickup_go_sends.*',
            'transaction_products.*',
            'users.*',
            'products.*',
            'product_categories.*',
            'outlets.outlet_code', 'outlets.outlet_name')
            ->leftJoin('outlets','outlets.id_outlet','=','transactions.id_outlet')
            ->leftJoin('transaction_pickup_go_sends','transaction_pickups.id_transaction_pickup','=','transaction_pickup_go_sends.id_transaction_pickup')
            ->leftJoin('transaction_products','transactions.id_transaction','=','transaction_products.id_transaction')
            ->leftJoin('users','transactions.id_user','=','users.id')
            ->leftJoin('products','products.id_product','=','transaction_products.id_product')
            ->leftJoin('product_categories','products.id_product_category','=','product_categories.id_product_category')
            ->whereDate('transactions.transaction_date', '>=', $start)
            ->whereDate('transactions.transaction_date', '<=', $end)
            ->with('user')
            ->orderBy('transactions.id_transaction', 'DESC')
            ->groupBy('transactions.id_transaction');
        // ->orderBy('transactions.id_transaction', 'DESC');
        if (strtolower($post['key']) !== 'all') {
            $query->where('trasaction_type', $post['key']);
            if($delivery){
                $query->where('pickup_by','<>','Customer');
            }else{
                $query->where('pickup_by','Customer');
            }
        }
        // return response()->json($query->get());
        if (isset($post['conditions'])) {
            foreach ($post['conditions'] as $key => $con) {
                if (isset($con['subject'])) {
                    if ($con['subject'] == 'receipt') {
                        $var = 'transactions.transaction_receipt_number';
                    } elseif ($con['subject'] == 'name' || $con['subject'] == 'phone' || $con['subject'] == 'email') {
                        $var = 'users.'.$con['subject'];
                    } elseif ($con['subject'] == 'product_name' || $con['subject'] == 'product_code') {
                        $var = 'products.'.$con['subject'];
                    } elseif ($con['subject'] == 'product_category') {
                        $var = 'product_categories.product_category_name';
                    } elseif ($con['subject'] == 'order_id') {
                        $var = 'transaction_pickups.order_id';
                    }

                    if (in_array($con['subject'], ['outlet_code', 'outlet_name'])) {
                        $var = 'outlets.'.$con['subject'];
                        if ($post['rule'] == 'and') {
                            if ($con['operator'] == 'like') {
                                $query = $query->where($var, 'like', '%'.$con['parameter'].'%');
                            } else {
                                $query = $query->where($var, '=', $con['parameter']);
                            }
                        } else {
                            if ($con['operator'] == 'like') {
                                $query = $query->orWhere($var, 'like', '%'.$con['parameter'].'%');
                            } else {
                                $query = $query->orWhere($var, '=', $con['parameter']);
                            }
                        }
                    }
                    if (in_array($con['subject'], ['receipt', 'name', 'phone', 'email', 'product_name', 'product_code', 'product_category', 'order_id'])) {
                        if ($post['rule'] == 'and') {
                            if ($con['operator'] == 'like') {
                                $query = $query->where($var, 'like', '%'.$con['parameter'].'%');
                            } else {
                                $query = $query->where($var, '=', $con['parameter']);
                            }
                        } else {
                            if ($con['operator'] == 'like') {
                                $query = $query->orWhere($var, 'like', '%'.$con['parameter'].'%');
                            } else {
                                $query = $query->orWhere($var, '=', $con['parameter']);
                            }
                        }
                    }

                    if ($con['subject'] == 'product_weight' || $con['subject'] == 'product_price') {
                        $var = 'products.'.$con['subject'];
                        if ($post['rule'] == 'and') {
                            $query = $query->where($var, $con['operator'], $con['parameter']);
                        } else {
                            $query = $query->orWhere($var, $con['operator'], $con['parameter']);
                        }
                    }

                    if ($con['subject'] == 'grand_total' || $con['subject'] == 'product_tax') {
                        if ($con['subject'] == 'grand_total') {
                            $var = 'transactions.transaction_grandtotal';
                        } else {
                            $var = 'transactions.transaction_tax';
                        }

                        if ($post['rule'] == 'and') {
                            $query = $query->where($var, $con['operator'], $con['parameter']);
                        } else {
                            $query = $query->orWhere($var, $con['operator'], $con['parameter']);
                        }
                    }

                    if ($con['subject'] == 'transaction_status') {
                        if ($post['rule'] == 'and') {
                            if($con['operator'] == 'pending'){
                                $query = $query->whereNull('transaction_pickups.receive_at');
                            }elseif($con['operator'] == 'taken_by_driver'){
                                $query = $query->whereNotNull('transaction_pickups.taken_at')
                                    ->whereNotIn('transaction_pickups.pickup_by', ['Customer']);
                            }elseif ($con['operator'] == 'taken_by_customer'){
                                $query = $query->whereNotNull('transaction_pickups.taken_at')
                                    ->where('transaction_pickups.pickup_by', 'Customer');
                            }elseif ($con['operator'] == 'taken_by_system'){
                                $query = $query->whereNotNull('transaction_pickups.ready_at')
                                    ->whereNotNull('transaction_pickups.taken_by_system_at');
                            }elseif($con['operator'] == 'receive_at'){
                                $query = $query->whereNotNull('transaction_pickups.receive_at')
                                    ->whereNull('transaction_pickups.ready_at');
                            }elseif($con['operator'] == 'ready_at'){
                                $query = $query->whereNotNull('transaction_pickups.ready_at')
                                    ->whereNull('transaction_pickups.taken_at');
                            }else{
                                $query = $query->whereNotNull('transaction_pickups.'.$con['operator']);
                            }
                        } else {
                            if($con['operator'] == 'pending'){
                                $query = $query->orWhereNotNull('transaction_pickups.receive_at');
                            }elseif($con['operator'] == 'taken_by_driver'){
                                $query = $query->orWhere(function ($q){
                                    $q->whereNotNull('transaction_pickups.taken_at')
                                        ->whereNotIn('transaction_pickups.pickup_by', ['Customer']);
                                });
                            }elseif ($con['operator'] == 'taken_by_customer'){
                                $query = $query->orWhere(function ($q){
                                    $q->whereNotNull('transaction_pickups.taken_at')
                                        ->where('transaction_pickups.pickup_by', 'Customer');
                                });
                            }elseif ($con['operator'] == 'taken_by_system'){
                                $query = $query->orWhere(function ($q){
                                    $q->whereNotNull('transaction_pickups.ready_at')
                                        ->whereNotNull('transaction_pickups.taken_by_system_at');
                                });
                            }elseif($con['operator'] == 'receive_at'){
                                $query = $query->orWhere(function ($q){
                                    $q->whereNotNull('transaction_pickups.receive_at')
                                        ->whereNull('transaction_pickups.ready_at');
                                });
                            }elseif($con['operator'] == 'ready_at'){
                                $query = $query->orWhere(function ($q) {
                                    $q->whereNotNull('transaction_pickups.ready_at')
                                        ->whereNull('transaction_pickups.taken_at');
                                });
                            }else{
                                $query = $query->orWhereNotNull('transaction_pickups.'.$con['operator']);
                            }
                        }
                    }

                    if (in_array($con['subject'], ['status', 'courier', 'id_outlet', 'id_product', 'pickup_by'])) {
                        switch ($con['subject']) {
                            case 'status':
                                $var = 'transactions.transaction_payment_status';
                                break;

                            case 'courier':
                                $var = 'transactions.transaction_courier';
                                break;

                            case 'id_product':
                                $var = 'products.id_product';
                                break;

                            case 'id_outlet':
                                $var = 'outlets.id_outlet';
                                break;

                            case 'pickup_by':
                                $var = 'transaction_pickups.pickup_by';
                                break;

                            default:
                                continue 2;
                        }

                        if ($post['rule'] == 'and') {
                            $query = $query->where($var, '=', $con['operator']);
                        } else {
                            $query = $query->orWhere($var, '=', $con['operator']);
                        }
                    }
                }

            }

            $conditions = $post['conditions'];
            $rule       = $post['rule'];
            $search     = '1';
        }

        $akhir = $query->paginate(10);
        // return $akhir;
        if ($akhir) {
            $result = [
                'status'     => 'success',
                'data'       => $akhir,
                'count'      => count($akhir),
                'conditions' => $conditions,
                'rule'       => $rule,
                'search'     => $search
            ];
        } else {
            $result = [
                'status'     => 'fail',
                'data'       => $akhir,
                'count'      => count($akhir),
                'conditions' => $conditions,
                'rule'       => $rule,
                'search'     => $search
            ];
        }

        return response()->json($result);
    }

    public function exportTransaction($filter, $statusReturn = null, $filter_type='admin') {
    // public function exportTransaction(Request $request) {
    //     $filter = [
    //         "rule" => "and",
    //         "operator" => "and",
    //         "date_start" => "2021-11-01",
    //         "date_end" => "2021-11-30",
    //         "detail" => 1,
    //         "key" => "all",
    //         "conditions" => [
    //             [
    //                 "subject" => "id_outlet",
    //                 "operator" => 12,
    //                 "parameter" => null,
    //             ],
    //             [
    //                 "subject" => "status",
    //                 "operator" => "Completed",
    //                 "parameter" => null,
    //             ],
    //             [
    //                 "subject" => "all_transaction"
    //             ]
    //         ]
    //     ];
    //     $statusReturn = 1;
    //     $filter_type = "franchise";
        $post = $filter;

        $delivery = false;
        if(strtolower($post['key']) == 'delivery'){
            $post['key'] = 'pickup order';
            $delivery = true;
        }

        $query = Transaction::join('transaction_outlet_services','transaction_outlet_services.id_transaction','=','transactions.id_transaction')
            ->select('transaction_outlet_services.*','transactions.*','transaction_products.*','users.*','outlets.outlet_code', 'outlets.outlet_name','product_name','product_code')
            ->leftJoin('outlets','outlets.id_outlet','=','transactions.id_outlet')
            ->leftJoin('users','transactions.id_user','=','users.id')
            ->leftJoin('transaction_products','transaction_products.id_transaction','=','transactions.id_transaction')
            ->leftJoin('products','products.id_product','=','transaction_products.id_product')
            ->orderBy('transactions.transaction_date', 'asc');

        // $query = $query->leftJoin('transaction_payment_midtrans', 'transactions.id_transaction', '=', 'transaction_payment_midtrans.id_transaction')
        //     ->leftJoin('transaction_payment_ipay88s', 'transactions.id_transaction', '=', 'transaction_payment_ipay88s.id_transaction')
        //     ->leftJoin('transaction_payment_shopee_pays', 'transactions.id_transaction', '=', 'transaction_payment_shopee_pays.id_transaction');
        
        $settingMDRAll = [];
        // if(isset($post['detail']) && $post['detail'] == 1){
        //     $settingMDRAll = MDR::get()->toArray();
        //     $query->leftJoin('disburse_outlet_transactions', 'disburse_outlet_transactions.id_transaction', 'transactions.id_transaction')
        //         ->join('transaction_products','transaction_products.id_transaction','=','transactions.id_transaction')
        //         ->leftJoin('transaction_balances','transaction_balances.id_transaction','=','transactions.id_transaction')
        //         ->join('products', 'products.id_product', 'transaction_products.id_product')
        //         ->join('brands', 'brands.id_brand', 'transaction_products.id_brand')
        //         ->leftJoin('product_categories','products.id_product_category','=','product_categories.id_product_category')
        //         ->join('cities', 'cities.id_city', 'outlets.id_city')
                    $query->leftJoin('cities', 'outlets.id_city','=', 'cities.id_city')
                    ->leftJoin('provinces', 'cities.id_province', 'provinces.id_province')
        //         ->leftJoin('transaction_bundling_products','transaction_products.id_transaction_bundling_product','=','transaction_bundling_products.id_transaction_bundling_product')
        //         ->leftJoin('bundling','bundling.id_bundling','=','transaction_bundling_products.id_bundling')
        //         ->leftJoin('rule_promo_payment_gateway','rule_promo_payment_gateway.id_rule_promo_payment_gateway','=','disburse_outlet_transactions.id_rule_promo_payment_gateway')
        //         ->leftJoin('promo_payment_gateway_transactions as promo_pg', 'promo_pg.id_transaction', 'transactions.id_transaction')
        //         ->with(['transaction_payment_subscription', 'vouchers', 'promo_campaign', 'point_refund', 'point_use', 'subscription_user_voucher.subscription_user.subscription'])
        //         ->orderBy('transaction_products.id_transaction_bundling_product', 'asc')
        //         ->addSelect('promo_pg.total_received_cashback',  'rule_promo_payment_gateway.name as promo_payment_gateway_name',
        //             'transaction_bundling_products.transaction_bundling_product_base_price', 'transaction_bundling_products.transaction_bundling_product_qty', 'transaction_bundling_products.transaction_bundling_product_total_discount', 'transaction_bundling_products.transaction_bundling_product_subtotal', 'bundling.bundling_name', 'disburse_outlet_transactions.bundling_product_fee_central', 'transaction_products.*', 'products.product_code', 'products.product_name', 'product_categories.product_category_name',
        //             'brands.name_brand', 'cities.city_name', 'c.city_name as user_city', 'provinces.province_name',
        //             'disburse_outlet_transactions.fee_item', 'disburse_outlet_transactions.payment_charge', 'disburse_outlet_transactions.discount', 'disburse_outlet_transactions.subscription',
        //             'disburse_outlet_transactions.point_use_expense',
        //             'disburse_outlet_transactions.fee_promo_payment_gateway_outlet', 'disburse_outlet_transactions.fee_promo_payment_gateway_central',
        //             'disburse_outlet_transactions.income_outlet', 'disburse_outlet_transactions.discount_central', 'disburse_outlet_transactions.subscription_central');
                    ->addSelect('cities.city_name', 'cities.city_name as outlet_city','provinces.province_name');
        // }
       
        if(isset($post['date_start']) && !empty($post['date_start'])
            && isset($post['date_end']) && !empty($post['date_end'])){
            $start = date('Y-m-d', strtotime($post['date_start']));
            $end = date('Y-m-d', strtotime($post['date_end']));
        }else{
            $start = date('Y-m-01 00:00:00');
            $end = date('Y-m-d 23:59:59');
        }

        $query = $query->whereDate('transactions.transaction_date', '>=', $start)
            ->whereDate('transactions.transaction_date', '<=', $end);

        if (strtolower($post['key']) !== 'all') {
            $query->where('trasaction_type', $post['key']);
            if($delivery){
                $query->where('pickup_by','<>','Customer');
            }else{
                $query->where('pickup_by','Customer');
            }
        }
        
        if($filter_type == 'admin'){
            $query = $this->filterExportTransactionForAdmin($query,$post);
        }else{
            $query = app('Modules\Franchise\Http\Controllers\ApiTransactionFranchiseController')->filterTransaction($query,$post);
        }
        
        if($statusReturn == 1){
            $columnsVariant = '';
            $addAdditionalColumnVariant = '';
            // $getVariant = ProductVariant::whereNull('id_parent')->get()->toArray();
            // $getAllVariant = ProductVariant::select('id_product_variant', 'id_parent')->get()->toArray();
            // foreach ($getVariant as $v){
            //     $columnsVariant .= '<td style="background-color: #dcdcdc;" width="10">'.$v['product_variant_name'].'</td>';
            //     $addAdditionalColumnVariant .= '<td></td>';
            // }
            if($filter_type == 'admin') {
                $query->whereNull('reject_at');
            }

            $dataTrxDetail = '';
            $cek = '';
            $get = $query->get()->toArray();
            $count = count($get);
            $tmpBundling = '';
            $htmlBundling = '';
            foreach ($get as $key=>$val) {
                $payment = '';
                // if(!empty($val['payment_type'])){
                //     $payment = $val['payment_type'];
                // }elseif(!empty($val['payment_method'])){
                //     $payment = $val['payment_method'];
                // }elseif(!empty($val['id_transaction_payment_shopee_pay'])){
                //     $payment = 'Shopeepay';
                // }

                $variant = [];
                $productCode = $val['product_code'];
                if(!empty($val['id_product_variant_group'])){
                    $getProductVariantGroup = ProductVariantGroup::where('id_product_variant_group', $val['id_product_variant_group'])->first();
                    $productCode = $getProductVariantGroup['product_variant_group_code']??'';
                }
                
                $modifierGroup = TransactionProductModifier::where('id_transaction_product', $val['id_transaction_product'])
                    ->whereNotNull('transaction_product_modifiers.id_product_modifier_group')
                    ->select('text', 'transaction_product_modifier_price')->get()->toArray();
                $modifierGroupText = array_column($modifierGroup, 'text');
                $modifierGroupPrice = array_sum(array_column($modifierGroup, 'transaction_product_modifier_price'));
                
                if(isset($post['detail']) && $post['detail'] == 1){

                    $mod = TransactionProductModifier::join('product_modifiers', 'product_modifiers.id_product_modifier', 'transaction_product_modifiers.id_product_modifier')
                        ->where('transaction_product_modifiers.id_transaction_product', $val['id_transaction_product'])
                        ->whereNull('transaction_product_modifiers.id_product_modifier_group')
                        ->select('product_modifiers.text', 'transaction_product_modifiers.transaction_product_modifier_price')->get()->toArray();

                    $addAdditionalColumn = '';
                    $promoName = '';
                    $promoType = '';
                    $promoCode = '';

                    $promoName2 = '';
                    $promoType2 = '';
                    $promoCode2 = '';
                    // if(count($val['vouchers']) > 0){
                    //     $getDeal = Deal::where('id_deals', $val['vouchers'][0]['id_deals'])->first();
                    //     if($getDeal['promo_type'] == 'Discount bill' || $getDeal['promo_type'] == 'Discount delivery'){
                    //         $promoName2 = $getDeal['deals_title'];
                    //         $promoType2 = 'Deals';
                    //         $promoCode2 = $val['vouchers'][0]['voucher_code'];
                    //     }else{
                    //         $promoName = $getDeal['deals_title'];
                    //         $promoType = 'Deals';
                    //         $promoCode = $val['vouchers'][0]['voucher_code'];
                    //     }

                    // }elseif (!empty($val['promo_campaign'])){
                    //     if($val['promo_campaign']['promo_type'] == 'Discount bill' || $val['promo_campaign']['promo_type'] == 'Discount delivery'){
                    //         $promoName2 = $val['promo_campaign']['promo_title'];
                    //         $promoType2 = 'Promo Campaign';
                    //         $promoCode2 = $val['promo_campaign']['promo_code'];
                    //     }else{
                    //         $promoName = $val['promo_campaign']['promo_title'];
                    //         $promoType = 'Promo Campaign';
                    //         $promoCode = $val['promo_campaign']['promo_code'];
                    //     }
                    // }elseif(isset($val['subscription_user_voucher']['subscription_user']['subscription']['subscription_title'])){
                    //     $promoName2 = htmlspecialchars($val['subscription_user_voucher']['subscription_user']['subscription']['subscription_title']);
                    //     $promoType2 = 'Subscription';
                    // }

                    // $promoName = htmlspecialchars($promoName);
                    $status = $val['transaction_payment_status'];
                    if(!is_null($val['reject_at'])){
                        $status = 'Reject';
                    }

                    $poinUse = '';
                    if(isset($val['point_use']) && !empty($val['point_use'])){
                        $poinUse = $val['point_use']['balance'];
                    }

                    $pointRefund = '';
                    if(isset($val['point_refund']) && !empty($val['point_refund'])){
                        $pointRefund = $val['point_refund']['balance'];
                    }

                    $paymentRefund = '';
                    // if($val['reject_type'] == 'payment'){
                    //     $paymentRefund = $val['amount']??$val['gross_amount'];
                    // }

                    $paymentCharge = 0;
                    // if((int)$val['point_use_expense'] > 0){
                    //     $paymentCharge = $val['point_use_expense'];
                    // }

                    // if((int)$val['payment_charge'] > 0){
                    //     $paymentCharge = $val['payment_charge'];
                    // }

                    $html = '';
                    $sameData = '';
                    $sameData .= '<td>'.$val['outlet_code'].'</td>';
                    $sameData .= '<td>'.htmlspecialchars($val['outlet_name']).'</td>';
                    $sameData .= '<td>'.$val['province_name'].'</td>';
                    $sameData .= '<td>'.$val['city_name'].'</td>';
                    $sameData .= '<td>'.$val['transaction_receipt_number'].'</td>';
                    $sameData .= '<td>'.$status.'</td>';
                    $sameData .= '<td>'.date('d M Y', strtotime($val['transaction_date'])).'</td>';
                    $sameData .= '<td>'.date('H:i:s', strtotime($val['transaction_date'])).'</td>';

                    //for check additional column
                    if(isset($post['show_product_code']) && $post['show_product_code'] == 1){
                        $addAdditionalColumn = "<td></td>";
                    }

                    if(!empty($val['id_transaction_bundling_product'])){
                        $totalModPrice = 0;
                        for($j=0;$j<$val['transaction_product_bundling_qty'];$j++){
                            $priceMod = 0;
                            $textMod = '';
                            if(!empty($mod)){
                                $priceMod = $mod[0]['transaction_product_modifier_price'];
                                $textMod = $mod[0]['text'];
                            }
                            $htmlBundling .= '<tr>';
                            $htmlBundling .= $sameData;
                            // $htmlBundling .= '<td>'.$val['name_brand'].'</td>';
                            $htmlBundling .= '<td>'.$val['product_category_name'].'</td>';
                            if(isset($post['show_product_code']) && $post['show_product_code'] == 1){
                                $htmlBundling .= '<td>'.$productCode.'</td>';
                            }
                            $htmlBundling .= '<td>'.$val['product_name'].'</td>';
                            // $getTransactionVariant = TransactionProductVariant::join('product_variants as pv', 'pv.id_product_variant', 'transaction_product_variants.id_product_variant')
                            //     ->where('id_transaction_product', $val['id_transaction_product'])->select('pv.*')->get()->toArray();
                            // foreach ($getTransactionVariant as $k=>$gtV){
                            //     $getTransactionVariant[$k]['main_parent'] = $this->getParentVariant($getAllVariant, $gtV['id_product_variant']);
                            // }
                            // foreach ($getVariant as $v){
                            //     $search = array_search($v['id_product_variant'], array_column($getTransactionVariant, 'main_parent'));
                            //     if($search !== false){
                            //         $htmlBundling .= '<td>'.$getTransactionVariant[$search]['product_variant_name'].'</td>';
                            //     }else{
                            //         $htmlBundling .= '<td></td>';
                            //     }
                            // }
                            $totalModPrice = $totalModPrice + $priceMod;
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= '<td>'.implode(",",$modifierGroupText).'</td>';
                            $htmlBundling .= '<td>'.$textMod.'</td>';
                            $htmlBundling .= '<td>0</td>';
                            $htmlBundling .= '<td>'.$priceMod.'</td>';
                            $htmlBundling .= '<td>'.htmlspecialchars($val['transaction_product_note']).'</td>';
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= '<td>'.$priceMod.'</td>';
                            $htmlBundling .= '<td>0</td>';
                            $htmlBundling .= '<td>'.($priceMod).'</td>';
                            $htmlBundling .= '<td></td><td></td><td></td><td></td>';
                            if(isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                $htmlBundling .= '<td></td><td></td><td></td><td></td>';
                            }
                            $htmlBundling .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                            $htmlBundling .= '</tr>';

                            $totalMod = count($mod);
                            if($totalMod > 1){
                                for($i=1;$i<$totalMod;$i++){
                                    $totalModPrice = $totalModPrice + $mod[$i]['transaction_product_modifier_price']??0;
                                    $htmlBundling .= '<tr>';
                                    $htmlBundling .= $sameData;
                                    $htmlBundling .= '<td></td>';
                                    $htmlBundling .= '<td></td>';
                                    $htmlBundling .= $addAdditionalColumn;
                                    $htmlBundling .= '<td></td>';
                                    $htmlBundling .= $addAdditionalColumnVariant;
                                    $htmlBundling .= '<td></td>';
                                    $htmlBundling .= '<td></td>';
                                    $htmlBundling .= '<td>'.$mod[$i]['text']??''.'</td>';
                                    $htmlBundling .= '<td></td>';
                                    $htmlBundling .= '<td>'.$mod[$i]['transaction_product_modifier_price']??(int)'0'.'</td>';
                                    $htmlBundling .= '<td></td>';
                                    $htmlBundling .= '<td></td>';
                                    $htmlBundling .= '<td></td>';
                                    $htmlBundling .= '<td>'.$mod[$i]['transaction_product_modifier_price'].'</td>';
                                    $htmlBundling .= '<td>0</td>';
                                    $htmlBundling .= '<td>'.$mod[$i]['transaction_product_modifier_price'].'</td>';
                                    $htmlBundling .= '<td></td><td></td><td></td><td></td>';
                                    if(isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                        $htmlBundling .= '<td></td><td></td><td></td><td></td>';
                                    }
                                    $htmlBundling .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                                    $htmlBundling .= '</tr>';
                                }
                            }
                        }

                        if($key == ($count-1) || (isset($get[$key+1]) && $val['id_transaction_bundling_product'] != $get[$key+1]['id_transaction_bundling_product'])){
                            $htmlBundling .= '<tr>';
                            $htmlBundling .= $sameData;
                            $htmlBundling .= '<td>Paket</td>';
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= $addAdditionalColumn;
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= $addAdditionalColumnVariant;
                            $htmlBundling .= '<td>'.$val['bundling_name'].'</td>';
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= '<td>'.(int)($val['transaction_bundling_product_base_price']+$val['transaction_bundling_product_total_discount']).'</td>';
                            $htmlBundling .= '<td>0</td>';
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= '<td></td>';
                            $htmlBundling .= '<td>'.(int)($val['transaction_bundling_product_base_price']+$val['transaction_bundling_product_total_discount']).'</td>';
                            $htmlBundling .= '<td>'.$val['transaction_bundling_product_total_discount'].'</td>';
                            $htmlBundling .= '<td>'.(int)($val['transaction_bundling_product_base_price']+$val['transaction_bundling_product_total_discount']-$val['transaction_bundling_product_total_discount']).'</td>';
                            $htmlBundling .= '<td></td><td></td><td></td><td></td>';
                            if(isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                $htmlBundling .= '<td></td><td></td><td></td><td></td>';
                            }
                            $htmlBundling .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                            $htmlBundling .= '</tr>';
                            for ($bun = 1;$bun<=$val['transaction_bundling_product_qty'];$bun++){
                                $html .= $htmlBundling;
                            }
                            $htmlBundling = "";
                        }

                        $tmpBundling = $val['id_transaction_bundling_product'];
                    }else{
                        for($j=0;$j<$val['transaction_product_qty'];$j++){
                            $priceMod = 0;
                            $textMod = '';
                            if(!empty($mod)){
                                $priceMod = $mod[0]['transaction_product_modifier_price'];
                                $textMod = $mod[0]['text'];
                            }
                            $html .= '<tr>';
                            $html .= $sameData;
                            // $html .= '<td>'.$val['name_brand'].'</td>';
                            // $html .= '<td>'.$val['product_category_name'].'</td>';
                            if(isset($post['show_product_code']) && $post['show_product_code'] == 1){
                                $html .= '<td>'.$productCode.'</td>';
                            }
                            $html .= '<td>'.$val['product_name'].'</td>';
                            // $getTransactionVariant = TransactionProductVariant::join('product_variants as pv', 'pv.id_product_variant', 'transaction_product_variants.id_product_variant')
                            //     ->where('id_transaction_product', $val['id_transaction_product'])->select('pv.*')->get()->toArray();
                            // foreach ($getTransactionVariant as $k=>$gtV){
                            //     $getTransactionVariant[$k]['main_parent'] = $this->getParentVariant($getAllVariant, $gtV['id_product_variant']);
                            // }
                            // foreach ($getVariant as $v){
                            //     $search = array_search($v['id_product_variant'], array_column($getTransactionVariant, 'main_parent'));
                            //     if($search !== false){
                            //         $html .= '<td>'.$getTransactionVariant[$search]['product_variant_name'].'</td>';
                            //     }else{
                            //         $html .= '<td></td>';
                            //     }
                            // }
                            $priceProd = $val['transaction_product_price']+(float)$val['transaction_variant_subtotal']+$modifierGroupPrice;
                            $html .= '<td></td>';
                            $html .= '<td>'.implode(",",$modifierGroupText).'</td>';
                            $html .= '<td>'.$textMod.'</td>';
                            $html .= '<td>'.$priceProd.'</td>';
                            $html .= '<td>'.$priceMod.'</td>';
                            $html .= '<td>'.htmlspecialchars($val['transaction_product_note']).'</td>';
                            if(!empty($val['transaction_product_qty_discount'])&& $val['transaction_product_qty_discount'] > $j){
                                $html .= '<td>'.$promoName.'</td>';
                                $html .= '<td>'.$promoCode.'</td>';
                                $html .= '<td>'.($priceProd+$priceMod).'</td>';
                                $html .= '<td>'.$val['transaction_product_base_discount'].'</td>';
                                $html .= '<td>'.(($priceProd+$priceMod)-$val['transaction_product_base_discount']).'</td>';
                            }else{
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td>'.($priceProd+$priceMod).'</td>';
                                $html .= '<td>0</td>';
                                $html .= '<td>'.($priceProd+$priceMod).'</td>';
                            }
                            $html .= '<td></td><td></td><td></td><td></td>';
                            if(isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                $html .= '<td></td><td></td><td></td><td></td>';
                            }
                            $html .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                            $html .= '</tr>';

                            $totalMod = count($mod);
                            if($totalMod > 1){
                                for($i=1;$i<$totalMod;$i++){
                                    $html .= '<tr>';
                                    $html .= $sameData;
                                    $html .= '<td></td>';
                                    $html .= '<td></td>';
                                    $html .= $addAdditionalColumn;
                                    $html .= '<td></td>';
                                    $html .= $addAdditionalColumnVariant;
                                    $html .= '<td></td>';
                                    $html .= '<td></td>';
                                    $html .= '<td>'.$mod[$i]['text']??''.'</td>';
                                    $html .= '<td></td>';
                                    $html .= '<td>'.$mod[$i]['transaction_product_modifier_price']??(int)'0'.'</td>';
                                    $html .= '<td></td>';
                                    $html .= '<td></td>';
                                    $html .= '<td></td>';
                                    $html .= '<td>'.($mod[$i]['transaction_product_modifier_price']??0).'</td>';
                                    $html .= '<td>0</td>';
                                    $html .= '<td>'.$mod[$i]['transaction_product_modifier_price'].'</td>';
                                    $html .= '<td></td><td></td><td></td><td></td>';
                                    if(isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                        $html .= '<td></td><td></td><td></td><td></td>';
                                    }
                                    $html .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                                    $html .= '</tr>';
                                }
                            }
                        }
                    }

                    $sub = 0;
                    if($key == ($count-1) || (isset($get[$key+1]['transaction_receipt_number']) && $val['transaction_receipt_number'] != $get[$key+1]['transaction_receipt_number'])){
                        //for product plastic
                        $productPlastics = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
                            ->where('id_transaction', $val['id_transaction'])->where('type', 'Plastic')
                            ->get()->toArray();

                        foreach ($productPlastics as $plastic){
                            for($j=0;$j<$plastic['transaction_product_qty'];$j++){
                                $html .= '<tr>';
                                $html .= $sameData;
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= $addAdditionalColumn;
                                $html .= '<td>'.$plastic['product_name']??''.'</td>';
                                $html .= $addAdditionalColumnVariant;
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td>'.$plastic['transaction_product_price']??(int)'0'.'</td>';
                                $html .= '<td>0</td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td>'.$plastic['transaction_product_price']??(int)'0'.'</td>';
                                $html .= '<td>0</td>';
                                $html .= '<td>'.$plastic['transaction_product_price']??(int)'0'.'</td>';
                                $html .= '<td></td><td></td><td></td><td></td>';
                                if(isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                    $html .= '<td></td><td></td><td></td><td></td>';
                                }
                                $html .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                                $html .= '</tr>';
                            }
                        }

                        if(!empty($val['transaction_payment_subscription'])) {
                            $getSubcription = SubscriptionUserVoucher::join('subscription_users', 'subscription_users.id_subscription_user', 'subscription_user_vouchers.id_subscription_user')
                                ->join('subscriptions', 'subscriptions.id_subscription', 'subscription_users.id_subscription')
                                ->where('subscription_user_vouchers.id_subscription_user_voucher', $val['transaction_payment_subscription']['id_subscription_user_voucher'])
                                ->groupBy('subscriptions.id_subscription')->select('subscriptions.*', 'subscription_user_vouchers.voucher_code')->first();

                            if($getSubcription){
                                $sub  = abs($val['transaction_payment_subscription']['subscription_nominal'])??0;
                                $html .= '<tr>';
                                $html .= $sameData;
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= $addAdditionalColumn;
                                $html .= '<td>'.htmlspecialchars($getSubcription['subscription_title']).'(subscription)</td>';
                                $html .= $addAdditionalColumnVariant;
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td></td>';
                                $html .= '<td>'.abs($val['transaction_payment_subscription']['subscription_nominal']??0).'</td>';
                                $html .= '<td>'.(-$val['transaction_payment_subscription']['subscription_nominal']??0).'</td>';
                                $html .= '<td></td><td></td><td></td><td></td>';
                                if(isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                    $html .= '<td></td><td></td><td></td><td></td>';
                                }
                                $html .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                                $html .= '</tr>';
                            }
                        }elseif(!empty($promoName2)){
                            $html .= '<tr>';
                            $html .= $sameData;
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= $addAdditionalColumn;
                            $html .= '<td>'.htmlspecialchars($promoName2).'('.$promoType2.')'.'</td>';
                            $html .= $addAdditionalColumnVariant;
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td>'.abs(abs($val['transaction_discount'])??0).'</td>';
                            $html .= '<td>'.(-abs($val['transaction_discount'])??0).'</td>';
                            $html .= '<td></td><td></td><td></td><td></td>';
                            if(isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                $html .= '<td></td><td></td><td></td><td></td>';
                            }
                            $html .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                            $html .= '</tr>';
                        }

                        $deliveryPrice = $val['transaction_shipment'];
                        if($val['transaction_shipment_go_send']){
                            $deliveryPrice = $val['transaction_shipment_go_send'];
                        }
                        if(!empty($deliveryPrice)) {
                            $discountDelivery = 0;
                            $promoDiscountDelivery = '';
                            if(abs($val['transaction_discount_delivery']) > 0){
                                $promoDiscountDelivery = ' ('.(empty($promoName) ? $promoName2 : $promoName).')';
                                $discountDelivery = abs($val['transaction_discount_delivery']);
                            }

                            if(isset($val['subscription_user_voucher'][0]['subscription_user'][0]['subscription']) && !empty($val['subscription_user_voucher'][0]['subscription_user'][0]['subscription'])){
                                $promoDiscountDelivery = ' ('.$val['subscription_user_voucher'][0]['subscription_user'][0]['subscription']['subscription_title'].')';
                            }
                            $html .= '<tr>';
                            $html .= $sameData;
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= $addAdditionalColumn;
                            $html .= '<td>Delivery'.$promoDiscountDelivery.'</td>';
                            $html .= $addAdditionalColumnVariant;
                            $html .= '<td></td>';
                            $html .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                            $html .= '<td>'.($deliveryPrice??0).'</td>';
                            $html .= '<td>'.$discountDelivery.'</td>';
                            $html .= '<td>'.($deliveryPrice-$discountDelivery??0).'</td>';
                            $html .= '<td></td><td></td><td></td><td></td>';
                            if(isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                $html .= '<td></td><td></td><td></td><td></td>';
                            }
                            $html .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                            $html .= '</tr>';
                        }

                        $promoNamePaymentGateway = (empty($val['promo_payment_gateway_name']) ? "": $val['promo_payment_gateway_name']);
                        // $nominalPromoPaymentGateway =  $val['total_received_cashback'];
                        if(!empty($promoNamePaymentGateway)) {
                            $html .= '<tr>';
                            $html .= $sameData;
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= $addAdditionalColumn;
                            $html .= '<td>'.htmlspecialchars($promoNamePaymentGateway).'(Promo Payment Gateway)</td>';
                            $html .= $addAdditionalColumnVariant;
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td></td>';
                            $html .= '<td>'.$nominalPromoPaymentGateway.'</td>';
                            $html .= '<td></td><td></td><td></td>';
                            if(isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                                $html .= '<td></td><td></td><td></td><td></td>';
                            }
                            $html .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                            $html .= '</tr>';
                        }

                        $html .= '<tr>';
                        $html .= $sameData;
                        $html .= '<td></td>';
                        $html .= '<td></td>';
                        $html .= $addAdditionalColumn;
                        $html .= '<td>Fee</td>';
                        $html .= $addAdditionalColumnVariant;
                        $html .= '<td></td>';
                        $html .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                        $html .= '<td>'.($val['transaction_grandtotal']-$sub).'</td>';
                        // $html .= '<td>'.(float)$val['fee_item'].'</td>';
                        $html .= '<td>'.(float)$paymentCharge.'</td>';
                        if(isset($post['show_another_income']) && $post['show_another_income'] == 1) {
                            $html .= '<td>' . (float)$val['discount_central'] . '</td>';
                            $html .= '<td>' . (float)$val['subscription_central'] . '</td>';
                            $html .= '<td>' . (float)$val['bundling_product_fee_central'] . '</td>';
                            $html .= '<td>' . (float)$val['fee_promo_payment_gateway_central'] . '</td>';
                        }
                        // $html .= '<td>'.(float)$val['income_outlet'].'</td>';
                        $html .= '<td>'.$payment.'</td>';
                        $html .= '<td>'.abs($poinUse).'</td>';
                        $html .= '<td>'.$val['transaction_cashback_earned'].'</td>';
                        $html .= '<td>'.$pointRefund.'</td>';
                        $html .= '<td>'.$paymentRefund.'</td>';
                        $html .= '<td>'.(!empty($deliveryPrice)  ? 'Delivery' : $val['trasaction_type']).'</td>';
                        // $html .= '<td>'.($val['receive_at'] == null ? '' : date('d M Y H:i:s', strtotime($val['receive_at']))).'</td>';
                        // $html .= '<td>'.($val['ready_at'] == null ? '' : date('d M Y H:i:s', strtotime($val['ready_at']))).'</td>';
                        // $html .= '<td>'.($val['taken_at'] == null ? '' : date('d M Y H:i:s', strtotime($val['taken_at']))).'</td>';
                        // $html .= '<td>'.($val['arrived_at'] == null ? '' : date('d M Y H:i:s', strtotime($val['arrived_at']))).'</td>';
                        $html .= '</tr>';
                    }
                }
                $dataTrxDetail .= $html;
            }
            return [
                'list' => $dataTrxDetail,
                'add_column' => $columnsVariant
            ];
        }else{
            return $query;
        }
    }

    public function filterExportTransactionForAdmin($query, $post){
        if (isset($post['conditions'])) {
            foreach ($post['conditions'] as $key => $con) {
                if(is_object($con)){
                    $con = (array)$con;
                }
                if (isset($con['subject'])) {
                    if ($con['subject'] == 'receipt') {
                        $var = 'transactions.transaction_receipt_number';
                    } elseif ($con['subject'] == 'name' || $con['subject'] == 'phone' || $con['subject'] == 'email') {
                        $var = 'users.'.$con['subject'];
                    } elseif ($con['subject'] == 'product_name' || $con['subject'] == 'product_code') {
                        $var = 'products.'.$con['subject'];
                    } elseif ($con['subject'] == 'product_category') {
                        $var = 'product_categories.product_category_name';
                    }

                    if (in_array($con['subject'], ['outlet_code', 'outlet_name'])) {
                        $var = 'outlets.'.$con['subject'];
                        if ($post['rule'] == 'and') {
                            if ($con['operator'] == 'like') {
                                $query = $query->where($var, 'like', '%'.$con['parameter'].'%');
                            } else {
                                $query = $query->where($var, '=', $con['parameter']);
                            }
                        } else {
                            if ($con['operator'] == 'like') {
                                $query = $query->orWhere($var, 'like', '%'.$con['parameter'].'%');
                            } else {
                                $query = $query->orWhere($var, '=', $con['parameter']);
                            }
                        }
                    }
                    if (in_array($con['subject'], ['receipt', 'name', 'phone', 'email', 'product_name', 'product_code', 'product_category'])) {
                        if ($post['rule'] == 'and') {
                            if ($con['operator'] == 'like') {
                                $query = $query->where($var, 'like', '%'.$con['parameter'].'%');
                            } else {
                                $query = $query->where($var, '=', $con['parameter']);
                            }
                        } else {
                            if ($con['operator'] == 'like') {
                                $query = $query->orWhere($var, 'like', '%'.$con['parameter'].'%');
                            } else {
                                $query = $query->orWhere($var, '=', $con['parameter']);
                            }
                        }
                    }

                    if ($con['subject'] == 'product_weight' || $con['subject'] == 'product_price') {
                        $var = 'products.'.$con['subject'];
                        if ($post['rule'] == 'and') {
                            $query = $query->where($var, $con['operator'], $con['parameter']);
                        } else {
                            $query = $query->orWhere($var, $con['operator'], $con['parameter']);
                        }
                    }

                    if ($con['subject'] == 'grand_total' || $con['subject'] == 'product_tax') {
                        if ($con['subject'] == 'grand_total') {
                            $var = 'transactions.transaction_grandtotal';
                        } else {
                            $var = 'transactions.transaction_tax';
                        }

                        if ($post['rule'] == 'and') {
                            $query = $query->where($var, $con['operator'], $con['parameter']);
                        } else {
                            $query = $query->orWhere($var, $con['operator'], $con['parameter']);
                        }
                    }

                    if ($con['subject'] == 'transaction_status') {
                        if ($post['rule'] == 'and') {
                            if($con['operator'] == 'pending'){
                                $query = $query->whereNull('transaction_pickups.receive_at');
                            }elseif($con['operator'] == 'taken_by_driver'){
                                $query = $query->whereNotNull('transaction_pickups.taken_at')
                                    ->whereNotIn('transaction_pickups.pickup_by', ['Customer']);
                            }elseif ($con['operator'] == 'taken_by_customer'){
                                $query = $query->whereNotNull('transaction_pickups.taken_at')
                                    ->where('transaction_pickups.pickup_by', 'Customer');
                            }elseif ($con['operator'] == 'taken_by_system'){
                                $query = $query->whereNotNull('transaction_pickups.ready_at')
                                    ->whereNotNull('transaction_pickups.taken_by_system_at');
                            }elseif($con['operator'] == 'receive_at'){
                                $query = $query->whereNotNull('transaction_pickups.receive_at')
                                    ->whereNull('transaction_pickups.ready_at');
                            }elseif($con['operator'] == 'ready_at'){
                                $query = $query->whereNotNull('transaction_pickups.ready_at')
                                    ->whereNull('transaction_pickups.taken_at');
                            }else{
                                $query = $query->whereNotNull('transaction_pickups.'.$con['operator']);
                            }
                        } else {
                            if($con['operator'] == 'pending'){
                                $query = $query->orWhereNotNull('transaction_pickups.receive_at');
                            }elseif($con['operator'] == 'taken_by_driver'){
                                $query = $query->orWhere(function ($q){
                                    $q->whereNotNull('transaction_pickups.taken_at')
                                        ->whereNotIn('transaction_pickups.pickup_by', ['Customer']);
                                });
                            }elseif ($con['operator'] == 'taken_by_customer'){
                                $query = $query->orWhere(function ($q){
                                    $q->whereNotNull('transaction_pickups.taken_at')
                                        ->where('transaction_pickups.pickup_by', 'Customer');
                                });
                            }elseif ($con['operator'] == 'taken_by_system'){
                                $query = $query->orWhere(function ($q){
                                    $q->whereNotNull('transaction_pickups.ready_at')
                                        ->whereNotNull('transaction_pickups.taken_by_system_at');
                                });
                            }elseif($con['operator'] == 'receive_at'){
                                $query = $query->orWhere(function ($q){
                                    $q->whereNotNull('transaction_pickups.receive_at')
                                        ->whereNull('transaction_pickups.ready_at');
                                });
                            }elseif($con['operator'] == 'ready_at'){
                                $query = $query->orWhere(function ($q) {
                                    $q->whereNotNull('transaction_pickups.ready_at')
                                        ->whereNull('transaction_pickups.taken_at');
                                });
                            }else{
                                $query = $query->orWhereNotNull('transaction_pickups.'.$con['operator']);
                            }
                        }
                    }

                    if (in_array($con['subject'], ['status', 'courier', 'id_outlet', 'id_product', 'pickup_by'])) {
                        switch ($con['subject']) {
                            case 'status':
                                $var = 'transactions.transaction_payment_status';
                                break;

                            case 'courier':
                                $var = 'transactions.transaction_courier';
                                break;

                            case 'id_product':
                                $var = 'products.id_product';
                                break;

                            case 'id_outlet':
                                $var = 'outlets.id_outlet';
                                break;

                            case 'pickup_by':
                                $var = 'transaction_pickups.pickup_by';
                                break;

                            default:
                                continue 2;
                        }

                        if ($post['rule'] == 'and') {
                            $query = $query->where($var, '=', $con['operator']);
                        } else {
                            $query = $query->orWhere($var, '=', $con['operator']);
                        }
                    }
                }
            }
        }

        return $query;
    }

    function returnExportYield($filter){
        $query = $this->exportTransaction($filter);
        $post = $filter;
        $forCheck = '';

        foreach ($query->cursor() as $val) {
            $payment = '';
            if(!empty($val['payment_type'])){
                $payment = $val['payment_type'];
            }elseif(!empty($val['payment_method'])){
                $payment = $val['payment_method'];
            }elseif(!empty($val['id_transaction_payment_shopee_pay'])){
                $payment = 'Shopeepay';
            }

            if(isset($post['detail']) && $post['detail'] == 1){

                $mod = TransactionProductModifier::join('product_modifiers', 'product_modifiers.id_product_modifier', 'transaction_product_modifiers.id_product_modifier')
                    ->where('transaction_product_modifiers.id_transaction_product', $val['id_transaction_product'])
                    ->whereNull('transaction_product_modifiers.id_product_modifier_group')
                    ->select('product_modifiers.text')->get()->toArray();

                $promoName = '';
                $promoType = '';
                $promoCode = '';
                if(count($val['vouchers']) > 0){
                    $getDeal = Deal::where('id_deals', $val['vouchers'][0]['id_deals'])->first();
                    $promoName = $getDeal['deals_title'];
                    $promoType = 'Deals';
                    $promoCode = $val['vouchers'][0]['voucher_code'];
                }elseif (!empty($val['promo_campaign'])){
                    $promoName = $val['promo_campaign']['promo_title'];
                    $promoType = 'Promo Campaign';
                    $promoCode = $val['promo_campaign']['promo_code'];
                }elseif (isset($val['subscription_user_voucher']['subscription_user']['subscription']['subscription_title'])){
                    $promoName = $val['subscription_user_voucher']['subscription_user']['subscription']['subscription_title'];
                    $promoType = 'Subscription';
                    $promoCode = '';
                }

                $paymentStatus = $val['transaction_payment_status'];
                $status = '';
                if(empty($val['receive_at'])){
                    $status = 'Pending';
                }elseif(!empty($val['receive_at']) && empty($val['ready_at'])){
                    $status = 'Received';
                }elseif(!empty($val['ready_at']) && empty($val['taken_at'])){
                    $status = 'Ready';
                }elseif(!empty($val['taken_at']) && $val['pickup_by'] == 'Customer'){
                    $status = 'Taken by Customer';
                }elseif(!empty($val['taken_at']) && $val['pickup_by'] != 'Customer'){
                    $status = 'Taken by Driver';
                }elseif(!empty($val['taken_by_system_at'])){
                    $status = 'Taken by System';
                }elseif(!empty($val['reject_at'])){
                    $status = 'Reject';
                }

                $poinUse = '';
                if(isset($val['point_use']) && !empty($val['point_use'])){
                    $poinUse = $val['point_use']['balance'];
                }

                $pointRefund = '';
                if(isset($val['point_refund']) && !empty($val['point_refund'])){
                    $pointRefund = $val['point_refund']['balance'];
                }
                $paymentRefund = '';
                if($val['reject_type'] == 'payment'){
                    $paymentRefund = $val['amount']??$val['gross_amount'];
                }

                $paymentCharge = 0;
                if((int)$val['point_use_expense'] > 0){
                    $paymentCharge = $val['point_use_expense'];
                }

                if((int)$val['payment_charge'] > 0){
                    $paymentCharge = $val['payment_charge'];
                }
                $taken = '';
                if(!empty($val['ready_at'])){
                    $taken = date('d M Y H:i', strtotime($val['ready_at']));
                }elseif(!empty($val['taken_by_system_at'])){
                    $taken = date('d M Y H:i', strtotime($val['taken_by_system_at']));
                }

                $deliveryPrice = $val['transaction_shipment'];
                if(!empty($val['transaction_shipment_go_send'])){
                    $deliveryPrice = $val['transaction_shipment_go_send'];
                }

                $dt = [
                    'Name' => $val['name'],
                    'Phone' => $val['phone'],
                    'Gender' => $val['gender'],
                    'Date of birth' => ($val['birthday'] == null ? '' : date('d M Y', strtotime($val['birthday']))),
                    'Customer City' => $val['user_city'],
                    'Outlet Code' => $val['outlet_code'],
                    'Outlet Name' => htmlspecialchars($val['outlet_name']),
                    'Province' => $val['province_name'],
                    'City' => $val['city_name'],
                    'Receipt number' => $val['transaction_receipt_number'],
                    'Payment Status' => $paymentStatus,
                    'Transaction Status' => $status,
                    'Transaction Date' => date('d M Y', strtotime($val['transaction_date'])),
                    'Transaction Time' => date('H:i:s', strtotime($val['transaction_date'])),
                    'Customer latitude' =>$val['latitude'],
                    'Customer longitude' =>$val['longitude'],
                    'Customer distance' => $val['distance_customer'],
                    'Brand' => $val['name_brand'],
                    'Category' => $val['product_category_name'],
                    'Items' => $val['product_code'].'-'.$val['product_name'],
                    'Modifier' => implode(",", array_column($mod, 'text')),
                    'Qty' => $val['transaction_product_qty'],
                    'Notes' => $val['transaction_product_note'],
                    'Promo Type' => $promoType,
                    'Promo Name' => $promoName,
                    'Promo Code' => $promoCode,
                    'Gross Sales' => $val['transaction_grandtotal'],
                    'Discounts' => $val['transaction_product_discount'],
                    'Delivery Fee' => $deliveryPrice??'0',
                    'Discount Delivery' => $val['transaction_discount_delivery']??'0',
                    'Subscription' => abs($val['transaction_payment_subscription']['subscription_nominal']??0),
                    'Total Fee (fee item+fee discount deliver+fee payment+fee promo+fee subscription) ' => ($paymentCharge == 0? '' : (float)($val['fee_item'] + $paymentCharge + $val['discount'] + $val['subscription'])),
                    'Fee Payment Gateway' =>(float)$paymentCharge,
                    'Net Sales (income outlet)' => (float)$val['income_outlet'],
                    'Payment' => $payment,
                    'Point Use' => $poinUse,
                    'Point Cashback' => $val['transaction_cashback_earned'],
                    'Point Refund' => $pointRefund,
                    'Refund' => $paymentRefund,
                    'Sales Type' => (!empty($deliveryPrice) ? 'Delivery' : $val['trasaction_type']),
                    'Received Time' =>  ($val['receive_at'] == null ? '' : date('d M Y H:i:s', strtotime($val['receive_at']))),
                    'Ready Time' =>  ($val['ready_at'] == null ? '' : date('d M Y H:i:s', strtotime($val['ready_at']))),
                    'Taken Time' =>  $taken,
                    'Arrived Time' =>  ($val['arrived_at'] == null ? '' : date('d M Y H:i:s', strtotime($val['arrived_at'])))
                ];
            }else{
                $paymentStatus = $val['transaction_payment_status'];
                $status = '';
                if(empty($val['receive_at'])){
                    $status = 'Pending';
                }elseif(!empty($val['receive_at']) && empty($val['ready_at'])){
                    $status = 'Received';
                }elseif(!empty($val['ready_at']) && empty($val['taken_at'])){
                    $status = 'Ready';
                }elseif(!empty($val['taken_at']) && $val['pickup_by'] == 'Customer'){
                    $status = 'Taken by Customer';
                }elseif(!empty($val['taken_at']) && $val['pickup_by'] != 'Customer') {
                    $status = 'Taken by Driver';
                }elseif(!empty($val['taken_by_system_at'])){
                    $status = 'Taken by System';
                }elseif(!empty($val['reject_at'])){
                    $status = 'Reject';
                }

                $deliveryPrice = $val['transaction_shipment'];
                if(!empty($val['transaction_shipment_go_send'])){
                    $deliveryPrice = $val['transaction_shipment_go_send'];
                }

                $dt = [
                    'Name' => $val['name'],
                    'Phone' => $val['phone'],
                    'Email' => $val['email'],
                    'Transaction Date' => date('d M Y', strtotime($val['transaction_date'])),
                    'Transaction Time' => date('H:i', strtotime($val['transaction_date'])),
                    'Payment Status' => $paymentStatus,
                    'Transaction Status' => $status,
                    'Outlet Code' => $val['outlet_code'],
                    'Outlet Name' => htmlspecialchars($val['outlet_name']),
                    'Gross Sales' => number_format($val['transaction_grandtotal']),
                    'Receipt number' => $val['transaction_receipt_number'],
                    'Point Received' => number_format($val['transaction_cashback_earned']),
                    'Payments' => $payment,
                    'Transaction Type' => (!empty($deliveryPrice) ? 'Delivery' : $val['trasaction_type']),
                    'Delivery Fee' => number_format($deliveryPrice)??'-'
                ];
            }

            yield $dt;
        }
    }

    function getKeyVariant($arr, $id)
    {
        foreach ($arr as $key => $val) {
            if ($val['id_product_variant'] === $id) {
                return $key;
            }
        }
        return null;
    }

    function getParentVariant($arr, $id)
    {
        $key = $this->getKeyVariant($arr, $id);
        if ($arr[$key]['id_parent'] == 0)
        {
            return $id;
        }
        else
        {
            return $this->getParentVariant($arr, $arr[$key]['id_parent']);
        }
    }
    public function transactionDetail(TransactionDetail $request){
        $trans_outlet_service = new ApiTransactionOutletService;
        if ($request->json('transaction_receipt_number') !== null) {
            $trx = Transaction::where(['transaction_receipt_number' => $request->json('transaction_receipt_number')])->first();
            if($trx) {
                $id = $trx->id_transaction;
            } else {
                return MyHelper::checkGet([]);
            }
        } else {
            $id = $request->json('id_transaction');
        }

        $trx = Transaction::where(['transactions.id_transaction' => $id])
                ->leftJoin('transaction_outlet_services','transaction_outlet_services.id_transaction','=','transactions.id_transaction')
                ->first();

        if(!$trx){
            return MyHelper::checkGet($trx);
        }

        $trxProducts = $trans_outlet_service->transactionProduct($trx);
        $trx['product_transaction'] = $trxProducts['product'];
        $productCount = $trxProducts['count'];

        $trxProductServices = $trans_outlet_service->transactionProductService($trx);
        $trx['product_service_transaction'] = $trxProductServices['product_service'];
        $productServiceCount = $trxProductServices['count'];

        $cart = $trx['transaction_subtotal'] + $trx['transaction_shipment'] + $trx['transaction_service'] + $trx['transaction_tax'] - $trx['transaction_discount'];
        $trx['transaction_carttotal'] = $cart;
        $trx['transaction_item_total'] = $productCount;
        $trx['transaction_item_service_total'] = $productServiceCount;

        $trxPayment = $trans_outlet_service->transactionPayment($trx);
        $trx['payment'] = $trxPayment['payment'];

        $trx->load('user','outlet');
        $result = [
            'id_transaction'                => $trx['id_transaction'],
            'transaction_receipt_number'    => $trx['transaction_receipt_number'],
            'receipt_qrcode'                        => 'https://chart.googleapis.com/chart?chl=' . str_replace('#', '', $trx['transaction_receipt_number']) . '&chs=250x250&cht=qr&chld=H%7C0',
            'transaction_date'              => date('d M Y H:i', strtotime($trx['transaction_date'])),
            'trasaction_type'               => $trx['trasaction_type'],
            'transaction_grandtotal'        => MyHelper::requestNumber($trx['transaction_grandtotal'],'_CURRENCY'),
            'transaction_subtotal'          => MyHelper::requestNumber($trx['transaction_subtotal'],'_CURRENCY'),
            'transaction_discount'          => MyHelper::requestNumber($trx['transaction_discount'],'_CURRENCY'),
            'transaction_cashback_earned'   => MyHelper::requestNumber($trx['transaction_cashback_earned'],'_POINT'),
            'trasaction_payment_type'       => $trx['trasaction_payment_type'],
            'transaction_payment_status'    => $trx['transaction_payment_status'],
            'pickup_at'                     => $trx['pickup_at'],
            'completed_at'                  => $trx['completed_at'],
            'reject_at'                     => $trx['reject_at'],
            'continue_payment'              => $trxPayment['continue_payment'],
            'payment_gateway'               => $trxPayment['payment_gateway'],
            'payment_type'                  => $trxPayment['payment_type'],
            'payment_redirect_url'          => $trxPayment['payment_redirect_url'],
            'payment_redirect_url_app'      => $trxPayment['payment_redirect_url_app'],
            'payment_token'                 => $trxPayment['payment_token'],
            'total_payment'                 => (int) $trxPayment['total_payment'],
            'timer_shopeepay'               => $trxPayment['timer_shopeepay'],
            'message_timeout_shopeepay'     => $trxPayment['message_timeout_shopeepay'],
            'outlet'                        => [
                'outlet_name'    => $trx['outlet']['outlet_name'],
                'outlet_address' => $trx['outlet']['outlet_address']
            ],
            'user'                          => [
                'phone' => $trx['user']['phone'],
                'name'  => $trx['user']['name'],
                'email' => $trx['user']['email']
            ],

        ];

        $lastLog = LogInvalidTransaction::where('id_transaction', $trx['id_transaction'])->orderBy('updated_at', 'desc')->first();

        $result['image_invalid_flag'] = NULL;
        if(!empty($trx['image_invalid_flag'])){
            $result['image_invalid_flag'] =  config('url.storage_url_api').$trx['image_invalid_flag'];
        }

        $result['transaction_flag_invalid'] =  $trx['transaction_flag_invalid'];
        $result['flag_reason'] =  $lastLog['reason'] ?? '';

        $formatedTrxProduct = $trans_outlet_service->formatTransactionProduct($trx);
        $trx['total_product_qty'] = $formatedTrxProduct['qty'];
        $result['product_transaction'] = $formatedTrxProduct['result'] ?? [];

        $formatedTrxProductService = $trans_outlet_service->formatTransactionProductService($trx);
        $trx['total_product_service_qty'] = $formatedTrxProductService['qty'];
        $result['product_service_transaction'] = $formatedTrxProductService['result'] ?? [];

        $result['payment_detail'] = $trans_outlet_service->transactionPaymentDetail($trx);

        if(!isset($trx['payment'])){
            $result['transaction_payment'] = null;
        }else{
            foreach ($trx['payment'] as $key => $value) {
                if ($value['name'] == 'Balance') {
                    $result['transaction_payment'][$key] = [
                        'name'      => (env('POINT_NAME')) ? env('POINT_NAME') : $value['name'],
                        'is_balance'=> 1,
                        'amount'    => MyHelper::requestNumber($value['amount'],'_POINT')
                    ];
                } else {
                    $result['transaction_payment'][$key] = [
                        'name'      => str_replace('_', ' ', $value['name']),
                        'amount'    => MyHelper::requestNumber($value['amount'],'_CURRENCY')
                    ];
                }
            }
        }
        
        return MyHelper::checkGet($result);
    }

    public function transactionDetailOld(TransactionDetail $request){
        if ($request->json('transaction_receipt_number') !== null) {
            $trx = Transaction::where(['transaction_receipt_number' => $request->json('transaction_receipt_number')])->first();
            if($trx) {
                $id = $trx->id_transaction;
            } else {
                return MyHelper::checkGet([]);
            }
        } else {
            $id = $request->json('id_transaction');
        }

        $type = $request->json('type');

        if ($type == 'trx') {
            if($request->json('admin')){
                $list = Transaction::where(['transactions.id_transaction' => $id])->with('user');
            }else{
                $list = Transaction::where(['transactions.id_transaction' => $id, 'id_user' => $request->user()->id]);
            }
            $list = $list->leftJoin('transaction_pickups','transaction_pickups.id_transaction','=','transactions.id_transaction')->with([
            // 'user.city.province',
                'productTransaction.product.product_category',
                'productTransaction.modifiers',
                'productTransaction.variants' => function($query){
                    $query->select('id_transaction_product','transaction_product_variants.id_product_variant','transaction_product_variants.id_product_variant','product_variants.product_variant_name', 'transaction_product_variant_price')->join('product_variants','product_variants.id_product_variant','=','transaction_product_variants.id_product_variant');
                },
                'productTransaction.product.product_photos',
                'productTransaction.product.product_discounts',
                'plasticTransaction.product',
                'transaction_payment_offlines',
                'transaction_vouchers.deals_voucher.deal',
                'promo_campaign_promo_code.promo_campaign',
                'transaction_pickup_go_send.transaction_pickup_update',
                'transaction_pickup_wehelpyou.transaction_pickup_wehelpyou_updates',
                'transaction_payment_subscription.subscription_user_voucher',
                'subscription_user_voucher',
                'outlet.city'])->first();
            if(!$list){
                return MyHelper::checkGet([],'empty');
            }
            $list = $list->toArray();
            $label = [];
            $label2 = [];
            $product_count=0;
            //get item bundling
            $listItemBundling = [];
            $quantityItemBundling = 0;
            $getBundling   = TransactionBundlingProduct::join('bundling', 'bundling.id_bundling', 'transaction_bundling_products.id_bundling')
                ->where('id_transaction', $id)->get()->toArray();
            foreach ($getBundling as $key=>$bundling){
                $listItemBundling[$key] = [
                    'bundling_name' => $bundling['bundling_name'],
                    'bundling_qty' => $bundling['transaction_bundling_product_qty']
                ];

                $bundlingProduct = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
                    ->where('id_transaction_bundling_product', $bundling['id_transaction_bundling_product'])->get()->toArray();
                $basePriceBundling = 0;
                $subTotalBundlingWithoutModifier = 0;
                $subItemBundlingWithoutModifie = 0;
                foreach ($bundlingProduct as $bp){
                    $mod = TransactionProductModifier::join('product_modifiers', 'product_modifiers.id_product_modifier', 'transaction_product_modifiers.id_product_modifier')
                        ->whereNull('transaction_product_modifiers.id_product_modifier_group')
                        ->where('id_transaction_product', $bp['id_transaction_product'])
                        ->select('transaction_product_modifiers.id_product_modifier', 'transaction_product_modifiers.text as text', DB::raw('FLOOR(transaction_product_modifier_price * '.$bp['transaction_product_bundling_qty'].' * '.$bundling['transaction_bundling_product_qty'].') as product_modifier_price'))->get()->toArray();
                    $variantPrice = TransactionProductVariant::join('product_variants', 'product_variants.id_product_variant', 'transaction_product_variants.id_product_variant')
                        ->where('id_transaction_product', $bp['id_transaction_product'])
                        ->select('product_variants.id_product_variant', 'product_variants.product_variant_name',  DB::raw('FLOOR(transaction_product_variant_price) as product_variant_price'))->get()->toArray();
                    $variantNoPrice =  TransactionProductModifier::join('product_modifiers', 'product_modifiers.id_product_modifier', 'transaction_product_modifiers.id_product_modifier')
                        ->whereNotNull('transaction_product_modifiers.id_product_modifier_group')
                        ->where('id_transaction_product', $bp['id_transaction_product'])
                        ->select('transaction_product_modifiers.id_product_modifier as id_product_variant', 'transaction_product_modifiers.text as product_variant_name', 'transaction_product_modifier_price as product_variant_price')->get()->toArray();
                    $variants = array_merge($variantPrice, $variantNoPrice);

                    $listItemBundling[$key]['products'][] = [
                        'product_qty' => $bp['transaction_product_bundling_qty'],
                        'product_name' => $bp['product_name'],
                        'note' => $bp['transaction_product_note'],
                        'variants' => $variants,
                        'modifiers' => $mod
                    ];
                    $productBasePrice = $bp['transaction_product_price'] + $bp['transaction_variant_subtotal'];
                    $basePriceBundling = $basePriceBundling + ($productBasePrice * $bp['transaction_product_bundling_qty']);
                    $subTotalBundlingWithoutModifier = $subTotalBundlingWithoutModifier + (($bp['transaction_product_subtotal'] - ($bp['transaction_modifier_subtotal'] * $bp['transaction_product_bundling_qty'])));
                    $subItemBundlingWithoutModifie = $subItemBundlingWithoutModifie + ($bp['transaction_product_bundling_price'] * $bp['transaction_product_bundling_qty']);
                    $quantityItemBundling = $quantityItemBundling + $bp['transaction_product_qty'];
                }
                $listItemBundling[$key]['bundling_price_no_discount'] = $basePriceBundling * $bundling['transaction_bundling_product_qty'];
                $listItemBundling[$key]['bundling_subtotal'] = $subTotalBundlingWithoutModifier * $bundling['transaction_bundling_product_qty'];
                $listItemBundling[$key]['bundling_sub_item'] = '@'.MyHelper::requestNumber($subItemBundlingWithoutModifie,'_CURRENCY');
            }
            $list['product_transaction'] = MyHelper::groupIt($list['product_transaction'],'id_brand',null,function($key,&$val) use (&$product_count){
                $product_count += array_sum(array_column($val,'transaction_product_qty'));
                $brand = Brand::select('name_brand')->find($key);
                if(!$brand){
                    return 'No Brand';
                }
                return $brand->name_brand;
            });
            $cart = $list['transaction_subtotal'] + $list['transaction_shipment'] + $list['transaction_service'] + $list['transaction_tax'] - $list['transaction_discount'];

            $list['transaction_carttotal'] = $cart;
            $list['transaction_item_total'] = $product_count;

            $order = Setting::where('key', 'transaction_grand_total_order')->value('value');
            $exp   = explode(',', $order);
            $exp2   = explode(',', $order);

            foreach ($exp as $i => $value) {
                if ($exp[$i] == 'subtotal') {
                    unset($exp[$i]);
                    unset($exp2[$i]);
                    continue;
                }

                if ($exp[$i] == 'tax') {
                    $exp[$i] = 'transaction_tax';
                    $exp2[$i] = 'transaction_tax';
                    array_push($label, 'Tax');
                    array_push($label2, 'Tax');
                }

                if ($exp[$i] == 'service') {
                    $exp[$i] = 'transaction_service';
                    $exp2[$i] = 'transaction_service';
                    array_push($label, 'Service Fee');
                    array_push($label2, 'Service Fee');
                }

                if ($exp[$i] == 'shipping') {
                    if ($list['trasaction_type'] == 'Pickup Order') {
                        unset($exp[$i]);
                        unset($exp2[$i]);
                        continue;
                    } else {
                        $exp[$i] = 'transaction_shipment';
                        $exp2[$i] = 'transaction_shipment';
                        array_push($label, 'Delivery Cost');
                        array_push($label2, 'Delivery Cost');
                    }
                }

                if ($exp[$i] == 'discount') {
                    $exp2[$i] = 'transaction_discount';
                    array_push($label2, 'Discount');
                    unset($exp[$i]);
                    continue;
                }

                if (stristr($exp[$i], 'empty')) {
                    unset($exp[$i]);
                    unset($exp2[$i]);
                    continue;
                }
            }

            $redirectUrlApp = "";
            $redirectUrl = "";
            $tokenPayment = "";
            $continuePayment = false;
            $totalPayment = 0;
            $shopeeTimer = 0;
            $shopeeMessage = "";
            $paymentType = "";
            $paymentGateway = "";
            switch ($list['trasaction_payment_type']) {
                case 'Balance':
                    $multiPayment = TransactionMultiplePayment::where('id_transaction', $list['id_transaction'])->get()->toArray();
                    if ($multiPayment) {
                        foreach ($multiPayment as $keyMP => $mp) {
                            switch ($mp['type']) {
                                case 'Balance':
                                    $log = LogBalance::where('id_reference', $mp['id_transaction'])->where('source', 'Online Transaction')->first();
                                    if ($log['balance'] < 0) {
                                        $list['balance'] = $log['balance'];
                                        $list['check'] = 'tidak topup';
                                    } else {
                                        $list['balance'] = $list['transaction_grandtotal'] - $log['balance'];
                                        $list['check'] = 'topup';
                                    }
                                    $list['payment'][] = [
                                        'name'      => 'Balance',
                                        'amount'    => $list['balance']
                                    ];
                                    break;
                                case 'Manual':
                                    $payment = TransactionPaymentManual::with('manual_payment_method.manual_payment')->where('id_transaction', $list['id_transaction'])->first();
                                    $list['payment'] = $payment;
                                    $list['payment'][] = [
                                        'name'      => 'Cash',
                                        'amount'    => $payment['payment_nominal']
                                    ];
                                    break;
                                case 'Midtrans':
                                    $payMidtrans = TransactionPaymentMidtran::find($mp['id_payment']);
                                    $payment['name']      = strtoupper(str_replace('_', ' ', $payMidtrans->payment_type)).' '.strtoupper($payMidtrans->bank);
                                    $payment['amount']    = $payMidtrans->gross_amount;
                                    $list['payment'][] = $payment;
                                    if($list['transaction_payment_status'] == 'Pending' && !empty($payMidtrans->token)) {
                                        $redirectUrl = $payMidtrans->redirect_url;
                                        $tokenPayment = $payMidtrans->token;
                                        $continuePayment =  true;
                                        $totalPayment = $payMidtrans->gross_amount;
                                        $paymentType = strtoupper($payMidtrans->payment_type);
                                        $paymentGateway = 'Midtrans';
                                    }
                                    break;
                                case 'Ovo':
                                    $payment = TransactionPaymentOvo::find($mp['id_payment']);
                                    $payment['name']    = 'OVO';
                                    $list['payment'][] = $payment;
                                    break;
                                case 'IPay88':
                                    $PayIpay = TransactionPaymentIpay88::find($mp['id_payment']);
                                    $payment['name']    = $PayIpay->payment_method;
                                    $payment['amount']    = $PayIpay->amount / 100;
                                    $list['payment'][] = $payment;
                                    if($list['transaction_payment_status'] == 'Pending'){
                                        $redirectUrl = config('url.api_url').'/api/ipay88/pay?type=trx&id_reference='.$list['id_transaction'].'&payment_id='.$PayIpay->payment_id;
                                        $continuePayment =  true;
                                        $totalPayment = $PayIpay->amount / 100;
                                        $paymentType = strtoupper($PayIpay->payment_method);
                                        $paymentGateway = 'IPay88';
                                    }
                                    break;
                                case 'Shopeepay':
                                    $shopeePay = TransactionPaymentShopeePay::find($mp['id_payment']);
                                    $payment['name']    = 'ShopeePay';
                                    $payment['amount']  = $shopeePay->amount / 100;
                                    $payment['reject']  = $shopeePay->err_reason?:'payment expired';
                                    $list['payment'][]  = $payment;
                                    if($list['transaction_payment_status'] == 'Pending'){
                                        $redirectUrl = $shopeePay->redirect_url_http;
                                        $redirectUrlApp = $shopeePay->redirect_url_app;
                                        $continuePayment =  true;
                                        $totalPayment = $shopeePay->amount / 100;
                                        $shopeeTimer = (int) MyHelper::setting('shopeepay_validity_period', 'value', 300);
                                        $shopeeMessage ='Sorry, your payment has expired';
                                        $paymentGateway = 'Shopeepay';
                                    }
                                    break;
                                case 'Offline':
                                    $payment = TransactionPaymentOffline::where('id_transaction', $list['id_transaction'])->get();
                                    foreach ($payment as $key => $value) {
                                        $list['payment'][$key] = [
                                            'name'      => $value['payment_bank'],
                                            'amount'    => $value['payment_amount']
                                        ];
                                    }
                                    break;
                                default:
                                    break;
                            }
                        }
                    } else {
                        $log = LogBalance::where('id_reference', $list['id_transaction'])->first();
                        if ($log['balance'] < 0) {
                            $list['balance'] = $log['balance'];
                            $list['check'] = 'tidak topup';
                        } else {
                            $list['balance'] = $list['transaction_grandtotal'] - $log['balance'];
                            $list['check'] = 'topup';
                        }
                        $list['payment'][] = [
                            'name'      => 'Balance',
                            'amount'    => $list['balance']
                        ];
                    }
                    break;
                case 'Manual':
                    $payment = TransactionPaymentManual::with('manual_payment_method.manual_payment')->where('id_transaction', $list['id_transaction'])->first();
                    $list['payment'] = $payment;
                    $list['payment'][] = [
                        'name'      => 'Cash',
                        'amount'    => $payment['payment_nominal']
                    ];
                    break;
                case 'Midtrans':
                    $multiPayment = TransactionMultiplePayment::where('id_transaction', $list['id_transaction'])->get();
                    $payment = [];
                    foreach($multiPayment as $dataKey => $dataPay){
                        if($dataPay['type'] == 'Midtrans'){
                            $payMidtrans = TransactionPaymentMidtran::find($dataPay['id_payment']);
                            $payment[$dataKey]['name']      = strtoupper(str_replace('_', ' ', $payMidtrans->payment_type)).' '.strtoupper($payMidtrans->bank);
                            $payment[$dataKey]['amount']    = $payMidtrans->gross_amount;
                            if($list['transaction_payment_status'] == 'Pending' && !empty($payMidtrans->token)){
                                $redirectUrl = $payMidtrans->redirect_url;
                                $tokenPayment = $payMidtrans->token;
                                $continuePayment =  true;
                                $totalPayment = $payMidtrans->gross_amount;
                                $paymentType = strtoupper($payMidtrans->payment_type);
                                $paymentGateway = 'Midtrans';
                            }

                        }else{
                            $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                            $payment[$dataKey] = $dataPay;
                            $list['balance'] = $dataPay['balance_nominal'];
                            $payment[$dataKey]['name']          = 'Balance';
                            $payment[$dataKey]['amount']        = $dataPay['balance_nominal'];
                        }
                    }
                    $list['payment'] = $payment;
                    break;
                case 'Ovo':
                    $multiPayment = TransactionMultiplePayment::where('id_transaction', $list['id_transaction'])->get();
                    $payment = [];
                    foreach($multiPayment as $dataKey => $dataPay){
                        if($dataPay['type'] == 'Ovo'){
                            $payment[$dataKey] = TransactionPaymentOvo::find($dataPay['id_payment']);
                            $payment[$dataKey]['name']    = 'OVO';
                        }else{
                            $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                            $payment[$dataKey] = $dataPay;
                            $list['balance'] = $dataPay['balance_nominal'];
                            $payment[$dataKey]['name']          = 'Balance';
                            $payment[$dataKey]['amount']        = $dataPay['balance_nominal'];
                        }
                    }
                    $list['payment'] = $payment;
                    break;
                case 'Ipay88':
                    $multiPayment = TransactionMultiplePayment::where('id_transaction', $list['id_transaction'])->get();
                    $payment = [];
                    foreach($multiPayment as $dataKey => $dataPay){
                        if($dataPay['type'] == 'IPay88'){
                            $PayIpay = TransactionPaymentIpay88::find($dataPay['id_payment']);
                            $payment[$dataKey]['name']    = $PayIpay->payment_method;
                            $payment[$dataKey]['amount']    = $PayIpay->amount / 100;

                            if($list['transaction_payment_status'] == 'Pending'){
                                $redirectUrl = config('url.api_url').'/api/ipay88/pay?type=trx&id_reference='.$list['id_transaction'].'&payment_id='.$PayIpay->payment_id;
                                $continuePayment =  true;
                                $totalPayment = $PayIpay->amount / 100;
                                $paymentType = strtoupper($PayIpay->payment_method);
                                $paymentGateway = 'Ipay88';
                            }
                        }else{
                            $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                            $payment[$dataKey] = $dataPay;
                            $list['balance'] = $dataPay['balance_nominal'];
                            $payment[$dataKey]['name']          = 'Balance';
                            $payment[$dataKey]['amount']        = $dataPay['balance_nominal'];
                        }
                    }
                    $list['payment'] = $payment;
                    break;
                case 'Shopeepay':
                    $multiPayment = TransactionMultiplePayment::where('id_transaction', $list['id_transaction'])->get();
                    $payment = [];
                    foreach($multiPayment as $dataKey => $dataPay){
                        if($dataPay['type'] == 'Shopeepay'){
                            $payShopee = TransactionPaymentShopeePay::find($dataPay['id_payment']);
                            $payment[$dataKey]['name']      = 'ShopeePay';
                            $payment[$dataKey]['amount']    = $payShopee->amount / 100;
                            $payment[$dataKey]['reject']    = $payShopee->err_reason?:'payment expired';
                            if($list['transaction_payment_status'] == 'Pending') {
                                $redirectUrl = $payShopee->redirect_url_http;
                                $redirectUrlApp = $payShopee->redirect_url_app;
                                $continuePayment =  true;
                                $totalPayment = $payShopee->amount / 100;
                                $shopeeTimer = (int) MyHelper::setting('shopeepay_validity_period', 'value', 300);
                                $shopeeMessage ='Sorry, your payment has expired';
                                $paymentGateway = 'Shopeepay';
                            }
                        }else{
                            $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                            $payment[$dataKey]              = $dataPay;
                            $list['balance']                = $dataPay['balance_nominal'];
                            $payment[$dataKey]['name']      = 'Balance';
                            $payment[$dataKey]['amount']    = $dataPay['balance_nominal'];
                        }
                    }
                    $list['payment'] = $payment;
                    break;
                case 'Offline':
                    $payment = TransactionPaymentOffline::where('id_transaction', $list['id_transaction'])->get();
                    foreach ($payment as $key => $value) {
                        $list['payment'][$key] = [
                            'name'      => $value['payment_bank'],
                            'amount'    => $value['payment_amount']
                        ];
                    }
                    break;
                default:
                    break;
            }

            array_splice($exp, 0, 0, 'transaction_subtotal');
            array_splice($label, 0, 0, 'Cart Total');

            array_splice($exp2, 0, 0, 'transaction_subtotal');
            array_splice($label2, 0, 0, 'Cart Total');

            array_values($exp);
            array_values($label);

            array_values($exp2);
            array_values($label2);

            $imp = implode(',', $exp);
            $order_label = implode(',', $label);

            $imp2 = implode(',', $exp2);
            $order_label2 = implode(',', $label2);

            $detail = [];

            if ($list['trasaction_type'] == 'Pickup Order') {
                $detail = TransactionPickup::where('id_transaction', $list['id_transaction'])->first()->toArray();
                if($detail){
                    $qr      = $detail['order_id'];

                    $qrCode = 'https://chart.googleapis.com/chart?chl='.$qr.'&chs=250x250&cht=qr&chld=H%7C0';
                    $qrCode =   html_entity_decode($qrCode);

                    $newDetail = [];
                    foreach($detail as $key => $value){
                        $newDetail[$key] = $value;
                        if($key == 'order_id'){
                            $newDetail['order_id_qrcode'] = $qrCode;
                        }
                    }

                    $detail = $newDetail;
                }
            } elseif ($list['trasaction_type'] == 'Delivery') {
                $detail = TransactionShipment::with('city.province')->where('id_transaction', $list['id_transaction'])->first();
            }

            $list['detail'] = $detail;
            $list['order'] = $imp;
            $list['order_label'] = $order_label;

            $list['order_v2'] = $imp2;
            $list['order_label_v2'] = $order_label2;

            $list['date'] = $list['transaction_date'];
            $list['type'] = 'trx';

            if(isset($list['pickup_by']) && ($list['pickup_by'] == 'GO-SEND' || $list['pickup_by'] == 'Wehelpyou')){
                $list['trasaction_type'] = 'Delivery';
            }

            $result = [
                'id_transaction'                => $list['id_transaction'],
                'transaction_receipt_number'    => $list['transaction_receipt_number'],
                'transaction_date'              => date('d M Y H:i', strtotime($list['transaction_date'])),
                'trasaction_type'               => $list['trasaction_type'],
                'transaction_grandtotal'        => MyHelper::requestNumber($list['transaction_grandtotal'],'_CURRENCY'),
                'transaction_subtotal'          => MyHelper::requestNumber($list['transaction_subtotal'],'_CURRENCY'),
                'transaction_discount'          => MyHelper::requestNumber($list['transaction_discount'],'_CURRENCY'),
                'transaction_cashback_earned'   => MyHelper::requestNumber($list['transaction_cashback_earned'],'_POINT'),
                'trasaction_payment_type'       => $list['trasaction_payment_type'],
                'transaction_payment_status'    => $list['transaction_payment_status'],
                'continue_payment'              => $continuePayment,
                'payment_gateway'               => $paymentGateway,
                'payment_type'                  => $paymentType,
                'payment_redirect_url'          => $redirectUrl,
                'payment_redirect_url_app'      => $redirectUrlApp,
                'payment_token'                 => $tokenPayment,
                'total_payment'                 => (int)$totalPayment,
                'timer_shopeepay'               => $shopeeTimer,
                'message_timeout_shopeepay'     => $shopeeMessage,
                'outlet'                        => [
                    'outlet_name'       => $list['outlet']['outlet_name'],
                    'outlet_address'    => $list['outlet']['outlet_address']
                ]
            ];

            if($request->json('admin')){
                $lastLog = LogInvalidTransaction::where('id_transaction', $list['id_transaction'])->orderBy('updated_at', 'desc')->first();

                if(!empty($list['image_invalid_flag'])){
                    $result['image_invalid_flag'] =  config('url.storage_url_api').$list['image_invalid_flag'];
                }else{
                    $result['image_invalid_flag'] = NULL;
                }

                $result['transaction_flag_invalid'] =  $list['transaction_flag_invalid'];
                $result['flag_reason'] =  $lastLog['reason']??'';
            }

            if(isset($list['user']['phone'])){
                $result['user']['phone'] = $list['user']['phone'];
                $result['user']['name'] = $list['user']['name'];
                $result['user']['email'] = $list['user']['email'];
            }

            if ($list['trasaction_payment_type'] != 'Offline') {
                $result['detail'] = [
                    'order_id_qrcode'   => $list['detail']['order_id_qrcode'],
                    'order_id'          => $list['detail']['order_id'],
                    'pickup_type'       => $list['detail']['pickup_type'],
                    'pickup_date'       => date('d F Y', strtotime($list['detail']['pickup_at'])),
                    'pickup_time'       => ($list['detail']['pickup_type'] == 'right now') ? 'RIGHT NOW' : date('H : i', strtotime($list['detail']['pickup_at'])),
                ];
                if (isset($list['transaction_payment_status']) && $list['transaction_payment_status'] == 'Cancelled') {
                    unset($result['detail']['order_id_qrcode']);
                    unset($result['detail']['order_id']);
                    // unset($result['detail']['pickup_time']);
                    $result['transaction_status'] = 0;
                    $result['transaction_status_text'] = 'PESANAN TELAH DIBATALKAN';
                } elseif (isset($list['transaction_payment_status']) && $list['transaction_payment_status'] == 'Pending') {
                    unset($result['detail']['order_id_qrcode']);
                    unset($result['detail']['order_id']);
                    // unset($result['detail']['pickup_time']);
                    $result['transaction_status'] = 6;
                    $result['transaction_status_text'] = 'MENUNGGU PEMBAYARAN';
                } elseif($list['detail']['reject_at'] != null) {
                    $reason = $list['detail']['reject_reason'];
                    $ditolak = 'PESANAN DITOLAK';
                    if (strpos($reason, 'auto reject order') !== false) {
                        $ditolak = 'PESANAN DITOLAK OTOMATIS';
                        if (strpos($reason, 'no driver') !== false) {
                            $reason = 'GAGAL MENEMUKAN DRIVER';
                        } elseif (strpos($reason, 'not ready') !== false) {
                            $reason = 'STATUS PESANAN TIDAK DIPROSES READY';
                        } else {
                            $reason = 'OUTLET GAGAL MENERIMA PESANAN';
                        }
                    }
                    if($reason) $reason = "\n$reason";
                    unset($result['detail']['order_id_qrcode']);
                    unset($result['detail']['order_id']);
                    // unset($result['detail']['pickup_time']);
                    $result['transaction_status']       = 0;
                    $result['transaction_status_text']  = "$ditolak$reason";
                } elseif($list['detail']['taken_by_system_at'] != null) {
                    $result['transaction_status'] = 1;
                    $result['transaction_status_text'] = 'ORDER SELESAI';
                } elseif($list['detail']['taken_at'] != null && $list['trasaction_type'] != 'Delivery') {
                    $result['transaction_status'] = 2;
                    $result['transaction_status_text'] = 'PESANAN TELAH DIAMBIL';
                } elseif($list['detail']['ready_at'] != null && $list['trasaction_type'] != 'Delivery') {
                    $result['transaction_status'] = 3;
                    $result['transaction_status_text'] = 'PESANAN SUDAH SIAP DIAMBIL';
                } elseif($list['detail']['receive_at'] != null) {
                    $result['transaction_status'] = 4;
                    $result['delivery_info'] = [
                        'delivery_status' => '',
                        'delivery_address' => '',
                        'delivery_address_note' => '',
                        'booking_status' => 0,
                        'cancelable' => 1,
                        'go_send_order_no' => '',
                        'live_tracking_url' => '',
                        'delivery_status_code' => 7
                    ];
                    $result['transaction_status_text'] = 'PESANAN DITERIMA. ORDER SEDANG DIPERSIAPKAN';
                } else {
                    $result['transaction_status'] = 5;
                    $result['transaction_status_text'] = 'PESANAN MASUK. MENUNGGU OUTLET UNTUK MENERIMA ORDER';
                }
                $result['delivery_info_be'] = [
                    'delivery_address' => '',
                    'delivery_address_note' => '',
                ];

                $deliveryStatus = [
                    ['ORDER SEDANG DIPERSIAPKAN', 'Sedang mencari driver'],
                    ['DRIVER DITEMUKAN DAN SEDANG MENUJU OUTLET', 'Driver ditemukan'],
                    ['DRIVER SEDANG MENUJU OUTLET', 'Driver dalam perjalanan menuju Outlet'],
                    ['DRIVER MENGAMBIL PESANAN DI OUTLET', 'Driver mengambil pesanan di Outlet'],
                    ['PESANAN SUDAH DI PICK UP OLEH DRIVER DAN SEDANG MENUJU LOKASI #TEMANSEJIWA', 'Driver mengantarkan pesanan'],
                    ['PESANAN TELAH SELESAI DAN DITERIMA', 'Pesanan sudah diterima Customer'],
                    ['PENGIRIMAN SEDANG DITAHAN', 'Pengiriman sedang ditahan'],
                    ['DRIVER TIDAK DITEMUKAN', 'Driver tidak ditemukan'],
                    ['PENGANTARAN PESANAN TELAH DIBATALKAN', 'Pengantaran dibatalkan']
                ];
                if ($list['transaction_pickup_go_send'] && !$list['detail']['reject_at']) {
                    // $result['transaction_status'] = 5;
                    $result['delivery_info'] = [
                        'driver' => null,
                        'delivery_status' => '',
                        'delivery_address' => $list['transaction_pickup_go_send']['destination_address']?:'',
                        'delivery_address_note' => $list['transaction_pickup_go_send']['destination_note'] ?: '',
                        'booking_status' => 0,
                        'cancelable' => 1,
                        'go_send_order_no' => $list['transaction_pickup_go_send']['go_send_order_no']?:'',
                        'live_tracking_url' => $list['transaction_pickup_go_send']['live_tracking_url']?:''
                    ];
                    if($list['transaction_pickup_go_send']['go_send_id']){
                        $result['delivery_info']['booking_status'] = 1;
                    }
                    switch (strtolower($list['transaction_pickup_go_send']['latest_status'])) {
                        case 'finding driver':
                        case 'confirmed':
                            $result['delivery_info']['delivery_status'] = $deliveryStatus[0][1];
                            $result['transaction_status_text']          = $deliveryStatus[0][0];
                            $result['delivery_info']['delivery_status_code']   = 1;
                            $result['transaction_status'] = 4;
                            break;
                        case 'driver allocated':
                        case 'allocated':
                            $result['transaction_status'] = 4;
                            $result['delivery_info']['delivery_status'] = $deliveryStatus[1][1];
                            $result['transaction_status_text']          = $deliveryStatus[1][0];
                            $result['delivery_info']['delivery_status_code']   = 2;
                            $result['delivery_info']['driver']          = [
                                'driver_id'         => $list['transaction_pickup_go_send']['driver_id']?:'',
                                'driver_name'       => $list['transaction_pickup_go_send']['driver_name']?:'',
                                'driver_phone'      => $list['transaction_pickup_go_send']['driver_phone']?:'',
                                'driver_whatsapp'   => env('URL_WA') . $list['transaction_pickup_go_send']['driver_phone']?:'',
                                'driver_photo'      => $list['transaction_pickup_go_send']['driver_photo']?:'',
                                'vehicle_number'    => $list['transaction_pickup_go_send']['vehicle_number']?:'',
                            ];
                            break;
                        case 'enroute pickup':
                        case 'out_for_pickup':
                            $result['transaction_status'] = 4;
                            $result['delivery_info']['delivery_status'] = $deliveryStatus[2][1];
                            $result['delivery_info']['delivery_status_code']   = 2;
                            $result['transaction_status_text']          = $deliveryStatus[2][0];
                            $result['delivery_info']['driver']          = [
                                'driver_id'         => $list['transaction_pickup_go_send']['driver_id']?:'',
                                'driver_name'       => $list['transaction_pickup_go_send']['driver_name']?:'',
                                'driver_phone'      => $list['transaction_pickup_go_send']['driver_phone']?:'',
                                'driver_whatsapp'   => env('URL_WA') . $list['transaction_pickup_go_send']['driver_phone']?:'',
                                'driver_photo'      => $list['transaction_pickup_go_send']['driver_photo']?:'',
                                'vehicle_number'    => $list['transaction_pickup_go_send']['vehicle_number']?:'',
                            ];
                            $result['delivery_info']['cancelable'] = 1;
                            break;
                        case 'picked':
                            $result['transaction_status'] = 4;
                            $result['delivery_info']['delivery_status'] = $deliveryStatus[3][1];
                            $result['delivery_info']['delivery_status_code']   = 2;
                            $result['transaction_status_text']          = $deliveryStatus[3][0];
                            $result['delivery_info']['driver']          = [
                                'driver_id'         => $list['transaction_pickup_go_send']['driver_id']?:'',
                                'driver_name'       => $list['transaction_pickup_go_send']['driver_name']?:'',
                                'driver_phone'      => $list['transaction_pickup_go_send']['driver_phone']?:'',
                                'driver_whatsapp'   => env('URL_WA') . $list['transaction_pickup_go_send']['driver_phone']?:'',
                                'driver_photo'      => $list['transaction_pickup_go_send']['driver_photo']?:'',
                                'vehicle_number'    => $list['transaction_pickup_go_send']['vehicle_number']?:'',
                            ];
                            $result['delivery_info']['cancelable'] = 1;
                            break;
                        case 'enroute drop':
                        case 'out_for_delivery':
                            $result['transaction_status'] = 3;
                            $result['delivery_info']['delivery_status'] = $deliveryStatus[4][1];
                            $result['delivery_info']['delivery_status_code']   = 3;
                            $result['transaction_status_text']          = $deliveryStatus[4][0];
                            $result['transaction_status']               = 3;
                            $result['delivery_info']['driver']          = [
                                'driver_id'         => $list['transaction_pickup_go_send']['driver_id']?:'',
                                'driver_name'       => $list['transaction_pickup_go_send']['driver_name']?:'',
                                'driver_phone'      => $list['transaction_pickup_go_send']['driver_phone']?:'',
                                'driver_whatsapp'   => env('URL_WA') . $list['transaction_pickup_go_send']['driver_phone']?:'',
                                'driver_photo'      => $list['transaction_pickup_go_send']['driver_photo']?:'',
                                'vehicle_number'    => $list['transaction_pickup_go_send']['vehicle_number']?:'',
                            ];
                            $result['delivery_info']['cancelable'] = 0;
                            break;
                        case 'completed':
                        case 'delivered':
                            $result['transaction_status'] = 2;
                            $result['transaction_status_text']          = $deliveryStatus[5][0];
                            $result['delivery_info']['delivery_status'] = $deliveryStatus[5][1];
                            $result['delivery_info']['delivery_status_code']   = 4;
                            $result['delivery_info']['driver']          = [
                                'driver_id'         => $list['transaction_pickup_go_send']['driver_id']?:'',
                                'driver_name'       => $list['transaction_pickup_go_send']['driver_name']?:'',
                                'driver_phone'      => $list['transaction_pickup_go_send']['driver_phone']?:'',
                                'driver_whatsapp'   => env('URL_WA') . $list['transaction_pickup_go_send']['driver_phone']?:'',
                                'driver_photo'      => $list['transaction_pickup_go_send']['driver_photo']?:'',
                                'vehicle_number'    => $list['transaction_pickup_go_send']['vehicle_number']?:'',
                            ];
                            $result['delivery_info']['cancelable'] = 0;
                            break;
                        case 'cancelled':
                            $result['delivery_info']['booking_status'] = 0;
                            $result['delivery_info']['delivery_status_code'] = 0;
                            $result['transaction_status_text']         = $deliveryStatus[8][0];
                            $result['delivery_info']['delivery_status'] = $deliveryStatus[8][1];
                            $result['delivery_info']['cancelable']     = 0;
                            $result['transaction_status'] = 0;
                            break;
                        case 'rejected':
                            $result['transaction_status'] = 0;
                            $result['delivery_info']['delivery_status_code'] = 0;
                            $result['delivery_info']['booking_status'] = 0;
                            $result['transaction_status_text']         = $deliveryStatus[8][0];
                            $result['delivery_info']['delivery_status'] = $deliveryStatus[8][1];
                            $result['delivery_info']['cancelable']     = 0;
                            break;
                        case 'on_hold':
                            $result['delivery_info']['delivery_status'] = $deliveryStatus[6][1];
                            $result['delivery_info']['delivery_status_code'] = 5;
                            $result['transaction_status_text']          = $deliveryStatus[6][0];
                            $result['transaction_status']               = 5;
                            $result['delivery_info']['driver']          = [
                                'driver_id'         => $list['transaction_pickup_go_send']['driver_id']?:'',
                                'driver_name'       => $list['transaction_pickup_go_send']['driver_name']?:'',
                                'driver_phone'      => $list['transaction_pickup_go_send']['driver_phone']?:'',
                                'driver_whatsapp'   => env('URL_WA') . $list['transaction_pickup_go_send']['driver_phone']?:'',
                                'driver_photo'      => $list['transaction_pickup_go_send']['driver_photo']?:'',
                                'vehicle_number'    => $list['transaction_pickup_go_send']['vehicle_number']?:'',
                            ];
                            $result['delivery_info']['cancelable'] = 0;
                            break;
                        case 'driver not found':
                        case 'no_driver':
                            $result['delivery_info']['booking_status']  = 0;
                            $result['delivery_info']['delivery_status_code'] = 6;
                            $result['transaction_status_text']          = $deliveryStatus[7][0];
                            $result['delivery_info']['delivery_status'] = $deliveryStatus[7][1];
                            $result['delivery_info']['cancelable']      = 0;
                            $result['transaction_status'] = 0;
                            break;
                    }
                    $result['delivery_info_be'] = [
                        'delivery_address' => $list['transaction_pickup_go_send']['destination_address']?:'',
                        'delivery_address_note' => $list['transaction_pickup_go_send']['destination_note'] ?: '',
                    ];
                }elseif ($list['transaction_pickup_wehelpyou'] && !$list['detail']['reject_at']) {
                    // $result['transaction_status'] = 5;
                    $result['delivery_info'] = [
                        'driver' => null,
                        'delivery_status' => '',
                        'delivery_address' => $list['transaction_pickup_wehelpyou']['receiver_address']?:'',
                        'delivery_address_note' => $list['transaction_pickup_wehelpyou']['receiver_notes'] ?: '',
                        'booking_status' => 0,
                        'cancelable' => 1,
                        'go_send_order_no' => $list['transaction_pickup_wehelpyou']['poNo']?:'',
                        'live_tracking_url' => $list['transaction_pickup_wehelpyou']['tracking_live_tracking_url']?:''
                    ];
                    if($list['transaction_pickup_wehelpyou']['poNo']){
                        $result['delivery_info']['booking_status'] = 1;
                    }
                    switch (strtolower($list['transaction_pickup_wehelpyou']['latest_status_id'])) {
                        case 1:
                        case 11:
                            $result['delivery_info']['delivery_status'] = $deliveryStatus[0][1];
                            $result['delivery_info']['delivery_status_code']   = 1;
                            $result['transaction_status_text']          = $deliveryStatus[0][0];
                            $result['transaction_status'] = 4;
                            break;
                        case 8:
                            $result['delivery_info']['delivery_status_code']   = 4;
                            $result['delivery_info']['delivery_status'] = $deliveryStatus[2][1];
                            $result['delivery_info']['delivery_status_code']   = 2;
                            $result['transaction_status_text']          = $deliveryStatus[2][0];
                            $result['delivery_info']['driver']          = [
                                'driver_id'         => '',
                                'driver_name'       => $list['transaction_pickup_wehelpyou']['tracking_driver_name']?:'',
                                'driver_phone'      => $list['transaction_pickup_wehelpyou']['tracking_driver_phone']?:'',
                                'driver_whatsapp'   => '',
                                'driver_photo'      => $list['transaction_pickup_wehelpyou']['tracking_photo']?:'',
                                'vehicle_number'    => $list['transaction_pickup_wehelpyou']['vehicle_type']?:'',
                            ];
                            break;
                        case 32:
                            $result['delivery_info']['delivery_status_code']   = 4;
                            $result['delivery_info']['delivery_status'] = $deliveryStatus[3][1];
                            $result['delivery_info']['delivery_status_code']   = 2;
                            $result['transaction_status_text']          = $deliveryStatus[3][0];
                            $result['delivery_info']['driver']          = [
                                'driver_id'         => '',
                                'driver_name'       => $list['transaction_pickup_wehelpyou']['tracking_driver_name']?:'',
                                'driver_phone'      => $list['transaction_pickup_wehelpyou']['tracking_driver_phone']?:'',
                                'driver_whatsapp'   => '',
                                'driver_photo'      => $list['transaction_pickup_wehelpyou']['tracking_photo']?:'',
                                'vehicle_number'    => $list['transaction_pickup_wehelpyou']['vehicle_type']?:'',
                            ];
                            $result['delivery_info']['cancelable'] = 1;
                            break;
                        case 9:
                            $result['delivery_info']['delivery_status'] = $deliveryStatus[4][1];
                            $result['delivery_info']['delivery_status_code']   = 3;
                            $result['transaction_status_text']          = $deliveryStatus[4][0];
                            $result['transaction_status']               = 3;
                            $result['delivery_info']['driver']          = [
                                'driver_id'         => '',
                                'driver_name'       => $list['transaction_pickup_wehelpyou']['tracking_driver_name']?:'',
                                'driver_phone'      => $list['transaction_pickup_wehelpyou']['tracking_driver_phone']?:'',
                                'driver_whatsapp'   => '',
                                'driver_photo'      => $list['transaction_pickup_wehelpyou']['tracking_photo']?:'',
                                'vehicle_number'    => $list['transaction_pickup_wehelpyou']['vehicle_type']?:'',
                            ];
                            $result['delivery_info']['cancelable'] = 0;
                            break;
                        case 2:
                            $result['transaction_status'] = 2;
                            $result['transaction_status_text']          = $deliveryStatus[5][0];
                            $result['delivery_info']['delivery_status'] = $deliveryStatus[5][1];
                            $result['delivery_info']['delivery_status_code']   = 4;
                            $result['delivery_info']['driver']          = [
                                'driver_id'         => '',
                                'driver_name'       => $list['transaction_pickup_wehelpyou']['tracking_driver_name']?:'',
                                'driver_phone'      => $list['transaction_pickup_wehelpyou']['tracking_driver_phone']?:'',
                                'driver_whatsapp'   => '',
                                'driver_photo'      => $list['transaction_pickup_wehelpyou']['tracking_photo']?:'',
                                'vehicle_number'    => $list['transaction_pickup_wehelpyou']['vehicle_type']?:'',
                            ];
                            $result['delivery_info']['cancelable'] = 0;
                            break;
                        case 89:
                        case 90:
                        case 91:
                        case 99:
                            $result['transaction_status'] = 0;
                            $result['delivery_info']['booking_status'] = 0;
                            $result['delivery_info']['delivery_status_code'] = 0;
                            $result['delivery_info']['delivery_status'] = $deliveryStatus[8][1];
                            $result['delivery_info']['cancelable']     = 0;
                            $result['transaction_status_text']         = $deliveryStatus[8][0];
                            break;
                        case 96:
                            $result['transaction_status'] = 0;
                            $result['delivery_info']['delivery_status_code'] = 0;
                            $result['delivery_info']['booking_status'] = 0;
                            $result['delivery_info']['delivery_status'] = $deliveryStatus[8][1];
                            $result['delivery_info']['cancelable']     = 0;
                            $result['transaction_status_text']         = $deliveryStatus[8][0];
                            break;
                        case 95:
                            $result['delivery_info']['booking_status']  = 0;
                            $result['delivery_info']['delivery_status_code'] = 6;
                            $result['transaction_status_text']          = $deliveryStatus[7][0];
                            $result['delivery_info']['delivery_status'] = $deliveryStatus[7][1];
                            $result['delivery_info']['cancelable']      = 0;
                            $result['transaction_status'] = 0;
                            break;
                        default:
                            break;
                    }

                    if (empty($list['transaction_pickup_wehelpyou']['poNo']) && $list['detail']['receive_at']) {
                        $result['delivery_info']['delivery_status_code'] = 7;
                    }
                    $result['delivery_info_be'] = [
                        'delivery_address' => $list['transaction_pickup_wehelpyou']['receiver_address']?:'',
                        'delivery_address_note' => $list['transaction_pickup_wehelpyou']['receiver_notes'] ?: '',
                    ];
                }
            }

            $nameBrandBundling = Setting::where('key', 'brand_bundling_name')->first();
            $result['name_brand_bundling'] = $nameBrandBundling['value']??'Bundling';
            $result['product_bundling_transaction_name'] = $nameBrandBundling['value']??'Bundling';
            $result['product_bundling_transaction'] = $listItemBundling;
            $result['product_transaction'] = [];
            $discount = 0;
            $quantity = 0;
            $keynya = 0;
            foreach ($list['product_transaction'] as $keyTrx => $valueTrx) {
                $result['product_transaction'][$keynya]['brand'] = $keyTrx;
                foreach ($valueTrx as $keyProduct => $valueProduct) {
                    $quantity = $quantity + $valueProduct['transaction_product_qty'];
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_qty']              = $valueProduct['transaction_product_qty'];
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_subtotal']         = MyHelper::requestNumber($valueProduct['transaction_product_subtotal'],'_CURRENCY');
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_sub_item']         = '@'.MyHelper::requestNumber($valueProduct['transaction_product_subtotal'] / $valueProduct['transaction_product_qty'],'_CURRENCY');
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_modifier_subtotal']        = MyHelper::requestNumber($valueProduct['transaction_modifier_subtotal'],'_CURRENCY');
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_variant_subtotal']         = MyHelper::requestNumber($valueProduct['transaction_variant_subtotal'],'_CURRENCY');
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_note']             = $valueProduct['transaction_product_note'];
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_discount']         = $valueProduct['transaction_product_discount'];
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_name']              = $valueProduct['product']['product_name'];
                    $discount = $discount + $valueProduct['transaction_product_discount'];
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_modifiers'] = [];
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'] = [];
                    $extra_modifiers = [];
                    $extra_modifier_price = 0;
                    foreach ($valueProduct['modifiers'] as $keyMod => $valueMod) {
                        if (!$valueMod['id_product_modifier_group']) {
                            $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_modifiers'][$keyMod]['product_modifier_name']   = $valueMod['text'];
                            $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_modifiers'][$keyMod]['product_modifier_qty']    = $valueMod['qty'];
                            $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_modifiers'][$keyMod]['product_modifier_price']  = MyHelper::requestNumber($valueMod['transaction_product_modifier_price']*$valueProduct['transaction_product_qty'],'_CURRENCY');
                        } else {
                            $extra_modifiers[] = $valueMod['id_product_modifier'];
                            $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants']['m'.$keyMod]['id_product_variant']   = $valueMod['id_product_modifier'];
                            $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants']['m'.$keyMod]['product_variant_name']   = $valueMod['text'];
                            $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants']['m'.$keyMod]['product_variant_price']  = (int)$valueMod['transaction_product_modifier_price'];
                            $extra_modifier_price += (int) ($valueMod['qty'] * $valueMod['transaction_product_modifier_price']);
                        }
                    }
                    $variantsPrice = 0;
                    foreach ($valueProduct['variants'] as $keyMod => $valueMod) {
                        $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'][$keyMod]['id_product_variant']   = $valueMod['id_product_variant'];
                        $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'][$keyMod]['product_variant_name']   = $valueMod['product_variant_name'];
                        $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'][$keyMod]['product_variant_price']  = (int)$valueMod['transaction_product_variant_price'];
                        $variantsPrice = $variantsPrice + $valueMod['transaction_product_variant_price'];
                    }
                    $variantsPrice += $extra_modifier_price;
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'] = array_values($result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants']);
                    if ($valueProduct['id_product_variant_group'] ?? false) {
                        $order = array_flip(Product::getVariantParentId($valueProduct['id_product_variant_group'], Product::getVariantTree($valueProduct['id_product'], $list['outlet'])['variants_tree'], $extra_modifiers));
                        usort($result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'], function ($a, $b) use ($order) {
                            return ($order[$a['id_product_variant']]??999) <=> ($order[$b['id_product_variant']]??999);
                        });
                    }
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['product_variant_group_price'] = (int)($valueProduct['transaction_product_price'] + $variantsPrice);

                    $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_modifiers'] = array_values($result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_modifiers']);
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'] = array_values($result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants']);
                }
                $keynya++;
            }

            $result['plastic_transaction_detail'] = [];
            $result['plastic_name'] = '';
            $quantityPlastic = 0;
            if(isset($list['plastic_transaction'])){
                $result['plastic_name'] = 'Kantong Belanja';
                $subtotal_plastic = 0;
                foreach($list['plastic_transaction'] as $key => $value){
                    $quantityPlastic = $quantityPlastic + $value['transaction_product_qty'];
                    $subtotal_plastic += $value['transaction_product_subtotal'];

                    $result['plastic_transaction_detail'][] = [
                        'plastic_name' => $value['product']['product_name'],
                        'plasctic_qty' => $value['transaction_product_qty'],
                        'plastic_base_price' => '@'.MyHelper::requestNumber((int)$value['transaction_product_price'],'_CURRENCY'),
                        'plasctic_subtotal' => MyHelper::requestNumber($value['transaction_product_subtotal'],'_CURRENCY')
                    ];
                }

                $result['plastic_transaction'] = [];
                $result['plastic_transaction']['transaction_plastic_total'] = $subtotal_plastic;
            }

            $totalItem = $quantity+$quantityItemBundling+$quantityPlastic;
            $result['payment_detail'][] = [
                'name'      => 'Subtotal',
                'desc'      => $totalItem . ' items',
                'amount'    => MyHelper::requestNumber($list['transaction_subtotal'],'_CURRENCY')
            ];

            if ($list['transaction_discount']) {
                $discount = abs($list['transaction_discount']);
                $p = 0;
                if (!empty($list['transaction_vouchers'])) {
                    foreach ($list['transaction_vouchers'] as $valueVoc) {
                        $result['promo']['code'][$p++]   = $valueVoc['deals_voucher']['voucher_code'];
                        $result['payment_detail'][] = [
                            'name'          => 'Diskon',
                            'desc'          => 'Promo',
                            "is_discount"   => 1,
                            'amount'        => '- '.MyHelper::requestNumber($discount,'_CURRENCY')
                        ];
                    }
                }

                if (!empty($list['promo_campaign_promo_code'])) {
                    $result['promo']['code'][$p++]   = $list['promo_campaign_promo_code']['promo_code'];
                    $result['payment_detail'][] = [
                        'name'          => 'Diskon',
                        'desc'          => 'Promo',
                        "is_discount"   => 1,
                        'amount'        => '- '.MyHelper::requestNumber($discount,'_CURRENCY')
                    ];
                }

                if (!empty($list['id_subscription_user_voucher']) && !empty($list['transaction_discount'])) {
                    $result['payment_detail'][] = [
                        'name'          => 'Subscription',
                        'desc'          => 'Diskon',
                        "is_discount"   => 1,
                        'amount'        => '- '.MyHelper::requestNumber($discount,'_CURRENCY')
                    ];
                }
            }

            if ($list['transaction_shipment_go_send'] > 0) {
                $result['payment_detail'][] = [
                    'name'      => 'Delivery',
                    'desc'      => $list['detail']['pickup_by'],
                    'amount'    => MyHelper::requestNumber($list['transaction_shipment_go_send'],'_CURRENCY')
                ];
            }elseif($list['transaction_shipment'] > 0){
                $result['payment_detail'][] = [
                    'name'      => 'Delivery',
                    'desc'      => strtoupper($list['shipment_courier']),
                    'amount'    => MyHelper::requestNumber($list['transaction_shipment'],'_CURRENCY')
                ];
            }

            if ($list['transaction_discount_delivery']) {
                $discount = abs($list['transaction_discount_delivery']);
                $p = 0;
                if (!empty($list['transaction_vouchers'])) {
                    foreach ($list['transaction_vouchers'] as $valueVoc) {
                        $result['promo']['code'][$p++]   = $valueVoc['deals_voucher']['voucher_code'];
                        $result['payment_detail'][] = [
                            'name'          => 'Diskon',
                            'desc'          => 'Delivery',
                            "is_discount"   => 1,
                            'amount'        => '- '.MyHelper::requestNumber($discount,'_CURRENCY')
                        ];
                    }
                }

                if (!empty($list['promo_campaign_promo_code'])) {
                    $result['promo']['code'][$p++]   = $list['promo_campaign_promo_code']['promo_code'];
                    $result['payment_detail'][] = [
                        'name'          => 'Diskon',
                        'desc'          => 'Delivery',
                        "is_discount"   => 1,
                        'amount'        => '- '.MyHelper::requestNumber($discount,'_CURRENCY')
                    ];
                }

                if (!empty($list['id_subscription_user_voucher']) && !empty($list['transaction_discount_delivery'])) {
                    $result['payment_detail'][] = [
                        'name'          => 'Subscription',
                        'desc'          => 'Delivery',
                        "is_discount"   => 1,
                        'amount'        => '- '.MyHelper::requestNumber($discount,'_CURRENCY')
                    ];
                }
            }



            $result['promo']['discount'] = $discount;
            $result['promo']['discount'] = MyHelper::requestNumber($discount,'_CURRENCY');

            if ($list['trasaction_payment_type'] != 'Offline') {
                if ($list['transaction_payment_status'] == 'Cancelled') {
                    $statusOrder[] = [
                        'text'  => 'Pesanan telah dibatalkan karena pembayaran gagal',
                        'date'  => $list['void_date']??$list['transaction_date']
                    ];
                }
                elseif ($list['transaction_payment_status'] == 'Pending') {
                    $statusOrder[] = [
                        'text'  => 'Menunggu konfirmasi pembayaran',
                        'date'  => $list['transaction_date']
                    ];
                } else {
                    if ($list['detail']['reject_at'] != null) {
                        $statusOrder[] = [
                            'text'  => 'Order rejected',
                            'date'  => $list['detail']['reject_at'],
                            'reason'=> $list['detail']['reject_reason']
                        ];
                    }
                    if ($list['detail']['taken_by_system_at'] != null) {
                        $statusOrder[] = [
                            'text'  => 'Pesanan Anda sudah selesai',
                            'date'  => $list['detail']['taken_by_system_at']
                        ];
                    }
                    if ($list['detail']['taken_at'] != null && empty($list['transaction_shipment_go_send']) && empty($list['transaction_shipment'])) {
                        $statusOrder[] = [
                            'text'  => 'Pesanan telah diambil',
                            'date'  => $list['detail']['taken_at']
                        ];
                    }
                    if ($list['detail']['ready_at'] != null && empty($list['transaction_shipment_go_send']) && empty($list['transaction_shipment'])) {
                        $is_admin = $request->user()->tokenCan('be');
                        $statusOrder[] = [
                            'text'  => 'Pesanan sudah siap diambil'. ($list['detail']['is_autoready'] && $is_admin ? ' (auto ready by system)' : ''),
                            'date'  => $list['detail']['ready_at']
                        ];
                    } elseif ($list['detail']['ready_at'] != null) {
                        $is_admin = $request->user()->tokenCan('be');
                        $statusOrder[] = [
                            'text'  => 'Pesanan sudah siap diambil Driver'. ($list['detail']['is_autoready'] && $is_admin ? ' (auto ready by system)' : ''),
                            'date'  => $list['detail']['ready_at']
                        ];
                    }
                    if ($list['transaction_pickup_go_send']) {
                        $flagStatus = [
                            'confirmed' => 0,
                            'no_driver' => 0,
                        ];
                        $hasPicked = false;
                        foreach ($list['transaction_pickup_go_send']['transaction_pickup_update'] as $valueGosend) {
                            switch (strtolower($valueGosend['status'])) {
                                // case 'finding driver':
                                // case 'confirmed':
                                //     if ($flagStatus['confirmed']) {
                                //         break;
                                //     }
                                //     $flagStatus['confirmed'] = 1;
                                //     if($list['detail']['ready_at'] != null){
                                //         $statusOrder[] = [
                                //             'text'  => 'Pesanan sudah siap dan menunggu diambil Driver',
                                //             'date'  => $list['detail']['ready_at']
                                //         ];
                                //     }
                                //     break;
                                // case 'driver allocated':
                                // case 'allocated':
                                //     $statusOrder[] = [
                                //         'text'  => 'Driver ditemukan',
                                //         'date'  => $valueGosend['created_at']
                                //     ];
                                //     break;
                                // case 'enroute pickup':
                                case 'out_for_pickup':
                                    $statusOrder[] = [
                                        'text'  => 'Driver dalam perjalanan menuju outlet',
                                        'date'  => $valueGosend['created_at']
                                    ];
                                    break;
                                case 'picked':
                                    if (!$hasPicked) {
                                        $statusOrder[] = [
                                            'text'  => 'Driver mengambil pesanan di outlet',
                                            'date'  => $valueGosend['created_at']
                                        ];
                                        $hasPicked = true;
                                    }
                                    break;
                                case 'enroute drop':
                                case 'out_for_delivery':
                                    $statusOrder[] = [
                                        'text'  => 'Pesanan sudah diambil dan sedang diantar ke lokasi #temansejiwa',
                                        'date'  => $valueGosend['created_at']
                                    ];
                                    if (!$hasPicked) {
                                        $statusOrder[] = [
                                            'text'  => 'Driver mengambil pesanan di outlet',
                                            'date'  => $valueGosend['created_at']
                                        ];
                                        $hasPicked = true;
                                    }
                                    break;
                                case 'completed':
                                case 'delivered':
                                    $statusOrder[] = [
                                        'text'  => 'Pesanan telah selesai',
                                        'date'  => $valueGosend['created_at']
                                    ];
                                    break;
                                case 'cancelled':
                                    $statusOrder[] = [
                                        'text'  => 'Pengantaran pesanan telah dibatalkan',
                                        'date'  => $valueGosend['created_at']
                                    ];
                                    break;
                                case 'rejected':
                                    $statusOrder[] = [
                                        'text'  => 'Pesanan telah dibatalkan karena driver tidak dapat mencapai lokasi #temansejiwa',
                                        'date'  => $valueGosend['created_at']
                                    ];
                                    break;
                                case 'on_hold':
                                    $has_rejected = in_array('rejected', array_column($list['transaction_pickup_go_send']['transaction_pickup_update'], 'status'));
                                    if (!$has_rejected) {
                                        $statusOrder[] = [
                                            'text'  => 'Pengiriman sedang ditahan karena driver tidak dapat mencapai lokasi #temansejiwa',
                                            'date'  => $valueGosend['created_at']
                                        ];
                                    }
                                    break;
                                case 'driver not found':
                                case 'no_driver':
                                    if ($flagStatus['no_driver']) {
                                        break;
                                    }
                                    $flagStatus['no_driver'] = 1;
                                    $statusOrder[] = [
                                        'text'  => 'Driver tidak ditemukan',
                                        'date'  => $valueGosend['created_at']
                                    ];
                                    break;
                            }
                        }
                    }elseif ($list['transaction_pickup_wehelpyou']) {
                        foreach ($list['transaction_pickup_wehelpyou']['transaction_pickup_wehelpyou_updates'] as $valueWehelpyou) {
                            switch (strtolower($valueWehelpyou['status_id'])) {
                                case 8:
                                    $statusOrder[] = [
                                        'text'  => 'Driver dalam perjalanan menuju outlet',
                                        'date'  => $valueWehelpyou['created_at']
                                    ];
                                    break;
                                case 32:
                                    $statusOrder[] = [
                                        'text'  => 'Driver mengambil pesanan di outlet',
                                        'date'  => $valueWehelpyou['created_at']
                                    ];
                                    break;
                                case 9:
                                    $statusOrder[] = [
                                        'text'  => 'Pesanan sudah diambil dan sedang diantar ke lokasi #temansejiwa',
                                        'date'  => $valueWehelpyou['created_at']
                                    ];
                                    break;
                                case 2:
                                    $statusOrder[] = [
                                        'text'  => 'Pesanan telah selesai',
                                        'date'  => $valueWehelpyou['created_at']
                                    ];
                                    break;
                                case 89:
                                case 90:
                                case 91:
                                    $statusOrder[] = [
                                        'text'  => 'Pengantaran pesanan telah dibatalkan',
                                        'date'  => $valueWehelpyou['created_at']
                                    ];
                                    break;
                                case 96:
                                    $statusOrder[] = [
                                        'text'  => 'Pesanan telah dibatalkan karena driver tidak dapat mencapai lokasi #temansejiwa',
                                        'date'  => $valueWehelpyou['created_at']
                                    ];
                                    break;
                                case 95:
                                    $flagStatus['no_driver'] = 1;
                                    $statusOrder[] = [
                                        'text'  => 'Driver tidak ditemukan',
                                        'date'  => $valueWehelpyou['created_at']
                                    ];
                                    break;
                            }
                        }
                    }
                    if ($list['detail']['receive_at'] != null) {
                        if ($list['transaction_pickup_go_send'] || $list['transaction_pickup_wehelpyou']) {
                            $statusOrder[] = [
                                'text'  => 'Pesanan diterima. Order sedang dipersiapkan',
                                'date'  => $list['detail']['receive_at']
                            ];
                        } else {
                            $statusOrder[] = [
                                'text'  => 'Pesanan diterima dan sedang dipersiapkan',
                                'date'  => $list['detail']['receive_at']
                            ];
                        }
                    }
                    $statusOrder[] = [
                        'text'  => 'Pesanan masuk. Menunggu outlet menerima order',
                        'date'  => $list['completed_at'] ?: $list['transaction_date']
                    ];
                }

                usort($statusOrder, function($a1, $a2) {
                    $v1 = strtotime($a1['date']);
                    $v2 = strtotime($a2['date']);
                    return $v2 - $v1; // $v2 - $v1 to reverse direction
                });

                foreach ($statusOrder as $keyStatus => $status) {
                    $result['detail']['detail_status'][$keyStatus] = [
                        'text'  => $status['text'],
                        'date'  => MyHelper::dateFormatInd($status['date'])
                    ];
                    if ($status['text'] == 'Order rejected') {
                        if (strpos($list['detail']['reject_reason'], 'auto reject order by system [no driver]') !== false) {
                            $result['detail']['detail_status'][$keyStatus]['text'] = 'Maaf pesanan ditolak karena driver tidak ditemukan. Mohon ulangi pemesanan';
                        } elseif (strpos($list['detail']['reject_reason'], 'auto reject order by system') !== false) {
                            $result['detail']['detail_status'][$keyStatus]['text'] = 'Maaf pesanan ditolak. Mohon ulangi pemesanan';
                        } else {
                            $result['detail']['detail_status'][$keyStatus]['text'] = 'Maaf pesanan ditolak karena '.strtolower($list['detail']['reject_reason']);
                        }
                        $result['detail']['reject_reason'] = $list['detail']['reject_reason'];
                        $result['detail']['detail_status'][$keyStatus]['reason'] = $list['detail']['reject_reason'];
                    }
                }
            }
            if(!isset($list['payment'])){
                $result['transaction_payment'] = null;
            }else{
                foreach ($list['payment'] as $key => $value) {
                    if ($value['name'] == 'Balance') {
                        $result['transaction_payment'][$key] = [
                            'name'      => (env('POINT_NAME')) ? env('POINT_NAME') : $value['name'],
                            'is_balance'=> 1,
                            'amount'    => MyHelper::requestNumber($value['amount'],'_POINT')
                        ];
                    } else {
                        $result['transaction_payment'][$key] = [
                            'name'      => $value['name'],
                            'amount'    => MyHelper::requestNumber($value['amount'],'_CURRENCY')
                        ];
                    }
                }
            }

            if (!empty($list['transaction_payment_subscription'])) {
                $payment_subscription = abs($list['transaction_payment_subscription']['subscription_nominal']);
                $result['transaction_payment'][] = [
                    'name'      => 'Subscription',
                    'amount'    => MyHelper::requestNumber($payment_subscription,'_CURRENCY')
                ];
            }

            return response()->json(MyHelper::checkGet($result));
        } else {
            $list = DealsUser::with('outlet', 'dealVoucher.deal')->where('id_deals_user', $id)->orderBy('claimed_at', 'DESC')->first();

            if (empty($list)) {
                return response()->json(MyHelper::checkGet($list));
            }

            $result = [
                'trasaction_type'               => 'voucher',
                'id_deals_user'                 => $list['id_deals_user'],
                'deals_receipt_number'          => implode('', [strtotime($list['claimed_at']), $list['id_deals_user']]),
                'date'                          => date('d M Y H:i', strtotime($list['claimed_at'])),
                'voucher_price_cash'            => MyHelper::requestNumber($list['voucher_price_cash'],'_CURRENCY'),
                'deals_voucher'                 => $list['dealVoucher']['deal']['deals_title'],
                'payment_methods'               => $list['payment_method']
            ];

            if (!is_null($list['balance_nominal'])) {
                $result['payment'][] = [
                    'name'      => (env('POINT_NAME')) ? env('POINT_NAME') : 'Balance',
                    'is_balance'=> 1,
                    'amount'    => MyHelper::requestNumber($list['balance_nominal'],'_POINT'),
                ];
            }
            switch ($list['payment_method']) {
                case 'Manual':
                    $payment = DealsPaymentManual::where('id_deals_user', $id)->first();
                    $result['payment'][] = [
                        'name'      => 'Manual',
                        'amount'    =>  MyHelper::requestNumber($payment->payment_nominal,'_CURRENCY')
                    ];
                    break;
                case 'Midtrans':
                    $payment = DealsPaymentMidtran::where('id_deals_user', $id)->first();
                    $result['payment'][] = [
                        'name'      => strtoupper(str_replace('_', ' ', $payment->payment_type)).' '.strtoupper($payment->bank),
                        'amount'    => MyHelper::requestNumber($payment->gross_amount,'_CURRENCY')
                    ];
                    break;
                case 'OVO':
                    $payment = DealsPaymentOvo::where('id_deals_user', $id)->first();
                    $result['payment'][] = [
                        'name'      => 'OVO',
                        'amount'    =>  MyHelper::requestNumber($payment->amount,'_CURRENCY')
                    ];
                    break;
                case 'Ipay88':
                    $payment = DealsPaymentIpay88::where('id_deals_user', $id)->first();
                    $result['payment'][] = [
                        'name'      => $payment->payment_method,
                        'amount'    =>  MyHelper::requestNumber($payment->amount / 100,'_CURRENCY')
                    ];
                    break;
                case 'Shopeepay':
                    $payment = DealsPaymentShopeePay::where('id_deals_user', $id)->first();
                    $result['payment'][] = [
                        'name'      => 'ShopeePay',
                        'amount'    =>  MyHelper::requestNumber($payment->amount,'_CURRENCY')
                    ];
                    break;
            }

            return response()->json(MyHelper::checkGet($result));
        }


    }
    // api/transaction/item
    // api order lagi
    public function transactionDetailTrx(Request $request) {
        $trid = $request->json('id_transaction');
        $rn = $request->json('request_number');
        $trx = Transaction::join('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
            ->join('outlets', 'outlets.id_outlet', '=', 'transactions.id_outlet')
            ->select('transactions.id_transaction', 'transactions.id_user', 'transactions.id_outlet', 'outlets.outlet_code', 'pickup_by', 'pickup_type', 'pickup_at', 'id_transaction_pickup')->where([
                'transactions.id_transaction' => $trid,
                'id_user' => $request->user()->id
            ])->first();
        if(!$trx){
            return [
                'status'=>'fail',
                'messages'=>['Transaction not found']
            ];
        }
        $id_transaction = $trx['id_transaction'];
        $pts = TransactionProduct::select(DB::raw('
            0 as id_custom,
            transaction_products.id_product,
            id_transaction_product,
            id_product_variant_group,
            id_brand,
            transaction_products.id_outlet,
            outlets.outlet_code,
            outlets.outlet_different_price,
            transaction_product_qty as qty,
            products.product_name,
            products.product_code,
            transaction_products.transaction_product_note as note,
            transaction_products.transaction_product_price
            '))
            ->join('products','products.id_product','=','transaction_products.id_product')
            ->join('outlets','outlets.id_outlet','=','transaction_products.id_outlet')
            ->where('transaction_products.type', 'product')
            ->whereNull('id_transaction_bundling_product')
            ->where(['id_transaction'=>$id_transaction])
            ->with(['modifiers'=>function($query){
                $query->select('id_transaction_product','product_modifiers.code','transaction_product_modifiers.id_product_modifier','qty','product_modifiers.text', 'transaction_product_modifier_price', 'modifier_type')->join('product_modifiers','product_modifiers.id_product_modifier','=','transaction_product_modifiers.id_product_modifier');
            },'variants'=>function($query){
                $query->select('id_transaction_product','transaction_product_variants.id_product_variant','transaction_product_variants.id_product_variant','product_variants.product_variant_name', 'transaction_product_variant_price')->join('product_variants','product_variants.id_product_variant','=','transaction_product_variants.id_product_variant');
            }])->get()->toArray();

        $id_outlet = $trx['id_outlet'];
        $total_mod_price = 0;
        foreach ($pts as &$pt) {
            if ($pt['outlet_different_price']) {
                $pt['product_price'] = ProductSpecialPrice::select('product_special_price')->where([
                    'id_outlet' => $pt['id_outlet'],
                    'id_product' => $pt['id_product']
                ])->pluck('product_special_price')->first()?:$pt['transaction_product_price'];
            } else {
                $pt['product_price'] = ProductGlobalPrice::select('product_global_price')->where('id_product',$pt['id_product'])->pluck('product_global_price')->first()?:$pt['transaction_product_price'];
            }
            $pt['extra_modifiers'] = [];
            foreach ($pt['modifiers'] as $key => &$modifier) {
                if ($pt['outlet_different_price']) {
                    $price = ProductModifierPrice::select('product_modifier_price')->where([
                        'id_product_modifier'=>$modifier['id_product_modifier'],
                        'id_outlet' => $id_outlet
                    ])->pluck('product_modifier_price')->first()?:$modifier['transaction_product_modifier_price'];
                } else {
                    $price = ProductModifierGlobalPrice::select('product_modifier_price')->where('id_product_modifier', $modifier['id_product_modifier'])->pluck('product_modifier_price')->first()?:$modifier['transaction_product_modifier_price'];
                }
                $total_mod_price+=$price*$modifier['qty'];
                $modifier['product_modifier_price'] = MyHelper::requestNumber($price,$rn);
                unset($modifier['transaction_product_modifier_price']);
                if ($modifier['modifier_type'] == 'Modifier Group') {
                    $pt['variants'][] = [
                        'id_transaction_product' => $pt['id_transaction_product'],
                        'id_product_variant' => $modifier['id_product_modifier'],
                        'product_variant_name' => $modifier['text'],
                        'product_variant_price' => (double) $price,
                    ];
                    $pt['extra_modifiers'][] = $modifier['id_product_modifier'];
                    unset($pt['modifiers'][$key]);
                }
            }
            $pt['modifiers'] = array_values($pt['modifiers']);
            if ($pt['id_product_variant_group']) {
                if ($pt['outlet_different_price']) {
                    $product_price = ProductVariantGroupSpecialPrice::select('product_variant_group_price')->where('id_product_variant_group', $pt['id_product_variant_group'])->first();
                } else {
                    $product_price = ProductVariantGroup::select('product_variant_group_price')->where('id_product_variant_group', $pt['id_product_variant_group'])->first();
                }
                $pt['selected_variant'] = Product::getVariantParentId($pt['id_product_variant_group'], Product::getVariantTree($pt['id_product'], $pt)['variants_tree'] ?? [], $pt['extra_modifiers']);
                if (!$product_price) {
                    $pt['product_price'] = $pt['product_price'] + array_sum(array_column($pt['variants'], 'transaction_product_variant_price'));
                } else {
                    $pt['product_price'] = $product_price->product_variant_group_price;
                }
            } else {
                $pt['selected_variant'] = [];
            }
            $order = array_flip($pt['selected_variant']);
            usort($pt['variants'], function ($a, $b) use ($order) {
                return ($order[$a['id_product_variant']]??999) <=> ($order[$b['id_product_variant']]??999);
            });
            $pt['product_price_total'] = MyHelper::requestNumber(($total_mod_price + $pt['product_price'])*$pt['qty'],$rn);
            $pt['product_price'] = MyHelper::requestNumber($pt['product_price'],$rn);
            $pt['note'] = $pt['note']?:'';
            unset($pt['transaction_product_price']);
        }

        //item bundling
        $getBundling   = TransactionBundlingProduct::join('bundling', 'bundling.id_bundling', 'transaction_bundling_products.id_bundling')
            ->where('id_transaction', $id_transaction)->get()->toArray();
        $itemBundling = [];
        foreach ($getBundling as $key=>$bundling){
            $bundlingProduct = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
                ->where('id_transaction_bundling_product', $bundling['id_transaction_bundling_product'])->get()->toArray();
            $basePriceBundling = 0;
            $products = [];
            foreach ($bundlingProduct as $bp){
                $mod = TransactionProductModifier::join('product_modifiers', 'product_modifiers.id_product_modifier', 'transaction_product_modifiers.id_product_modifier')
                    ->whereNull('transaction_product_modifiers.id_product_modifier_group')
                    ->where('id_transaction_product', $bp['id_transaction_product'])
                    ->select('transaction_product_modifiers.code', 'transaction_product_modifiers.qty', 'transaction_product_modifiers.id_product_modifier', 'transaction_product_modifiers.text as text',
                        DB::raw('FLOOR(transaction_product_modifier_price * '.$bp['transaction_product_bundling_qty'].' * '.$bundling['transaction_bundling_product_qty'].') as product_modifier_price'))->get()->toArray();
                $variantPrice = TransactionProductVariant::join('product_variants', 'product_variants.id_product_variant', 'transaction_product_variants.id_product_variant')
                    ->where('id_transaction_product', $bp['id_transaction_product'])
                    ->select('product_variants.id_product_variant', 'product_variants.product_variant_name',  DB::raw('FLOOR(transaction_product_variant_price) as product_variant_price'))->get()->toArray();
                $variantNoPrice =  TransactionProductModifier::join('product_modifiers', 'product_modifiers.id_product_modifier', 'transaction_product_modifiers.id_product_modifier')
                    ->whereNotNull('transaction_product_modifiers.id_product_modifier_group')
                    ->where('id_transaction_product', $bp['id_transaction_product'])
                    ->select('transaction_product_modifiers.id_product_modifier as id_product_variant', 'transaction_product_modifiers.text as product_variant_name', 'transaction_product_modifier_price as product_variant_price')->get()->toArray();
                $variants = array_merge($variantPrice, $variantNoPrice);
                $extraMod = array_column($variantNoPrice, 'id_product_variant');

                for ($i=1;$i<=$bp['transaction_product_bundling_qty'];$i++){
                    $products[] = [
                        'id_brand' => $bp['id_brand'],
                        'id_bundling' => $bundling['id_bundling'],
                        'id_bundling_product' => $bp['id_bundling_product'],
                        'id_product' => $bp['id_product'],
                        'id_product_variant_group' => $bp['id_product_variant_group'],
                        'note' => $bp['transaction_product_note'],
                        'product_code' => $bp['product_code'],
                        'product_name' => $bp['product_name'],
                        'note' => $bp['transaction_product_note'],
                        'extra_modifiers' => $extraMod,
                        'variants' => $variants,
                        'modifiers' => $mod
                    ];
                }
                $productPrice = $bp['transaction_product_price'] + $bp['transaction_variant_subtotal'];
                $basePriceBundling = $basePriceBundling + ($productPrice * $bp['transaction_product_bundling_qty']);
            }

            $itemBundling[] = [
                'id_custom' => $key+1,
                'id_bundling' => $bundling['id_bundling'],
                'bundling_name' => $bundling['bundling_name'],
                'bundling_qty' => $bundling['transaction_bundling_product_qty'],
                'bundling_code' =>  $bundling['bundling_code'],
                'bundling_base_price' => (int)$bundling['transaction_bundling_product_base_price'],
                'bundling_price_no_discount' => $basePriceBundling * $bundling['transaction_bundling_product_qty'],
                'bundling_price_total' => (int)$bundling['transaction_bundling_product_subtotal'],
                'products' => $products
            ];
        }

        if(empty($pts) && empty($getBundling)){
            return MyHelper::checkGet([]);
        }

        $result = [
            'id_outlet' => $trx->id_outlet,
            'outlet_code' => $trx->outlet_code,
            'item' => $pts,
            'item_bundling' => $itemBundling
        ];
        if ($trx->pickup_by == 'Customer') {
            $result += [
                'transaction_type' => 'Pickup Order',
                'pickup_type' => $trx->pickup_type
            ];
            if ($trx->pickup_type == 'set time') {
                $result += [
                    'pickup_at' => date('H:i',strtotime($trx->pickup_at))
                ];
            }
        } else {
            if ($trx->pickup_by == 'GO-SEND') {
                $address = TransactionPickupGoSend::where('id_transaction_pickup',$trx->id_transaction_pickup)->first();
                $result += [
                    'transaction_type' => 'Delivery Order',
                    'courier' => 'gosend',
                    'destination' => [
                        'name' => $address->destination_address_name?:$address->destination_short_address,
                        'short_address' => $address->destination_short_address,
                        'address' => $address->destination_address,
                        'description' => $address->destination_note,
                        'latitude' => $address->destination_latitude,
                        'longitude' => $address->destination_longitude,
                    ]
                ];
            } else {
                $address = TransactionPickupWehelpyou::where('id_transaction_pickup', $trx->id_transaction_pickup)->first();
                $result += [
                    'transaction_type' => 'Delivery Order',
                    'courier' => $address->courier,
                    'destination' => [
                        'name' => $address->address_name ?: ($address->short_address ?: $address->receiver_address),
                        'short_address' => $address->short_address ?: $address->receiver_address,
                        'address' => $address->receiver_address,
                        'description' => $address->receiver_notes,
                        'latitude' => $address->receiver_latitude,
                        'longitude' => $address->receiver_longitude,
                    ]
                ];
            }

            if (!$result['destination']['name']) {
                $ua = UserAddress::where(['id_user' => $trx->id_user, 'latitude'=>$address->destination_latitude, 'longitude' => $address->destination_longitude])->first();
                if ($ua) {
                    $result['destination']['name'] = $ua->name?:$ua->short_address;
                    $result['destination']['short_address'] = $ua->short_address;
                }
            }
        }
        return MyHelper::checkGet($result);
    }

    public function transactionPointDetail(Request $request) {
        $id     = $request->json('id');
        $select = [];
        $data   = LogPoint::where('id_log_point', $id)->first();

        if ($data['source'] == 'Transaction') {
            $select = Transaction::with('outlet')->where('id_transaction', $data['id_reference'])->first();

            $data['date'] = $select['transaction_date'];
            $data['type'] = 'trx';
            $data['outlet'] = $select['outlet']['outlet_name'];
            if ($select['trasaction_type'] == 'Offline') {
                $data['online'] = 0;
            } else {
                $data['online'] = 1;
            }

        } else {
            $select = DealsUser::with('dealVoucher.deal')->where('id_deals_user', $data['id_reference'])->first();
            $data['type']   = 'voucher';
            $data['date']   = date('Y-m-d H:i:s', strtotime($select['claimed_at']));
            $data['outlet'] = $select['outlet']['outlet_name'];
            $data['online'] = 1;
        }

        $data['detail'] = $select;
        return response()->json(MyHelper::checkGet($data));
    }

    public function transactionBalanceDetail(Request $request) {
        $id     = $request->json('id');
        $select = [];
        $data   = LogBalance::where('id_log_balance', $id)->first();
        // dd($data);
        $statusTrx = ['Online Transaction', 'Transaction', 'Transaction Failed', 'Rejected Order', 'Rejected Order Midtrans', 'Rejected Order Point', 'Rejected Order Ovo', 'Reversal'];
        if (in_array($data['source'], $statusTrx)) {
            $select = Transaction::select(DB::raw('transactions.*,sum(transaction_products.transaction_product_qty) item_total'))->leftJoin('transaction_products','transactions.id_transaction','=','transaction_products.id_transaction')->with('outlet')->where('transactions.id_transaction', $data['id_reference'])->groupBy('transactions.id_transaction')->first();

            $data['date'] = $select['transaction_date'];
            $data['type'] = 'trx';
            $data['item_total'] = $select['item_total'];
            $data['outlet'] = $select['outlet']['outlet_name'];
            if ($select['trasaction_type'] == 'Offline') {
                $data['online'] = 0;
            } else {
                $data['online'] = 1;
            }
            $data['detail'] = $select;

            $result = [
                'type'                          => $data['type'],
                'id_log_balance'                => $data['id_log_balance'],
                'id_transaction'                => $data['detail']['id_transaction'],
                'transaction_receipt_number'    => $data['detail']['transaction_receipt_number'],
                'transaction_date'              => date('d M Y H:i', strtotime($data['detail']['transaction_date'])),
                'balance'                       => MyHelper::requestNumber($data['balance'], '_POINT'),
                'transaction_grandtotal'        => MyHelper::requestNumber($data['detail']['transaction_grandtotal'], '_CURRENCY'),
                'transaction_cashback_earned'   => MyHelper::requestNumber($data['detail']['transaction_cashback_earned'], '_POINT'),
                'name'                          => $data['detail']['outlet']['outlet_name'],
                'title'                         => 'Total Payment'
            ];
        } elseif ($data['source'] == 'Quest Benefit') {
            $quest = Quest::find($data['id_reference']);
            $result = [
                'type'                          => 'quest',
                'id_log_balance'                => $data['id_log_balance'],
                'id_quest'                      => $data['id_reference'],
                'transaction_date'              => date('d M Y H:i', strtotime($data['created_at'])),
                'balance'                       => '+' . MyHelper::requestNumber($data['balance'], '_POINT'),
                'title'                         => $quest['name'] ?? 'Misi tidak diketahui',
            ];
        } else {
            $select = DealsUser::with('dealVoucher.deal')->where('id_deals_user', $data['id_reference'])->first();
            $data['type']   = 'voucher';
            $data['date']   = date('Y-m-d H:i:s', strtotime($select['claimed_at']));
            $data['outlet'] = $select['outlet']['outlet_name'];
            $data['online'] = 1;
            $data['detail'] = $select;

            $usedAt = '';
            $status = 'UNUSED';
            if($data['detail']['used_at'] != null){
                $usedAt = date('d M Y H:i', strtotime($data['detail']['used_at']));
                $status = 'USED';
            }

            $price = 0;
            if($data['detail']['voucher_price_cash'] != NULL){
                $price = MyHelper::requestNumber($data['detail']['voucher_price_cash'],'_CURRENCY');
            }elseif($data['detail']['voucher_price_point'] != NULL){
                $price = MyHelper::requestNumber($data['detail']['voucher_price_point'],'_POINT').' points';
            }

            $result = [
                'type'                          => $data['type'],
                'id_log_balance'                => $data['id_log_balance'],
                'id_deals_user'                 => $data['detail']['id_deals_user'],
                'status'                        => $status,
                'used_at'                       => $usedAt,
                'transaction_receipt_number'    => implode('', [strtotime($data['date']), $data['detail']['id_deals_user']]),
                'transaction_date'              => date('d M Y H:i', strtotime($data['date'])),
                'balance'                       => MyHelper::requestNumber($data['balance'], '_POINT'),
                'transaction_grandtotal'        => $price,
                'transaction_cashback_earned'   => null,
                'name'                          => 'Buy Voucher',
                'title'                         => $data['detail']['dealVoucher']['deal']['deals_title']
            ];
        }

        return response()->json(MyHelper::checkGet($result));
    }

    public function setting($value) {
        $setting = Setting::where('key', $value)->first();

        if (empty($setting->value)) {
            return response()->json(['Setting Not Found']);
        }

        return $setting->value;
    }

    public function transactionHistory(TransactionHistory $request) {
        if($request->json('phone') == "") {
            $data = $request->user();
            $id   = $data['id'];
        } else {
            $user = User::where('phone', $request->json('phone'))->get->first();
            $id = $user['id'];
        }

        $transaction = Transaction::where('id_user', $id)->with('user', 'productTransaction', 'user.city', 'user.city.province', 'productTransaction.product', 'productTransaction.product.category', 'productTransaction.product.photos', 'productTransaction.product.discount')->get()->toArray();

        return response()->json(MyHelper::checkGet($transaction));
    }

    public function getProvince(GetProvince $request) {
        $id_province = $request->json('id_province');
        if (isset($id_province)) {
            $province = Province::where('id_province', $id_province)->orderBy('id_province', 'ASC');
        } else {
            $province = Province::orderBy('id_province', 'ASC');
        }

        $province = $province->with('cities')->get();

        return response()->json(MyHelper::checkGet($province));

    }

    public function getCity(GetCity $request) {
        $id_city = $request->json('id_city');
        if (isset($id_city)) {
            $city = City::where('id_city', $id_city)->orderBy('id_city', 'ASC');
        } else {
            $city = City::orderBy('id_city', 'ASC');
        }

        $city = $city->with('province')->get();

        return response()->json(MyHelper::checkGet($city));

    }

    public function getSubdistrict(GetSub $request) {
        $id_city = $request->json('id_city');
        $id_subdistrict = $request->json('id_subdistrict');

        $subdistrict = MyHelper::urlTransaction('https://pro.rajaongkir.com/api/subdistrict?city='.$id_city.'&id='.$id_subdistrict, 'GET', '', 'application/json');

        if ($subdistrict->rajaongkir->status->code == 200) {
            $subdistrict = $subdistrict->rajaongkir->results;
        }

        return response()->json(MyHelper::checkGet($subdistrict));
    }

    public function getAddress(GetAddress $request) {
        $id = $request->user()->id;

        if (!$id) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['User Not Found']
            ]);
        }

        $address = UserAddress::select('id_user_address','name','short_address','address','type','latitude','longitude','description','favorite','id_subdistrict')->where('id_user', $id)->orderBy('favorite', 'DESC')->orderBy('updated_at', 'DESC');
        if(is_numeric($request->json('favorite'))){
            $address->where('favorite',$request->json('favorite'));
            if(!$request->json('favorite')){
                $address->whereNull('type');
            }
        }
        $address = $address->get()->toArray();
        $result = [
        ];
        $misc = [];
        foreach ($address as $key => $adr) {
            switch (strtolower($adr['type'])) {
                case 'home':
                    $adr['position'] = 1;
                    $result[] = $adr;
                    break;

                case 'work':
                    $adr['position'] = 2;
                    $result[] = $adr;
                    break;

                case 'other':
                    $adr['position'] = 3;
                    $result[] = $adr;
                    break;

                default:
                    $adr['position'] = $key+3;
                    $result[] = $adr;
                    break;
            }
        }
        usort($result, function ($a, $b){
            return $a['position'] <=> $b['position'];
        });

        return [
            'status' => 'success',
            'result' => $result
        ];
    }

    public function getNearbyAddress(GetNearbyAddress $request) {
        $id = $request->user()->id;
        $distance = Setting::select('value')->where('key','history_address_max_distance')->pluck('value')->first()?:50;
        $maxmin = MyHelper::getRadius($request->json('latitude'),$request->json('longitude'),$distance);
        $latitude = $request->json('latitude');
        $longitude = $request->json('longitude');

        $gmaps = $this->getListLocation($request);

        if($gmaps['status'] === 'OK'){
            if ($gmaps['send_gmaps_data'] ?? false) {
                MyHelper::sendGmapsData($gmaps['results']);
            }
            $gmaps = $gmaps['results'];
        }else{
            $gmaps = [];
        };

        $maxmin = MyHelper::getRadius($latitude,$longitude,$distance);
        $user_address = UserAddress::select('id_user_address','short_address','address','latitude','longitude','description','favorite')->where('id_user',$id)
            ->whereBetween('latitude',[$maxmin['latitude']['min'],$maxmin['latitude']['max']])
            ->whereBetween('longitude',[$maxmin['longitude']['min'],$maxmin['longitude']['max']])
            ->take(10);

        if($keyword = $request->json('keyword')){
            $user_address->where(function($query) use ($keyword) {
                $query->where('name',$keyword);
                $query->orWhere('address',$keyword);
                $query->orWhere('short_address',$keyword);
            });
        }

        $user_address = $user_address->get()->toArray();

        $saved = array_map(function($i){
            return [
                'latitude' => $i['latitude'],
                'longitude' => $i['longitude']
            ];
        },$user_address);

        foreach ($gmaps as $key => &$gmap){
            $coor = [
                'latitude' => number_format($gmap['geometry']['location']['lat'],8),
                'longitude' => number_format($gmap['geometry']['location']['lng'],8)
            ];
            if(in_array($coor, $saved)){
                unset($gmaps[$key]);
            }
            $gmap = [
                'id_user_address' => 0,
                'short_address' => $gmap['name'],
                'address' => $gmap['vicinity']??'',
                'latitude' => $coor['latitude'],
                'longitude' => $coor['longitude'],
                'description' => '',
                'favorite' => 0
            ];
        }

        // mix history and gmaps
        $user_address = array_merge($user_address,$gmaps);

        // reorder based on distance
        usort($user_address,function(&$a,&$b) use ($latitude,$longitude){
            return MyHelper::count_distance($latitude,$longitude,$a['latitude'],$a['longitude']) <=> MyHelper::count_distance($latitude,$longitude,$b['latitude'],$b['longitude']);
        });

        $selected_address = null;
        foreach ($user_address as $key => $addr) {
            if ($addr['favorite']) {
                $selected_address = $addr;
                break;
            }
            if ($addr['id_user_address']) {
                $selected_address = $addr;
                continue;
            }
            if ($key == 0) {
                $selected_address = $addr;
            }
        }

        if(!$selected_address){
            $selected_address = $user_address[0]??null;
        }
        // apply limit;
        // $max_item = Setting::select('value')->where('key','history_address_max_item')->pluck('value')->first()?:10;
        // $user_address = array_splice($user_address,0,$max_item);
        $result = [];
        if($user_address){
            $result = [
                'default' => $selected_address,
                'nearby' => $user_address
            ];
        }
        return response()->json(MyHelper::checkGet($result));
    }

    public function getDefaultAddress (GetNearbyAddress $request) {
        $id = $request->user()->id;
        $distance = Setting::select('value')->where('key','history_address_max_distance')->pluck('value')->first()?:50;
        $maxmin = MyHelper::getRadius($request->json('latitude'),$request->json('longitude'),$distance);
        $latitude = $request->json('latitude');
        $longitude = $request->json('longitude');

        $maxmin = MyHelper::getRadius($latitude,$longitude,$distance);
        $user_address = UserAddress::select('id_user_address','short_address','address','latitude','longitude','description','favorite')->where('id_user',$id)
            ->whereBetween('latitude',[$maxmin['latitude']['min'],$maxmin['latitude']['max']])
            ->whereBetween('longitude',[$maxmin['longitude']['min'],$maxmin['longitude']['max']])
            ->take(10);

        if($keyword = $request->json('keyword')){
            $user_address->where(function($query) use ($keyword) {
                $query->where('name',$keyword);
                $query->orWhere('address',$keyword);
                $query->orWhere('short_address',$keyword);
            });
        }

        $user_address = $user_address->get()->toArray();
        if (!$user_address) {
            $gmaps = $this->getListLocation($request);

            if($gmaps['status'] === 'OK'){
                if ($gmaps['send_gmaps_data'] ?? false) {
                    MyHelper::sendGmapsData($gmaps['results']);
                }
                $gmaps = $gmaps['results'];
            }else{
                return MyHelper::checkGet([]);
            };

            foreach ($gmaps as $key => &$gmap){
                $coor = [
                    'latitude' => number_format($gmap['geometry']['location']['lat'],8),
                    'longitude' => number_format($gmap['geometry']['location']['lng'],8)
                ];
                $gmap = [
                    'id_user_address' => 0,
                    'short_address' => $gmap['name'],
                    'address' => $gmap['vicinity']??'',
                    'latitude' => $coor['latitude'],
                    'longitude' => $coor['longitude'],
                    'description' => '',
                    'favorite' => 0
                ];
            }
            // mix history and gmaps
            $user_address = array_merge($user_address,$gmaps);
        }

        // reorder based on distance
        usort($user_address,function(&$a,&$b) use ($latitude,$longitude){
            return MyHelper::count_distance($latitude,$longitude,$a['latitude'],$a['longitude']) <=> MyHelper::count_distance($latitude,$longitude,$b['latitude'],$b['longitude']);
        });

        foreach ($user_address as $key => $addr) {
            if ($addr['favorite']) {
                $selected_address = $addr;
                break;
            }
            if ($addr['id_user_address']) {
                $selected_address = $addr;
                continue;
            }
            if ($key == 0) {
                $selected_address = $addr;
            }
        }

        if(!$selected_address){
            $selected_address = $user_address[0]??null;
        }
        // apply limit;
        // $max_item = Setting::select('value')->where('key','history_address_max_item')->pluck('value')->first()?:10;
        // $user_address = array_splice($user_address,0,$max_item);
        $result = [];
        if($user_address){
            $result = [
                'default' => $selected_address
            ];
        }
        return response()->json(MyHelper::checkGet($result));
    }

    public function getListLocation($request)
    {
        $key_maps = env('LOCATION_PRIMARY_KEY');
        $locationUrl = env('LOCATION_PRIMARY_URL');
        if (env('LOCATION_PRIMARY_KEY_TOTAL')) {
            $weekNow = date('W') % env('LOCATION_PRIMARY_KEY_TOTAL');
            $key_maps = env('LOCATION_PRIMARY_KEY'.$weekNow, $key_maps);
        }

        $param = [
            'key'       => $key_maps,
            'location'  => sprintf('%s,%s',$request->json('latitude'),$request->json('longitude')),
            'rankby'    => 'distance'
        ];

        if($request->json('keyword')){
            $param['keyword'] = $request->json('keyword');
        }

        $gmaps = MyHelper::get($locationUrl.'?'.http_build_query($param));

        if ($gmaps['status'] !== 'OK'
            || ($gmaps['status'] === 'OK' && count($gmaps['results']) < env('LOCATION_MIN_TOTAL'))
        ) {
            // get place from google maps . max 20
            $key_maps = env('LOCATION_SECONDARY_KEY');
            $locationUrl = env('LOCATION_SECONDARY_URL');
            if (env('LOCATION_SECONDARY_KEY_TOTAL')) {
                $weekNow = date('W') % env('LOCATION_SECONDARY_KEY_TOTAL');
                $key_maps = env('LOCATION_SECONDARY_KEY'.$weekNow, $key_maps);
            }
            $param = [
                'key'=>$key_maps,
                'location'=>sprintf('%s,%s',$request->json('latitude'),$request->json('longitude')),
                'rankby'=>'distance'
            ];
            if($request->json('keyword')){
                $param['keyword'] = $request->json('keyword');
            }
            $gmaps = MyHelper::get($locationUrl.'?'.http_build_query($param));
        }

        $gmaps['send_gmaps_data'] = (strpos($locationUrl, 'google') !== false) ? true : false;

        return $gmaps;
    }

    public function detailAddress(GetAddress $request) {
        $id = $request->user()->id;

        $address = UserAddress::where(['id_user'=> $id,'id_user_address'=>$request->id_user_address])->orderBy('id_user_address', 'DESC')->get()->toArray();
        return response()->json(MyHelper::checkGet($address));
    }

    public function addAddress(AddAddress $request) {
        $post = $request->json()->all();
        $user = $request->user();

        $data = [
            'id_user' => $user->id,
            'name' => $post['name'] ?? $post['short_address'],
            'short_address' => $post['short_address'] ?? null,
            'address' => $post['address'] ?? null,
            'description' => $post['description'] ?? null,
            'latitude' => number_format($post['latitude'],8),
            'longitude' => number_format($post['longitude'],8),
            'id_subdistrict' => $post['id_subdistrict'] ?? null,
            'favorite' => $post['favorite'] ?? 0
        ];

        $exists = UserAddress::where('id_user',$user->id)->where('name',$data['name'])->exists();
        if($exists){
            return ['status'=>'fail','messages'=>['Alamat dengan nama yang sama sudah ada']];
        }

        $create = UserAddress::create($data);
        if ($create && $create->favorite) {
            UserAddress::where('id_user',$user->id)->where('id_user_address','!=',$create->id_user_address)->update(['favorite' => 0]);
        }

        return response()->json(MyHelper::checkCreate($create));
    }

    public function updateAddress (UpdateAddress $request) {
        $post = $request->json()->all();
        $user = $request->user();
        $data['id_user'] = $request->user()->id;

        if (empty($data['id_user'])) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['User not found']
            ]);
        }

        $userAddress =  UserAddress::where('id_user_address', $post['id_user_address'])->first();
        if (!$userAddress) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Address not found']
            ]);
        }

        $data['name']           = $post['name'] ?? $userAddress->name;
        $data['address']        = $post['address'] ?? $userAddress->address;
        $data['short_address']  = $post['short_address'] ?? $userAddress->short_address;
        $data['description']    = $post['description'] ?? $userAddress->description;
        $data['latitude']       = $post['latitude'] ?? $userAddress->latitude;
        $data['longitude']      = $post['longitude'] ?? $userAddress->longitude;
        $data['type']           = null;
        $data['favorite']       = $post['favorite'] ?? $userAddress->favorite;
        $data['id_subdistrict'] = $post['id_subdistrict'] ?? $userAddress->id_subdistrict;

        $update = UserAddress::where('id_user_address', $post['id_user_address'])->update($data);
        if ($data['favorite']) {
            UserAddress::where('id_user',$user->id)->where('id_user_address','!=',$post['id_user_address'])->update(['favorite' => 0]);
        }
        return response()->json(MyHelper::checkUpdate($update));
    }

    public function updateAddressFavorite(Request $request)
    {
        $user = $request->user();
        $userAddress =  UserAddress::where('id_user_address', $request->id_user_address)->first();
        if (!$userAddress) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Address not found']
            ]);
        }

        $update = $userAddress->update(['favorite' => 1]);
        UserAddress::where('id_user',$user->id)->where('id_user_address','!=',$request->id_user_address)->update(['favorite' => 0]);

        return MyHelper::checkUpdate($update);
    }

    public function deleteAddress (DeleteAddress $request) {
        $id = $request->json('id_user_address');

        $check = UserAddress::where('id_user_address', $id)->first();
        if (empty($check)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Address not found']
            ]);
        }

        $check->delete();

        return response()->json(MyHelper::checkDelete($check));
    }

    public function getCourier(Request $request) {
        $courier = Courier::orderBy('id_courier', 'ASC')->get();

        return response()->json(MyHelper::checkGet($courier));
    }

    public function getShippingFee(TransactionShipping $request) {
        $post = $request->json()->all();

        if (isset($post['from'])) {
            $from = $post['from'];
        }

        if (isset($post['fromType'])) {
            $fromType = $post['fromType'];
        }

        if (isset($post['to'])) {
            $to = $post['to'];
        }

        if (isset($post['toType'])) {
            $toType = $post['toType'];
        }

        if (isset($post['weight'])) {
            $weight = $post['weight'];
        }

        if (isset($post['courier'])) {
            $courier = $post['courier'];
        }

        $data = "origin=".$from."&originType=".$fromType."&destination=".$to."&destinationType=".$toType."&weight=".$weight."&courier=".$courier;

        $shiping = MyHelper::urlTransaction('http://pro.rajaongkir.com/api/cost', 'POST', $data, 'application/x-www-form-urlencoded');

        if (isset($shiping->rajaongkir->status->code) && $shiping->rajaongkir->status->code == 200) {
            if (!empty($shiping->rajaongkir->results[0]->costs)) {
                $data = [
                    'status'    => 'success',
                    'result'    => $shiping->rajaongkir->results[0]->costs
                ];
            } else {
                $data = [
                    'status'      => 'empty',
                    'messages'    => ['Maaf, pengiriman ke kota tersebut belum tersedia']
                ];
            }

        } elseif (isset($shiping->rajaongkir->status->code) && $shiping->rajaongkir->status->code == 400) {
            $data = [
                'status'    => 'fail',
                'messages'    => [$shiping->rajaongkir->status->description]
            ];
        } else {
            $data = [
                'status'    => 'error',
                'messages'    => ['Data invalid!!']
            ];
        }

        return response()->json($data);
    }

    public function transactionVoid(Request $request) {
        $id = $request->json('transaction_receipt_number');

        $transaction = Transaction::where('transaction_receipt_number', $id)->first();
        if (empty($transaction)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Transaction not found !!']
            ]);
        }

        MyHelper::updateFlagTransactionOnline($transaction, 'cancel');

        $transaction->void_date = date('Y-m-d H:i:s');
        $transaction->save();

        if (!$transaction) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Void transaction failure !!']
            ]);
        }

        return response()->json([
            'status'    => 'success',
            'messages'  => ['Void transaction success']
        ]);
    }

    public function transactionFinish(Request $request) {
        $result = $request->input('result_data');
        $result = json_decode($result);
        echo $result->status_message . '<br>';
        echo 'RESULT <br><pre>';
        var_dump($result);
        echo '</pre>' ;
    }

    public function transactionApprove(Request $request) {
        $json_result = file_get_contents('php://input');
        $result = json_decode($json_result);

        $url = 'https://api.sandbox.midtrans.com/v2/'.$result->order_id.'/status';
    }

    public function transactionCancel(Request $request) {
        return 'cancel';
    }

    public function transactionError(Request $request) {
        return 'error';
    }

    public function transactionNotif(Request $request) {
        $json_result = file_get_contents('php://input');
        $result = json_decode($json_result);

        DB::beginTransaction();
        $checkTransaction = Transaction::where('transaction_receipt_number', $result->order_id)->first();

        if (!$checkTransaction) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Receipt number not available']
            ]);
        }

        if (count($checkTransaction) > 0) {
            $url = 'https://api.sandbox.midtrans.com/v2/'.$result->order_id.'/status';

            $getStatus = $this->getToken(false, $url, false);

            if ($getStatus->status_code != 200) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Cannot access this transaction']
                ]);
            }

            if (!empty($getStatus)) {
                $masked_card        = isset($getStatus['masked_card']) ? $getStatus['masked_card'] : null;
                $approval_code      = isset($getStatus['approval_code']) ? $getStatus['approval_code'] : null;
                $bank               = isset($getStatus['bank']) ? $getStatus['bank'] : null;
                $eci                = isset($getStatus['eci']) ? $getStatus['eci'] : null;
                $transaction_time   = isset($getStatus['transaction_time']) ? $getStatus['transaction_time'] : null;
                $payment_type       = isset($getStatus['payment_type']) ? $getStatus['payment_type'] : null;
                $signature_key      = isset($getStatus['signature_key']) ? $getStatus['signature_key'] : null;
                $status_code        = isset($getStatus['status_code']) ? $getStatus['status_code'] : null;
                $vt_transaction_id  = isset($getStatus['vt_transaction_id']) ? $getStatus['vt_transaction_id'] : null;
                $transaction_status = isset($getStatus['transaction_status']) ? $getStatus['transaction_status'] : null;
                $fraud_status       = isset($getStatus['fraud_status']) ? $getStatus['fraud_status'] : null;
                $status_message     = isset($getStatus['status_message']) ? $getStatus['status_message'] : null;

                if ($getStatus->status_code == 200) {
                    if ($transaction_status == 'capture') {
                        $checkTransaction->transaction_payment_status = 'Success';

                        if (!empty($checkTransaction->id_user)) {
                            $dataPoint = [
                                'id_user'      => $checkTransaction->id_user,
                                'point'        => $checkTransaction->transaction_point_earned,
                                'id_reference' => $checkTransaction->id_transaction,
                                'source'       => 'transaction'
                            ];

                            $insertPoint = PointLog::create($dataPoint);

                            if (!$insertPoint) {
                                DB::rollback();
                                return response()->json([
                                    'status'    => 'fail',
                                    'messages'  => ['insert point failed']
                                ]);
                            }
                        }
                    } else {
                        $checkTransaction->transaction_payment_status = ucwords($transaction_status);
                    }

                    $checkTransaction->transaction_payment_method = $payment_type;
                    $checkTransaction->save();

                    if (!$checkTransaction) {
                        DB::rollback();
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => ['Update status payment failed']
                        ]);
                    }
                }


                $dataPayment = [
                    'id_transaction'     => $checkTransaction->id_transaction,
                    'masked_card'        => $masked_card,
                    'approval_code'      => $approval_code,
                    'bank'               => $bank,
                    'eci'                => $eci,
                    'transaction_time'   => $transaction_time,
                    'gross_amount'       => $getStatus->gross_amount,
                    'order_id'           => $getStatus->order_id,
                    'payment_type'       => $payment_type,
                    'signature_key'      => $signature_key,
                    'status_code'        => $status_code,
                    'vt_transaction_id'  => $vt_transaction_id,
                    'transaction_status' => $transaction_status,
                    'fraud_status'       => $fraud_status,
                    'status_message'     => $status_message,
                ];

                $insertPayment = TransactionPayment::create($dataPayment);

                if (!$insertPayment) {
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Transaction payment cannot be create']
                    ]);
                }

                DB::commit();
                return $response->json([
                    'status'    => 'success',
                    'result'    => $dataPayment
                ]);

            }
        }
    }

    public function sendTransaction($data) {
        $tes = MyHelper::curlData('http://localhost/natasha-api/public/api/transaction/tes2', $data);
    }

    public function testing() {
        $testing = MyHelper::logCount('089674657270', 'point');
        return $testing;
    }

    public function insertUserTrxProduct($data){
        foreach ($data as $key => $value) {
            # code...
            $check = UserTrxProduct::where('id_user', $value['id_user'])->where('id_product', $value['id_product'])->first();

            if(empty($check)){
                $insertData = UserTrxProduct::create($value);
            }else{
                $value['product_qty'] = $check->product_qty + $value['product_qty'];
                $insertData = $check->update($value);
            }

            if(!$insertData){
                return 'fail';
            }
        }
        return 'success';
    }

    public function shippingCostGoSend(ShippingGosend $request){
        $post = $request->json()->all();

        $outlet = Outlet::find($post['id_outlet']);
        if(!$outlet){
            return response()->json(['status' => 'fail', 'messages' => ['Outlet not found.']]);
        }

        $origin['latitude'] = $outlet['latitude'];
        $origin['longitude'] = $outlet['longitude'];
        $shipping = GoSend::getPrice($origin, $post['destination']);

        if(isset($shipping['Instant']['price']['total_price'])){
            $shippingCost = $shipping['Instant']['price']['total_price'];
            $shippingFree = null;
            $isFree = '0';
            $setting = Setting::where('key', 'like', '%free_delivery%')->get();
            if($setting){
                $freeDev = [];
                foreach($setting as $dataSetting){
                    $freeDev[$dataSetting['key']] = $dataSetting['value'];
                }

                if(isset($freeDev['free_delivery_type'])){
                    if($freeDev['free_delivery_type'] == 'free' || isset($freeDev['free_delivery_nominal'])){
                        if(isset($freeDev['free_delivery_requirement_type']) && $freeDev['free_delivery_requirement_type'] == 'total item' && isset($freeDev['free_delivery_min_item'])){
                            if($post['total_item'] >= $freeDev['free_delivery_min_item']){
                                $isFree = '1';
                            }
                        }elseif(isset($freeDev['free_delivery_requirement_type']) && $freeDev['free_delivery_requirement_type'] == 'subtotal' && isset($freeDev['free_delivery_min_subtotal'])){
                            if($post['subtotal'] >= $freeDev['free_delivery_min_subtotal']){
                                $isFree = '1';
                            }
                        }

                        if($isFree == '1'){
                            if($freeDev['free_delivery_type'] == 'free'){
                                $shippingFree = 'FREE';
                            }else{
                                $shippingFree = $freeDev['free_delivery_nominal'];
                            }
                        }
                    }
                }

            }

            $result['shipping_cost_go_send'] = $shippingCost;

            if($shippingFree != null){
                if($shippingFree == 'FREE'){
                    $result['shipping_cost_discount'] = $shippingCost;
                    $result['is_free'] = 'yes';
                    $result['shipping_cost'] = 'FREE';
                }else{
                    if($shippingFree > $shippingCost){
                        $result['shipping_cost_discount'] = $shippingCost;
                        $result['is_free'] = 'no';
                        $result['shipping_cost'] = 0;
                    }else{
                        $result['shipping_cost_discount'] = (int)$shippingFree;
                        $result['is_free'] = 'no';
                        $result['shipping_cost'] = $shippingCost - $shippingFree;
                    }
                }
            }else{
                $result['shipping_cost_discount'] = 0;
                $result['is_free'] = 'no';
                $result['shipping_cost'] = $shippingCost;
            }

            return response()->json([
                'status' => 'success',
                'result' => $result
            ]);
        }else{
            if(isset($shipping['status']) && $shipping['status'] == 'fail'){
                return response()->json($shipping);
            }
            return response()->json([
                'status' => 'fail',
                'messages' => [$shipping]
            ]);
        }

    }

    public function updateStatusInvalidTrx(Request $request){
        $post = $request->json()->all();
        $update = Transaction::where('id_transaction', $request['id_transaction'])->update(['transaction_flag_invalid' => $request['transaction_flag_invalid']]);

        if($request->user()->id){
            $insertLog = [
                'id_transaction' => $request['id_transaction'],
                'tansaction_flag' => $request['transaction_flag_invalid'],
                'updated_by' => $request->user()->id,
                'updated_date' => date('Y-m-d H:i:s')
            ];

            LogInvalidTransaction::create($insertLog);
        }

        return MyHelper::checkUpdate($update);
    }

    public function logInvalidFlag(Request $request){
        $post = $request->json()->all();

        $list = LogInvalidTransaction::join('transactions', 'transactions.id_transaction', 'log_invalid_transactions.id_transaction')
                ->join('users', 'users.id', 'log_invalid_transactions.updated_by')
                ->groupBy('log_invalid_transactions.id_transaction');

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(isset($row['subject'])){
                        if($row['subject'] == 'status'){
                            $list->where('transactions.transaction_flag_invalid', $row['operator']);
                        }

                        if($row['subject'] == 'receipt_number'){
                            if($row['operator'] == '='){
                                $list->where('transactions.transaction_receipt_number', $row['parameter']);
                            }else{
                                $list->where('transactions.transaction_receipt_number', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'updated_by'){
                            if($row['operator'] == '='){
                                $list->whereIn('id_log_invalid_transaction', function ($q) use($row){
                                    $q->select('l.id_log_invalid_transaction')
                                        ->from('log_invalid_transactions as l')
                                        ->join('users', 'users.id', 'l.updated_by')
                                        ->where('users.name', $row['parameter']);
                                });
                            }else{
                                $list->whereIn('id_log_invalid_transaction', function ($q) use($row){
                                    $q->select('l.id_log_invalid_transaction')
                                        ->from('log_invalid_transactions as l')
                                        ->join('users', 'users.id', 'l.updated_by')
                                        ->where('users.name', 'like', '%'.$row['parameter'].'%');
                                });
                            }
                        }
                    }
                }
            }else{
                $list->where(function ($subquery) use ($post){
                    foreach ($post['conditions'] as $row){
                        if(isset($row['subject'])){
                            if($row['subject'] == 'status'){
                                $subquery->orWhere('transactions.transaction_flag_invalid', $row['operator']);
                            }

                            if($row['subject'] == 'receipt_number'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('transactions.transaction_receipt_number', $row['parameter']);
                                }else{
                                    $subquery->orWhere('transactions.transaction_receipt_number', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'updated_by'){
                                if($row['operator'] == '='){
                                    $subquery->orWhereIn('id_log_invalid_transaction', function ($q) use($row){
                                        $q->select('l.id_log_invalid_transaction')
                                            ->from('log_invalid_transactions as l')
                                            ->join('users', 'users.id', 'l.updated_by')
                                            ->where('users.name', $row['parameter']);
                                    });
                                }else{
                                    $subquery->orWhereIn('id_log_invalid_transaction', function ($q) use($row){
                                        $q->select('l.id_log_invalid_transaction')
                                            ->from('log_invalid_transactions as l')
                                            ->join('users', 'users.id', 'l.updated_by')
                                            ->where('users.name', 'like', '%'.$row['parameter'].'%');
                                    });
                                }
                            }
                        }
                    }
                });
            }
        }

        $list = $list->paginate(30);

        return MyHelper::checkGet($list);
    }

    public function detailInvalidFlag(Request $request){
        $post = $request->json()->all();
        $list = LogInvalidTransaction::join('transactions', 'transactions.id_transaction', 'log_invalid_transactions.id_transaction')
            ->leftJoin('users', 'users.id', 'log_invalid_transactions.updated_by')
            ->where('log_invalid_transactions.id_transaction', $request['id_transaction'])
            ->select(DB::raw('DATE_FORMAT(log_invalid_transactions.updated_date, "%d %M %Y %H:%i") as updated_date'), 'users.name', 'log_invalid_transactions.tansaction_flag', 'transactions.transaction_receipt_number')
            ->get()->toArray();

        return MyHelper::checkGet($list);
    }

    public function retryRefund($id_transaction, &$errors = [], $manualRetry = false)
    {
        $trx = Transaction::where('transactions.id_transaction', $id_transaction)->join('transaction_multiple_payments', function($join) {
            $join->on('transaction_multiple_payments.id_transaction', 'transactions.id_transaction')
                ->whereIn('type', ['Midtrans', 'Shopeepay', 'Xendit']);
        })->first();
        if (!$trx) {
            $errors[] = 'Transaction Not Found';
            return false;
        }
        $result = true;
        switch ($trx->type) {
            case 'Midtrans':
                $payMidtrans = TransactionPaymentMidtran::where('id_transaction', $id_transaction)->first();
                if (!$payMidtrans) {
                    $errors[] = 'Model TransactionPaymentMidtran not found';
                    return false;
                }
                if($trx['refund_requirement'] > 0){
                    $refund = Midtrans::refundPartial($payMidtrans['vt_transaction_id'],['reason' => 'refund transaksi', 'amount' => (int)$trx['refund_requirement']]);
                }else{
                    $refund = Midtrans::refund($payMidtrans['vt_transaction_id'],['reason' => 'refund transaksi']);
                }

                if ($refund['status'] != 'success') {
                    Transaction::where('id_transaction', $id_transaction)->update(['failed_void_reason' => implode(', ', $refund['messages'] ?? [])]);
                    $errors = $refund['messages'] ?? [];
                    $result = false;
                } else {
                    Transaction::where('id_transaction', $id_transaction)->update(['need_manual_void' => 0]);
                }
                break;
            case 'Shopeepay':
                $payShopeepay = TransactionPaymentShopeePay::where('id_transaction', $id_transaction)->first();
                if (!$payShopeepay) {
                    $errors[] = 'Model TransactionPaymentShopeePay not found';
                    return false;
                }
                $refund = app($this->shopeepay)->refund($id_transaction, 'trx', $errors2);
                if (!$refund) {
                    Transaction::where('id_transaction', $id_transaction)->update(['failed_void_reason' => implode(', ', $errors2 ?: [])]);
                    $errors = $errors2;
                    $result = false;
                } else {
                    Transaction::where('id_transaction', $id_transaction)->update(['need_manual_void' => 0]);
                }
                break;

            case 'Xendit':
                $payXendit = TransactionPaymentXendit::where('id_transaction', $id_transaction)->first();
                if (!$payXendit) {
                    $errors[] = 'Model TransactionPaymentXendit not found';
                    return false;
                }
                if($trx['refund_requirement'] > 0){
                    $refund = app($this->xendit)->refund($id_transaction, 'trx', [
                        'amount' => (int) $trx['refund_requirement'],
                        'reason' => 'Refund partial'
                    ], $errors2);
                }else{
                    $refund = app($this->xendit)->refund($id_transaction, 'trx', [], $errors2);
                }

                if (!$refund) {
                    Transaction::where('id_transaction', $id_transaction)->update(['failed_void_reason' => implode(', ', $errors2 ?: [])]);
                    $errors = $errors2;
                    $result = false;
                } else {
                    Transaction::where('id_transaction', $id_transaction)->update(['need_manual_void' => 0]);
                }
                break;

            default:
                $errors[] = 'Unkown payment type '.$trx->type;
                return false;
        }
        return $result;
    }

    public function retry(Request $request)
    {
        $retry = $this->retryRefund($request->id_transaction, $errors);
        if ($retry) {
            return [
                'status' => 'success'
            ];
        } else {
            return [
                'status' => 'fail',
                'messages' => $errors ?? ['Something went wrong']
            ];
        }
    }

    public function outletServiceList(Request $request) {
        $user = $request->user();
        $bearerToken = $request->bearerToken();
        $tokenId = (new Parser())->parse($bearerToken)->getHeader('jti');
        $getOauth = OauthAccessToken::find($tokenId);
        $scopeUser = str_replace(str_split('[]""'),"",$getOauth['scopes']);

        $list = Transaction::where('transaction_from', 'outlet-service')
                ->join('transaction_outlet_services','transactions.id_transaction', 'transaction_outlet_services.id_transaction')
                ->where('id_user', $user->id)
                ->select('transactions.*', 'transaction_outlet_services.*', 'transactions.reject_at')
                ->orderBy('transaction_date', 'desc')
                ->with('outlet.brands', 'products', 'transaction_outlet_service', 'user_feedbacks');

        if ($scopeUser == 'apps') {
            switch (strtolower($request->status)) {
                case 'unpaid':
                    $list->where('transaction_payment_status','Pending');
                    break;

                case 'ongoing':
                    $list->whereNull('transaction_outlet_services.completed_at')
                    ->where('transaction_payment_status','Completed')
                    ->whereNull('transactions.reject_at');
                    break;

                case 'complete':
                    $list->where(function($q) {
                        $q->whereNotNull('transaction_outlet_services.completed_at')
                        ->orWhere('transaction_payment_status','Cancelled')
                        ->orWhereNotNull('transactions.reject_at');
                    });
                    break;
                
                default:
                    // code...
                    break;
            }
        } else {
            $list->where(function ($q) {
                $q->whereNull('transaction_outlet_services.completed_at')
                    ->orWhere('transactions.show_rate_popup', 1);
            })->where('scope','web-apps');
        }


        $list = $list->paginate(10)->toArray();

        $resData = [];
        foreach ($list['data'] ?? [] as $val) {

            $outlet = [
                'id_outlet' => $val['outlet']['id_outlet'],
                'outlet_code' => $val['outlet']['outlet_code'],
                'outlet_name' => $val['outlet']['outlet_name'],
                'outlet_latitude' => $val['outlet']['outlet_latitude'],
                'outlet_longitude' => $val['outlet']['outlet_longitude']
            ];

            $brand = null;
            if(!empty($val['outlet']['brands'][0])){
                $brand = [
                    'id_brand' => $val['outlet']['brands'][0]['id_brand'],
                    'brand_code' => $val['outlet']['brands'][0]['code_brand'],
                    'brand_name' => $val['outlet']['brands'][0]['name_brand'],
                    'brand_logo' => $val['outlet']['brands'][0]['logo_brand'],
                    'brand_logo_landscape' => $val['outlet']['brands'][0]['logo_landscape_brand']
                ];
            }

            $orders = [];
            foreach ($val['products'] as $product) {
                $orders[] = [
                    'product_name' => $product['product_name'],
                    'transaction_product_qty' => $product['pivot']['transaction_product_qty'],
                    'transaction_product_price' => $product['pivot']['transaction_product_price'],
                    'transaction_product_subtotal' => $product['pivot']['transaction_product_subtotal']
                ];
            }

            $cancelReason = null;
            if ($val['transaction_payment_status'] == 'Pending') {
                $status = 'unpaid';
            } elseif ($val['transaction_payment_status'] == 'Cancelled') {
                $status = 'cancelled';
                $cancelReason = 'Pembayaran gagal';
            } elseif (empty($val['completed_at']) && $val['transaction_payment_status'] == 'Completed') {
                $status = 'ongoing';
            } else {
                $status = 'completed';
            }

            if ($val['reject_at']) {
                $status = 'cancelled';
                $cancelReason = $val['reject_reason'];
            }

            $resData[] = [
                'id_transaction' => $val['id_transaction'],
                'transaction_receipt_number' => $val['transaction_receipt_number'],
                'qrcode' => 'https://quickchart.io/qr?text=' . str_replace('#', '', $val['transaction_receipt_number']) . '&margin=0&size=250',
                'transaction_date' => $val['transaction_date'],
                'customer_name' => $val['transaction_outlet_service']['customer_name'],
                'color' => (!empty($val['outlet']['brands'][0]['color_brand']) ? $val['outlet']['brands'][0]['color_brand'] : null),
                'status' => $status,
                'cancel_reason' => $cancelReason,
                'show_rate_popup' => $val['show_rate_popup'],
                'outlet' => $outlet,
                'brand' => $brand,
                'order' => $orders
            ];
        }

        $list['data'] = $resData;
        return MyHelper::checkGet($list);
    }

    public function outletServiceListV2(Request $request) {
        $user = $request->user();
        $bearerToken = $request->bearerToken();
        $tokenId = (new Parser())->parse($bearerToken)->getHeader('jti');
        $getOauth = OauthAccessToken::find($tokenId);
        $scopeUser = str_replace(str_split('[]""'),"",$getOauth['scopes']);

        $list = Transaction::where('transaction_from', 'outlet-service')
                ->join('transaction_outlet_services','transactions.id_transaction', 'transaction_outlet_services.id_transaction')
                ->where('id_user', $user->id)
                ->select('transactions.*', 'transaction_outlet_services.*', 'transactions.reject_at')
                ->orderBy('transaction_date', 'desc')
                ->with('outlet.brands', 'products', 'transaction_outlet_service', 'user_feedbacks','transaction_products.transaction_product_service','transaction_products.product');

        if ($scopeUser == 'apps') {
            switch (strtolower($request->status)) {
                case 'unpaid':
                    $list->where('transaction_payment_status','Pending');
                    break;

                case 'ongoing':
                    $list->whereNull('transaction_outlet_services.completed_at')
                    ->where('transaction_payment_status','Completed')
                    ->whereNull('transactions.reject_at');
                    break;

                case 'complete':
                    $list->where(function($q) {
                        $q->whereNotNull('transaction_outlet_services.completed_at')
                        ->orWhere('transaction_payment_status','Cancelled')
                        ->orWhereNotNull('transactions.reject_at');
                    });
                    break;
                
                default:
                    // code...
                    break;
            }
        } else {
            $list->where(function ($q) {
                $q->whereNull('transaction_outlet_services.completed_at')
                    ->orWhere('transactions.show_rate_popup', 1);
            })->where('scope','web-apps');
        }


        $list = $list->paginate(10)->toArray();

        $resData = [];
        foreach ($list['data'] ?? [] as $val) {

            $outlet = [
                'id_outlet' => $val['outlet']['id_outlet'],
                'outlet_code' => $val['outlet']['outlet_code'],
                'outlet_name' => $val['outlet']['outlet_name'],
                'outlet_latitude' => $val['outlet']['outlet_latitude'],
                'outlet_longitude' => $val['outlet']['outlet_longitude']
            ];

            $brand = null;
            if(!empty($val['outlet']['brands'][0])){
                $brand = [
                    'id_brand' => $val['outlet']['brands'][0]['id_brand'],
                    'brand_code' => $val['outlet']['brands'][0]['code_brand'],
                    'brand_name' => $val['outlet']['brands'][0]['name_brand'],
                    'brand_logo' => $val['outlet']['brands'][0]['logo_brand'],
                    'brand_logo_landscape' => $val['outlet']['brands'][0]['logo_landscape_brand']
                ];
            }

            $queue = null;

            $prod_services = [];
            foreach ($val['transaction_products'] ?? [] as $product_ser) {
                if ($product_ser['type'] == 'Service') {
                    
                    if(isset($prod_services[$product_ser['id_product'].'_'.$product_ser['transaction_product_service']['schedule_date']])){
                        $prod_services[$product_ser['id_product'].'_'.$product_ser['transaction_product_service']['schedule_date']]['qty'] += 1;
                        $prod_services[$product_ser['id_product'].'_'.$product_ser['transaction_product_service']['schedule_date']]['total_all_service'] += $product_ser['transaction_product_subtotal'];
                        $queue = $product_ser['transaction_product_service']['queue'];
    
                    }else{
                        $prod_services[$product_ser['id_product'].'_'.$product_ser['transaction_product_service']['schedule_date']] = $product_ser;
                        $prod_services[$product_ser['id_product'].'_'.$product_ser['transaction_product_service']['schedule_date']]['qty'] = 1;
                        $prod_services[$product_ser['id_product'].'_'.$product_ser['transaction_product_service']['schedule_date']]['total_all_service'] = $product_ser['transaction_product_subtotal'];
                        $queue = $product_ser['transaction_product_service']['queue']; 
                    }
                }
            }
            $orders = [];
            foreach($prod_services ?? [] as $prod_ser){
                $orders[] = [
                    'product_name' => $prod_ser['product']['product_name'],
                    'transaction_product_qty' => $prod_ser['qty'],
                    'transaction_product_price' => $prod_ser['transaction_product_subtotal'],
                    'transaction_product_subtotal' => $prod_ser['total_all_service']
                ];
            }
            foreach ($val['products'] as $product) {
                if($product['product_type'] == 'product'){
                    $orders[] = [
                        'product_name' => $product['product_name'],
                        'transaction_product_qty' => $product['pivot']['transaction_product_qty'],
                        'transaction_product_price' => $product['pivot']['transaction_product_price'],
                        'transaction_product_subtotal' => $product['pivot']['transaction_product_subtotal']
                    ];
                }
            }

            $cancelReason = null;
            if ($val['transaction_payment_status'] == 'Pending') {
                $status = 'unpaid';
            } elseif ($val['transaction_payment_status'] == 'Cancelled') {
                $status = 'cancelled';
                $cancelReason = 'Pembayaran gagal';
            } elseif (empty($val['completed_at']) && $val['transaction_payment_status'] == 'Completed') {
                $status = 'ongoing';
            } else {
                $status = 'completed';
            }

            if ($val['reject_at']) {
                $status = 'cancelled';
                $cancelReason = $val['reject_reason'];
            }
            if($queue){
                if($queue<10){
                    $queue_code = '00'.$queue;
                }elseif($queue<100){
                    $queue_code = '0'.$queue;
                }else{
                    $queue_code = $queue;
                }
            }else{
                $queue_code = null;
            }

            $currents = TransactionProductService::join('transactions', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
                ->join('transaction_outlet_services', 'transaction_product_services.id_transaction', 'transaction_outlet_services.id_transaction')
                ->join('transaction_products', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
                ->join('products', 'transaction_products.id_product', 'products.id_product')
                ->where(function($q) {
                    $q->where('service_status','In Progress');
                })
                ->where(function($q){
                    $q->whereNotNull('transaction_product_services.id_user_hair_stylist');
                })
                ->where(function($q) {
                    $q->where('trasaction_payment_type', 'Cash')
                    ->orWhere('transaction_payment_status', 'Completed');
                })
                ->where('transactions.id_outlet',$outlet['id_outlet'])
                ->whereNotNull('transaction_product_services.queue')
                ->whereNotNull('transaction_product_services.queue_code')
                ->whereDate('schedule_date',date('Y-m-d'))
                ->where('transaction_payment_status', '!=', 'Cancelled')
                ->wherenull('transaction_products.reject_at')
                ->where('transactions.id_transaction', '<>', $val['id_transaction'])
                ->orderBy('schedule_time', 'asc')
                ->select('transactions.id_transaction','transaction_product_services.id_transaction_product_service','transaction_product_services.queue_code', 'transaction_product_services.queue')
                ->first();
        
            $res_cs = null;
            if($currents){
                if($currents['queue']<10){
                    $res_cs = '00'.$currents['queue'];
                }elseif($currents['queue']<100){
                    $res_cs = '0'.$currents['queue'];
                }else{
                    $res_cs = $currents['queue'];
                }
            }
            
            $resData[] = [
                'id_transaction' => $val['id_transaction'],
                'queue' => $queue_code,
                'current_service' => $res_cs,
                'transaction_receipt_number' => $val['transaction_receipt_number'],
                'qrcode' => 'https://quickchart.io/qr?text=' . str_replace('#', '', $val['transaction_receipt_number']) . '&margin=0&size=250',
                'transaction_date' => $val['transaction_date'],
                'customer_name' => $val['transaction_outlet_service']['customer_name'],
                'color' => (!empty($val['outlet']['brands'][0]['color_brand']) ? $val['outlet']['brands'][0]['color_brand'] : null),
                'status' => $status,
                'cancel_reason' => $cancelReason,
                'show_rate_popup' => $val['show_rate_popup'],
                'outlet' => $outlet,
                'brand' => $brand,
                'order' => $orders
            ];
        }

        $list['data'] = $resData;
        return MyHelper::checkGet($list);
    }

    public function outletServiceDetail(Request $request) {

        if ($request->json('transaction_receipt_number') !== null) {
            $trx = Transaction::where(['transaction_receipt_number' => $request->json('transaction_receipt_number')])->first();
            if($trx) {
                $id_transaction = $trx->id_transaction;
            } else {
                return MyHelper::checkGet([]);
            }
        } else {
            $id_transaction = $request->json('id_transaction');
        }

        $user = $request->user();
        $detail = Transaction::where('transaction_from', 'outlet-service')
                ->join('transaction_outlet_services','transactions.id_transaction', 'transaction_outlet_services.id_transaction')
                ->where('id_user', $user->id)
                ->where('transactions.id_transaction', $id_transaction)
                ->orderBy('transaction_date', 'desc')
                ->select('transactions.*', 'transaction_outlet_services.*', 'transactions.reject_at')
                ->with(
                    'outlet.brands', 
                    'transaction_outlet_service', 
                    'transaction_products.transaction_product_service.user_hair_stylist',
                    'transaction_products.product.photos',
                    'user_feedbacks'
                )
                ->first();

        $outletZone = Outlet::join('cities', 'cities.id_city', 'outlets.id_city')
            ->join('provinces', 'provinces.id_province', 'cities.id_province')
            ->where('id_outlet', $detail['id_outlet'])
            ->select('outlets.*', 'cities.city_name', 'provinces.time_zone_utc as province_time_zone_utc')->first();

        if (!$detail) {
            return [
                'status' => 'fail',
                'messages' => ['Transaction not found']
            ];
        }

        $trxPromo = $this->transactionPromo($detail);

        $outlet = [
            'id_outlet' => $detail['outlet']['id_outlet'],
            'outlet_code' => $detail['outlet']['outlet_code'],
            'outlet_name' => $detail['outlet']['outlet_name'],
            'outlet_address' => $detail['outlet']['outlet_address'],
            'outlet_latitude' => $detail['outlet']['outlet_latitude'],
            'outlet_longitude' => $detail['outlet']['outlet_longitude']
        ];

        $brand = [
            'id_brand' => $detail['outlet']['brands'][0]['id_brand'],
            'brand_code' => $detail['outlet']['brands'][0]['code_brand'],
            'brand_name' => $detail['outlet']['brands'][0]['name_brand'],
            'brand_logo' => $detail['outlet']['brands'][0]['logo_brand'],
            'brand_logo_landscape' => $detail['outlet']['brands'][0]['logo_landscape_brand']
        ];

        $products = [];
        $services = [];
        $subtotalProduct = 0;
        $subtotalService = 0;
        foreach ($detail['transaction_products'] as $product) {
            $show_rate_popup = 0;
            if ($product['type'] == 'Service') {
                if ($product['transaction_product_service']['completed_at']) {
                    $logRating = UserRatingLog::where([
                        'id_user' => $user->id,
                        'id_transaction' => $detail['id_transaction'],
                        'id_user_hair_stylist' => $product['transaction_product_service']['id_user_hair_stylist']
                    ])->first();

                    if ($logRating) {
                        $show_rate_popup = 1;
                    }
                }

                $timeZone = $outletZone['province_time_zone_utc'] - 7;
                $time = date('H:i', strtotime('+'.$timeZone.' hours', strtotime($product['transaction_product_service']['schedule_time'])));

                $services[] = [
                    'id_user_hair_stylist' => $product['transaction_product_service']['id_user_hair_stylist'],
                    'hairstylist_name' => $product['transaction_product_service']['user_hair_stylist']['nickname'],
                    'schedule_date' => MyHelper::dateFormatInd($product['transaction_product_service']['schedule_date'], true, false),
                    'schedule_time' => $time,
                    'product_name' => $product['product']['product_name'],
                    'subtotal' => $product['transaction_product_subtotal'],
                    'show_rate_popup' => $show_rate_popup
                ];

                $subtotalService += abs($product['transaction_product_subtotal']);
            } else {
                $productPhoto = config('url.storage_url_api') . ($product['product']['photos'][0]['product_photo'] ?? 'img/product/item/default.png');
                $products[] = [
                    'product_name' => $product['product']['product_name'],
                    'transaction_product_qty' => $product['transaction_product_qty'],
                    'transaction_product_price' => $product['transaction_product_price'],
                    'transaction_product_subtotal' => $product['transaction_product_subtotal'],
                    'photo' => $productPhoto
                ];
                $subtotalProduct += abs($product['transaction_product_subtotal']);
            }
        }

        $cancelReason = null;
        if ($detail['transaction_payment_status'] == 'Pending') {
            $status = 'unpaid';
        } elseif ($detail['transaction_payment_status'] == 'Cancelled') {
            $status = 'cancelled';
            $cancelReason = 'Pembayaran gagal';
        } elseif (empty($detail['completed_at']) && $detail['transaction_payment_status'] == 'Completed') {
            $status = 'ongoing';
        } else {
            $status = 'completed';
        }

        if ($detail['reject_at']) {
            $status = 'cancelled';
            $cancelReason = $detail['reject_reason'];
        }

        $paymentDetail = [];
        
        $paymentDetail[] = [
            'name'          => 'Total',
            "is_discount"   => 0,
            'amount'        => MyHelper::requestNumber($detail['transaction_subtotal'],'_CURRENCY')
        ];

        if (!empty($detail['transaction_tax'])) {
            $paymentDetail[] = [
                'name'          => 'Base Price',
                "is_discount"   => 0,
                'amount'        => MyHelper::requestNumber($detail['transaction_subtotal'] - $detail['transaction_tax'],'_CURRENCY')
            ];
            $paymentDetail[] = [
                'name'          => 'Tax',
                "is_discount"   => 0,
                'amount'        => MyHelper::requestNumber(round($detail['transaction_tax']),'_CURRENCY')
            ];
        }

        if($paymentDetail && isset($trxPromo)){
            $lastKey = array_key_last($paymentDetail);
            for($i = 0; $i < count($trxPromo); $i++){
                $KeyPosition = 1 + $i;
                $paymentDetail[$lastKey+$KeyPosition] = $trxPromo[$i];
            }
        }

        $show_rate_popup = 0;
        $logRating = UserRatingLog::where([
            'id_user' => $user->id,
            'id_transaction' => $detail['id_transaction']
        ])->first();

        if ($logRating) {
            $show_rate_popup = 1;
        }

        $trx = Transaction::where('id_transaction', $detail['id_transaction'])->first();
        $trxPayment = app($this->trx_outlet_service)->transactionPayment($trx);
        $paymentMethod = null;
        foreach ($trxPayment['payment'] as $p) {
            $paymentMethod = $p['name'];
            if (strtolower($p['name']) != 'balance') {
                break;
            }
        }

        $paymentMethodDetail = null;
        if ($paymentMethod) {
            $paymentMethodDetail = [
                'text'  => 'Metode Pembayaran',
                'value' => $paymentMethod
            ];
        }

        $paymentCashCode = null;
        // if ($detail['transaction_payment_status'] == 'Pending' && $detail['trasaction_payment_type'] == 'Cash') {
        //     $paymentCash = TransactionPaymentCash::where('id_transaction', $detail['id_transaction'])->first();
        //     $paymentCashCode = $paymentCash->payment_code;
        // }

        $res = [
            'id_transaction' => $detail['id_transaction'],
            'transaction_receipt_number' => $detail['transaction_receipt_number'],
            'qrcode' => 'https://quickchart.io/qr?text=' . str_replace('#', '', $detail['transaction_receipt_number']) . '&margin=0&size=250',
            'transaction_date' => $detail['transaction_date'],
            'transaction_date_indo' => MyHelper::indonesian_date_v2(date('Y-m-d', strtotime($detail['transaction_date'])), 'j F Y'),
            'transaction_subtotal' => $detail['transaction_subtotal'],
            'transaction_grandtotal' => $detail['transaction_grandtotal'],
            'transaction_tax' => $detail['transaction_tax'],
            'transaction_product_subtotal' => $subtotalProduct,
            'transaction_service_subtotal' => $subtotalService,
            'customer_name' => $detail['transaction_outlet_service']['customer_name'],
            'color' => $detail['outlet']['brands'][0]['color_brand'],
            'status' => $status,
            'cancel_reason' => $cancelReason,
            'transaction_payment_status' => $detail['transaction_payment_status'],
            'payment_method' => $paymentMethod,
            'payment_cash_code' => $paymentCashCode,
            'show_rate_popup' => $show_rate_popup,
            'outlet' => $outlet,
            'brand' => $brand,
            'service' => $services,
            'product' => $products,
            'payment_detail' => $paymentDetail,
            'payment_method' => $paymentMethodDetail
        ];
        
        return MyHelper::checkGet($res);
    }

    public function outletServiceDetailV2(Request $request) {

        if ($request->json('transaction_receipt_number') !== null) {
            $trx = Transaction::where(['transaction_receipt_number' => $request->json('transaction_receipt_number')])->first();
            if($trx) {
                $id_transaction = $trx->id_transaction;
            } else {
                return MyHelper::checkGet([]);
            }
        } else {
            $id_transaction = $request->json('id_transaction');
        }

        $user = $request->user();
        $detail = Transaction::where('transaction_from', 'outlet-service')
                ->join('transaction_outlet_services','transactions.id_transaction', 'transaction_outlet_services.id_transaction')
                ->where('id_user', $user->id)
                ->where('transactions.id_transaction', $id_transaction)
                ->orderBy('transaction_date', 'desc')
                ->select('transactions.*', 'transaction_outlet_services.*', 'transactions.reject_at')
                ->with(
                    'outlet.brands', 
                    'transaction_outlet_service', 
                    'transaction_products.transaction_product_service.user_hair_stylist',
                    'transaction_products.product.photos',
                    'user_feedbacks'
                )
                ->first();

        $outletZone = Outlet::join('cities', 'cities.id_city', 'outlets.id_city')
            ->join('provinces', 'provinces.id_province', 'cities.id_province')
            ->where('id_outlet', $detail['id_outlet'])
            ->select('outlets.*', 'cities.city_name', 'provinces.time_zone_utc as province_time_zone_utc')->first();

        if (!$detail) {
            return [
                'status' => 'fail',
                'messages' => ['Transaction not found']
            ];
        }

        $trxPromo = $this->transactionPromo($detail);

        $outlet = [
            'id_outlet' => $detail['outlet']['id_outlet'],
            'outlet_code' => $detail['outlet']['outlet_code'],
            'outlet_name' => $detail['outlet']['outlet_name'],
            'outlet_address' => $detail['outlet']['outlet_address'],
            'outlet_latitude' => $detail['outlet']['outlet_latitude'],
            'outlet_longitude' => $detail['outlet']['outlet_longitude']
        ];

        $brand = [
            'id_brand' => $detail['outlet']['brands'][0]['id_brand'],
            'brand_code' => $detail['outlet']['brands'][0]['code_brand'],
            'brand_name' => $detail['outlet']['brands'][0]['name_brand'],
            'brand_logo' => $detail['outlet']['brands'][0]['logo_brand'],
            'brand_logo_landscape' => $detail['outlet']['brands'][0]['logo_landscape_brand']
        ];

        $products = [];
        $prod_services = [];
        $services = [];
        $queue = null;
        $subtotalProduct = 0;
        $subtotalService = 0;
        foreach ($detail['transaction_products'] as $product) {
            $show_rate_popup = 0;
            if ($product['type'] == 'Service') {
                if(isset($prod_services[$product['id_product'].'_'.$product['transaction_product_service']['schedule_date']])){
                    $prod_services[$product['id_product'].'_'.$product['transaction_product_service']['schedule_date']]['qty'] += 1;
                    $prod_services[$product['id_product'].'_'.$product['transaction_product_service']['schedule_date']]['total_all_service'] += $product['transaction_product_subtotal'];
                    $queue = $product['transaction_product_service']['queue'];

                }else{
                    $prod_services[$product['id_product'].'_'.$product['transaction_product_service']['schedule_date']] = $product;
                    $prod_services[$product['id_product'].'_'.$product['transaction_product_service']['schedule_date']]['qty'] = 1;
                    $prod_services[$product['id_product'].'_'.$product['transaction_product_service']['schedule_date']]['total_all_service'] = $product['transaction_product_subtotal'];
                    $queue = $product['transaction_product_service']['queue'];

                    
                }

            } else {
                $productPhoto = config('url.storage_url_api') . ($product['product']['photos'][0]['product_photo'] ?? 'img/product/item/default.png');
                $products[] = [
                    'product_name' => $product['product']['product_name'],
                    'transaction_product_qty' => $product['transaction_product_qty'],
                    'transaction_product_price' => $product['transaction_product_price'],
                    'transaction_product_subtotal' => $product['transaction_product_subtotal'],
                    'photo' => $productPhoto
                ];
                $subtotalProduct += abs($product['transaction_product_subtotal']);
            }
        }
        
        foreach($prod_services ?? [] as $prod_ser){
            if ($prod_ser['transaction_product_service']['completed_at']) {
                    $logRating = UserRatingLog::where([
                        'id_user' => $user->id,
                        'id_transaction' => $detail['id_transaction'],
                        'id_user_hair_stylist' => $prod_ser['transaction_product_service']['id_user_hair_stylist']
                    ])->first();

                    if ($logRating) {
                        $show_rate_popup = 1;
                    }
                }

                $timeZone = $outletZone['province_time_zone_utc'] - 7;
                $time = date('H:i', strtotime('+'.$timeZone.' hours', strtotime($prod_ser['transaction_product_service']['schedule_time'])));

                $services[] = [
                    'schedule_date' => MyHelper::dateFormatInd($prod_ser['transaction_product_service']['schedule_date'], true, false),
                    'qty' => $prod_ser['qty'],
                    'product_name' => $prod_ser['product']['product_name'],
                    'subtotal' => $prod_ser['transaction_product_subtotal'],
                    'total_all_service' => $prod_ser['total_all_service'],
                    'show_rate_popup' => $show_rate_popup
                ];

                $subtotalService += abs($product['transaction_product_subtotal']);
        }

        $cancelReason = null;
        if ($detail['transaction_payment_status'] == 'Pending') {
            $status = 'unpaid';
        } elseif ($detail['transaction_payment_status'] == 'Cancelled') {
            $status = 'cancelled';
            $cancelReason = 'Pembayaran gagal';
        } elseif (empty($detail['completed_at']) && $detail['transaction_payment_status'] == 'Completed') {
            $status = 'ongoing';
        } else {
            $status = 'completed';
        }

        if ($detail['reject_at']) {
            $status = 'cancelled';
            $cancelReason = $detail['reject_reason'];
        }

        $paymentDetail = [];
        
        $paymentDetail[] = [
            'name'          => 'Total',
            "is_discount"   => 0,
            'amount'        => MyHelper::requestNumber($detail['transaction_subtotal'],'_CURRENCY')
        ];

        if (!empty($detail['transaction_tax'])) {
            $paymentDetail[] = [
                'name'          => 'Base Price',
                "is_discount"   => 0,
                'amount'        => MyHelper::requestNumber($detail['transaction_subtotal'] - $detail['transaction_tax'],'_CURRENCY')
            ];
            $paymentDetail[] = [
                'name'          => 'Tax',
                "is_discount"   => 0,
                'amount'        => MyHelper::requestNumber(round($detail['transaction_tax']),'_CURRENCY')
            ];
        }

        if($paymentDetail && isset($trxPromo)){
            $lastKey = array_key_last($paymentDetail);
            for($i = 0; $i < count($trxPromo); $i++){
                $KeyPosition = 1 + $i;
                $paymentDetail[$lastKey+$KeyPosition] = $trxPromo[$i];
            }
        }

        $show_rate_popup = 0;
        $logRating = UserRatingLog::where([
            'id_user' => $user->id,
            'id_transaction' => $detail['id_transaction']
        ])->first();

        if ($logRating) {
            $show_rate_popup = 1;
        }

        $trx = Transaction::where('id_transaction', $detail['id_transaction'])->first();
        $trxPayment = app($this->trx_outlet_service)->transactionPayment($trx);
        $paymentMethod = null;
        foreach ($trxPayment['payment'] as $p) {
            $paymentMethod = $p['name'];
            if (strtolower($p['name']) != 'balance') {
                break;
            }
        }

        $paymentMethodDetail = null;
        if ($paymentMethod) {
            $paymentMethodDetail = [
                'text'  => 'Metode Pembayaran',
                'value' => $paymentMethod
            ];
        }

        $paymentCashCode = null;
        // if ($detail['transaction_payment_status'] == 'Pending' && $detail['trasaction_payment_type'] == 'Cash') {
        //     $paymentCash = TransactionPaymentCash::where('id_transaction', $detail['id_transaction'])->first();
        //     $paymentCashCode = $paymentCash->payment_code;
        // }
        if($queue){
            if($queue<10){
                $queue_code = '00'.$queue;
            }elseif($queue<100){
                $queue_code = '0'.$queue;
            }else{
                $queue_code = $queue;
            }
        }else{
            $queue_code = null;
        }
        
        $currents = TransactionProductService::join('transactions', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
                ->join('transaction_outlet_services', 'transaction_product_services.id_transaction', 'transaction_outlet_services.id_transaction')
                ->join('transaction_products', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
                ->join('products', 'transaction_products.id_product', 'products.id_product')
                ->where(function($q) {
                    $q->where('service_status','In Progress');
                })
                ->where(function($q){
                    $q->whereNotNull('transaction_product_services.id_user_hair_stylist');
                })
                ->where(function($q) {
                    $q->where('trasaction_payment_type', 'Cash')
                    ->orWhere('transaction_payment_status', 'Completed');
                })
                ->where('transactions.id_outlet',$outlet['id_outlet'])
                ->whereNotNull('transaction_product_services.queue')
                ->whereNotNull('transaction_product_services.queue_code')
                ->whereDate('schedule_date',date('Y-m-d'))
                ->where('transaction_payment_status', '!=', 'Cancelled')
                ->wherenull('transaction_products.reject_at')
                // ->where('transactions.id_transaction', '<>', $detail['id_transaction'])
                ->orderBy('queue', 'asc')
                ->select('transactions.id_transaction','transaction_product_services.id_transaction_product_service','transaction_product_services.queue_code', 'transaction_product_services.queue')
                ->get()->toArray();
        
        $res_cs = [];
        if($currents){
            foreach($currents ?? [] as $key => $current){
                if($current['queue']<10){
                    $res_cs[] = '00'.$current['queue'];
                }elseif($current['queue']<100){
                    $res_cs[] = '0'.$current['queue'];
                }else{
                    $res_cs[] = $current['queue'];
                }
            }
        }else{
            $res_cs = [];
        }
        $res = [
            'id_transaction' => $detail['id_transaction'],
            'transaction_receipt_number' => $detail['transaction_receipt_number'],
            'queue' => $queue_code,
            'current_service' => $res_cs,
            'qrcode' => 'https://quickchart.io/qr?text=' . str_replace('#', '', $detail['transaction_receipt_number']) . '&margin=0&size=250',
            'transaction_date' => $detail['transaction_date'],
            'transaction_date_indo' => MyHelper::indonesian_date_v2(date('Y-m-d', strtotime($detail['transaction_date'])), 'j F Y'),
            'transaction_subtotal' => $detail['transaction_subtotal'],
            'transaction_grandtotal' => $detail['transaction_grandtotal'],
            'transaction_tax' => $detail['transaction_tax'],
            'transaction_product_subtotal' => $subtotalProduct,
            'transaction_service_subtotal' => $subtotalService,
            'customer_name' => $detail['transaction_outlet_service']['customer_name'],
            'color' => $detail['outlet']['brands'][0]['color_brand'],
            'status' => $status,
            'cancel_reason' => $cancelReason,
            'transaction_payment_status' => $detail['transaction_payment_status'],
            'payment_method' => $paymentMethod,
            'payment_cash_code' => $paymentCashCode,
            'show_rate_popup' => $show_rate_popup,
            'outlet' => $outlet,
            'brand' => $brand,
            'service' => $services,
            'product' => $products,
            'payment_detail' => $paymentDetail,
            'payment_method' => $paymentMethodDetail
        ];
        
        return MyHelper::checkGet($res);
    }

    public function homeServiceList(Request $request) {
        $user = $request->user();

        $list = Transaction::where('transaction_from', 'home-service')
                ->join('transaction_home_services','transactions.id_transaction', 'transaction_home_services.id_transaction')
                ->where('id_user', $user->id)
                ->select('transaction_home_services.*', 'transactions.*')
                ->orderBy('transaction_date', 'desc')
                ->with('outlet.brands', 'products', 'user_feedbacks');

        switch (strtolower($request->status)) {
            case 'unpaid':
                $list->where('transaction_payment_status','Pending');
                break;

            case 'ongoing':
                $list->where(function($q) {
                    $q->whereNotIn('transaction_home_services.status', ['Cancelled', 'Completed'])
                    ->orWhereNull('transaction_home_services.status');
                })
                ->where('transaction_payment_status','Completed');
                break;

            case 'complete':
                $list->where(function($q) {
                    $q->whereIn('transaction_home_services.status', ['Cancelled', 'Completed'])
                    ->orWhere('transaction_payment_status','Cancelled')
                    ->orWhereNotNull('transactions.reject_at');
                });
                break;
            
            default:
                // code...
                break;
        }

        $list = $list->paginate(10)->toArray();

        $resData = [];
        foreach ($list['data'] ?? [] as $val) {

            if (!empty($val['outlet'])) {
                $outlet = [
                    'id_outlet' => $val['outlet']['id_outlet'],
                    'outlet_code' => $val['outlet']['outlet_code'],
                    'outlet_name' => $val['outlet']['outlet_name'],
                    'outlet_latitude' => $val['outlet']['outlet_latitude'],
                    'outlet_longitude' => $val['outlet']['outlet_longitude']
                ];
            }

            if (!empty($val['outlet']['brands'])) {
                $brand = [
                    'id_brand' => $val['outlet']['brands'][0]['id_brand'],
                    'brand_code' => $val['outlet']['brands'][0]['code_brand'],
                    'brand_name' => $val['outlet']['brands'][0]['name_brand'],
                    'brand_logo' => $val['outlet']['brands'][0]['logo_brand'],
                    'brand_logo_landscape' => $val['outlet']['brands'][0]['logo_landscape_brand']
                ];
            }

            $orders = [];
            foreach ($val['products'] as $product) {
                $orders[] = [
                    'product_name' => $product['product_name'],
                    'transaction_product_qty' => $product['pivot']['transaction_product_qty'],
                    'transaction_product_price' => $product['pivot']['transaction_product_price'],
                    'transaction_product_subtotal' => $product['pivot']['transaction_product_subtotal']
                ];
            }

            $cancelReason = null;
            if ($val['transaction_payment_status'] == 'Pending') {
                $status = 'unpaid';
            } elseif ($val['transaction_payment_status'] == 'Cancelled') {
                $status = 'cancelled';
                $cancelReason = 'Pembayaran gagal';
            } elseif (in_array($val['status'], ['Cancelled', 'Completed']) && $val['transaction_payment_status'] == 'Completed') {
                $status = 'completed';
            } else {
                $status = 'ongoing';
            }

            if ($val['status'] == 'Cancelled') {
                $status = 'cancelled';
                $cancelReason = 'Hairstylist tidak ditemukan';
            }

            if ($val['reject_at']) {
                $status = 'cancelled';
                $cancelReason = $val['reject_reason'];
            }

            $resData[] = [
                'id_transaction' => $val['id_transaction'],
                'transaction_receipt_number' => $val['transaction_receipt_number'],
                'qrcode' => 'https://chart.googleapis.com/chart?chl=' . str_replace('#', '', $val['transaction_receipt_number']) . '&chs=250x250&cht=qr&chld=H%7C0',
                'transaction_date' => MyHelper::dateFormatInd($val['transaction_date'], true, false),
                'transaction_time' => date('H:i', strtotime($val['transaction_date'])),
                'customer_name' => null,
                'color' => (!empty($val['outlet']['brands'][0]['color_brand']) ? $val['outlet']['brands'][0]['color_brand'] : null),
                'status' => $status,
                'cancel_reason' => $cancelReason,
                'home_service_status' => $val['status'],
                'home_service_status_text' => $this->home_service_status[$val['status']]['text']??'',
                'home_service_status_code' => $this->home_service_status[$val['status']]['code']??0,
                'show_rate_popup' => $val['show_rate_popup'],
                'destination_address' => $val['destination_address'],
                'outlet' => $outlet ?? null,
                'brand' => $brand ?? null,
                'order' => $orders
            ];
        }

        $list['data'] = $resData;
        return MyHelper::checkGet($list);
    }

    public function homeServiceDetail(Request $request) {

        $user = $request->user();
        $detail = Transaction::where('transaction_from', 'home-service')
                ->join('transaction_home_services','transactions.id_transaction', 'transaction_home_services.id_transaction')
                ->where('id_user', $user->id)
                ->where(function ($q) use ($request) {
                    $q->where('transactions.id_transaction', $request->id_transaction);
                    $q->orWhere('transactions.transaction_receipt_number', $request->transaction_receipt_number);
                })
                ->orderBy('transaction_date', 'desc')
                ->with(
                    'outlet.brands', 
                    'transaction_products.product.photos',
                    'user_feedbacks'
                )
                ->first();

        if (!$detail) {
            return [
                'status' => 'fail',
                'messages' => ['Transaction not found']
            ];
        }

        $trxPromo = $this->transactionPromo($detail);

        $hairstylist = UserHairStylist::where('id_user_hair_stylist', $detail['id_user_hair_stylist'])->first();
        if (!empty($detail['outlet'])) {
            $outlet = [
                'id_outlet' => $detail['outlet']['id_outlet'],
                'outlet_code' => $detail['outlet']['outlet_code'],
                'outlet_name' => $detail['outlet']['outlet_name'],
                'outlet_address' => $detail['outlet']['outlet_address'],
                'outlet_latitude' => $detail['outlet']['outlet_latitude'],
                'outlet_longitude' => $detail['outlet']['outlet_longitude']
            ];
        }

        if (!empty($detail['outlet']['brands'])) {
            $brand = [
                'id_brand' => $detail['outlet']['brands'][0]['id_brand'],
                'brand_code' => $detail['outlet']['brands'][0]['code_brand'],
                'brand_name' => $detail['outlet']['brands'][0]['name_brand'],
                'brand_logo' => $detail['outlet']['brands'][0]['logo_brand'],
                'brand_logo_landscape' => $detail['outlet']['brands'][0]['logo_landscape_brand']
            ];
        }

        $services = [];
        $subtotalService = 0;
        foreach ($detail['transaction_products'] as $product) {
            $show_rate_popup = 0;
            if ($product['type'] == 'Service') {
                if ( $detail['status'] == ['Completed'] ) {
                    $logRating = UserRatingLog::where([
                        'id_user' => $user->id,
                        'id_transaction' => $detail['id_transaction'],
                        'id_user_hair_stylist' => $hairstylist['id_user_hair_stylist']
                    ])->first();

                    if ($logRating) {
                        $show_rate_popup = 1;
                    }
                }

                $services[] = [
                    'id_user_hair_stylist' => $hairstylist['id_user_hair_stylist']??null,
                    'hairstylist_name' => $hairstylist['nickname']??'',
                    'hairstylist_phone_number' => $hairstylist['phone_number']??'',
                    'hairstylist_photo' => $hairstylist['user_hair_stylist_photo']??'',
                    'schedule_date' => MyHelper::dateFormatInd($detail['schedule_date'], true, false),
                    'schedule_time' => date('H:i', strtotime($detail['schedule_time'])),
                    'product_name' => $product['product']['product_name'],
                    'subtotal' => $product['transaction_product_subtotal'],
                    'qty' => $product['transaction_product_qty'],
                    'show_rate_popup' => $show_rate_popup
                ];

                $subtotalService += abs($product['transaction_product_subtotal']);
            }
        }

        $cancelReason = null;
        if ($detail['transaction_payment_status'] == 'Pending') {
            $status = 'unpaid';
        } elseif ($detail['transaction_payment_status'] == 'Cancelled') {
            $status = 'cancelled';
            $cancelReason = 'Pembayaran gagal';
        } elseif (empty($detail['completed_at']) && $detail['transaction_payment_status'] == 'Completed') {
            $status = 'ongoing';
        } else {
            $status = 'completed';
        }

        if ($detail['status'] == 'Cancelled') {
            $status = 'cancelled';
            $cancelReason = 'Hairstylist tidak ditemukan';
        }

        if ($detail['reject_at']) {
            $status = 'cancelled';
            $cancelReason = $detail['reject_reason'];
        }

        $paymentDetail = [];
        $paymentDetail[] = [
            'name'          => 'Total',
            "is_discount"   => 0,
            'amount'        => MyHelper::requestNumber($detail['transaction_subtotal'],'_CURRENCY')
        ];

        if (!empty($detail['transaction_tax'])) {
            $paymentDetail[] = [
                'name'          => 'Tax',
                "is_discount"   => 0,
                'amount'        => MyHelper::requestNumber(round($detail['transaction_tax']),'_CURRENCY')
            ];
        }

        if($paymentDetail && isset($trxPromo)){
            $lastKey = array_key_last($paymentDetail);
            for($i = 0; $i < count($trxPromo); $i++){
                $KeyPosition = 1 + $i;
                $paymentDetail[$lastKey+$KeyPosition] = $trxPromo[$i];
            }
        }

        $show_rate_popup = 0;
        $logRating = UserRatingLog::where([
            'id_user' => $user->id,
            'id_transaction' => $detail['id_transaction']
        ])->first();

        if ($logRating) {
            $show_rate_popup = 1;
        }

        $trx = Transaction::where('id_transaction', $detail['id_transaction'])->first();
        $trxPayment = app($this->trx_outlet_service)->transactionPayment($trx);
        $paymentMethod = null;
        foreach ($trxPayment['payment'] as $p) {
            $paymentMethod = $p['name'];
            if (strtolower($p['name']) != 'balance') {
                break;
            }
        }

        $paymentMethodDetail = null;
        if ($paymentMethod) {
            $paymentMethodDetail = [
                'text'  => 'Metode Pembayaran',
                'value' => $paymentMethod
            ];
        }

        $homeDetail = [
            'preference_hair_stylist' => $detail['preference_hair_stylist'],
            'destination_phone' => $detail['destination_phone'],
            'destination_address' => $detail['destination_address'],
            'destination_short_address' => $detail['destination_short_address'],
            'destination_address_name' => $detail['destination_address_name'],
            'destination_note' => $detail['destination_note'],
            'destination_latitude' => $detail['destination_latitude'],
            'destination_longitude' => $detail['destination_longitude'],
        ];

        $res = [
            'id_transaction' => $detail['id_transaction'],
            'transaction_receipt_number' => $detail['transaction_receipt_number'],
            'qrcode' => 'https://chart.googleapis.com/chart?chl=' . str_replace('#', '', $detail['transaction_receipt_number']) . '&chs=250x250&cht=qr&chld=H%7C0',
            'transaction_date' => $detail['transaction_date'],
            'transaction_date_indo' => MyHelper::indonesian_date_v2(date('Y-m-d', strtotime($detail['transaction_date'])), 'j F Y'),
            'transaction_time' => date('H:i', strtotime($detail['transaction_date'])),
            'transaction_subtotal' => $detail['transaction_subtotal'],
            'transaction_grandtotal' => $detail['transaction_grandtotal'],
            'transaction_tax' => $detail['transaction_tax'],
            'transaction_product_subtotal' => null,
            'transaction_service_subtotal' => $subtotalService,
            'customer_name' => $detail['destination_name'],
            'color' => $detail['outlet']['brands'][0]['color_brand'],
            'status' => $status,
            'cancel_reason' => $cancelReason,
            'home_service_status' => $detail['status'],
            'home_service_status_text' => $this->home_service_status[$detail['status']]['text']??'',
            'home_service_status_code' => $this->home_service_status[$detail['status']]['code']??0,
            'transaction_payment_status' => $detail['transaction_payment_status'],
            'payment_method' => $paymentMethod,
            'payment_cash_code' => null,
            'show_rate_popup' => $show_rate_popup,
            'outlet' => $outlet ?? null,
            'brand' => $brand ?? null,
            'service' => $services,
            'product' => null,
            'payment_detail' => $paymentDetail,
            'payment_method' => $paymentMethodDetail,
            'currency'       => 'Rp',
            'home_service_detail' => $homeDetail
        ];
        
        return MyHelper::checkGet($res);
    }

    public function transactionPromo(Transaction $trx){
        $trx = clone $trx;
        $promo_discount = [];
        $promos = TransactionPromo::where('id_transaction', $trx['id_transaction'])->get()->toArray();
        if($promos){
            $promo_discount[0]=[
                "name"  => "Promo / Discount:",
                "is_discount" => 0,
                "amount" => null 
            ];
            foreach($promos as $p => $promo){
                if($promo['promo_type']=='Promo Campaign'){
                    $promo['promo_name'] = PromoCampaign::where('promo_title',$promo['promo_name'])->select('campaign_name')->first()['campaign_name'];
                }
                $promo_discount[$p+1] = [
                    "name"  => $promo['promo_name'],
                    "is_discount" => 1,
                    "amount" => '- '.MyHelper::requestNumber($promo['discount_value'],'_CURRENCY')
                ];
            }
        }
        return $promo_discount;
    }

    public function CronOutletSericeICountPOO($date = null) {
        $log = MyHelper::logCron('Create Order POO Icount Outlet Service');
        try{
            $date_now = date('Y-m-d');
            if ($date) {
                $date_trans = date('Y-m-d', strtotime($date));
            } else {
                $date_trans = date('Y-m-d', strtotime('-1 days'));
            }
            $outlets_mid = Outlet::join('locations','locations.id_location','=','outlets.id_location')
                            ->join('transactions','transactions.id_outlet','=','outlets.id_outlet')
                            ->join('transaction_outlet_services','transaction_outlet_services.id_transaction','=','transactions.id_transaction')
                            ->leftJoin('partners','partners.id_partner','=','locations.id_partner')->whereNotIn('outlets.id_outlet', [9, 12, 17, 20, 48, 49, 50, 54, 74]);

            $outlets_xen = Outlet::join('locations','locations.id_location','=','outlets.id_location')
                            ->join('transactions','transactions.id_outlet','=','outlets.id_outlet')
                            ->join('transaction_outlet_services','transaction_outlet_services.id_transaction','=','transactions.id_transaction')
                            ->leftJoin('partners','partners.id_partner','=','locations.id_partner')->whereNotIn('outlets.id_outlet', [9, 12, 17, 20, 48, 49, 50, 54, 74]);

            $outlets_cash = Outlet::join('locations','locations.id_location','=','outlets.id_location')
                            ->join('transactions','transactions.id_outlet','=','outlets.id_outlet')
                            ->join('transaction_outlet_services','transaction_outlet_services.id_transaction','=','transactions.id_transaction')
                            ->leftJoin('partners','partners.id_partner','=','locations.id_partner')->whereNotIn('outlets.id_outlet', [9, 12, 17, 20, 48, 49, 50, 54, 74]);

            $outlets_mid->join('transaction_payment_midtrans','transaction_payment_midtrans.id_transaction','=','transactions.id_transaction')->whereDate('transactions.transaction_date', '=', $date_trans)->where('transactions.flag_icount','0');
            $outlets_mid->select('outlets.*','partners.*','locations.*','outlets.is_tax as is_tax','transaction_payment_midtrans.id_transaction_payment','transaction_payment_midtrans.payment_type','transactions.transaction_date')->groupBy('outlets.id_outlet','transaction_payment_midtrans.payment_type')->orderBy('outlets.id_outlet', 'DESC');
            $outlets_mid =  $outlets_mid->get()->toArray();

            $outlets_xen->join('transaction_payment_xendits','transaction_payment_xendits.id_transaction','=','transactions.id_transaction')->whereDate('transactions.transaction_date', '=', $date_trans)->where('transactions.flag_icount','0');
            $outlets_xen->select('outlets.*','partners.*','locations.*','outlets.is_tax as is_tax','transaction_payment_xendits.id_transaction_payment_xendit','transaction_payment_xendits.type','transactions.transaction_date')->groupBy('outlets.id_outlet','transaction_payment_xendits.type')->orderBy('outlets.id_outlet', 'DESC');
            $outlets_xen =  $outlets_xen->get()->toArray();

            $outlets_cash->join('transaction_payment_cash','transaction_payment_cash.id_transaction','=','transactions.id_transaction')->whereDate('transactions.transaction_date', '=', $date_trans)->where('transactions.flag_icount','0');
            $outlets_cash->select('outlets.*','partners.*','locations.*','outlets.is_tax as is_tax','transaction_payment_cash.id_transaction_payment_cash','transactions.transaction_date')->groupBy('outlets.id_outlet')->orderBy('outlets.id_outlet', 'DESC');
            $outlets_cash =  $outlets_cash->get()->toArray();
            
            $outlets = [];
            if($outlets_mid){
                foreach($outlets_mid as $key => $mid){
                    if(isset($mid) && !empty($mid)){
                        $outlets[$key] = $mid;
                    }
                }
            }   
            $index_outlets = count($outlets);
            if($outlets_xen){
                foreach($outlets_xen as $xen){
                    if(isset($xen) && !empty($xen)){
                        $outlets[$index_outlets] = $xen;
                        $index_outlets++;
                    }
                }
            }
            if($outlets_cash){
                foreach($outlets_cash as $cash){
                    if(isset($cash) && !empty($cash)){
                        $outlets[$index_outlets] = $cash;
                        $index_outlets++;
                    }
                }
            }
            $i = 0;
            
            foreach($outlets as $key => $outlet){
                $transaction = Transaction::join('transaction_outlet_services','transaction_outlet_services.id_transaction','=','transactions.id_transaction')
                                ->leftJoin('outlets','outlets.id_outlet','=','transactions.id_outlet')
                                ->leftJoin('transaction_products','transaction_products.id_transaction','transactions.id_transaction')
                                ->leftJoin('users','transactions.id_user','=','users.id')
                                ->leftJoin('products','products.id_product','=','transaction_products.id_product');

                if(isset($outlet['id_transaction_payment'])){
                    $transaction->join('transaction_payment_midtrans','transaction_payment_midtrans.id_transaction','=','transactions.id_transaction');
                    $group = 'transaction_payment_midtrans.payment_type';
                }elseif(isset($outlet['id_transaction_payment_xendit'])){
                    $transaction->join('transaction_payment_xendits','transaction_payment_xendits.id_transaction','=','transactions.id_transaction');
                    $group = 'transaction_payment_xendits.type';
                }elseif(isset($outlet['id_transaction_payment_cash'])){
                    $transaction->join('transaction_payment_cash','transaction_payment_cash.id_transaction','=','transactions.id_transaction');
                }
                $transaction->whereDate('transactions.transaction_date', '=', $date_trans)->where('transactions.flag_icount','0')->where('outlets.id_outlet', '=', $outlet['id_outlet'])->where('transactions.transaction_payment_status','Completed')->whereNotNull('transactions.transaction_date');
                
                if(isset($outlet['id_transaction_payment'])){
                    $transaction->where('transaction_payment_midtrans.payment_type', '=', $outlet['payment_type']);
                }elseif(isset($outlet['id_transaction_payment_xendit'])){
                    $transaction->where('transaction_payment_xendits.type', '=', $outlet['type']);
                }
                $transaction->select('transactions.*','transaction_outlet_services.*','transaction_products.*','products.*','outlets.outlet_code', 'outlets.outlet_name')
                            ->groupBy('transactions.id_transaction','transaction_products.id_transaction_product');  

                if(!isset($outlet['id_transaction_payment_cash'])){
                    $transaction->groupBy($group);
                }
                $outlets[$key]['trans_date'] = $date_trans;
                $outlets[$key]['due_date'] = $date_trans;
                $transaction->orderBy('transactions.id_transaction', 'DESC');
                $transaction = $transaction->get()->toArray();

                if($transaction){
                    foreach($transaction as $t => $tr){
                        $transaction[$t]['total_product'] = TransactionProduct::Join('transactions','transactions.id_transaction','=','transaction_products.id_transaction')
                                ->where('transactions.id_transaction', $tr['id_transaction'])
                                ->select(DB::raw('
                                        SUM(IF(transaction_products.id_transaction = transactions.id_transaction,transaction_product_price,0)) as total_product
                                    '))
                                ->first()['total_product'];
                    }
                    $outlets[$key]['transaction'] = $transaction;
                }else{
                    unset($outlets[$key]);
                }

                $i++;
            }

            $new = 0;
            $new_outlets = [];
            foreach($outlets as $outlet){
                if($outlet['transaction']){
                    if($outlet['company_type']=='PT IMA'){
                        $company_type = 'ima';
                    }elseif($outlet['company_type']=='PT IMS'){
                        $company_type = 'ims';
                    }
                    $new_trans_non = 0;
                    $new_trans_use = 0;
                    $new_transaction_non = [];
                    $new_transaction = [];
                    foreach($outlet['transaction'] as $t => $tran){
                        if($tran['product_type']=='product'){
                            $cek_prod = ProductProductIcount::where('id_product',$tran['id_product'])->where('company_type',$company_type)->first();
                            if($cek_prod){
                                $prod_icount = ProductIcount::where('id_product_icount',$cek_prod['id_product_icount'])->first();
                                $tran['id_item_icount'] = $prod_icount['id_item'];
                            }
                        }
                        
                        if($tran['transaction_tax']==0){
                            $new_transaction_non[$new_trans_non] = $tran;
                            $num_is_tax = 100;
                            $new_transaction_non[$new_trans_non]['total_price'] = ($tran['transaction_product_price'] * $tran['transaction_product_qty'] - $tran['transaction_product_discount_all']) * (100/$num_is_tax);
                            $new_trans_non++;
                        }else{
                            $new_transaction[$new_trans_use] = $tran;
                            $num_is_tax = isset($outlet['is_tax']) ? 100 + $outlet['is_tax'] : 111;
                            $new_transaction[$new_trans_use]['total_price'] = ($tran['transaction_product_price'] * $tran['transaction_product_qty'] - $tran['transaction_product_discount_all']) * (100/$num_is_tax);
                            $new_trans_use++;
                        }

                    }

                    if($new_transaction){
                        $new_outlets[$new] = $outlet;
                        $new_outlets[$new]['transaction'] = $new_transaction;
                        $new_outlets[$new]['ppn'] = $outlet['is_tax'] ?? 11;
                        $new++;

                    }
                    if($new_transaction_non){
                        $new_outlets[$new] = $outlet;
                        $new_outlets[$new]['transaction'] = $new_transaction_non;
                        $new_outlets[$new]['ppn'] = 0;
                        $new++;
                    }
                }
            }   
            
            $create_order_poo= [];
            foreach($new_outlets as $n => $new_outlet){
                    $create_order_poo[$n] = Icount::ApiCreateOrderPOO($new_outlet, $new_outlet['company_type']);
            }

            foreach($create_order_poo as $c => $poo){
                if($poo['status_code'] == 200 && $poo['response']['Message'] == 'success'){
                    foreach($new_outlets[$c]['transaction'] as $detail_trans){
                        $update_transaction = Transaction::where('id_transaction', $detail_trans['id_transaction'])->update(['flag_icount' => 1]);
                    }
                }
            }

            $log->success('success');
            return response()->json(['status' => 'success','data' => $create_order_poo]);

        } catch (\Exception $e) {
            DB::rollBack();
            $log->fail($e->getMessage());
        }        
    }

    public function CronHomeServiceICountPOO(Request $request){
        $log = MyHelper::logCron('Create Order POO Icount Outlet Service');
        try{
            $date_now = date('Y-m-d');
            $date_trans = date('Y-m-d', strtotime('-1 days'));
            $outlets_mid = Outlet::join('locations','locations.id_location','=','outlets.id_location')
                            ->join('transactions','transactions.id_outlet','=','outlets.id_outlet')
                            ->join('transaction_home_services','transaction_home_services.id_transaction','=','transactions.id_transaction')
                            ->leftJoin('partners','partners.id_partner','=','locations.id_partner');

            $outlets_xen = Outlet::join('locations','locations.id_location','=','outlets.id_location')
                            ->join('transactions','transactions.id_outlet','=','outlets.id_outlet')
                            ->join('transaction_home_services','transaction_home_services.id_transaction','=','transactions.id_transaction')
                            ->leftJoin('partners','partners.id_partner','=','locations.id_partner'); 

            $outlets_cash = Outlet::join('locations','locations.id_location','=','outlets.id_location')
                            ->join('transactions','transactions.id_outlet','=','outlets.id_outlet')
                            ->join('transaction_home_services','transaction_home_services.id_transaction','=','transactions.id_transaction')
                            ->leftJoin('partners','partners.id_partner','=','locations.id_partner');

            $outlets_mid->join('transaction_payment_midtrans','transaction_payment_midtrans.id_transaction','=','transactions.id_transaction')->whereDate('transaction.transaction_date', '=', $date_trans)->where('transactions.flag_icount','0');
            $outlets_mid->select('outlets.*','partners.*','locations.*','outlets.is_tax as is_tax','transaction_payment_midtrans.id_transaction_payment','transaction_payment_midtrans.payment_type','transaction.transaction_date')->groupBy('outlets.id_outlet','transaction_payment_midtrans.payment_type')->orderBy('outlets.id_outlet', 'DESC');
            $outlets_mid =  $outlets_mid->get()->toArray();

            $outlets_xen->join('transaction_payment_xendits','transaction_payment_xendits.id_transaction','=','transactions.id_transaction')->whereDate('transaction.transaction_date', '=', $date_trans)->where('transactions.flag_icount','0');
            $outlets_xen->select('outlets.*','partners.*','locations.*','outlets.is_tax as is_tax','transaction_payment_xendits.id_transaction_payment_xendit','transaction_payment_xendits.type','transaction.transaction_date')->groupBy('outlets.id_outlet','transaction_payment_xendits.type')->orderBy('outlets.id_outlet', 'DESC');
            $outlets_xen =  $outlets_xen->get()->toArray();

            $outlets_cash->join('transaction_payment_cash','transaction_payment_cash.id_transaction','=','transactions.id_transaction')->whereDate('transaction.transaction_date', '=', $date_trans)->where('transactions.flag_icount','0');
            $outlets_cash->select('outlets.*','partners.*','locations.*','outlets.is_tax as is_tax','transaction_payment_cash.id_transaction_payment_cash','transaction.transaction_date')->groupBy('outlets.id_outlet')->orderBy('outlets.id_outlet', 'DESC');
            $outlets_cash =  $outlets_cash->get()->toArray();

            $outlets = [];
            if($outlets_mid){
                foreach($outlets_mid as $key => $mid){
                    if(isset($mid) && !empty($mid)){
                        $outlets[$key] = $mid;
                    }
                }
            }   
            $index_outlets = count($outlets);
            if($outlets_xen){
                foreach($outlets_xen as $xen){
                    if(isset($xen) && !empty($xen)){
                        $outlets[$index_outlets] = $xen;
                        $index_outlets++;
                    }
                }
            }
            if($outlets_cash){
                foreach($outlets_cash as $cash){
                    if(isset($cash) && !empty($cash)){
                        $outlets[$index_outlets] = $cash;
                        $index_outlets++;
                    }
                }
            }
            $i = 0;

            foreach($outlets as $key => $outlet){
                $transaction = Transaction::join('transaction_home_services','transaction_home_services.id_transaction','=','transactions.id_transaction')
                                ->leftJoin('outlets','outlets.id_outlet','=','transactions.id_outlet')
                                ->leftJoin('transaction_products','transaction_products.id_transaction','transactions.id_transaction')
                                ->leftJoin('users','transactions.id_user','=','users.id')
                                ->leftJoin('products','products.id_product','=','transaction_products.id_product');

                if(isset($outlet['id_transaction_payment'])){
                    $transaction->join('transaction_payment_midtrans','transaction_payment_midtrans.id_transaction','=','transactions.id_transaction');
                    $group = 'transaction_payment_midtrans.payment_type';
                }elseif(isset($outlet['id_transaction_payment_xendit'])){
                    $transaction->join('transaction_payment_xendits','transaction_payment_xendits.id_transaction','=','transactions.id_transaction');
                    $group = 'transaction_payment_xendits.type';
                }elseif(isset($outlet['id_transaction_payment_cash'])){
                    $transaction->join('transaction_payment_cash','transaction_payment_cash.id_transaction','=','transactions.id_transaction');
                }
                $transaction->whereDate('transaction.transaction_date', '=', $date_trans)->where('transactions.flag_icount','0')->where('outlets.id_outlet', '=', $outlet['id_outlet'])->where('transactions.transaction_payment_status','Completed')->whereNotNull('transaction.transaction_date');
                
                if(isset($outlet['id_transaction_payment'])){
                    $transaction->where('transaction_payment_midtrans.payment_type', '=', $outlet['payment_type']);
                }elseif(isset($outlet['id_transaction_payment_xendit'])){
                    $transaction->where('transaction_payment_xendits.type', '=', $outlet['type']);
                }
                $transaction->select('transactions.*','transaction_home_services.*','transaction_products.*','products.*','outlets.outlet_code', 'outlets.outlet_name')
                            ->groupBy('transactions.id_transaction','transaction_products.id_transaction_product');  

                if(!isset($outlet['id_transaction_payment_cash'])){
                    $transaction->groupBy($group);
                }
                $outlets[$key]['trans_date'] = $date_trans;
                $outlets[$key]['due_date'] = $date_trans;
                $transaction->orderBy('transactions.id_transaction', 'DESC');
                $transaction = $transaction->get()->toArray();

                if($transaction){
                    foreach($transaction as $t => $tr){
                        $transaction[$t]['total_product'] = TransactionProduct::Join('transactions','transactions.id_transaction','=','transaction_products.id_transaction')
                                ->where('transactions.id_transaction', $tr['id_transaction'])
                                ->select(DB::raw('
                                        SUM(IF(transaction_products.id_transaction = transactions.id_transaction,transaction_product_price,0)) as total_product
                                    '))
                                ->first()['total_product'];
                    }
                    $outlets[$key]['transaction'] = $transaction;
                }else{
                    unset($outlets[$key]);
                }

                $i++;
            }

            $new = 0;
            $new_outlets = [];
            foreach($outlets as $outlet){
                if($outlet['transaction']){
                    if($outlet['company_type']=='PT IMA'){
                        $company_type = 'ima';
                    }elseif($outlet['company_type']=='PT IMS'){
                        $company_type = 'ims';
                    }
                    $new_trans_non = 0;
                    $new_trans_use = 0;
                    $new_transaction_non = [];
                    $new_transaction = [];
                    foreach($outlet['transaction'] as $t => $tran){
                        if($tran['product_type']=='product'){
                            $cek_prod = ProductProductIcount::where('id_product',$tran['id_product'])->where('company_type',$company_type)->first();
                            if($cek_prod){
                                $prod_icount = ProductIcount::where('id_product_icount',$cek_prod['id_product_icount'])->first();
                                $tran['id_item_icount'] = $prod_icount['id_item'];
                            }
                        }

                        if($tran['transaction_tax']==0){
                            $new_transaction_non[$new_trans_non] = $tran;
                            $num_is_tax = 100;
                            $new_transaction_non[$new_trans_non]['total_price'] = ($tran['transaction_product_price'] * $tran['transaction_product_qty'] - $tran['transaction_product_discount_all']) *  (100/$num_is_tax);
                            $new_trans_non++;
                        }else{
                            $new_transaction[$new_trans_use] = $tran;
                            $num_is_tax = isset($outlet['is_tax']) ? 100 + $outlet['is_tax'] : 111;
                            $new_transaction[$new_trans_use]['total_price'] = ($tran['transaction_product_price'] * $tran['transaction_product_qty'] - $tran['transaction_product_discount_all']) *  (100/$num_is_tax);
                            $new_trans_use++;
                        }

                    }

                    if($new_transaction){
                        $new_outlets[$new] = $outlet;
                        $new_outlets[$new]['transaction'] = $new_transaction;
                        $new_outlets[$new]['ppn'] = $outlet['is_tax'] ?? 11;
                        $new++;

                    }
                    if($new_transaction_non){
                        $new_outlets[$new] = $outlet;
                        $new_outlets[$new]['transaction'] = $new_transaction_non;
                        $new_outlets[$new]['ppn'] = 0;
                        $new++;
                    }
                }
            }   

            $create_order_poo= [];
            foreach($new_outlets as $n => $new_outlet){
                    $create_order_poo[$n] = Icount::ApiCreateOrderPOO($new_outlet, $new_outlet['company_type']);
            }

            foreach($create_order_poo as $c => $poo){
                if($poo['status_code'] == 200 && $poo['response']['Message'] == 'success'){
                    foreach($new_outlets[$c]['transaction'] as $detail_trans){
                        $update_transaction = Transaction::where('id_transaction', $detail_trans['id_transaction'])->update(['flag_icount' => 1]);
                    }
                }
            }

            $log->success('success');
            return response()->json(['status' => 'success','data' => $create_order_poo]);
        } catch (\Exception $e) {
            DB::rollBack();
            $log->fail($e->getMessage());
        }   
    }

    public function revenue_sharing(){
        $log = MyHelper::logCron('Revenue Sharing');
        try{
        $tanggal = 25;
        $tanggal_awal = date('Y-m-d 00:00:00', strtotime(date('Y-m-'.$tanggal) . '- 1 month'));
        $tanggal_akhir = date('Y-m-d 00:00:00', strtotime(date('Y-m-'.$tanggal)));
        $partners = Partner::where(array('locations.cooperation_scheme'=>'Revenue Sharing'))
                    ->join('locations','locations.id_partner','partners.id_partner')
                    ->join('outlets','outlets.id_location','locations.id_location')
                    ->join('transactions','transactions.id_outlet','outlets.id_outlet')
                    ->where('transactions.transaction_payment_status','Completed')
                    ->select('partners.id_partner')
                    ->distinct()
                    ->get();
        $data = array();
        foreach($partners as $value){
            $total_transaksi = 0;
            $beban_outlet = 0;
            $disc = 0;
            $partner = Partner::where(array('id_partner'=>$value['id_partner']))->first();
            $location = Location::where(array('id_partner'=>$value['id_partner']))->first();
            $tax = 0;
            if($partner->is_tax??0 == 1){
            $tax = 10;   
            }
            $percent   = $partner->sharing_percent??0;
            $sharing   = $partner->sharing_value??0;   
            $transaksi = Transaction::wherebetween('transactions.completed_at',[$tanggal_awal,$tanggal_akhir])
                    ->where('transactions.transaction_payment_status','Completed')
                    ->join('outlets','outlets.id_outlet','transactions.id_outlet')
                    ->join('locations','locations.id_location','outlets.id_location')
                    ->where('locations.id_partner',$value['id_partner'])
                    ->get();
                $transaksi_id = array();
                foreach ($transaksi as $va) {
                    array_push($transaksi_id,array('id_transaction'=>$va['id_transaction']));
                    $total_transaksi += $va['transaction_grandtotal']??0;
                    $disc += $va['transaction_discount']??0;
                    $disc += $va['transaction_discount_item']??0;
                    $disc += $va['transaction_discount_bill']??0;
                    $disc += $va['transaction_discount_delivery']??0;
                   }
                   if(count($transaksi)!=0){
                   $b = array(
                        'partner'=>$partner,
                        'location'=>$location,
                        'tanggal_awal'=>$tanggal_awal,
                        'tanggal_akhir'=>$tanggal_akhir,
                        'total_transaksi'=>$total_transaksi,
                        'beban_outlet'=>$beban_outlet,
                        'tax'=>$tax,
                        'percent'=>$percent,
                        'sharing'=>$sharing,
                        'disc'=>$disc,
                        'id_transaction'=>$transaksi_id,
                        'transfer'=> ($total_transaksi*$sharing/100)-$beban_outlet
                    );
                    if ($b['transfer']) {
                        array_push($data,$b);
                    }
                   }
        }
        foreach($data as $n => $request){
                $revenue_sharing = Icount::RevenueSharing($request);
                if($revenue_sharing['response']['Status']=='1' && $revenue_sharing['response']['Message']=='success'){
                      $store_data = [
                        'id_partner'=>$request['partner']['id_partner'],
                        'type'=>'Revenue Sharing',
                        'start_date'=>$request['tanggal_awal'],
                        'end_date'=>$request['tanggal_akhir'],
                        'total_transaksi'=>$request['total_transaksi'],
                        'total_beban'=>$request['beban_outlet'],
                        'tax'=>$request['tax'],
                        'percent'=>$request['percent'],
                        'sharing'=>$request['sharing'],
                        'disc'=>$request['disc'],
                        'transfer'=>$request['transfer'],
                        'status'=>'Proccess',
                        'PurchaseInvoiceID'=>$revenue_sharing['response']['Data'][0]['PurchaseInvoiceID'],
                        'data'=>json_encode($revenue_sharing['response']['Data']),
                        'id_transaction'=>json_encode($request['id_transaction']),
                    ];
                    $store = SharingManagementFee::create($store_data);   
                 }
            }
        $log->success('success');
            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            DB::rollBack();
            $log->fail($e->getMessage());
             return response()->json(['status' => 'fail','message'=>$e->getMessage()]); 
        }      
    }
    public function management_fee(){
        $log = MyHelper::logCron('Management Fee');
        try{
        $tanggal = 25;
        $tanggal_awal = date('Y-m-d 00:00:00', strtotime(date('Y-m-'.$tanggal) . '- 1 month'));
        $tanggal_akhir = date('Y-m-d 00:00:00', strtotime(date('Y-m-'.$tanggal)));
        $partners = Partner::where(array('locations.cooperation_scheme'=>'Management Fee'))
                    ->join('locations','locations.id_partner','partners.id_partner')
                    ->join('outlets','outlets.id_location','locations.id_location')
                    ->join('transactions','transactions.id_outlet','outlets.id_outlet')
                    ->where('transactions.transaction_payment_status','Completed')
                    ->select('partners.id_partner')
                    ->distinct()
                    ->get();
        $data = array();
        foreach($partners as $value){
            $total_transaksi = 0;
            $beban_outlet = 0;
            $disc = 0;
             $hs_fee = 0;
            $partner = Partner::where(array('id_partner'=>$value['id_partner']))->first();
            $location = Location::where(array('id_partner'=>$value['id_partner']))->first();
            $tax = 0;
            if($partner->is_tax??0 == 1){
            $tax = 10;   
            }
            $percent   = $partner->sharing_percent??0;
            $sharing   = $partner->sharing_value??0;   
            $transaksi = Transaction::wherebetween('transactions.completed_at',[$tanggal_awal,$tanggal_akhir])
                    ->where('transactions.transaction_payment_status','Completed')
                    ->join('outlets','outlets.id_outlet','transactions.id_outlet')
                    ->join('locations','locations.id_location','outlets.id_location')
                    ->where('locations.id_partner',$value['id_partner'])
                    ->get();
            $transaksi_id = array();
                foreach ($transaksi as $va) {
                    array_push($transaksi_id,array('id_transaction'=>$va['id_transaction']));
                    $total_transaksi += $va['transaction_grandtotal']??0;
                    $disc += $va['transaction_discount']??0;
                    $disc += $va['transaction_discount_item']??0;
                    $disc += $va['transaction_discount_bill']??0;
                    $disc += $va['transaction_discount_delivery']??0;
                    $transaction_product = TransactionProduct::where('id_transaction',$va['id_transaction'])->get();
                    foreach($transaction_product as $v){
                        $hs_fee = 0;
                     $transaction_breakdown = TransactionBreakdown::where(array('id_transaction_product'=>$v['id_transaction_product'],'type'=>'fee_hs'))->get();
                     foreach($transaction_breakdown as $val){
                         $hs_fee += $val['value']??0;
                     }
                    }
                   }
                    if(count($transaksi)!=0){
                   if($percent==1){
                   $b = array(
                        'partner'=>$partner,
                        'location'=>$location,
                        'tanggal_awal'=>$tanggal_awal,
                        'tanggal_akhir'=>$tanggal_akhir,
                        'total_transaksi'=>$total_transaksi,
                        'beban_outlet'=>$beban_outlet,
                        'tax'=>$tax,
                        'percent'=>$percent,
                        'sharing'=>$sharing,
                        'disc'=>$disc,
                        'id_transaction'=>$transaksi_id,
                        'transfer'=> ($total_transaksi-$hs_fee)*$sharing/100
                    );
                  }else{
                      $b = array(
                        'partner'=>$partner,
                        'location'=>$location,
                        'tanggal_awal'=>$tanggal_awal,
                        'tanggal_akhir'=>$tanggal_akhir,
                        'total_transaksi'=>$total_transaksi,
                        'beban_outlet'=>$beban_outlet,
                        'tax'=>$tax,
                        'percent'=>$percent,
                        'sharing'=>$sharing,
                        'disc'=>$disc,
                        'id_transaction'=>$transaksi_id,
                        'transfer'=> $total_transaksi-$hs_fee-$sharing
                    );
                  }
                    array_push($data,$b);
                    }
        }
       
        foreach($data as $n => $request){
            
                    $management_fee = Icount::ManagementFee($request);
                    if($management_fee['response']['Status']=='1' && $management_fee['response']['Message']=='success'){
                        $store_data = [
                        'id_partner'=>$request['partner']['id_partner'],
                        'type'=>'Management Fee',
                        'start_date'=>$request['tanggal_awal'],
                        'end_date'=>$request['tanggal_akhir'],
                        'total_transaksi'=>$request['total_transaksi'],
                        'total_beban'=>$request['beban_outlet'],
                        'tax'=>$request['tax'],
                        'percent'=>$request['percent'],
                        'sharing'=>$request['sharing'],
                        'disc'=>$request['disc'],
                        'status'=>'Proccess',
                        'PurchaseInvoiceID'=>$management_fee['response']['Data'][0]['PurchaseInvoiceID'],
                        'transfer'=>$request['transfer'],
                        'data'=>json_encode($management_fee['response']['Data']),
                        'id_transaction'=>json_encode($request['id_transaction']),
                    ];
                    $store = SharingManagementFee::create($store_data);    
                }
            }
         $log->success('success');
            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            DB::rollBack();
            $log->fail($e->getMessage());
            return response()->json(['status' => 'fail','message'=>$e->getMessage()]); 
        }      
    }

    public function callbacksharing(CallbackFromIcount $request){
        $data = SharingManagementFee::where(array('PurchaseInvoiceID'=>$request->PurchaseInvoiceID))->update([
            'status'=>$request->status,
            'date_disburse'=>$request->date_disburse,
        ]);
        return response()->json(['status' => 'success','code'=>$data]); 
    }

    function api_secret($length = 40) {
        if (config('app.env') != 'local') {
            return [
                'status' => 'fail',
                'messages' => ['Jangan regenerate secret key server']
            ];
        }
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomStrings = '';
        for ($a = 0; $a < 5; $a++) {
            $random = '';
            for ($i = 0; $i < 5; $i++) {
                $random .= $characters[rand(0, $charactersLength - 1)];
            }
            if($a == 0){
                $randomStrings .= $random;
            }else{
                $randomStrings .= '-'.$random;
            }
        }
        $api_key = Setting::where('key','api_secret')->first();
        if($api_key){
            $data = Setting::where('key','api_secret')->update([
              'value'=>$randomStrings,
          ]);
        }else{
            $data = Setting::create([
               'key'=>'api_secret',
               'value'=>$randomStrings
               
           ]);
        }
        return $randomStrings;
    }   

    function api_key($length = 40) {
        if (config('app.env') != 'local') {
            return [
                'status' => 'fail',
                'messages' => ['Jangan regenerate secret key server']
            ];
        }
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        $api_secret = Setting::where('key','api_key')->first();
        if($api_secret){
            $data = Setting::where('key','api_key')->update([
              'value'=>$randomString,
          ]);
        }else{
            $data = Setting::create([
               'key'=>'api_key',
               'value'=>$randomString
               
           ]);
        }
        return $randomString;
    } 
    function signature(Signature $request) {
        $data = hash_hmac('sha256',$request->PurchaseInvoiceID.$request->status.$request->date_disburse,$request->api_secret);
        return $data;
    }
    function signature_cash_advance(SignatureCashAdvance $request) {
        $data = hash_hmac('sha256',$request->PurchaseDepositRequestID.$request->status.$request->date_disburse,$request->api_secret);
        return $data;
    }
    function signature_loan(SignatureLoan $request) {
        if (config('app.env') != 'local') {
            return [
                'status' => 'fail',
                'messages' => ['Jangan regenerate secret key server']
            ];
        }
        $data = hash_hmac('sha256',$request->BusinessPartnerID.$request->SalesInvoiceID.$request->amount,$request->api_secret);
        return $data;
    }

    public function exportSalesReport(Request $request){
        $post = $request->json()->all();

        $dateStart = date('Y-m-d', strtotime($post['date_start']));
        $dateEnd = date('Y-m-d', strtotime($post['date_end']));
        $idOutlets = $post['id_outlet'];

        $services = Product::select('id_product', 'product_name')->get()->toArray();

        $datas = Transaction::join('transaction_products', 'transaction_products.id_transaction', 'transactions.id_transaction')
            ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
            ->join('products', 'products.id_product', 'transaction_products.id_product')
            ->whereDate('transaction_date', '>=', $dateStart)->whereDate('transaction_date', '<=', $dateEnd)
            ->where('transaction_payment_status', 'Completed')
            ->whereIn('transactions.id_outlet', $idOutlets)
            ->whereIn('transaction_from', ['outlet-service', 'home-service'])
//            ->where('transaction_products.type', 'service')
            ->whereNull('transaction_products.reject_at')
            ->groupBy(DB::raw('DATE(transaction_date)'), 'transactions.id_outlet', 'transaction_products.id_product')
            ->select(DB::raw('DATE(transaction_date) as transaction_date'), 'transactions.id_outlet', 'transaction_products.id_product', 'outlet_name', 'product_name',
                DB::raw('SUM(transaction_products.transaction_product_discount_all) as discount'),
                DB::raw('SUM(transaction_products.transaction_product_price*transaction_products.transaction_product_qty) as gross_sales'),
                DB::raw('SUM(transaction_products.transaction_product_price_base*transaction_products.transaction_product_qty) as net_sales'),
                DB::raw('SUM(transaction_products.transaction_product_price_tax*transaction_products.transaction_product_qty) as tax'),
                DB::raw('SUM(transaction_products.transaction_product_qty) as total'))
            ->get()->toArray();

        $count = Transaction::join('transaction_products', 'transaction_products.id_transaction', 'transactions.id_transaction')
            ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
            ->join('products', 'products.id_product', 'transaction_products.id_product')
            ->whereDate('transaction_date', '>=', $dateStart)->whereDate('transaction_date', '<=', $dateEnd)
            ->where('transaction_payment_status', 'Completed')
            ->whereIn('transactions.id_outlet', $idOutlets)
            ->whereIn('transaction_from', ['outlet-service', 'home-service'])
//            ->where('transaction_products.type', 'service')
            ->whereNull('transaction_products.reject_at')
            ->groupBy(DB::raw('DATE(transaction_date)'), 'transactions.id_outlet')
            ->select(DB::raw('DATE(transaction_date) as transaction_date'), 'transactions.id_outlet', DB::raw('COUNT(Distinct transactions.id_transaction) as total'), DB::raw("group_concat(transaction_products.id_transaction_product separator ',') as trx_product_id"))
            ->get()->toArray();

        $countOrder = $count;
        $tmpCount = [];
        $tmpPayment = [];
        foreach ($countOrder as $count){
            if(!isset($tmpCount[$count['transaction_date'].'|'.$count['id_outlet']])){
                $tmpCount[$count['transaction_date'].'|'.$count['id_outlet']] = 0;
            }
            $tmpCount[$count['transaction_date'].'|'.$count['id_outlet']] += $count['total'];

            if(!isset($tmpPayment[$count['transaction_date'].'|'.$count['id_outlet']])){
                $tmpPayment[$count['transaction_date'].'|'.$count['id_outlet']] = [];
            }
            $explode = explode(',', $count['trx_product_id']);
            $tmpPayment[$count['transaction_date'].'|'.$count['id_outlet']] = array_unique(array_merge($tmpPayment[$count['transaction_date'].'|'.$count['id_outlet']], $explode));
        }

        $tmpData = [];
        $payments = app($this->trx)->availablePayment(new Request())['result'] ?? [];
        foreach ($datas as $data){
            $key = $data['transaction_date'].'|'.$data['id_outlet'];
            if(!isset($tmpData[$key])){
                $tmpData[$key] = [
                    'Date' => date('Y-m-d', strtotime($data['transaction_date'])),
                    'Outlet' => $data['outlet_name']
                ];
            }

            foreach ($services as $service){
                $serviceName = str_replace('"', "", $service['product_name']);
                if(empty($tmpData[$key][$serviceName])){
                    $tmpData[$key][$serviceName] = 0;
                }
                if($service['id_product'] == $data['id_product']){
                    $tmpData[$key][$serviceName] = $tmpData[$key][$serviceName] + $data['total'];
                }
            }

            if(!isset($tmpData[$key]['Qty Order'])){
                $tmpData[$key]['Qty Order'] = 0;
                $tmpData[$key]['Qty Item'] = 0;
                $tmpData[$key]['Gross Sales'] = 0;
                $tmpData[$key]['Refund'] = 0;
                $tmpData[$key]['Promo'] = 0;
                $tmpData[$key]['Discount'] = 0;
                $tmpData[$key]['Net Sales'] = 0;
                $tmpData[$key]['Tax'] = 0;
                $tmpData[$key]['Rounding'] = 0;
                $tmpData[$key]['Total Sales'] = 0;
            }

            $tmpData[$key]['Qty Order'] = $tmpCount[$key] ??0;
            $tmpData[$key]['Qty Item'] += $data['total'];
            $tmpData[$key]['Gross Sales'] += $data['gross_sales'];
            $tmpData[$key]['Refund'] = 0;
            $tmpData[$key]['Discount'] += $data['discount'];
            $tmpData[$key]['Net Sales'] += ($data['gross_sales'] - $data['discount']);
            $tmpData[$key]['Tax'] += $data['tax'];
            $tmpData[$key]['Rounding'] = 0;
            $tmpData[$key]['Total Sales'] += ($data['gross_sales'] - $data['discount'] - $data['tax']);

            foreach ($payments as $payment){
                $paymentName = $payment['payment_gateway'].'-'.$payment['payment_method'];
                if($payment['payment_gateway'] == 'Cash'){
                    $paymentName = $payment['payment_method'];
                }

                if(!isset($tmpData[$key]['Qty Order '.$paymentName])){
                    $tmpData[$key]['Qty Order '.$paymentName] = [];
                    $tmpData[$key]['Qty Item '.$paymentName] = 0;
                    $tmpData[$key]['Gross Sales '.$paymentName] = 0;
                    $tmpData[$key]['Refund '.$paymentName] = 0;
                    $tmpData[$key]['Promo '.$paymentName] = 0;
                    $tmpData[$key]['Discount '.$paymentName] = 0;
                    $tmpData[$key]['Net Sales '.$paymentName] = 0;
                    $tmpData[$key]['Tax '.$paymentName] = 0;
                    $tmpData[$key]['Rounding '.$paymentName] = 0;
                    $tmpData[$key]['Total Sales '.$paymentName] = 0;
                }
            }
        }

        $tmpPaymentNotFound = [];
        foreach ($tmpData as $key=>$tmp){
            $getTrxProductId = $tmpPayment[$key];
            $transactions = Transaction::join('transaction_products', 'transaction_products.id_transaction', 'transactions.id_transaction')
                ->leftJoin('transaction_payment_midtrans', 'transactions.id_transaction', '=', 'transaction_payment_midtrans.id_transaction')
                ->leftJoin('transaction_payment_xendits', 'transactions.id_transaction', '=', 'transaction_payment_xendits.id_transaction')
                ->whereIn('id_transaction_product', $getTrxProductId)->select('transactions.trasaction_payment_type', 'transaction_products.*', 'transaction_payment_xendits.type', 'payment_type')->get()->toArray();

            foreach ($transactions as $transaction){
                if(!empty($transaction['type'])){
                    $paymentGateway = 'Xendit-'.$transaction['type'];
                }elseif (!empty($transaction['payment_type'])){
                    $paymentGateway = 'Midtrans-'.$transaction['payment_type'];
                }else{
                    $paymentGateway = $transaction['trasaction_payment_type'];
                }

                $index = $paymentGateway;
                if(!isset($tmpData[$key]['Qty Order '.$index])){
                    $tmpPaymentNotFound[] = $index;
                    $tmpData[$key]['Qty Order '.$index] = [];
                    $tmpData[$key]['Qty Item '.$index] = 0;
                    $tmpData[$key]['Gross Sales '.$index] = 0;
                    $tmpData[$key]['Refund '.$index] = 0;
                    $tmpData[$key]['Promo '.$index] = 0;
                    $tmpData[$key]['Discount '.$index] = 0;
                    $tmpData[$key]['Net Sales '.$index] = 0;
                    $tmpData[$key]['Tax '.$index] = 0;
                    $tmpData[$key]['Rounding '.$index] = 0;
                    $tmpData[$key]['Total Sales '.$index] = 0;
                }

                $tmpData[$key]['Qty Order '.$index][] = $transaction['id_transaction'];
                $tmpData[$key]['Qty Item '.$index] += $transaction['transaction_product_qty'];
                $tmpData[$key]['Gross Sales '.$index] += ($transaction['transaction_product_price'] * $transaction['transaction_product_qty']);
                $tmpData[$key]['Refund '.$index] = 0;
                $tmpData[$key]['Discount '.$index] += $transaction['transaction_product_discount_all'];
                $tmpData[$key]['Net Sales '.$index] += (($transaction['transaction_product_price'] * $transaction['transaction_product_qty']) - $transaction['transaction_product_discount_all']);
                $tmpData[$key]['Tax '.$index] += $transaction['transaction_product_price_tax'];
                $tmpData[$key]['Rounding '.$index] = 0;
                $tmpData[$key]['Total Sales '.$index] += ($transaction['transaction_product_price'] - $transaction['transaction_product_discount_all'] - $transaction['transaction_product_price_tax']);
            }
        }

        $res = [];
        foreach ($tmpData as $index=>$dt){
            foreach ($tmpPaymentNotFound as $val){
                if(!isset($dt['Qty Order '.$val])){
                    $dt['Qty Order '.$val] = [];
                    $dt['Qty Item '.$val] = 0;
                    $dt['Gross Sales '.$val] = 0;
                    $dt['Refund '.$val] = 0;
                    $dt['Promo '.$val] = 0;
                    $dt['Discount '.$val] = 0;
                    $dt['Net Sales '.$val] = 0;
                    $dt['Tax '.$val] = 0;
                    $dt['Rounding '.$val] = 0;
                    $dt['Total Sales '.$val] = 0;
                }
            }
            foreach ($dt as $key=>$value){
                if(is_array($value)){
                    $dt[$key] = count(array_unique($value));
                }

                if(is_float($value)){
                    $value = (int)$value;
                }

                if(is_int($value)){
                    $dt[$key] = $value;
                }

                if($value == 0){
                    $dt[$key] = (string)$value;
                }
            }
            $res[] = $dt;
        }

        return response()->json(['status' => 'success', 'result' => $res]);
    }

    public function completePaymentFindingTrx(Request $request){
        $post = $request->json()->all();

        if(empty($post['transaction_receipt_number'])){
            return response()->json(['status' => 'fail', 'messages' => ['Receipt number can not be empty']]);
        }

        $detail = Transaction::where('transaction_receipt_number', $post['transaction_receipt_number'])
                    ->where('transaction_payment_status', 'Cancelled')->whereNotIn('trasaction_payment_type', ['Cash'])->first();

        if($detail){
            if($detail['trasaction_payment_type'] == 'Midtrans'){
                $payment = TransactionPaymentMidtran::where('id_transaction', $detail['id_transaction'])->first();
                $detail['payment_method'] = $payment['payment_type']??'';
                $detail['id_payment'] = $payment['vt_transaction_id']??$detail['transaction_receipt_number']??'';
            }elseif($detail['trasaction_payment_type'] == 'Xendit'){
                $payment = TransactionPaymentXendit::where('id_transaction', $detail['id_transaction'])->first();
                $detail['payment_method'] = $payment['type']??'';
                $detail['id_payment'] = $payment['xendit_id']??'';
            }
            $detail['grandtotal'] = number_format($detail['transaction_grandtotal'],0,",",".");
        }

        return response()->json(MyHelper::checkGet($detail));
    }

    public function completedPayment(Request $request){
        $post = $request->json()->all();

        if(empty($post['transaction_receipt_number'])){
            return response()->json(['status' => 'fail', 'messages' => ['Receipt number can not be empty']]);
        }

        $trx = Transaction::where('transaction_receipt_number', $post['transaction_receipt_number'])->where('transaction_payment_status', 'Cancelled')->first();

        if(empty($trx)){
            return response()->json(['status' => 'fail', 'messages' => ['Transaction '.$post['transaction_receipt_number'].' not found']]);
        }

        $statusCompleted = false;
        if($trx['trasaction_payment_type'] == 'Midtrans'){
            $midtransStatus = Midtrans::status($trx['id_transaction']);

            if(!empty($midtransStatus['status_code']) && $midtransStatus['status_code'] == 200) {
                $statusCompleted = true;
            }
        }elseif($trx['trasaction_payment_type'] == 'Xendit'){
            $dtXendit = TransactionPaymentXendit::where('id_transaction', $trx['id_transaction'])->first();
            if(empty($dtXendit['xendit_id'])){
                $dtXendit['xendit_id'] = $post['id_payment'];
            }

            $status = app('Modules\Xendit\Http\Controllers\XenditController')->checkStatus($dtXendit['xendit_id'], $dtXendit['type']);
            if($status && ($status['status'] == 'COMPLETED' || $status['status'] == 'PAID' || $status['status'] == 'SETTLED')){
                $statusCompleted = true;
            }
        }

        if($statusCompleted){
            $completed = $trx->triggerPaymentCompletedFromCancelled();
            return response()->json(MyHelper::checkUpdate($completed));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Update Failed! Transactions with receipt number '.$post['transaction_receipt_number'].' are not paid by the customer']]);
        }
    }
}
