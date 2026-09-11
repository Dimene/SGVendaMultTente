<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();

            $table->integer('empresa_id')->nullable();
                // ->constrained('empresas')
                // ->cascadeOnDelete();

            $table->integer('categoria_id')
                ->nullable();
                // ->constrained('categorias')
                // ->nullOnDelete();

            $table->string('codigo')->nullable();
            $table->string('nome');

            $table->text('descricao')->nullable();

            $table->decimal('preco_custo', 15, 2)->default(0);
            $table->decimal('preco_venda', 15, 2)->default(0);

            $table->integer('estoque_atual')->default(0);
            $table->integer('estoque_minimo')->default(0);

            $table->boolean('ativo')->default(true);

            $table->timestamps();

            $table->unique(['codigo', 'empresa_id']);
            $table->index('nome');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
