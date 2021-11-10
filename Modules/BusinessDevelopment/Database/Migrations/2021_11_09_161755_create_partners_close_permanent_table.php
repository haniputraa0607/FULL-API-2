<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePartnersClosePermanentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('partners_close_permanent', function (Blueprint $table) {
            $table->increments('id_partners_close_permanent');
            $table->integer('id_partner')->unsigned();
            $table->string('title',255);
            $table->text('note')->nullable();
            $table->dateTime('close_date')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->enum('status',['Process','Waiting','Success','Reject'])->default('Process');
            $table->timestamps();
            $table->foreign('id_partner', 'fk_partner_close_permanent')->references('id_partner')->on('partners')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('partners_close_permanent');
    }
}
