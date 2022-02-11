<?php

use Illuminate\Database\Seeder;

class SettingsTableSeeder extends Seeder
{
    public function run()
    {


        \DB::table('settings')->delete();

        \DB::table('settings')->insert(array(
            0 =>
            array(
                'id_setting' => 1,
                'key' => 'transaction_grand_total_order',
                'value' => 'subtotal,service,discount,shipping,tax',
                'value_text' => NULL,
                'created_at' => '2018-05-09 10:54:31',
                'updated_at' => '2018-05-09 10:54:32',
            ),
            1 =>
            array(
                'id_setting' => 2,
                'key' => 'transaction_service_formula',
                'value' => '( subtotal ) * value',
                'value_text' => NULL,
                'created_at' => '2018-05-09 10:54:31',
                'updated_at' => '2018-05-09 10:54:32',
            ),
            2 =>
            array(
                'id_setting' => 3,
                'key' => 'transaction_discount_formula',
                'value' => '( subtotal + service ) * value',
                'value_text' => NULL,
                'created_at' => '2018-05-09 10:54:31',
                'updated_at' => '2018-05-09 10:54:32',
            ),
            3 =>
            array(
                'id_setting' => 4,
                'key' => 'transaction_tax_formula',
                'value' => '( subtotal + service - discount + shipping ) * value',
                'value_text' => NULL,
                'created_at' => '2018-05-09 10:54:31',
                'updated_at' => '2018-05-09 10:54:32',
            ),
            4 =>
            array(
                'id_setting' => 5,
                'key' => 'point_acquisition_formula',
                'value' => '( subtotal ) / value',
                'value_text' => NULL,
                'created_at' => '2018-05-09 10:54:31',
                'updated_at' => '2018-05-09 10:54:32',
            ),
            5 =>
            array(
                'id_setting' => 6,
                'key' => 'cashback_acquisition_formula',
                'value' => '( subtotal + service ) / value',
                'value_text' => NULL,
                'created_at' => '2018-05-09 10:54:31',
                'updated_at' => '2018-05-09 10:54:32',
            ),
            6 =>
            array(
                'id_setting' => 7,
                'key' => 'transaction_delivery_standard',
                'value' => 'subtotal',
                'value_text' => NULL,
                'created_at' => '2018-05-09 10:54:31',
                'updated_at' => '2018-05-09 10:54:32',
            ),
            7 =>
            array(
                'id_setting' => 8,
                'key' => 'transaction_delivery_min_value',
                'value' => '100000',
                'value_text' => NULL,
                'created_at' => '2018-05-09 10:54:31',
                'updated_at' => '2018-05-09 10:54:32',
            ),
            8 =>
            array(
                'id_setting' => 9,
                'key' => 'transaction_delivery_max_distance',
                'value' => '10',
                'value_text' => NULL,
                'created_at' => '2018-05-09 10:54:31',
                'updated_at' => '2018-05-09 10:54:32',
            ),
            9 =>
            array(
                'id_setting' => 10,
                'key' => 'transaction_delivery_pricing',
                'value' => 'By KM',
                'value_text' => NULL,
                'created_at' => '2018-05-09 10:54:31',
                'updated_at' => '2018-05-09 10:54:32',
            ),
            10 =>
            array(
                'id_setting' => 11,
                'key' => 'transaction_delivery_price',
                'value' => '5000',
                'value_text' => NULL,
                'created_at' => '2018-05-09 10:54:31',
                'updated_at' => '2018-05-09 10:54:32',
            ),
            11 =>
            array(
                'id_setting' => 12,
                'key' => 'default_outlet',
                'value' => '1',
                'value_text' => NULL,
                'created_at' => '2018-05-09 11:43:51',
                'updated_at' => '2018-05-09 11:43:53',
            ),
            12 =>
            array(
                'id_setting' => 13,
                'key' => 'about',
                'value' => NULL,
                'value_text' => '<h1>About US </h1>',
                'created_at' => '2018-05-09 11:43:51',
                'updated_at' => '2018-05-09 11:43:53',
            ),
            13 =>
            array(
                'id_setting' => 14,
                'key' => 'tos',
                'value' => NULL,
                'value_text' => '<h1>Terms of Service</h1>',
                'created_at' => '2018-05-09 11:43:51',
                'updated_at' => '2018-05-09 11:43:53',
            ),
            14 =>
            array(
                'id_setting' => 15,
                'key' => 'contact',
                'value' => NULL,
                'value_text' => '<h1>Contact US</h1>',
                'created_at' => '2018-05-09 11:43:51',
                'updated_at' => '2018-05-09 11:43:53',
            ),
            15 =>
            array(
                'id_setting' => 16,
                'key' => 'greetings_morning',
                'value' => '05:00:00',
                'value_text' => NULL,
                'created_at' => '2018-05-09 14:47:16',
                'updated_at' => '2018-05-09 14:47:16',
            ),
            16 =>
            array(
                'id_setting' => 17,
                'key' => 'greetings_afternoon',
                'value' => '11:00:00',
                'value_text' => NULL,
                'created_at' => '2018-05-09 14:47:16',
                'updated_at' => '2018-05-09 14:47:16',
            ),
            17 =>
            array(
                'id_setting' => 18,
                'key' => 'greetings_evening',
                'value' => '17:00:00',
                'value_text' => NULL,
                'created_at' => '2018-05-09 14:47:16',
                'updated_at' => '2018-05-09 14:47:16',
            ),
            18 =>
            array(
                'id_setting' => 19,
                'key' => 'greetings_latenight',
                'value' => '22:00:00',
                'value_text' => NULL,
                'created_at' => '2018-05-09 14:47:16',
                'updated_at' => '2018-05-09 14:47:16',
            ),
            19 =>
            array(
                'id_setting' => 20,
                'key' => 'point_conversion_value',
                'value' => '10000',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            20 =>
            array(
                'id_setting' => 21,
                'key' => 'cashback_conversion_value',
                'value' => '10',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            21 =>
            array(
                'id_setting' => 22,
                'key' => 'service',
                'value' => '0.05',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            22 =>
            array(
                'id_setting' => 23,
                'key' => 'tax',
                'value' => '0.1',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            23 =>
            array(
                'id_setting' => 24,
                'key' => 'cashback_maximum',
                'value' => '100000',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            24 =>
            array(
                'id_setting' => 25,
                'key' => 'default_home_text1',
                'value' => 'Please Login / Register',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            25 =>
            array(
                'id_setting' => 26,
                'key' => 'default_home_text2',
                'value' => 'to enjoy the full experience',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            26 =>
            array(
                'id_setting' => 27,
                'key' => 'default_home_text3',
                'value' => 'of Gudeg Techno Mobile Apps',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            27 =>
            array(
                'id_setting' => 28,
                'key' => ' 	default_home_image',
                'value' => 'img/7991531810380.jpg',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            28 =>
            array(
                'id_setting' => 29,
                'key' => 'api_key',
                'value' => 'c5d5410e7f14ba184b44f51bf3aaa691',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            29 =>
            array(
                'id_setting' => 30,
                'key' => 'api_secret',
                'value' => 'C82FBB254221B637AF1CF1E6007C83FD6F5D8FD272DCB5CE915CA486A855C456',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            30 =>
            array(
                'id_setting' => 31,
                'key' => 'default_home_splash_screen',
                'value' => 'img/splash.jpg',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            31 =>
            array(
                'id_setting' => 32,
                'key' => 'email_sync_menu',
                'value' => NULL,
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            32 =>
            array(
                'id_setting' => 33,
                'key' => 'qrcode_expired',
                'value' => 10,
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            33 =>
            array(
                'id_setting' => 34,
                'key' => 'delivery_services',
                'value' => 'Delivery Services',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            34 =>
            array(
                'id_setting' => 35,
                'key' => 'delivery_service_content',
                'value' => 'Big Order Delivery Service',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            35 =>
            array(
                'id_setting' => 36,
                'key' => 'enquiries_subject_list',
                'value' => 'Subject',
                // 'value_text' => 'Customer Feedback* outlet,visiting_time,messages| Marketing Partnership* messages| Business Development* messages| Career* position',
                'value_text' => 'Kritik, Saran & Keluhan* outlet,visiting_time,messages| Pengubahan Data Diri* messages| Lain - Lain* messages',
                'created_at' => '2019-10-03 12:00:00',
                'updated_at' => '2019-10-03 12:00:00',
            ),
            36 =>
            array(
                'id_setting' => 37,
                'key' => 'enquiries_position_list',
                'value' => 'Position',
                'value_text' => 'Part Time, Supervisor',
                'created_at' => '2019-10-03 12:00:00',
                'updated_at' => '2019-10-03 12:00:00',
            ),37 =>
            array (
                'id_setting' => 38,
                'key' => 'text_menu_main',
                'value' => NULL,
                'value_text' => '{"menu1":{"text_menu":"Home","text_header":"Home","text_color":"","icon1":"","icon2":""},"menu2":{"text_menu":"Promo","text_header":"Promo","text_color":"","icon1":"","icon2":""},"menu3":{"text_menu":"Order","text_header":"Order","text_color":"","icon1":"","icon2":""},"menu4":{"text_menu":"Riwayat","text_header":"Riwayat","text_color":"","icon1":"","icon2":""},"menu5":{"text_menu":"Other","text_header":"Other","text_color":"","icon1":"","icon2":""}}',
                'created_at' => '2019-10-08 09:03:16',
                'updated_at' => '2019-10-08 09:03:19',
            ),
            38 =>
            array (
                'id_setting' => 39,
                'key' => 'text_menu_other',
                'value' => NULL,
                'value_text' => '{"menu1":{"text_menu":"Profile","text_header":"Profile","text_color":"","icon":""},"menu2":{"text_menu":"Membership","text_header":"Membership","text_color":"","icon":""},"menu3":{"text_menu":"Outlet","text_header":"Outlet","text_color":"","icon":""},"menu4":{"text_menu":"Kabar","text_header":"Kabar","text_color":"","icon":""},"menu5":{"text_menu":"Lokasi Favorit","text_header":"LOKASI FAVORIT","text_color":"","icon":""},"menu6":{"text_menu":"Tentang","text_header":"Tentang","text_color":"","icon":""},"menu7":{"text_menu":"FAQ","text_header":"FAQ","text_color":"","icon":""},"menu8":{"text_menu":"Ketentuan","text_header":"Ketentuan","text_color":"","icon":""},"menu9":{"text_menu":"Kontak","text_header":"Kontak","text_color":"","icon":""}}',
                'created_at' => '2019-10-08 09:04:01',
                'updated_at' => '2019-10-08 09:04:02',
            ),
            39 =>
            array(
                'id_setting' => 40,
                'key' => 'point_range_start',
                'value' => '0',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            40 =>
            array(
                'id_setting' => 41,
                'key' => 'point_range_end',
                'value' => '1000000',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            41 =>
            array(
                'id_setting' => 42,
                'key' => 'count_login_failed',
                'value' => '3',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            42 =>
            array(
                'id_setting' => 43,
                'key' => 'processing_time',
                'value' => '15',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            43 =>
            array(
                'id_setting' => 44,
                'key' => 'home_subscription_title',
                'value' => 'Subscription',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            44 =>
            array(
                'id_setting' => 45,
                'key' => 'home_subscription_sub_title',
                'value' => 'Banyak untungnya kalo berlangganan',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            45 =>
                array(
                    'id_setting' => 46,
                    'key' => 'order_now_title',
                    'value' => 'Pesan Sekarang',
                    'value_text' => NULL,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            47 =>
                array(
                    'id_setting' => 48,
                    'key' => 'order_now_sub_title_success',
                    'value' => 'Cek outlet terdekatmu',
                    'value_text' => NULL,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            48 =>
                array(
                    'id_setting' => 49,
                    'key' => 'order_now_sub_title_fail',
                    'value' => 'Tidak ada outlet yang tersedia',
                    'value_text' => NULL,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            49 =>
            array(
                'id_setting' => 50,
                'key' => 'payment_messages_cash',
                'value' => 'Anda akan membeli Voucher %deals_title% dengan harga %cash% ?',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            50 =>
                array(
                    'id_setting' => 51,
                    'key' => 'welcome_voucher_setting',
                    'value' => '1',
                    'value_text' => NULL,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            51 =>
            array(
                'id_setting' => 52,
                'key' => 'message_mysubscription_empty_header',
                'value' => 'Anda belum memiliki Paket',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            52 =>
            array(
                'id_setting' => 53,
                'key' => 'message_mysubscription_empty_content',
                'value' => 'Banyak keuntungan dengan berlangganan',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            53 =>
            array(
                'id_setting' => 54,
                'key' => 'message_myvoucher_empty_header',
                'value' => 'Anda belum memiliki Kupon',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            54 =>
            array(
                'id_setting' => 55,
                'key' => 'message_myvoucher_empty_content',
                'value' => 'Potongan menarik untuk setiap pembelian',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            55 =>
            array(
                'id_setting' => 56,
                'key' => 'home_deals_title',
                'value' => 'Penawaran Spesial',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            56 =>
            array(
                'id_setting' => 57,
                'key' => 'home_deals_sub_title',
                'value' => 'Potongan menarik untuk setiap pembelian',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
           57 =>
           array(
                'id_setting' => 58,
                'key' => 'subscription_payment_messages',
                'value' => 'Kamu yakin ingin membeli subscription ini',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            58 =>
            array(
                'id_setting' => 59,
                'key' => 'subscription_payment_messages_point',
                'value' => 'Anda akan menukarkan %point% points anda dengan subscription %subscription_title%?',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            59 =>
            array(
                'id_setting' => 60,
                'key' => 'subscription_payment_messages_cash',
                'value' => 'Kamu yakin ingin membeli subscription %subscription_title% dengan harga %cash% ?',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            60 =>
            array(
                'id_setting' => 61,
                'key' => 'subscription_payment_success_messages',
                'value' => 'Anda telah membeli subscription %subscription_title%',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            61 =>
            array(
                'id_setting' => 62,
                'key' => 'max_order',
                'value' => '50',
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            62 =>
                array(
                    'id_setting' => 63,
                    'key' => 'processing_time_text',
                    'value' => null,
                    'value_text' => 'Set pickup time minimum %processing_time% minutes from now',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            63 =>
                array(
                    'id_setting' => 64,
                    'key' => 'favorite_already_exists_message',
                    'value' => null,
                    'value_text' => 'Favorite already exists',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            64 =>
                array(
                    'id_setting' => 65,
                    'key' => 'favorite_add_success_message',
                    'value' => null,
                    'value_text' => 'Success add favorite',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            65 =>
                array(
                    'id_setting' => 66,
                    'key' => 'popup_max_refuse',
                    'value' => 3,
                    'value_text' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            66 =>
                array(
                    'id_setting' => 67,
                    'key' => 'popup_min_interval',
                    'value' => 15,
                    'value_text' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            67 =>
                array(
                    'id_setting' => 68,
                    'key' => 'description_product_discount',
                    'value' => 'Anda berhak mendapatkan potongan %discount% untuk pembelian %product%. Maksimal %qty% buah untuk setiap produk',
                    'value_text' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            68 =>
                array(
                    'id_setting' => 69,
                    'key' => 'description_tier_discount',
                    'value' => 'Anda berhak mendapatkan potongan setelah melakukan pembelian %product% sebanyak %minmax%',
                    'value_text' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            69 =>
                array(
                    'id_setting' => 70,
                    'key' => 'description_buyxgety_discount',
                    'value' => 'Anda berhak mendapatkan potongan setelah melakukan pembelian %product% sebanyak %minmax%',
                    'value_text' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            70 =>
                array(
                    'id_setting' => 71,
                    'key' => 'error_product_discount',
                    'value' => null,
                    'value_text' => 'Promo hanya akan berlaku jika anda membeli <b>%product%</b>.',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            71 =>
                array(
                    'id_setting' => 72,
                    'key' => 'error_tier_discount',
                    'value' => null,
                    'value_text' => 'Promo hanya akan berlaku jika anda membeli <b>%product%</b> sebanyak <b>%minmax%</b>.',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            72 =>
                array(
                    'id_setting' => 73,
                    'key' => 'error_buyxgety_discount',
                    'value' => null,
                    'value_text' => 'Promo hanya akan berlaku jika anda membeli <b>%product%</b> sebanyak <b>%minmax%</b>.',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            73 =>
                array(
                    'id_setting' => 74,
                    'key' => 'promo_error_title',
                    'value' => 'promo tidak berlaku',
                    'value_text' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            74 =>
                array(
                    'id_setting' => 75,
                    'key' => 'promo_error_ok_button',
                    'value' => 'tambah item',
                    'value_text' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            75 =>
                array(
                    'id_setting' => 76,
                    'key' => 'promo_error_cancel_button',
                    'value' => 'hapus promo',
                    'value_text' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            
            76 =>
                array(
                   'id_setting' => 77,
                    'key' => 'phone_setting',
                    'value' => NULL,
                    'value_text' => '{"min_length_number":"9","max_length_number":"14","message_failed":"Invalid number format","message_success":"Valid number format"}',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            77 =>
                array(
                   'id_setting' => 78,
                    'key' => 'coupon_confirmation_pop_up',
                    'value' => NULL,
                    'value_text' => 'Kupon <b>%title%</b> untuk pembelian <b>%product%</b> akan digunakan pada transaksi ini',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            78 =>
                array(
                    'id_setting' => 79,
                    'key' => 'maintenance_mode',
                    'value' => '0',
                    'value_text' => '{"message":"there is maintenance","image":""}',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            79 =>
                array(
                    'id_setting' => 80,
                    'key' => 'description_product_discount_no_qty',
                    'value' => 'Anda berhak mendapatkan potongan %discount% untuk pembelian %product%.',
                    'value_text' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            80 =>
                array(
                    'id_setting' => 81,
                    'key' => 'promo_error_ok_button_v2',
                    'value' => 'Ok',
                    'value_text' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            81 =>
                array(
                    'id_setting' => 82,
                    'key' => 'global_setting_fee',
                    'value' => null,
                    'value_text' => '{"fee_outlet":"","fee_central":""}',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            82 =>
                array(
                    'id_setting' => 83,
                    'key' => 'global_setting_point_charged',
                    'value' => null,
                    'value_text' => '{"outlet":"","central":""}',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            83 =>
                array(
                    'id_setting' => 84,
                    'key' => 'disburse_auto_approve_setting',
                    'value' => 0,
                    'value_text' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            84 =>
                array(
                    'id_setting' => 85,
                    'key' => 'setting_expired_time_email_verify',
                    'value' => 30,
                    'value_text' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            85 =>
                array(
                    'id_setting' => 86,
                    'key' => 'disburse_global_setting_time_to_sent',
                    'value' => 4,
                    'value_text' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
           86 =>
                array(
                    'id_setting' => 87,
                    'key' => 'setting_expired_otp',
                    'value' => 30,
                    'value_text' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            87 =>
                array(
                    'id_setting' => 88,
                    'key' => 'otp_rule_request',
                    'value' => null,
                    'value_text' => '{"hold_time": 60, "max_value_request": 20}',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            88 =>
                array(
                    'id_setting' => 89,
                    'key' => 'email_verify_rule_request',
                    'value' => null,
                    'value_text' => '{"hold_time": 60, "max_value_request": 20}',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            89 =>
                array(
                    'id_setting' => 90,
                    'key' => 'transaction_set_time_notif_message',
                    'value' => null,
                    'value_text' => '{"title_5mnt": "5 menit Pesananmu siap lho", "msg_5mnt": "hai %name%, siap - siap ke outlet %outlet_name% yuk. Pesananmu akan siap 5 menit lagi nih.","title_15mnt": "15 menit Pesananmu siap lho", "msg_15mnt": "hai %name%, siap - siap ke outlet %outlet_name% yuk. Pesananmu akan siap 15 menit lagi nih."}',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            90 =>
                array(
                    'id_setting' => 91,
                    'key' => 'transaction_set_time_notif_message_outlet',
                    'value' => null,
                    'value_text' => '{"title_5mnt": "Pesanan %order_id% akan diambil 5 menit lagi", "msg_5mnt": "Pesanan %order_id% atas nama %name% akan diambil 5 menit lagi nih, segera disiapkan ya !","title_15mnt": "Pesanan %order_id% akan diambil 15 menit lagi", "msg_15mnt": "Pesanan %order_id% atas nama %name% akan diambil 15 menit lagi nih, segera disiapkan ya !"}',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            91 =>
                array(
                    'id_setting' => 92,
                    'key' => 'description_product_discount_brand',
                    'value' => 'Anda berhak mendapatkan potongan %discount% untuk pembelian %product%. Maksimal %qty% buah untuk setiap produk di %brand%',
                    'value_text' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            92 =>
                array(
                    'id_setting' => 93,
                    'key' => 'description_tier_discount_brand',
                    'value' => 'Anda berhak mendapatkan potongan setelah melakukan pembelian %product% sebanyak %minmax% di %brand%',
                    'value_text' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            93 =>
                array(
                    'id_setting' => 94,
                    'key' => 'description_buyxgety_discount_brand',
                    'value' => 'Anda berhak mendapatkan potongan setelah melakukan pembelian %product% sebanyak %minmax% di %brand%',
                    'value_text' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            94 =>
                array(
                    'id_setting' => 95,
                    'key' => 'description_product_discount_brand_no_qty',
                    'value' => 'Anda berhak mendapatkan potongan %discount% untuk pembelian %product% di %brand%',
                    'value_text' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            95 =>
                array(
                    'id_setting' => 96,
                    'key' => 'welcome_subscription_setting',
                    'value' => '1',
                    'value_text' => NULL,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            96 =>
                array(
                    'id_setting' => 97,
                    'key' => 'disburse_setting_fee_transfer',
                    'value' => NULL,
                    'value_text' => NULL,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            97 =>
                array(
                    'id_setting' => 98,
                    'key' => 'disburse_setting_email_send_to',
                    'value' => NULL,
                    'value_text' => '{"outlet_franchise":null,"outlet_central":null}',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            98 =>
                array(
                    'id_setting' => 99,
                    'key' => 'default_splash_screen_outlet_apps',
                    'value' => NULL,
                    'value_text' => NULL,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            99 =>
                array(
                    'id_setting' => 100,
                    'key' => 'default_splash_screen_outlet_apps_duration',
                    'value' => NULL,
                    'value_text' => NULL,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            100 =>
                array(
                    'id_setting' => 101,
                    'key' => 'email_to_send_recap_transaction',
                    'value' => NULL,
                    'value_text' => NULL,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            101 =>
                array(
                    'id_setting' => 102,
                    'key' => 'disburse_date',
                    'value' => NULL,
                    'value_text' => '{"last_date_disburse":null,"date_cut_of":"20","min_date_send_disburse":"25"}',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            102 =>
                array(
                    'id_setting' => 103,
                    'key' => 'brand_bundling_name',
                    'value' => "Bundling",
                    'value_text' => NULL,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            103 =>
                array(
                    'id_setting' => 104,
                    'key' => 'disburse_fee_product_plastic',
                    'value' => 0,
                    'value_text' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            104 =>
                array(
                    'id_setting' => 105,
                    'key' => 'available_delivery',
                    'value' => null,
                    'value_text' => '[{"code":"gosend","delivery_name":"GoSend","delivery_method":"GoSend","show_status":1,"available_status":"1","logo":"","position":0,"description":""},{"code":"wehelpyou_grabexpress","delivery_name":"Grab Express","delivery_method":"wehelpyou","show_status":1,"available_status":"1","logo":"","position":1,"description":""},{"code":"wehelpyou_mrspeedy","delivery_name":"Mrspeedy","delivery_method":"wehelpyou","show_status":1,"available_status":"1","logo":"","position":2,"description":""},{"code":"wehelpyou_lalamove","delivery_name":"Lalamove","delivery_method":"wehelpyou","show_status":1,"available_status":"1","logo":"","position":3,"description":""}]',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            105 =>
                array(
                    'id_setting' => 106,
                    'key' => 'default_delivery',
                    'value' => 'selected',
                    'value_text' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            106 =>
                array(
                    'id_setting' => 107,
                    'key' => 'package_detail_delivery',
                    'value' => null,
                    'value_text' => '{"package_name":"","package_description":"","length":0,"width":0,"height":0,"weight":0}',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            107 =>
                array(
                    'id_setting' => 108,
                    'key' => 'default_image_delivery',
                    'value' => null,
                    'value_text' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
           	108 =>
                array(
                    'id_setting' => 109,
                    'key' => 'cashback_earned_text',
                    'value' => 'Point yang akan didapatkan',
                    'value_text' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            109 =>
                array(
                    'id_setting' => 110,
                    'key' => 'default_splash_screen_mitra_apps',
                    'value' => NULL,
                    'value_text' => NULL,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            110 =>
                array(
                    'id_setting' => 111,
                    'key' => 'default_splash_screen_mitra_apps_duration',
                    'value' => NULL,
                    'value_text' => NULL,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            111 =>
                array (
                    'id_setting' => 112,
                    'key' => 'text_menu_home',
                    'value' => NULL,
                    'value_text' => '{"menu1":{"text_menu":"Outlet","text_color":"","container_type":"","container_color":"","icon":"","visible":true},"menu2":{"text_menu":"Home Sevice","text_color":"","container_type":"","container_color":"","icon":"","visible":true},"menu3":{"text_menu":"Shop","text_color":"","container_type":"","container_color":"","icon":"","visible":true},"menu4":{"text_menu":"Academy","text_color":"","container_type":"","container_color":"","icon":"","visible":true}}',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            112 =>
                array (
                    'id_setting' => 113,
                    'key' => 'total_list_nearby_outlet',
                    'value' => 5,
                    'value_text' => NULL,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            113 =>
                array (
                    'id_setting' => 114,
                    'key' => 'total_show_date_booking_service',
                    'value' => 7,
                    'value_text' => NULL,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            114 =>
                array (
                    'id_setting' => 115,
                    'key' => 'facebook_url',
                    'value' => NULL,
                    'value_text' => NULL,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            115 =>
                array (
                    'id_setting' => 116,
                    'key' => 'instagram_url',
                    'value' => NULL,
                    'value_text' => NULL,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            116 =>
                array (
                    'id_setting' => 117,
                    'key' => 'tolerant_processing_time_service',
                    'value' => 30,
                    'value_text' => NULL,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            117 =>
                array(
                    'id_setting' => 118,
                    'key' => 'home_news_title',
                    'value' => 'Berita',
                    'value_text' => NULL,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            118 =>
                array(
                    'id_setting' => 119,
                    'key' => 'home_news_sub_title',
                    'value' => 'Berita menarik untuk Anda',
                    'value_text' => NULL,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            119 =>
                array(
                    'id_setting' => 120,
                    'key' => 'privacypolicy',
                    'value' => NULL,
                    'value_text' => '<h1>Privacy Policy</h1>',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            120 =>
                array(
                    'id_setting' => 121,
                    'key' => 'confirmation_letter_tempalate',
                    'value' => NULL,
                    'value_text' => '<h5 class="font-weight-bold mb-0" style="font-size: 11pt">SURAT KONFIRMASI</h5>
                    <h6 class="font-weight-normal mb-0">%lokasi_surat%, %tanggal_surat% </h6>
                    <h6 class="font-weight-normal mb-0">No: %no_surat%</h6>
                    <br>
                    <h6 class="font-weight-normal mb-0">PIHAK I	 :	PT IXOBOX MULTITREN ASIA </h6>
                    <h6 class="font-weight-normal mb-0">PIHAK II :	%pihak_dua% </h6>
                    <h6 class="font-weight-normal mb-0">LOCATION : 	%location_mall% - %location_city% </h6>

                    <table class="table table-bordered mt-4 mb-0" width="700px" nobr>
                        <thead>
                            <tr class="text-center">
                                <th width="10px">NO</th>
                                <th colspan="2">SERVICE DESCRIPTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td width="130px">OBJECTIVE</td>
                                <td>
                                    <ol class="pl-2 text-justify terluar">
                                        <li>Pihak I sebagai pemegang merek IXOBOX di teritori wilayah Indonesia, bermaksud mengadakan Perjanjian Kerja Sama Operasional (KSO) dengan Pihak II dalam mengembangkan unit outlet Ixobox di Kota Kasablanka, Jakarta;</li>
                                        <li>Pihak I menunjuk PT. Ixobox Mitra Sejahtera sebagai Sole Operator di teritori wilayah Indonesia, untuk membantu Pihak II menjalankan operasional unit outlet Ixobox di Kota Kasablanka, Jakarta;</li>
                                    </ol>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td width="130px">UNIT OUTLET</td>
                                <td>
                                    <ol class="pl-2 text-justify terluar">
                                        <li>%address%;</li>
                                        <li>Luas Lokasi = %large% M2;</li>
                                        <li>Perjanjian Kerja Sama Operasional ini hanya berlaku untuk lokasi yang tercantum dalam poin 2a;</li>
                                    </ol>
                                </td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td width="130px">MASA KERJASAMA</td>
                                <td>%total_waktu%</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td width="130px">ROLES & RESPONSIBILITIEST</td>
                                <td>
                                    <ol class="pl-2 text-justify terluar">
                                        <li>Tugas dan tanggung jawab Pihak I:
                                            <ul class="pl-0 mb-1 text-justify dalam">
                                                <li>Pihak I bertanggung jawab untuk mengelola sumber daya manusia (tenaga kerja) agar operasional outlet dapat berjalan dengan baik;</li>
                                                <li>Pengembangan Merek;</li>
                                                <li>Melakukan pemeliharaan dan pengembangan Ixobox system;</li>
                                                <li>Pengembangan SOP dan sistem pendukung lainnya;</li>
                                                <li>Melakukan pelatihan dan pengembangan kualitas tenaga kerja;</li>
                                                <li>Pembagian hasil setiap bulan kepada Pihak II (poin 5);</li>
                                                <li>Menanggung seluruh biaya operasional termasuk gaji dan insentif tenaga kerja;</li>
                                                <li>Biaya akomodasi dan transportasi yang berhubungan dengan peningkatan kualitas dan pengembangan tenaga kerja ataupun penambahan atau penggantian tenaga kerja (jika diperlukan);</li>
                                                <li>Biaya-biaya supplies dan biaya-biaya yang berhubungan dengan operasional;</li>
                                                <li>Biaya media marketing promotion secara lokal dan nasional;</li>
                                            </ul>
                                        </li>
                                    </ol>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <table class="table table-bordered mt-4 mb-0" width="700px" nobr>
                        <thead>
                            <tr>
                                <td width="10px"></td>
                                <td width="130px"></td>
                                <td>
                                    <ol class="pl-2 text-justify terluar" start="2">
                                        <li>Tugas dan tanggung jawab Pihak II:
                                            <ul class="pl-0 text-justify dalam">
                                                <li>Membayar Partnership Fee kepada Pihak I (point 6);</li>
                                                <li>Menanggung biaya bulanan yang berhubungan dengan lokasi (mall) setelah outlet beroperasi: biaya sewa, service charge, promotion levy, biaya listrik, biaya air (jika ada), dan lain-lain;</li>
                                                <li>Biaya-biaya yang berhubungan dengan pemeliharaan dan kerusakan fisik outlet, dan asuransi outlet (jika ada);</li>
                                            </ul>
                                        </li>
                                    </ol>
                                </td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>5</td>
                                <td width="130px"PEMBAGIAN HASIL KERJASAMA</td>
                                <td>Selama masa perjanjian Kerjasama, pembagian hasil yang dibagikan oleh Pihak I kepada Pihak II adalah sebagai berikut:
                                    <ol class="pl-2 text-justify terluar">
                                        <li>Skema pembagian hasil antara Pihak I dan Pihak II: 50%:50% dihitung dari penghasilan bersih outlet (net revenue), setelah dipotong 10% untuk management contribution dan diskon penjualan serta biaya administrasi bank atau merchant discount rate dari digital payment (jika ada);</li>
                                        <li>Pembayaran akan ditransfer oleh Pihak I ke rekening Pihak II, dalam 15 hari kerja bulan berikutnya setiap bulan sesuai poin 5a;</li>
                                    </ol>
                                </td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td width="130px"PARTNERSHIP FEE</td>
                                <td>
                                    <ol class="pl-2 text-justify terluar">
                                        <li>Partnership fee = Rp %partnership_fee% (%partnership_fee_string%). Harga belum termasuk PPN 10%. Harga tersebut mencakup:
                                            <ul class="pl-0 text-justify dalam">
                                                <li>Paket 5 Boxes termasuk peralatan dan perlengkapan pendukung operasional;</li>
                                                <li>Furniture pelengkap seperti bench, credenza, meja, bangku, lemari dan lainnya;</li>
                                                <li>Sistem pendukung, hardware dan software termasuk kiosk mesin, handphone dan fraud system panel, cctv dan finger print;</li>
                                                <li>Starter kit package untuk supplies dan perlengkapan;</li>
                                                <li>Decoration dan marketing tools seperti signage, TV, poster, sticker dan lainnya;</li>
                                                <li>Penggunaan merek Ixobox selama masa berlaku Perjanjian Kerja Sama;</li>
                                                <li>Penggunaan SOP dan sistem pendukung Ixobox lainnya;</li>
                                                <li>Recruitment, Training dan Penempatan Tenaga kerja dan tenaga pendukung lainnya, selama masa berlaku Perjanjian Kerja Sama;</li>
                                                <li>3D & 2D Ixobox Kota Kasablanka design concept;</li>
                                            </ul>
                                        </li>
                                    </ol>
                                </td>
                            </tr>
                            <tr>
                                <td>7</td>
                                <td width="130px">CARA PEMBAYARAN</td>
                                <td>
                                    <ol class="pl-2 text-justify terluar">
                                        <li>Booking fee kesepakatan kerjasama Ixobox 20% = Rp %dp% (%dp_string%), belum termasuk PPN. Dibayar maksimal 5 hari kerja setelah ditandatangani surat konfirmasi ini;</li>
                                        <li>Down Payment 1 = Rp %dp2% (%dp2_string%), belum termasuk PPN dan dibayar maksimal 2 minggu sebelum proses renovasi dilakukan;</li>
                                        <li>Final Payment = Rp %final% (%final_string%), belum termasuk PPN;</li>
                                        %angsuran%    
                                        <li>Pembayaran dilakukan dengan cara transfer ke PT.Ixobox Multitren Asia, Bank BCA, nomor rekening: 6840308608 dan seluruh pembayaran yang telah disepakati dan dilakukan oleh Pihak II kepada Pihak I, tidak dapat dikembalikan;</li>
                                    </ol>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <table class="table table-bordered mt-4 mb-0 pb-0" width="700px" nobr>
                        <tbody>
                            <tr>
                                <td width="10px">8</td>
                                <td width="130px" colspan="2">OTHER (INITIAL) INVESTMENT</td>
                                <td colspan="8">
                                    <ol class="pl-2 text-justify terluar">
                                        <li>Biaya-biaya awal yang berhubungan dengan lokasi seperti instalasi listrik, internet, hoarding, APAR dan lainnya serta persiapan fit out akan dibayar oleh Pihak II kepada pemilik unit lokasi (manajemen mal);</li>
                                        <li>Biaya-biaya yang berhubungan dengan fit out lokasi, dibayar oleh Pihak II kepada kontraktor, yang ditunjuk oleh Pihak I (termasuk biaya transportasi dan akomodasi tim kontraktor);</li>
                                    </ol>
                                </td>
                            </tr>
                            <tr>
                                <td>9</td>
                                <td width="130px" colspan="2">LAIN-LAIN</td>
                                <td colspan="8">Detail dari Surat Konfimasi ini akan dituangkan dalam Perjanjian Kerjasama Operasional (KSO), yang akan ditanda-tangani oleh Pihak I dan Pihak II.</td>
                            </tr>
                            <tr class="text-center">
                                <td colspan="6">Pihak Pertama <br>
                                    <b>PT Ixobox Multitren Asia</b> <br><br><br><br><br>
                                    <b><u>Alese Sandria</u></b> <br>
                                    <b>General Manager</b>
                                </td>
                                <td colspan="5">Pihak Kedua <br><br><br><br><br><br>
                                    <b><u>%ttd_pihak_dua%</u></b> <br>
                                    <b>Business Partner</b>
                                </td>
                            </tr>
                        </tbody>
                    </table>',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            121 =>
                array(
                    'id_setting' => 122,
                    'key' => 'form_survey',
                    'value' => NULL,
                    'value_text' => '{"1":{"cat1":{"category":"KONDISI UMUM LOKASI","question":["Lokasi di area residensial (komplek perumahan) dengan populasi penduduk padat","Lokasi dikelilingi offices, apartements, schools, campus","Lokasi sekitartnya banyak retail F&B dan retail lainnya","Lokasi sangat strategis: jalan raya utama 2 arah, terlihat sekilas dengan mudah, area parkir cukup luas","Lokasi di tengah pusat kota yang padat dan meeting point","Lokasi tidak rawan hal-hal yang mengakibatkan outlet tutup, seperti banjir, kebakaran, demonstrasi, dll"]},"cat2":{"category":"KONDISI UMUM LOKASI","question":["Ruangan Lokasi siap pakai tanpa partisi","Ruangan Lokasi tidak banyak kerusakan secara keseluruhan sehingga minimalkan biaya renovasi","Tampak depan lokasi dapat dipasang signage dan fasad ixobox","Lokasi dapat pasang reklame untuk promosi","Listrik lokasi minimal 6000 watt","Lokasi ada instalasi air bersih dan kotor","Lokasi dapat dipasang partisi untuk mess hairstylist"]},"cat3":{"category":"KONDISI UMUM LOKASI","question":["Unit lokasi berada di area banyak salon/barbershop/gunting rambut anak-anak","Lokasi unit outlet sebelumnya ditempati oleh salon/barbershop (diutamakan yang ramai pelanggan)","Unit lokasi dilewati banyak pejalan kaki dan posisinya terlihat jelas","Ukuruan luas unit outlet adalah sesuai dengan kebutuhan/kriteria Ixobox minimal 30 m","Biaya sewa dan service charge sesuai dengan budget sub-brand Ixobox yang ditentukan","Biaya renovasi lokasi masuk dalam perhitungan biaya Ixobox","Kapasitas unit outlet, dapat menambah jumlah kapasitas box di masa yang akan datang","Rata-rata harga jual competitor lebih tinggi dari harga jual Ixobox","Tidak sulit recruit, penempatan dan lakukan rotasi hairstylist untuk unit outlet tersebut","Keberadaan unit outlet tidak membutuhkan biaya promosi yang tinggi"]}},"2":{"cat1":{"category":"KONDISI UMUM LOKASI","question":["Lokasi di area residensial (komplek perumahan) dengan populasi penduduk padat","Lokasi dikelilingi offices, apartements, schools, campus","Lokasi sekitartnya banyak retail F&B dan retail lainnya","Lokasi sangat strategis: jalan raya utama 2 arah, terlihat sekilas dengan mudah, area parkir cukup luas","Lokasi di tengah pusat kota yang padat dan meeting point","Lokasi tidak rawan hal-hal yang mengakibatkan outlet tutup, seperti banjir, kebakaran, demonstrasi, dll"]},"cat2":{"category":"KONDISI UMUM LOKASI","question":["Ruangan Lokasi siap pakai tanpa partisi","Ruangan Lokasi tidak banyak kerusakan secara keseluruhan sehingga minimalkan biaya renovasi","Tampak depan lokasi dapat dipasang signage dan fasad ixobox","Lokasi dapat pasang reklame untuk promosi","Listrik lokasi minimal 6000 watt","Lokasi ada instalasi air bersih dan kotor","Lokasi dapat dipasang partisi untuk mess hairstylist"]},"cat3":{"category":"KONDISI UMUM LOKASI","question":["Unit lokasi berada di area banyak salon/barbershop/gunting rambut anak-anak","Lokasi unit outlet sebelumnya ditempati oleh salon/barbershop (diutamakan yang ramai pelanggan)","Unit lokasi dilewati banyak pejalan kaki dan posisinya terlihat jelas","Ukuruan luas unit outlet adalah sesuai dengan kebutuhan/kriteria Ixobox minimal 30 m","Biaya sewa dan service charge sesuai dengan budget sub-brand Ixobox yang ditentukan","Biaya renovasi lokasi masuk dalam perhitungan biaya Ixobox","Kapasitas unit outlet, dapat menambah jumlah kapasitas box di masa yang akan datang","Rata-rata harga jual competitor lebih tinggi dari harga jual Ixobox","Tidak sulit recruit, penempatan dan lakukan rotasi hairstylist untuk unit outlet tersebut","Keberadaan unit outlet tidak membutuhkan biaya promosi yang tinggi"]}},"3":{"cat1":{"category":"KONDISI UMUM LOKASI","question":["Lokasi di area residensial (komplek perumahan) dengan populasi penduduk padat","Lokasi dikelilingi offices, apartements, schools, campus","Lokasi sekitartnya banyak retail F&B dan retail lainnya","Lokasi sangat strategis: jalan raya utama 2 arah, terlihat sekilas dengan mudah, area parkir cukup luas","Lokasi di tengah pusat kota yang padat dan meeting point","Lokasi tidak rawan hal-hal yang mengakibatkan outlet tutup, seperti banjir, kebakaran, demonstrasi, dll"]},"cat2":{"category":"KONDISI UMUM LOKASI","question":["Ruangan Lokasi siap pakai tanpa partisi","Ruangan Lokasi tidak banyak kerusakan secara keseluruhan sehingga minimalkan biaya renovasi","Tampak depan lokasi dapat dipasang signage dan fasad ixobox","Lokasi dapat pasang reklame untuk promosi","Listrik lokasi minimal 6000 watt","Lokasi ada instalasi air bersih dan kotor","Lokasi dapat dipasang partisi untuk mess hairstylist"]},"cat3":{"category":"KONDISI UMUM LOKASI","question":["Unit lokasi berada di area banyak salon/barbershop/gunting rambut anak-anak","Lokasi unit outlet sebelumnya ditempati oleh salon/barbershop (diutamakan yang ramai pelanggan)","Unit lokasi dilewati banyak pejalan kaki dan posisinya terlihat jelas","Ukuruan luas unit outlet adalah sesuai dengan kebutuhan/kriteria Ixobox minimal 30 m","Biaya sewa dan service charge sesuai dengan budget sub-brand Ixobox yang ditentukan","Biaya renovasi lokasi masuk dalam perhitungan biaya Ixobox","Kapasitas unit outlet, dapat menambah jumlah kapasitas box di masa yang akan datang","Rata-rata harga jual competitor lebih tinggi dari harga jual Ixobox","Tidak sulit recruit, penempatan dan lakukan rotasi hairstylist untuk unit outlet tersebut","Keberadaan unit outlet tidak membutuhkan biaya promosi yang tinggi"]}},"4":{"cat1":{"category":"KONDISI UMUM LOKASI","question":["Lokasi di area residensial (komplek perumahan) dengan populasi penduduk padat","Lokasi dikelilingi offices, apartements, schools, campus","Lokasi sekitartnya banyak retail F&B dan retail lainnya","Lokasi sangat strategis: jalan raya utama 2 arah, terlihat sekilas dengan mudah, area parkir cukup luas","Lokasi di tengah pusat kota yang padat dan meeting point","Lokasi tidak rawan hal-hal yang mengakibatkan outlet tutup, seperti banjir, kebakaran, demonstrasi, dll"]},"cat2":{"category":"KONDISI UMUM LOKASI","question":["Ruangan Lokasi siap pakai tanpa partisi","Ruangan Lokasi tidak banyak kerusakan secara keseluruhan sehingga minimalkan biaya renovasi","Tampak depan lokasi dapat dipasang signage dan fasad ixobox","Lokasi dapat pasang reklame untuk promosi","Listrik lokasi minimal 6000 watt","Lokasi ada instalasi air bersih dan kotor","Lokasi dapat dipasang partisi untuk mess hairstylist"]},"cat3":{"category":"KONDISI UMUM LOKASI","question":["Unit lokasi berada di area banyak salon/barbershop/gunting rambut anak-anak","Lokasi unit outlet sebelumnya ditempati oleh salon/barbershop (diutamakan yang ramai pelanggan)","Unit lokasi dilewati banyak pejalan kaki dan posisinya terlihat jelas","Ukuruan luas unit outlet adalah sesuai dengan kebutuhan/kriteria Ixobox minimal 30 m","Biaya sewa dan service charge sesuai dengan budget sub-brand Ixobox yang ditentukan","Biaya renovasi lokasi masuk dalam perhitungan biaya Ixobox","Kapasitas unit outlet, dapat menambah jumlah kapasitas box di masa yang akan datang","Rata-rata harga jual competitor lebih tinggi dari harga jual Ixobox","Tidak sulit recruit, penempatan dan lakukan rotasi hairstylist untuk unit outlet tersebut","Keberadaan unit outlet tidak membutuhkan biaya promosi yang tinggi"]}}}',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
			122 =>
                array (
                    'id_setting' => 123,
                    'key' => 'outlet_service_extend_popup_time',
                    'value' => 5,
                    'value_text' => NULL,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            
        ));
    }
}
