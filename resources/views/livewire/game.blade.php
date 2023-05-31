<div id="game" data-round="{{ $round }}"  wire:poll.1000ms="refresh" class="w-full h-full relative bg-gradient-to-b from-[#0C1C51] to-[#132570] overflow-hidden">
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[1000px] h-[1000px] opacity-[0.08]">
        <img src="{{ asset('images/Group 3.png') }}" class="w-full h-full object-center object-contain" alt="">
    </div>
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
             @if(!$player->is_alive) opacity-50 @endif
            ">
    <div class="w-[540px] h-[356px] text-white bg-black border-[20px] border-solid @if(!$current_player_turn) border-white @else border-green-500 @endif flex items-center text-4xl justify-center">
        {{ $player->name }}
          <div class="absolute top-0 left-0 text-sm">
    </div>
    </div>
    <div class="
    @if($p < 3)
    mt-[55px] 
    rotate-180
    @elseif ($p > 2)
    mb-[55px]
    @endif
    
    @if($p % 2 == 1) 
    ml-[20px]
    @elseif ($p % 2 == 0)
    mr-[20px]
    @endif
    ">
    @if($round != 3 || count($balls) > 11)
        @php
            $player_top_balls = $player->balls->take(2);
            $player_bottom_balls = $player->balls->skip(2)->values();
            $rotate = false;
            if($p < 3)
                $rotate = true;
        @endphp
        <div class="flex w-full justify-center">
            @foreach ($player_top_balls as $key=>$ball)
                <div data-is_revealed="{{ $ball['pivot']['is_revealed'] }}" data-is_killer="{{ $ball['is_killer'] }}"  class="ball scale-100 w-[75px] h-[75px] rounded-full transition-all 
                @if($key > 0)
                ml-[20px]
                @endif
                 {{ $key }}
                 overflow-hidden">
                    <div class="w-full h-full relative 
                    @if($rotate == true) rotate-180 @endif"
                    >
                        @if($ball->is_killer == true && $ball->pivot->is_revealed)
                            <img src="{{ asset('images/killer.png') }}" class="w-full h-full" alt="">
                        @else
                            <img src="{{ asset('images/ball.png') }}" class="w-full h-full" alt="">
                        @endif
                        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-black text-2xl font-bold">
                            @if($ball->pivot->is_revealed == true)
                                @if($ball->is_killer == false)
                                    {{ $ball->number }}
                                @else
                                Killer
                                @endif
                            @endif
                        </div>
                    </div>

                </div>
            @endforeach
        </div>
        <div class="flex mt-[20px]">
            @foreach ($player_bottom_balls as $k=>$ball)
                <div  data-is_revealed="{{ $ball['pivot']['is_revealed'] }}" data-is_killer="{{ $ball['is_killer'] }}" class="ball scale-100 w-[75px] h-[75px] rounded-full transition-all 
                 @if($k > 0)
                 {{ $key }}
                ml-[20px]
                @endif overflow-hidden">
                    <div class="w-full h-full relative 
                    @if($rotate == true) rotate-180 @endif"
                    >
                        @if($ball->is_killer == true && $ball->pivot->is_revealed)
                            <img src="{{ asset('images/killer.png') }}" class="w-full h-full" alt="">
                        @else
                            <img src="{{ asset('images/ball.png') }}" class="w-full h-full" alt="">
                        @endif                        
                        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-black text-2xl font-bold">
                        @if($ball->pivot->is_revealed == true)
                            @if($ball->is_killer == false)
                                {{ $ball->number }}
                            @else
                            Killer
                            @endif
                        @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        @endif
    </div>
</div>
    @endforeach
    
    
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
        <div class="w-[389px] h-[256px] bg-white">
        </div>
        
    </div>
    @if($round == 3 && count($balls) == 11)
    <div class="absolute top-20 w-96 border-8 border-white bg-[#FF8B00] flex items-center justify-center text-5xl font-bold h-48 left-1/2 transform -translate-x-1/2">
        <div class="text-center text-white">
            <p class="text-2xl">Total Win:</p>
            {{ $game->total }}
        </div>
    </div>
    <div class="flex flex-wrap justify-center w-full absolute bottom-8">
        @foreach ($balls as $k=>$ball)
            @if($k == 4 || $k == 8)
            <div class="w-full"></div>
            @endif
            <div class="w-[75px] h-[75px] m-2 rounded-full ball scale-100 transition-all" data-is_revealed="{{ $ball['pivot']['is_revealed'] }}" data-is_binned="{{ $ball['pivot']['is_binned'] }}" data-is_winned="{{ $ball['pivot']['is_winned'] }}" data-is_killer="{{ $ball['is_killer'] }}">
                <div class="w-full h-full relative "
                >
                    @if($ball['is_killer'] == true && $ball['pivot']['is_revealed'])
                        <img src="{{ asset('images/killer.png') }}" class="w-full h-full" alt="">
                    @else
                        <img src="{{ asset('images/ball.png') }}" class="w-full h-full" alt="">
                    @endif
                    <div class="absolute top-1/2 left-1/2 bg-neutral-900 w-[110%] text-center -translate-x-1/2 font-bold -translate-y-1/2 text-white text-2xl" style="--tw-bg-opacity:1;">
                        @if($ball['pivot']['is_revealed'] == true)
                            @if($ball['is_killer'] == false)
                                {{ $ball['number'] }}
                            @else
                            Killer
                            @endif
                        @endif
                    </div>
                    <div class="absolute bottom-0 left-0 text-black text-xl">
                        @if($ball['pivot']['is_revealed'] == true)
                            @if($ball['pivot']['is_binned'] == true)
                                <div class="text-xl">❌</div>
                            @elseif($ball['pivot']['is_winned'] == true)
                                <div class="text-xl">✅</div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
        </div>
        
        @endif
    @else
        <div class="text-white m-8 text-xl">
            Game Not Ready
        </div>
    @endif
  
   <script>
            var winned_balls = [];
            var binned_balls = [];
            var revealed_balls = [];
            count_balls();
            var interval = setInterval(() => {
                current_winned_count = winned_balls.length;
                current_binned_count = binned_balls.length;
                current_revealed_count = revealed_balls.length;
                current_revealed_balls = [...revealed_balls];
                count_balls();
                if(document.getElementById('game').dataset.round == 3){
                    if(current_winned_count != winned_balls.length){
                        // play win sound
                        // get last winned ball
                        var last_ball = winned_balls[winned_balls.length - 1];
                        if(last_ball.dataset.is_killer == 1){
                            var audio = new Audio("{{ asset('sounds/ooh.mp3') }}");
                        }else{
                            var audio = new Audio("{{ asset('sounds/ball_winned.mp3') }}");
                        }
                            audio.play();
                    }
                    if(current_binned_count != binned_balls.length){
                        var audio = new Audio("{{ asset('sounds/ball_binned.mp3') }}");
                        audio.play();
                    }
                }else{
                    if(current_revealed_count != revealed_balls.length){
                        // get difference
                        var audio = new Audio("{{ asset('sounds/reveal.mp3') }}");
                        audio.play();
                        
                    }
                }
                var diff = [];
                        revealed_balls.forEach(ball => {
                            if(!current_revealed_balls.includes(ball)){
                                diff.push(ball);
                            }
                        });
                        console.log(diff);
                        diff.forEach(ball => {
                            ball.classList.add('scale-150');
                            ball.classList.add('duration-500');
                            ball.classList.remove('scale-100');
                        });
                var balls = document.querySelectorAll('.ball');
            }, 1000);
            function count_balls(){
                var balls = document.querySelectorAll('.ball');
                balls.forEach(ball => {
                    ball.classList.remove('scale-150');
                    ball.classList.add('scale-100');
                    if(ball.dataset.is_revealed == 1){
                        if(!revealed_balls.includes(ball)){
                            revealed_balls.push(ball);
                        }
                        if(ball.dataset.is_binned == 1){
                            if(!binned_balls.includes(ball)){
                                binned_balls.push(ball);
                                ball.classList.add('binned');
                            }
                        }
                        if(ball.dataset.is_winned == 1){
                            if(!winned_balls.includes(ball)){
                                winned_balls.push(ball);
                                ball.classList.add('winned');
                            }
                        }
                    }else{
                        if(revealed_balls.includes(ball)){
                            revealed_balls.splice(revealed_balls.indexOf(ball), 1);
                        }
                        if(binned_balls.includes(ball)){
                            binned_balls.splice(binned_balls.indexOf(ball), 1);
                            ball.classList.remove('binned');
                        }
                        if(winned_balls.includes(ball)){
                            winned_balls.splice(winned_balls.indexOf(ball), 1);
                            ball.classList.remove('winned');
                        }
                    }
                });
            }
        </script>
</div>
