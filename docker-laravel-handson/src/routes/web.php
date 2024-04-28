<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\ActionController;
use App\Http\Controllers\ContactFormController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('tests/test', [TestController::class, 'index']);
Route::resource('goals', GoalController::class);
Route::resource('actions', ActionController::class); 

// ルーティングのグループ化
Route::prefix('contact')->middleware(['auth'])
->controller(ContactFormController::class)
->name('cpntacts.')
->group(function() {
    Route::get('/', 'index')->name('index');
});
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
