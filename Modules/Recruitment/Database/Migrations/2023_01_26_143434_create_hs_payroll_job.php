<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsPayrollJob extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_payroll_queues', function (Blueprint $table) {
            $table->bigIncrements('id_hairstylist_payroll_queue');
            $table->string('month')->nullable();
            $table->string('year')->nullable();
            $table->text('message')->nullable();
            $table->enum('type', array('middle', 'end'))->default('end');
            $table->enum('status_export', array('Running', 'Ready'))->nullable();
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
        Schema::dropIfExists('hairstylist_payroll_queues');
    }
}
