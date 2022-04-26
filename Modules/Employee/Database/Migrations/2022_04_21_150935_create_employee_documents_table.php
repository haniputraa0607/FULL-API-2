<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
    {
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->bigIncrements('id_employee_document');
            $table->unsignedInteger('id_employee');
            $table->string('document_type', 255);
            $table->dateTime('process_date')->nullable();
            $table->string('process_name_by')->nullable();
            $table->mediumText('process_notes')->nullable();
            $table->mediumText('attachment')->nullable();
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
        Schema::dropIfExists('employee_documents');
    }
}
