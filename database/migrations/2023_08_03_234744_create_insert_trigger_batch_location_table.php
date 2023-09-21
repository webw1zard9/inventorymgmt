<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared('DROP TRIGGER IF EXISTS insert_batch_location_trigger');

        DB::unprepared("
        CREATE TRIGGER insert_batch_location_trigger
        AFTER INSERT ON batch_location
        FOR EACH ROW
        BEGIN
        SET @batch_id := NEW.batch_id;
        CALL sync_batch_location_aggregate();
        END
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared('DROP TRIGGER IF EXISTS insert_batch_location_trigger');
    }
};
