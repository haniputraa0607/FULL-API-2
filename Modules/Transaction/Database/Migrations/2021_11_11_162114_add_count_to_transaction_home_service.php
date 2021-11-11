<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCountToTransactionHomeService extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_home_services', function (Blueprint $table) {
            $table->integer('counter_finding_hair_stylist')->default(0)->after('destination_longitude');
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
            $table->dropColumn('counter_finding_hair_stylist');
        });
    }
}
