<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDesignRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('design_requests', function (Blueprint $table) {
            $table->increments('id_design_request');
            $table->unsignedInteger('id_request');
            $table->string('title');
            $table->date('required_date');
            $table->text('required_note');
            $table->unsignedInteger('id_approve')->nullable();
            $table->date('update_status_date')->nullable();
            $table->date('estimated_date')->nullable();
            $table->string('design_path')->nullable();
            $table->text('finished_note')->nullable();
            $table->enum('status',['Pending','Approved','Rejected','Finished','Done Finished','Provided'])->default('Pending');
            $table->timestamps();

            $table->foreign('id_request', 'fk_design_user_request')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('id_approve', 'fk_design_user_approve')->references('id')->on('users')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('design_requests');
    }
}
