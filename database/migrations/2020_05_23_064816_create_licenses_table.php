<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::create('licenses', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('user_id')->unsigned()->index()->nullable();
            $table->foreign('user_id')->references('id')->on('users');

            $table->integer('license_type_id')->unsigned()->index();
            $table->foreign('license_type_id')->references('id')->on('license_types');

            $table->string('number');
            $table->string('legal_business_name');
            $table->string('premise_address');
            $table->string('premise_address2')->nullable();
            $table->string('premise_city');
            $table->string('premise_state')->default('CA');
            $table->string('premise_zip');

            $table->date('valid');
            $table->date('expires');

            $table->string('link')->nullable();

            $table->tinyInteger('active')->default(1);

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
        Schema::dropIfExists('licenses');
    }
};
