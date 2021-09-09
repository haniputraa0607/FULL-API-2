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

    public function __construct() {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    public function up()
     {
         Schema::table('partners', function (Blueprint $table) {
            $table->date('start_date')->change();
            $table->date('end_date')->change();
            $table->bigInteger('id_bank_account')->unsigned()->nullable()->change();
         });
         DB::statement('ALTER TABLE `partners` CHANGE `ownership_status` `ownership_status` ENUM("Central","Partner") NULL;');
         DB::statement('ALTER TABLE `partners` CHANGE `cooperation_scheme` `cooperation_scheme` ENUM("Profit Sharing","Management Fee") NULL;');
         
     }

     /**
      * Reverse the migrations.
      *
      * @return void
      */
     public function down()
     {
         Schema::table('partners', function (Blueprint $table) {
            $table->dateTime('start_date')->change();
            $table->dateTime('end_date')->change();
            $table->bigInteger('id_bank_account')->unsigned()->change();
         });
         DB::statement('ALTER TABLE `partners` CHANGE `ownership_status` `ownership_status` ENUM("Central","Partner") default "Central";');
         DB::statement('ALTER TABLE `partners` CHANGE `cooperation_scheme` `cooperation_scheme` ENUM("Profit Sharing","Management Fee") default "Profit Sharing";');
     }
}