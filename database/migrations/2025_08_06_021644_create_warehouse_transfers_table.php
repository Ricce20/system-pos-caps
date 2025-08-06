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
        Schema::create('warehouse_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number')->unique()->comment('Número único de transferencia');
            $table->unsignedBigInteger('source_warehouse_id')->comment('Almacén origen');
            $table->unsignedBigInteger('destination_warehouse_id')->comment('Almacén destino');
            // $table->unsignedBigInteger('item_id')->comment('Producto a transferir');
            // $table->integer('quantity')->comment('Cantidad a transferir');
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending')->comment('Estado de la transferencia');
            $table->text('notes')->nullable()->comment('Notas adicionales');
            $table->unsignedBigInteger('created_by')->comment('Usuario que creó la transferencia');
            $table->unsignedBigInteger('approved_by')->nullable()->comment('Usuario que aprobó la transferencia');
            $table->timestamp('approved_at')->nullable()->comment('Fecha de aprobación');
            $table->timestamp('completed_at')->nullable()->comment('Fecha de completado');
            $table->timestamps();
            $table->softDeletes();

            // Claves foráneas
            $table->foreign('source_warehouse_id')->references('id')->on('warehouses')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('destination_warehouse_id')->references('id')->on('warehouses')->onDelete('restrict')->onUpdate('cascade');
            // $table->foreign('item_id')->references('id')->on('items')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');

            // Índices para mejorar el rendimiento
            $table->index(['source_warehouse_id', 'status']);
            $table->index(['destination_warehouse_id', 'status']);
            $table->index(['status']);
            $table->index('transfer_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_transfers');
    }
};
