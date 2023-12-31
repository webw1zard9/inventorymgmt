<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_journals', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ledger_id')->nullable();
            $table->bigInteger('balance');
            $table->string('currency', 5);
            $table->string('morphed_type', 32);
            $table->integer('morphed_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_journals');
    }
};
