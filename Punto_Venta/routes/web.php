<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    //return view('welcome');
    return redirect('/login');
});

Route::get('/dashboard', function () {
    return view('layouts.app');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::get('/productos', [ProductoController::class, 'productos'])->name('inventario.productos');
Route::get('/categorias', [CategoriaController::class, 'categorias'])->name('inventario.categorias');
Route::get('/ventas/nueva', [VentaController::class, 'create'])->name('ventas.create');
Route::get('/ventas', [VentaController::class, 'index'])->name('ventas.index');
Route::get('/inventario', [InventarioController::class, 'index'])->name('inventario.index');
Route::get('/usuarios', [UsuariosController::class, 'index'])->name('usuarios.index');
Route::get('/usuariosc	', [UsuariosCreateController::class, 'index'])->name('usuarios.create');
