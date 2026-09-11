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
      Schema::create('compras', function (Blueprint $table) {
    $table->id();
    $table->integer('empresa_id')->nullable();
    $table->integer('fornecedor_id');
    $table->integer('usuario_id')->nullable();

    $table->string('numero_pedido')->nullable();
    $table->enum('status', ['PENDENTE','APROVADO','ENTREGUE','CANCELADO'])->default('PENDENTE');

    $table->decimal('subtotal', 15, 2)->default(0);
    $table->decimal('total', 15, 2)->default(0);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
