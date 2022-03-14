<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductHairstylistCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_hairstylist_categories', function (Blueprint $table) {
            $table->bigIncrements('id_product_hairstylist_category');
            $table->unsignedInteger('id_product');
            $table->unsignedInteger('id_hairstylist_category');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_hairstylist_categories');
    }
}
