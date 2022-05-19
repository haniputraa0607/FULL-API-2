<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAssetInventoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
    {
        Schema::create('asset_inventorys', function (Blueprint $table) {
            $table->Increments('id_asset_inventory');
            $table->integer('id_asset_inventory_category')->nullable()->unsigned();
            $table->string('name_asset_inventory')->nullable();
            $table->string('code')->unique();
            $table->integer('qty')->default(1);
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
        Schema::dropIfExists('asset_inventorys');
    }
}
