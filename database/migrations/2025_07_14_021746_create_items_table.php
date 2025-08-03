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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('size_id');
            // $table->decimal('price')->default(0.0);
            $table->string('code',14)->unique(true);
            $table->string('barcode',255)->unique()->nullable(true);
            $table->string('qr')->nullable(true)->unique(true);
            $table->boolean('is_available')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['product_id','size_id']);
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('size_id')->references('id')->on('sizes')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
