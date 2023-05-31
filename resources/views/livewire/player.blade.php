
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
    <div class="w-[540px] h-[356px] text-white bg-black border-[20px] border-solid @if(!$player_turn) border-white @else border-green-500 @endif flex items-center text-4xl justify-center">
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
        <div class="flex w-full justify-center ">
            @foreach ($player_top_balls as $key=>$ball)
                <div class="w-[75px] h-[75px] rounded-full 
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
                <div class="w-[75px] h-[75px] rounded-full
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