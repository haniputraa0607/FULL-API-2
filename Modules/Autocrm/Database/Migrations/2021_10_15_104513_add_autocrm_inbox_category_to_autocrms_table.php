<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAutocrmInboxCategoryToAutocrmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('autocrms', function (Blueprint $table) {
    		$table->string('autocrm_inbox_category')->nullable()->after('autocrm_inbox_id_reference');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('autocrms', function (Blueprint $table) {
        	$table->dropColumn('autocrm_inbox_category');
        });
    }
}
