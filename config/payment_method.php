<?php

return [
    'cash' => [
        'payment_gateway' => 'Cash',
        'payment_method'  => 'Cash',
        'status'          => 'platform:webapps',
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_cash.png',
        'text'            => 'Tunai',
        'text_2'          => null
    ],
    'midtrans_gopay' => [
        'payment_gateway' => 'Midtrans',
        'payment_method'  => 'Gopay',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_gopay.png',
        'text'            => 'GoPay',
        'text_2'          => null
    ],
    'midtrans_cc'    => [
        'payment_gateway' => 'Midtrans',
        'payment_method'  => 'Credit Card',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_creditcard.png',
        'text'            => 'Debit/Credit Card',
        'text_2'          => null
    ],
    'midtrans_banktransfer'    => [
        'payment_gateway' => 'Midtrans',
        'payment_method'  => 'Bank Transfer',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_banktransfer.png',
        'text'            => 'Bank Transfer',
        'text_2'          => null
    ],
    'midtrans_akulaku'    => [
        'payment_gateway' => 'Midtrans',
        'payment_method'  => 'akulaku',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_akulaku.png',
        'text'            => 'Akulaku',
        'text_2'          => null
    ],
    'midtrans_qris'    => [
        'payment_gateway' => 'Midtrans',
        'payment_method'  => 'shopeepay-qris',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_shopeepay-qris.png',
        'text'            => 'ShopeePay/e-Wallet Lainnya',
        'text_2'          => null
    ],
    'ipay88_cc'      => [
        'payment_gateway' => 'Ipay88',
        'payment_method'  => 'Credit Card',
        'status'          => 0, //'credit_card_payment_gateway:Ipay88',
        'logo'            => 0,
        'text'            => 'Debit/Credit Card',
        'text_2'          => null,
        'available_time'    => [
            'start' => '00:00',
            'end'   => '23:45',
        ]
    ],
    'ipay88_ovo'     => [
        'payment_gateway' => 'Ipay88',
        'payment_method'  => 'Ovo',
        'status'          => 0,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_ovo_pay.png',
        'text'            => 'OVO',
        'text_2'          => null,
        'available_time'    => [
            'start' => '00:00',
            'end'   => '23:45',
        ]
    ],
    'ovo'            => [
        'payment_gateway' => 'Ovo',
        'payment_method'  => 'Ovo',
        'status'          => 0,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_ovo_pay.png',
        'text'            => 'OVO',
        'text_2'          => null
    ],
    'shopeepay'      => [
        'payment_gateway' => 'Shopeepay',
        'payment_method'  => 'Shopeepay',
        'status'          => 0,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_shopee_pay.png',
        'text'            => 'ShopeePay',
        'text_2'          => null,
        'available_time'    => [
            'start' => '03:00',
            'end'   => '23:45',
        ]
    ],
    'online_payment' => [
        'payment_gateway' => 'Midtrans',
        'payment_method'  => 'Midtrans',
        'status'          => 0,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_online_payment.png',
        'text'            => 'Online Payment',
        'text_2'          => null,
    ],
    'xendit_ovo'          => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'Ovo',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_ovo_pay.png',
        'text'            => 'OVO',
        'text_2'          => null
    ],
    'xendit_dana'         => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'Dana',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_dana.png',
        'text'            => 'DANA',
        'text_2'          => null
    ],
    'xendit_linkaja'      => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'Linkaja',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_linkaja.png',
        'text'            => 'LinkAJa',
        'text_2'          => null
    ],
    'xendit_shopeepay'      => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'SHOPEEPAY',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_shopee_pay.png',
        'text'            => 'ShopeePay',
        'text_2'          => 'QRIS',
    ],
    'xendit_kredivo'      => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'KREDIVO',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_kredivo.png',
        'text'            => 'Kredivo',
        'text_2'          => null
    ],
    'xendit_qris'      => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'QRIS',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_qris.png',
        'text'            => 'QRIS',
        'text_2'          => null
    ],
    'xendit_credit_card'      => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'CREDIT_CARD',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_creditcard.png',
        'text'            => 'Credit Card',
        'text_2'          => null
    ],
    'xendit_bank_transfer'      => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'BANK_TRANSFER',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_banktransfer.png',
        'text'            => 'Virtual Account',
        'text_2'          => null
    ],
];
