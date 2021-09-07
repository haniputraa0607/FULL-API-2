<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdatePartnersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::table('partners', function (Blueprint $table) {
        //     $table->date('start_date')->change();
        //     $table->date('end_date')->change();
        //     $table->enum('ownership_status', ['Central','Partner'])->nullable()->change();
        //     $table->enum('cooperation_scheme', ['Profit Sharing','Management Fee'])->nullable()->change();
        //     $table->bigInteger('id_bank_account')->unsigned()->nullable()->change();
        // });
        DB::statement('ALTER TABLE `partners` CHANGE `start_date` `start_date` date NULL;');
        DB::statement('ALTER TABLE `partners` CHANGE `end_date` `end_date` date NULL;');
        DB::statement('ALTER TABLE `partners` CHANGE `ownership_status` `ownership_status` ENUM("Central","Partner") NULL;');
        DB::statement('ALTER TABLE `partners` CHANGE `cooperation_scheme` `cooperation_scheme` ENUM("Profit Sharing","Management Fee") NULL;');
        DB::statement('ALTER TABLE `partners` CHANGE `id_bank_account` `id_bank_account` bigint unsigned NULL;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('', function (Blueprint $table) {

        });
    }
}
