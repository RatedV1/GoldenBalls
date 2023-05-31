<div id="game" data-round="{{ $round }}"  wire:poll.1000ms="refresh" class="w-full h-full relative overflow-hidden">
   
    @if($game_ready)
    {{-- Player 1 --}}
    @foreach ($players as $p=>$player)
        @php
        $p = $p + 1;
            $current_player_turn = false;
            if($player_turn)
                if($round == 3){
                    if($player_turn['player']->id == $player->id)
                        $current_player_turn = true;
                }
                else{
                    if($player_turn->id == $player->id)
                        $current_player_turn = true;
                }
                
        @endphp
        <div class="absolute 
            @if($p == 1) 
            top-0 left-0 
            @elseif ($p == 2)
            top-0 right-0
            @elseif ($p == 3)
            bottom-0 left-0
            @elseif ($p == 4)
            bottom-0 right-0
            @endif
            flex  
            @if($p % 2 == 0) flex-row-reverse @endif
            @if($p < 3)
            items-start
            @elseif ($p > 2)
            items-end
            @endif
            ">
            <div class="w-[540px] h-[356px] ">
                @if(!$player->is_alive)
                    <div class="absolute bottom-8 right-8 text-5xl bg-opacity-50 text-white z-10">
                        ❌ (Out)
                    </div>
                @endif
            </div>

    
</div>
    @endforeach
  
    @endif
  
</div>
