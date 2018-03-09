<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Locations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id', false, true);
            $table->string('address');
            $table->string('phone')->nullable();
            $table->decimal('lat', 8, 6)->nullable();
            $table->decimal('lng', 8, 6)->nullable();
            $table->foreign('company_id')->references('id')->on('companies')
                ->onUpdate('cascade')->onDelete('cascade');
        });

        Schema::table('menu_items', function ($table) {
            $table->integer('location_id', false, true)->nullable();
        });

        Schema::table('orders', function ($table) {
            $table->integer('location_id', false, true)->nullable();
        });

        Schema::table('users', function ($table) {
            $table->integer('location_id', false, true)->nullable();
        });

        Schema::table('device_tokens', function ($table) {
            $table->integer('location_id', false, true)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('menu_items', function ($table) {
            $table->dropColumn(['location_id']);
        });

        Schema::table('orders', function ($table) {
            $table->dropColumn(['location_id']);
        });

        Schema::table('users', function ($table) {
            $table->dropColumn(['location_id']);
        });

        Schema::table('device_tokens', function ($table) {
            $table->dropColumn(['location_id']);
        });

        Schema::drop('locations');
    }
}
