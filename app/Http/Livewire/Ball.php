<?php

namespace App\Http\Livewire;

use Livewire\Component;

class Ball extends Component
{
    public $ball;
    public $rotate;
    public function mount($ball, $rotate)
    {
        $this->ball = $ball;
        $this->rotate = $rotate;
    }

    public function render()
    {
        return view('livewire.ball');
    }
}
