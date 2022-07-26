<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeIdProjectInInvoiceSpkToNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoice_spk', function (Blueprint $table) {
            $table->unsignedInteger('id_project')->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoice_spk', function (Blueprint $table) {
            $table->unsignedInteger('id_project')->nullable(false)->change();
        });
    }
}
