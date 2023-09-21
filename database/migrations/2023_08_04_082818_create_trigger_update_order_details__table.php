<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared('DROP TRIGGER IF EXISTS update_order_detail_fulfilled_trigger');

        DB::unprepared("
        CREATE TRIGGER update_order_detail_fulfilled_trigger
        AFTER UPDATE ON order_details
        FOR EACH ROW
        BEGIN
        IF ((OLD.units_fulfilled IS NULL AND NEW.units_fulfilled IS NOT NULL) OR 
            (NEW.units_fulfilled IS NULL AND OLD.units_fulfilled IS NOT NULL) OR 
            (OLD.units_fulfilled <> NEW.units_fulfilled)
            ) THEN
        SET @batch_id := NEW.batch_id;
            CALL sync_batch_location_aggregate();
        END IF;
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
        DB::unprepared('DROP TRIGGER IF EXISTS update_order_detail_fulfilled_trigger');
    }
};
