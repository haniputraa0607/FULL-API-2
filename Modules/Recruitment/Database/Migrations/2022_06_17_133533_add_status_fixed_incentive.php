<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusFixedIncentive extends Migration
{
    public function __construct() {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string','integer');
    }
    public function up()
    {
        Schema::table('hairstylist_group_default_fixed_incentives', function (Blueprint $table) {
            $table->enum('status',['incentive','salary_cut'])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
       Schema::table('hairstylist_group_default_fixed_incentives', function (Blueprint $table) {
            $table->dropColumn('status',['incentive','salary_cut'])->nullable();
        });
    }
}
