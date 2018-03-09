<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDataModel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('logo')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('currency', 20);
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id', false, true);
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('volume')->nullable();
            $table->decimal('price')->nullable();
            $table->foreign('company_id')->references('id')->on('companies')
                ->onUpdate('cascade')->onDelete('cascade');
        });

        Schema::create('menu_item_options', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('menu_item_id', false, true);
            $table->string('name');
            $table->decimal('price')->nullable();
            $table->integer('count')->default(0);
            $table->foreign('menu_item_id')->references('id')->on('menu_items')
                ->onUpdate('cascade')->onDelete('cascade');

        });

        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id', false, true);
            $table->integer('user_id', false, true);
            $table->string('state');
            $table->decimal('cost')->nullable();
            $table->binary('items');
            $table->timestamps();
            $table->foreign('company_id')->references('id')->on('companies')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')
                ->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('orders');
        Schema::drop('menu_item_options');
        Schema::drop('menu_items');
        Schema::drop('companies');
    }
}
