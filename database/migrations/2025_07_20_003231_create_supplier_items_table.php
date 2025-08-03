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
        Schema::create('supplier_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('item_id');
            $table->boolean('is_available')->default(true);
            $table->decimal('purchase_price',10,2);
            $table->decimal('sale_price',10,2);
            $table->boolean('is_primary')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['supplier_id','item_id']);
            $table->unique(['supplier_id','item_id','is_primary']);

            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_items');
    }
};
