<?php

namespace App\Http\Livewire;

use App\Http\Controllers\GameController;
use App\Models\Game as ModelsGame;
use Livewire\Component;

class Overlay extends Component
{

    public $code;
    public $game;
    public $data;
    public $players;
    public $player_turn;
    public $round;
    public $balls;
    public $game_ready = false;
    public function mount($code)
    {
        $this->code = $code;
        $this->game = ModelsGame::where('code', $code)->first();
        $this->refresh();
    }

    public function refresh()
    {
        $gameController = new GameController();
        $game = $this->game;
        $players = $gameController->getPlayers($game);
        if ($players->count() < 4) {
            $this->game_ready = false;
        } else if ($players->count() == 4) {
            $this->game_ready = true;
        }
        $this->players = $players;
        $round = 0;
        if ($game->getAlivePlayers()->count() == 4) {
            $round = 1;
            $this->player_turn = $gameController->getPlayerTurn($game);
        }
        if ($game->getAlivePlayers()->count() == 3) {
            $round = 2;
            $this->player_turn = $gameController->getPlayerTurn($game);
        }
        if ($game->getAlivePlayers()->count() == 2) {
            $round = 3;
            $this->player_turn = $gameController->getBinOrWinPlayerTurn($game);
        }
        $this->round = $round;
        $balls = $game->playerAllBalls();
        $this->balls = $balls;
    }

    public function render()
    {
        return view('livewire.overlay');
    }
}
