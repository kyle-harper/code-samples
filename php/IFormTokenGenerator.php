<?php

/**
 * IFormTokenGenerator.php
 *
 * Class to generate iFormBuilder API Tokens.
 *
 * Built for PHP version 5.6 and 7.0
 *
 * @author     Kyle Harper <kyle.p.harper@gmail.com>
 * @version    0.1.0
 */

namespace App\Classes;

class IFormTokenGenerator
{
    function __construct($params)
    {
        $this->alg = isset($params['alg']) ? $params['alg'] : 'HS256';
        $this->key = isset($params['key']) ? $params['key'] : false;
        $this->secret = isset($params['secret']) ? $params['secret'] : false;
        $this->tokenUrl = isset($params['url']) ? $params['url'] : false;
        $this->expSeconds = 10;
        $this->iat = time();
        $this->exp = $this->iat + $this->expSeconds;
        $this->header = [
            "alg" => $this->alg,
            "typ" => "JWT"
        ];
        $this->claimSet = [
            "iss" => $this->key,
            "aud" => $this->tokenUrl,
            "exp" => $this->exp,
            "iat" => $this->iat
        ];
    }

    ///////////////////////////////////////////////////////////////////////////
    //  PUBLIC METHODS
    //
    public function getToken()
    {
        $segments = array();
        $segments[] = $this->urlsafeB64Encode(json_encode($this->header));
        $segments[] = $this->urlsafeB64Encode(json_encode($this->claimSet));
        $signing_input = implode('.', $segments);

        $signature = $this->sign($signing_input, $this->secret, $this->alg);
        $segments[] = $this->urlsafeB64Encode($signature);

        $assertion = implode('.', $segments);

        $data = [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $assertion
        ];

        // now curl over to iForm to request the token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->tokenUrl);
        curl_setopt($ch, CURLOPT_POST, count($data));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);

        $token = null;
        $output = json_decode($output, true);
        if (is_array($output) && array_key_exists('access_token', $output)) {
            return $output['access_token'];
        }
        return false;
    }

    ///////////////////////////////////////////////////////////////////////////
    //  PROTECTED FUNCTIONS
    //
    protected function urlsafeB64Encode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    protected function sign($msg, $key, $alg = 'HS256')
    {
        $supported_algs = array(
            'HS256' => array('hash_hmac', 'SHA256'),
        );
        list($function, $algorithm) = $supported_algs[$alg];
        switch($function) {
            case 'hash_hmac':
                return hash_hmac($algorithm, $msg, $key, true);
        }
        return false;
    }
}