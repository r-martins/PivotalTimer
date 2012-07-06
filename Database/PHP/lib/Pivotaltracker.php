<?php

class PivotalTracker {

    public $output;
    public $token;
    public $membershipId;
    private $baseurl = 'https://www.pivotaltracker.com/services/v3/';

    public function getUserToken($user, $pass) {
        $url = $this->baseurl . 'tokens/active';
        $this->user = $user;
        $this->pass = $pass;
        $xml = $this->curl($url);
        
        if($xml == FALSE)
            return FALSE;
        
        $this->token = $xml->guid;
        $this->membershipId = $xml->id;

        return (string) $xml->guid;
    }

    private function curl($url){
        $ch = curl_init($url);

        // Follow redirects
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_HTTPGET, 1);

        $do_auth = isset($this->user) && isset($this->pass) && !empty($this->user) && !empty($this->pass);
        if ($do_auth) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->user . ':' . $this->pass);
        }
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $output = curl_exec($ch);
        //print_rr($output);
        // $response contains the response HTTP headers
        $response = curl_getinfo($ch);

        if (curl_errno($ch)) {
            return curl_error($ch);
        } else {
            curl_close($ch);
        }

        $xml = @simplexml_load_string($output);
        if ($xml == FALSE) {
            $this->output = $output;
            return FALSE;
        }
        return $xml;
    }
}