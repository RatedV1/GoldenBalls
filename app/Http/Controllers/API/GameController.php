<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GameController as ControllersGameController;
use App\Models\Game;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function getGameState($code)
    {
        $game = Game::where('code', $code)->first();
        if(!$game) {
            return response()->json([
                'message' => 'Game not found'
            ], 404);
        }
        $gameController = new ControllersGameController();
        $players = $gameController->getPlayers($game);
        if ($players->count() < 4) {
            return response()->json([
                'message' => 'Game not ready'
            ], 404);
        }
        $data['players'] = $players;
        $data['game'] = $game;
        $round = 0;
        $data['player_turn'] = null;
        if($game->getAlivePlayers()->count() == 4){
            $round = 1;
            $data['player_turn'] = $gameController->getPlayerTurn($game);
        }
        if($game->getAlivePlayers()->count() == 3){
            $round = 2;
            $data['player_turn'] = $gameController->getPlayerTurn($game);
        }
        if($game->getAlivePlayers()->count() == 2){
            $round = 3;
            $data['player_turn'] = $gameController->getBinOrWinPlayerTurn($game);
        }
        $data['round'] = $round;
        $balls = $game->playerAllBalls();
        $data['balls'] = $balls;
        return response()->json($data, 200);
    }
}
