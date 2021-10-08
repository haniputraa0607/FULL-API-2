<?php

use Illuminate\Database\Seeder;

class FeaturesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('features')->delete();
        
        \DB::table('features')->insert(array (
            0 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Dashboard',
                'feature_type' => 'Report',
                'id_feature' => 1,
                'order' => 1,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            1 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Users',
                'feature_type' => 'List',
                'id_feature' => 2,
                'order' => 2,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            2 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Users',
                'feature_type' => 'Detail',
                'id_feature' => 3,
                'order' => 2,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            3 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Users',
                'feature_type' => 'Create',
                'id_feature' => 4,
                'order' => 2,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            4 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Users',
                'feature_type' => 'Update',
                'id_feature' => 5,
                'order' => 2,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            5 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Users',
                'feature_type' => 'Delete',
                'id_feature' => 6,
                'order' => 2,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            6 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Log Activity',
                'feature_type' => 'List',
                'id_feature' => 7,
                'order' => 3,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            7 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Log Activity',
                'feature_type' => 'Detail',
                'id_feature' => 8,
                'order' => 3,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            8 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Admin Outlet',
                'feature_type' => 'List',
                'id_feature' => 9,
                'order' => 4,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            9 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Membership',
                'feature_type' => 'List',
                'id_feature' => 10,
                'order' => 5,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            10 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Membership',
                'feature_type' => 'Detail',
                'id_feature' => 11,
                'order' => 5,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            11 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Membership',
                'feature_type' => 'Create',
                'id_feature' => 12,
                'order' => 5,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            12 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Membership',
                'feature_type' => 'Update',
                'id_feature' => 13,
                'order' => 5,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            13 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Membership',
                'feature_type' => 'Delete',
                'id_feature' => 14,
                'order' => 5,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            14 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Greeting & Background',
                'feature_type' => 'List',
                'id_feature' => 15,
                'order' => 6,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            15 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Greeting & Background',
                'feature_type' => 'Create',
                'id_feature' => 16,
                'order' => 6,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            16 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Greeting & Background',
                'feature_type' => 'Update',
                'id_feature' => 17,
                'order' => 6,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            17 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Greeting & Background',
                'feature_type' => 'Delete',
                'id_feature' => 18,
                'order' => 6,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            18 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'News',
                'feature_type' => 'List',
                'id_feature' => 19,
                'order' => 7,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            19 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'News',
                'feature_type' => 'Detail',
                'id_feature' => 20,
                'order' => 7,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            20 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'News',
                'feature_type' => 'Create',
                'id_feature' => 21,
                'order' => 7,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            21 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'News',
                'feature_type' => 'Update',
                'id_feature' => 22,
                'order' => 7,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            22 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'News',
                'feature_type' => 'Delete',
                'id_feature' => 23,
                'order' => 7,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            23 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Outlet',
                'feature_type' => 'List',
                'id_feature' => 24,
                'order' => 8,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            24 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Outlet',
                'feature_type' => 'Detail',
                'id_feature' => 25,
                'order' => 8,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            25 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Outlet',
                'feature_type' => 'Create',
                'id_feature' => 26,
                'order' => 8,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            26 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Outlet',
                'feature_type' => 'Update',
                'id_feature' => 27,
                'order' => 8,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            27 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Outlet',
                'feature_type' => 'Delete',
                'id_feature' => 28,
                'order' => 8,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            28 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Outlet Photo',
                'feature_type' => 'List',
                'id_feature' => 29,
                'order' => 9,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            29 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Outlet Photo',
                'feature_type' => 'Create',
                'id_feature' => 30,
                'order' => 9,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            30 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Outlet Photo',
                'feature_type' => 'Delete',
                'id_feature' => 31,
                'order' => 9,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            31 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Outlet Import',
                'feature_type' => 'Update',
                'id_feature' => 32,
                'order' => 10,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            32 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Outlet Export',
                'feature_type' => 'Detail',
                'id_feature' => 33,
                'order' => 10,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            33 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Outlet Holiday',
                'feature_type' => 'List',
                'id_feature' => 34,
                'order' => 11,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            34 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Outlet Holiday',
                'feature_type' => 'Detail',
                'id_feature' => 35,
                'order' => 11,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            35 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Outlet Holiday',
                'feature_type' => 'Create',
                'id_feature' => 36,
                'order' => 11,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            36 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Outlet Holiday',
                'feature_type' => 'Update',
                'id_feature' => 37,
                'order' => 11,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            37 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Outlet Holiday',
                'feature_type' => 'Delete',
                'id_feature' => 38,
                'order' => 11,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            38 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Outlet Admin',
                'feature_type' => 'Detail',
                'id_feature' => 39,
                'order' => 12,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            39 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Outlet Admin',
                'feature_type' => 'Create',
                'id_feature' => 40,
                'order' => 12,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            40 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Outlet Admin',
                'feature_type' => 'Update',
                'id_feature' => 41,
                'order' => 12,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            41 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Outlet Admin',
                'feature_type' => 'Delete',
                'id_feature' => 42,
                'order' => 12,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            42 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Product Category',
                'feature_type' => 'List',
                'id_feature' => 43,
                'order' => 13,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            43 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Product Category',
                'feature_type' => 'Detail',
                'id_feature' => 44,
                'order' => 13,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            44 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Product Category',
                'feature_type' => 'Create',
                'id_feature' => 45,
                'order' => 13,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            45 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Product Category',
                'feature_type' => 'Update',
                'id_feature' => 46,
                'order' => 13,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            46 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Product Category',
                'feature_type' => 'Delete',
                'id_feature' => 47,
                'order' => 13,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            47 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Product',
                'feature_type' => 'List',
                'id_feature' => 48,
                'order' => 14,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            48 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Product',
                'feature_type' => 'Detail',
                'id_feature' => 49,
                'order' => 14,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            49 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Product',
                'feature_type' => 'Create',
                'id_feature' => 50,
                'order' => 14,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            50 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Product',
                'feature_type' => 'Update',
                'id_feature' => 51,
                'order' => 14,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            51 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Product',
                'feature_type' => 'Delete',
                'id_feature' => 52,
                'order' => 14,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            52 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Product Photo',
                'feature_type' => 'List',
                'id_feature' => 53,
                'order' => 15,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            53 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Product Photo',
                'feature_type' => 'Create',
                'id_feature' => 54,
                'order' => 15,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            54 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Product Photo',
                'feature_type' => 'Delete',
                'id_feature' => 55,
                'order' => 15,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            55 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Product Import',
                'feature_type' => 'Update',
                'id_feature' => 56,
                'order' => 16,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            56 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Product Export',
                'feature_type' => 'Detail',
                'id_feature' => 57,
                'order' => 17,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            57 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Grand Total Calculation Rule',
                'feature_type' => 'Update',
                'id_feature' => 58,
                'order' => 18,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            58 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Point Acquisition Setting',
                'feature_type' => 'Update',
                'id_feature' => 59,
                'order' => 19,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            59 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Cashback Acquisition Setting',
                'feature_type' => 'Update',
                'id_feature' => 60,
                'order' => 20,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            60 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Delivery Price Setting',
                'feature_type' => 'Update',
                'id_feature' => 61,
                'order' => 21,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            61 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Outlet Product Price Setting',
                'feature_type' => 'Update',
                'id_feature' => 62,
                'order' => 22,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            62 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Internal Courier Setting',
                'feature_type' => 'Update',
                'id_feature' => 63,
                'order' => 23,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            63 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Manual Payment',
                'feature_type' => 'List',
                'id_feature' => 64,
                'order' => 24,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            64 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Manual Payment',
                'feature_type' => 'Detail',
                'id_feature' => 65,
                'order' => 24,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            65 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Manual Payment',
                'feature_type' => 'Create',
                'id_feature' => 66,
                'order' => 24,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            66 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Manual Payment',
                'feature_type' => 'Update',
                'id_feature' => 67,
                'order' => 24,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            67 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Manual Payment',
                'feature_type' => 'Delete',
                'id_feature' => 68,
                'order' => 24,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            68 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Transaction',
                'feature_type' => 'List',
                'id_feature' => 69,
                'order' => 25,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            69 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Transaction',
                'feature_type' => 'Detail',
                'id_feature' => 70,
                'order' => 25,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            70 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Point Log History',
                'feature_type' => 'List',
                'id_feature' => 71,
                'order' => 26,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            71 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Deals',
                'feature_type' => 'List',
                'id_feature' => 72,
                'order' => 27,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            72 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Deals',
                'feature_type' => 'Detail',
                'id_feature' => 73,
                'order' => 27,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            73 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Deals',
                'feature_type' => 'Create',
                'id_feature' => 74,
                'order' => 27,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            74 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Deals',
                'feature_type' => 'Update',
                'id_feature' => 75,
                'order' => 27,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            75 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Deals',
                'feature_type' => 'Delete',
                'id_feature' => 76,
                'order' => 27,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            76 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Inject Voucher',
                'feature_type' => 'List',
                'id_feature' => 77,
                'order' => 28,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            77 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Inject Voucher',
                'feature_type' => 'Detail',
                'id_feature' => 78,
                'order' => 28,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            78 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Inject Voucher',
                'feature_type' => 'Create',
                'id_feature' => 79,
                'order' => 28,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            79 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Inject Voucher',
                'feature_type' => 'Update',
                'id_feature' => 80,
                'order' => 28,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            80 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Inject Voucher',
                'feature_type' => 'Delete',
                'id_feature' => 81,
                'order' => 28,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            81 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Text Replace',
                'feature_type' => 'Update',
                'id_feature' => 82,
                'order' => 29,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            82 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Enquiries',
                'feature_type' => 'List',
                'id_feature' => 83,
                'order' => 30,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            83 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Enquiries',
                'feature_type' => 'Detail',
                'id_feature' => 84,
                'order' => 30,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            84 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'About Us',
                'feature_type' => 'Update',
                'id_feature' => 85,
                'order' => 31,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            85 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Terms Of Services',
                'feature_type' => 'Update',
                'id_feature' => 86,
                'order' => 32,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            86 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Contact Us',
                'feature_type' => 'Update',
                'id_feature' => 87,
                'order' => 33,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            87 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Frequently Asked Question',
                'feature_type' => 'List',
                'id_feature' => 88,
                'order' => 34,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            88 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Frequently Asked Question',
                'feature_type' => 'Create',
                'id_feature' => 89,
                'order' => 34,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            89 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Frequently Asked Question',
                'feature_type' => 'Update',
                'id_feature' => 90,
                'order' => 34,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            90 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Frequently Asked Question',
                'feature_type' => 'Delete',
                'id_feature' => 91,
                'order' => 34,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            91 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Auto CRM User',
                'feature_type' => 'Update',
                'id_feature' => 92,
                'order' => 35,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            92 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Auto CRM Transaction',
                'feature_type' => 'Update',
                'id_feature' => 93,
                'order' => 36,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            93 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Auto CRM Enquiry',
                'feature_type' => 'Update',
                'id_feature' => 94,
                'order' => 37,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            94 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Auto CRM Deals',
                'feature_type' => 'Update',
                'id_feature' => 95,
                'order' => 38,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            95 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Text Replaces',
                'feature_type' => 'Update',
                'id_feature' => 96,
                'order' => 29,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            96 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Email Header & Footer',
                'feature_type' => 'Update',
                'id_feature' => 97,
                'order' => 39,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            97 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Campaign',
                'feature_type' => 'List',
                'id_feature' => 98,
                'order' => 40,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            98 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Campaign',
                'feature_type' => 'Detail',
                'id_feature' => 99,
                'order' => 40,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            99 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Campaign',
                'feature_type' => 'Create',
                'id_feature' => 100,
                'order' => 40,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            100 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Campaign',
                'feature_type' => 'Update',
                'id_feature' => 101,
                'order' => 40,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            101 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Campaign',
                'feature_type' => 'Delete',
                'id_feature' => 102,
                'order' => 40,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            102 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Campaign Email Queue',
                'feature_type' => 'List',
                'id_feature' => 103,
                'order' => 41,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            103 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Campaign Email Sent',
                'feature_type' => 'List',
                'id_feature' => 104,
                'order' => 42,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            104 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Campaign SMS Queue',
                'feature_type' => 'List',
                'id_feature' => 105,
                'order' => 43,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            105 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Campaign SMS Sent',
                'feature_type' => 'List',
                'id_feature' => 106,
                'order' => 44,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            106 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Campaign Push Queue',
                'feature_type' => 'List',
                'id_feature' => 107,
                'order' => 45,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            107 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Campaign Push Sent',
                'feature_type' => 'List',
                'id_feature' => 108,
                'order' => 46,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            108 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Promotion',
                'feature_type' => 'List',
                'id_feature' => 109,
                'order' => 47,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            109 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Promotion',
                'feature_type' => 'Detail',
                'id_feature' => 110,
                'order' => 47,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            110 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Promotion',
                'feature_type' => 'Create',
                'id_feature' => 111,
                'order' => 47,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            111 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Promotion',
                'feature_type' => 'Update',
                'id_feature' => 112,
                'order' => 47,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            112 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Promotion',
                'feature_type' => 'Delete',
                'id_feature' => 113,
                'order' => 47,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            113 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Inbox Global',
                'feature_type' => 'List',
                'id_feature' => 114,
                'order' => 48,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            114 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Inbox Global',
                'feature_type' => 'Detail',
                'id_feature' => 115,
                'order' => 48,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            115 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Inbox Global',
                'feature_type' => 'Create',
                'id_feature' => 116,
                'order' => 48,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            116 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Inbox Global',
                'feature_type' => 'Update',
                'id_feature' => 117,
                'order' => 48,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            117 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Inbox Global',
                'feature_type' => 'Delete',
                'id_feature' => 118,
                'order' => 48,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            118 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Auto CRM',
                'feature_type' => 'List',
                'id_feature' => 119,
                'order' => 49,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            119 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Auto CRM',
                'feature_type' => 'Detail',
                'id_feature' => 120,
                'order' => 49,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            120 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Auto CRM',
                'feature_type' => 'Create',
                'id_feature' => 121,
                'order' => 49,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            121 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Auto CRM',
                'feature_type' => 'Update',
                'id_feature' => 122,
                'order' => 49,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            122 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Auto CRM',
                'feature_type' => 'Delete',
                'id_feature' => 123,
                'order' => 49,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            123 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Advertisement',
                'feature_type' => 'Update',
                'id_feature' => 124,
                'order' => 50,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            124 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Report Global',
                'feature_type' => 'Report',
                'id_feature' => 125,
                'order' => 51,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            125 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Report Customer',
                'feature_type' => 'Report',
                'id_feature' => 126,
                'order' => 52,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            126 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Report Product',
                'feature_type' => 'Report',
                'id_feature' => 127,
                'order' => 53,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            127 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Report Outlet',
                'feature_type' => 'Report',
                'id_feature' => 128,
                'order' => 54,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            128 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Magic Report',
                'feature_type' => 'Report',
                'id_feature' => 129,
                'order' => 55,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            129 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Reward',
                'feature_type' => 'List',
                'id_feature' => 130,
                'order' => 56,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            130 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Reward',
                'feature_type' => 'Detail',
                'id_feature' => 131,
                'order' => 56,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            131 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Reward',
                'feature_type' => 'Create',
                'id_feature' => 132,
                'order' => 56,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            132 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Reward',
                'feature_type' => 'Update',
                'id_feature' => 133,
                'order' => 56,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            133 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Reward',
                'feature_type' => 'Delete',
                'id_feature' => 134,
                'order' => 56,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            134 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Spin The Wheel',
                'feature_type' => 'Create',
                'id_feature' => 135,
                'order' => 57,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            135 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Spin The Wheel',
                'feature_type' => 'Update',
                'id_feature' => 136,
                'order' => 57,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            136 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Spin The Wheel',
                'feature_type' => 'Delete',
                'id_feature' => 137,
                'order' => 57,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            137 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Spin The Wheel Setting',
                'feature_type' => 'Update',
                'id_feature' => 138,
                'order' => 58,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            138 => 
            array (
                'created_at' => '2018-12-12 08:00:00',
                'feature_module' => 'Deals Subscription',
                'feature_type' => 'List',
                'id_feature' => 139,
                'order' => 59,
                'show_hide' => 0,
                'updated_at' => '2018-12-12 08:00:00',
            ),
            139 => 
            array (
                'created_at' => '2018-12-12 08:00:00',
                'feature_module' => 'Deals Subscription',
                'feature_type' => 'Detail',
                'id_feature' => 140,
                'order' => 59,
                'show_hide' => 0,
                'updated_at' => '2018-12-12 08:00:00',
            ),
            140 => 
            array (
                'created_at' => '2018-12-12 08:00:00',
                'feature_module' => 'Deals Subscription',
                'feature_type' => 'Create',
                'id_feature' => 141,
                'order' => 59,
                'show_hide' => 0,
                'updated_at' => '2018-12-12 08:00:00',
            ),
            141 => 
            array (
                'created_at' => '2018-12-12 08:00:00',
                'feature_module' => 'Deals Subscription',
                'feature_type' => 'Update',
                'id_feature' => 142,
                'order' => 59,
                'show_hide' => 0,
                'updated_at' => '2018-12-12 08:00:00',
            ),
            142 => 
            array (
                'created_at' => '2018-12-12 08:00:00',
                'feature_module' => 'Deals Subscription',
                'feature_type' => 'Delete',
                'id_feature' => 143,
                'order' => 59,
                'show_hide' => 0,
                'updated_at' => '2018-12-12 08:00:00',
            ),
            143 => 
            array (
                'created_at' => '2018-12-14 08:00:00',
                'feature_module' => 'Banner',
                'feature_type' => 'List',
                'id_feature' => 144,
                'order' => 60,
                'show_hide' => 1,
                'updated_at' => '2018-12-14 08:00:00',
            ),
            144 => 
            array (
                'created_at' => '2018-12-14 08:00:00',
                'feature_module' => 'Banner',
                'feature_type' => 'Create',
                'id_feature' => 145,
                'order' => 60,
                'show_hide' => 1,
                'updated_at' => '2018-12-14 08:00:00',
            ),
            145 => 
            array (
                'created_at' => '2018-12-14 08:00:00',
                'feature_module' => 'Banner',
                'feature_type' => 'Update',
                'id_feature' => 146,
                'order' => 60,
                'show_hide' => 1,
                'updated_at' => '2018-12-14 08:00:00',
            ),
            146 => 
            array (
                'created_at' => '2018-12-14 08:00:00',
                'feature_module' => 'Banner',
                'feature_type' => 'Delete',
                'id_feature' => 147,
                'order' => 60,
                'show_hide' => 1,
                'updated_at' => '2018-12-14 08:00:00',
            ),
            147 => 
            array (
                'created_at' => '2018-12-17 16:20:00',
                'feature_module' => 'User Profile Completing',
                'feature_type' => 'Update',
                'id_feature' => 148,
                'order' => 61,
                'show_hide' => 1,
                'updated_at' => '2018-12-17 16:20:00',
            ),
            148 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Custom Page',
                'feature_type' => 'List',
                'id_feature' => 149,
                'order' => 62,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            149 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Custom Page',
                'feature_type' => 'Create',
                'id_feature' => 150,
                'order' => 62,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            150 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Custom Page',
                'feature_type' => 'Update',
                'id_feature' => 151,
                'order' => 62,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            151 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Custom Page',
                'feature_type' => 'Delete',
                'id_feature' => 152,
                'order' => 62,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            152 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Custom Page',
                'feature_type' => 'Detail',
                'id_feature' => 153,
                'order' => 62,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            153 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Delivery Service',
                'feature_type' => 'Create',
                'id_feature' => 154,
                'order' => 63,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            154 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Brand',
                'feature_type' => 'List',
                'id_feature' => 155,
                'order' => 64,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            155 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Brand',
                'feature_type' => 'Create',
                'id_feature' => 156,
                'order' => 64,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            156 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Brand',
                'feature_type' => 'Update',
                'id_feature' => 157,
                'order' => 64,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            157 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Brand',
                'feature_type' => 'Delete',
                'id_feature' => 158,
                'order' => 64,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            158 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Brand',
                'feature_type' => 'Detail',
                'id_feature' => 159,
                'order' => 64,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            159 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Text Menu',
                'feature_type' => 'List',
                'id_feature' => 160,
                'order' => 65,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            160 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Text Menu',
                'feature_type' => 'Update',
                'id_feature' => 161,
                'order' => 65,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            161 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Confirmation Messages',
                'feature_type' => 'List',
                'id_feature' => 162,
                'order' => 66,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            162 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Confirmation Messages',
                'feature_type' => 'Update',
                'id_feature' => 163,
                'order' => 66,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            163 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'News Category',
                'feature_type' => 'List',
                'id_feature' => 164,
                'order' => 67,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            164 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'News Category',
                'feature_type' => 'Create',
                'id_feature' => 165,
                'order' => 67,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            165 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'News Category',
                'feature_type' => 'Update',
                'id_feature' => 166,
                'order' => 67,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            166 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'News Category',
                'feature_type' => 'Delete',
                'id_feature' => 167,
                'order' => 67,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            167 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Intro',
                'feature_type' => 'List',
                'id_feature' => 168,
                'order' => 68,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            168 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Intro',
                'feature_type' => 'Create',
                'id_feature' => 169,
                'order' => 68,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            169 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Intro',
                'feature_type' => 'Update',
                'id_feature' => 170,
                'order' => 68,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            170 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Intro',
                'feature_type' => 'Delete',
                'id_feature' => 171,
                'order' => 68,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            171 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Subscription',
                'feature_type' => 'Create',
                'id_feature' => 172,
                'order' => 69,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            172 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Subscription',
                'feature_type' => 'List',
                'id_feature' => 173,
                'order' => 69,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            173 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Subscription',
                'feature_type' => 'Detail',
                'id_feature' => 174,
                'order' => 69,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            174 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Subscription',
                'feature_type' => 'Update',
                'id_feature' => 175,
                'order' => 69,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            175 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Subscription',
                'feature_type' => 'Delete',
                'id_feature' => 176,
                'order' => 69,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            176 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Subscription',
                'feature_type' => 'Report',
                'id_feature' => 177,
                'order' => 69,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            177 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Auto CRM Subscription',
                'feature_type' => 'Update',
                'id_feature' => 178,
                'order' => 70,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            178 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'User Feedback',
                'feature_type' => 'List',
                'id_feature' => 179,
                'order' => 71,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            179 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Product Modifier',
                'feature_type' => 'List',
                'id_feature' => 180,
                'order' => 72,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            180 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Product Modifier',
                'feature_type' => 'Create',
                'id_feature' => 181,
                'order' => 72,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            181 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Product Modifier',
                'feature_type' => 'Detail',
                'id_feature' => 182,
                'order' => 72,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            182 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Product Modifier',
                'feature_type' => 'Update',
                'id_feature' => 183,
                'order' => 72,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            183 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Product Modifier',
                'feature_type' => 'Delete',
                'id_feature' => 184,
                'order' => 72,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            184 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Product Modifier Price',
                'feature_type' => 'List',
                'id_feature' => 185,
                'order' => 73,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            185 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Product Modifier Price',
                'feature_type' => 'Update',
                'id_feature' => 186,
                'order' => 73,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            186 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Welcome Voucher',
                'feature_type' => 'List',
                'id_feature' => 187,
                'order' => 74,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            187 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Welcome Voucher',
                'feature_type' => 'Detail',
                'id_feature' => 188,
                'order' => 74,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            188 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Welcome Voucher',
                'feature_type' => 'Create',
                'id_feature' => 189,
                'order' => 74,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            189 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Welcome Voucher',
                'feature_type' => 'Update',
                'id_feature' => 190,
                'order' => 74,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            190 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Welcome Voucher',
                'feature_type' => 'Delete',
                'id_feature' => 191,
                'order' => 74,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            191 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Fraud Detection Settings',
                'feature_type' => 'Update',
                'id_feature' => 192,
                'order' => 75,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            192 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Report Fraud Device',
                'feature_type' => 'Report',
                'id_feature' => 193,
                'order' => 76,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            193 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Report Fraud Transaction Day',
                'feature_type' => 'Report',
                'id_feature' => 194,
                'order' => 77,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            194 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Report Fraud Transaction Week',
                'feature_type' => 'Report',
                'id_feature' => 195,
                'order' => 78,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            195 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'List User Fraud',
                'feature_type' => 'Update',
                'id_feature' => 196,
                'order' => 79,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            196 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Maximum Order',
                'feature_type' => 'List',
                'id_feature' => 197,
                'order' => 80,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            197 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Maximum Order',
                'feature_type' => 'Update',
                'id_feature' => 198,
                'order' => 80,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            198 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Default Outlet',
                'feature_type' => 'Update',
                'id_feature' => 199,
                'order' => 81,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            199 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Promo Campaign',
                'feature_type' => 'List',
                'id_feature' => 200,
                'order' => 82,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            200 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Promo Campaign',
                'feature_type' => 'Detail',
                'id_feature' => 201,
                'order' => 82,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            201 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Promo Campaign',
                'feature_type' => 'Create',
                'id_feature' => 202,
                'order' => 82,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            202 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Promo Campaign',
                'feature_type' => 'Update',
                'id_feature' => 203,
                'order' => 82,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            203 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Promo Campaign',
                'feature_type' => 'Delete',
                'id_feature' => 204,
                'order' => 82,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            204 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Point Injection',
                'feature_type' => 'List',
                'id_feature' => 205,
                'order' => 83,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            205 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Point Injection',
                'feature_type' => 'Detail',
                'id_feature' => 206,
                'order' => 83,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            206 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Point Injection',
                'feature_type' => 'Create',
                'id_feature' => 207,
                'order' => 83,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            207 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Point Injection',
                'feature_type' => 'Update',
                'id_feature' => 208,
                'order' => 83,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            208 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Point Injection',
                'feature_type' => 'Delete',
                'id_feature' => 209,
                'order' => 83,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            209 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Setting Phone',
                'feature_type' => 'Update',
                'id_feature' => 210,
                'order' => 82,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            210 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'User Feedback',
                'feature_type' => 'Detail',
                'id_feature' => 211,
                'order' => 71,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            211 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Feedback Rating Item',
                'feature_type' => 'List',
                'id_feature' => 212,
                'order' => 83,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            212 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Feedback Rating Item',
                'feature_type' => 'Update',
                'id_feature' => 213,
                'order' => 83,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            213 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Report Fraud Transaction Point',
                'feature_type' => 'Report',
                'id_feature' => 214,
                'order' => 84,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            214 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Report Fraud In Between Transaction',
                'feature_type' => 'Report',
                'id_feature' => 215,
                'order' => 85,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            215 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Referral',
                'feature_type' => 'Update',
                'id_feature' => 216,
                'order' => 86,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            216 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Report Fraud Referral User',
                'feature_type' => 'Report',
                'id_feature' => 217,
                'order' => 87,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            217 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Report Fraud Referral',
                'feature_type' => 'Report',
                'id_feature' => 218,
                'order' => 88,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            218 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Report Fraud Promo Code',
                'feature_type' => 'Report',
                'id_feature' => 219,
                'order' => 89,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            219 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Maintenance Mode Setting',
                'feature_type' => 'Update',
                'id_feature' => 220,
                'order' => 90,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            220 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Achievement',
                'feature_type' => 'List',
                'id_feature' => 221,
                'order' => 91,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            221 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Achievement',
                'feature_type' => 'Detail',
                'id_feature' => 222,
                'order' => 91,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            222 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Achievement',
                'feature_type' => 'Create',
                'id_feature' => 223,
                'order' => 91,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            223 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Achievement',
                'feature_type' => 'Update',
                'id_feature' => 224,
                'order' => 91,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            224 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Achievement',
                'feature_type' => 'Delete',
                'id_feature' => 225,
                'order' => 91,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            225 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Achievement',
                'feature_type' => 'Report',
                'id_feature' => 226,
                'order' => 91,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            226 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Quest',
                'feature_type' => 'List',
                'id_feature' => 227,
                'order' => 92,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            227 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Quest',
                'feature_type' => 'Detail',
                'id_feature' => 228,
                'order' => 92,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            228 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Quest',
                'feature_type' => 'Create',
                'id_feature' => 229,
                'order' => 92,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            229 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Quest',
                'feature_type' => 'Update',
                'id_feature' => 230,
                'order' => 92,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            230 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Quest',
                'feature_type' => 'Delete',
                'id_feature' => 231,
                'order' => 92,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            231 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Quest',
                'feature_type' => 'Report',
                'id_feature' => 232,
                'order' => 92,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            232 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Promo Cashback Setting',
                'feature_type' => 'Update',
                'id_feature' => 233,
                'order' => 93,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            233 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'List Disburse',
                'feature_type' => 'List',
                'id_feature' => 234,
                'order' => 94,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            234 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Settings Disburse',
                'feature_type' => 'Update',
                'id_feature' => 235,
                'order' => 95,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            235 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Product Promo Category',
                'feature_type' => 'List',
                'id_feature' => 236,
                'order' => 96,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            236 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Product Promo Category',
                'feature_type' => 'Detail',
                'id_feature' => 237,
                'order' => 96,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            237 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Product Promo Category',
                'feature_type' => 'Create',
                'id_feature' => 238,
                'order' => 96,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            238 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Product Promo Category',
                'feature_type' => 'Update',
                'id_feature' => 239,
                'order' => 96,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            239 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Product Promo Category',
                'feature_type' => 'Delete',
                'id_feature' => 240,
                'order' => 96,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            240 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Featured Subscription',
                'feature_type' => 'List',
                'id_feature' => 241,
                'order' => 97,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            241 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Featured Subscription',
                'feature_type' => 'Create',
                'id_feature' => 242,
                'order' => 97,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            242 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Featured Subscription',
                'feature_type' => 'Update',
                'id_feature' => 243,
                'order' => 97,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            243 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Featured Subscription',
                'feature_type' => 'Delete',
                'id_feature' => 244,
                'order' => 97,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            244 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Report Point Injection',
                'feature_type' => 'Report',
                'id_feature' => 245,
                'order' => 98,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            245 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Setting Inbox User',
                'feature_type' => 'Update',
                'id_feature' => 246,
                'order' => 99,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            246 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'List User Franchise',
                'feature_type' => 'List',
                'id_feature' => 247,
                'order' => 100,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            247 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'User Franchise',
                'feature_type' => 'Update',
                'id_feature' => 248,
                'order' => 101,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            248 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Report GoSend',
                'feature_type' => 'Report',
                'id_feature' => 249,
                'order' => 102,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            249 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Setting Payment Method',
                'feature_type' => 'Update',
                'id_feature' => 250,
                'order' => 103,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            250 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Setting Time Expired OTP',
                'feature_type' => 'Update',
                'id_feature' => 251,
                'order' => 104,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            251 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Setting Time Expired Email',
                'feature_type' => 'Update',
                'id_feature' => 252,
                'order' => 105,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            252 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Payment Method',
                'feature_type' => 'Create',
                'id_feature' => 253,
                'order' => 107,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            253 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Payment Method',
                'feature_type' => 'List',
                'id_feature' => 254,
                'order' => 107,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            254 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Payment Method',
                'feature_type' => 'Update',
                'id_feature' => 255,
                'order' => 107,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            255 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Payment Method',
                'feature_type' => 'Delete',
                'id_feature' => 256,
                'order' => 107,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            256 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Payment Method Category',
                'feature_type' => 'Create',
                'id_feature' => 257,
                'order' => 107,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            257 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Payment Method Category',
                'feature_type' => 'List',
                'id_feature' => 258,
                'order' => 107,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            258 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Payment Method Category',
                'feature_type' => 'Update',
                'id_feature' => 259,
                'order' => 107,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            259 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Payment Method Category',
                'feature_type' => 'Delete',
                'id_feature' => 260,
                'order' => 107,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            260 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Outlet Pin',
                'feature_type' => 'List',
                'id_feature' => 261,
                'order' => 8,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            261 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Order Auto Reject Time',
                'feature_type' => 'Update',
                'id_feature' => 262,
                'order' => 106,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            262 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Report Payment',
                'feature_type' => 'Report',
                'id_feature' => 263,
                'order' => 109,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            263 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Welcome Subscription',
                'feature_type' => 'List',
                'id_feature' => 264,
                'order' => 110,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            264 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Welcome Subscription',
                'feature_type' => 'Detail',
                'id_feature' => 265,
                'order' => 110,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            265 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Welcome Subscription',
                'feature_type' => 'Create',
                'id_feature' => 266,
                'order' => 110,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            266 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Welcome Subscription',
                'feature_type' => 'Update',
                'id_feature' => 267,
                'order' => 110,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            267 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Welcome Subscription',
                'feature_type' => 'Delete',
                'id_feature' => 268,
                'order' => 110,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            268 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Start Deals',
                'feature_type' => 'Update',
                'id_feature' => 269,
                'order' => 111,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            269 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Start Subscription',
                'feature_type' => 'Update',
                'id_feature' => 270,
                'order' => 112,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            270 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Report Shift',
                'feature_type' => 'Report',
                'id_feature' => 271,
                'order' => 107,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            271 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Setting Timer ShopeePay',
                'feature_type' => 'Update',
                'id_feature' => 272,
                'order' => 108,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            272 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Setting Splash Screen Outlet Apps',
                'feature_type' => 'Update',
                'id_feature' => 273,
                'order' => 109,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            273 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Flag Invalid Transaction',
                'feature_type' => 'Create',
                'id_feature' => 274,
                'order' => 110,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            274 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Flag Invalid Transaction',
                'feature_type' => 'Update',
                'id_feature' => 275,
                'order' => 110,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            275 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Flag Invalid Transaction',
                'feature_type' => 'Report',
                'id_feature' => 276,
                'order' => 110,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            276 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Product Variant',
                'feature_type' => 'List',
                'id_feature' => 278,
                'order' => 111,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            277 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Product Variant',
                'feature_type' => 'Create',
                'id_feature' => 279,
                'order' => 111,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            278 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Product Variant',
                'feature_type' => 'Detail',
                'id_feature' => 280,
                'order' => 111,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            279 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Product Variant',
                'feature_type' => 'Update',
                'id_feature' => 281,
                'order' => 111,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            280 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Product Variant',
                'feature_type' => 'Delete',
                'id_feature' => 282,
                'order' => 111,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            281 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Product Modifier Group',
                'feature_type' => 'List',
                'id_feature' => 283,
                'order' => 112,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            282 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Product Modifier Group',
                'feature_type' => 'Create',
                'id_feature' => 284,
                'order' => 112,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            283 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Product Modifier Group',
                'feature_type' => 'Detail',
                'id_feature' => 285,
                'order' => 112,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            284 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Product Modifier Group',
                'feature_type' => 'Update',
                'id_feature' => 286,
                'order' => 112,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            285 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Product Modifier Group',
                'feature_type' => 'Delete',
                'id_feature' => 287,
                'order' => 112,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            286 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Product Bundling',
                'feature_type' => 'List',
                'id_feature' => 288,
                'order' => 113,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            287 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Product Bundling',
                'feature_type' => 'Detail',
                'id_feature' => 289,
                'order' => 113,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            288 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Product Bundling',
                'feature_type' => 'Create',
                'id_feature' => 290,
                'order' => 113,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            289 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Product Bundling',
                'feature_type' => 'Update',
                'id_feature' => 291,
                'order' => 113,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            290 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Product Bundling',
                'feature_type' => 'Delete',
                'id_feature' => 292,
                'order' => 113,
                'show_hide' => 0,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            291 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Auto CRM Voucher',
                'feature_type' => 'Update',
                'id_feature' => 293,
                'order' => 114,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            292 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Outlet Group Filter',
                'feature_type' => 'List',
                'id_feature' => 294,
                'order' => 115,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            293 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Outlet Group Filter',
                'feature_type' => 'Detail',
                'id_feature' => 295,
                'order' => 115,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            294 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Outlet Group Filter',
                'feature_type' => 'Create',
                'id_feature' => 296,
                'order' => 115,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            295 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Outlet Group Filter',
                'feature_type' => 'Update',
                'id_feature' => 297,
                'order' => 115,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            296 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Outlet Group Filter',
                'feature_type' => 'Delete',
                'id_feature' => 298,
                'order' => 115,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            297 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Failed Void Payment',
                'feature_type' => 'List',
                'id_feature' => 299,
                'order' => 116,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            298 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Failed Void Payment',
                'feature_type' => 'Update',
                'id_feature' => 300,
                'order' => 116,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            299 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'User Franchise',
                'feature_type' => 'List',
                'id_feature' => 301,
                'order' => 116,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            300 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'User Franchise',
                'feature_type' => 'Detail',
                'id_feature' => 302,
                'order' => 116,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            301 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'User Franchise',
                'feature_type' => 'Create',
                'id_feature' => 303,
                'order' => 116,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            302 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'User Franchise',
                'feature_type' => 'Update',
                'id_feature' => 304,
                'order' => 116,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            303 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'User Franchise',
                'feature_type' => 'Delete',
                'id_feature' => 305,
                'order' => 116,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            304 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Quest Voucher',
                'feature_type' => 'List',
                'id_feature' => 306,
                'order' => 117,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            305 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Quest Voucher',
                'feature_type' => 'Detail',
                'id_feature' => 307,
                'order' => 117,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            306 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Quest Voucher',
                'feature_type' => 'Create',
                'id_feature' => 308,
                'order' => 117,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            307 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Quest Voucher',
                'feature_type' => 'Update',
                'id_feature' => 309,
                'order' => 117,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            308 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Quest Voucher',
                'feature_type' => 'Delete',
                'id_feature' => 310,
                'order' => 117,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            309 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Rule Promo Payment Gateway',
                'feature_type' => 'List',
                'id_feature' => 311,
                'order' => 118,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            310 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Rule Promo Payment Gateway',
                'feature_type' => 'Detail',
                'id_feature' => 312,
                'order' => 118,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            311 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Rule Promo Payment Gateway',
                'feature_type' => 'Create',
                'id_feature' => 313,
                'order' => 118,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            312 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Rule Promo Payment Gateway',
                'feature_type' => 'Update',
                'id_feature' => 314,
                'order' => 118,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            313 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Rule Promo Payment Gateway',
                'feature_type' => 'Delete',
                'id_feature' => 315,
                'order' => 118,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            314 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Auto Response With Code',
                'feature_type' => 'List',
                'id_feature' => 316,
                'order' => 117,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            315 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Auto Response With Code',
                'feature_type' => 'Create',
                'id_feature' => 317,
                'order' => 117,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            316 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Auto Response With Code',
                'feature_type' => 'Update',
                'id_feature' => 318,
                'order' => 117,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            317 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Auto Response With Code',
                'feature_type' => 'Delete',
                'id_feature' => 319,
                'order' => 117,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            318 => 
            array (
                'created_at' => '2018-05-10 08:00:00',
                'feature_module' => 'Setting Delivery Method',
                'feature_type' => 'Update',
                'id_feature' => 320,
                'order' => 104,
                'show_hide' => 1,
                'updated_at' => '2018-05-10 08:00:00',
            ),
            319 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Transaction Messages',
                'feature_type' => 'Update',
                'id_feature' => 321,
                'order' => 119,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            320 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Report Wehelpyou',
                'feature_type' => 'Report',
                'id_feature' => 322,
                'order' => 120,
                'show_hide' => 0,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            321 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Job Levels',
                'feature_type' => 'List',
                'id_feature' => 323,
                'order' => 121,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            322 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Job Levels',
                'feature_type' => 'Create',
                'id_feature' => 324,
                'order' => 121,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            323 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Job Levels',
                'feature_type' => 'Detail',
                'id_feature' => 325,
                'order' => 121,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            324 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Job Levels',
                'feature_type' => 'Update',
                'id_feature' => 326,
                'order' => 121,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            325 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Job Levels',
                'feature_type' => 'Delete',
                'id_feature' => 327,
                'order' => 121,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            326 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Department',
                'feature_type' => 'List',
                'id_feature' => 328,
                'order' => 122,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            327 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Department',
                'feature_type' => 'Create',
                'id_feature' => 329,
                'order' => 122,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            328 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Department',
                'feature_type' => 'Detail',
                'id_feature' => 330,
                'order' => 122,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            329 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Department',
                'feature_type' => 'Update',
                'id_feature' => 331,
                'order' => 122,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            330 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Department',
                'feature_type' => 'Delete',
                'id_feature' => 332,
                'order' => 122,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            331 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Role',
                'feature_type' => 'List',
                'id_feature' => 333,
                'order' => 123,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            332 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Role',
                'feature_type' => 'Create',
                'id_feature' => 334,
                'order' => 123,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            333 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Role',
                'feature_type' => 'Detail',
                'id_feature' => 335,
                'order' => 123,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            334 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Role',
                'feature_type' => 'Update',
                'id_feature' => 336,
                'order' => 123,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            335 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Role',
                'feature_type' => 'Delete',
                'id_feature' => 337,
                'order' => 123,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            336 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Partner',
                'feature_type' => 'List',
                'id_feature' => 338,
                'order' => 124,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            337 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Partner',
                'feature_type' => 'Detail',
                'id_feature' => 339,
                'order' => 124,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            338 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Partner',
                'feature_type' => 'Update',
                'id_feature' => 340,
                'order' => 124,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            339 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Partner',
                'feature_type' => 'Delete',
                'id_feature' => 341,
                'order' => 124,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            340 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Location',
                'feature_type' => 'List',
                'id_feature' => 342,
                'order' => 125,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            341 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Location',
                'feature_type' => 'Detail',
                'id_feature' => 343,
                'order' => 125,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            342 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Location',
                'feature_type' => 'Update',
                'id_feature' => 344,
                'order' => 125,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            343 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Location',
                'feature_type' => 'Delete',
                'id_feature' => 345,
                'order' => 125,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            344 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Setting Splash Screen Mitra Apps',
                'feature_type' => 'Update',
                'id_feature' => 346,
                'order' => 126,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            345 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Hair Stylist',
                'feature_type' => 'List',
                'id_feature' => 347,
                'order' => 127,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            346 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Hair Stylist',
                'feature_type' => 'Detail',
                'id_feature' => 348,
                'order' => 127,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            347 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Hair Stylist',
                'feature_type' => 'Update',
                'id_feature' => 349,
                'order' => 127,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            348 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Hair Stylist',
                'feature_type' => 'Delete',
                'id_feature' => 350,
                'order' => 127,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            349 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Bank Account',
                'feature_type' => 'Detail',
                'id_feature' => 351,
                'order' => 128,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            350 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Bank Account',
                'feature_type' => 'Update',
                'id_feature' => 352,
                'order' => 128,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            351 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Hair Stylist Schedule',
                'feature_type' => 'List',
                'id_feature' => 353,
                'order' => 129,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            352 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Hair Stylist Schedule',
                'feature_type' => 'Detail',
                'id_feature' => 354,
                'order' => 129,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            353 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Hair Stylist Schedule',
                'feature_type' => 'Update',
                'id_feature' => 355,
                'order' => 129,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            354 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'User Rating',
                'feature_type' => 'List',
                'id_feature' => 356,
                'order' => 130,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            355 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'User Rating',
                'feature_type' => 'Delete',
                'id_feature' => 357,
                'order' => 130,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            356 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Rating Option',
                'feature_type' => 'List',
                'id_feature' => 358,
                'order' => 131,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            357 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Rating Option',
                'feature_type' => 'Create',
                'id_feature' => 359,
                'order' => 131,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            358 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Rating Option',
                'feature_type' => 'Update',
                'id_feature' => 360,
                'order' => 131,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            359 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Rating Option',
                'feature_type' => 'Delete',
                'id_feature' => 361,
                'order' => 131,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            360 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Product Service',
                'feature_type' => 'List',
                'id_feature' => 362,
                'order' => 132,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            361 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Product Service',
                'feature_type' => 'Detail',
                'id_feature' => 363,
                'order' => 132,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            362 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Product Service',
                'feature_type' => 'Create',
                'id_feature' => 364,
                'order' => 132,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            363 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Product Service',
                'feature_type' => 'Update',
                'id_feature' => 365,
                'order' => 132,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            364 => 
            array (
                'created_at' => '2021-09-29 10:00:00',
                'feature_module' => 'Product Service',
                'feature_type' => 'Delete',
                'id_feature' => 366,
                'order' => 132,
                'show_hide' => 1,
                'updated_at' => '2021-09-29 10:00:00',
            ),
            365 =>
            array(
                'id_feature' => 367,
                'feature_type' => 'Update',
                'feature_module' => 'privacy policy',
                'show_hide' => 1,
                'order' => 133,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
        ));
        
        
    }
}
