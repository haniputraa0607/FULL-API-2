<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeStatusStepOutletChangeLocationTable extends Migration
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
         DB::statement('ALTER TABLE `outlet_change_location` CHANGE `status_steps` `status_steps` ENUM("Select Location","Calculation","Confirmation Letter","Payment") default "Select Location";');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         DB::statement('ALTER TABLE `outlet_change_location` CHANGE `status_steps` `status_steps` ENUM("Survey Location","Finished Survey Location","Select Location","Calculation","Confirmation Letter","Payment") default "Select Location";');
    }
}
