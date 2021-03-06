<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id', false, true)->nullable();
            $table->string('name')->nullable();
            $table->string('email')
                ->unique()
                ->nullable();
            $table->string('phone')
                ->unique()
                ->nullable();
            $table->string('password')->nullable();
            $table->string('api_token')
                ->unique()
                ->nullable();
            $table->string('verify_code')->nullable();
            $table->string('role');
            $table->rememberToken();
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
        Schema::drop('users');
    }
}
