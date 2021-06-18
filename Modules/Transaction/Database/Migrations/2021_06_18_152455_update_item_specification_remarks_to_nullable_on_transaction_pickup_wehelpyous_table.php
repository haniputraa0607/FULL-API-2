<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateItemSpecificationRemarksToNullableOnTransactionPickupWehelpyousTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_pickup_wehelpyous', function (Blueprint $table) {
        	$table->text('item_specification_remarks')->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_pickup_wehelpyous', function (Blueprint $table) {
        	$table->text('item_specification_remarks')->nullable(false)->change();
        });
    }
}
