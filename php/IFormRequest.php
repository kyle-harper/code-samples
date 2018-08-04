<?php

/**
 * IFormRequest.php
 *
 * Wrapper for the iFormBuilder 6.x API.
 * Built for PHP version >=5.6.
 *
 * TODO: for GET requests, make $fields parameter optional... return ALL fields if not specified (would need to get a sample record first as a template...)
 *
 * @author     Kyle Harper <kyle.p.harper@gmail.com>
 * @version    0.4.0
 */

namespace App\Classes;

use App\Classes\CurlHelper;
use App\Classes\IFormTokenGenerator;

use Log;

class IFormRequest {

    protected $server;
    protected $tokenUrl;
    protected $clientId;
    protected $clientSecret;
    protected $defaultProfile;

    /**
     * Set the context for the API.
     * Rule: each instance of this class is associated with one
     *       and only one server/profile at a time
     *
     * @return void
     */
    public function __construct($server, $tokenUrl, $clientId, $clientSecret, $defaultProfile)
    {
        $this->server = $server;
        $this->tokenUrl = $tokenUrl;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->profile = $defaultProfile;

        // sets the max number of API calls to make in one iteration/function
        $this->maxRequests = 50;
    }

    /**
     * Add items to the Option List.
     *
     * @param  string  $listId
     * @param  array  $items
     * @return  boolean
     */
    function addOptionListItems($listId, $items)
    {
        if ($listId && $items) {
            $profile = $this->profile;
            $server = $this->server;
            $url = "https://${server}.iformbuilder.com/exzact/api/v60/profiles/${profile}/optionlists/${listId}/options";

            $results = $this->post($url, $items);

            return json_encode($results);
        }

        return false;
    }

    /**
     * Create new records in the specified table.
     *
     * @param  string  $pageId
     * @param  array  $records
     * @return  boolean
     */
    public function createRecords($pageId, $records)
    {
        if ($pageId && $records) {
            $profile = $this->profile;
            $server = $this->server;
            // set the URL of the POST request
            $url = "https://${server}.iformbuilder.com/exzact/api/v60/profiles/${profile}/pages/${pageId}/records";
            $response = $this->post($url, $records);

            return $response;
        }

        return false;
    }

    /**
     * Delete records in the specified table.
     *
     * @param  string  $pageId
     * @param  string  $fields
     * @return  boolean
     */
    public function deleteRecords($pageId, $fields)
    {
        if ($pageId && $fields) {
            $profile = $this->profile;
            $server = $this->server;
            // set the URL of the page for a GET request
            $getUrl = "https://${server}.iformbuilder.com/exzact/api/v60/profiles/${profile}/pages/${pageId}/records?${fields}";
            do {
                $response = json_decode($this->get($getUrl), true);
                if ($response) {
                    // set the URL of the delete request
                    $deleteUrl = "https://${server}.iformbuilder.com/exzact/api/v60/profiles/${profile}/pages/${pageId}/records";
                    $response = $this->delete($deleteUrl, $response);
                }
            } while ($response);

            return true;
        }

        return false;
    }

    /**
     * Get a Bearer token to authenticate to the iFormBuilder API.
     *
     * @return string
     */
    public function generateToken()
    {
        // obtain token
        $t = new IFormTokenGenerator([
            'url' => $this->tokenUrl,
            'profile' => $this->profile,
            'key' => $this->clientId,
            'secret' => $this->clientSecret,
        ]);
        $this->token = $t->getToken();
        $t = null;

        return $this->token;
    }

    /**
     * Get the elements from the requested page.
     *
     * @param  string  $pageId
     * @param  string  $fields
     * @return object
     */
    public function getElements($pageId, $fields = 'id,name')
    {
        $profile = $this->profile;
        $server = $this->server;
        $url = "https://${server}.iformbuilder.com/exzact/api/v60/profiles/${profile}/pages/${pageId}/elements?fields=${fields}";

        return $this->get($url);
    }

    /**
     * Get the requested option list items.
     *
     * @param  string $listId
     * @return object|Boolean
     */
    public function getOptionList($listId)
    {
        $profile = $this->profile;
        $server = $this->server;
        $url = "https://${server}.iformbuilder.com/exzact/api/v60/profiles/${profile}/optionlists/${listId}";

        $ol = $this->get($url);
        if ($ol->count()) {
            return $ol->first();
        }

        return false;
    }

    /**
     * Get the requested iForm option lists associated with the current profile.
     *
     * @param  string  $fields
     * @return object
     */
    public function getOptionLists($fields)
    {
        $profile = $this->profile;
        $server = $this->server;
        $url = "https://${server}.iformbuilder.com/exzact/api/v60/profiles/${profile}/optionlists?fields=${fields}";

        return $this->get($url);
    }

    /**
     * Get the requested iForm option list items.
     *
     * @param string $listId
     * @param string $fields
     * @return array|false
     */
    public function getOptions($listId, $fields='id,key_value,label')
    {
        $profile = $this->profile;
        $server = $this->server;
        if ($listId && $fields) {
            $url = "https://${server}.iformbuilder.com/exzact/api/v60/profiles/${profile}/optionlists/${listId}/options?fields=${fields}";

            return $this->get($url);
        }

        return false;
    }
    // helper function that builds on getOptions() and returns a K:V array of option list items
    public function getOptionsAsKeyValueArray($listId)
    {
        $kvCache = [];
        $items = $this->getOptions($listId);
        if ($items) {
            foreach ($items as $item) {
                $kvCache[$item->key_value] = $item->label;
            }
        }

        return $kvCache;
    }

    /**
     * Get all pages for the current profile.
     *
     * @param string $fields
     * @return  array
     */
    public function getPages($fields)
    {
        $profile = $this->profile;
        $server = $this->server;
        $url = "https://${server}.iformbuilder.com/exzact/api/v60/profiles/${profile}/pages?fields=${fields}";

        return $this->get($url);
    }

    /**
     * Get all profiles.
     *
     * @param  string  $fields
     * @return  array
     */
    public function getProfiles($fields)
    {
        $server = $this->server;
        $url = "https://${server}.iformbuilder.com/exzact/api/v60/profiles?fields=${fields}";

        return $this->get($url);
    }

    /**
     * Get the requested records.
     *
     * @param string $pageId
     * @param string $fields
     * @return array
     */
    public function getRecords($pageId, $fields)
    {
        $profile = $this->profile;
        $server = $this->server;
        if ($pageId && $fields) {
            $url = "https://${server}.iformbuilder.com/exzact/api/v60/profiles/${profile}/pages/${pageId}/records?fields=${fields}";

            return $this->get($url);
        }

        return false;
    }

    /**
     * Get the active API Token.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Determine if an item is in the specified Option List.
     * Checks by label.
     *
     * @param  string  $listId
     * @param  string  $item
     * @return  boolean
     */
    public function inOptionList($listId, $item)
    {
        $profile = $this->profile;
        $server = $this->server;
        if ($listId && $item) {
            $url = "https://${server}.iformbuilder.com/exzact/api/v60/profiles/${profile}/optionlists/${listId}/options?fields=" . urlencode('label(="' . $item['label'] . '")');

            $results = json_decode($this->get($url), true);

            if ($results) {

                return true;
            }
        }

        return false;
    }

    // parse the location string provided by iFormBuilder metadata
    public static function parseLocationString($string, $stringType)
    {
        if ('meta' === $stringType) {
            $data = explode(':', $string);
            if (count($data) > 1) {
                return [
                    'x' => $data[1],
                    'y' => $data[0],
                    'accuracy' => ((int)$data[3] > 5 ? (int)$data[3] : 5) . 'm',
                ];
            } else {
                return [
                    'x' => 0,
                    'y' => 0,
                    'accuracy' => '',
                ];
            }
        } elseif ('widget' === $stringType) {
            $data = explode(',', $string);
            if (count($data) > 4) {
                $accuracy = explode(':', $data[4])[1];
                return [
                    'x' => explode(':', $data[1])[1],
                    'y' => explode(':', $data[0])[1],
                    'accuracy' => ((int)$accuracy > 5 ? (int)$accuracy : 5) . 'm',
                ];
            } else {
                return [
                    'x' => 0,
                    'y' => 0,
                    'accuracy' => '',
                ];
            }
        }
    }

    /**
     * Set the active profile.
     *
     * @param  string  $profileId
     * @return string
     */
    public function setProfile($profileId)
    {
        $this->profile = $profileId;
    }

    /**
     * Sort an option list by the label or key_value.
     *
     * @param  string  $listId
     * @param  string  $sortBy (key_value | label)
     * @return  Boolean
     */
    function sortOptionList($listId, $sortBy = 'label')
    {
        if ($listId) {
            // create the payload
            $items = $this->getOptions($listId, 'id,label,key_value,sort_order')
                          ->sortBy(function($value, $key) use ($sortBy) {
                              return strtolower($value->{"$sortBy"});
                          });
            // --> if the 'Other' option exists, shift it to the top of the list
            $otherOption = $items->firstWhere('label', 'Other');
            if ($otherOption) {
                $items = $items->reject(function($value, $key) {
                    return strtolower($value->label) === 'other';
                });
                $items->prepend($otherOption);
            }
            $i = 0;
            $payload = [];
            foreach ($items as $item) {
                $payload[] = [
                    'id' => $item->id,
                    'sort_order' => $i,
                ];
                $i++;
            }
            // send the request
            $profile = $this->profile;
            $server = $this->server;
            $url = "https://${server}.iformbuilder.com/exzact/api/v60/profiles/${profile}/optionlists/${listId}/options";

            $results = $this->put($url, $payload);

            return json_encode($results);
        }

        return false;
    }

    /**
     * Update a record.
     *
     * @param  string  $pageId
     * @param  string  $recordId
     * @param  array  $payload
     * @return  array
     */
    public function updateRecord($pageId, $recordId, $payload)
    {
        $profile = $this->profile;
        $server = $this->server;

        if ($pageId && $recordId && $payload) {
            $url = "https://${server}.iformbuilder.com/exzact/api/v60/profiles/${profile}/pages/${pageId}/records/${recordId}";

            return $this->put($url, $payload);
        }

        return false;
    }

    //
    // PROTECTED FUNCTIONS
    //

    /**
     * GET from the iFormBuilder API.
     * (may require multiple iterations due to data limits on iFormBuilder's server)
     *
     * @return array
     */
    protected function get($url)
    {
        $ch = new CurlHelper();

        // the 'offset' GET variable is mandatory
        // test to see if there is already a query string,
        // and if so check for the offset variable (add if missing).
        if (strpos($url, 'offset=') === false) {
            $query = parse_url($url, PHP_URL_QUERY);
            if ($query) {
                $prefix = '&';
            } else {
                $prefix = '?';
            }
            $url .= "${prefix}offset=";
        }

        // initialize some variables
        $results = collect([]);
        $offset = 0;
        $iRequestCount = 0;
        $iTokenAttempts = 0;
        $previousResponseCount = 0;
        $keepGoing = true;

        // loop through as many API calls as necessary to get all the records
        do {
            $headers = [
                'Authorization: Bearer ' . $this->token,
            ];
            $response = $ch->get($url . $offset, $headers);
            if ('debug' === env('APP_LOG_LEVEL')) {
                Log::info("IFORM API REQUEST MADE:  GET  ${url}${offset}");
            }

            // analyze the length of the string (for later use to check if we should make another API call)
            $responseKb = mb_strlen($response) / 1024;
            $response = json_decode($response);

            // check the response, and re-up the token if it has expired
            $tokenExpired = isset($response->error_message) && 'invalid access token' === strtolower($response->error_message);
            if ($tokenExpired) {
                if ('debug' === env('APP_LOG_LEVEL')) {
                    Log::info("IFORM TOKEN EXPIRED... GETTING ANOTHER ONE...");
                }
                $this->generateToken();
                $tryAgain = true;
                $iTokenAttempts++;
                continue;
            }
            $tryAgain = false;

            // otherwise merge the response into the result array
            $responseCount = is_array($response) && count($response) ? count($response) : 0;
            if ($responseCount) {
                $results = $results->merge($response);
            } elseif (!isset($response->error_message)) {
                $results = $results->merge([$response]);
            }

            // increment the offset
            $offset += $responseCount;
            $iRequestCount++;
            // sleep(1);

            // keep iterating if:
            //    1. we have not exceeded our max request threshold
            //    AND
            //    2. there are at least 100 records OR 1MB in the response.
            $keepGoing = $iRequestCount < $this->maxRequests &&
                          ($responseCount >= 100 || $responseKb > 1024);

        } while ($keepGoing || ($tryAgain && $iRequestCount < 2));  // the $i counter is a failsafe... I don't want to hit iForm with an infinite loop by mistake.
        $ch = null;

        \App\SyncLog::incrementCount('api_calls', ($iRequestCount + $iTokenAttempts));

        return $results;
    }

    /**
     * POST to the iFormBuilder API.
     *
     * @return string
     */
    protected function post($url, $payload)
    {
        $ch = new CurlHelper();

        // check for a token
        if (!isset($this->token)) {
            $this->generateToken();
        }

        // send the request
        $i = 0;
        do {
            $headers = [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json',
            ];
            $response = $ch->post($url, $payload, $headers);
            $response = json_decode($response);
            // check the response, and re-up the token if it has expired
            $tokenExpired = isset($response->error_message) && 'invalid access token' === strtolower($response->error_message);
            if ($tokenExpired) {
                $this->generateToken();
                $tryAgain = true;
                $i++;
                continue;
            }
            $tryAgain = false;
        } while ($tryAgain && $i < 2);

        if ('debug' === env('APP_LOG_LEVEL')) {
            Log::info("IFORM API REQUEST MADE:  POST  ${url}");
        }
        \App\SyncLog::incrementCount('api_calls');
        $ch = null;

        return $response;
    }

    /**
     * PUT to the iFormBuilder API.
     *
     * @return string
     */
    protected function put($url, $payload)
    {
        $ch = new CurlHelper();

        // check for a token
        if (!isset($this->token)) {
            $this->generateToken();
        }

        // send the request
        $i = 0;
        do {
            $headers = [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json',
            ];
            $response = $ch->put($url, $payload, $headers);
            $response = json_decode($response);
            // check the response, and re-up the token if it has expired
            $tokenExpired = isset($response->error_message) && 'invalid access token' === strtolower($response->error_message);
            if ($tokenExpired) {
                $this->generateToken();
                $tryAgain = true;
                $i++;
                continue;
            }
            $tryAgain = false;
        } while ($tryAgain && $i < 2);

        if ('debug' === env('APP_LOG_LEVEL')) {
            Log::info("IFORM API REQUEST MADE:  PUT  ${url}");
        }
        \App\SyncLog::incrementCount('api_calls');
        $ch = null;

        return $response;
    }

    /**
     * Send a Delete request to the iFormBuilder API.
     *
     * @return string
     */
    protected function delete($url, $payload = [])
    {
        $ch = new CurlHelper();

        // check for a token
        if (!isset($this->token)) {
            $this->generateToken();
        }

        // send the request
        $i = 0;
        do {
            $headers = [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json',
            ];
            $response = $ch->delete($url, $payload, $headers);
            $response = json_decode($response);
            // check the response, and re-up the token if it has expired
            $tokenExpired = isset($response->error_message) && 'invalid access token' === strtolower($response->error_message);
            if ($tokenExpired) {
                $this->generateToken();
                $tryAgain = true;
                $i++;
                continue;
            }
            $tryAgain = false;
        } while ($tryAgain && $i < 2);

        if ('debug' === env('APP_LOG_LEVEL')) {
            Log::info("IFORM API REQUEST MADE:  DELETE  ${url}");
        }
        \App\SyncLog::incrementCount('api_calls');
        $ch = null;

        return $response;
    }

}
