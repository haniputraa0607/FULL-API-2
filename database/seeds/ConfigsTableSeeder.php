<?php

use Illuminate\Database\Seeder;

class ConfigsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('configs')->delete();
        
        \DB::table('configs')->insert(array (
            0 => 
            array (
                'config_name' => 'sync raptor',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 1,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            1 => 
            array (
                'config_name' => 'outlet import excel',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 2,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            2 => 
            array (
                'config_name' => 'outlet export excel',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 3,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            3 => 
            array (
                'config_name' => 'outlet holiday',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 4,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            4 => 
            array (
                'config_name' => 'admin outlet',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 5,
                'is_active' => '0',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            5 => 
            array (
                'config_name' => 'admin outlet pickup order',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 6,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            6 => 
            array (
                'config_name' => 'admin outlet delivery order',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 7,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            7 => 
            array (
                'config_name' => 'admin outlet finance',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 8,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            8 => 
            array (
                'config_name' => 'admin outlet enquiry',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 9,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            9 => 
            array (
                'config_name' => 'product import excel',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 10,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            10 => 
            array (
                'config_name' => 'product export excel',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 11,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            11 => 
            array (
                'config_name' => 'pickup order',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 12,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            12 => 
            array (
                'config_name' => 'delivery order',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 13,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            13 => 
            array (
                'config_name' => 'internal courier',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 14,
                'is_active' => '0',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            14 => 
            array (
                'config_name' => 'online order',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 15,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            15 => 
            array (
                'config_name' => 'automatic payment',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 16,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            16 => 
            array (
                'config_name' => 'manual payment',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 17,
                'is_active' => '0',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            17 => 
            array (
                'config_name' => 'point',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 18,
                'is_active' => '0',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            18 => 
            array (
                'config_name' => 'balance',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 19,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            19 => 
            array (
                'config_name' => 'membership',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 20,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            20 => 
            array (
                'config_name' => 'membership benefit point',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 21,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            21 => 
            array (
                'config_name' => 'membership benefit cashback',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 22,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            22 => 
            array (
                'config_name' => 'membership benefit discount',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 23,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            23 => 
            array (
                'config_name' => 'membership benefit promo id',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 24,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            24 => 
            array (
                'config_name' => 'deals',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 25,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            25 => 
            array (
                'config_name' => 'hidden deals',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 26,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            26 => 
            array (
                'config_name' => 'deals by money',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 27,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            27 => 
            array (
                'config_name' => 'deals by point',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 28,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            28 => 
            array (
                'config_name' => 'deals free',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 29,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            29 => 
            array (
                'config_name' => 'greetings',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 30,
                'is_active' => '0',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            30 => 
            array (
                'config_name' => 'greetings text',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 31,
                'is_active' => '0',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            31 => 
            array (
                'config_name' => 'greetings background',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 32,
                'is_active' => '0',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            32 => 
            array (
                'config_name' => 'advert',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 33,
                'is_active' => '0',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            33 => 
            array (
                'config_name' => 'news',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 34,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            34 => 
            array (
                'config_name' => 'crm',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 35,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            35 => 
            array (
                'config_name' => 'crm push notification',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 36,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            36 => 
            array (
                'config_name' => 'crm inbox',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 37,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            37 => 
            array (
                'config_name' => 'crm email',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 38,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            38 => 
            array (
                'config_name' => 'crm sms',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 39,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            39 => 
            array (
                'config_name' => 'auto response',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 40,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            40 => 
            array (
                'config_name' => 'auto response pin sent',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 41,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            41 => 
            array (
                'config_name' => 'auto response pin verified',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 42,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            42 => 
            array (
                'config_name' => 'auto response pin changed',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 43,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            43 => 
            array (
                'config_name' => 'auto response login success',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 44,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            44 => 
            array (
                'config_name' => 'auto response login failed',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 45,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            45 => 
            array (
                'config_name' => 'auto response enquiry question',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 46,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            46 => 
            array (
                'config_name' => 'auto response enquiry partnership',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 47,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            47 => 
            array (
                'config_name' => 'auto response enquiry complaint',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 48,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            48 => 
            array (
                'config_name' => 'auto response deals',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 49,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            49 => 
            array (
                'config_name' => 'campaign',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 50,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            50 => 
            array (
                'config_name' => 'campaign email',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 51,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            51 => 
            array (
                'config_name' => 'campaign sms',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 52,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            52 => 
            array (
                'config_name' => 'campaign push notif',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 53,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            53 => 
            array (
                'config_name' => 'campaign inbox',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 54,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            54 => 
            array (
                'config_name' => 'auto crm',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 55,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            55 => 
            array (
                'config_name' => 'enquiry',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 56,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            56 => 
            array (
                'config_name' => 'reply enquiry',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 57,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            57 => 
            array (
                'config_name' => 'enquiry question',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 58,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            58 => 
            array (
                'config_name' => 'enquiry partnership',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 59,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            59 => 
            array (
                'config_name' => 'enquiry complaint',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 60,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            60 => 
            array (
                'config_name' => 'report transaction daily',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 61,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            61 => 
            array (
                'config_name' => 'report transaction weekly',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 62,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            62 => 
            array (
                'config_name' => 'report transaction monthly',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 63,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            63 => 
            array (
                'config_name' => 'report transaction yearly',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 64,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            64 => 
            array (
                'config_name' => 'product by recurring',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 65,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            65 => 
            array (
                'config_name' => 'product by quantity',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 66,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            66 => 
            array (
                'config_name' => 'outlet by nominal transaction',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 67,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            67 => 
            array (
                'config_name' => 'outlet by total transaction',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 68,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            68 => 
            array (
                'config_name' => 'customer by total transaction',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 69,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            69 => 
            array (
                'config_name' => 'customer by nominal transaction',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 70,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            70 => 
            array (
                'config_name' => 'customer by point',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 71,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            71 => 
            array (
                'config_name' => 'promotion',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 72,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            72 => 
            array (
                'config_name' => 'reward',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 73,
                'is_active' => '0',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            73 => 
            array (
                'config_name' => 'crm whatsapp',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 74,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            74 => 
            array (
                'config_name' => 'campaign whatsapp',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 75,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            75 => 
            array (
                'config_name' => 'spin the wheel',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 76,
                'is_active' => '0',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            76 => 
            array (
                'config_name' => 'point reset',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 77,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            77 => 
            array (
                'config_name' => 'balance reset',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 78,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            78 => 
            array (
                'config_name' => 'free delivery',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 79,
                'is_active' => '0',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            79 => 
            array (
                'config_name' => 'GO-SEND',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 80,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            80 => 
            array (
                'config_name' => 'retain membership',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 81,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            81 => 
            array (
                'config_name' => 'POS sync Outlet',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 82,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            82 => 
            array (
                'config_name' => 'auto response pin forgot',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 83,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            83 => 
            array (
                'config_name' => 'subscription voucher',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 84,
                'is_active' => '0',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            84 => 
            array (
                'config_name' => 'subscription voucher by money',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 85,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            85 => 
            array (
                'config_name' => 'subscription voucher by point',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 86,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            86 => 
            array (
                'config_name' => 'subscription voucher free',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 87,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            87 => 
            array (
                'config_name' => 'icon main menu',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 88,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            88 => 
            array (
                'config_name' => 'icon other menu',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 89,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            89 => 
            array (
                'config_name' => 'user feedback',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 90,
                'is_active' => '0',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            90 => 
            array (
                'config_name' => 'product modifier',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 91,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            91 => 
            array (
                'config_name' => 'advance order',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 92,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            92 => 
            array (
                'config_name' => 'promo campaign',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 93,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            93 => 
            array (
                'config_name' => 'phone format setting',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 94,
                'is_active' => '0',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            94 => 
            array (
                'config_name' => 'use brand',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 95,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            95 => 
            array (
                'config_name' => 'delivery services',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 96,
                'is_active' => '0',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            96 => 
            array (
                'config_name' => 'deals offline',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 97,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            97 => 
            array (
                'config_name' => 'deals online',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 98,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            98 => 
            array (
                'config_name' => 'achievement',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 99,
                'is_active' => '0',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            99 => 
            array (
                'config_name' => 'quest',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 100,
                'is_active' => '0',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            100 => 
            array (
                'config_name' => 'admin outlet apps',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 101,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            101 => 
            array (
                'config_name' => 'voucher online get point',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 102,
                'is_active' => '0',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            102 => 
            array (
                'config_name' => 'voucher offline get point',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 103,
                'is_active' => '0',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            103 => 
            array (
                'config_name' => 'promo code get point',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 104,
                'is_active' => '0',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            104 => 
            array (
                'config_name' => 'deals second title',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 105,
                'is_active' => '0',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            105 => 
            array (
                'config_name' => 'auto response email verified',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 106,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            106 => 
            array (
                'config_name' => 'custom form news',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 107,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            107 => 
            array (
                'config_name' => 'intro',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 108,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            108 => 
            array (
                'config_name' => 'credit card multi payment',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 109,
                'is_active' => '0',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            109 => 
            array (
                'config_name' => 'refund midtrans',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 110,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            110 => 
            array (
                'config_name' => 'refund ovo',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 111,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            111 => 
            array (
                'config_name' => 'subscription get point',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 112,
                'is_active' => '0',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            112 => 
            array (
                'config_name' => 'auto response subscription',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 113,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            113 => 
            array (
                'config_name' => 'fraud use queue',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 114,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            114 => 
            array (
                'config_name' => 'referral',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 115,
                'is_active' => '0',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            115 => 
            array (
                'config_name' => 'offline payment method',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 116,
                'is_active' => '0',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            116 => 
            array (
                'config_name' => 'banner daily time limit',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 117,
                'is_active' => '0',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            117 => 
            array (
                'config_name' => 'show or hide info calculation disburse',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 118,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            118 => 
            array (
                'config_name' => 'redirect complex',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 119,
                'is_active' => '0',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            119 => 
            array (
                'config_name' => 'shopeepay',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 120,
                'is_active' => '0',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            120 => 
            array (
                'config_name' => 'business development',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 121,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
            121 => 
            array (
                'config_name' => 'user rating',
                'created_at' => '2021-09-29 10:57:33',
                'description' => '',
                'id_config' => 122,
                'is_active' => '1',
                'updated_at' => '2021-09-29 10:57:33',
            ),
        ));
        
        
    }
}