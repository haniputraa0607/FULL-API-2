<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewEnumStatusRequestEmployeeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE `request_employees` CHANGE `status` `status` ENUM("Request","Approved", "Rejected", "Done Approved", "Finished") default "Request" NOT NULL;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `request_employees` CHANGE `status` `status` ENUM("Request","Approved", "Rejected", "Done Approved") default "Request" NOT NULL;');
    }
}
