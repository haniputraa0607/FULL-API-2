<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInvoiceBapTable extends Migration
{
    public function up()
    {
        Schema::table('invoice_bap', function (Blueprint $table) {
               $table->text('message')->nullable();
               $table->boolean('status_invoice_bap')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoice_bap', function (Blueprint $table) {
               $table->dropColumn('message');
               $table->dropColumn('status_invoice_bap');
        });
    }
}
