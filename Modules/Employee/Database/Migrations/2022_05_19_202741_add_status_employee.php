<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusEmployee extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
     public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('bank_name')->nullable();
            $table->integer('id_bank_name')->nullable()->unsigned();
            $table->boolean('status_employee')->nullable();
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
            $table->string('bank_name')->nullable();
            $table->dropColumn('id_bank_name');
            $table->dropColumn('status_employee');
        });
    }
}
