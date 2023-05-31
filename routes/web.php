<?php

use App\Http\Controllers\GameController;
use App\Http\Livewire\Game as LivewireGame;
use App\Models\Ball;
use App\Models\Game;
use Illuminate\Support\Facades\Route;

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

Route::any('/webhook', [GameController::class, 'getTelegramMessage']);

Route::get('/game/{code}', [GameController::class, 'index'])->name('game.index');
Route::get('/game/overlay/{code}', [GameController::class, 'overlay'])->name('game.overlay');

Route::get('/test', function(){
    $game = Game::find(11);
    return collect($game->playerAllBalls())->map(function ($ball) {
        return collect($ball)['number'];
    })->sort()->values()->all();
});
