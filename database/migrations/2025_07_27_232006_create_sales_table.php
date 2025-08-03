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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->dateTime('sale_date');
            $table->unsignedBigInteger('employee_id')->nullable(true);
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('cash_register_id');
            $table->decimal('total',10,2);
            $table->boolean('is_check')->default(false);
            $table->enum('method_of_payment',['efectivo','tarjeta']);
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('cash_register_id')->references('id')->on('cash_registers')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
