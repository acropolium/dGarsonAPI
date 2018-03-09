<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MenuTranslate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu_item_translations', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('menu_item_id')->unsigned();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('locale')->index();

            $table->unique(['menu_item_id','locale']);
            $table->foreign('menu_item_id')->references('id')->on('menu_items')->onDelete('cascade');
        });


        Schema::table('menu_items', function ($table) {
            $table->dropColumn(['name', 'description']);
        });

        Schema::create('menu_item_option_translations', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('menu_item_option_id')->unsigned();
            $table->string('name');
            $table->string('locale')->index();

            $table->unique(['menu_item_option_id','locale']);
            $table->foreign('menu_item_option_id')->references('id')->on('menu_item_options')->onDelete('cascade');
        });

        Schema::table('menu_item_options', function ($table) {
            $table->dropColumn(['name']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('menu_item_options', function ($table) {
            $table->string('name');
        });

        Schema::table('menu_items', function ($table) {
            $table->string('name');
            $table->string('description')->nullable();
        });
        Schema::drop('menu_item_option_translations');
        Schema::drop('menu_item_translations');
    }
}
