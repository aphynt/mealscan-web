<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 50);
            $table->enum('meal_type', ['breakfast', 'lunch', 'dinner']);
            $table->enum('status', ['present', 'absent', 'late'])->default('present');
            $table->integer('quantity')->default(1)->comment('Jumlah makanan yang diambil');
            $table->integer('rating')->nullable();
            $table->integer('sys_post')->nullable();
            $table->text('remarks')->nullable()->comment('Saran dari karyawan');
            $table->string('created_by')->nullable();

            $table->date('attendance_date');
            $table->timestamp('attendance_time')->useCurrent();
            $table->float('similarity_score')->nullable();
            $table->float('confidence_score')->nullable();
            $table->timestamps();

            $table->foreign('nik')->references('nik')->on('employees')->onDelete('cascade');
            $table->unique(['nik', 'meal_type', 'attendance_date'], 'unique_attendance');

            $table->index('attendance_date');
            $table->index('nik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
