<?php

use Illuminate\Database\Seeder;

class CitiesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('cities')->delete();
        
        \DB::table('cities')->insert(array (
            0 => 
            array (
                'id_city' => 1101,
                'id_province' => 11,
                'city_name' => 'KAB. ACEH SELATAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            1 => 
            array (
                'id_city' => 1102,
                'id_province' => 11,
                'city_name' => 'KAB. ACEH TENGGARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            2 => 
            array (
                'id_city' => 1103,
                'id_province' => 11,
                'city_name' => 'KAB. ACEH TIMUR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            3 => 
            array (
                'id_city' => 1104,
                'id_province' => 11,
                'city_name' => 'KAB. ACEH TENGAH',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            4 => 
            array (
                'id_city' => 1105,
                'id_province' => 11,
                'city_name' => 'KAB. ACEH BARAT',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            5 => 
            array (
                'id_city' => 1106,
                'id_province' => 11,
                'city_name' => 'KAB. ACEH BESAR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            6 => 
            array (
                'id_city' => 1107,
                'id_province' => 11,
                'city_name' => 'KAB. PIDIE',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            7 => 
            array (
                'id_city' => 1108,
                'id_province' => 11,
                'city_name' => 'KAB. ACEH UTARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            8 => 
            array (
                'id_city' => 1109,
                'id_province' => 11,
                'city_name' => 'KAB. SIMEULUE',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            9 => 
            array (
                'id_city' => 1110,
                'id_province' => 11,
                'city_name' => 'KAB. ACEH SINGKIL',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            10 => 
            array (
                'id_city' => 1111,
                'id_province' => 11,
                'city_name' => 'KAB. BIREUEN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            11 => 
            array (
                'id_city' => 1112,
                'id_province' => 11,
                'city_name' => 'KAB. ACEH BARAT DAYA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            12 => 
            array (
                'id_city' => 1113,
                'id_province' => 11,
                'city_name' => 'KAB. GAYO LUES',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            13 => 
            array (
                'id_city' => 1114,
                'id_province' => 11,
                'city_name' => 'KAB. ACEH JAYA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            14 => 
            array (
                'id_city' => 1115,
                'id_province' => 11,
                'city_name' => 'KAB. NAGAN RAYA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            15 => 
            array (
                'id_city' => 1116,
                'id_province' => 11,
                'city_name' => 'KAB. ACEH TAMIANG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            16 => 
            array (
                'id_city' => 1117,
                'id_province' => 11,
                'city_name' => 'KAB. BENER MERIAH',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            17 => 
            array (
                'id_city' => 1118,
                'id_province' => 11,
                'city_name' => 'KAB. PIDIE JAYA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            18 => 
            array (
                'id_city' => 1171,
                'id_province' => 11,
                'city_name' => 'KOTA BANDA ACEH',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            19 => 
            array (
                'id_city' => 1172,
                'id_province' => 11,
                'city_name' => 'KOTA SABANG',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            20 => 
            array (
                'id_city' => 1173,
                'id_province' => 11,
                'city_name' => 'KOTA LHOKSEUMAWE',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            21 => 
            array (
                'id_city' => 1174,
                'id_province' => 11,
                'city_name' => 'KOTA LANGSA',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            22 => 
            array (
                'id_city' => 1175,
                'id_province' => 11,
                'city_name' => 'KOTA SUBULUSSALAM',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            23 => 
            array (
                'id_city' => 1201,
                'id_province' => 12,
                'city_name' => 'KAB. TAPANULI TENGAH',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            24 => 
            array (
                'id_city' => 1202,
                'id_province' => 12,
                'city_name' => 'KAB. TAPANULI UTARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            25 => 
            array (
                'id_city' => 1203,
                'id_province' => 12,
                'city_name' => 'KAB. TAPANULI SELATAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            26 => 
            array (
                'id_city' => 1204,
                'id_province' => 12,
                'city_name' => 'KAB. NIAS',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            27 => 
            array (
                'id_city' => 1205,
                'id_province' => 12,
                'city_name' => 'KAB. LANGKAT',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            28 => 
            array (
                'id_city' => 1206,
                'id_province' => 12,
                'city_name' => 'KAB. KARO',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            29 => 
            array (
                'id_city' => 1207,
                'id_province' => 12,
                'city_name' => 'KAB. DELI SERDANG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            30 => 
            array (
                'id_city' => 1208,
                'id_province' => 12,
                'city_name' => 'KAB. SIMALUNGUN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            31 => 
            array (
                'id_city' => 1209,
                'id_province' => 12,
                'city_name' => 'KAB. ASAHAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            32 => 
            array (
                'id_city' => 1210,
                'id_province' => 12,
                'city_name' => 'KAB. LABUHANBATU',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            33 => 
            array (
                'id_city' => 1211,
                'id_province' => 12,
                'city_name' => 'KAB. DAIRI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            34 => 
            array (
                'id_city' => 1212,
                'id_province' => 12,
                'city_name' => 'KAB. TOBA SAMOSIR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            35 => 
            array (
                'id_city' => 1213,
                'id_province' => 12,
                'city_name' => 'KAB. MANDAILING NATAL',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            36 => 
            array (
                'id_city' => 1214,
                'id_province' => 12,
                'city_name' => 'KAB. NIAS SELATAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            37 => 
            array (
                'id_city' => 1215,
                'id_province' => 12,
                'city_name' => 'KAB. PAKPAK BHARAT',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            38 => 
            array (
                'id_city' => 1216,
                'id_province' => 12,
                'city_name' => 'KAB. HUMBANG HASUNDUTAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            39 => 
            array (
                'id_city' => 1217,
                'id_province' => 12,
                'city_name' => 'KAB. SAMOSIR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            40 => 
            array (
                'id_city' => 1218,
                'id_province' => 12,
                'city_name' => 'KAB. SERDANG BEDAGAI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            41 => 
            array (
                'id_city' => 1219,
                'id_province' => 12,
                'city_name' => 'KAB. BATU BARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            42 => 
            array (
                'id_city' => 1220,
                'id_province' => 12,
                'city_name' => 'KAB. PADANG LAWAS UTARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            43 => 
            array (
                'id_city' => 1221,
                'id_province' => 12,
                'city_name' => 'KAB. PADANG LAWAS',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            44 => 
            array (
                'id_city' => 1222,
                'id_province' => 12,
                'city_name' => 'KAB. LABUHANBATU SELATAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            45 => 
            array (
                'id_city' => 1223,
                'id_province' => 12,
                'city_name' => 'KAB. LABUHANBATU UTARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            46 => 
            array (
                'id_city' => 1224,
                'id_province' => 12,
                'city_name' => 'KAB. NIAS UTARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            47 => 
            array (
                'id_city' => 1225,
                'id_province' => 12,
                'city_name' => 'KAB. NIAS BARAT',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            48 => 
            array (
                'id_city' => 1271,
                'id_province' => 12,
                'city_name' => 'KOTA MEDAN',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            49 => 
            array (
                'id_city' => 1272,
                'id_province' => 12,
                'city_name' => 'KOTA PEMATANGSIANTAR',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            50 => 
            array (
                'id_city' => 1273,
                'id_province' => 12,
                'city_name' => 'KOTA SIBOLGA',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            51 => 
            array (
                'id_city' => 1274,
                'id_province' => 12,
                'city_name' => 'KOTA TANJUNG BALAI',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            52 => 
            array (
                'id_city' => 1275,
                'id_province' => 12,
                'city_name' => 'KOTA BINJAI',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            53 => 
            array (
                'id_city' => 1276,
                'id_province' => 12,
                'city_name' => 'KOTA TEBING TINGGI',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            54 => 
            array (
                'id_city' => 1277,
                'id_province' => 12,
                'city_name' => 'KOTA PADANGSIDIMPUAN',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            55 => 
            array (
                'id_city' => 1278,
                'id_province' => 12,
                'city_name' => 'KOTA GUNUNGSITOLI',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            56 => 
            array (
                'id_city' => 1301,
                'id_province' => 13,
                'city_name' => 'KAB. PESISIR SELATAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            57 => 
            array (
                'id_city' => 1302,
                'id_province' => 13,
                'city_name' => 'KAB. SOLOK',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            58 => 
            array (
                'id_city' => 1303,
                'id_province' => 13,
                'city_name' => 'KAB. SIJUNJUNG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            59 => 
            array (
                'id_city' => 1304,
                'id_province' => 13,
                'city_name' => 'KAB. TANAH DATAR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            60 => 
            array (
                'id_city' => 1305,
                'id_province' => 13,
                'city_name' => 'KAB. PADANG PARIAMAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            61 => 
            array (
                'id_city' => 1306,
                'id_province' => 13,
                'city_name' => 'KAB. AGAM',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            62 => 
            array (
                'id_city' => 1307,
                'id_province' => 13,
                'city_name' => 'KAB. LIMA PULUH KOTA',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            63 => 
            array (
                'id_city' => 1308,
                'id_province' => 13,
                'city_name' => 'KAB. PASAMAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            64 => 
            array (
                'id_city' => 1309,
                'id_province' => 13,
                'city_name' => 'KAB. KEPULAUAN MENTAWAI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            65 => 
            array (
                'id_city' => 1310,
                'id_province' => 13,
                'city_name' => 'KAB. DHARMASRAYA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            66 => 
            array (
                'id_city' => 1311,
                'id_province' => 13,
                'city_name' => 'KAB. SOLOK SELATAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            67 => 
            array (
                'id_city' => 1312,
                'id_province' => 13,
                'city_name' => 'KAB. PASAMAN BARAT',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            68 => 
            array (
                'id_city' => 1371,
                'id_province' => 13,
                'city_name' => 'KOTA PADANG',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            69 => 
            array (
                'id_city' => 1372,
                'id_province' => 13,
                'city_name' => 'KOTA SOLOK',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            70 => 
            array (
                'id_city' => 1373,
                'id_province' => 13,
                'city_name' => 'KOTA SAWAHLUNTO',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            71 => 
            array (
                'id_city' => 1374,
                'id_province' => 13,
                'city_name' => 'KOTA PADANG PANJANG',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            72 => 
            array (
                'id_city' => 1375,
                'id_province' => 13,
                'city_name' => 'KOTA BUKITTINGGI',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            73 => 
            array (
                'id_city' => 1376,
                'id_province' => 13,
                'city_name' => 'KOTA PAYAKUMBUH',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            74 => 
            array (
                'id_city' => 1377,
                'id_province' => 13,
                'city_name' => 'KOTA PARIAMAN',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            75 => 
            array (
                'id_city' => 1401,
                'id_province' => 14,
                'city_name' => 'KAB. KAMPAR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            76 => 
            array (
                'id_city' => 1402,
                'id_province' => 14,
                'city_name' => 'KAB. INDRAGIRI HULU',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            77 => 
            array (
                'id_city' => 1403,
                'id_province' => 14,
                'city_name' => 'KAB. BENGKALIS',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            78 => 
            array (
                'id_city' => 1404,
                'id_province' => 14,
                'city_name' => 'KAB. INDRAGIRI HILIR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            79 => 
            array (
                'id_city' => 1405,
                'id_province' => 14,
                'city_name' => 'KAB. PELALAWAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            80 => 
            array (
                'id_city' => 1406,
                'id_province' => 14,
                'city_name' => 'KAB. ROKAN HULU',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            81 => 
            array (
                'id_city' => 1407,
                'id_province' => 14,
                'city_name' => 'KAB. ROKAN HILIR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            82 => 
            array (
                'id_city' => 1408,
                'id_province' => 14,
                'city_name' => 'KAB. SIAK',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            83 => 
            array (
                'id_city' => 1409,
                'id_province' => 14,
                'city_name' => 'KAB. KUANTAN SINGINGI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            84 => 
            array (
                'id_city' => 1410,
                'id_province' => 14,
                'city_name' => 'KAB. KEPULAUAN MERANTI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            85 => 
            array (
                'id_city' => 1471,
                'id_province' => 14,
                'city_name' => 'KOTA PEKANBARU',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            86 => 
            array (
                'id_city' => 1472,
                'id_province' => 14,
                'city_name' => 'KOTA DUMAI',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            87 => 
            array (
                'id_city' => 1501,
                'id_province' => 15,
                'city_name' => 'KAB. KERINCI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            88 => 
            array (
                'id_city' => 1502,
                'id_province' => 15,
                'city_name' => 'KAB. MERANGIN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            89 => 
            array (
                'id_city' => 1503,
                'id_province' => 15,
                'city_name' => 'KAB. SAROLANGUN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            90 => 
            array (
                'id_city' => 1504,
                'id_province' => 15,
                'city_name' => 'KAB. BATANGHARI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            91 => 
            array (
                'id_city' => 1505,
                'id_province' => 15,
                'city_name' => 'KAB. MUARO JAMBI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            92 => 
            array (
                'id_city' => 1506,
                'id_province' => 15,
                'city_name' => 'KAB. TANJUNG JABUNG BARAT',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            93 => 
            array (
                'id_city' => 1507,
                'id_province' => 15,
                'city_name' => 'KAB. TANJUNG JABUNG TIMUR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            94 => 
            array (
                'id_city' => 1508,
                'id_province' => 15,
                'city_name' => 'KAB. BUNGO',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            95 => 
            array (
                'id_city' => 1509,
                'id_province' => 15,
                'city_name' => 'KAB. TEBO',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            96 => 
            array (
                'id_city' => 1571,
                'id_province' => 15,
                'city_name' => 'KOTA JAMBI',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            97 => 
            array (
                'id_city' => 1572,
                'id_province' => 15,
                'city_name' => 'KOTA SUNGAI PENUH',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            98 => 
            array (
                'id_city' => 1601,
                'id_province' => 16,
                'city_name' => 'KAB. OGAN KOMERING ULU',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            99 => 
            array (
                'id_city' => 1602,
                'id_province' => 16,
                'city_name' => 'KAB. OGAN KOMERING ILIR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            100 => 
            array (
                'id_city' => 1603,
                'id_province' => 16,
                'city_name' => 'KAB. MUARA ENIM',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            101 => 
            array (
                'id_city' => 1604,
                'id_province' => 16,
                'city_name' => 'KAB. LAHAT',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            102 => 
            array (
                'id_city' => 1605,
                'id_province' => 16,
                'city_name' => 'KAB. MUSI RAWAS',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            103 => 
            array (
                'id_city' => 1606,
                'id_province' => 16,
                'city_name' => 'KAB. MUSI BANYUASIN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            104 => 
            array (
                'id_city' => 1607,
                'id_province' => 16,
                'city_name' => 'KAB. BANYUASIN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            105 => 
            array (
                'id_city' => 1608,
                'id_province' => 16,
                'city_name' => 'KAB. OGAN KOMERING ULU TIMUR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            106 => 
            array (
                'id_city' => 1609,
                'id_province' => 16,
                'city_name' => 'KAB. OGAN KOMERING ULU SELATAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            107 => 
            array (
                'id_city' => 1610,
                'id_province' => 16,
                'city_name' => 'KAB. OGAN ILIR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            108 => 
            array (
                'id_city' => 1611,
                'id_province' => 16,
                'city_name' => 'KAB. EMPAT LAWANG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            109 => 
            array (
                'id_city' => 1612,
                'id_province' => 16,
                'city_name' => 'KAB. PENUKAL ABAB LEMATANG ILIR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            110 => 
            array (
                'id_city' => 1613,
                'id_province' => 16,
                'city_name' => 'KAB. MUSI RAWAS UTARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            111 => 
            array (
                'id_city' => 1671,
                'id_province' => 16,
                'city_name' => 'KOTA PALEMBANG',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            112 => 
            array (
                'id_city' => 1672,
                'id_province' => 16,
                'city_name' => 'KOTA PAGAR ALAM',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            113 => 
            array (
                'id_city' => 1673,
                'id_province' => 16,
                'city_name' => 'KOTA LUBUK LINGGAU',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            114 => 
            array (
                'id_city' => 1674,
                'id_province' => 16,
                'city_name' => 'KOTA PRABUMULIH',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            115 => 
            array (
                'id_city' => 1701,
                'id_province' => 17,
                'city_name' => 'KAB. BENGKULU SELATAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            116 => 
            array (
                'id_city' => 1702,
                'id_province' => 17,
                'city_name' => 'KAB. REJANG LEBONG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            117 => 
            array (
                'id_city' => 1703,
                'id_province' => 17,
                'city_name' => 'KAB. BENGKULU UTARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            118 => 
            array (
                'id_city' => 1704,
                'id_province' => 17,
                'city_name' => 'KAB. KAUR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            119 => 
            array (
                'id_city' => 1705,
                'id_province' => 17,
                'city_name' => 'KAB. SELUMA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            120 => 
            array (
                'id_city' => 1706,
                'id_province' => 17,
                'city_name' => 'KAB. MUKO MUKO',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            121 => 
            array (
                'id_city' => 1707,
                'id_province' => 17,
                'city_name' => 'KAB. LEBONG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            122 => 
            array (
                'id_city' => 1708,
                'id_province' => 17,
                'city_name' => 'KAB. KEPAHIANG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            123 => 
            array (
                'id_city' => 1709,
                'id_province' => 17,
                'city_name' => 'KAB. BENGKULU TENGAH',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            124 => 
            array (
                'id_city' => 1771,
                'id_province' => 17,
                'city_name' => 'KOTA BENGKULU',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            125 => 
            array (
                'id_city' => 1801,
                'id_province' => 18,
                'city_name' => 'KAB. LAMPUNG SELATAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            126 => 
            array (
                'id_city' => 1802,
                'id_province' => 18,
                'city_name' => 'KAB. LAMPUNG TENGAH',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            127 => 
            array (
                'id_city' => 1803,
                'id_province' => 18,
                'city_name' => 'KAB. LAMPUNG UTARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            128 => 
            array (
                'id_city' => 1804,
                'id_province' => 18,
                'city_name' => 'KAB. LAMPUNG BARAT',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            129 => 
            array (
                'id_city' => 1805,
                'id_province' => 18,
                'city_name' => 'KAB. TULANG BAWANG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            130 => 
            array (
                'id_city' => 1806,
                'id_province' => 18,
                'city_name' => 'KAB. TANGGAMUS',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            131 => 
            array (
                'id_city' => 1807,
                'id_province' => 18,
                'city_name' => 'KAB. LAMPUNG TIMUR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            132 => 
            array (
                'id_city' => 1808,
                'id_province' => 18,
                'city_name' => 'KAB. WAY KANAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            133 => 
            array (
                'id_city' => 1809,
                'id_province' => 18,
                'city_name' => 'KAB. PESAWARAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            134 => 
            array (
                'id_city' => 1810,
                'id_province' => 18,
                'city_name' => 'KAB. PRINGSEWU',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            135 => 
            array (
                'id_city' => 1811,
                'id_province' => 18,
                'city_name' => 'KAB. MESUJI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            136 => 
            array (
                'id_city' => 1812,
                'id_province' => 18,
                'city_name' => 'KAB. TULANG BAWANG BARAT',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            137 => 
            array (
                'id_city' => 1813,
                'id_province' => 18,
                'city_name' => 'KAB. PESISIR BARAT',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            138 => 
            array (
                'id_city' => 1871,
                'id_province' => 18,
                'city_name' => 'KOTA BANDAR LAMPUNG',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            139 => 
            array (
                'id_city' => 1872,
                'id_province' => 18,
                'city_name' => 'KOTA METRO',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            140 => 
            array (
                'id_city' => 1901,
                'id_province' => 19,
                'city_name' => 'KAB. BANGKA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            141 => 
            array (
                'id_city' => 1902,
                'id_province' => 19,
                'city_name' => 'KAB. BELITUNG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            142 => 
            array (
                'id_city' => 1903,
                'id_province' => 19,
                'city_name' => 'KAB. BANGKA SELATAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            143 => 
            array (
                'id_city' => 1904,
                'id_province' => 19,
                'city_name' => 'KAB. BANGKA TENGAH',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            144 => 
            array (
                'id_city' => 1905,
                'id_province' => 19,
                'city_name' => 'KAB. BANGKA BARAT',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            145 => 
            array (
                'id_city' => 1906,
                'id_province' => 19,
                'city_name' => 'KAB. BELITUNG TIMUR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            146 => 
            array (
                'id_city' => 1971,
                'id_province' => 19,
                'city_name' => 'KOTA PANGKAL PINANG',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            147 => 
            array (
                'id_city' => 2101,
                'id_province' => 21,
                'city_name' => 'KAB. BINTAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            148 => 
            array (
                'id_city' => 2102,
                'id_province' => 21,
                'city_name' => 'KAB. KARIMUN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            149 => 
            array (
                'id_city' => 2103,
                'id_province' => 21,
                'city_name' => 'KAB. NATUNA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            150 => 
            array (
                'id_city' => 2104,
                'id_province' => 21,
                'city_name' => 'KAB. LINGGA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            151 => 
            array (
                'id_city' => 2105,
                'id_province' => 21,
                'city_name' => 'KAB. KEPULAUAN ANAMBAS',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            152 => 
            array (
                'id_city' => 2171,
                'id_province' => 21,
                'city_name' => 'KOTA BATAM',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            153 => 
            array (
                'id_city' => 2172,
                'id_province' => 21,
                'city_name' => 'KOTA TANJUNG PINANG',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            154 => 
            array (
                'id_city' => 3101,
                'id_province' => 31,
                'city_name' => 'KAB. ADM. KEP. SERIBU',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            155 => 
            array (
                'id_city' => 3171,
                'id_province' => 31,
                'city_name' => 'KOTA ADM. JAKARTA PUSAT',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            156 => 
            array (
                'id_city' => 3172,
                'id_province' => 31,
                'city_name' => 'KOTA ADM. JAKARTA UTARA',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            157 => 
            array (
                'id_city' => 3173,
                'id_province' => 31,
                'city_name' => 'KOTA ADM. JAKARTA BARAT',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            158 => 
            array (
                'id_city' => 3174,
                'id_province' => 31,
                'city_name' => 'KOTA ADM. JAKARTA SELATAN',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            159 => 
            array (
                'id_city' => 3175,
                'id_province' => 31,
                'city_name' => 'KOTA ADM. JAKARTA TIMUR',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            160 => 
            array (
                'id_city' => 3201,
                'id_province' => 32,
                'city_name' => 'KAB. BOGOR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            161 => 
            array (
                'id_city' => 3202,
                'id_province' => 32,
                'city_name' => 'KAB. SUKABUMI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            162 => 
            array (
                'id_city' => 3203,
                'id_province' => 32,
                'city_name' => 'KAB. CIANJUR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            163 => 
            array (
                'id_city' => 3204,
                'id_province' => 32,
                'city_name' => 'KAB. BANDUNG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            164 => 
            array (
                'id_city' => 3205,
                'id_province' => 32,
                'city_name' => 'KAB. GARUT',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            165 => 
            array (
                'id_city' => 3206,
                'id_province' => 32,
                'city_name' => 'KAB. TASIKMALAYA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            166 => 
            array (
                'id_city' => 3207,
                'id_province' => 32,
                'city_name' => 'KAB. CIAMIS',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            167 => 
            array (
                'id_city' => 3208,
                'id_province' => 32,
                'city_name' => 'KAB. KUNINGAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            168 => 
            array (
                'id_city' => 3209,
                'id_province' => 32,
                'city_name' => 'KAB. CIREBON',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            169 => 
            array (
                'id_city' => 3210,
                'id_province' => 32,
                'city_name' => 'KAB. MAJALENGKA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            170 => 
            array (
                'id_city' => 3211,
                'id_province' => 32,
                'city_name' => 'KAB. SUMEDANG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            171 => 
            array (
                'id_city' => 3212,
                'id_province' => 32,
                'city_name' => 'KAB. INDRAMAYU',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            172 => 
            array (
                'id_city' => 3213,
                'id_province' => 32,
                'city_name' => 'KAB. SUBANG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            173 => 
            array (
                'id_city' => 3214,
                'id_province' => 32,
                'city_name' => 'KAB. PURWAKARTA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            174 => 
            array (
                'id_city' => 3215,
                'id_province' => 32,
                'city_name' => 'KAB. KARAWANG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            175 => 
            array (
                'id_city' => 3216,
                'id_province' => 32,
                'city_name' => 'KAB. BEKASI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            176 => 
            array (
                'id_city' => 3217,
                'id_province' => 32,
                'city_name' => 'KAB. BANDUNG BARAT',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            177 => 
            array (
                'id_city' => 3218,
                'id_province' => 32,
                'city_name' => 'KAB. PANGANDARAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            178 => 
            array (
                'id_city' => 3271,
                'id_province' => 32,
                'city_name' => 'KOTA BOGOR',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            179 => 
            array (
                'id_city' => 3272,
                'id_province' => 32,
                'city_name' => 'KOTA SUKABUMI',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            180 => 
            array (
                'id_city' => 3273,
                'id_province' => 32,
                'city_name' => 'KOTA BANDUNG',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            181 => 
            array (
                'id_city' => 3274,
                'id_province' => 32,
                'city_name' => 'KOTA CIREBON',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            182 => 
            array (
                'id_city' => 3275,
                'id_province' => 32,
                'city_name' => 'KOTA BEKASI',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            183 => 
            array (
                'id_city' => 3276,
                'id_province' => 32,
                'city_name' => 'KOTA DEPOK',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            184 => 
            array (
                'id_city' => 3277,
                'id_province' => 32,
                'city_name' => 'KOTA CIMAHI',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            185 => 
            array (
                'id_city' => 3278,
                'id_province' => 32,
                'city_name' => 'KOTA TASIKMALAYA',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            186 => 
            array (
                'id_city' => 3279,
                'id_province' => 32,
                'city_name' => 'KOTA BANJAR',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            187 => 
            array (
                'id_city' => 3301,
                'id_province' => 33,
                'city_name' => 'KAB. CILACAP',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            188 => 
            array (
                'id_city' => 3302,
                'id_province' => 33,
                'city_name' => 'KAB. BANYUMAS',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            189 => 
            array (
                'id_city' => 3303,
                'id_province' => 33,
                'city_name' => 'KAB. PURBALINGGA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            190 => 
            array (
                'id_city' => 3304,
                'id_province' => 33,
                'city_name' => 'KAB. BANJARNEGARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            191 => 
            array (
                'id_city' => 3305,
                'id_province' => 33,
                'city_name' => 'KAB. KEBUMEN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            192 => 
            array (
                'id_city' => 3306,
                'id_province' => 33,
                'city_name' => 'KAB. PURWOREJO',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            193 => 
            array (
                'id_city' => 3307,
                'id_province' => 33,
                'city_name' => 'KAB. WONOSOBO',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            194 => 
            array (
                'id_city' => 3308,
                'id_province' => 33,
                'city_name' => 'KAB. MAGELANG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            195 => 
            array (
                'id_city' => 3309,
                'id_province' => 33,
                'city_name' => 'KAB. BOYOLALI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            196 => 
            array (
                'id_city' => 3310,
                'id_province' => 33,
                'city_name' => 'KAB. KLATEN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            197 => 
            array (
                'id_city' => 3311,
                'id_province' => 33,
                'city_name' => 'KAB. SUKOHARJO',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            198 => 
            array (
                'id_city' => 3312,
                'id_province' => 33,
                'city_name' => 'KAB. WONOGIRI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            199 => 
            array (
                'id_city' => 3313,
                'id_province' => 33,
                'city_name' => 'KAB. KARANGANYAR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            200 => 
            array (
                'id_city' => 3314,
                'id_province' => 33,
                'city_name' => 'KAB. SRAGEN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            201 => 
            array (
                'id_city' => 3315,
                'id_province' => 33,
                'city_name' => 'KAB. GROBOGAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            202 => 
            array (
                'id_city' => 3316,
                'id_province' => 33,
                'city_name' => 'KAB. BLORA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            203 => 
            array (
                'id_city' => 3317,
                'id_province' => 33,
                'city_name' => 'KAB. REMBANG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            204 => 
            array (
                'id_city' => 3318,
                'id_province' => 33,
                'city_name' => 'KAB. PATI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            205 => 
            array (
                'id_city' => 3319,
                'id_province' => 33,
                'city_name' => 'KAB. KUDUS',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            206 => 
            array (
                'id_city' => 3320,
                'id_province' => 33,
                'city_name' => 'KAB. JEPARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            207 => 
            array (
                'id_city' => 3321,
                'id_province' => 33,
                'city_name' => 'KAB. DEMAK',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            208 => 
            array (
                'id_city' => 3322,
                'id_province' => 33,
                'city_name' => 'KAB. SEMARANG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            209 => 
            array (
                'id_city' => 3323,
                'id_province' => 33,
                'city_name' => 'KAB. TEMANGGUNG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            210 => 
            array (
                'id_city' => 3324,
                'id_province' => 33,
                'city_name' => 'KAB. KENDAL',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            211 => 
            array (
                'id_city' => 3325,
                'id_province' => 33,
                'city_name' => 'KAB. BATANG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            212 => 
            array (
                'id_city' => 3326,
                'id_province' => 33,
                'city_name' => 'KAB. PEKALONGAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            213 => 
            array (
                'id_city' => 3327,
                'id_province' => 33,
                'city_name' => 'KAB. PEMALANG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            214 => 
            array (
                'id_city' => 3328,
                'id_province' => 33,
                'city_name' => 'KAB. TEGAL',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            215 => 
            array (
                'id_city' => 3329,
                'id_province' => 33,
                'city_name' => 'KAB. BREBES',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            216 => 
            array (
                'id_city' => 3371,
                'id_province' => 33,
                'city_name' => 'KOTA MAGELANG',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            217 => 
            array (
                'id_city' => 3372,
                'id_province' => 33,
                'city_name' => 'KOTA SURAKARTA',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            218 => 
            array (
                'id_city' => 3373,
                'id_province' => 33,
                'city_name' => 'KOTA SALATIGA',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            219 => 
            array (
                'id_city' => 3374,
                'id_province' => 33,
                'city_name' => 'KOTA SEMARANG',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            220 => 
            array (
                'id_city' => 3375,
                'id_province' => 33,
                'city_name' => 'KOTA PEKALONGAN',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            221 => 
            array (
                'id_city' => 3376,
                'id_province' => 33,
                'city_name' => 'KOTA TEGAL',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            222 => 
            array (
                'id_city' => 3401,
                'id_province' => 34,
                'city_name' => 'KAB. KULON PROGO',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            223 => 
            array (
                'id_city' => 3402,
                'id_province' => 34,
                'city_name' => 'KAB. BANTUL',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            224 => 
            array (
                'id_city' => 3403,
                'id_province' => 34,
                'city_name' => 'KAB. GUNUNGKIDUL',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            225 => 
            array (
                'id_city' => 3404,
                'id_province' => 34,
                'city_name' => 'KAB. SLEMAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            226 => 
            array (
                'id_city' => 3471,
                'id_province' => 34,
                'city_name' => 'KOTA YOGYAKARTA',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            227 => 
            array (
                'id_city' => 3501,
                'id_province' => 35,
                'city_name' => 'KAB. PACITAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            228 => 
            array (
                'id_city' => 3502,
                'id_province' => 35,
                'city_name' => 'KAB. PONOROGO',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            229 => 
            array (
                'id_city' => 3503,
                'id_province' => 35,
                'city_name' => 'KAB. TRENGGALEK',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            230 => 
            array (
                'id_city' => 3504,
                'id_province' => 35,
                'city_name' => 'KAB. TULUNGAGUNG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            231 => 
            array (
                'id_city' => 3505,
                'id_province' => 35,
                'city_name' => 'KAB. BLITAR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            232 => 
            array (
                'id_city' => 3506,
                'id_province' => 35,
                'city_name' => 'KAB. KEDIRI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            233 => 
            array (
                'id_city' => 3507,
                'id_province' => 35,
                'city_name' => 'KAB. MALANG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            234 => 
            array (
                'id_city' => 3508,
                'id_province' => 35,
                'city_name' => 'KAB. LUMAJANG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            235 => 
            array (
                'id_city' => 3509,
                'id_province' => 35,
                'city_name' => 'KAB. JEMBER',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            236 => 
            array (
                'id_city' => 3510,
                'id_province' => 35,
                'city_name' => 'KAB. BANYUWANGI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            237 => 
            array (
                'id_city' => 3511,
                'id_province' => 35,
                'city_name' => 'KAB. BONDOWOSO',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            238 => 
            array (
                'id_city' => 3512,
                'id_province' => 35,
                'city_name' => 'KAB. SITUBONDO',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            239 => 
            array (
                'id_city' => 3513,
                'id_province' => 35,
                'city_name' => 'KAB. PROBOLINGGO',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            240 => 
            array (
                'id_city' => 3514,
                'id_province' => 35,
                'city_name' => 'KAB. PASURUAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            241 => 
            array (
                'id_city' => 3515,
                'id_province' => 35,
                'city_name' => 'KAB. SIDOARJO',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            242 => 
            array (
                'id_city' => 3516,
                'id_province' => 35,
                'city_name' => 'KAB. MOJOKERTO',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            243 => 
            array (
                'id_city' => 3517,
                'id_province' => 35,
                'city_name' => 'KAB. JOMBANG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            244 => 
            array (
                'id_city' => 3518,
                'id_province' => 35,
                'city_name' => 'KAB. NGANJUK',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            245 => 
            array (
                'id_city' => 3519,
                'id_province' => 35,
                'city_name' => 'KAB. MADIUN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            246 => 
            array (
                'id_city' => 3520,
                'id_province' => 35,
                'city_name' => 'KAB. MAGETAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            247 => 
            array (
                'id_city' => 3521,
                'id_province' => 35,
                'city_name' => 'KAB. NGAWI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            248 => 
            array (
                'id_city' => 3522,
                'id_province' => 35,
                'city_name' => 'KAB. BOJONEGORO',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            249 => 
            array (
                'id_city' => 3523,
                'id_province' => 35,
                'city_name' => 'KAB. TUBAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            250 => 
            array (
                'id_city' => 3524,
                'id_province' => 35,
                'city_name' => 'KAB. LAMONGAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            251 => 
            array (
                'id_city' => 3525,
                'id_province' => 35,
                'city_name' => 'KAB. GRESIK',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            252 => 
            array (
                'id_city' => 3526,
                'id_province' => 35,
                'city_name' => 'KAB. BANGKALAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            253 => 
            array (
                'id_city' => 3527,
                'id_province' => 35,
                'city_name' => 'KAB. SAMPANG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            254 => 
            array (
                'id_city' => 3528,
                'id_province' => 35,
                'city_name' => 'KAB. PAMEKASAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            255 => 
            array (
                'id_city' => 3529,
                'id_province' => 35,
                'city_name' => 'KAB. SUMENEP',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            256 => 
            array (
                'id_city' => 3571,
                'id_province' => 35,
                'city_name' => 'KOTA KEDIRI',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            257 => 
            array (
                'id_city' => 3572,
                'id_province' => 35,
                'city_name' => 'KOTA BLITAR',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            258 => 
            array (
                'id_city' => 3573,
                'id_province' => 35,
                'city_name' => 'KOTA MALANG',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            259 => 
            array (
                'id_city' => 3574,
                'id_province' => 35,
                'city_name' => 'KOTA PROBOLINGGO',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            260 => 
            array (
                'id_city' => 3575,
                'id_province' => 35,
                'city_name' => 'KOTA PASURUAN',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            261 => 
            array (
                'id_city' => 3576,
                'id_province' => 35,
                'city_name' => 'KOTA MOJOKERTO',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            262 => 
            array (
                'id_city' => 3577,
                'id_province' => 35,
                'city_name' => 'KOTA MADIUN',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            263 => 
            array (
                'id_city' => 3578,
                'id_province' => 35,
                'city_name' => 'KOTA SURABAYA',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            264 => 
            array (
                'id_city' => 3579,
                'id_province' => 35,
                'city_name' => 'KOTA BATU',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            265 => 
            array (
                'id_city' => 3601,
                'id_province' => 36,
                'city_name' => 'KAB. PANDEGLANG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            266 => 
            array (
                'id_city' => 3602,
                'id_province' => 36,
                'city_name' => 'KAB. LEBAK',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            267 => 
            array (
                'id_city' => 3603,
                'id_province' => 36,
                'city_name' => 'KAB. TANGERANG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            268 => 
            array (
                'id_city' => 3604,
                'id_province' => 36,
                'city_name' => 'KAB. SERANG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            269 => 
            array (
                'id_city' => 3671,
                'id_province' => 36,
                'city_name' => 'KOTA TANGERANG',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            270 => 
            array (
                'id_city' => 3672,
                'id_province' => 36,
                'city_name' => 'KOTA CILEGON',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            271 => 
            array (
                'id_city' => 3673,
                'id_province' => 36,
                'city_name' => 'KOTA SERANG',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            272 => 
            array (
                'id_city' => 3674,
                'id_province' => 36,
                'city_name' => 'KOTA TANGERANG SELATAN',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            273 => 
            array (
                'id_city' => 5101,
                'id_province' => 51,
                'city_name' => 'KAB. JEMBRANA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            274 => 
            array (
                'id_city' => 5102,
                'id_province' => 51,
                'city_name' => 'KAB. TABANAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            275 => 
            array (
                'id_city' => 5103,
                'id_province' => 51,
                'city_name' => 'KAB. BADUNG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            276 => 
            array (
                'id_city' => 5104,
                'id_province' => 51,
                'city_name' => 'KAB. GIANYAR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            277 => 
            array (
                'id_city' => 5105,
                'id_province' => 51,
                'city_name' => 'KAB. KLUNGKUNG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            278 => 
            array (
                'id_city' => 5106,
                'id_province' => 51,
                'city_name' => 'KAB. BANGLI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            279 => 
            array (
                'id_city' => 5107,
                'id_province' => 51,
                'city_name' => 'KAB. KARANGASEM',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            280 => 
            array (
                'id_city' => 5108,
                'id_province' => 51,
                'city_name' => 'KAB. BULELENG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            281 => 
            array (
                'id_city' => 5171,
                'id_province' => 51,
                'city_name' => 'KOTA DENPASAR',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            282 => 
            array (
                'id_city' => 5201,
                'id_province' => 52,
                'city_name' => 'KAB. LOMBOK BARAT',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            283 => 
            array (
                'id_city' => 5202,
                'id_province' => 52,
                'city_name' => 'KAB. LOMBOK TENGAH',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            284 => 
            array (
                'id_city' => 5203,
                'id_province' => 52,
                'city_name' => 'KAB. LOMBOK TIMUR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            285 => 
            array (
                'id_city' => 5204,
                'id_province' => 52,
                'city_name' => 'KAB. SUMBAWA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            286 => 
            array (
                'id_city' => 5205,
                'id_province' => 52,
                'city_name' => 'KAB. DOMPU',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            287 => 
            array (
                'id_city' => 5206,
                'id_province' => 52,
                'city_name' => 'KAB. BIMA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            288 => 
            array (
                'id_city' => 5207,
                'id_province' => 52,
                'city_name' => 'KAB. SUMBAWA BARAT',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            289 => 
            array (
                'id_city' => 5208,
                'id_province' => 52,
                'city_name' => 'KAB. LOMBOK UTARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            290 => 
            array (
                'id_city' => 5271,
                'id_province' => 52,
                'city_name' => 'KOTA MATARAM',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            291 => 
            array (
                'id_city' => 5272,
                'id_province' => 52,
                'city_name' => 'KOTA BIMA',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            292 => 
            array (
                'id_city' => 5301,
                'id_province' => 53,
                'city_name' => 'KAB. KUPANG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            293 => 
            array (
                'id_city' => 5302,
                'id_province' => 53,
                'city_name' => 'KAB TIMOR TENGAH SELATAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            294 => 
            array (
                'id_city' => 5303,
                'id_province' => 53,
                'city_name' => 'KAB. TIMOR TENGAH UTARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            295 => 
            array (
                'id_city' => 5304,
                'id_province' => 53,
                'city_name' => 'KAB. BELU',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            296 => 
            array (
                'id_city' => 5305,
                'id_province' => 53,
                'city_name' => 'KAB. ALOR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            297 => 
            array (
                'id_city' => 5306,
                'id_province' => 53,
                'city_name' => 'KAB. FLORES TIMUR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            298 => 
            array (
                'id_city' => 5307,
                'id_province' => 53,
                'city_name' => 'KAB. SIKKA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            299 => 
            array (
                'id_city' => 5308,
                'id_province' => 53,
                'city_name' => 'KAB. ENDE',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            300 => 
            array (
                'id_city' => 5309,
                'id_province' => 53,
                'city_name' => 'KAB. NGADA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            301 => 
            array (
                'id_city' => 5310,
                'id_province' => 53,
                'city_name' => 'KAB. MANGGARAI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            302 => 
            array (
                'id_city' => 5311,
                'id_province' => 53,
                'city_name' => 'KAB. SUMBA TIMUR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            303 => 
            array (
                'id_city' => 5312,
                'id_province' => 53,
                'city_name' => 'KAB. SUMBA BARAT',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            304 => 
            array (
                'id_city' => 5313,
                'id_province' => 53,
                'city_name' => 'KAB. LEMBATA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            305 => 
            array (
                'id_city' => 5314,
                'id_province' => 53,
                'city_name' => 'KAB. ROTE NDAO',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            306 => 
            array (
                'id_city' => 5315,
                'id_province' => 53,
                'city_name' => 'KAB. MANGGARAI BARAT',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            307 => 
            array (
                'id_city' => 5316,
                'id_province' => 53,
                'city_name' => 'KAB. NAGEKEO',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            308 => 
            array (
                'id_city' => 5317,
                'id_province' => 53,
                'city_name' => 'KAB. SUMBA TENGAH',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            309 => 
            array (
                'id_city' => 5318,
                'id_province' => 53,
                'city_name' => 'KAB. SUMBA BARAT DAYA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            310 => 
            array (
                'id_city' => 5319,
                'id_province' => 53,
                'city_name' => 'KAB. MANGGARAI TIMUR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            311 => 
            array (
                'id_city' => 5320,
                'id_province' => 53,
                'city_name' => 'KAB. SABU RAIJUA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            312 => 
            array (
                'id_city' => 5321,
                'id_province' => 53,
                'city_name' => 'KAB. MALAKA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            313 => 
            array (
                'id_city' => 5371,
                'id_province' => 53,
                'city_name' => 'KOTA KUPANG',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            314 => 
            array (
                'id_city' => 6101,
                'id_province' => 61,
                'city_name' => 'KAB. SAMBAS',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            315 => 
            array (
                'id_city' => 6102,
                'id_province' => 61,
                'city_name' => 'KAB. MEMPAWAH',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            316 => 
            array (
                'id_city' => 6103,
                'id_province' => 61,
                'city_name' => 'KAB. SANGGAU',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            317 => 
            array (
                'id_city' => 6104,
                'id_province' => 61,
                'city_name' => 'KAB. KETAPANG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            318 => 
            array (
                'id_city' => 6105,
                'id_province' => 61,
                'city_name' => 'KAB. SINTANG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            319 => 
            array (
                'id_city' => 6106,
                'id_province' => 61,
                'city_name' => 'KAB. KAPUAS HULU',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            320 => 
            array (
                'id_city' => 6107,
                'id_province' => 61,
                'city_name' => 'KAB. BENGKAYANG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            321 => 
            array (
                'id_city' => 6108,
                'id_province' => 61,
                'city_name' => 'KAB. LANDAK',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            322 => 
            array (
                'id_city' => 6109,
                'id_province' => 61,
                'city_name' => 'KAB. SEKADAU',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            323 => 
            array (
                'id_city' => 6110,
                'id_province' => 61,
                'city_name' => 'KAB. MELAWI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            324 => 
            array (
                'id_city' => 6111,
                'id_province' => 61,
                'city_name' => 'KAB. KAYONG UTARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            325 => 
            array (
                'id_city' => 6112,
                'id_province' => 61,
                'city_name' => 'KAB. KUBU RAYA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            326 => 
            array (
                'id_city' => 6171,
                'id_province' => 61,
                'city_name' => 'KOTA PONTIANAK',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            327 => 
            array (
                'id_city' => 6172,
                'id_province' => 61,
                'city_name' => 'KOTA SINGKAWANG',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            328 => 
            array (
                'id_city' => 6201,
                'id_province' => 62,
                'city_name' => 'KAB. KOTAWARINGIN BARAT',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            329 => 
            array (
                'id_city' => 6202,
                'id_province' => 62,
                'city_name' => 'KAB. KOTAWARINGIN TIMUR',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            330 => 
            array (
                'id_city' => 6203,
                'id_province' => 62,
                'city_name' => 'KAB. KAPUAS',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            331 => 
            array (
                'id_city' => 6204,
                'id_province' => 62,
                'city_name' => 'KAB. BARITO SELATAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            332 => 
            array (
                'id_city' => 6205,
                'id_province' => 62,
                'city_name' => 'KAB. BARITO UTARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            333 => 
            array (
                'id_city' => 6206,
                'id_province' => 62,
                'city_name' => 'KAB. KATINGAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            334 => 
            array (
                'id_city' => 6207,
                'id_province' => 62,
                'city_name' => 'KAB. SERUYAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            335 => 
            array (
                'id_city' => 6208,
                'id_province' => 62,
                'city_name' => 'KAB. SUKAMARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            336 => 
            array (
                'id_city' => 6209,
                'id_province' => 62,
                'city_name' => 'KAB. LAMANDAU',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            337 => 
            array (
                'id_city' => 6210,
                'id_province' => 62,
                'city_name' => 'KAB. GUNUNG MAS',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            338 => 
            array (
                'id_city' => 6211,
                'id_province' => 62,
                'city_name' => 'KAB. PULANG PISAU',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            339 => 
            array (
                'id_city' => 6212,
                'id_province' => 62,
                'city_name' => 'KAB. MURUNG RAYA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            340 => 
            array (
                'id_city' => 6213,
                'id_province' => 62,
                'city_name' => 'KAB. BARITO TIMUR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            341 => 
            array (
                'id_city' => 6271,
                'id_province' => 62,
                'city_name' => 'KOTA PALANGKARAYA',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            342 => 
            array (
                'id_city' => 6301,
                'id_province' => 63,
                'city_name' => 'KAB. TANAH LAUT',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            343 => 
            array (
                'id_city' => 6302,
                'id_province' => 63,
                'city_name' => 'KAB. KOTABARU',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            344 => 
            array (
                'id_city' => 6303,
                'id_province' => 63,
                'city_name' => 'KAB. BANJAR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            345 => 
            array (
                'id_city' => 6304,
                'id_province' => 63,
                'city_name' => 'KAB. BARITO KUALA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            346 => 
            array (
                'id_city' => 6305,
                'id_province' => 63,
                'city_name' => 'KAB. TAPIN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            347 => 
            array (
                'id_city' => 6306,
                'id_province' => 63,
                'city_name' => 'KAB. HULU SUNGAI SELATAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            348 => 
            array (
                'id_city' => 6307,
                'id_province' => 63,
                'city_name' => 'KAB. HULU SUNGAI TENGAH',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            349 => 
            array (
                'id_city' => 6308,
                'id_province' => 63,
                'city_name' => 'KAB. HULU SUNGAI UTARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            350 => 
            array (
                'id_city' => 6309,
                'id_province' => 63,
                'city_name' => 'KAB. TABALONG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            351 => 
            array (
                'id_city' => 6310,
                'id_province' => 63,
                'city_name' => 'KAB. TANAH BUMBU',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            352 => 
            array (
                'id_city' => 6311,
                'id_province' => 63,
                'city_name' => 'KAB. BALANGAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            353 => 
            array (
                'id_city' => 6371,
                'id_province' => 63,
                'city_name' => 'KOTA BANJARMASIN',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            354 => 
            array (
                'id_city' => 6372,
                'id_province' => 63,
                'city_name' => 'KOTA BANJARBARU',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            355 => 
            array (
                'id_city' => 6401,
                'id_province' => 64,
                'city_name' => 'KAB. PASER',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            356 => 
            array (
                'id_city' => 6402,
                'id_province' => 64,
                'city_name' => 'KAB. KUTAI KARTANEGARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            357 => 
            array (
                'id_city' => 6403,
                'id_province' => 64,
                'city_name' => 'KAB. BERAU',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            358 => 
            array (
                'id_city' => 6407,
                'id_province' => 64,
                'city_name' => 'KAB. KUTAI BARAT',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            359 => 
            array (
                'id_city' => 6408,
                'id_province' => 64,
                'city_name' => 'KAB. KUTAI TIMUR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            360 => 
            array (
                'id_city' => 6409,
                'id_province' => 64,
                'city_name' => 'KAB. PENAJAM PASER UTARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            361 => 
            array (
                'id_city' => 6411,
                'id_province' => 64,
                'city_name' => 'KAB. MAHAKAM ULU',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            362 => 
            array (
                'id_city' => 6471,
                'id_province' => 64,
                'city_name' => 'KOTA BALIKPAPAN',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            363 => 
            array (
                'id_city' => 6472,
                'id_province' => 64,
                'city_name' => 'KOTA SAMARINDA',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            364 => 
            array (
                'id_city' => 6474,
                'id_province' => 64,
                'city_name' => 'KOTA BONTANG',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            365 => 
            array (
                'id_city' => 6501,
                'id_province' => 65,
                'city_name' => 'KAB. BULUNGAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            366 => 
            array (
                'id_city' => 6502,
                'id_province' => 65,
                'city_name' => 'KAB. MALINAU',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            367 => 
            array (
                'id_city' => 6503,
                'id_province' => 65,
                'city_name' => 'KAB. NUNUKAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            368 => 
            array (
                'id_city' => 6504,
                'id_province' => 65,
                'city_name' => 'KAB. TANA TIDUNG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            369 => 
            array (
                'id_city' => 6571,
                'id_province' => 65,
                'city_name' => 'KOTA TARAKAN',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            370 => 
            array (
                'id_city' => 7101,
                'id_province' => 71,
                'city_name' => 'KAB. BOLAANG MONGONDOW',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            371 => 
            array (
                'id_city' => 7102,
                'id_province' => 71,
                'city_name' => 'KAB. MINAHASA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            372 => 
            array (
                'id_city' => 7103,
                'id_province' => 71,
                'city_name' => 'KAB. KEPULAUAN SANGIHE',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            373 => 
            array (
                'id_city' => 7104,
                'id_province' => 71,
                'city_name' => 'KAB. KEPULAUAN TALAUD',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            374 => 
            array (
                'id_city' => 7105,
                'id_province' => 71,
                'city_name' => 'KAB. MINAHASA SELATAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            375 => 
            array (
                'id_city' => 7106,
                'id_province' => 71,
                'city_name' => 'KAB. MINAHASA UTARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            376 => 
            array (
                'id_city' => 7107,
                'id_province' => 71,
                'city_name' => 'KAB. MINAHASA TENGGARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            377 => 
            array (
                'id_city' => 7108,
                'id_province' => 71,
                'city_name' => 'KAB. BOLAANG MONGONDOW UTARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            378 => 
            array (
                'id_city' => 7109,
                'id_province' => 71,
                'city_name' => 'KAB. KEP. SIAU TAGULANDANG BIARO',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            379 => 
            array (
                'id_city' => 7110,
                'id_province' => 71,
                'city_name' => 'KAB. BOLAANG MONGONDOW TIMUR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            380 => 
            array (
                'id_city' => 7111,
                'id_province' => 71,
                'city_name' => 'KAB. BOLAANG MONGONDOW SELATAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            381 => 
            array (
                'id_city' => 7171,
                'id_province' => 71,
                'city_name' => 'KOTA MANADO',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            382 => 
            array (
                'id_city' => 7172,
                'id_province' => 71,
                'city_name' => 'KOTA BITUNG',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            383 => 
            array (
                'id_city' => 7173,
                'id_province' => 71,
                'city_name' => 'KOTA TOMOHON',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            384 => 
            array (
                'id_city' => 7174,
                'id_province' => 71,
                'city_name' => 'KOTA KOTAMOBAGU',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            385 => 
            array (
                'id_city' => 7201,
                'id_province' => 72,
                'city_name' => 'KAB. BANGGAI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            386 => 
            array (
                'id_city' => 7202,
                'id_province' => 72,
                'city_name' => 'KAB. POSO',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            387 => 
            array (
                'id_city' => 7203,
                'id_province' => 72,
                'city_name' => 'KAB. DONGGALA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            388 => 
            array (
                'id_city' => 7204,
                'id_province' => 72,
                'city_name' => 'KAB. TOLI TOLI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            389 => 
            array (
                'id_city' => 7205,
                'id_province' => 72,
                'city_name' => 'KAB. BUOL',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            390 => 
            array (
                'id_city' => 7206,
                'id_province' => 72,
                'city_name' => 'KAB. MOROWALI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            391 => 
            array (
                'id_city' => 7207,
                'id_province' => 72,
                'city_name' => 'KAB. BANGGAI KEPULAUAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            392 => 
            array (
                'id_city' => 7208,
                'id_province' => 72,
                'city_name' => 'KAB. PARIGI MOUTONG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            393 => 
            array (
                'id_city' => 7209,
                'id_province' => 72,
                'city_name' => 'KAB. TOJO UNA UNA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            394 => 
            array (
                'id_city' => 7210,
                'id_province' => 72,
                'city_name' => 'KAB. SIGI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            395 => 
            array (
                'id_city' => 7211,
                'id_province' => 72,
                'city_name' => 'KAB. BANGGAI LAUT',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            396 => 
            array (
                'id_city' => 7212,
                'id_province' => 72,
                'city_name' => 'KAB. MOROWALI UTARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            397 => 
            array (
                'id_city' => 7271,
                'id_province' => 72,
                'city_name' => 'KOTA PALU',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            398 => 
            array (
                'id_city' => 7301,
                'id_province' => 73,
                'city_name' => 'KAB. KEPULAUAN SELAYAR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            399 => 
            array (
                'id_city' => 7302,
                'id_province' => 73,
                'city_name' => 'KAB. BULUKUMBA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            400 => 
            array (
                'id_city' => 7303,
                'id_province' => 73,
                'city_name' => 'KAB. BANTAENG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            401 => 
            array (
                'id_city' => 7304,
                'id_province' => 73,
                'city_name' => 'KAB. JENEPONTO',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            402 => 
            array (
                'id_city' => 7305,
                'id_province' => 73,
                'city_name' => 'KAB. TAKALAR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            403 => 
            array (
                'id_city' => 7306,
                'id_province' => 73,
                'city_name' => 'KAB. GOWA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            404 => 
            array (
                'id_city' => 7307,
                'id_province' => 73,
                'city_name' => 'KAB. SINJAI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            405 => 
            array (
                'id_city' => 7308,
                'id_province' => 73,
                'city_name' => 'KAB. BONE',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            406 => 
            array (
                'id_city' => 7309,
                'id_province' => 73,
                'city_name' => 'KAB. MAROS',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            407 => 
            array (
                'id_city' => 7310,
                'id_province' => 73,
                'city_name' => 'KAB. PANGKAJENE KEPULAUAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            408 => 
            array (
                'id_city' => 7311,
                'id_province' => 73,
                'city_name' => 'KAB. BARRU',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            409 => 
            array (
                'id_city' => 7312,
                'id_province' => 73,
                'city_name' => 'KAB. SOPPENG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            410 => 
            array (
                'id_city' => 7313,
                'id_province' => 73,
                'city_name' => 'KAB. WAJO',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            411 => 
            array (
                'id_city' => 7314,
                'id_province' => 73,
                'city_name' => 'KAB. SIDENRENG RAPPANG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            412 => 
            array (
                'id_city' => 7315,
                'id_province' => 73,
                'city_name' => 'KAB. PINRANG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            413 => 
            array (
                'id_city' => 7316,
                'id_province' => 73,
                'city_name' => 'KAB. ENREKANG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            414 => 
            array (
                'id_city' => 7317,
                'id_province' => 73,
                'city_name' => 'KAB. LUWU',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            415 => 
            array (
                'id_city' => 7318,
                'id_province' => 73,
                'city_name' => 'KAB. TANA TORAJA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            416 => 
            array (
                'id_city' => 7322,
                'id_province' => 73,
                'city_name' => 'KAB. LUWU UTARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            417 => 
            array (
                'id_city' => 7324,
                'id_province' => 73,
                'city_name' => 'KAB. LUWU TIMUR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            418 => 
            array (
                'id_city' => 7326,
                'id_province' => 73,
                'city_name' => 'KAB. TORAJA UTARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            419 => 
            array (
                'id_city' => 7371,
                'id_province' => 73,
                'city_name' => 'KOTA MAKASSAR',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            420 => 
            array (
                'id_city' => 7372,
                'id_province' => 73,
                'city_name' => 'KOTA PARE PARE',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            421 => 
            array (
                'id_city' => 7373,
                'id_province' => 73,
                'city_name' => 'KOTA PALOPO',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            422 => 
            array (
                'id_city' => 7401,
                'id_province' => 74,
                'city_name' => 'KAB. KOLAKA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            423 => 
            array (
                'id_city' => 7402,
                'id_province' => 74,
                'city_name' => 'KAB. KONAWE',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            424 => 
            array (
                'id_city' => 7403,
                'id_province' => 74,
                'city_name' => 'KAB. MUNA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            425 => 
            array (
                'id_city' => 7404,
                'id_province' => 74,
                'city_name' => 'KAB. BUTON',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            426 => 
            array (
                'id_city' => 7405,
                'id_province' => 74,
                'city_name' => 'KAB. KONAWE SELATAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            427 => 
            array (
                'id_city' => 7406,
                'id_province' => 74,
                'city_name' => 'KAB. BOMBANA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            428 => 
            array (
                'id_city' => 7407,
                'id_province' => 74,
                'city_name' => 'KAB. WAKATOBI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            429 => 
            array (
                'id_city' => 7408,
                'id_province' => 74,
                'city_name' => 'KAB. KOLAKA UTARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            430 => 
            array (
                'id_city' => 7409,
                'id_province' => 74,
                'city_name' => 'KAB. KONAWE UTARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            431 => 
            array (
                'id_city' => 7410,
                'id_province' => 74,
                'city_name' => 'KAB. BUTON UTARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            432 => 
            array (
                'id_city' => 7411,
                'id_province' => 74,
                'city_name' => 'KAB. KOLAKA TIMUR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            433 => 
            array (
                'id_city' => 7412,
                'id_province' => 74,
                'city_name' => 'KAB. KONAWE KEPULAUAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            434 => 
            array (
                'id_city' => 7413,
                'id_province' => 74,
                'city_name' => 'KAB. MUNA BARAT',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            435 => 
            array (
                'id_city' => 7414,
                'id_province' => 74,
                'city_name' => 'KAB. BUTON TENGAH',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            436 => 
            array (
                'id_city' => 7415,
                'id_province' => 74,
                'city_name' => 'KAB. BUTON SELATAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            437 => 
            array (
                'id_city' => 7471,
                'id_province' => 74,
                'city_name' => 'KOTA KENDARI',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            438 => 
            array (
                'id_city' => 7472,
                'id_province' => 74,
                'city_name' => 'KOTA BAU BAU',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            439 => 
            array (
                'id_city' => 7501,
                'id_province' => 75,
                'city_name' => 'KAB. GORONTALO',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            440 => 
            array (
                'id_city' => 7502,
                'id_province' => 75,
                'city_name' => 'KAB. BOALEMO',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            441 => 
            array (
                'id_city' => 7503,
                'id_province' => 75,
                'city_name' => 'KAB. BONE BOLANGO',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            442 => 
            array (
                'id_city' => 7504,
                'id_province' => 75,
                'city_name' => 'KAB. PAHUWATO',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            443 => 
            array (
                'id_city' => 7505,
                'id_province' => 75,
                'city_name' => 'KAB. GORONTALO UTARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            444 => 
            array (
                'id_city' => 7571,
                'id_province' => 75,
                'city_name' => 'KOTA GORONTALO',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            445 => 
            array (
                'id_city' => 7601,
                'id_province' => 76,
                'city_name' => 'KAB. PASANGKAYU',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            446 => 
            array (
                'id_city' => 7602,
                'id_province' => 76,
                'city_name' => 'KAB. MAMUJU',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            447 => 
            array (
                'id_city' => 7603,
                'id_province' => 76,
                'city_name' => 'KAB. MAMASA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            448 => 
            array (
                'id_city' => 7604,
                'id_province' => 76,
                'city_name' => 'KAB. POLEWALI MANDAR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            449 => 
            array (
                'id_city' => 7605,
                'id_province' => 76,
                'city_name' => 'KAB. MAJENE',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            450 => 
            array (
                'id_city' => 7606,
                'id_province' => 76,
                'city_name' => 'KAB. MAMUJU TENGAH',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            451 => 
            array (
                'id_city' => 8101,
                'id_province' => 81,
                'city_name' => 'KAB. MALUKU TENGAH',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            452 => 
            array (
                'id_city' => 8102,
                'id_province' => 81,
                'city_name' => 'KAB. MALUKU TENGGARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            453 => 
            array (
                'id_city' => 8103,
                'id_province' => 81,
                'city_name' => 'KAB. KEPULAUAN TANIMBAR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            454 => 
            array (
                'id_city' => 8104,
                'id_province' => 81,
                'city_name' => 'KAB. BURU',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            455 => 
            array (
                'id_city' => 8105,
                'id_province' => 81,
                'city_name' => 'KAB. SERAM BAGIAN TIMUR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            456 => 
            array (
                'id_city' => 8106,
                'id_province' => 81,
                'city_name' => 'KAB. SERAM BAGIAN BARAT',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            457 => 
            array (
                'id_city' => 8107,
                'id_province' => 81,
                'city_name' => 'KAB. KEPULAUAN ARU',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            458 => 
            array (
                'id_city' => 8108,
                'id_province' => 81,
                'city_name' => 'KAB. MALUKU BARAT DAYA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            459 => 
            array (
                'id_city' => 8109,
                'id_province' => 81,
                'city_name' => 'KAB. BURU SELATAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            460 => 
            array (
                'id_city' => 8171,
                'id_province' => 81,
                'city_name' => 'KOTA AMBON',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            461 => 
            array (
                'id_city' => 8172,
                'id_province' => 81,
                'city_name' => 'KOTA TUAL',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            462 => 
            array (
                'id_city' => 8201,
                'id_province' => 82,
                'city_name' => 'KAB. HALMAHERA BARAT',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            463 => 
            array (
                'id_city' => 8202,
                'id_province' => 82,
                'city_name' => 'KAB. HALMAHERA TENGAH',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            464 => 
            array (
                'id_city' => 8203,
                'id_province' => 82,
                'city_name' => 'KAB. HALMAHERA UTARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            465 => 
            array (
                'id_city' => 8204,
                'id_province' => 82,
                'city_name' => 'KAB. HALMAHERA SELATAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            466 => 
            array (
                'id_city' => 8205,
                'id_province' => 82,
                'city_name' => 'KAB. KEPULAUAN SULA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            467 => 
            array (
                'id_city' => 8206,
                'id_province' => 82,
                'city_name' => 'KAB. HALMAHERA TIMUR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            468 => 
            array (
                'id_city' => 8207,
                'id_province' => 82,
                'city_name' => 'KAB. PULAU MOROTAI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            469 => 
            array (
                'id_city' => 8208,
                'id_province' => 82,
                'city_name' => 'KAB. PULAU TALIABU',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            470 => 
            array (
                'id_city' => 8271,
                'id_province' => 82,
                'city_name' => 'KOTA TERNATE',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            471 => 
            array (
                'id_city' => 8272,
                'id_province' => 82,
                'city_name' => 'KOTA TIDORE KEPULAUAN',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            472 => 
            array (
                'id_city' => 9101,
                'id_province' => 91,
                'city_name' => 'KAB. MERAUKE',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            473 => 
            array (
                'id_city' => 9102,
                'id_province' => 91,
                'city_name' => 'KAB. JAYAWIJAYA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            474 => 
            array (
                'id_city' => 9103,
                'id_province' => 91,
                'city_name' => 'KAB. JAYAPURA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            475 => 
            array (
                'id_city' => 9104,
                'id_province' => 91,
                'city_name' => 'KAB. NABIRE',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            476 => 
            array (
                'id_city' => 9105,
                'id_province' => 91,
                'city_name' => 'KAB. KEPULAUAN YAPEN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            477 => 
            array (
                'id_city' => 9106,
                'id_province' => 91,
                'city_name' => 'KAB. BIAK NUMFOR',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            478 => 
            array (
                'id_city' => 9107,
                'id_province' => 91,
                'city_name' => 'KAB. PUNCAK JAYA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            479 => 
            array (
                'id_city' => 9108,
                'id_province' => 91,
                'city_name' => 'KAB. PANIAI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            480 => 
            array (
                'id_city' => 9109,
                'id_province' => 91,
                'city_name' => 'KAB. MIMIKA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            481 => 
            array (
                'id_city' => 9110,
                'id_province' => 91,
                'city_name' => 'KAB. SARMI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            482 => 
            array (
                'id_city' => 9111,
                'id_province' => 91,
                'city_name' => 'KAB. KEEROM',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            483 => 
            array (
                'id_city' => 9112,
                'id_province' => 91,
                'city_name' => 'KAB. PEGUNUNGAN BINTANG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            484 => 
            array (
                'id_city' => 9113,
                'id_province' => 91,
                'city_name' => 'KAB. YAHUKIMO',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            485 => 
            array (
                'id_city' => 9114,
                'id_province' => 91,
                'city_name' => 'KAB. TOLIKARA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            486 => 
            array (
                'id_city' => 9115,
                'id_province' => 91,
                'city_name' => 'KAB. WAROPEN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            487 => 
            array (
                'id_city' => 9116,
                'id_province' => 91,
                'city_name' => 'KAB. BOVEN DIGOEL',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            488 => 
            array (
                'id_city' => 9117,
                'id_province' => 91,
                'city_name' => 'KAB. MAPPI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            489 => 
            array (
                'id_city' => 9118,
                'id_province' => 91,
                'city_name' => 'KAB. ASMAT',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            490 => 
            array (
                'id_city' => 9119,
                'id_province' => 91,
                'city_name' => 'KAB. SUPIORI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            491 => 
            array (
                'id_city' => 9120,
                'id_province' => 91,
                'city_name' => 'KAB. MAMBERAMO RAYA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            492 => 
            array (
                'id_city' => 9121,
                'id_province' => 91,
                'city_name' => 'KAB. MAMBERAMO TENGAH',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            493 => 
            array (
                'id_city' => 9122,
                'id_province' => 91,
                'city_name' => 'KAB. YALIMO',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            494 => 
            array (
                'id_city' => 9123,
                'id_province' => 91,
                'city_name' => 'KAB. LANNY JAYA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            495 => 
            array (
                'id_city' => 9124,
                'id_province' => 91,
                'city_name' => 'KAB. NDUGA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            496 => 
            array (
                'id_city' => 9125,
                'id_province' => 91,
                'city_name' => 'KAB. PUNCAK',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            497 => 
            array (
                'id_city' => 9126,
                'id_province' => 91,
                'city_name' => 'KAB. DOGIYAI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            498 => 
            array (
                'id_city' => 9127,
                'id_province' => 91,
                'city_name' => 'KAB. INTAN JAYA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            499 => 
            array (
                'id_city' => 9128,
                'id_province' => 91,
                'city_name' => 'KAB. DEIYAI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
        ));
        \DB::table('cities')->insert(array (
            0 => 
            array (
                'id_city' => 9171,
                'id_province' => 91,
                'city_name' => 'KOTA JAYAPURA',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            1 => 
            array (
                'id_city' => 9201,
                'id_province' => 92,
                'city_name' => 'KAB. SORONG',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            2 => 
            array (
                'id_city' => 9202,
                'id_province' => 92,
                'city_name' => 'KAB. MANOKWARI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            3 => 
            array (
                'id_city' => 9203,
                'id_province' => 92,
                'city_name' => 'KAB. FAK FAK',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            4 => 
            array (
                'id_city' => 9204,
                'id_province' => 92,
                'city_name' => 'KAB. SORONG SELATAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            5 => 
            array (
                'id_city' => 9205,
                'id_province' => 92,
                'city_name' => 'KAB. RAJA AMPAT',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            6 => 
            array (
                'id_city' => 9206,
                'id_province' => 92,
                'city_name' => 'KAB. TELUK BINTUNI',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            7 => 
            array (
                'id_city' => 9207,
                'id_province' => 92,
                'city_name' => 'KAB. TELUK WONDAMA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            8 => 
            array (
                'id_city' => 9208,
                'id_province' => 92,
                'city_name' => 'KAB. KAIMANA',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            9 => 
            array (
                'id_city' => 9209,
                'id_province' => 92,
                'city_name' => 'KAB. TAMBRAUW',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            10 => 
            array (
                'id_city' => 9210,
                'id_province' => 92,
                'city_name' => 'KAB. MAYBRAT',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            11 => 
            array (
                'id_city' => 9211,
                'id_province' => 92,
                'city_name' => 'KAB. MANOKWARI SELATAN',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            12 => 
            array (
                'id_city' => 9212,
                'id_province' => 92,
                'city_name' => 'KAB. PEGUNUNGAN ARFAK',
                'city_type' => 'Kabupaten',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
            13 => 
            array (
                'id_city' => 9271,
                'id_province' => 92,
                'city_name' => 'KOTA SORONG',
                'city_type' => 'Kota',
                'city_postal_code' => NULL,
                'city_latitude' => NULL,
                'city_longitude' => NULL,
            ),
        ));
        
        
    }
}