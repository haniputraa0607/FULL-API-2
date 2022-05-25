<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAssetInventoryLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
    {
        Schema::create('asset_inventory_logs', function (Blueprint $table) {
            $table->Increments('id_asset_inventory_log');
            $table->integer('id_user')->unsigned();
            $table->integer('id_asset_inventory')->nullable()->unsigned();
            $table->integer('id_approved')->nullable()->unsigned();
            $table->enum('status_asset_inventory',[
                'Pending',
                'Approved',
                'Rejected'
            ])->default('Pending');
            $table->enum('type_asset_inventory',[
                'Loan',
                'Return'
            ])->default('Loan');
            $table->integer('qty_logs')->default('1');
            $table->string('notes')->nullable();
            $table->string('attachment')->nullable();
            $table->datetime('date_action')->nullable();
            $table->timestamps();
            $table->foreign('id_approved', 'fk_employee_asset_user_approved')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('id_user', 'fk_employee_user_asset_inventory')->references('id')->on('users')->onDelete('restrict');
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('asset_inventory_logs');
    }
}
