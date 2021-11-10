<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_groups', function (Blueprint $table) {
        	$table->increments('id_product_group');
			$table->string('product_group_code', 191)->unique();
			$table->string('product_group_name', 191);
			$table->integer('id_product_category')->unsigned()->nullable()->index('fk_id_product_category_categories');
			$table->integer('product_group_position')->default(0);
			$table->text('product_group_description', 65535)->nullable();
			$table->string('product_group_photo', 150)->nullable();
			$table->string('product_group_image_detail', 191)->nullable();
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
        Schema::dropIfExists('product_groups');
    }
}
