<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDateDisburseSharingManagementFeeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sharing_management_fee', function (Blueprint $table) {
            $table->datetime('date_disburse')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sharing_management_fee', function (Blueprint $table) {
            $table->dropColumn('date_disburse');
        });
    }
}
