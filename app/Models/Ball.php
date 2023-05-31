<!--GoldenBalls/app/Models/Ball.php-->
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ball extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'number',
        'is_killer',
    ];

    public function players()
    {
        return $this->belongsToMany(Player::class, 'balls_players')->withPivot(['is_revealed', 'is_binned', 'is_winned']);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function isKiller()
    {
        return $this->is_killer;
    }

    public function isNotKiller()
    {
        return !$this->isKiller();
    }

    
}
