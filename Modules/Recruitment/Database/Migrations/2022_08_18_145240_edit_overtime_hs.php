<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditOvertimeHs extends Migration
{
    public function __construct()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    } 
    public function up()
    {
       Schema::table('hairstylist_group_default_overtimes', function (Blueprint $table) {
            $table->renameColumn('hours','days')->nullable();
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hairstylist_group_default_overtimes', function (Blueprint $table) {
            $table->renameColumn('days','hours')->nullable();
        });
    }
}
