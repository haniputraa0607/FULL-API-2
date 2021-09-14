<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOutletBoxTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outlet_box', function (Blueprint $table) {
            $table->bigIncrements('id_outlet_box');
            $table->unsignedInteger('id_outlet');
            $table->string('outlet_box_code');
            $table->string('outlet_box_name');
            $table->enum('outlet_box_status', ['Active', 'Inactive'])->default('Active');
            $table->smallInteger('outlet_box_use_status')->default(0);
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
        Schema::dropIfExists('outlet_box');
    }
}
