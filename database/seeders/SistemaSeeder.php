<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

use App\Models\Empresa;
use App\Models\Usuario;
use App\Models\Fornecedor;
use App\Models\Cliente;
use App\Models\Categoria;
use App\Models\Produto;

class SistemaSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | EMPRESA
        |--------------------------------------------------------------------------
        */
        $empresa = Empresa::create([
            'nome' => 'ERP Comercial',
            'nome_fantasia' => 'ERP Comercial',
            'documento' => '400123456',
            'email' => 'admin@erp.com',
            'telefone' => '840000000',
            'cidade' => 'Maputo',
            'provincia' => 'Maputo',
            'pais' => 'Moçambique',
            'ativo' => true
        ]);

        /*
        |--------------------------------------------------------------------------
        | USUÁRIO ADMIN
        |--------------------------------------------------------------------------
        */
        Usuario::create([
            'empresa_id' => $empresa->id,
            'nome' => 'Administrador',
            'email' => 'admin@erp.com',
            'password' => Hash::make('123456'),
            'telefone' => '840000000',
            'cargo' => 'Administrador',
            'ativo' => true
        ]);

        /*
        |--------------------------------------------------------------------------
        | CLIENTES
        |--------------------------------------------------------------------------
        */
        Cliente::insert([
            [
                'empresa_id' => $empresa->id,
                'nome' => 'Cliente Consumidor',
                'telefone' => '840111111',
                'cidade' => 'Maputo',
                'status' => 'ATIVO',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'empresa_id' => $empresa->id,
                'nome' => 'Cliente Teste',
                'telefone' => '840222222',
                'cidade' => 'Matola',
                'status' => 'ATIVO',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        /*
        |--------------------------------------------------------------------------
        | FORNECEDORES
        |--------------------------------------------------------------------------
        */
        Fornecedor::insert([
            [
                'empresa_id' => $empresa->id,
                'nome' => 'Fornecedor Geral',
                'telefone' => '840333333',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'empresa_id' => $empresa->id,
                'nome' => 'Fornecedor Informática',
                'telefone' => '840444444',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        /*
        |--------------------------------------------------------------------------
        | CATEGORIAS
        |--------------------------------------------------------------------------
        */
        Categoria::insert([
            [
                'empresa_id' => $empresa->id,
                'nome' => 'Informática',
                'tipo' => 'PRODUTO',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'empresa_id' => $empresa->id,
                'nome' => 'Material de Escritório',
                'tipo' => 'PRODUTO',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        /*
        |--------------------------------------------------------------------------
        | PRODUTOS
        |--------------------------------------------------------------------------
        */
        $categoria = Categoria::first();

        Produto::insert([
            [
                'empresa_id' => $empresa->id,
                'categoria_id' => $categoria->id,
                'codigo' => 'PROD001',
                'nome' => 'Mouse USB',
                'preco_custo' => 250,
                'preco_venda' => 500,
                'estoque_atual' => 50,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'empresa_id' => $empresa->id,
                'categoria_id' => $categoria->id,
                'codigo' => 'PROD002',
                'nome' => 'Teclado USB',
                'preco_custo' => 400,
                'preco_venda' => 800,
                'estoque_atual' => 30,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
