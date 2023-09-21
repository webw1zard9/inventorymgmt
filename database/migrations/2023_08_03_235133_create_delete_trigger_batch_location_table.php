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
        DB::unprepared('DROP TRIGGER IF EXISTS delete_batch_location_trigger');

        DB::unprepared("
        CREATE TRIGGER delete_batch_location_trigger
        AFTER DELETE ON batch_location
        FOR EACH ROW
        BEGIN
        SET @batch_id := OLD.batch_id;
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
        DB::unprepared('DROP TRIGGER IF EXISTS delete_batch_location_trigger');
    }
};
