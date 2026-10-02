<?php

use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\FornecedorController;
use App\Http\Controllers\homecontroller;
use App\Http\Controllers\EstoqueController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\permissoesController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\roleController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\vendascontroler;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
  
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION
    ]);
});

Route::post('/cadastrousuario', [UsuarioController::class, 'store'])
    ->middleware(['auth', 'verified'])->name('cadastrousuario');
    
  
// Route::get('/dashboard', function () {
   
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

Route::get('/', [HomeController::class, 'index'])
    ->name('dashboard');

Route::get('/dashboard', [HomeController::class, 'index'])
    ->name('dashboard.index');
    Route::get('/estoque', [EstoqueController::class, 'index'])->name('estoque.index');
    Route::get('/estoque/visualizar', [EstoqueController::class, 'visualizar'])->name('estoque.visualizar');
    Route::get('/configuracoes/empresa', [EmpresaController::class, 'index'])->name('configuracoes.empresa');
    Route::post('/configuracoes/empresa/guardar/{empresa}', [EmpresaController::class, 'update'])->name('configuracoes.empresa.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/compras/relatorios', [CompraController::class, 'relatorios'])->name('compras.relatorios');
    Route::get('/compras/relatorios/dados', [CompraController::class, 'relatorioDados'])->name('compras.relatorioDados');
    Route::resource('compras',CompraController::class);
     Route::get('/dadoscompra',[CompraController::class,"dadosCompra"])->name('compras.dados');

    Route::resource('fornecedor',FornecedorController::class);
    Route::get('/compras/fornecedores/all', [FornecedorController::class, 'index'])->name('compras.fornecedores');
    Route::resource('vendas',vendascontroler::class);
    Route::get('vendas/efetuar/{loja}',[vendascontroler::class,'create'])->name('vendas.efetuar');
    Route::get('vendas/efetuar/{loja}',[vendascontroler::class,'create'])->name('vendas.efetuar');
    
    Route::resource('clientes', ClienteController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('/vendas/reverter/vendas/{id}',[vendascontroler::class,'reverter'])->name('vendas.reverter');



    Route::get('/vendas/relatorios/mostrar',[vendascontroler::class,'relatorios'])->name('vendas.relatorios');
    Route::get('/vendas/passar/loja',[vendascontroler::class,'passarLoja'])->name('vendas.passarLoja');
    Route::post('/vendas/adicionar/lojas',[vendascontroler::class,'addicionarlojas'])->name('vendas.addicionarlojas');
    Route::get('/vendas/relatorio/dados/{id?}/{dataInicial?}/{DataFinal?}',
    [vendascontroler::class,'relatorioDados'])->name('vendas.relatorioDados');
    Route::resource('Produto',ProdutoController::class);
Route::post('/Produto', [ProdutoController::class, 'store']);
// routes/web.php
Route::get('/produtos/modelo/import/{grupo}', [ProdutoController::class, 'gerarModeloImport']);

    Route::get('/registo/produtos/{nome}',[ProdutoController::class,'create'])->name('produtos.create');
    // Route::post('/produtos/store',[ProdutoController::class,'store'])->name('produtos.store');
    Route::get("/categoria/atributos/{cat}",[CategoriaController::class,"atributos"]);
    Route::get("/categoria/create",[CategoriaController::class,"create"]);
    Route::post("/categoria/store",[CategoriaController::class,"store"])->name("categoria.store");

Route::resource('roles', roleController::class);

Route::get('roles/lista/dados', [roleController::class, 'index'])
    ->name('roles.lista.dados');


Route::get('roles/mostrar/elementos', [roleController::class, 'mostrar'])
    ->name('roles.mostrar');

    Route::resource('permissoes', permissoesController::class);
Route::delete('/produtos/{id}', [ProdutoController::class, 'destroy'])
    ->name('produtos.destroy');


    Route::get('/produtos/modelo/import{id}', [ProdutoController::class, 'import'])
    ->name('produtos.import');


    Route::resource('usuario',UsuarioController::class);
    Route::post('usuario/resete/{usuario}',[UsuarioController::class,'resetarSenha'])->name('usuario.resetarSenha');
    Route::get('usuario/resete/senha',[UsuarioController::class,'resetarSenhapessoal'])->name('usuario.resetarSenhapessoal');
    // Route::resource('contas', fechamentocontaController::class)
});


 require __DIR__.'/auth.php';
