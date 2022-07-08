<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTypeDataFixedIncentive extends Migration
{
    public function __construct()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    } 
    public function up()
    {
       DB::statement('ALTER TABLE `employee_role_default_fixed_incentive_details` CHANGE `range` `range` integer default null;');
    }
    public function down()
    {
        DB::statement('ALTER TABLE `employee_role_default_fixed_incentive_details` CHANGE `range` `range` integer default null;');
    }
}
