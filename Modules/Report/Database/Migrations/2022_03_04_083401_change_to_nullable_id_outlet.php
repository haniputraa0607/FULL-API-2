<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeToNullableIdOutlet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE daily_report_trx MODIFY id_outlet INTEGER null default 0;");
        \DB::statement("ALTER TABLE monthly_report_trx MODIFY id_outlet INTEGER null default 0;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE daily_report_trx MODIFY id_outlet INTEGER default 0;");
        \DB::statement("ALTER TABLE monthly_report_trx MODIFY id_outlet INTEGER default 0;");
    }
}
