<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


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
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->string('profile_photo_path', 2048)->nullable();;
            $table->string('default_image', 2048)->default("8f5f271e-aea7-11ec-b909-0242ac120002.jpg");
            $table->string('oauth_id')->nullable();
            $table->string('oauth_type')->nullable();
            $table->string('phone');
            $table->integer('dogs_no')->default(0);
            $table->integer('cats_no')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
