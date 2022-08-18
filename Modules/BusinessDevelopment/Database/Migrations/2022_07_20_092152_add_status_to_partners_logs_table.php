<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusToPartnersLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('partners_logs', function (Blueprint $table) {
            $table->enum('update_status',['process','approve','reject'])->default('process')->after('update_address');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('partners_logs', function (Blueprint $table) {
            $table->dropColumn('update_status');
        });
    }
}
