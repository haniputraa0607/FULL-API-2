<?php

use Illuminate\Database\Seeder;

class BankNameTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('bank_name')->delete();
        
        \DB::table('bank_name')->insert(array (
            0 => 
            array (
                'bank_code' => 'aceh',
                'bank_name' => 'PT. BANK ACEH',
                'created_at' => NULL,
                'id_bank_name' => 1,
                'updated_at' => NULL,
            ),
            1 => 
            array (
                'bank_code' => 'aceh_syar',
                'bank_name' => 'PT. BPD ISTIMEWA ACEH SYARIAH',
                'created_at' => NULL,
                'id_bank_name' => 2,
                'updated_at' => NULL,
            ),
            2 => 
            array (
                'bank_code' => 'agris',
                'bank_name' => 'PT. BANK AGRIS',
                'created_at' => NULL,
                'id_bank_name' => 3,
                'updated_at' => NULL,
            ),
            3 => 
            array (
                'bank_code' => 'agroniaga',
                'bank_name' => 'PT. BANK RAKYAT INDONESIA AGRONIAGA TBK.',
                'created_at' => NULL,
                'id_bank_name' => 4,
                'updated_at' => NULL,
            ),
            4 => 
            array (
                'bank_code' => 'amar',
                'bank_name' => 'PT. BANK AMAR INDONESIA',
                'created_at' => NULL,
                'id_bank_name' => 5,
                'updated_at' => NULL,
            ),
            5 => 
            array (
                'bank_code' => 'andara',
                'bank_name' => 'PT. BANK ANDARA',
                'created_at' => NULL,
                'id_bank_name' => 6,
                'updated_at' => NULL,
            ),
            6 => 
            array (
                'bank_code' => 'anglomas',
                'bank_name' => 'PT. ANGLOMAS INTERNATIONAL BANK',
                'created_at' => NULL,
                'id_bank_name' => 7,
                'updated_at' => NULL,
            ),
            7 => 
            array (
                'bank_code' => 'antar_daerah',
                'bank_name' => 'PT. BANK ANTAR DAERAH',
                'created_at' => NULL,
                'id_bank_name' => 8,
                'updated_at' => NULL,
            ),
            8 => 
            array (
                'bank_code' => 'anz',
                'bank_name' => 'PT. BANK ANZ INDONESIA',
                'created_at' => NULL,
                'id_bank_name' => 9,
                'updated_at' => NULL,
            ),
            9 => 
            array (
                'bank_code' => 'artajasa',
                'bank_name' => 'PT. ARTAJASA PEMBAYARAN ELEKTRONIK',
                'created_at' => NULL,
                'id_bank_name' => 10,
                'updated_at' => NULL,
            ),
            10 => 
            array (
                'bank_code' => 'artha',
                'bank_name' => 'PT. BANK ARTHA GRAHA INTERNASIONAL TBK.',
                'created_at' => NULL,
                'id_bank_name' => 11,
                'updated_at' => NULL,
            ),
            11 => 
            array (
                'bank_code' => 'artos',
                'bank_name' => 'PT. BANK ARTOS INDONESIA',
                'created_at' => NULL,
                'id_bank_name' => 12,
                'updated_at' => NULL,
            ),
            12 => 
            array (
                'bank_code' => 'bali',
                'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH BALI',
                'created_at' => NULL,
                'id_bank_name' => 13,
                'updated_at' => NULL,
            ),
            13 => 
            array (
                'bank_code' => 'bangkok',
                'bank_name' => 'BANGKOK BANK PUBLIC CO.LTD',
                'created_at' => NULL,
                'id_bank_name' => 14,
                'updated_at' => NULL,
            ),
            14 => 
            array (
                'bank_code' => 'banten',
                'bank_name' => 'PT. BANK BANTEN',
                'created_at' => NULL,
                'id_bank_name' => 15,
                'updated_at' => NULL,
            ),
            15 => 
            array (
                'bank_code' => 'barclays',
                'bank_name' => 'PT BANK BARCLAYS INDONESIA',
                'created_at' => NULL,
                'id_bank_name' => 16,
                'updated_at' => NULL,
            ),
            16 => 
            array (
                'bank_code' => 'bca',
                'bank_name' => 'PT. BANK CENTRAL ASIA TBK.',
                'created_at' => NULL,
                'id_bank_name' => 17,
                'updated_at' => NULL,
            ),
            17 => 
            array (
                'bank_code' => 'bca_syar',
                'bank_name' => 'PT. BANK BCA SYARIAH',
                'created_at' => NULL,
                'id_bank_name' => 18,
                'updated_at' => NULL,
            ),
            18 => 
            array (
                'bank_code' => 'bengkulu',
                'bank_name' => 'PT. BPD BENGKULU',
                'created_at' => NULL,
                'id_bank_name' => 19,
                'updated_at' => NULL,
            ),
            19 => 
            array (
                'bank_code' => 'bisnis',
                'bank_name' => 'PT. BANK BISNIS INTERNASIONAL',
                'created_at' => NULL,
                'id_bank_name' => 20,
                'updated_at' => NULL,
            ),
            20 => 
            array (
                'bank_code' => 'bjb',
                'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH JABAR DAN BANTEN',
                'created_at' => NULL,
                'id_bank_name' => 21,
                'updated_at' => NULL,
            ),
            21 => 
            array (
                'bank_code' => 'bjb_syar',
                'bank_name' => 'PT. BANK JABAR BANTEN SYARIAH',
                'created_at' => NULL,
                'id_bank_name' => 22,
                'updated_at' => NULL,
            ),
            22 => 
            array (
                'bank_code' => 'bni',
            'bank_name' => 'PT. BANK NEGARA INDONESIA (PERSERO)',
                'created_at' => NULL,
                'id_bank_name' => 23,
                'updated_at' => NULL,
            ),
            23 => 
            array (
                'bank_code' => 'bni_syar',
                'bank_name' => 'PT. BANK BNI SYARIAH',
                'created_at' => NULL,
                'id_bank_name' => 24,
                'updated_at' => NULL,
            ),
            24 => 
            array (
                'bank_code' => 'bnp',
                'bank_name' => 'PT. BANK NUSANTARA PARAHYANGAN',
                'created_at' => NULL,
                'id_bank_name' => 25,
                'updated_at' => NULL,
            ),
            25 => 
            array (
                'bank_code' => 'bnp_paribas',
                'bank_name' => 'PT. BANK BNP PARIBAS INDONESIA',
                'created_at' => NULL,
                'id_bank_name' => 26,
                'updated_at' => NULL,
            ),
            26 => 
            array (
                'bank_code' => 'boa',
                'bank_name' => 'BANK OF AMERICA NA',
                'created_at' => NULL,
                'id_bank_name' => 27,
                'updated_at' => NULL,
            ),
            27 => 
            array (
                'bank_code' => 'bri',
            'bank_name' => 'PT. BANK RAKYAT INDONESIA (PERSERO)',
                'created_at' => NULL,
                'id_bank_name' => 28,
                'updated_at' => NULL,
            ),
            28 => 
            array (
                'bank_code' => 'bri_syar',
                'bank_name' => 'PT. BANK SYARIAH BRI',
                'created_at' => NULL,
                'id_bank_name' => 29,
                'updated_at' => NULL,
            ),
            29 => 
            array (
                'bank_code' => 'btn',
            'bank_name' => 'PT. BANK TABUNGAN NEGARA (PERSERO)',
                'created_at' => NULL,
                'id_bank_name' => 30,
                'updated_at' => NULL,
            ),
            30 => 
            array (
                'bank_code' => 'btn_syar',
            'bank_name' => 'PT. BANK TABUNGAN NEGARA (PERSERO) UNIT USAHA SYARIAH',
                'created_at' => NULL,
                'id_bank_name' => 31,
                'updated_at' => NULL,
            ),
            31 => 
            array (
                'bank_code' => 'btpn',
                'bank_name' => 'PT. BANK TABUNGAN PENSIUNAN NASIONAL',
                'created_at' => NULL,
                'id_bank_name' => 32,
                'updated_at' => NULL,
            ),
            32 => 
            array (
                'bank_code' => 'btpn_syar',
                'bank_name' => 'PT. BANK TABUNGAN PENSIUNAN NASIONAL SYARIAH',
                'created_at' => NULL,
                'id_bank_name' => 33,
                'updated_at' => NULL,
            ),
            33 => 
            array (
                'bank_code' => 'bukopin',
                'bank_name' => 'PT. BANK BUKOPIN TBK.',
                'created_at' => NULL,
                'id_bank_name' => 34,
                'updated_at' => NULL,
            ),
            34 => 
            array (
                'bank_code' => 'bukopin_syar',
                'bank_name' => 'PT. BANK SYARIAH BUKOPIN',
                'created_at' => NULL,
                'id_bank_name' => 35,
                'updated_at' => NULL,
            ),
            35 => 
            array (
                'bank_code' => 'bumi_artha',
                'bank_name' => 'PT. BANK BUMI ARTA',
                'created_at' => NULL,
                'id_bank_name' => 36,
                'updated_at' => NULL,
            ),
            36 => 
            array (
                'bank_code' => 'bumiputera',
                'bank_name' => 'PT. BANK BUMIPUTERA',
                'created_at' => NULL,
                'id_bank_name' => 37,
                'updated_at' => NULL,
            ),
            37 => 
            array (
                'bank_code' => 'capital',
                'bank_name' => 'PT. BANK CAPITAL INDONESIA',
                'created_at' => NULL,
                'id_bank_name' => 38,
                'updated_at' => NULL,
            ),
            38 => 
            array (
                'bank_code' => 'centratama',
                'bank_name' => 'PT. CENTRATAMA NASIONAL BANK',
                'created_at' => NULL,
                'id_bank_name' => 39,
                'updated_at' => NULL,
            ),
            39 => 
            array (
                'bank_code' => 'chase',
                'bank_name' => 'KC JPMORGAN CHASE BANK, N.A.',
                'created_at' => NULL,
                'id_bank_name' => 40,
                'updated_at' => NULL,
            ),
            40 => 
            array (
                'bank_code' => 'china',
                'bank_name' => 'KC BANK OF CHINA LIMITED',
                'created_at' => NULL,
                'id_bank_name' => 41,
                'updated_at' => NULL,
            ),
            41 => 
            array (
                'bank_code' => 'china_cons',
                'bank_name' => 'PT. BANK CHINA CONSTRUCTION BANK INDONESIA, TBK.',
                'created_at' => NULL,
                'id_bank_name' => 42,
                'updated_at' => NULL,
            ),
            42 => 
            array (
                'bank_code' => 'chinatrust',
                'bank_name' => 'PT. BANK CTBC INDONESIA',
                'created_at' => NULL,
                'id_bank_name' => 43,
                'updated_at' => NULL,
            ),
            43 => 
            array (
                'bank_code' => 'cimb',
                'bank_name' => 'PT. BANK CIMB NIAGA TBK.',
                'created_at' => NULL,
                'id_bank_name' => 44,
                'updated_at' => NULL,
            ),
            44 => 
            array (
                'bank_code' => 'cimb_rekening_ponsel',
                'bank_name' => 'PT. BANK CIMB NIAGA TBK. - REKENING PONSEL',
                'created_at' => NULL,
                'id_bank_name' => 45,
                'updated_at' => NULL,
            ),
            45 => 
            array (
                'bank_code' => 'cimb_syar',
                'bank_name' => 'PT. BANK CIMB NIAGA TBK. - UNIT USAHA SYARIAH',
                'created_at' => NULL,
                'id_bank_name' => 46,
                'updated_at' => NULL,
            ),
            46 => 
            array (
                'bank_code' => 'citibank',
                'bank_name' => 'CITIBANK',
                'created_at' => NULL,
                'id_bank_name' => 47,
                'updated_at' => NULL,
            ),
            47 => 
            array (
                'bank_code' => 'commonwealth',
                'bank_name' => 'PT. BANK COMMONWEALTH',
                'created_at' => NULL,
                'id_bank_name' => 48,
                'updated_at' => NULL,
            ),
            48 => 
            array (
                'bank_code' => 'danamon',
                'bank_name' => 'PT. BANK DANAMON INDONESIA TBK.',
                'created_at' => NULL,
                'id_bank_name' => 49,
                'updated_at' => NULL,
            ),
            49 => 
            array (
                'bank_code' => 'danamon_syar',
                'bank_name' => 'PT. BANK DANAMON INDONESIA UNIT USAHA SYARIAH',
                'created_at' => NULL,
                'id_bank_name' => 50,
                'updated_at' => NULL,
            ),
            50 => 
            array (
                'bank_code' => 'dbs',
                'bank_name' => 'PT. BANK DBS INDONESIA',
                'created_at' => NULL,
                'id_bank_name' => 51,
                'updated_at' => NULL,
            ),
            51 => 
            array (
                'bank_code' => 'deutsche',
                'bank_name' => 'DEUTSCHE BANK AG.',
                'created_at' => NULL,
                'id_bank_name' => 52,
                'updated_at' => NULL,
            ),
            52 => 
            array (
                'bank_code' => 'dinar',
                'bank_name' => 'PT. BANK DINAR INDONESIA',
                'created_at' => NULL,
                'id_bank_name' => 53,
                'updated_at' => NULL,
            ),
            53 => 
            array (
                'bank_code' => 'dipo',
                'bank_name' => 'PT. BANK DIPO INTERNATIONAL',
                'created_at' => NULL,
                'id_bank_name' => 54,
                'updated_at' => NULL,
            ),
            54 => 
            array (
                'bank_code' => 'diy',
                'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH DIY',
                'created_at' => NULL,
                'id_bank_name' => 55,
                'updated_at' => NULL,
            ),
            55 => 
            array (
                'bank_code' => 'diy_syar',
                'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH DIY UNIT USAHA SYARIAH',
                'created_at' => NULL,
                'id_bank_name' => 56,
                'updated_at' => NULL,
            ),
            56 => 
            array (
                'bank_code' => 'dki',
                'bank_name' => 'PT. BANK DKI',
                'created_at' => NULL,
                'id_bank_name' => 57,
                'updated_at' => NULL,
            ),
            57 => 
            array (
                'bank_code' => 'dki_syar',
                'bank_name' => 'PT. BANK DKI UNIT USAHA SYARIAH',
                'created_at' => NULL,
                'id_bank_name' => 58,
                'updated_at' => NULL,
            ),
            58 => 
            array (
                'bank_code' => 'ekonomi',
                'bank_name' => 'PT. BANK EKONOMI RAHARJA',
                'created_at' => NULL,
                'id_bank_name' => 59,
                'updated_at' => NULL,
            ),
            59 => 
            array (
                'bank_code' => 'fama',
                'bank_name' => 'PT. BANK FAMA INTERNATIONAL',
                'created_at' => NULL,
                'id_bank_name' => 60,
                'updated_at' => NULL,
            ),
            60 => 
            array (
                'bank_code' => 'ganesha',
                'bank_name' => 'PT. BANK GANESHA',
                'created_at' => NULL,
                'id_bank_name' => 61,
                'updated_at' => NULL,
            ),
            61 => 
            array (
                'bank_code' => 'hana',
                'bank_name' => 'PT. BANK KEB HANA INDONESIA',
                'created_at' => NULL,
                'id_bank_name' => 62,
                'updated_at' => NULL,
            ),
            62 => 
            array (
                'bank_code' => 'harda',
                'bank_name' => 'PT. BANK HARDA INTERNATIONAL',
                'created_at' => NULL,
                'id_bank_name' => 63,
                'updated_at' => NULL,
            ),
            63 => 
            array (
                'bank_code' => 'hs_1906',
                'bank_name' => 'PT. BANK HS 1906',
                'created_at' => NULL,
                'id_bank_name' => 64,
                'updated_at' => NULL,
            ),
            64 => 
            array (
                'bank_code' => 'hsbc',
                'bank_name' => 'PT. BANK HSBC INDONESIA',
                'created_at' => NULL,
                'id_bank_name' => 65,
                'updated_at' => NULL,
            ),
            65 => 
            array (
                'bank_code' => 'icbc',
                'bank_name' => 'PT. BANK ICBC INDONESIA',
                'created_at' => NULL,
                'id_bank_name' => 66,
                'updated_at' => NULL,
            ),
            66 => 
            array (
                'bank_code' => 'ina_perdana',
                'bank_name' => 'PT. BANK INA PERDANA',
                'created_at' => NULL,
                'id_bank_name' => 67,
                'updated_at' => NULL,
            ),
            67 => 
            array (
                'bank_code' => 'index_selindo',
                'bank_name' => 'PT. BANK INDEX SELINDO',
                'created_at' => NULL,
                'id_bank_name' => 68,
                'updated_at' => NULL,
            ),
            68 => 
            array (
                'bank_code' => 'india',
                'bank_name' => 'PT. BANK OF INDIA INDONESIA TBK.',
                'created_at' => NULL,
                'id_bank_name' => 69,
                'updated_at' => NULL,
            ),
            69 => 
            array (
                'bank_code' => 'jambi',
                'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH JAMBI',
                'created_at' => NULL,
                'id_bank_name' => 70,
                'updated_at' => NULL,
            ),
            70 => 
            array (
                'bank_code' => 'jasa_jakarta',
                'bank_name' => 'PT. BANK JASA JAKARTA',
                'created_at' => NULL,
                'id_bank_name' => 71,
                'updated_at' => NULL,
            ),
            71 => 
            array (
                'bank_code' => 'jateng',
                'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH JAWA TENGAH',
                'created_at' => NULL,
                'id_bank_name' => 72,
                'updated_at' => NULL,
            ),
            72 => 
            array (
                'bank_code' => 'jateng_syar',
                'bank_name' => 'PT. BPD JAWA TENGAH UNIT USAHA SYARIAH',
                'created_at' => NULL,
                'id_bank_name' => 73,
                'updated_at' => NULL,
            ),
            73 => 
            array (
                'bank_code' => 'jatim',
                'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH JATIM',
                'created_at' => NULL,
                'id_bank_name' => 74,
                'updated_at' => NULL,
            ),
            74 => 
            array (
                'bank_code' => 'jatim_syar',
                'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH JATIM - UNIT USAHA SYARIAH',
                'created_at' => NULL,
                'id_bank_name' => 75,
                'updated_at' => NULL,
            ),
            75 => 
            array (
                'bank_code' => 'jtrust',
                'bank_name' => 'PT. BANK JTRUST INDONESIA TBK.',
                'created_at' => NULL,
                'id_bank_name' => 76,
                'updated_at' => NULL,
            ),
            76 => 
            array (
                'bank_code' => 'kalbar',
                'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH KALBAR',
                'created_at' => NULL,
                'id_bank_name' => 77,
                'updated_at' => NULL,
            ),
            77 => 
            array (
                'bank_code' => 'kalbar_syar',
                'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH KALBAR - UNIT USAHA SYARIAH',
                'created_at' => NULL,
                'id_bank_name' => 78,
                'updated_at' => NULL,
            ),
            78 => 
            array (
                'bank_code' => 'kalsel',
                'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH KALSEL',
                'created_at' => NULL,
                'id_bank_name' => 79,
                'updated_at' => NULL,
            ),
            79 => 
            array (
                'bank_code' => 'kalsel_syar',
                'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH KALSEL - UNIT USAHA SYARIAH',
                'created_at' => NULL,
                'id_bank_name' => 80,
                'updated_at' => NULL,
            ),
            80 => 
            array (
                'bank_code' => 'kalteng',
                'bank_name' => 'PT. BPD KALIMANTAN TENGAH',
                'created_at' => NULL,
                'id_bank_name' => 81,
                'updated_at' => NULL,
            ),
            81 => 
            array (
                'bank_code' => 'kaltim',
                'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH KALTIM',
                'created_at' => NULL,
                'id_bank_name' => 82,
                'updated_at' => NULL,
            ),
            82 => 
            array (
                'bank_code' => 'kaltim_syar',
                'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH KALTIM - UNIT USAHA SYARIAH',
                'created_at' => NULL,
                'id_bank_name' => 83,
                'updated_at' => NULL,
            ),
            83 => 
            array (
                'bank_code' => 'kesejahteraan',
                'bank_name' => 'PT. BANK KESEJAHTERAAN EKONOMI',
                'created_at' => NULL,
                'id_bank_name' => 84,
                'updated_at' => NULL,
            ),
            84 => 
            array (
                'bank_code' => 'lampung',
                'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH LAMPUNG',
                'created_at' => NULL,
                'id_bank_name' => 85,
                'updated_at' => NULL,
            ),
            85 => 
            array (
                'bank_code' => 'maluku',
                'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH MALUKU',
                'created_at' => NULL,
                'id_bank_name' => 86,
                'updated_at' => NULL,
            ),
            86 => 
            array (
                'bank_code' => 'mandiri',
            'bank_name' => 'PT. BANK MANDIRI (PERSERO) TBK.',
                'created_at' => NULL,
                'id_bank_name' => 87,
                'updated_at' => NULL,
            ),
            87 => 
            array (
                'bank_code' => 'mandiri_syar',
                'bank_name' => 'PT. BANK SYARIAH MANDIRI TBK.',
                'created_at' => NULL,
                'id_bank_name' => 88,
                'updated_at' => NULL,
            ),
            88 => 
            array (
                'bank_code' => 'mandiri_taspen',
                'bank_name' => 'PT. BANK MANDIRI TASPEN POS',
                'created_at' => NULL,
                'id_bank_name' => 89,
                'updated_at' => NULL,
            ),
            89 => 
            array (
                'bank_code' => 'maspion',
                'bank_name' => 'PT. BANK MASPION INDONESIA',
                'created_at' => NULL,
                'id_bank_name' => 90,
                'updated_at' => NULL,
            ),
            90 => 
            array (
                'bank_code' => 'mayapada',
                'bank_name' => 'PT. BANK MAYAPADA INTERNASIONAL, TBK',
                'created_at' => NULL,
                'id_bank_name' => 91,
                'updated_at' => NULL,
            ),
            91 => 
            array (
                'bank_code' => 'maybank',
                'bank_name' => 'PT. BANK MAYBANK INDONESIA TBK.',
                'created_at' => NULL,
                'id_bank_name' => 92,
                'updated_at' => NULL,
            ),
            92 => 
            array (
                'bank_code' => 'maybank_syar',
                'bank_name' => 'PT. BANK MAYBANK SYARIAH INDONESIA',
                'created_at' => NULL,
                'id_bank_name' => 93,
                'updated_at' => NULL,
            ),
            93 => 
            array (
                'bank_code' => 'maybank_uus',
                'bank_name' => 'PT. BANK MAYBANK INDONESIA TBK. UNIT USAHA SYARIAH',
                'created_at' => NULL,
                'id_bank_name' => 94,
                'updated_at' => NULL,
            ),
            94 => 
            array (
                'bank_code' => 'mayora',
                'bank_name' => 'PT. BANK MAYORA',
                'created_at' => NULL,
                'id_bank_name' => 95,
                'updated_at' => NULL,
            ),
            95 => 
            array (
                'bank_code' => 'mega_syar',
                'bank_name' => 'PT. BANK MEGA SYARIAH',
                'created_at' => NULL,
                'id_bank_name' => 96,
                'updated_at' => NULL,
            ),
            96 => 
            array (
                'bank_code' => 'mega_tbk',
                'bank_name' => 'PT. BANK MEGA TBK.',
                'created_at' => NULL,
                'id_bank_name' => 97,
                'updated_at' => NULL,
            ),
            97 => 
            array (
                'bank_code' => 'mestika',
                'bank_name' => 'PT. BANK MESTIKA DHARMA',
                'created_at' => NULL,
                'id_bank_name' => 98,
                'updated_at' => NULL,
            ),
            98 => 
            array (
                'bank_code' => 'metro',
                'bank_name' => 'PT. BANK METRO EXPRESS',
                'created_at' => NULL,
                'id_bank_name' => 99,
                'updated_at' => NULL,
            ),
            99 => 
            array (
                'bank_code' => 'mitraniaga',
                'bank_name' => 'PT. BANK MITRANIAGA',
                'created_at' => NULL,
                'id_bank_name' => 100,
                'updated_at' => NULL,
            ),
            100 => 
            array (
                'bank_code' => 'mitsubishi',
                'bank_name' => 'THE BANK OF TOKYO MITSUBISHI UFJ LTD.',
                'created_at' => NULL,
                'id_bank_name' => 101,
                'updated_at' => NULL,
            ),
            101 => 
            array (
                'bank_code' => 'mizuho',
                'bank_name' => 'PT. BANK MIZUHO INDONESIA',
                'created_at' => NULL,
                'id_bank_name' => 102,
                'updated_at' => NULL,
            ),
            102 => 
            array (
                'bank_code' => 'mnc',
                'bank_name' => 'PT. BANK MNC INTERNASIONAL TBK.',
                'created_at' => NULL,
                'id_bank_name' => 103,
                'updated_at' => NULL,
            ),
            103 => 
            array (
                'bank_code' => 'muamalat',
                'bank_name' => 'PT. BANK MUAMALAT INDONESIA',
                'created_at' => NULL,
                'id_bank_name' => 104,
                'updated_at' => NULL,
            ),
            104 => 
            array (
                'bank_code' => 'multiarta',
                'bank_name' => 'PT. BANK MULTI ARTA SENTOSA',
                'created_at' => NULL,
                'id_bank_name' => 105,
                'updated_at' => NULL,
            ),
            105 => 
            array (
                'bank_code' => 'mutiara',
                'bank_name' => 'PT. BANK MUTIARA TBK.',
                'created_at' => NULL,
                'id_bank_name' => 106,
                'updated_at' => NULL,
            ),
            106 => 
            array (
                'bank_code' => 'nagari',
                'bank_name' => 'PT. BANK NAGARI',
                'created_at' => NULL,
                'id_bank_name' => 107,
                'updated_at' => NULL,
            ),
            107 => 
            array (
                'bank_code' => 'niaga_syar',
                'bank_name' => 'PT. BANK NIAGA TBK. SYARIAH',
                'created_at' => NULL,
                'id_bank_name' => 108,
                'updated_at' => NULL,
            ),
            108 => 
            array (
                'bank_code' => 'nobu',
                'bank_name' => 'PT. BANK NATIONALNOBU',
                'created_at' => NULL,
                'id_bank_name' => 109,
                'updated_at' => NULL,
            ),
            109 => 
            array (
                'bank_code' => 'ntb',
                'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH NTB',
                'created_at' => NULL,
                'id_bank_name' => 110,
                'updated_at' => NULL,
            ),
            110 => 
            array (
                'bank_code' => 'ntt',
                'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH NTT',
                'created_at' => NULL,
                'id_bank_name' => 111,
                'updated_at' => NULL,
            ),
            111 => 
            array (
                'bank_code' => 'ocbc',
                'bank_name' => 'PT. BANK OCBC NISP TBK.',
                'created_at' => NULL,
                'id_bank_name' => 112,
                'updated_at' => NULL,
            ),
            112 => 
            array (
                'bank_code' => 'ocbc_syar',
                'bank_name' => 'PT. BANK OCBC NISP TBK. - UNIT USAHA SYARIAH',
                'created_at' => NULL,
                'id_bank_name' => 113,
                'updated_at' => NULL,
            ),
            113 => 
            array (
                'bank_code' => 'panin',
                'bank_name' => 'PT. PANIN BANK TBK.',
                'created_at' => NULL,
                'id_bank_name' => 114,
                'updated_at' => NULL,
            ),
            114 => 
            array (
                'bank_code' => 'panin_syar',
                'bank_name' => 'PT. BANK PANIN SYARIAH',
                'created_at' => NULL,
                'id_bank_name' => 115,
                'updated_at' => NULL,
            ),
            115 => 
            array (
                'bank_code' => 'papua',
                'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH PAPUA',
                'created_at' => NULL,
                'id_bank_name' => 116,
                'updated_at' => NULL,
            ),
            116 => 
            array (
                'bank_code' => 'permata',
                'bank_name' => 'PT. BANK PERMATA TBK.',
                'created_at' => NULL,
                'id_bank_name' => 117,
                'updated_at' => NULL,
            ),
            117 => 
            array (
                'bank_code' => 'permata_syar',
                'bank_name' => 'PT. BANK PERMATA TBK. UNIT USAHA SYARIAH',
                'created_at' => NULL,
                'id_bank_name' => 118,
                'updated_at' => NULL,
            ),
            118 => 
            array (
                'bank_code' => 'prima_master',
                'bank_name' => 'PT. PRIMA MASTER BANK',
                'created_at' => NULL,
                'id_bank_name' => 119,
                'updated_at' => NULL,
            ),
            119 => 
            array (
                'bank_code' => 'pundi',
                'bank_name' => 'PT. BANK PUNDI INDONESIA',
                'created_at' => NULL,
                'id_bank_name' => 120,
                'updated_at' => NULL,
            ),
            120 => 
            array (
                'bank_code' => 'purba',
                'bank_name' => 'PT. BANK PURBA DANARTA',
                'created_at' => NULL,
                'id_bank_name' => 121,
                'updated_at' => NULL,
            ),
            121 => 
            array (
                'bank_code' => 'qnb',
                'bank_name' => 'PT. BANK QNB INDONESIA TBK.',
                'created_at' => NULL,
                'id_bank_name' => 122,
                'updated_at' => NULL,
            ),
            122 => 
            array (
                'bank_code' => 'rabobank',
                'bank_name' => 'PT. BANK RABOBANK INTERNATIONAL INDONESIA',
                'created_at' => NULL,
                'id_bank_name' => 123,
                'updated_at' => NULL,
            ),
            123 => 
            array (
                'bank_code' => 'rbos',
                'bank_name' => 'THE ROYAL BANK OF SCOTLAND N.V.',
                'created_at' => NULL,
                'id_bank_name' => 124,
                'updated_at' => NULL,
            ),
            124 => 
            array (
                'bank_code' => 'resona',
                'bank_name' => 'PT. BANK RESONA PERDANIA',
                'created_at' => NULL,
                'id_bank_name' => 125,
                'updated_at' => NULL,
            ),
            125 => 
            array (
                'bank_code' => 'riau',
                'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH RIAU KEPRI',
                'created_at' => NULL,
                'id_bank_name' => 126,
                'updated_at' => NULL,
            ),
            126 => 
            array (
                'bank_code' => 'royal',
                'bank_name' => 'PT. BANK ROYAL INDONESIA',
                'created_at' => NULL,
                'id_bank_name' => 127,
                'updated_at' => NULL,
            ),
            127 => 
            array (
                'bank_code' => 'sampoerna',
                'bank_name' => 'PT. BANK SAHABAT SAMPOERNA',
                'created_at' => NULL,
                'id_bank_name' => 128,
                'updated_at' => NULL,
            ),
            128 => 
            array (
                'bank_code' => 'sbi',
                'bank_name' => 'PT. BANK SBI INDONESIA',
                'created_at' => NULL,
                'id_bank_name' => 129,
                'updated_at' => NULL,
            ),
            129 => 
            array (
                'bank_code' => 'shinhan',
                'bank_name' => 'PT. BANK SHINHAN INDONESIA',
                'created_at' => NULL,
                'id_bank_name' => 130,
                'updated_at' => NULL,
            ),
            130 => 
            array (
                'bank_code' => 'sinarmas',
                'bank_name' => 'PT. BANK SINARMAS',
                'created_at' => NULL,
                'id_bank_name' => 131,
                'updated_at' => NULL,
            ),
            131 => 
            array (
                'bank_code' => 'sinarmas_syar',
                'bank_name' => 'PT. BANK SINARMAS UNIT USAHA SYARIAH',
                'created_at' => NULL,
                'id_bank_name' => 132,
                'updated_at' => NULL,
            ),
            132 => 
            array (
                'bank_code' => 'stanchard',
                'bank_name' => 'STANDARD CHARTERED BANK',
                'created_at' => NULL,
                'id_bank_name' => 133,
                'updated_at' => NULL,
            ),
            133 => 
            array (
                'bank_code' => 'sulselbar',
                'bank_name' => 'PT. BANK SULSELBAR',
                'created_at' => NULL,
                'id_bank_name' => 134,
                'updated_at' => NULL,
            ),
            134 => 
            array (
                'bank_code' => 'sulselbar_syar',
                'bank_name' => 'PT. BPD SULAWESI SELATAN DAN SULAWESI BARAT UNIT USAHA SYARIAH',
                'created_at' => NULL,
                'id_bank_name' => 135,
                'updated_at' => NULL,
            ),
            135 => 
            array (
                'bank_code' => 'sulteng',
                'bank_name' => 'PT. BPD SULAWESI TENGAH',
                'created_at' => NULL,
                'id_bank_name' => 136,
                'updated_at' => NULL,
            ),
            136 => 
            array (
                'bank_code' => 'sultenggara',
                'bank_name' => 'PT. BPD SULAWESI TENGGARA',
                'created_at' => NULL,
                'id_bank_name' => 137,
                'updated_at' => NULL,
            ),
            137 => 
            array (
                'bank_code' => 'sulut',
                'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH SULUT',
                'created_at' => NULL,
                'id_bank_name' => 138,
                'updated_at' => NULL,
            ),
            138 => 
            array (
                'bank_code' => 'sumbar',
                'bank_name' => 'PT. BPD SUMATERA BARAT',
                'created_at' => NULL,
                'id_bank_name' => 139,
                'updated_at' => NULL,
            ),
            139 => 
            array (
                'bank_code' => 'sumitomo',
                'bank_name' => 'PT. BANK SUMITOMO MITSUI INDONESIA',
                'created_at' => NULL,
                'id_bank_name' => 140,
                'updated_at' => NULL,
            ),
            140 => 
            array (
                'bank_code' => 'sumsel_babel',
                'bank_name' => 'PT. BPD SUMSEL DAN BABEL',
                'created_at' => NULL,
                'id_bank_name' => 141,
                'updated_at' => NULL,
            ),
            141 => 
            array (
                'bank_code' => 'sumsel_babel_syar',
                'bank_name' => 'PT. BPD SUMSEL DAN BABEL UNIT USAHA SYARIAH',
                'created_at' => NULL,
                'id_bank_name' => 142,
                'updated_at' => NULL,
            ),
            142 => 
            array (
                'bank_code' => 'sumut',
                'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH SUMUT',
                'created_at' => NULL,
                'id_bank_name' => 143,
                'updated_at' => NULL,
            ),
            143 => 
            array (
                'bank_code' => 'sumut_syar',
                'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH SUMUT UUS',
                'created_at' => NULL,
                'id_bank_name' => 144,
                'updated_at' => NULL,
            ),
            144 => 
            array (
                'bank_code' => 'uob',
                'bank_name' => 'PT. BANK UOB INDONESIA',
                'created_at' => NULL,
                'id_bank_name' => 145,
                'updated_at' => NULL,
            ),
            145 => 
            array (
                'bank_code' => 'victoria',
                'bank_name' => 'PT. BANK VICTORIA INTERNATIONAL',
                'created_at' => NULL,
                'id_bank_name' => 146,
                'updated_at' => NULL,
            ),
            146 => 
            array (
                'bank_code' => 'victoria_syar',
                'bank_name' => 'PT. BANK VICTORIA SYARIAH',
                'created_at' => NULL,
                'id_bank_name' => 147,
                'updated_at' => NULL,
            ),
            147 => 
            array (
                'bank_code' => 'woori',
                'bank_name' => 'PT. BANK WOORI SAUDARA INDONESIA 1906 TBK.',
                'created_at' => NULL,
                'id_bank_name' => 148,
                'updated_at' => NULL,
            ),
            148 => 
            array (
                'bank_code' => 'yudha_bhakti',
                'bank_name' => 'PT. BANK YUDHA BHAKTI',
                'created_at' => NULL,
                'id_bank_name' => 149,
                'updated_at' => NULL,
            ),
            149 => 
            array (
                'bank_code' => 'bank_indonesia',
                'bank_name' => 'BANK INDONESIA',
                'created_at' => NULL,
                'id_bank_name' => 150,
                'updated_at' => NULL,
            ),
            150 => 
            array (
                'bank_code' => 'gopay',
                'bank_name' => 'GO-PAY',
                'created_at' => NULL,
                'id_bank_name' => 151,
                'updated_at' => NULL,
            ),
            151 => 
            array (
                'bank_code' => 'ovo',
                'bank_name' => 'OVO',
                'created_at' => NULL,
                'id_bank_name' => 152,
                'updated_at' => NULL,
            ),
            152 => 
            array (
                'bank_code' => 'riau_syar',
                'bank_name' => 'PT. BANK PEMBANGUNAN DAERAH RIAU KEPRI SYARIAH',
                'created_at' => NULL,
                'id_bank_name' => 153,
                'updated_at' => NULL,
            ),
        ));
        
        
    }
}