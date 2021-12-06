<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('uploads')) {
            return;
        }

        Schema::create('uploads', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->string('key')->index();

            $table->foreignId('user_id')->constrained();
            $table->morphs('owner');

            $table->string('name');
            $table->string('extension');
            $table->string('type');
            $table->unsignedBigInteger('size');

            $table->timestamps();
            $table->timestamp('completed_at')->nullable();
        });
    }
};
