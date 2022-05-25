<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAssetInventoryLoansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
    {
        Schema::create('asset_inventory_loans', function (Blueprint $table) {
            $table->Increments('id_asset_inventory_loan');
            $table->integer('id_asset_inventory_log')->nullable()->unsigned();
            $table->integer('id_asset_inventory')->nullable()->unsigned();
            $table->enum('status_loan',[
                'Active',
                'Inactive'
            ])->default('Inactive');
            $table->date('start_date_loan')->nullable();
            $table->date('end_date_loan')->nullable();
            $table->integer('qty_loan')->default(1);
            $table->integer('long')->nullable();
            $table->enum('long_loan',[
                'Day','Month',"Year"
            ])->default('Day');
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
        Schema::dropIfExists('asset_inventory_loans');
    }
}
