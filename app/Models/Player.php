<!--GoldenBalls/app/Models/Player.php-->
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_alive',
        'is_winner',
        'chat_id',
        'game_id'
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function balls()
    {
        return $this->belongsToMany(Ball::class, 'balls_players')->withPivot(['is_revealed', 'is_binned', 'is_winned']);
    }

    public function isAlive()
    {
        return $this->is_alive;
    }

    public function isDead()
    {
        return !$this->isAlive();
    }

    public function isWinner()
    {
        return $this->is_winner;
    }


}
