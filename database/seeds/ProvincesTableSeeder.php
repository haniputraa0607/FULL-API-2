<?php

use Illuminate\Database\Seeder;

class ProvincesTableSeeder extends Seeder
{
    public function run()
    {
        

        \DB::table('provinces')->delete();
        
        \DB::table('provinces')->insert(array (
            0 => 
            array (
               'id_province' => 11,
               'province_name' => 'Nanggroe Aceh Darussalam (NAD)',
               'time_zone_utc' => 7
            ),
            1 => 
            array (
                'id_province' => 12,
                'province_name' => 'Sumatera Utara',
                'time_zone_utc' => 7
            ),
            2 => 
            array (
                'id_province' => 13,
                'province_name' => 'Sumatera Barat',
                'time_zone_utc' => 7
            ),
            3 => 
            array (
                'id_province' => 14,
                'province_name' => 'Riau',
                'time_zone_utc' => 7
            ),
            4 => 
            array (
                'id_province' => 15,
                'province_name' => 'Jambi',
                'time_zone_utc' => 7
            ),
            5 => 
            array (
                'id_province' => 16,
                'province_name' => 'Sumatera Selatan',
                'time_zone_utc' => 7
            ),
            6 => 
            array (
                'id_province' => 17,
                'province_name' => 'Bengkulu',
                'time_zone_utc' => 7
            ),
            7 => 
            array (
                'id_province' => 18,
                'province_name' => 'Lampung',
                'time_zone_utc' => 7
            ),
            8 => 
            array (
                'id_province' => 19,
                'province_name' => 'Kepulauan Bangka Belitung',
                'time_zone_utc' => 7
            ),
            9 => 
            array (
                'id_province' => 21,
                'province_name' => 'Kepulauan Riau',
                'time_zone_utc' => 7
            ),
            10 => 
            array (
                'id_province' => 31,
                'province_name' => 'DKI Jakarta',
                'time_zone_utc' => 7
            ),
            11 => 
            array (
                'id_province' => 32,
                'province_name' => 'Jawa Barat',
                'time_zone_utc' => 7
            ),
            12 => 
            array (
                'id_province' => 33,
                'province_name' => 'Jawa Tengah',
                'time_zone_utc' => 7
            ),
            13 => 
            array (
                'id_province' => 34,
                'province_name' => 'DI Yogyakarta',
                'time_zone_utc' => 7
            ),
            14 => 
            array (
                'id_province' => 35,
                'province_name' => 'Jawa Timur',
                'time_zone_utc' => 7
            ),
            15 => 
            array (
                'id_province' => 36,
                'province_name' => 'Banten',
                'time_zone_utc' => 7
            ),
            16 => 
            array (
                'id_province' => 51,
                'province_name' => 'Bali',
                'time_zone_utc' => 8
            ),
            17 => 
            array (
                'id_province' => 52,
                'province_name' => 'Nusa Tenggara Barat (NTB)',
            	'time_zone_utc' => 8
            ),
            18 => 
            array (
                'id_province' => 53,
                'province_name' => 'Nusa Tenggara Timur (NTT)',
            	'time_zone_utc' => 8
            ),
            19 => 
            array (
                'id_province' => 61,
                'province_name' => 'Kalimantan Barat',
                'time_zone_utc' => 7
            ),
            20 => 
            array (
                'id_province' => 62,
                'province_name' => 'Kalimantan Tengah',
                'time_zone_utc' => 7
            ),
            21 => 
            array (
                'id_province' => 63,
	        'province_name' => 'Kalimantan Selatan',
                'time_zone_utc' => 8
            ),
            22 => 
            array (
                'id_province' => 64,
            	'province_name' => 'Kalimantan Timur',
                'time_zone_utc' => 8
            ),
            23 => 
            array (
                'id_province' => 65,
            	'province_name' => 'Kalimantan Utara',
                'time_zone_utc' => 8
            ),
            24 => 
            array (
                'id_province' => 71,
                'province_name' => 'Sulawesi Utara',
                'time_zone_utc' => 8
            ),
            25 => 
            array (
                'id_province' => 72,
                'province_name' => 'Sulawesi Tengah',
                'time_zone_utc' => 8
            ),
            26 => 
            array (
                'id_province' => 73,
                'province_name' => 'Sulawesi Selatan',
                'time_zone_utc' => 8
            ),
            27 => 
            array (
                'id_province' => 74,
                'province_name' => 'Sulawesi Tenggara',
                'time_zone_utc' => 8
            ),
            28 => 
            array (
                'id_province' => 75,
                'province_name' => 'Gorontalo',
                'time_zone_utc' => 8
            ),
            29 => 
            array (
                'id_province' => 76,
                 'province_name' => 'Sulawesi Barat',
                'time_zone_utc' => 8
            ),
            30 => 
            array (
                'id_province' => 81,
                'province_name' => 'Maluku',
                'time_zone_utc' => 9
            ),
            31 => 
            array (
                'id_province' => 82,
                'province_name' => 'Maluku Utara',
                'time_zone_utc' => 9
            ),
            32 => 
            array (
                'id_province' => 91,
                'province_name' => 'Papua',
                'time_zone_utc' => 9
            ),
            33 => 
            array (
                'id_province' => 92,
                'province_name' => 'Papua Barat',
                'time_zone_utc' => 9
            ),
        ));
        
        
    }
}