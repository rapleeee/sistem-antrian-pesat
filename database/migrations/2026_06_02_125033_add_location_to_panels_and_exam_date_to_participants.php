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
        Schema::table('panels', function (Blueprint $table) {
            $table->string('location')->nullable()->after('operator_pin');
        });

        Schema::table('participants', function (Blueprint $table) {
            $table->date('exam_date')->nullable()->after('student_number');
            // role: presenter = orang yang presentasi, observer = penonton tetap dari group ini
            $table->enum('role', ['presenter', 'observer'])->default('presenter')->after('exam_date');
            // group_order: urutan kelompok (1 kelompok = 1 presenter + 2 observer dari import)
            $table->unsignedSmallInteger('group_order')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('panels', function (Blueprint $table) {
            $table->dropColumn('location');
        });

        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn(['exam_date', 'role', 'group_order']);
        });
    }
};
