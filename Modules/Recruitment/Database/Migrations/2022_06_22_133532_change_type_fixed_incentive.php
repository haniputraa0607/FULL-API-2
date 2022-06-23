<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTypeFixedIncentive extends Migration
{
    public function __construct() {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    public function up()
    {
         DB::statement('ALTER TABLE `hairstylist_group_default_fixed_incentives` CHANGE `type` `type` ENUM("Single","Multiple") default "Single";');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         DB::statement('ALTER TABLE `hairstylist_group_default_fixed_incentives` CHANGE `type` `type` ENUM("Type 1","Type 2") Null;');
    }
}
