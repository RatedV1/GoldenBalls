<?php

namespace App\Http\Livewire;

use Livewire\Component;

class Player extends Component
{
    public $p;
    public $player;
    public $player_turn;
    public $round;
    public function mount($p, $player, $player_turn, $round)
    {
        $this->p = $p;
        $this->player = $player;
        $this->player_turn = $player_turn;
        $this->round = $round;
    }

    public function render()
    {
        return view('livewire.player');
    }
}
