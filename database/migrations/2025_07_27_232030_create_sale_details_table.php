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
        Schema::create('sale_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('item_id');
            $table->integer('quantity');
            $table->decimal('subtotal');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_details');
    }
};
