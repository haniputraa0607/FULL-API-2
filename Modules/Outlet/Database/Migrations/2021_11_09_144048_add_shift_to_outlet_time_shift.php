<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddShiftToOutletTimeShift extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE `outlet_time_shift` CHANGE COLUMN `shift` `shift` ENUM('Morning', 'Middle', 'Evening') COLLATE 'utf8mb4_unicode_ci' NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE `outlet_time_shift` CHANGE COLUMN `shift` `shift` ENUM('Morning', 'Evening') COLLATE 'utf8mb4_unicode_ci' NULL");
    }
}
