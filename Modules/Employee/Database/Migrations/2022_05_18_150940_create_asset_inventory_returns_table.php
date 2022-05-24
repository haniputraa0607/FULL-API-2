<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAssetInventoryReturnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
    {
        Schema::create('asset_inventory_returns', function (Blueprint $table) {
            $table->Increments('id_asset_inventory_return');
            $table->integer('id_asset_inventory_log')->nullable()->unsigned();
            $table->integer('id_asset_inventory')->nullable()->unsigned();
            $table->integer('id_asset_inventory_loan')->nullable()->unsigned();
            $table->date('date_return')->nullable();
            $table->string('notes')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('asset_inventory_returns');
    }
}
