<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBankEmployee extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
     public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->text('bank_account_name')->nullable();
            $table->text('bank_account_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('bank_account_name')->nullable();
            $table->dropColumn('bank_account_number')->nullable();
        });
    }
}
