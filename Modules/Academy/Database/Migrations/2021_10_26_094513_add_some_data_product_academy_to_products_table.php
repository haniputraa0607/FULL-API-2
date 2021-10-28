<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeDataProductAcademyToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('product_academy_hours_meeting')->nullable()->after('processing_time_service');
            $table->integer('product_academy_total_meeting')->nullable()->after('processing_time_service');
            $table->integer('product_academy_duration')->nullable()->after('processing_time_service');
            $table->string('product_short_description', 255)->nullable()->after('product_name_pos');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('product_academy_hours_meeting');
            $table->dropColumn('product_academy_total_meeting');
            $table->dropColumn('product_academy_duration');
            $table->dropColumn('product_short_description');
        });
    }
}
