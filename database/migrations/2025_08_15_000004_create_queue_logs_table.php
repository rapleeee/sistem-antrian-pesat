<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('presenter_id')->constrained('participants')->cascadeOnDelete();
            $table->foreignId('observer1_id')->nullable()->constrained('participants')->nullOnDelete();
            $table->foreignId('observer2_id')->nullable()->constrained('participants')->nullOnDelete();
            $table->enum('action', ['started', 'done', 'skipped']);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_logs');
    }
};
