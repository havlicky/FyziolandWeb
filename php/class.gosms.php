<?php

class Gosms {
    protected $accessToken = "";
    protected $url = "https://app.gosms.cz/api/v1/messages/";
    protected $testUrl = "https://app.gosms.cz/api/v1/messages/test";
    
    public function __construct() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://app.gosms.cz/oauth/v2/token");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
            "client_id" => Settings::$goSMSclientId,
            "client_secret" => Settings::$goSMSclientSecret,
            "grant_type" => "client_credentials"
        ));
        $response = json_decode(curl_exec($ch));
        
        $this->accessToken = $response->access_token;
        
        curl_close($ch);
    }
    
    public function send($message, $recipients, $test = FALSE) {
        $url = ($test ? $this->testUrl : $this->url);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Authorization: Bearer {$this->accessToken}"
        ));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array(
            "message" => $message,
            "recipients" => $recipients,
            "channel" => Settings::$goSMSchannel
        )));
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response);
    }
    
    public function getMessageDetail($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://app.gosms.cz" . $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Authorization: Bearer {$this->accessToken}"
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response);
    }
    
}
