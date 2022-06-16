<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeHairstylistGroupDefaultFixedIncentiveDetails extends Migration
{
    public function __construct() {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string','integer');
    }
    public function up()
    {
         DB::statement('ALTER TABLE `hairstylist_group_default_fixed_incentive_details` CHANGE `range` `range` INT(11) NULL DEFAULT NULL;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `hairstylist_group_default_fixed_incentive_details` CHANGE  `range` `range` VARCHAR(191) NULL DEFAULT NULL;');
    }
}
