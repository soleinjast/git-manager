<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('commits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('repository_id');
            $table->string('sha');
            $table->string('message');
            $table->string('author');
            $table->timestamp('date');
            $table->string('author_git_id')->nullable();
            $table->boolean('is_first_commit');
            $table->timestamps();
            $table->foreign('repository_id')->references('id')->on('repositories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commits');
    }
};
