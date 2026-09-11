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
       Schema::create('fornecedores', function (Blueprint $table) {

    $table->id();

    $table->integer('empresa_id')->nullable();
    //     ->constrained('empresas')
    //     ->cascadeOnDelete();

    $table->string('nome');

    $table->string('documento')->nullable();

    $table->string('email')->nullable();

    $table->string('telefone')->nullable();
    $table->string('celular')->nullable();

    $table->string('contato_nome')->nullable();

    $table->text('endereco')->nullable();

    $table->string('cidade')->nullable();
    $table->string('provincia')->nullable();

    $table->text('observacoes')->nullable();

    $table->boolean('ativo')->default(true);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fornecedors');
    }
};
