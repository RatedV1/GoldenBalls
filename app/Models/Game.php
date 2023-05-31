<!--GoldenBalls/app/Models/Game.php-->
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'host_chat_id'
    ];

    public static function generateCode()
    {
        $code = '';
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        for ($i = 0; $i < 6; $i++) {
            $code .= $characters[rand(0, $charactersLength - 1)];
        }
        return $code;
    }

    public static function createBalls($min, $max)
    {
        $balls = [];
        for ($i = 1; $i <= 100; $i++) {
            $balls[] = [
                'number' => rand($min, $max),
                'is_killer' => false,
            ];
        }

        for ($i = 1; $i <= 10; $i++) {
            $balls[] = [
                'number' => 0,
                'is_killer' => true,
            ];
        }
        
        shuffle($balls);

        return $balls;
    }

    public function balls()
    {
        return $this->hasMany(Ball::class);
    }

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function playerBalls(){
        return $this->players()->where('is_alive', true)->with('balls')->get()->pluck('balls')->flatten();
    }

    public function playerAllBalls()
    {
        $balls = $this->players()->with('balls')->get()->pluck('balls')->flatten()->toArray();
        // sort by id
        usort($balls, function ($a, $b) {
            return $a['id'] <=> $b['id'];
        });
        return $balls;
        
    }

    public function getKillerBall()
    {
        return $this->balls()->where('is_killer', true)->first();
    }

    public function getNonKillerBalls()
    {
        return $this->balls()->where('is_killer', false)->get();
    }

    public function getAlivePlayers()
    {
        return $this->players()->where('is_alive', true)->get();
    }

    public function getDeadPlayers()
    {
        return $this->players()->where('is_alive', false)->get();
    }

    public function getWinnerPlayer()
    {
        return $this->players()->where('is_winner', true)->first();
    }
}
