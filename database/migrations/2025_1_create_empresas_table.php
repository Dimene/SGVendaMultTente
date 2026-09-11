<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {

            $table->id();

            $table->string('nome', 200);
            $table->string('nome_fantasia', 150)->nullable();

            $table->string('documento', 50)->nullable(); // NUIT

            $table->string('email')->nullable();
            $table->string('telefone', 30)->nullable();
            $table->string('celular', 30)->nullable();

            $table->text('endereco')->nullable();

            $table->string('cidade', 100)->nullable();
            $table->string('provincia', 100)->nullable();

            $table->string('pais', 100)->default('Moçambique');

            $table->string('site')->nullable();

            $table->string('logo')->nullable();

            $table->boolean('ativo')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
