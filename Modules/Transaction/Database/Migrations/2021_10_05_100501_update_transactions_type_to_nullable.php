<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTransactionsTypeToNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE `transactions` CHANGE COLUMN `trasaction_type` `trasaction_type` ENUM('Pickup Order', 'Delivery', 'Offline', 'Advance Order') COLLATE 'utf8mb4_unicode_ci' NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE `transactions` CHANGE COLUMN `trasaction_type` `trasaction_type` ENUM('Pickup Order', 'Delivery', 'Offline', 'Advance Order') COLLATE 'utf8mb4_unicode_ci'");
    }
}
