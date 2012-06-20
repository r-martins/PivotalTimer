<?php

class PivotalTracker {

    public $output;
    public $token;
    public $membershipId;
    private $baseurl = 'https://www.pivotaltracker.com/services/v3/';

    public function getUserToken($user, $pass) {
        $url = $this->baseurl . 'tokens/active';
        $xml = $this->curl($url);
        
        if($xml == FALSE)
            return FALSE;
        
        $this->token = $xml->guid;
        $this->membershipId = $xml->id;

        return (string) $xml->guid;
    }

    public function getStoriesByFilter($token, $projectId, $filter) {
        $url = $this->baseurl . 'projects/' . $projectId . '/stories?filter=' . $filter;
        $xml = $this->curl($url);
        if($xml == FALSE)
            return FALSE;
        
        return (array)$xml->stories;
    }

    private function curl($url){
        $ch = curl_init($url);

        // Follow redirects
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_HTTPGET, 1);

        $do_auth = !empty($user) && !empty($pass);
        if ($do_auth) {
            curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . $pass);
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