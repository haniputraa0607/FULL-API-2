<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOutletCloseTemporaryTable extends Migration
{
    public function up()
    {
        Schema::create('outlet_close_temporary', function (Blueprint $table) {
            $table->increments('id_outlet_close_temporary');
            $table->integer('id_partner')->unsigned();
            $table->integer('id_outlet')->unsigned();
            $table->string('title',255);
            $table->text('note')->nullable();
            $table->dateTime('date')->nullable();
            $table->enum('status',['Process',"Waiting",'Success','Reject'])->default('Process');
            $table->enum('jenis',['Close',"Aktive"])->default('Close');
            $table->timestamps();
            $table->foreign('id_partner', 'fk_partner_outlet_close_temporary')->references('id_partner')->on('partners')->onDelete('restrict');
            $table->foreign('id_outlet', 'fk_outlet_close_temporary')->references('id_outlet')->on('outlets')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('outlet_close_temporary');
    }
}
