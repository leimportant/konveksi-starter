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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->date('payroll_date')->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->decimal('total_gaji', 20, 6)->default(0);
            $table->decimal('uang_makan', 20, 6)->default(0);
            $table->decimal('lembur', 20, 6)->default(0);
            $table->decimal('potongan', 20, 6)->default(0);
            $table->decimal('net_gaji', 20, 6)->virtualAs('total_gaji + uang_makan + lembur - potongan');
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
