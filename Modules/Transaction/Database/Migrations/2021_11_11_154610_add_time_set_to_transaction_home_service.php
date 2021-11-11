<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTimeSetToTransactionHomeService extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_home_services', function (Blueprint $table) {
            $table->enum('schedule_set_time', ['right now', 'set time'])->nullable()->after('schedule_date');
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
            $table->dropColumn('schedule_set_time');
        });
    }
}
