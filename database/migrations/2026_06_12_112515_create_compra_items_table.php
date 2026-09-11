<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compras_itens', function (Blueprint $table) {
            $table->id();

            $table->integer('compra_id')->nullable();
                // ->constrained('compras')
                // ->cascadeOnDelete()
                ;

            $table->integer('produto_id')->nullable();
                // ->constrained('produtos')
                // ->restrictOnDelete();

            $table->integer('lote_id')
                ->nullable();
                // ->constrained('lotes')
                // ->nullOnDelete();

            $table->integer('quantidade');
            $table->integer('quantidade_recebida')->default(0);

            $table->decimal('preco_unitario', 15, 2);
            $table->decimal('desconto', 15, 2)->default(0);

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('imposto', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            $table->timestamps();

            $table->index('compra_id');
            $table->index('produto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras_itens');
    }
};
