<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->dateTime('time')->index();
            $table->uuid('target_id')->nullable()->index()
                ->comment('profile_id, group_id');
            $table->string('target_type', 32)->nullable()->index()
                ->comment('group, profile, proxy, ...');
            $table->uuid('user_id')->nullable()->index();
            $table->string('type', 16)->index()
                ->comment('info, warn, error');
            $table->text('message')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('logs');
    }
};
