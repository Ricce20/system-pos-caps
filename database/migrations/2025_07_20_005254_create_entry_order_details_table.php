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
        Schema::create('entry_order_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entry_order_id');
            $table->unsignedBigInteger('item_id');
            $table->integer('quantity');
            $table->decimal('subtotal',10,2)->nullable(true);
            $table->softDeletes();
            $table->timestamps();
            
            // Claves foráneas
            $table->foreign('entry_order_id')->references('id')->on('entry_orders')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entry_order_details');
    }
};
