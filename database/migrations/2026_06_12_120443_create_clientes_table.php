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
        Schema::create('clientes', function (Blueprint $table) {

    $table->id();

    $table->integer('empresa_id')->nullable();
        // ->constrained('empresas')
        // ->cascadeOnDelete();

    $table->string('nome');

    $table->string('documento')->nullable();
    $table->string('email')->nullable();

    $table->string('telefone')->nullable();
    $table->string('celular')->nullable();

    $table->text('endereco')->nullable();

    $table->string('cidade')->nullable();
    $table->string('provincia')->nullable();

    $table->decimal('limite_credito',15,2)
        ->default(0);

    $table->enum('status',[
        'ATIVO',
        'INATIVO',
        'BLOQUEADO'
    ])->default('ATIVO');

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
