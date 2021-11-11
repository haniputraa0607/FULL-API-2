<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class StatusHomeServiceChangeToNull extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE `transaction_home_services` CHANGE COLUMN `status` `status` ENUM('Finding Hair Stylist', 'Get Hair Stylist', 'On The Way', 'Arrived', 'Start Service', 'Completed', 'Cancelled') COLLATE 'utf8mb4_unicode_ci' NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE `transaction_home_services` CHANGE COLUMN `status` `status` ENUM('Finding Hair Stylist', 'Get Hair Stylist', 'On The Way', 'Arrived', 'Start Service', 'Completed', 'Cancelled') COLLATE 'utf8mb4_unicode_ci' NOT NULL");
    }
}
