<?php

class TelegramController
{

    public $message;
    public $text;
    public $chat_id;
    public $data;
    public $chat_id2;
    public $type;
    public $chat_member;
    public $new_chat_members;

    private function bot($method, $data = [])
    {
        $url = "https://api.telegram.org/bot" . API_KEY . "/" . $method;
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

    public function start(){
        $this->bot('sendMessage', [
            'chat_id' => $this->chat_id,
            'text' => 'Welcome! Please choose an option',
            'reply_markup' => '{"keyboard":[[{"text":"Create Game"},{"text":"Join Game"}]],"resize_keyboard": true}',
        ]);
    }
}
