<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPurchaseSpkTable extends Migration
{
    public function up()
    {
        Schema::table('purchase_spk', function (Blueprint $table) {
                 $table->text('message')->nullable();
               $table->boolean('status_purchase_spk')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_spk', function (Blueprint $table) {
             $table->dropColumn('message')->nullable();
               $table->dropColumn('status_purchase_spk');
        });
    }
}
