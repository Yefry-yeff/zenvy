<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MenuController;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

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

Route::get('/logout', function () {
    Auth::logout();
    Session::flush();
    return redirect('/login');
})->name('logout');

Route::get('/register', function () {
    return redirect('/login')->with('status', 'El registro está deshabilitado. Contacta al administrador.');
})->name('register');


Route::get('/menus/data', [MenuController::class, 'data'])->name('menus.data');

require __DIR__.'/auth.php';

Route::post('/debug-log', function (Request $request) {
    Log::debug('📩 [JS DEBUG] ' . $request->input('mensaje'));
    return response()->json(['status' => 'ok']);
});

