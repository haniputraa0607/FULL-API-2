<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOutletCutOffTable extends Migration
{
    public function up()
    {
        Schema::create('outlet_cut_off', function (Blueprint $table) {
            $table->increments('id_outlet_cut_off');
            $table->integer('id_partner')->unsigned();
            $table->integer('id_outlet')->unsigned();
            $table->string('title',255);
            $table->text('note')->nullable();
            $table->dateTime('date')->nullable();
            $table->enum('status',['Process',"Waiting",'Success','Reject'])->default('Process');
            $table->timestamps();
            $table->foreign('id_partner', 'fk_partner_outlet_cut_off')->references('id_partner')->on('partners')->onDelete('restrict');
            $table->foreign('id_outlet', 'fk_outlet_cut_off')->references('id_outlet')->on('outlets')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('outlet_cut_off');
    }
}
