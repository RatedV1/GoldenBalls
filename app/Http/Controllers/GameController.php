<!--GoldenBalls/app/Http/Controllers/GameController.php-->
<?php

namespace App\Http\Controllers;

use App\Models\Ball;
use App\Models\Game;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use \Exception;
class GameController extends Controller
{

    public function index($code){
        return view('game', compact('code'));
    }

    public function overlay($code)
    {
        return view('overlay', compact('code'));
    }

    public function getGame($code)
    {
        return Game::where('code', $code)->where('is_end', false)->first();
    }

    public function getGameByHost($chat_id){
        $game = Game::where('host_chat_id', $chat_id)->where('is_end',false)->first();
        
        return $game;
    }

    public function createGame($chat_id)
    {
        $game = Game::create([
            'code' => Game::generateCode(),
            'host_chat_id' => $chat_id,
        ]);

        $balls = $game->createBalls(10, 1000);
        $game->balls()->createMany($balls);
        return $game;
    }

    public function createPlayer($game, $name, $chat_id)
    {
        return Player::create([
            'name' => $name,
            'chat_id' => $chat_id,
            'is_alive' => true,
            'is_winner' => false,
            'game_id' => $game->id
        ]);

    }

    public function getGamePlayerBalls($game)
    {
        return $game->playerBalls;
    }

    public function mixer($game){
        // get 12 non-killer balls
        $balls = $game->balls()->where('is_killer', false)->get()->random(12);
        $players = $this->getPlayers($game);
        if($players->count() < 4){
            return false;
        }
        // round 1 - 4 players alive - 4 balls each
        if($game->getAlivePlayers()->count() == 4){
            // check if players have balls
            $players = $game->players()->with('balls')->get();
            // count balls
            $current_balls_count = 0;
            $players->each(function($player) use (&$current_balls_count){
                $current_balls_count += $player->balls()->count();
            });
            // if balls count is 16, return false
            if($current_balls_count == 16){
                return false;
            }
            // get 4 killer balls
            $killerBalls = $game->balls()->where('is_killer', true)->get()->random(4);
            // add killer balls to balls
            $balls = $balls->merge($killerBalls);
            // shuffle balls
            $balls = $balls->shuffle();
            // assign balls to players
            // add 4 balls to each player
            $players->each(function($player) use (&$balls){
                $player->balls()->attach($balls->take(4));
                $balls = $balls->slice(4);
            });
            // total balls for 4 players = 16
        }
        // round 2 - 3 players alive - 5 balls each
        else if($game->getAlivePlayers()->count() == 3){
            // check if players have balls
            $players = $game->players()->where('is_alive')->with('balls')->get();
            // count balls
            $current_balls_count = 0;
            $players->each(function ($player) use (&$current_balls_count) {
                $current_balls_count += $player->balls()->count();
            });
            // if balls count is 16, return false
            if ($current_balls_count == 15) {
                return false;
            }
            // get balls from round 1 from alive players
            $alivePlayers = $game->players()->where('is_alive', true)->get();
            $balls = collect();
            $alivePlayers->each(function($player) use (&$balls){
                $balls = $balls->merge($player->balls()->get());
            });
            
            // get new 1 killer balls not in round 1
            $killerBalls = $game->balls()->where('is_killer', true)->whereNotIn('id', $balls->pluck('id'))->get()->random(1);
            // get 2 non-killer balls not in round 1
            $nonKillerBalls = $game->balls()->where('is_killer', false)->whereNotIn('id', $balls->pluck('id'))->get()->random(2);
            // add killer balls to balls
            $balls = $balls->merge($killerBalls);
            // add non-killer balls to balls
            $balls = $balls->merge($nonKillerBalls);
            // shuffle balls
            $balls = $balls->shuffle();
            
            
            // remove balls from round 1 from alive players
            
            $alivePlayers->each(function($player){
                $player->balls()->detach();
            });
            // assign balls to players
            // add 5 balls to each player
            
            $alivePlayers->each(function($player) use (&$balls){
                $player->balls()->attach($balls->take(5)->pluck('id'));
                $balls = $balls->slice(5);
                
                
            });
        }else{
            // round 3 - 2 players alive - 5 balls each + 1 killer ball
            // get balls from round 2 from alive players
            $alivePlayers = $game->players()->where('is_alive', true)->get();
            $balls = $game->playerBalls();
            // get new 1 killer balls not in round 2
            $killerBalls = $game->balls()->where('is_killer', true)->whereNotIn('id', $balls->pluck('id'))->get()->random(1);
            // add killer balls to balls
            $balls = $balls->merge($killerBalls);
            // shuffle balls
            $balls = $balls->shuffle();
            
            // detach balls from dead players
            $deadPlayers = $game->players()->where('is_alive', false)->get();
            
            $deadPlayers->each(function($player){
                $player->balls()->detach();
                
                
            });
            
            $alivePlayers->each(function($player){
                $player->balls()->detach();
                
                
            });
            
            // assign balls to players
            // add 5 balls to each player
            
            $player = $deadPlayers->first();
            $player->balls()->attach($balls->take(10)->pluck('id'));
            $balls = $balls->slice(10);
            // add killer ball to last player
            $player->balls()->attach($balls->take(1)->pluck('id'));
            
            
        }
        return true;
    }

    public function getPlayers($game)
    {
        return $game->players()->with('balls')->get();
    }

    public function getAlivePlayers($game){
        $players = $game->players()->where('is_alive', true)->with('balls')->get();
        
        return $players;
    }

    public function revealBalls($player){
        if(!$this->playerCanReveal($player)){
            return $this->getRevealedBalls($player);
        }
        $balls = $player->balls()->get();
        // reveal 2 balls
        $balls->take(2)->each(function($ball){
            $ball->pivot->is_revealed = true;
            $ball->pivot->save();
        });
        // save balls
        // return revealed balls
        return $this->getRevealedBalls($player);
    }

    public function revealAllBalls($game){
        $players = $game->players()->with('balls')->get();
        $players->each(function($player){
            $player->balls()->updateExistingPivot($player->balls()->pluck('ball_id'), ['is_revealed' => true]);
        });
    }

    public function getRevealedBalls($player){
        return $player->balls()->get()->filter(function($ball) use ($player){
            // log the ball
            if($ball->pivot == null){
                return false;
            }
            return $ball->pivot->is_revealed;
        });
    }

    public function getUnrevealedBalls($player){
        return $player->balls()->get()->filter(function($ball){
            if ($ball->pivot == null) {
                return false;
            }
            return !$ball->pivot->is_revealed;
        });
    }

    public function playerCanReveal($player){
        return $this->getUnrevealedBalls($player)->count() > 0;
    }

    public function killPlayer($player){
        if($this->canPlayersGetKilled($player->game)){
            $this->killPlayers($player->game);
        }
        $player->is_alive = false;
        $player->save();
    }

    public function canPlayersGetKilled($game){
        // if all players revealed 2 balls
        $players = $this->getPlayers($game);
        $players = $players->filter(function($player){
            return $this->getRevealedBalls($player)->count() == 2;
        });

        return $players->count() == $game->players()->count();
    }

    public function getPlayerTurn($game){
        $players = $this->getAlivePlayers($game);
        $players = $players->filter(function($player){
            return $this->getRevealedBalls($player)->count() < 2;
        });
        if($players->count() == 0){
            return false;
        }
        return $players->first();
    }

    public function getBinOrWinPlayerTurn($game){
        $players = $this->getAlivePlayers($game);
        
        // get binned balls from players
        $binnedBalls = $players->map(function($player){
            return $player->balls()->where('is_binned', true)->get();
        })->flatten();
        $winnedBalls = $players->map(function($player){
            return $player->balls()->where('is_winned', true)->get();
        })->flatten();
        // get player with least winned balls
        $player = $players->sortBy(function($player) use ($winnedBalls){
            return $player->balls()->where('is_winned', true)->count();
        })->first();
        if($winnedBalls->count() + $binnedBalls->count() == 11){
            return false;
        }
        // each turn player bin then win 1 ball
        // check if winned balls are less than binned balls
        if($winnedBalls->count() < $binnedBalls->count()){
            // win ball
            return [
                'player' => $player,
                'chat_id' => $player->chat_id,
                'action' => 'win',
                'binned' => $binnedBalls->count(),
                'winned' => $winnedBalls->count()
            ];
        }
        // bin ball
        return [
            'player' => $player,
            'chat_id' => $player->chat_id,
            'action' => 'bin',
            'binned' => $binnedBalls->count(),
            'winned' => $winnedBalls->count()
        ];
    }

    

    public function getTelegramMessage(){
        $update = json_decode(file_get_contents('php://input'));
        $message = @$update->message;
        $text = @$message->text;
        $chat_id = @$message->chat->id;
        $data = @$update->callback_query->data;
        $chat_id2 = @$update->callback_query->message->chat->id;
        $type = @$message->chat->type;
        $chat_member = @$update->chat_member->user->id;
        $new_chat_members = @$message->new_chat_members;
        $allowed_ids = [6153312651, 1209760821]; // TODO: remove this
        if(!in_array($chat_id, $allowed_ids)){
            // TODO: remove this
            $this->bot('sendMessage',[
                'chat_id' => $chat_id,
                'text' => "Sorry, this bot is not available for you. "
            ]);
            return false;
        }
        try{
            if($text == '/start'){
                $this->start($chat_id);
            }

            if($text == '/forceendgame'){
                $this->forceEndGame($chat_id);
            }

            if($text == '/creategame'){
                $this->newGame($chat_id);
            }

            if($text == '/joingame'){
                $this->joinGame($chat_id);
            }

            // check /join <game_code> <nickname> command
            if(preg_match('/^\/join\s([a-zA-Z0-9]{6})\s(.*)$/', $text, $matches)){
                $this->joinGame($chat_id, $matches[1], $matches[2]);
            }

            // check /startgame
            if($text == '/startgame'){
                $this->startGame($chat_id);
            }

            // check /reveal
            if($text == '/reveal'){
                $this->reveal($chat_id);
            }

            // check /reveal <number>
            if(preg_match('/^\/reveal\s([0-9]+)$/', $text, $matches)){
                $this->revealNumber($chat_id, $matches[1]);
            }

            // check /kill <nickname>
            if(preg_match('/^\/kill\s(.*)$/', $text, $matches)){
                $this->kill($chat_id, $matches[1]);
            }

            //check /split
            if($text == '/split'){
                $this->split($chat_id, true, false);
            }

            //check /steal
            if($text == '/steal'){
                $this->split($chat_id, false, true);
            }

            // check /end
            if($text == '/end'){
                $this->end($chat_id);
            }

            // check /continue
            if($text == '/continue'){
                $this->continue($chat_id);
            }
        }
        catch(Exception $e){
            $this->bot('sendMessage',[
                'chat_id' => $chat_id,
                'text' => "There was an error. Please contact @xTrimy.\n\n".$e->getMessage()
            ]);
        }
        catch(\Throwable $e){
            $this->bot('sendMessage',[
                'chat_id' => $chat_id,
                'text' => "There was an error. Please contact @xTrimy.\n\n".$e->getMessage()
            ]);
        }catch(\Error $e){
            $this->bot('sendMessage',[
                'chat_id' => $chat_id,
                'text' => "There was an error. Please contact @xTrimy.\n\n".$e->getMessage()
            ]);
        }catch(\ParseError $e){
            $this->bot('sendMessage',[
                'chat_id' => $chat_id,
                'text' => "There was an error. Please contact @xTrimy.\n\n".$e->getMessage()
            ]);
        }catch(\TypeError $e){
            $this->bot('sendMessage',[
                'chat_id' => $chat_id,
                'text' => "There was an error. Please contact @xTrimy.\n\n".$e->getMessage()
            ]);
        }catch(\DivisionByZeroError $e){
            $this->bot('sendMessage',[
                'chat_id' => $chat_id,
                'text' => "There was an error. Please contact @xTrimy.\n\n".$e->getMessage()
            ]);
        }catch(\ErrorException  $e){
            $this->bot('sendMessage',[
                'chat_id' => $chat_id,
                'text' => "There was an error. Please contact @xTrimy.\n\n".$e->getMessage()
            ]);
        }
        
    }


    
    public function bot($method, $data = [])
    {
        $allowed_ids = [6153312651, 1209760821]; // TODO: remove this
        if (!in_array($data['chat_id'], $allowed_ids)) {
            // TODO: remove this
            return false;
        }
        $url = "https://api.telegram.org/bot" . env('TELEGRAM_API_KEY') . "/" . $method;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $res = curl_exec($ch);
        if (curl_error($ch)) {
            var_dump(curl_error($ch));
        } else {
            return json_decode($res);
        }
        curl_close($ch);
    }

    public function start($chat_id)
    {
        
        $this->bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => '*This game needs to be played with 4 players communicating in voice chat. (You can use Discord, Skype, etc.)',
        ]);
        $this->bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "Welcome! Please choose an option below:\nIf you want to create a new game you will be the host and you can't participate in the game, or you can join a game you to be a player and can't participate as a host.",
        ]);
        $this->bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "To create a new game, type /creategame\nTo join a game, type /joingame",
        ]);
        
    }

    public function checkPlayerInGame($chat_id){
        // check if user already in a game
        $player = Player::where('chat_id', $chat_id)->where('game_id', '!=', null)->with('game')->first();
        if($player){
            $game = $player->game()->where('is_end', 0)->first();
            if ($game != null) {
                $this->bot('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => 'You are already in a game!',
                ]);
                return true;
            }
        }
        // check if user already created a game
        $game = Game::where('host_chat_id', $chat_id)->where('is_end', 0)->first();
        if ($game != null) {
            $this->bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => 'You already created a game!',
            ]);
            return true;
        }
        return false;
    }

    public function newGame($chat_id)
    {
        if($this->checkPlayerInGame($chat_id)){
            return false;
        }

        $game = $this->createGame($chat_id);
        $this->bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => 'Game created! Please share this code with the players: ' . $game->code,
        ]);
        $this->bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => 'Waiting for players to join... (Need 4 players) (Players can join by typing the bellow command:)',
        ]);
        $this->bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => '/join ' . $game->code . ' <nickname>',
        ]);
        
    }

    public function joinGame($chat_id, $code = null, $nickname = null)
    {
        if($code == null || $nickname == null){
            $this->bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => 'Please type /join <game code> <nickname>',
            ]);
        }
        else{
            if ($this->checkPlayerInGame($chat_id)) {
                return false;
            }

            $game = $this->getGame($code);
            if($game == null){
                $this->bot('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => 'Game not found!',
                ]);
            }
            else{
                $player = $this->createPlayer($game, $nickname, $chat_id);
                $this->bot('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => 'You have joined the game!',
                ]);
                $this->bot('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => 'Waiting for other players to join... (Current players: ' . $game->players()->count() . ')',
                ]);
                // send message to other players
                $players = $game->players()->where('chat_id', '!=', $chat_id)->pluck('chat_id');
                $host = $game->host_chat_id;
                // merge host and players
                $players->push($host);

                foreach($players as $player){
                    $this->bot('sendMessage', [
                        'chat_id' => $player,
                        'text' => $nickname . ' has joined the game!',
                    ]);
                    // send players count
                    $this->bot('sendMessage', [
                        'chat_id' => $player,
                        'text' => 'Current players: ' . $game->players()->count(),
                    ]);
                }
                // check if game is ready to start
                if($game->players()->count() == 4){
                    // send message to host
                    $this->bot('sendMessage', [
                        'chat_id' => $host,
                        'text' => 'Game is ready to start! Please type /startgame to start the game!',
                    ]);
                }
            }
        }
    }

    public function startGame($chat_id){
        $game = $this->getGameByHost($chat_id);
        if($game == null){
            $this->bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => 'You are not the host of any game!',
            ]);
        }
        else{
            if($game->players()->count() != 4){
                $this->bot('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => 'Game is not ready to start! (Need 4 players)',
                ]);
            }
            else{
                $this->bot('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => 'Game started!',
                ]);
                // send message to other players
                $this->sendMessageToAllPlayers($game,  'Game started!');

                // start game
                $this->startGameProcess($game);
            }
        }
    }

    // startGameProcess
    public function startGameProcess($game){
        // get player turn 
        $player_turn = $this->getPlayerTurn($game);
        if(!$player_turn){
            // send message to host that game already started
            $this->bot('sendMessage', [
                'chat_id' => $game->host_chat_id,
                'text' => 'Game already started!',
            ]);
            return false;
        }
        // send message to player turn
        $this->bot('sendMessage', [
            'chat_id' => $player_turn->chat_id,
            'text' => 'Your turn!',
        ]);
        // send message to other players
        $this->sendMessageToAllPlayers($game, $player_turn->name . ' turn!');
        // send message to host
        $this->bot('sendMessage', [
            'chat_id' => $game->host_chat_id,
            'text' => $player_turn->name . ' turn!',
        ]);
        $this->mixAndAttachBalls($game);
    }

    // mixAndAttachBalls
    public function mixAndAttachBalls($game){
        $mixer = $this->mixer($game);
        if(!$mixer){
            // send message to host that game already started
            $this->bot('sendMessage', [
                'chat_id' => $game->host_chat_id,
                'text' => 'Game already started!',
            ]);
        }
        else{
            $this->sendMessageToAllPlayers($game, 'Balls are getting mixed!');
            $balls_count = count($game->playerAllBalls());
            if($balls_count == 11){
                // round 3
                $this->sendPlayerBalls($game, false, true);
                // get player turn
                $player_turn = $this->getBinOrWinPlayerTurn($game);
                // send message to all players 
                
                if ($player_turn){
                    $this->sendMessageToAllPlayers($game, 'Bin or Win round!');
                    $this->sendMessageToAllPlayers($game, $player_turn['player']->name . ' is going to '.$player_turn['action'].' a ball!');
                    $this->bot('sendMessage', [
                        'chat_id' => $player_turn['player']->chat_id,
                        'text' => 'Please type /reveal <number> to '. $player_turn['action'].' a ball. Choose wisely!',
                    ]);
                }
                
                
            }else{
                // round 1 and 2
                $this->sendPlayerBalls($game);
                // get player turn
                $player_turn = $this->getPlayerTurn($game);
                // send message to player turn
                if ($player_turn)
                $this->bot('sendMessage', [
                    'chat_id' => $player_turn->chat_id,
                    'text' => 'Please type /reveal to reveal top 2 balls!',
                ]);
            }
            
            
        }
    }

    public function sendPlayerBalls($game, $with_dead = false, $is_bin_or_win = false){

        if($with_dead){
            $players = $this->getPlayers($game);
        }
        else{
            $players = $this->getAlivePlayers($game);
        }

        $message = [];
        $all_balls = [];
        if($is_bin_or_win){
            $ballss = $game->playerAllBalls();
            $ballss = collect($ballss);
            $all_balls[] = $ballss;
        }
        foreach ($players as $player) {
            // send message to player with balls
            if(!$is_bin_or_win){
                $ballss = $player->balls;
                $all_balls[] = $ballss;
            }
            // if ball is revealed display it else display 🟡
            $balls = $ballss->map(function ($ball) {
                if ($ball['is_killer'] && $ball['pivot'][ 'is_revealed']) {
                    return '🔴 (Killer)';
                }
                else if ($ball['pivot']['is_revealed']) {
                    return "🟡 (" . $ball['number'] . ")";
                } else {
                    return '🟡';
                }
            });
            if(!$is_bin_or_win){
                // send message to players with <nickname> balls: <balls>
                $message[] = $player->name . ' balls: ' . $balls->implode(' ');

                // check if this player has any revealed balls
                $revealed_balls = $ballss->filter(function ($ball) {
                    return $ball['pivot']['is_revealed'];
                });
                if($revealed_balls->count() >0){
                    // send player their balls revealed
                    $player_balls = $ballss->map(function ($ball) {
                        if ($ball['is_killer'] ) {
                            return '🔴 (Killer)';
                        }
                        else  {
                            return "🟡 (" . $ball['number'] . ")";
                        } 
                    });
                    $this->bot('sendMessage', [
                        'chat_id' => $player->chat_id,
                        'text' => 'Your balls: ' . $player_balls->implode(' '),
                    ]);
                }

            }
        }
        if($is_bin_or_win){
            // collect all balls with ball index 
            $all_balls = collect($all_balls)->flatten(1);
            // order by id
            $all_balls = $all_balls->map(function ($ball, $index) {
                $message = $index + 1 . '. ';
                
                if ($ball['is_killer'] && $ball['pivot']['is_revealed']) {
                    $message .= '🔴 (Killer)';
                }else if ($ball['pivot']['is_revealed']) {
                    $message .= "🟡 (" . $ball['number'] . ")";
                }else{
                    $message .= '🟡';
                }
                if ($ball['pivot']['is_binned']) {
                    $message .= ' (❌)';
                }else if ($ball['pivot']['is_winned']) {
                    $message .= ' (✅)';
                }
                return $message;
            });
            $message[] = "All balls: ";
            $message[] = $all_balls->implode("\n");
        }
        $this->sendMessageToAllPlayersAndHost($game, implode("\n", $message));

    }

    public function sendMessageToAllPlayers($game, $message, $except_chat_id = null){
        // send message to other players
        $players = $game->players()->pluck('chat_id');
        foreach($players as $player){
            if($except_chat_id != null && $player == $except_chat_id){
                continue;
            }
            $this->bot('sendMessage', [
                'chat_id' => $player,
                'text' => $message,
            ]);
        }
    }
    public function sendMessageToAllPlayersAndHost($game, $message, $except_chat_id = null)
    {
        // send message to other players
        $this->sendMessageToAllPlayers($game, $message, $except_chat_id);
        // send message to host
        $this->bot('sendMessage', [
            'chat_id' => $game->host_chat_id,
            'text' => $message,
        ]);
    }
    public function getGameByPlayer($chat_id){
        return Game::where('is_end',false)->whereHas('players', function ($query) use ($chat_id) {
            $query->where('chat_id', $chat_id);
        })->first();
    }

    public function getPlayer($game, $chat_id){
        return $game->players()->where('chat_id', $chat_id)->with('balls')->first();
    }

    // reveal
    public function reveal($chat_id){
        $game = $this->getGameByPlayer($chat_id);
        if($game == null){
            $this->bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => 'You are not in any game!',
            ]);
        }
        else{
            $player = $this->getPlayer($game, $chat_id);
            if($player == null){
                $this->bot('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => 'You are not in any game!',
                ]);
            }
            else{
                // check if player turn
                $player_turn = $this->getPlayerTurn($game);
                if(!$player_turn){
                    return false;
                }
                if($player_turn->chat_id != $chat_id
                 && false  // TODO: remove this
                 ){
                    $this->bot('sendMessage', [
                        'chat_id' => $chat_id,
                        'text' => 'It is not your turn!',
                    ]);
                }
                else{
                    $player_turn = $this->getPlayerTurn($game); // TODO: remove this
                    $player =  $player_turn; // TODO: remove this
                    // reveal top 2 balls
                    $this->revealBalls($player);
                    // send message to other players
                    $this->sendMessageToAllPlayersAndHost($game, $player->name . ' revealed top 2 balls!');
                    // send message to player
                    $this->sendPlayerBalls($game);
                    // get player turn
                    $player_turn = $this->getPlayerTurn($game);
                    if($player_turn){
                        // send message to player turn
                        $this->sendMessageToAllPlayersAndHost($game, $player_turn->name . ' turn!', $player_turn->chat_id);
                        $this->bot('sendMessage', [
                            'chat_id' => $player_turn->chat_id,
                            'text' => 'Your turn!',
                        ]);
                        $this->bot('sendMessage', [
                            'chat_id' => $player_turn->chat_id,
                            'text' => 'Please type /reveal to reveal top 2 balls!',
                        ]);
                    }else{
                        // send message to other players
                        $this->sendMessageToAllPlayersAndHost($game, 'Balls revealed by all players!');
                        // send message to player
                        $this->sendMessageToAllPlayersAndHost($game, 'Voting phase started!');
                        // send message to host with /kill <nickname>
                        $this->bot('sendMessage', [
                            'chat_id' => $game->host_chat_id,
                            'text' => 'Please type /kill <nickname> to kill a player! (e.g. /kill John). Choose from the following nicknames depending on the votes:',
                        ]);
                        // send nicknames to host
                        $players = $this->getAlivePlayers($game);
                        foreach ($players as $player) {
                            $this->bot('sendMessage', [
                                'chat_id' => $game->host_chat_id,
                                'text' => $player->name,
                            ]);
                        }
                    }
                }
            }
        }
    }

    public function revealNumber($chat_id, $number, $final = false){
        $player = Player::where('chat_id', $chat_id)->where('game_id', '!=', null)->where('is_end', false)->with('game')->first();
        if($player == null){
            $this->bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => 'You are not in any game!',
            ]);
        }
        else{
            $game = $player->game;
            // check if player turn
            $player_turn = $this->getBinOrWinPlayerTurn($game);
            if(!$player_turn){
                return false;
            }
            if($player_turn['player']->chat_id != $chat_id 
            && false  // TODO: remove this
            ){
                $this->bot('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => 'It is not your turn!',
                ]);
            }
            else{
                // reveal number
                $balls = $game->playerAllBalls();
                // get ball by index <number>
                $ball = $balls;
                
                if (!isset($ball[$number - 1])){
                    $this->bot('sendMessage', [
                        'chat_id' => $chat_id,
                        'text' => 'Number is not valid!',
                    ]);
                    return false;
                }
                $ball = $ball[$number - 1];
                $ball = Ball::find($ball['id']);
                
                if($ball == null){
                    $this->bot('sendMessage', [
                        'chat_id' => $chat_id,
                        'text' => 'Number is not valid!',
                    ]);
                }else{
                    // check if ball is revealed
                    $balls = $game->playerAllBalls();
                    $ball = $game->balls()->with('players')->where('id', $ball->id)->first();
                    if($ball->players()->first()->pivot->is_revealed){
                        $this->bot('sendMessage', [
                            'chat_id' => $chat_id,
                            'text' => 'Number is already revealed!',
                        ]);
                        return false;
                    }
                    $pivot = $ball->players()->first()->pivot;
                     $pivot->is_revealed = true;
                     $pivot->player_id = $player_turn['player']->id;
                    if ($player_turn['action'] == 'bin') {
                         $pivot->is_binned = true;
                    }else if ($player_turn['action'] == 'win') {
                         $pivot->is_winned = true;
                        if($ball->is_killer){
                            $game->total = $game->total / 10;
                            $game->save();
                        }
                        else{
                            $game->total = $game->total + $ball->number;
                            $game->save();
                        }
                    }
                     $pivot->save();
                     if(!$final){
                        // send message to other players
                        if($player_turn['action'] == 'win'){
                            $this->sendMessageToAllPlayersAndHost($game, $player_turn['player']->name . ' selected ball number ' . $number . ' for win!');
                        }else{
                            $this->sendMessageToAllPlayersAndHost($game, $player_turn['player']->name . ' selected ball number ' . $number . ' for bin!');
                        }
                    }else{
                        // send message to other players
                        $this->sendMessageToAllPlayersAndHost($game, 'Remaining ball number ' . $number . ' is binned!');
                    }
                    // send message to player
                    $this->sendPlayerBalls($game, false, true);
                    $this->sendMessageToAllPlayersAndHost($game, 'Total Winnings: ' . $game->total);

                    $binnedBallsCount = $player_turn['binned'];
                    $winnedBallsCount = $player_turn['winned'];
                    $totalBallsCount = $binnedBallsCount + $winnedBallsCount + 1; // +1 for current ball
                    if( $totalBallsCount == 10 ){
                        // get the number of the remaining non-revealed ball
                        $number_to_reveal = 0;
                        foreach ($balls as $key=>$b) {
                            
                            if(!$b['pivot']['is_revealed']){
                                if($key + 1 != $number){
                                    $number_to_reveal = $key + 1;
                                    break;
                                }
                            }
                        }
                        $this->revealNumber($chat_id, $number_to_reveal, true);
                    }else if( $totalBallsCount < 10 ){
                        $player_turn = $this->getBinOrWinPlayerTurn($game);
                        if($player_turn){
                            $this->bot('sendMessage', [
                                'chat_id' => $player_turn['player']->chat_id,
                                'text' => 'It is your turn!',
                            ]);
                            $this->bot('sendMessage', [
                                'chat_id' => $player_turn['player']->chat_id,
                                'text' => 'Please type /reveal <number> to '.$player_turn['action'].' a ball!',
                            ]);
                        }
                    }else if($totalBallsCount == 11){
                        // split or steal round
                        $this->splitOrSteal($game);
                    }

                }
            }
        }
    }

    public function splitOrSteal($game){
        $this->sendMessageToAllPlayersAndHost($game, 'Split or Steal Round!');
        // get alive players
        $alive_players = $game->players()->where('is_alive', true)->get();
        // send message to alive participants
        foreach ($alive_players as $player) {
            $this->bot('sendMessage', [
                'chat_id' => $player->chat_id,
                'text' => 'Please type 🟡 /split or 🔴 /steal to split or steal the total winnings!',
            ]);
        }
    }
    

    public function getPlayerByNickname($game, $nickname){
        return $game->players()->where('name', $nickname)->first();
    }

    // kill, chat_id is host chat_id
    public function kill($chat_id, $nickname){
        $game = Game::where('host_chat_id', $chat_id)->where('is_end', false)->first();
        if($game == null){
            $this->bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => 'You are not hosting any game!',
            ]);
        }
        else{
            // check if nickname is valid
            $player = $this->getPlayerByNickname($game, $nickname);
            if($player == null){
                $this->bot('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => 'Nickname is not valid!',
                ]);
            }
            else{
                // check if player is already killed
                if(!$player->is_alive){
                    $this->bot('sendMessage', [
                        'chat_id' => $chat_id,
                        'text' => 'Player is already kicked!',
                    ]);
                }
                else{
                    // kill player
                    $player->is_alive = false;
                    $player->save();
                    // reveal all balls
                    $this->revealAllBalls($game);
                    // send message to other players
                    $this->sendMessageToAllPlayersAndHost($game, $player->name . ' is voted out! The game continues!');
                    // send message to player
                    $this->sendMessageToAllPlayersAndHost($game, 'Revealing all balls!');
                    $this->sendPlayerBalls($game, true);
                    // send message to host 
                    $this->bot('sendMessage', [
                        'chat_id' => $chat_id,
                        'text' => 'Type /continue to continue the next round!',
                    ]);
                    // $this->mixAndAttachBalls($game);
                }
            }
        }
       
    }

    // split function
    public function split($chat_id, $split = false, $steal = false){
        $player = Player::where('chat_id', $chat_id)->where('game_id', '!=', null)->with('game')->first();
        if(!$player){
            $this->bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => 'You are not in any game!',
            ]);
        }
        else{
            $game = $player->game()->where('is_end', false)->first();
            if($game == null){
                $this->bot('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => 'You are not in any game!',
                ]);
            }
            else{
                // check if player is alive
                if(!$player->is_alive){
                    $this->bot('sendMessage', [
                        'chat_id' => $chat_id,
                        'text' => 'You are kicked from the game!',
                    ]);
                }
                else{
                    // check if player is already split
                    if($player->is_split || $player->is_steal){
                        $this->bot('sendMessage', [
                            'chat_id' => $chat_id,
                            'text' => 'You have already executed your action!',
                        ]);
                    }
                    else{
                        // split
                        if($split){
                            $player->is_split = true;
                            $player->save();
                            $this->bot('sendMessage', [
                                'chat_id' => $chat_id,
                                'text' => 'You have chosen to split!',
                            ]);
                        }
                        // steal
                        else if($steal){
                            $player->is_steal = true;
                            $player->save();
                            $this->bot('sendMessage', [
                                'chat_id' => $chat_id,
                                'text' => 'You have chosen to steal!',
                            ]);
                            // send message to all players
                            $this->sendMessageToAllPlayersAndHost($game, $player->name . ' has decided their action!', $chat_id);
                        }
                        $player->save();
                        // check if all players have split
                        $all_players = $game->players()->where('is_alive', true)->get();
                        foreach ($all_players as $p) {
                            if(!$p->is_split && !$p->is_steal){
                                $this->bot('sendMessage', [
                                    'chat_id' => $chat_id,
                                    'text' => 'Waiting for your opponent to choose!',
                                ]);
                                return;
                            }
                        }
                        // all players have decided
                        // send message to all players
                        $this->sendMessageToAllPlayersAndHost($game, "All players have decided to split or steal!\nWaiting for the host to reveal the balls!");
                    
                        // send message to host
                        $this->bot('sendMessage', [
                            'chat_id' => $game->host_chat_id,
                            'text' => 'Please type /end to reveal the the actions and end the game!',
                        ]);
                        
                    }
                }
            }
        }
    }

    // end function
    public function end($chat_id){
        $game = Game::where('host_chat_id', $chat_id)->where('is_end', false)->first();
        if($game == null){
            $this->bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => 'You are not hosting any game!',
            ]);
        }
        else{
            // check if all players have split or steal
            $all_players = $game->players()->where('is_alive', true)->get();
            foreach ($all_players as $p) {
                if(!$p->is_split && !$p->is_steal){
                    $this->bot('sendMessage', [
                        'chat_id' => $chat_id,
                        'text' => 'Waiting for split or steal round to end!',
                    ]);
                    return;
                }
            }
            // reveal split or steal for alive players
            $message = [];
            $split = 0;
            $steal = 0;
            $split_players = [];
            $steal_players = [];
            foreach ($all_players as $p) {
                if($p->is_split){
                    $message[] = $p->name . ' has chosen to split!';
                    $split++;
                    $split_players[] = $p;
                }
                else if($p->is_steal){
                    $message[] = $p->name . ' has chosen to steal!';
                    $steal++;
                    $steal_players[] = $p;
                }
            }
            // if split = 2, steal = 0, total_winnings are split equally
            // if split = 1, steal = 1, total_winnings goes to the player who steals
            // if split = 0, steal = 2, total_winnings = 0
            $total_winnings = $game->total_winnings;
            if($split == 2 && $steal == 0){
                $total_winnings = $total_winnings / 2;
                $message[] = 'Total winnings are split equally!';
                foreach ($split_players as $p) {
                    $p->is_winner = 1;
                    $p->save();
                }
            }
            else if($split == 1 && $steal == 1){
                $message[] = 'Total winnings goes to the player who steals!';
                foreach ($steal_players as $p) {
                    $p->is_winner = 1;
                    $p->save();
                }
            }
            else if($split == 0 && $steal == 2){
                $total_winnings = 0;
                $message[] = 'No winnings for anyone!';
            }
            // send message to all players
            $this->sendMessageToAllPlayersAndHost($game, implode("\n", $message));
            // send message to all players to congratulate the winners
            $winners = $game->players()->where('is_winner', 1)->get();
            if($winners->count() >  0){
                $message = [];
                $message[] = 'Congratulations to ';
                foreach ($winners as $p) {
                    $message[] = $p->name;
                }
                $message[] = '🎉🎊🎉🎊🎉🎊🎉';
                if($winners->count() > 1){
                    $message[] = 'You have won ' . $total_winnings . ' coins each!';
                }
                else if($winners->count() == 1){
                    $message[] = 'You have won ' . $total_winnings . ' coins!';
                }
                $this->sendMessageToAllPlayersAndHost($game, implode("\n", $message));
            }else{
                $this->sendMessageToAllPlayersAndHost($game, "No winners this time!\nBetter luck next time!");
            }
            $this->sendMessageToAllPlayersAndHost($game, 'Game ended!');
            $game->is_end = true; 
            $game->save(); 
            $game->players()->update(['is_end' => true]);

            
        }
    }

    // force end function
    public function forceEndGame($chat_id){
        $game = Game::where('host_chat_id', $chat_id)->where('is_end', false)->first();
        if($game == null){
            $this->bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => 'You are not hosting any game!',
            ]);
        }
        else{
            // send message to all players
            $this->sendMessageToAllPlayersAndHost($game, 'Game ended by the host!');
            $game->is_end = true; 
            $game->save();
            $game->players()->update(['is_end' => true]);
        }
    }

    // /continue function
    public function continue($chat_id){
        // host can only continue the game
        $game = Game::where('host_chat_id', $chat_id)->where('is_end', false)->first();
        if($game == null){
            $this->bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => 'You are not hosting any game!',
            ]);
        }
        else{
        //    check if all balls revealed
        $balls = $game->playerAllBalls();
        $balls = collect($balls);
        $all_revealed = true;
        foreach ($balls as $b) {
            if($b['pivot']['is_revealed'] == false){
                $all_revealed = false;
                break;
            }
        }
        if(!$all_revealed){
            $this->bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => 'Waiting for all players to reveal their balls!',
            ]);
            return;
        }
        $this->mixAndAttachBalls($game);
    }
}

}
