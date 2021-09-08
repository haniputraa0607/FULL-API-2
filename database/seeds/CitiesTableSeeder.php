@@ -0,0 +1,1230 @@
<?php

use Illuminate\Database\Seeder;

class CitiesTableSeeder extends Seeder
{
    public function run()
    {
        

        \DB::table('cities')->delete();
        
        \DB::table('cities')->insert(array (
            0 => 
            array (
              'id_city' => 1,
                'id_province' => 11,
                'city_name' => 'Aceh Selatan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '23719',
            ),
             1 => 
            array (
                'id_city' => 2,
                'id_province' => 11,
                'city_name' => 'Aceh Tenggara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '24611',
            ),
              2 => 
            array (
                'id_city' => 3,
                'id_province' => 11,
                'city_name' => 'Aceh Timur',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '24454',
            ),
             3 => 
            array (
                'id_city' => 4,
                'id_province' => 11,
                'city_name' => 'Aceh Tengah',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '24511',
            ),
             4 => 
            array (
                'id_city' => 5,
                'id_province' => 11,
                'city_name' => 'Aceh Tengah',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '24511',
            ),
             5 => 
            array (
               'id_city' => 6,
                'id_province' => 11,
                'city_name' => 'Aceh Barat',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '23681',
            ),
             6 => 
            array (
                'id_city' => 7,
                'id_province' => 11,
                'city_name' => 'Aceh Besar',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '23951',
            ),
             7 => 
            array (
                'id_city' => 8,
                'id_province' => 11,
                'city_name' => 'Pidie',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '24116',
            ),
             8 => 
            array (
                'id_city' => 9,
                'id_province' => 11,
                'city_name' => 'Aceh Utara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '24382',
            ),
             9 => 
            array (
                'id_city' => 10,
                'id_province' => 11,
                'city_name' => 'Simeulue',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '23891',
            ),
             10 => 
            array (
                'id_city' => 11,
                'id_province' => 11,
                'city_name' => 'Aceh Singkil',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '24785',
            ),
             11 => 
            array (
                'id_city' => 12,
                'id_province' => 11,
                'city_name' => 'Bireuen',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '24219',
            ),
             12 => 
            array (
                'id_city' => 13,
                'id_province' => 11,
                'city_name' => 'Aceh Barat Daya',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '23764',
            ),
            13 => 
            array (
                'id_city' => 14,
                'id_province' => 11,
                'city_name' => 'Gayo Lues',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '24653',
            ),
            14 => 
            array (
                'id_city' => 15,
                'id_province' => 11,
                'city_name' => 'Aceh Jaya',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '23654',
            ),
            15 => 
            array (
                'id_city' => 16,
                'id_province' => 11,
                'city_name' => 'Nagan Raya',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '23674',
            ),
            16 => 
            array (
               'id_city' => 17,
                'id_province' => 11,
                'city_name' => 'Aceh Tamiang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '24476',
            ),
            17 => 
            array (
                'id_city' => 18,
                'id_province' => 11,
                'city_name' => 'Bener Meriah',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '24581',
            ),
            18 => 
            array (
                'id_city' => 19,
                'id_province' => 11,
                'city_name' => 'Pidie Jaya',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '24186',
            ),
            19 => 
            array (
                'id_city' => 20,
                'id_province' => 11,
                'city_name' => 'Banda Aceh',
                'city_type' => 'Kota',
                'city_postal_code' => '23238',
            ),
            20 => 
            array (
               'id_city' => 21,
                'id_province' => 11,
                'city_name' => 'Sabang',
                'city_type' => 'Kota',
                'city_postal_code' => '23512',
            ),
            21 => 
            array (
                'id_city' => 22,
                'id_province' => 11,
                'city_name' => 'Lhokseumawe',
                'city_type' => 'Kota',
                'city_postal_code' => '24352',
            ),
            22 => 
            array (
                'id_city' => 23,
                'id_province' => 11,
                'city_name' => 'Langsa',
                'city_type' => 'Kota',
                'city_postal_code' => '24412',
            ),
            23 => 
            array (
                'id_city' => 24,
                'id_province' => 11,
                'city_name' => 'Subulussalam',
                'city_type' => 'Kota',
                'city_postal_code' => '24882',
            ),
            
            //Sumatera Utara
            24 => 
            array (
                'id_city' => 25,
                'id_province' => 12,
                'city_name' => 'Tapanuli Tengah',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '22611',
            ),
            25 => 
            array (
                'id_city' => 26,
                'id_province' => 12,
                'city_name' => 'Tapanuli Utara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '22414',
            ),
            26 => 
            array (
                'id_city' => 27,
                'id_province' => 12,
                'city_name' => 'Tapanuli Selatan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '22742',
            ),
            27 => 
            array (
                'id_city' => 28,
                'id_province' => 12,
                'city_name' => 'Nias',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '22876',
            ),
            28 => 
            array (
                'id_city' => 29,
                'id_province' => 12,
                'city_name' => 'Langkat',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '20811',
            ),
            29 => 
            array (
                'id_city' => 30,
                'id_province' => 12,
                'city_name' => 'Karo',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '22119',
            ),
            30 => 
            array (
                'id_city' => 31,
                'id_province' => 12,
                'city_name' => 'Deli Serdang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '20511',
            ),
            31 => 
            array (
                'id_city' => 32,
                'id_province' => 12,
                'city_name' => 'Simalungun',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '21162',
            ),
            32 => 
            array (
                'id_city' => 33,
                'id_province' => 12,
                'city_name' => 'Asahan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '21214',
            ),
            33 => 
            array (
                'id_city' => 34,
                'id_province' => 12,
                'city_name' => 'Labuhanbatu',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '21412',
            ),
            34 => 
            array (
                'id_city' => 35,
                'id_province' => 12,
                'city_name' => 'Dairi',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '22211',
            ),
            35 => 
            array (
                'id_city' => 36,
                'id_province' => 12,
                'city_name' => 'Toba Samosir',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '22316',
            ),
            36 => 
            array (
                'id_city' => 37,
                'id_province' => 12,
                'city_name' => 'Mandailing Natal',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '22916',
            ),
            37 => 
            array (
                'id_city' => 38,
                'id_province' => 12,
                'city_name' => 'Nias Selatan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '22865',
            ),
            38 => 
            array (
                'id_city' => 39,
                'id_province' => 12,
                'city_name' => 'Pakpak Bharat',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '22272',
            ),
            39 => 
            array (
                'id_city' => 40,
                'id_province' => 12,
                'city_name' => 'Humbang Hasundutan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '22457',
            ),
            40 => 
            array (
                'id_city' => 41,
                'id_province' => 12,
                'city_name' => 'Samosir',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '22392',
            ),
            41 => 
            array (
                'id_city' => 42,
                'id_province' => 12,
                'city_name' => 'Serdang Bedagai',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '20915',
            ),
            42 => 
            array (
                'id_city' => 43,
                'id_province' => 12,
                'city_name' => 'Batu Bara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '21655',
            ),
            43 => 
            array (
                'id_city' => 44,
                'id_province' => 12,
                'city_name' => 'Padang Lawas Utara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '22753',
            ),
            44 => 
            array (
                'id_city' => 45,
                'id_province' => 12,
                'city_name' => 'Padang Lawas',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '22763',
            ),
            45 => 
            array (
                'id_city' => 46,
                'id_province' => 12,
                'city_name' => 'Labuhan Batu Selatan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '21511',
            ),
            46 => 
            array (
                'id_city' => 47,
                'id_province' => 12,
                'city_name' => 'Labuhan Batu Utara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '21711',
            ),
            47 => 
            array (
                'id_city' => 48,
                'id_province' => 12,
                'city_name' => 'Nias Utara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '22856',
            ),
            48 => 
            array (
                'id_city' => 49,
                'id_province' => 12,
                'city_name' => 'Nias Barat',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '22895',
            ),
            49 => 
            array (
                'id_city' => 50,
                'id_province' => 12,
                'city_name' => 'Medan',
                'city_type' => 'Kota',
                'city_postal_code' => '20228',
            ),
            50 => 
            array (
                'id_city' => 51,
                'id_province' => 12,
                'city_name' => 'Pematang Siantar',
                'city_type' => 'Kota',
                'city_postal_code' => '21126',
            ),
            51 => 
            array (
                'id_city' => 52,
                'id_province' => 12,
                'city_name' => 'Sibolga',
                'city_type' => 'Kota',
                'city_postal_code' => '22522',
            ),
            52 => 
            array (
                'id_city' => 53,
                'id_province' => 12,
                'city_name' => 'Tanjung Balai',
                'city_type' => 'Kota',
                'city_postal_code' => '21321',
            ),
            53 => 
            array (
                'id_city' => 54,
                'id_province' => 12,
                'city_name' => 'Binjai',
                'city_type' => 'Kota',
                'city_postal_code' => '20712',
            ),
            54 => 
            array (
                'id_city' => 55,
                'id_province' => 12,
                'city_name' => 'Tebing Tinggi',
                'city_type' => 'Kota',
                'city_postal_code' => '20632',
            ),
            55 => 
            array (
                'id_city' => 56,
                'id_province' => 12,
                'city_name' => 'Padangsidimpuan',
                'city_type' => 'Kota',
                'city_postal_code' => '22727',
            ),
            
            //Sumatera Barat
            56 => 
            array (
                'id_city' => 57,
                'id_province' => 13,
                'city_name' => 'Pesisir Selatan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '25611',
            ),
            57 => 
            array (
                 'id_city' => 58,
                'id_province' => 13,
                'city_name' => 'Solok',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '27365',
            ),
            58 => 
            array (
                 'id_city' => 59,
                'id_province' => 13,
                'city_name' => 'Sijunjung',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '27511',
            ),
            59 => 
            array (
                'id_city' => 60,
                'id_province' => 13,
                'city_name' => 'Tanah Datar',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '27211',
            ),
            60 => 
            array (
                'id_city' => 61,
                'id_province' => 13,
                'city_name' => 'Padang Pariaman',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '25583',
            ),
            61 => 
            array (
                'id_city' => 62,
                'id_province' => 13,
                'city_name' => 'Agam',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '26411',
            ),
            62 => 
            array (
               'id_city' => 63,
                'id_province' => 13,
                'city_name' => 'Lima Puluh Kota',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '26671',
            ),
            63 => 
            array (
                'id_city' => 64,
                'id_province' => 13,
                'city_name' => 'Pasaman',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '26318',
            ),
            64 => 
            array (
                'id_city' => 65,
                'id_province' => 13,
                'city_name' => 'Kepulauan Mentawai',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '25771',
            ),
            65 => 
            array (
                'id_city' => 66,
                'id_province' => 13,
                'city_name' => 'Dharmasraya',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '27612',
            ),
            66 => 
            array (
                'id_city' => 67,
                'id_province' => 13,
                'city_name' => 'Solok Selatan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '27779',
            ),
            67 => 
            array (
               'id_city' => 68,
                'id_province' => 13,
                'city_name' => 'Pasaman Barat',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '26511',
            ),
            68 => 
            array (
               'id_city' => 69,
                'id_province' => 13,
                'city_name' => 'Padang',
                'city_type' => 'Kota',
                'city_postal_code' => '25112',
            ),
            69 => 
            array (
                'id_city' => 70,
                'id_province' => 13,
                'city_name' => 'Solok',
                'city_type' => 'Kota',
                'city_postal_code' => '27315',
            ),
            70 => 
            array (
              'id_city' => 71,
                'id_province' => 13,
                'city_name' => 'Sawahlunto',
                'city_type' => 'Kota',
                'city_postal_code' => '27416',
            ),
            71 => 
            array (
                'id_city' => 71,
                'id_province' => 13,
                'city_name' => 'Padang Panjang',
                'city_type' => 'Kota',
                'city_postal_code' => '27122',
            ),
            71 => 
            array (
                'id_city' => 72,
                'id_province' => 13,
                'city_name' => 'Bukittinggi',
                'city_type' => 'Kota',
                'city_postal_code' => '26115',
            ),
            72 => 
            array (
                'id_city' => 73,
                'id_province' => 13,
                'city_name' => 'Payakumbuh',
                'city_type' => 'Kota',
                'city_postal_code' => '26213',
            ),
            73 => 
            array (
                'id_city' => 74,
                'id_province' => 13,
                'city_name' => 'Pariaman',
                'city_type' => 'Kota',
                'city_postal_code' => '25511',
            ),
            
            //Riau
            74 => 
            array (
                'id_city' => 75,
                'id_province' => 14,
                'city_name' => 'Kampar',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '28411',
            ),
            75 => 
            array (
                'id_city' => 76,
                'id_province' => 14,
                'city_name' => 'Indragiri Hulu',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '29319',
            ),
            76 => 
            array (
                'id_city' => 77,
                'id_province' => 14,
                'city_name' => 'Bengkalis',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '28719'
            ),
            77 => 
            array (
                'id_city' => 78,
                'id_province' => 14,
                'city_name' => 'Indragiri Hilir',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '29212',
            ),
            78 => 
            array (
                'id_city' => 79,
                'id_province' => 14,
                'city_name' => 'Pelalawan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '28311',
            ),
            79 => 
            array (
                'id_city' => 80,
                'id_province' => 14,
                'city_name' => 'Rokan Hulu',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '28511',
            ),
            80 => 
            array (
                'id_city' => 81,
                'id_province' => 14,
                'city_name' => 'Rokan Hilir',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '28992',
            ),
            81 => 
            array (
                'id_city' => 82,
                'id_province' => 14,
                'city_name' => 'Siak',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '28623',
            ),
            82 => 
            array (
                'id_city' => 83,
                'id_province' => 14,
                'city_name' => 'Kuantan Singingi',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '29519',
            ),
            83 => 
            array (
                'id_city' => 84,
                'id_province' => 14,
                'city_name' => 'Kepulauan Meranti',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '28791',
            ),
            84 => 
            array (
                'id_city' => 85,
                'id_province' => 14,
                'city_name' => 'Pekanbaru',
                'city_type' => 'Kota',
                'city_postal_code' => '28112',
            ),
            85 => 
            array (
                'id_city' => 86,
                'id_province' => 14,
                'city_name' => 'Dumai',
                'city_type' => 'Kota',
                'city_postal_code' => '28811',
            ),
            
            //Jambi
            86 => 
            array (
                'id_city' => 87,
                'id_province' => 15,
                'city_name' => 'Kerinci',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '37167'
            ),
            87 => 
            array (
                'id_city' => 88,
                'id_province' => 15,
                'city_name' => 'Merangin',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '37319',
            ),
            88 => 
            array (
                'id_city' => 89,
                'id_province' => 15,
                'city_name' => 'Sarolangun',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '37419',
            ),
            89 => 
            array (
                'id_city' => 90,
                'id_province' => 15,
                'city_name' => 'Batanghari',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '36613',
            ),
            90 => 
            array (
                'id_city' => 91,
                'id_province' => 15,
                'city_name' => 'Muaro Jambi',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '36311',
            ),
            91 => 
            array (
                'id_city' => 92,
                'id_province' => 15,
                'city_name' => 'Tanjung Jabung Barat',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '36513',
            ),
            92 => 
            array (
                'id_city' => 93,
                'id_province' => 15,
                'city_name' => 'Tanjung Jabung Timur',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '36719',
            ),
            93 => 
            array (
                'id_city' => 94,
                'id_province' => 15,
                'city_name' => 'Bungo',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '37216',
            ),
            94 => 
            array (
                'id_city' => 95,
                'id_province' => 15,
                'city_name' => 'Tebo',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '37519',
            ),
            95 => 
            array (
                'id_city' => 96,
                'id_province' => 15,
                'city_name' => 'Jambi',
                'city_type' => 'Kota',
                'city_postal_code' => '36111',
            ),
            96 => 
            array (
                'id_city' => 97,
                'id_province' => 15,
                'city_name' => 'Sungai Penuh',
                'city_type' => 'Kota',
                'city_postal_code' => '37113',
            ),
            
            //Sumsel
            97 => 
            array (
                'id_city' => 98,
                'id_province' => 16,
                'city_name' => 'Ogan Komering Ulu',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '32112'
            ),
            98 => 
            array (
                'id_city' => 99,
                'id_province' => 16,
                'city_name' => 'Ogan Komering Ilir',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '30618',
            ),
            99 => 
            array (
                'id_city' => 100,
                'id_province' => 16,
                'city_name' => 'Muara Enim',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '31315',
            ),
            100 => 
            array (
                'id_city' => 101,
                'id_province' => 16,
                'city_name' => 'Lahat',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '31419',
            ),
            101 => 
            array (
                'id_city' => 102,
                'id_province' => 16,
                'city_name' => 'Musi Rawas',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '31661',
            ),
            102 => 
            array (
                'id_city' => 103,
                'id_province' => 16,
                'city_name' => 'Musi Banyuasin',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '30719',
            ),
            103 => 
            array (
                'id_city' => 104,
                'id_province' => 16,
                'city_name' => 'Banyuasin',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '30911',
            ),
            104 => 
            array (
                'id_city' => 105,
                'id_province' => 16,
                'city_name' => 'Ogan Komering Ulu Timur',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '32312',
            ),
            105 => 
            array (
                'id_city' => 106,
                'id_province' => 16,
                'city_name' => 'Ogan Komering Ulu Selatan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '32211',
            ),
            106 => 
            array (
                'id_city' => 107,
                'id_province' => 16,
                'city_name' => 'Ogan Ilir',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '30811',
            ),
            107 => 
            array (
                'id_city' => 108,
                'id_province' => 16,
                'city_name' => 'Empat Lawang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '31811',
            ),
            108 => 
            array (
                'id_city' => 109,
                'id_province' => 16,
                'city_name' => 'Penukal Abab Lematang Ilir',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '31211',
            ),
            109 => 
            array (
                'id_city' => 110,
                'id_province' => 16,
                'city_name' => 'Penukal Abab Lematang Ilir',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '31653',
            ),
            110 => 
            array (
                'id_city' => 111,
                'id_province' => 16,
                'city_name' => 'Palembang',
                'city_type' => 'Kota',
                'city_postal_code' => '31512',
            ),
            111 => 
            array (
                'id_city' => 112,
                'id_province' => 16,
                'city_name' => 'Pagar Alam',
                'city_type' => 'Kota',
                'city_postal_code' => '31512',
            ),
            112 => 
            array (
                'id_city' => 113,
                'id_province' => 16,
                'city_name' => 'Lubuk Linggau',
                'city_type' => 'Kota',
                'city_postal_code' => '31614',
            ),
            113 => 
            array (
                'id_city' => 114,
                'id_province' => 16,
                'city_name' => 'Prabumulih',
                'city_type' => 'Kota',
                'city_postal_code' => '31121',
            ),
            
            
            //Bengkulu
            114 => 
            array (
                'id_city' => 115,
                'id_province' => 17,
                'city_name' => 'Bengkulu Selatan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '38519',
            ),
            115 => 
            array (
                'id_city' => 116,
                'id_province' => 17,
                'city_name' => 'Rejang Lebong',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '39112',
            ),
            116 => 
            array (
                'id_city' => 117,
                'id_province' => 17,
                'city_name' => 'Bengkulu Utara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '38619',
            ),
            117 => 
            array (
                'id_city' => 118,
                'id_province' => 17,
                'city_name' => 'Kaur',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '38911',
            ),
            118 => 
            array (
                'id_city' => 119,
                'id_province' => 17,
                'city_name' => 'Seluma',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '38811',
            ),
            119 => 
            array (
                'id_city' => 120,
                'id_province' => 17,
                'city_name' => 'Muko Muko',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '38715',
            ),
            120 => 
            array (
                'id_city' => 121,
                'id_province' => 17,
                'city_name' => 'Lebong',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '39264',
            ),
            121 => 
            array (
                'id_city' => 122,
                'id_province' => 17,
                'city_name' => 'Kepahiang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '39319',
            ),
            122 => 
            array (
                'id_city' => 123,
                'id_province' => 17,
                'city_name' => 'Kepahiang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '39319',
            ),
            123 => 
            array (
                'id_city' => 124,
                'id_province' => 17,
                'city_name' => 'Bengkulu Tengah',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '38319',
            ),
            124 => 
            array (
                'id_city' => 125,
                'id_province' => 17,
                'city_name' => 'Bengkulu',
                'city_type' => 'Kota',
                'city_postal_code' => '38229',
            ),
            
            //Lampung
            125 => 
            array (
                'id_city' => 126,
                'id_province' => 18,
                'city_name' => 'Lampung Selatan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '35511',
            ),
            126 => 
            array (
                'id_city' => 127,
                'id_province' => 18,
                'city_name' => 'Lampung Tengah',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '34212',
            ),
            127 => 
            array (
                'id_city' => 128,
                'id_province' => 18,
                'city_name' => 'Lampung Utara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '34516',
            ),
            128 => 
            array (
                'id_city' => 129,
                'id_province' => 18,
                'city_name' => 'Lampung Barat',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '34814',
            ),
            129 => 
            array (
                'id_city' => 130,
                'id_province' => 18,
               'city_name' => 'Tulang Bawang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '34613',
            ),
            130 => 
            array (
                'id_city' => 131,
                'id_province' => 18,
                'city_name' => 'Tanggamus',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '35619',
            ),
            131 => 
            array (
                'id_city' => 132,
                'id_province' => 18,
                'city_name' => 'Lampung Timur',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '34319',
            ),
            132 => 
            array (
                'id_city' => 133,
                'id_province' => 18,
                'city_name' => 'Way Kanan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '34711',
            ),
            133 => 
            array (
                'id_city' => 134,
                'id_province' => 18,
                'city_name' => 'Pesawaran',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '35312',
            ),
            134 => 
            array (
                'id_city' => 135,
                'id_province' => 18,
                'city_name' => 'Pringsewu',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '35719',
            ),
            135 => 
            array (
                'id_city' => 136,
                'id_province' => 18,
                'city_name' => 'Mesuji',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '34911',
            ),
            136 => 
            array (
                'id_city' => 137,
                'id_province' => 18,
                'city_name' => 'Tulang Bawang Barat',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '34419',
            ),
            137 => 
            array (
                'id_city' => 138,
                'id_province' => 18,
                'city_name' => 'Pesisir Barat',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '35974',
            ),
            138 => 
            array (
                'id_city' => 139,
                'id_province' => 18,
                'city_name' => 'Bandar Lampung',
                'city_type' => 'Kota',
                'city_postal_code' => '35139',
            ),
            139 => 
            array (
                'id_city' => 140,
                'id_province' => 18,
                'city_name' => 'Metro',
                'city_type' => 'Kota',
                'city_postal_code' => '34111',
            ),
            
            //Kepulauan Bangka Belitung
            140 => 
            array (
                'id_city' => 141,
                'id_province' => 19,
                'city_name' => 'Bangka',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '33212',
            ),
            140 => 
            array (
                'id_city' => 141,
                'id_province' => 19,
                'city_name' => 'Belitung',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '33419',
            ),
            141 => 
            array (
                'id_city' => 142,
                'id_province' => 19,
                'city_name' => 'Bangka Selatan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '33719',
            ),
            142 => 
            array (
                'id_city' => 143,
                'id_province' => 19,
                'city_name' => 'Bangka Tengah',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '33613',
            ),
            143 => 
            array (
                'id_city' => 144,
                'id_province' => 19,
                'city_name' => 'Bangka Barat',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '33315',
            ),
            144 => 
            array (
                'id_city' => 145,
                'id_province' => 19,
                'city_name' => 'Belitung Timur',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '33519',
            ),
            145 => 
            array (
                'id_city' => 146,
                'id_province' => 19,
                'city_name' => 'Pangkal Pinang',
                'city_type' => 'Kota',
                'city_postal_code' => '33115',
            ),
            
            //
            146 => 
            array (
                'id_city' => 147,
                'id_province' => 21,
                'city_name' => 'Bintan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '29135',
            ),
            147 => 
            array (
                'id_city' => 148,
                'id_province' => 21,
                'city_name' => 'Karimun',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '29611',
            ),
            148 => 
            array (
                'id_city' => 149,
                'id_province' => 21,
                'city_name' => 'Natuna',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '29711',
            ),
            149 => 
            array (
                'id_city' => 150,
                'id_province' => 21,
                'city_name' => 'Lingga',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '29811',
            ),
            150 => 
            array (
                'id_city' => 151,
                'id_province' => 21,
                'city_name' => 'Kepulauan Anambas',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '29991',
            ),
            151 => 
            array (
                'id_city' => 152,
                'id_province' => 21,
                'city_name' => 'Batam',
                'city_type' => 'Kota',
                'city_postal_code' => '29413',
            ),
            152 => 
            array (
                'id_city' => 153,
                'id_province' => 21,
                'city_name' => 'Tanjung Pinang',
                'city_type' => 'Kota',
                'city_postal_code' => '29111',
            ),
            
            //DKI JAKARTA
            153 => 
            array (
                'id_city' => 154,
                'id_province' => 31,
                'city_name' => 'Kepulauan Seribu',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '14550',
            ),
            154 => 
            array (
                'id_city' => 155,
                'id_province' => 31,
                'city_name' => 'Jakarta Pusat',
                'city_type' => 'Kota',
                'city_postal_code' => '10540',
            ),
            155 => 
            array (
                'id_city' => 156,
                'id_province' => 31,
                'city_name' => 'Jakarta Utara',
                'city_type' => 'Kota',
                'city_postal_code' => '14140',
            ),
            156 => 
            array (
                'id_city' => 157,
                'id_province' => 31,
                'city_name' => 'Jakarta Barat',
                'city_type' => 'Kota',
                'city_postal_code' => '11220',
            ),
            157 => 
            array (
                'id_city' => 158,
                'id_province' => 31,
                'city_name' => 'Jakarta Selatan',
                'city_type' => 'Kota',
                'city_postal_code' => '12230',
            ),
            158 => 
            array (
                'id_city' => 159,
                'id_province' => 31,
                'city_name' => 'Jakarta Timur',
                'city_type' => 'Kota',
                'city_postal_code' => '13330',
            ),
            
            //Jabar
            159 => 
            array (
                'id_city' => 160,
                'id_province' => 32,
                'city_name' => 'Bogor',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '16911',
            ),
            160 => 
            array (
                'id_city' => 161,
                'id_province' => 32,
                'city_name' => 'Sukabumi',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '43311',
            ),
            161 => 
            array (
                'id_city' => 162,
                'id_province' => 32,
                'city_name' => 'Cianjur',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '43217',
            ),
            162 => 
            array (
                'id_city' => 163,
                'id_province' => 32,
                'city_name' => 'Bandung',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '40311',
            ),
            163 => 
            array (
                'id_city' => 164,
                'id_province' => 32,
                'city_name' => 'Garut',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '44126',
            ),
            164 => 
            array (
                'id_city' => 165,
                'id_province' => 32,
                'city_name' => 'Tasikmalaya',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '46411',
            ),
            165 => 
            array (
                'id_city' => 166,
                'id_province' => 32,
                'city_name' => 'Ciamis',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '46211',
            ),
            166 => 
            array (
                'id_city' => 167,
                'id_province' => 32,
                'city_name' => 'Kuningan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '45511',
            ),
            167 => 
            array (
                'id_city' => 168,
                'id_province' => 32,
                'city_name' => 'Cirebon',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '45611',
            ),
            168 => 
            array (
                'id_city' => 169,
                'id_province' => 32,
                'city_name' => 'Majalengka',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '45412',
            ),
            169 => 
            array (
                'id_city' => 170,
                'id_province' => 32,
                'city_name' => 'Sumedang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '45326',
            ),
            170 => 
            array (
                'id_city' => 171,
                'id_province' => 32,
                'city_name' => 'Indramayu',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '45214',
            ),
            171 => 
            array (
                'id_city' => 172,
                'id_province' => 32,
                'city_name' => 'Subang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '41215',
            ),
            172 => 
            array (
                'id_city' => 173,
                'id_province' => 32,
                'city_name' => 'Purwakarta',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '41119',
            ),
            173 => 
            array (
                'id_city' => 174,
                'id_province' => 32,
                'city_name' => 'Karawang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '41311',
            ),
            174 => 
            array (
                'id_city' => 175,
                'id_province' => 32,
                'city_name' => 'Bekasi',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '17837',
            ),
            175 => 
            array (
                'id_city' => 176,
                'id_province' => 32,
                'city_name' => 'Bandung Barat',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '40721',
            ),
            176 => 
            array (
                'id_city' => 177,
                'id_province' => 32,
                'city_name' => 'Pangandaran',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '46511',
            ),
            177 => 
            array (
                'id_city' => 178,
                'id_province' => 32,
                'city_name' => 'Bogor',
                'city_type' => 'Kota',
                'city_postal_code' => '16119',
            ),
            178 => 
            array (
                'id_city' => 179,
                'id_province' => 32,
                'city_name' => 'Sukabumi',
                'city_type' => 'Kota',
                'city_postal_code' => '43114',
            ),
            179 => 
            array (
                'id_city' => 180,
                'id_province' => 32,
                'city_name' => 'Bandung',
                'city_type' => 'Kota',
                'city_postal_code' => '40111',
            ),
            180 => 
            array (
                'id_city' => 181,
                'id_province' => 32,
                'city_name' => 'Cirebon',
                'city_type' => 'Kota',
                'city_postal_code' => '45116',
            ),
            181 => 
            array (
                'id_city' => 182,
                'id_province' => 32,
                'city_name' => 'Bekasi',
                'city_type' => 'Kota',
                'city_postal_code' => '17121',
            ),
            182 => 
            array (
                'id_city' => 183,
                'id_province' => 32,
                'city_name' => 'Depok',
                'city_type' => 'Kota',
                'city_postal_code' => '16416',
            ),
            183 => 
            array (
                'id_city' => 184,
                'id_province' => 32,
                'city_name' => 'Cimahi',
                'city_type' => 'Kota',
                'city_postal_code' => '40512',
            ),
            184 => 
            array (
                'id_city' => 185,
                'id_province' => 32,
                'city_name' => 'Tasikmalaya',
                'city_type' => 'Kota',
                'city_postal_code' => '46116',
            ),
            185 => 
            array (
                'id_city' => 186,
                'id_province' => 32,
                'city_name' => 'Banjar',
                'city_type' => 'Kota',
                'city_postal_code' => '46311',
            ),
            
            //jateng
            186 => 
            array (
                'id_city' => 187,
                'id_province' => 33,
                'city_name' => 'Cilacap',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '53211',
            ),
            187 => 
            array (
                'id_city' => 188,
                'id_province' => 33,
                'city_name' => 'Banyumas',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '53114',
            ),
            188 => 
            array (
                'id_city' => 189,
                'id_province' => 33,
                'city_name' => 'Purbalingga',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '53312',
            ),
            189 => 
            array (
                'id_city' => 190,
                'id_province' => 33,
                'city_name' => 'Banjarnegara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '53419',
            ),
            190 => 
            array (
                'id_city' => 191,
                'id_province' => 33,
                'city_name' => 'Kebumen',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '54319',
            ),
            191 => 
            array (
                'id_city' => 192,
                'id_province' => 33,
                'city_name' => 'Purworejo',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '54111',
            ),
            192 => 
            array (
                'id_city' => 193,
                'id_province' => 33,
                'city_name' => 'Wonosobo',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '56311',
            ),
            193 => 
            array (
                'id_city' => 194,
                'id_province' => 33,
                'city_name' => 'Magelang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '56519'
            ),
            194 => 
            array (
                'id_city' => 195,
                'id_province' => 33,
               'city_name' => 'Boyolali',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '57312',
            ),
            195 => 
            array (
                'id_city' => 196,
                'id_province' => 33,
                'city_name' => 'Klaten',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '57411',
            ),
            196 => 
            array (
                'id_city' => 197,
                'id_province' => 33,
                'city_name' => 'Sukoharjo',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '57514',
            ),
            197 => 
            array (
                'id_city' => 198,
                'id_province' => 33,
                'city_name' => 'Wonogiri',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '57619',
            ),
            198 => 
            array (
                'id_city' => 199,
                'id_province' => 33,
                'city_name' => 'Karanganyar',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '57718',
            ),
            199 => 
            array (
                'id_city' => 200,
                'id_province' => 33,
                'city_name' => 'Sragen',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '57211',
            ),
            200 => 
            array (
                'id_city' => 201,
                'id_province' => 33,
                'city_name' => 'Grobogan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '58111',
            ),
            201 => 
            array (
                'id_city' => 202,
                'id_province' => 33,
                'city_name' => 'Blora',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '58219',
            ),
            202 => 
            array (
                'id_city' => 203,
                'id_province' => 33,
                'city_name' => 'Rembang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '59219',
            ),
            203 => 
            array (
                'id_city' => 204,
                'id_province' => 33,
                'city_name' => 'Pati',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '59114',
            ),
            204 => 
            array (
                'id_city' => 205,
                'id_province' => 33,
                'city_name' => 'Kudus',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '59311',
            ),
            205 => 
            array (
                'id_city' => 206,
                'id_province' => 33,
                'city_name' => 'Jepara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '59419',
            ),
            206 => 
            array (
                'id_city' => 207,
                'id_province' => 33,
                'city_name' => 'Demak',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '59519',
            ),
            207 => 
            array (
                'id_city' => 208,
                'id_province' => 33,
                'city_name' => 'Semarang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '50511',
            ),
            208 => 
            array (
                'id_city' => 209,
                'id_province' => 33,
                'city_name' => 'Temanggung',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '56212',
            ),
            209 => 
            array (
                'id_city' => 210,
                'id_province' => 33,
                'city_name' => 'Kendal',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '51314',
            ),
            210 => 
            array (
                'id_city' => 211,
                'id_province' => 33,
                'city_name' => 'Batang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '51211',
            ),
            211 => 
            array (
                'id_city' => 212,
                'id_province' => 33,
                'city_name' => 'Pekalongan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '51161',
            ),
            212 => 
            array (
                'id_city' => 213,
                'id_province' => 33,
                'city_name' => 'Pemalang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '52319'
            ),
            213 => 
            array (
                'id_city' => 214,
                'id_province' => 33,
                'city_name' => 'Tegal',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '52419',
            ),
            214 => 
            array (
                'id_city' => 215,
                'id_province' => 33,
                'city_name' => 'Brebes',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '52212',
            ),
            215 => 
            array (
                'id_city' => 216,
                'id_province' => 33,
                'city_name' => 'Magelang',
                'city_type' => 'Kota',
                'city_postal_code' => '56133',
            ),
            216 => 
            array (
                'id_city' => 217,
                'id_province' => 33,
                'city_name' => 'Surakarta (Solo)',
                'city_type' => 'Kota',
                'city_postal_code' => '57113',
            ),
            217 => 
            array (
                'id_city' => 218,
                'id_province' => 33,
                'city_name' => 'Salatiga',
                'city_type' => 'Kota',
                'city_postal_code' => '50711',
            ),
            218 => 
            array (
                'id_city' => 219,
                'id_province' => 33,
                'city_name' => 'Semarang',
                'city_type' => 'Kota',
                'city_postal_code' => '50135',
            ),
            219 => 
            array (
                'id_city' => 220,
                'id_province' => 33,
                'city_name' => 'Pekalongan',
                'city_type' => 'Kota',
                'city_postal_code' => '51122',
            ),
            220 => 
            array (
                'id_city' => 221,
                'id_province' => 33,
                'city_name' => 'Tegal',
                'city_type' => 'Kota',
                'city_postal_code' => '52114',
            ),
            
            //DIY
            221 => 
            array (
                'id_city' => 222,
                'id_province' => 34,
                'city_name' => 'Kulon Progo',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '55611',
            ),
            222 => 
            array (
                'id_city' => 223,
                'id_province' => 34,
                'city_name' => 'Bantul',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '55715',
            ),
            223 => 
            array (
                'id_city' => 224,
                'id_province' => 34,
                'city_name' => 'Gunung Kidul',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '55812',
            ),
            224 => 
            array (
                'id_city' => 225,
                'id_province' => 34,
                'city_name' => 'Sleman',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '55513',
            ),
            225 => 
            array (
                'id_city' => 226,
                'id_province' => 34,
                'city_name' => 'Yogyakarta',
                'city_type' => 'Kota',
                'city_postal_code' => '55222',
            ),
            
            //Jatim
            226 => 
            array (
                'id_city' => 227,
                'id_province' => 35,
                'city_name' => 'Pacitan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '63512',
            ),
            227 => 
            array (
                'id_city' => 228,
                'id_province' => 35,
                'city_name' => 'Ponorogo',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '63411',
            ),
            228 => 
            array (
                'id_city' => 229,
                'id_province' => 35,
                'city_name' => 'Trenggalek',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '66312',
            ),
            229 => 
            array (
                'id_city' => 230,
                'id_province' => 35,
                'city_name' => 'Tulungagung',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '66212',
            ),
            230 => 
            array (
                'id_city' => 231,
                'id_province' => 35,
                'city_name' => 'Blitar',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '66171',
            ),
            231 => 
            array (
                'id_city' => 232,
                'id_province' => 35,
                'city_name' => 'Kediri',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '64184',
            ),
            232 => 
            array (
                'id_city' => 233,
                'id_province' => 35,
                'city_name' => 'Malang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '65163',
            ),
            233 => 
            array (
                'id_city' => 234,
                'id_province' => 35,
                'city_name' => 'Lumajang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '67319',
            ),
            234 => 
            array (
                'id_city' => 235,
                'id_province' => 35,
                'city_name' => 'Jember',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '68113',
            ),
            235 => 
            array (
                'id_city' => 236,
                'id_province' => 35,
                'city_name' => 'Banyuwangi',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '68416',
            ),
            236 => 
            array (
                'id_city' => 237,
                'id_province' => 35,
                'city_name' => 'Bondowoso',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '68219',
            ),
            237 => 
            array (
                'id_city' => 238,
                'id_province' => 35,
                'city_name' => 'Situbondo',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '68316',
            ),
            238 => 
            array (
                'id_city' => 239,
                'id_province' => 35,
                'city_name' => 'Probolinggo',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '67282',
            ),
            239 => 
            array (
                'id_city' => 240,
                'id_province' => 35,
                'city_name' => 'Pasuruan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '67153',
            ),
            240 => 
            array (
                'id_city' => 241,
                'id_province' => 35,
                'city_name' => 'Sidoarjo',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '61219',
            ),
            241 => 
            array (
                'id_city' => 242,
                'id_province' => 35,
                'city_name' => 'Mojokerto',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '61382',
            ),
            242 => 
            array (
                'id_city' => 243,
                'id_province' => 35,
                'city_name' => 'Jombang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '61415',
            ),
            243 => 
            array (
                'id_city' => 244,
                'id_province' => 35,
                'city_name' => 'Nganjuk',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '64414',
            ),
            244 => 
            array (
                'id_city' => 245,
                'id_province' => 35,
                'city_name' => 'Madiun',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '63153',
            ),
            245 => 
            array (
                'id_city' => 246,
                'id_province' => 35,
                'city_name' => 'Magetan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '63314',
            ),
            246 => 
            array (
                'id_city' => 247,
                'id_province' => 35,
                'city_name' => 'Ngawi',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '63219',
            ),
            247 => 
            array (
                'id_city' => 248,
                'id_province' => 35,
                'city_name' => 'Bojonegoro',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '62119',
            ),
            248 => 
            array (
                'id_city' => 249,
                'id_province' => 35,
                'city_name' => 'Tuban',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '62319',
            ),
            249 => 
            array (
                'id_city' => 250,
                'id_province' => 35,
                'city_name' => 'Lamongan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '64125',
            ),
            251 => 
            array (
                'id_city' => 251,
                'id_province' => 35,
                'city_name' => 'Gresik',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '61115',
            ),
            252 => 
            array (
                'id_city' => 253,
                'id_province' => 35,
                'city_name' => 'Bangkalan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '69118',
            ),
            253 => 
            array (
                'id_city' => 254,
                'id_province' => 35,
                'city_name' => 'Sampang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '69219',
            ),
            254 => 
            array (
                'id_city' => 255,
                'id_province' => 35,
                'city_name' => 'Pamekasan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '69319',
            ),
            255 => 
            array (
                'id_city' => 256,
                'id_province' => 35,
                'city_name' => 'Pamekasan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '69319',
            ),
            256 => 
            array (
                'id_city' => 257,
                'id_province' => 35,
                'city_name' => 'Sumenep',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '69413',
            ),
            257 => 
            array (
                'id_city' => 258,
                'id_province' => 35,
                'city_name' => 'Kediri',
                'city_type' => 'Kota',
                'city_postal_code' => '64125',
            ),
            258 => 
            array (
                'id_city' => 259,
                'id_province' => 35,
                'city_name' => 'Blitar',
                'city_type' => 'Kota',
                'city_postal_code' => '66124',
            ),
            259 => 
            array (
                'id_city' => 260,
                'id_province' => 35,
                'city_name' => 'Malang',
                'city_type' => 'Kota',
                'city_postal_code' => '65112',
            ),
            260 => 
            array (
                'id_city' => 261,
                'id_province' => 35,
                'city_name' => 'Probolinggo',
                'city_type' => 'Kota',
                'city_postal_code' => '67215',
            ),
            261 => 
            array (
                'id_city' => 262,
                'id_province' => 35,
                'city_name' => 'Pasuruan',
                'city_type' => 'Kota',
                'city_postal_code' => '67118',
            ),
            262 => 
            array (
                'id_city' => 263,
                'id_province' => 35,
                'city_name' => 'Mojokerto',
                'city_type' => 'Kota',
                'city_postal_code' => '61316',
            ),
            263 => 
            array (
                'id_city' => 264,
                'id_province' => 35,
                'city_name' => 'Madiun',
                'city_type' => 'Kota',
                'city_postal_code' => '63122',
            ),
            264 => 
            array (
                'id_city' => 265,
                'id_province' => 35,
                'city_name' => 'Surabaya',
                'city_type' => 'Kota',
                'city_postal_code' => '60119',
            ),
            265 => 
            array (
                'id_city' => 266,
                'id_province' => 35,
                'city_name' => 'Batu',
                'city_type' => 'Kota',
                'city_postal_code' => '65311',
            ),
            
            //banten
            266 => 
            array (
                'id_city' => 267,
                'id_province' => 35,
                'city_name' => 'Pandeglang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '42212',
            ),
            267 => 
            array (
                'id_city' => 268,
                'id_province' => 35,
                'city_name' => 'Lebak',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '42319',
            ),
            268 => 
            array (
                'id_city' => 269,
                'id_province' => 35,
                'city_name' => 'Tangerang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '15914',
            ),
            269 => 
            array (
                'id_city' => 270,
                'id_province' => 35,
                'city_name' => 'Serang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '42182',
            ),
            270 => 
            array (
                'id_city' => 271,
                'id_province' => 35,
                'city_name' => 'Tangerang',
                'city_type' => 'Kota',
                'city_postal_code' => '15111',
            ),
            271 => 
            array (
                'id_city' => 272,
                'id_province' => 35,
                'city_name' => 'Cilegon',
                'city_type' => 'Kota',
                'city_postal_code' => '42417',
            ),
            272 => 
            array (
                'id_city' => 273,
                'id_province' => 35,
                'city_name' => 'Serang',
                'city_type' => 'Kota',
                'city_postal_code' => '42111',
            ),
            273 => 
            array (
                'id_city' => 274,
                'id_province' => 35,
                'city_name' => 'Tangerang Selatan',
                'city_type' => 'Kota',
                'city_postal_code' => '15332',
            ),
            
            //Bali
            274 => 
            array (
                'id_city' => 275,
                'id_province' => 51,
                'city_name' => 'Jembrana',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '82251',
            ),
            275 => 
            array (
                'id_city' => 276,
                'id_province' => 51,
                'city_name' => 'Tabanan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '82119',
            ),
            276 => 
            array (
                'id_city' => 277,
                'id_province' => 51,
                'city_name' => 'Badung',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '80351',
            ),
            277 => 
            array (
                'id_city' => 278,
                'id_province' => 51,
                'city_name' => 'Gianyar',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '80519',
            ),
            278 => 
            array (
                'id_city' => 279,
                'id_province' => 51,
                'city_name' => 'Klungkung',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '80719',
            ),
            279 => 
            array (
                'id_city' => 280,
                'id_province' => 51,
                'city_name' => 'Bangli',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '80619',
            ),
            280 => 
            array (
                'id_city' => 281,
                'id_province' => 51,
                'city_name' => 'Karangasem',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '80819',
            ),
            281 => 
            array (
                'id_city' => 282,
                'id_province' => 51,
                'city_name' => 'Buleleng',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '81111',
            ),
            282 => 
            array (
                'id_city' => 283,
                'id_province' => 51,
                'city_name' => 'Denpasar',
                'city_type' => 'Kota',
                'city_postal_code' => '80227',
            ),
            
            //NTB
            282 => 
            array (
                'id_city' => 283,
                'id_province' => 52,
                'city_name' => 'Lombok Barat',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '83311',
            ),
            283 => 
            array (
                'id_city' => 284,
                'id_province' => 52,
                'city_name' => 'Lombok Tengah',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '83511',
            ),
            284 => 
            array (
                'id_city' => 285,
                'id_province' => 52,
                'city_name' => 'Lombok Timur',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '83612',
            ),
            285 => 
            array (
                'id_city' => 286,
                'id_province' => 52,
                'city_name' => 'Sumbawa',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '84315',
            ),
            286 => 
            array (
                'id_city' => 287,
                'id_province' => 52,
                'city_name' => 'Dompu',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '84217',
            ),
            287 => 
            array (
                'id_city' => 288,
                'id_province' => 52,
                'city_name' => 'Bima',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '84171',
            ),
            288 => 
            array (
                'id_city' => 289,
                'id_province' => 52,
                'city_name' => 'Bima',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '84171',
            ),
            289 => 
            array (
                'id_city' => 290,
                'id_province' => 52,
                'city_name' => 'Sumbawa Barat',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '84419',
            ),
            290 => 
            array (
                'id_city' => 291,
                'id_province' => 52,
                'city_name' => 'Lombok Utara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '83711',
            ),
            291 => 
            array (
                'id_city' => 292,
                'id_province' => 52,
                'city_name' => 'Mataram',
                'city_type' => 'Kota',
                'city_postal_code' => '83131',
            ),
            292 => 
            array (
                'id_city' => 293,
                'id_province' => 52,
                'city_name' => 'Bima',
                'city_type' => 'Kota',
                'city_postal_code' => '84139',
            ),
            
            //ntt
            293 => 
            array (
                'id_city' => 294,
                'id_province' => 53,
                'city_name' => 'Kupang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '85362',
            ),
            294 => 
            array (
                'id_city' => 295,
                'id_province' => 53,
                'city_name' => 'Timor Tengah Selatan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '85562',
            ),
            295 => 
            array (
                'id_city' => 296,
                'id_province' => 53,
                'city_name' => 'Timor Tengah Utara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '85612',
            ),
            296 => 
            array (
                'id_city' => 297,
                'id_province' => 53,
                'city_name' => 'Belu',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '85711',
            ),
            297 => 
            array (
                'id_city' => 298,
                'id_province' => 53,
                'city_name' => 'Alor',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '85811',
            ),
            298 => 
            array (
                'id_city' => 299,
                'id_province' => 53,
                'city_name' => 'Flores Timur',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '86213',
            ),
            299 => 
            array (
                'id_city' => 300,
                'id_province' => 53,
                'city_name' => 'Sikka',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '86121',
            ),
            300 => 
            array (
                'id_city' => 301,
                'id_province' => 53,
                'city_name' => 'Ende',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '86351',
            ),
            301 => 
            array (
                'id_city' => 302,
                'id_province' => 53,
                'city_name' => 'Ngada',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '86413',
            ),
            302 => 
            array (
                'id_city' => 303,
                'id_province' => 53,
                'city_name' => 'Manggarai',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '86551',
            ),
            303 => 
            array (
                'id_city' => 304,
                'id_province' => 53,
                'city_name' => 'Sumba Timur',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '87112',
            ),
            304 => 
            array (
                'id_city' => 305,
                'id_province' => 53,
                'city_name' => 'Sumba Barat',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '87219',
            ),
            305 => 
            array (
                'id_city' => 306,
                'id_province' => 53,
                'city_name' => 'Lembata',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '86611', 
            ),
            306 => 
            array (
                'id_city' => 307,
                'id_province' => 53,
                'city_name' => 'Rote Ndao',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '85982',
            ),
            307 => 
            array (
                'id_city' => 308,
                'id_province' => 53,
                'city_name' => 'Manggarai Barat',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '86711',
            ),
            308 => 
            array (
                'id_city' => 309,
                'id_province' => 53,
                'city_name' => 'Nagekeo',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '86911',
            ),
            309 => 
            array (
                'id_city' => 310,
                'id_province' => 53,
                'city_name' => 'Sumba Tengah',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '87358',
            ),
            310 => 
            array (
                'id_city' => 311,
                'id_province' => 53,
                'city_name' => 'Sumba Barat Daya',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '87453',
            ),
            311 => 
            array (
                'id_city' => 312,
                'id_province' => 53,
                'city_name' => 'Manggarai Timur',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '86811',
            ),
            312 => 
            array (
                'id_city' => 313,
                'id_province' => 53,
                'city_name' => 'Sabu Raijua',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '85391',
            ),
            313 => 
            array (
                'id_city' => 314,
                'id_province' => 53,
                'city_name' => 'Malaka',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '85775',
            ),
            314 => 
            array (
                'id_city' => 315,
                'id_province' => 53,
                'city_name' => 'Kupang',
                'city_type' => 'Kota',
                'city_postal_code' => '85119',
            ),
            
            //kalbar
            315 => 
            array (
                'id_city' => 316,
                'id_province' => 61,
                'city_name' => 'Sambas',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '79453',
            ),
            316 => 
            array (
                'id_city' => 317,
                'id_province' => 61,
                'city_name' => 'Mempawah',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '78353',
            ),
            317 => 
            array (
                'id_city' => 318,
                'id_province' => 61,
                'city_name' => 'Sanggau',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '78557',
            ),
            318 => 
            array (
                'id_city' => 319,
                'id_province' => 61,
                'city_name' => 'Ketapang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '78874',
            ),
            319 => 
            array (
                'id_city' => 320,
                'id_province' => 61,
                'city_name' => 'Sintang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '78619',
            ),
            320 => 
            array (
                'id_city' => 321,
                'id_province' => 61,
                'city_name' => 'Kapuas Hulu',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '78719',
            ),
            321 => 
            array (
                'id_city' => 322,
                'id_province' => 61,
                'city_name' => 'Bengkayang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '79213',
            ),
            322 => 
            array (
                'id_city' => 323,
                'id_province' => 61,
                'city_name' => 'Landak',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '78319',
            ),
            323 => 
            array (
                'id_city' => 324,
                'id_province' => 61,
                'city_name' => 'Sekadau',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '79583',
            ),
            324 => 
            array (
                'id_city' => 325,
                'id_province' => 61,
                'city_name' => 'Melawi',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '78619',
            ),
            325 => 
            array (
                'id_city' => 326,
                'id_province' => 61,
                'city_name' => 'Kayong Utara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '78852',
            ),
            326 => 
            array (
                'id_city' => 327,
                'id_province' => 61,
                'city_name' => 'Kubu Raya',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '78311',
            ),
            327 => 
            array (
                'id_city' => 328,
                'id_province' => 61,
                'city_name' => 'Pontianak',
                'city_type' => 'Kota',
                'city_postal_code' => '78112',
            ),
            327 => 
            array (
                'id_city' => 328,
                'id_province' => 61,
                'city_name' => 'Singkawang',
                'city_type' => 'Kota',
                'city_postal_code' => '79117',
            ),
            
            //Kalteng
            328 => 
            array (
                'id_city' => 329,
                'id_province' => 62,
                'city_name' => 'Kotawaringin Barat',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '74119',
            ),
            329 => 
            array (
                'id_city' => 330,
                'id_province' => 62,
                'city_name' => 'Kotawaringin Timur',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '74364',
            ),
            330 => 
            array (
                'id_city' => 331,
                'id_province' => 62,
                'city_name' => 'Kapuas',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '73583',
            ),
            331 => 
            array (
                'id_city' => 332,
                'id_province' => 62,
                'city_name' => 'Barito Selatan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '73711',
            ),
            332 => 
            array (
                'id_city' => 333,
                'id_province' => 62,
                'city_name' => 'Barito Utara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '73881',
            ),
            333 => 
            array (
                'id_city' => 334,
                'id_province' => 62,
                'city_name' => 'Katingan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '74411',
            ),
            334 => 
            array (
                'id_city' => 335,
                'id_province' => 62,
                'city_name' => 'Seruyan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '74211',
            ),
            335 => 
            array (
                'id_city' => 336,
                'id_province' => 62,
                'city_name' => 'Sukamara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '74712',
            ),
            336 => 
            array (
                'id_city' => 337,
                'id_province' => 62,
                'city_name' => 'Lamandau',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '74611',
            ),
            337 => 
            array (
                'id_city' => 338,
                'id_province' => 62,
                'city_name' => 'Gunung Mas',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '74511',
            ),
            338 => 
            array (
                'id_city' => 339,
                'id_province' => 62,
                'city_name' => 'Pulang Pisau',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '74811',
            ),
            339 => 
            array (
                'id_city' => 340,
                'id_province' => 62,
                'city_name' => 'Murung Raya',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '73911',
            ),
            340 => 
            array (
                'id_city' => 341,
                'id_province' => 62,
                'city_name' => 'Barito Timur',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '73671',
            ),
            341 => 
            array (
                'id_city' => 342,
                'id_province' => 62,
                'city_name' => 'Palangkaraya',
                'city_type' => 'Kota',
                'city_postal_code' => '73112',
            ),
            
            //Kalsel
            342 => 
            array (
                'id_city' => 343,
                'id_province' => 63,
                'city_name' => 'Tanah Laut',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '70811',
            ),
            343 => 
            array (
                'id_city' => 344,
                'id_province' => 63,
                'city_name' => 'Kotabaru',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '72119',
            ),
            344 => 
            array (
                'id_city' => 345,
                'id_province' => 63,
                'city_name' => 'Banjar',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '70619',
            ),
            345 => 
            array (
                'id_city' => 346,
                'id_province' => 63,
                'city_name' => 'Barito Kuala',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '70511',
            ),
            346 => 
            array (
                'id_city' => 347,
                'id_province' => 63,
                'city_name' => 'Tapin',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '71119',
            ),
            347 => 
            array (
                'id_city' => 348,
                'id_province' => 63,
                'city_name' => 'Hulu Sungai Selatan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '71212',
            ),
            348 => 
            array (
                'id_city' => 349,
                'id_province' => 63,
                'city_name' => 'Hulu Sungai Tengah',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '71313',
            ),
            349 => 
            array (
                'id_city' => 350,
                'id_province' => 63,
                'city_name' => 'Hulu Sungai Utara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '71419',
            ),
            350 => 
            array (
                'id_city' => 351,
                'id_province' => 63,
                'city_name' => 'Tabalong',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '71513',
            ),
            351 => 
            array (
                'id_city' => 352,
                'id_province' => 63,
                'city_name' => 'Tanah Bumbu',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '72211',
            ),
            352 => 
            array (
                'id_city' => 353,
                'id_province' => 63,
                'city_name' => 'Balangan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '71611',
            ),
            353 => 
            array (
                'id_city' => 354,
                'id_province' => 63,
                'city_name' => 'Banjarmasin',
                'city_type' => 'Kota',
                'city_postal_code' => '70117',
            ),
            354 => 
            array (
                'id_city' => 355,
                'id_province' => 63,
                'city_name' => 'Banjarbaru',
                'city_type' => 'Kota',
                'city_postal_code' => '70712',
            ),
            
            //kaltim
            354 => 
            array (
                'id_city' => 355,
                'id_province' => 64,
                'city_name' => 'Paser',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '76211',
            ),
            355 => 
            array (
                'id_city' => 356,
                'id_province' => 64,
                'city_name' => 'Kutai Kartanegara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '75511',
            ),
            356 => 
            array (
                'id_city' => 357,
                'id_province' => 64,
                'city_name' => 'Berau',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '77311',
            ),
            357 => 
            array (
                'id_city' => 358,
                'id_province' => 64,
                'city_name' => 'Kutai Barat',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '75711',
            ),
            358 => 
            array (
                'id_city' => 359,
                'id_province' => 64,
                'city_name' => 'Kutai Timur',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '75611',
            ),
            359 => 
            array (
                'id_city' => 360,
                'id_province' => 64,
                'city_name' => 'Penajam Paser Utara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '76311',
            ),
            360 => 
            array (
                'id_city' => 361,
                'id_province' => 64,
                'city_name' => 'Mahakam Ulu',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '75767',
            ),
            361=> 
            array (
                'id_city' => 362,
                'id_province' => 64,
                'city_name' => 'Balikpapan',
                'city_type' => 'Kota',
                'city_postal_code' => '76111',
            ),
            362=> 
            array (
                'id_city' => 363,
                'id_province' => 64,
                'city_name' => 'Samarinda',
                'city_type' => 'Kota',
                'city_postal_code' => '75133',
            ),
            363=> 
            array (
                'id_city' => 364,
                'id_province' => 64,
                'city_name' => 'Bontang',
                'city_type' => 'Kota',
                'city_postal_code' => '75313',
            ),
            
            //Kalimantan Utara
            364 => 
            array (
                'id_city' => 365,
                'id_province' => 65,
                'city_name' => 'Bulungan (Bulongan)',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '77211',
            ),
            365 => 
            array (
                'id_city' => 366,
                'id_province' => 65,
                'city_name' => 'Malinau',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '77511',
            ),
            366 => 
            array (
                'id_city' => 367,
                'id_province' => 65,
                'city_name' => 'Nunukan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '77421',
            ),
            367 => 
            array (
                'id_city' => 368,
                'id_province' => 65,
                'city_name' => 'Tana Tidung',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '77611',
            ),
            368 => 
            array (
                'id_city' => 369,
                'id_province' => 65,
                'city_name' => 'Tarakan',
                'city_type' => 'Kota',
                'city_postal_code' => '77114',
            ),
            
            //Sulut
            369 => 
            array (
                'id_city' => 370,
                'id_province' => 71,
                'city_name' => 'Bolaang Mongondow (Bolmong)',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '95755',
            ),
            370 => 
            array (
                'id_city' => 371,
                'id_province' => 71,
                'city_name' => 'Minahasa',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '95614',
            ),
            371 => 
            array (
                'id_city' => 372,
                'id_province' => 71,
                'city_name' => 'Kepulauan Sangihe',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '95819',
            ),
            372 => 
            array (
                'id_city' => 373,
                'id_province' => 71,
                'city_name' => 'Kepulauan Talaud',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '95885',
            ),
            373 => 
            array (
                'id_city' => 374,
                'id_province' => 71,
                'city_name' => 'Minahasa Selatan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '95914',
            ),
            374 => 
            array (
                'id_city' => 375,
                'id_province' => 71,
                'city_name' => 'Minahasa Utara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '95316',
            ),
            375 => 
            array (
                'id_city' => 376,
                'id_province' => 71,
                'city_name' => 'Minahasa Tenggara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '95995',
            ),
            376 => 
            array (
                'id_city' => 377,
                'id_province' => 71,
                'city_name' => 'Bolaang Mongondow Utara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '95765',
            ),
            377 => 
            array (
                'id_city' => 378,
                'id_province' => 71,
                'city_name' => 'Kepulauan Siau Tagulandang Biaro (Sitaro)',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '95862',
            ),
            378 => 
            array (
                'id_city' => 379,
                'id_province' => 71,
                'city_name' => 'Bolaang Mongondow Timur',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '95783',
            ),
            379 => 
            array (
                'id_city' => 380,
                'id_province' => 71,
                'city_name' => 'Bolaang Mongondow Selatan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '95774',
            ),
            380 => 
            array (
                'id_city' => 381,
                'id_province' => 71,
                'city_name' => 'Manado',
                'city_type' => 'Kota',
                'city_postal_code' => '95247',
            ),
            381 => 
            array (
                'id_city' => 382,
                'id_province' => 71,
                'city_name' => 'Bitung',
                'city_type' => 'Kota',
                'city_postal_code' => '95512',
            ),
            382 => 
            array (
                'id_city' => 383,
                'id_province' => 71,
                'city_name' => 'Tomohon',
                'city_type' => 'Kota',
                'city_postal_code' => '95416',
            ),
            383 => 
            array (
                'id_city' => 384,
                'id_province' => 71,
                'city_name' => 'Kotamobagu',
                'city_type' => 'Kota',
                'city_postal_code' => '95711',
            ),
            
            //Sulawesi Tengah
            384 => 
             array (
                'id_city' => 385,
                'id_province' => 72,
                'city_name' => 'Banggai',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '94711',
             ),
            385 => 
             array (
                'id_city' => 386,
                'id_province' => 72,
                'city_name' => 'Poso',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '94615',
             ),
            386 => 
             array (
                'id_city' => 387,
                'id_province' => 72,
                'city_name' => 'Donggala',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '94341',
             ),
            387 => 
             array (
                'id_city' => 388,
                'id_province' => 72,
                'city_name' => 'Toli-Toli',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '94542',
             ),
            388 => 
             array (
                'id_city' => 389,
                'id_province' => 72,
                'city_name' => 'Buol',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '94564',
             ),
            389 => 
             array (
                'id_city' => 390,
                'id_province' => 72,
                'city_name' => 'Morowali',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '94911',
             ),
            390 => 
             array (
                'id_city' => 391,
                'id_province' => 72,
                'city_name' => 'Banggai Kepulauan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '94881',
             ),
            391 => 
             array (
                'id_city' => 392,
                'id_province' => 72,
                'city_name' => 'Parigi Moutong',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '94411',
             ),
            392 => 
             array (
                'id_city' => 393,
                'id_province' => 72,
                'city_name' => 'Tojo Una-Una',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '94683',
             ),
            393 => 
             array (
                'id_city' => 394,
                'id_province' => 72,
                'city_name' => 'Sigi',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '94364',
             ),
            394 => 
             array (
                'id_city' => 395,
                'id_province' => 72,
                'city_name' => 'Banggai Laut',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '94891',
             ),
            395 => 
             array (
                'id_city' => 396,
                'id_province' => 72,
                'city_name' => 'Morowali Utara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '94963',
             ),
            396 => 
             array (
                'id_city' => 397,
                'id_province' => 72,
                'city_name' => 'Palu',
                'city_type' => 'Kota',
                'city_postal_code' => '94111',
             ),
            
            //sulawesi selatan
            397 => 
             array (
                'id_city' => 398,
                'id_province' => 73,
                'city_name' => 'Selayar (Kepulauan Selayar)',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '92812',
             ),
            398 => 
             array (
                'id_city' => 399,
                'id_province' => 73,
                'city_name' => 'Bulukumba',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '92511',
             ),
            399 => 
             array (
                'id_city' => 400,
                'id_province' => 73,
                'city_name' => 'Bantaeng',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '92411',
             ),
            400 => 
             array (
                'id_city' => 401,
                'id_province' => 73,
                'city_name' => 'Jeneponto',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '92319',
             ),
            401 => 
             array (
                'id_city' => 402,
                'id_province' => 73,
                'city_name' => 'Takalar',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '92212',
             ),
            402 => 
             array (
                'id_city' => 403,
                'id_province' => 73,
                'city_name' => 'Gowa',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '92111',
             ),
            403 => 
             array (
                'id_city' => 404,
                'id_province' => 73,
                'city_name' => 'Sinjai',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '92615',
             ),
            404 => 
             array (
                'id_city' => 405,
                'id_province' => 73,
                'city_name' => 'Bone',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '92713',
             ),
            405 => 
             array (
                'id_city' => 406,
                'id_province' => 73,
                'city_name' => 'Maros',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '90511',
             ),
            406 => 
             array (
                'id_city' => 407,
                'id_province' => 73,
                'city_name' => 'Pangkajene Kepulauan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '90611',
             ),
            407 => 
             array (
                'id_city' => 408,
                'id_province' => 73,
                'city_name' => 'Barru',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '90719',
             ),
            408 => 
             array (
                'id_city' => 409,
                'id_province' => 73,
                'city_name' => 'Soppeng',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '90812',
             ),
            409 => 
             array (
                'id_city' => 410,
                'id_province' => 73,
                'city_name' => 'Wajo',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '90911',
             ),
            410 => 
             array (
                'id_city' => 411,
                'id_province' => 73,
                'city_name' => 'Sidenreng Rappang/Rapang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '91613',
             ),
            411 => 
             array (
                'id_city' => 412,
                'id_province' => 73,
                'city_name' => 'Pinrang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '91251'
             ),
            412 => 
             array (
                'id_city' => 413,
                'id_province' => 73,
                'city_name' => 'Enrekang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '91719',
             ),
            413 => 
             array (
                'id_city' => 414,
                'id_province' => 73,
                'city_name' => 'Luwu',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '91994',
             ),
            414 => 
             array (
                'id_city' => 415,
                'id_province' => 73,
                'city_name' => 'Tana Toraja',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '91819',
             ),
            415 => 
             array (
                'id_city' => 416,
                'id_province' => 73,
                'city_name' => 'Luwu Utara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '92911',
             ),
            416 => 
             array (
                'id_city' => 417,
                'id_province' => 73,
                'city_name' => 'Luwu Timur',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '92981',
             ),
            417 => 
             array (
                'id_city' => 418,
                'id_province' => 73,
                'city_name' => 'Toraja Utara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '91831',
             ),
            418 => 
             array (
                'id_city' => 419,
                'id_province' => 73,
                'city_name' => 'Makassar',
                'city_type' => 'Kota',
                'city_postal_code' => '90111',
             ),
            419 => 
             array (
                'id_city' => 420,
                'id_province' => 73,
                'city_name' => 'Pare Pare',
                'city_type' => 'Kota',
                'city_postal_code' => '91123',
             ),
            420 => 
             array (
                'id_city' => 421,
                'id_province' => 73,
                'city_name' => 'Palopo',
                'city_type' => 'Kota',
                'city_postal_code' => '91911',
             ),
            //Sulawesi Tenggara
             421 => 
             array (
                'id_city' => 422,
                'id_province' => 74,
                'city_name' => 'Kolaka',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '93511',
             ),
             422 => 
             array (
                'id_city' => 423,
                'id_province' => 74,
                'city_name' => 'Konawe',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '93411',
             ),
             423 => 
             array (
                'id_city' => 424,
                'id_province' => 74,
                'city_name' => 'Muna',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '93611',
             ),
             424 => 
             array (
                'id_city' => 425,
                'id_province' => 74,
                'city_name' => 'Buton',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '93754',
             ),
             425 => 
             array (
                'id_city' => 426,
                'id_province' => 74,
                'city_name' => 'Konawe Selatan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '93811',
             ),
             426 => 
             array (
                'id_city' => 427,
                'id_province' => 74,
                'city_name' => 'Bombana',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '93771',
             ),
             427 => 
             array (
                'id_city' => 428,
                'id_province' => 74,
                'city_name' => 'Wakatobi',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '93791',
             ),
             428 => 
             array (
                'id_city' => 429,
                'id_province' => 74,
                'city_name' => 'Kolaka Utara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '93911',
             ),
             429 => 
             array (
                'id_city' => 430,
                'id_province' => 74,
                'city_name' => 'Konawe Utara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '93311',
             ),
             430 => 
             array (
                'id_city' => 431,
                'id_province' => 74,
                'city_name' => 'Buton Utara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '93745',
             ),
             431 => 
             array (
                'id_city' => 432,
                'id_province' => 74,
                'city_name' => 'Kolaka Timur',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '93574',
             ),
             432 => 
             array (
                'id_city' => 433,
                'id_province' => 74,
                'city_name' => 'Konawe Kepulauan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '93393',
             ),
             433 => 
             array (
                'id_city' => 434,
                'id_province' => 74,
                'city_name' => 'Muna Barat',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '93643',
             ),
             434 => 
             array (
                'id_city' => 435,
                'id_province' => 74,
                'city_name' => 'Buton Tengah',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '93764',
             ),
             435 => 
             array (
                'id_city' => 436,
                'id_province' => 74,
                'city_name' => 'Buton Selatan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '93743',
             ),
             436 => 
             array (
                'id_city' => 437,
                'id_province' => 74,
                'city_name' => 'Kendari',
                'city_type' => 'Kota',
                'city_postal_code' => '93126',
             ),
             437 => 
             array (
                'id_city' => 438,
                'id_province' => 74,
                'city_name' => 'Bau Bau',
                'city_type' => 'Kota',
                'city_postal_code' => '93719',
             ),
            //Gorontalo
             438 => 
             array (
                'id_city' => 439,
                'id_province' => 75,
                'city_name' => 'Gorontalo',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '96218',
             ),
             439 => 
             array (
                'id_city' => 440,
                'id_province' => 75,
                'city_name' => 'Boalemo',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '96319',
             ),
             440 => 
             array (
                'id_city' => 441,
                'id_province' => 75,
                'city_name' => 'Bone Bolango',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '96511',
             ),
             441 => 
             array (
                'id_city' => 442,
                'id_province' => 75,
                'city_name' => 'Pohuwato',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '96419',
             ),
             442 => 
             array (
                'id_city' => 443,
                'id_province' => 75,
                'city_name' => 'Gorontalo Utara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '96611',
             ),
             443 => 
             array (
                'id_city' => 444,
                'id_province' => 75,
                'city_name' => 'Gorontalo',
                'city_type' => 'Kota',
                'city_postal_code' => '96115',
             ),
            
            //Sulawesi Barat
            444 => 
             array (
                'id_city' => 445,
                'id_province' => 76,
                'city_name' => 'Mamuju Utara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '91571',
             ),
            445 => 
             array (
                'id_city' => 446,
                'id_province' => 76,
                'city_name' => 'Mamuju',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '91519',
             ),
            446 => 
             array (
                'id_city' => 447,
                'id_province' => 76,
                'city_name' => 'Mamasa',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '91362',
             ),
            447 => 
             array (
                'id_city' => 448,
                'id_province' => 76,
                'city_name' => 'Polewali Mandar',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '91311',
             ),
            448 => 
             array (
                'id_city' => 449,
                'id_province' => 76,
                'city_name' => 'Majene',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '91411',
             ),
            449 => 
             array (
                'id_city' => 450,
                'id_province' => 76,
                'city_name' => 'Mamuju Tengah',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '91460',
             ),
            
            //Maluku 
             450 => 
             array (
                'id_city' => 451,
                'id_province' => 81,
                'city_name' => 'Maluku Tengah',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '97513',
             ),
             451 => 
             array (
                'id_city' => 452,
                'id_province' => 81,
                'city_name' => 'Maluku Tenggara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '97651',
             ),
             452 => 
             array (
                'id_city' => 453,
                'id_province' => 81,
                'city_name' => 'Maluku Tenggara Barat',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '97465',
             ),
             453 => 
             array (
                'id_city' => 454,
                'id_province' => 81,
                'city_name' => 'Buru',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '97371',
             ),
             454 => 
             array (
                'id_city' => 455,
                'id_province' => 81,
                'city_name' => 'Seram Bagian Timur',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '97581',
             ),
             455 => 
             array (
                'id_city' => 456,
                'id_province' => 81,
                'city_name' => 'Seram Bagian Barat',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '97561',
             ),
             456 => 
             array (
                'id_city' => 457,
                'id_province' => 81,
                'city_name' => 'Kepulauan Aru',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '97681',
             ),
             457 => 
             array (
                'id_city' => 458,
                'id_province' => 81,
                'city_name' => 'Maluku Barat Daya',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '97451',
             ),
             458 => 
             array (
                'id_city' => 459,
                'id_province' => 81,
                'city_name' => 'Buru Selatan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '97351',
             ),
             460 => 
             array (
                'id_city' => 461,
                'id_province' => 81,
                'city_name' => 'Ambon',
                'city_type' => 'Kota',
                'city_postal_code' => '97222',
             ),
             461 => 
             array (
                'id_city' => 462,
                'id_province' => 81,
                'city_name' => 'Tual',
                'city_type' => 'Kota',
                'city_postal_code' => '97612',
             ),
            //Maluku Utara
            462 => 
             array (
                'id_city' => 463,
                'id_province' => 82,
                'city_name' => 'Halmahera Barat',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '97757',
             ),
            463 => 
             array (
                'id_city' => 464,
                'id_province' => 82,
                'city_name' => 'Halmahera Tengah',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '97853',
             ),
            464 => 
             array (
                'id_city' => 465,
                'id_province' => 82,
                'city_name' => 'Halmahera Utara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '97762',
             ),
            465 => 
             array (
                'id_city' => 466,
                'id_province' => 82,
                'city_name' => 'Halmahera Selatan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '97911',
             ),
            466 => 
             array (
                'id_city' => 467,
                'id_province' => 82,
                'city_name' => 'Kepulauan Sula',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '97995',
             ),
            467 => 
             array (
                'id_city' => 468,
                'id_province' => 82,
                'city_name' => 'Halmahera Timur',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '97862',
             ),
            468 => 
             array (
                'id_city' => 469,
                'id_province' => 82,
                'city_name' => 'Pulau Morotai',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '97771',
             ),
            469 => 
             array (
                'id_city' => 470,
                'id_province' => 82,
                'city_name' => 'Pulau Taliabu',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '97794',
             ),
            470 => 
             array (
                'id_city' => 471,
                'id_province' => 82,
                'city_name' => 'Ternate',
                'city_type' => 'Kota',
                'city_postal_code' => '97714',
             ),
            471 => 
             array (
                'id_city' => 472,
                'id_province' => 82,
                'city_name' => 'Tidore Kepulauan',
                'city_type' => 'Kota',
                'city_postal_code' => '97815',
             ),
            
            //Papua
            472 => 
             array (
                'id_city' => 473,
                'id_province' => 91,
                'city_name' => 'Merauke',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '99613',
             ),
            472 => 
             array (
                'id_city' => 473,
                'id_province' => 91,
                'city_name' => 'Jayawijaya',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '99511',
             ),
            473 => 
             array (
                'id_city' => 474,
                'id_province' => 91,
                'city_name' => 'Jayapura',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '99352',
             ),
            474 => 
             array (
                'id_city' => 475,
                'id_province' => 91,
                'city_name' => 'Nabire',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '98816',
             ),
            475 => 
             array (
                'id_city' => 476,
                'id_province' => 91,
                'city_name' => 'Kepulauan Yapen (Yapen Waropen)',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '98211',
             ),
            476 => 
             array (
                'id_city' => 477,
                'id_province' => 91,
                'city_name' => 'Biak Numfor',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '98119',
             ),
            477 => 
             array (
                'id_city' => 478,
                'id_province' => 91,
                'city_name' => 'Puncak Jaya',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '98979',
             ),
            478 => 
             array (
                'id_city' => 479,
                'id_province' => 91,
                'city_name' => 'Paniai',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '98765',
             ),
            479 => 
             array (
                'id_city' => 480,
                'id_province' => 91,
                'city_name' => 'Mimika',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '99962',
             ),
            480 => 
             array (
                'id_city' => 481,
                'id_province' => 91,
                'city_name' => 'Sarmi',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '99373',
             ),
            481 => 
             array (
                'id_city' => 482,
                'id_province' => 91,
                'city_name' => 'Keerom',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '99461',
             ),
            482 => 
             array (
                'id_city' => 483,
                'id_province' => 91,
                'city_name' => 'Pegunungan Bintang',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '99573',
             ),
            483 => 
             array (
                'id_city' => 484,
                'id_province' => 91,
                'city_name' => 'Yahukimo',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '99041',
             ),
            484 => 
             array (
                'id_city' => 485,
                'id_province' => 91,
                'city_name' => 'Tolikara',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '99411',
             ),
            485 => 
             array (
                'id_city' => 486,
                'id_province' => 91,
                'city_name' => 'Waropen',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '98269',
             ),
            486 => 
             array (
                'id_city' => 487,
                'id_province' => 91,
                'city_name' => 'Boven Digoel',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '99662',
             ),
            487 => 
             array (
                'id_city' => 488,
                'id_province' => 91,
                'city_name' => 'Mappi',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '99853',
             ),
            488 => 
             array (
                'id_city' => 489,
                'id_province' => 91,
                'city_name' => 'Asmat',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '99777',
             ),
            489 => 
             array (
                'id_city' => 490,
                'id_province' => 91,
                'city_name' => 'Supiori',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '98164',
             ),
            490 => 
             array (
                'id_city' => 491,
                'id_province' => 91,
                'city_name' => 'Mamberamo Raya',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '99381',
             ),
            491 => 
             array (
                'id_city' => 492,
                'id_province' => 91,
                'city_name' => 'Mamberamo Tengah',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '99553',
             ),
            492 => 
             array (
                'id_city' => 493,
                'id_province' => 91,
                'city_name' => 'Yalimo',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '99481',
             ),
            492 => 
             array (
                'id_city' => 493,
                'id_province' => 91,
                'city_name' => 'Yalimo',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '99481',
             ),
            493 => 
             array (
                'id_city' => 494,
                'id_province' => 91,
                'city_name' => 'Lanny Jaya',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '99531',
             ),
            494 => 
             array (
                'id_city' => 495,
                'id_province' => 91,
                'city_name' => 'Nduga',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '99541',
             ),
            495 => 
             array (
                'id_city' => 496,
                'id_province' => 91,
                'city_name' => 'Puncak',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '98981',
             ),
            496 => 
             array (
                'id_city' => 497,
                'id_province' => 91,
                'city_name' => 'Dogiyai',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '98866',
             ),
            497 => 
             array (
                'id_city' => 498,
                'id_province' => 91,
                'city_name' => 'Intan Jaya',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '98771',
             ),
            498 => 
             array (
                'id_city' => 499,
                'id_province' => 91,
                'city_name' => 'Deiyai (Deliyai)',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '98784',
             ),
            499 => 
             array (
                'id_city' => 500,
                'id_province' => 91,
                'city_name' => 'Jayapura',
                'city_type' => 'Kota',
                'city_postal_code' => '99114',
             ),
            
            //Papua Barat
            500 => 
             array (
                'id_city' => 501,
                'id_province' => 92,
                'city_name' => 'Sorong',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '98431',
             ),
            501 => 
             array (
                'id_city' => 502,
                'id_province' => 92,
                'city_name' => 'Manokwari',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '98311',
             ),
            502 => 
             array (
                'id_city' => 503,
                'id_province' => 92,
                'city_name' => 'Fak Fak',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '98651',
             ),
            503 => 
             array (
                'id_city' => 504,
                'id_province' => 92,
                'city_name' => 'Sorong Selatan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '98454',
             ),
            504 => 
             array (
                'id_city' => 505,
                'id_province' => 92,
                'city_name' => 'Raja Ampat',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '98489',
             ),
            505 => 
             array (
                'id_city' => 506,
                'id_province' => 92,
                'city_name' => 'Teluk Bintuni',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '98551',
             ),
            506 => 
             array (
                'id_city' => 507,
                'id_province' => 92,
                'city_name' => 'Teluk Wondama',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '98591',
             ),
            507 => 
             array (
                'id_city' => 508,
                'id_province' => 92,
                'city_name' => 'Kaimana',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '98671',
             ),
            508 => 
             array (
                'id_city' => 509,
                'id_province' => 92,
                'city_name' => 'Tambrauw',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '98475',
             ),
            509 => 
             array (
                'id_city' => 510,
                'id_province' => 92,
                'city_name' => 'Maybrat',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '98051',
             ),
            510 => 
             array (
                'id_city' => 511,
                'id_province' => 92,
                'city_name' => 'Manokwari Selatan',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '98355',
             ),
            511 => 
             array (
                'id_city' => 512,
                'id_province' => 92,
                'city_name' => 'Pegunungan Arfak',
                'city_type' => 'Kabupaten',
                'city_postal_code' => '98354',
             ),
            512 => 
             array (
                'id_city' => 513,
                'id_province' => 92,
                'city_name' => 'Sorong',
                'city_type' => 'Kota',
                'city_postal_code' => '98411',
             ),
        ));
     
        
        
    }
}