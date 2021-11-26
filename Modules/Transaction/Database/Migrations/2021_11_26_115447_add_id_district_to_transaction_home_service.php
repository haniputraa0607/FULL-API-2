<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdDistrictToTransactionHomeService extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_home_services', function (Blueprint $table) {
            $table->unsignedInteger('destination_id_subdistrict')->nullable()->after('destination_phone');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_home_services', function (Blueprint $table) {
            $table->dropColumn('destination_id_subdistrict');
        });
    }
}
