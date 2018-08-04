<?php

/**
 * ArcGisRequest.php
 *
 * Wrapper for the ArcGIS 10.x API.
 * Built for PHP version 5.6 or 7.0.
 *
 *
 * @author     Kyle Harper <kyle.p.harper@gmail.com>
 * @version    0.1.0
 */

namespace App\Classes;

use App\Classes\CurlHelper;

use \Carbon\Carbon;

use Log;

class ArcGisRequest
{
    protected $serverUrl;
    protected $tokenUrl;
    protected $username;
    protected $password;

    /**
     * Set the context for the API.
     *
     * @return void
     */
    public function __construct($serverUrl, $tokenUrl, $username, $password)
    {
        $this->serverUrl = $serverUrl;
        $this->tokenUrl = $tokenUrl;
        $this->username = $username;
        $this->password = $password;

        // sets the max number of API calls to make in one iteration/function
        $this->maxRequests = 50;
        $this->maxTokenAttempts = 2;
        $this->token = null;
    }

    /**
     * Generate an API token.
     *
     * @return string
     */
    public function generateToken()
    {
        $params = [
            'username' => $this->username,
            'password' => $this->password,
            'expiration' => 60,
            'client' => 'requestip',
            'f' => 'json',
        ];
        $ch = new CurlHelper();
        $response = $ch->post($this->tokenUrl, $params);
        $ch = null;
        $responseJson = json_decode($response, true);

        if (isset($responseJson['token'])) {
            $this->token = $responseJson['token'];
        }
        return $this->token;
    }

    /**
     * Return the current API token.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * GET the request.
     *
     * @param string $url
     * @param array $payload
     */
    protected function get($url)
    {
      $ch = new CurlHelper();
      $response = $ch->get($url);
      $ch = null;

      return $response;
    }

    /**
     * POST the request.
     *
     * @param string $url
     * @param array $payload
     */
    protected function post($url, $payload)
    {
      $ch = new CurlHelper();
      $response = $ch->post($url, $payload);
      $ch = null;

      return $response;
    }

    /**
     * Get info about the service.
     *
     * @param  string $serviceUrl
     * @return object
     */
    public function getInfo($serviceUrl)
    {
        // make sure we have a token
        if (!$this->token) {
            $this->generateToken();
        }
        // get the service info
        $url = $serviceUrl . '/info?f=json&token=' . $this->token;
        $response = $this->get($url);

        return json_decode($response);
    }

    /**
     * Get features from the specified Service URL
     *
     * @param  string $serviceUrl
     * @param  array $fields
     * @return array
     */
    public function getFeatures($serviceUrl, $params) {
        // make sure we have a token
        if (!$this->token) {
            $this->generateToken();
        }
        // initialize
        $iRequestCount = 0;
        $iTokenAttempts = 0;
        $results = false;
        $params['f'] = isset($params['f']) ? $params['f'] : 'json';
        $params['resultRecordCount'] = isset($params['resultRecordCount']) ? $params['resultRecordCount'] : 1000;
        $params['resultOffset'] = isset($params['resultOffset']) ? $params['resultOffset'] : 0;
        $url = $serviceUrl . '/query?token=' . $this->token;
        // get service info (so we can add in the OBJECTID and GlobalID fields, and pass codedValue domains through)
        $serviceInfo = $this->getInfo($serviceUrl);
        foreach ($serviceInfo->fields as $field) {
            if (in_array(strtoupper($field->type), ['ESRIFIELDTYPEOID', 'ESRIFIELDTYPEGLOBALID'])) {
                $params['outFields'] = implode(',', array_unique(array_merge(explode(',', $params['outFields']), [$field->name])));
            }
            if (isset($field->domain) && isset($field->domain->codedValues)) {
                $domainKey[$field->name] = [];
                foreach ($field->domain->codedValues as $domainValue) {
                    $domainKey[$field->name][$domainValue->code] = $domainValue->name;
                }
            }
        }
        // keep making requests with offset until we've traversed and cached the entire recordset
        do {
            $response = $this->post($url, $params);
            if ('debug' === env('APP_LOG_LEVEL')) {
                Log::info("ARCGIS API REQUEST MADE:  POST  ${url} (OFFSET: " . $params['resultOffset'] . ')');
            }
            $response = json_decode($response, true);
            // check the response, and re-up the token if it has expired
            $tokenExpired = isset($response->error_message) && 'invalid access token' === strtolower($response->error_message);
            if ($tokenExpired) {
                if ('debug' === env('APP_LOG_LEVEL')) {
                    Log::info("GIS TOKEN EXPIRED... GETTING ANOTHER ONE...");
                }
                $this->generateToken();
                $tryAgain = true;
                $iTokenAttempts++;
                continue;
            }
            $tryAgain = false;

            // merge the response feature array into the results feature array
            $featureCount = is_array($response['features']) && count($response['features']) ? count($response['features']) : 0;
            if ($results && $featureCount) {
                $results['features'] = array_merge($results['features'], $response['features']);
            } elseif ($featureCount) {
                $results = $response;
                // recache the fields in a more useful way, with the name as the key
                $fieldInfos = [];
                foreach ($results['fields'] as $field) {
                    $fieldInfos[$field['name']] = $field;
                }
                $results['fields'] = $fieldInfos;
            }

            // increment the offset
            $params['resultOffset'] += $featureCount;
            $iRequestCount++;

            $keepGoing = isset($response['exceededTransferLimit']) &&
                         $iTokenAttempts < $this->maxTokenAttempts &&
                         $iRequestCount < $this->maxRequests;
            $tryAgain = $tryAgain && ($iTokenAttempts < $this->maxTokenAttempts);
        } while ($keepGoing || $tryAgain);

        // push the coded value domains and service info onto the results
        $results['domains'] = $domainKey;
        $results['info'] = $serviceInfo;
        // recache the fields in a more useful way, with the name as the key (TODO: DRY this up with the other fields array)
        $fieldInfos = [];
        foreach ($results['info']->fields as $field) {
            $fieldInfos[$field->name] = $field;
        }
        $results['info']->fields = $fieldInfos;

        return $results;
    }

    /**
     * Upsert features to the service URL
     *
     * @param  string $serviceUrl
     * @param  array $addFeatures (optional)
     * @param  array $updateFeatures (optional)
     * @return array
     */
    public function upsertFeatures($serviceUrl, $addFeatures = [], $updateFeatures = [])
    {

        // initialize variables
        $gisToken = $this->getToken();
        if (!$gisToken) {
            $gisToken = $this->generateToken();
        }
        $responses = [];

        // insert new features
        if ($addFeatures) {
            $payload = [
                'features' => $addFeatures,
                'f' => 'pjson',
            ];
            $url = $serviceUrl . '/addFeatures?token=' . $gisToken;
            $payload['features'] = json_encode($payload['features']);
            $responses[] = ['INSERTS' => $this->post($url, $payload)];
        }

        // update existing features
        if ($updateFeatures) {
            $payload = [
                'features' => $updateFeatures,
                'f' => 'pjson',
            ];
            $url = $serviceUrl . '/updateFeatures?token=' . $gisToken;
            $payload['features'] = json_encode($payload['features']);
            $responses[] = ['UPDATES' => $this->post($url, $payload)];
        }

        return $responses;

    }

    /**
     *  Convert a date, time, or datetime string into ArcGIS-friendly epoch milliseconds
     *
     */
    public static function convertDateTimeString($dtString, $inTz='America/Los_Angeles', $outTz='UTC')
    {
        if (!$dtString) {

            return null;
        }

        // convert datetime to epoch milliseconds
        // in cases of time only (no date), use negative epoch milliseconds for Dec 30, 1899
        if (strpos($dtString, '-') === false && strpos($dtString, '/') === false && strpos($dtString, ':') !== false) {
            $dtString = '1899-12-30 ' . $dtString;
        }
        if (strpos($dtString, ':') === false) {
            $dtString .= ' 00:00:00';
        }
        $dt = new Carbon($dtString, $inTz);
        $dt->tz = $outTz;
        // $dt = Carbon::createFromFormat('Y-m-d H:i:s', $dtString, $tz);
        $epochDt = Carbon::createFromTimeStamp(0);
        $diff = $dt->diffInSeconds($epochDt) * 1000;
        if ($dt < $epochDt) {
            $diff = $diff * -1;
        }

        return $diff;
    }
}
