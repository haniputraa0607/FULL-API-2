<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInvoiceSpkTable extends Migration
{
    public function up()
    {
        Schema::table('invoice_spk', function (Blueprint $table) {
               $table->text('message')->nullable();
               $table->boolean('status_invoice_spk')->default(1);
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
                 $table->dropColumn('message')->nullable();
               $table->dropColumn('status_invoice_spk');
        });
    }
}
