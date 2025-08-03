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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name',50)->unique(true);
            $table->string('description',255)->nullable();
            $table->boolean('is_available')->default(true);
            $table->unsignedBigInteger('brand_id');
            $table->unsignedBigInteger('model_cap_id');
            $table->unsignedBigInteger('category_id');
            $table->string('image_1')->nullable(true);
            $table->string('image_2')->nullable(true);
            $table->string('image_3')->nullable(true);
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('model_cap_id')->references('id')->on('model_caps')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
