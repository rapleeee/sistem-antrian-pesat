<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('grade', ['10', '11', '12']);
            $table->enum('major', ['RPL', 'DKV', 'TKJ']);
            $table->enum('status', ['inactive', 'active', 'closed'])->default('inactive');
            $table->string('operator_pin', 6)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panels');
    }
};
