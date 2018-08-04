<?php

/**
 * CurlHelper.php
 *
 * Class to facilitate curl requests
 *
 * Built for PHP version 5.6 or 7.0
 *
 * @author     Kyle Harper <kyle.p.harper@gmail.com>
 * @version    0.1.0
 */

namespace App\Classes;

class CurlHelper {

    //
    // generic curl requests below
    // if this gets unmaintainable, see also: https://github.com/ixudra/curl
    //

    // generic curl GET request
    public function get($url, $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    // generic curl POST request
    public function post($url, $payload, $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            if (in_array('Content-Type: application/json', $headers)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
            }
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        }
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    // generic curl PUT request
    public function put($url, $payload, $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            if (in_array('Content-Type: application/json', $headers)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
            }
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        }
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    // generic curl DELETE request
    public function delete($url, $payload, $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            if (in_array('Content-Type: application/json', $headers)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
            }
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        }
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

}
