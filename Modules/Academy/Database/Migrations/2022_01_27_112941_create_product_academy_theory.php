<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductAcademyTheory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_academy_theory', function (Blueprint $table) {
            $table->bigIncrements('id_product_academy_theory');
            $table->unsignedInteger('id_product');
            $table->unsignedInteger('id_theory');
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
        Schema::dropIfExists('product_academy_theory');
    }
}
