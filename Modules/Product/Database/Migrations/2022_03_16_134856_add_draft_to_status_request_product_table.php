<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDraftToStatusRequestProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE `request_products` CHANGE `status` `status` ENUM("Draft","Pending","Completed By User","Completed By Finance","Rejected") default "Draft" NOT NULL;');

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `request_products` CHANGE `status` `status` ENUM("Pending","Completed By User","Completed By Finance","Rejected") default "Pending" NOT NULL;');
    }
}
