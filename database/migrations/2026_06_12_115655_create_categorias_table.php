<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {

            $table->id();


            $table->integer('pai_id')
                ->nullable();
                // ->constrained('categorias')
                // ->nullOnDelete();

            $table->string('nome',100);

            $table->text('descricao')->nullable();

            $table->enum('tipo',[
                'PRODUTO',
                'SERVICO',
                'AMBOS'
            ])->default('PRODUTO');

            $table->integer('ordem')->default(0);

            $table->boolean('ativo')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
