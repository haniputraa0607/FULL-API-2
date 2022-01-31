<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateManageOutletTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outlet_manage', function (Blueprint $table) {
            $table->bigIncrements('id_outlet_manage');
            $table->integer('id_partner')->nullable();
            $table->integer('id_outlet')->nullable();
            $table->date('date')->nullable();
            $table->enum('type',['Cut Off','Change Ownership','Change Location','Close Temporary','Active Temporary'])->nullable();
            $table->enum('status',['Process','Waiting','Success','Reject'])->default('Process');
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
        Schema::dropIfExists('outlet_manage');
    }
}
