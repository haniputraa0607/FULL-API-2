<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTypeHairstylistLogBalance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hairstylist_log_balances', function (Blueprint $table) {
            $table->string('type_log_balance')->default('transactions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hairstylist_log_balances', function (Blueprint $table) {
            $table->dropColumn('type_log_balance');
        });
    }
}
