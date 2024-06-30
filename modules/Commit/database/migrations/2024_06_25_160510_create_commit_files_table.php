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
        Schema::create('commit_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commit_id')->constrained()->onDelete('cascade');
            $table->string('filename');
            $table->enum('status', ['added', 'modified', 'removed', 'renamed']);
            $table->text('changes');
            $table->boolean('meaningful')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commit_files');
    }
};