<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Config;
use Log;

class Event extends BaseModel
{
    use SoftDeletes;

    /**
     *  The name of the database table.
     *
     * @var string
     */
    protected $table = 'event';

    /**
     * Create a model at the DEBUG log level.
     *
     * @param  array|string $data
     * @param  Boolean $echo (optional)
     * @return \App\Event
     */
    public static function debug($data, $echo = false)
    {
        return self::logEvent($data, 'debug', $echo);
    }

    /**
     * Create a model at the INFO log level.
     *
     * @param  array $data
     * @param  Boolean $echo (optional)
     * @return \App\Event
     */
    public static function info($data, $echo = false)
    {
        return self::logEvent($data, 'info', $echo);
    }

    /**
     * Create a model at the WARNING log level.
     *
     * @param  array $data
     * @param  Boolean $echo (optional)
     * @return \App\Event
     */
    public static function warning($data, $echo = false)
    {
        return self::logEvent($data, 'warning', $echo);
    }

    /**
     * Create a model at the ERROR log level.
     *
     * @param  array $data
     * @param  Boolean $echo (optional)
     * @return \App\Event
     */
    public static function error($data, $echo = false)
    {
        return self::logEvent($data, 'error', $echo);
    }

    /**
     * Create a model at the SEVERE log level.
     *
     * @param  array $data
     * @param  Boolean $echo (optional)
     * @return \App\Event
     */
    public static function severe($data, $echo = false)
    {
        return self::logEvent($data, 'severe', $echo);
    }

    /**
     * Get the retention policy for the Event Log.
     *
     * The policy will be in the following format: Event::retention::{time string}
     * and will be returned as an array of the components, split on ::
     *
     * @return string|Boolean
     */
    public static function getRetentionPolicy()
    {
        $policy = \App\Policy::fetch('Event::retention');
        if ($policy) {
            return array_pop($policy);
        }

        return false;
    }

    /**
     * Create the Event model, write to the Laravel Log,
     * and optionally echo the event in case anyone's listening.
     *
     * @param  array|string $data
     * @param  string $level
     * @param  Boolean $echo
     * @return \App\Event|Boolean
     */
    protected static function logEvent($data, $level, $echo = false)
    {
        $data = self::checkData($data);
        $data['level'] = strtoupper($level);
        $level = strtolower($level);
        $appLogLevel = strtolower(env('APP_LOG_LEVEL'));

        // do not log the event if the current event's level is below the
        // application's log level threshold
        if (Config::get("constants.log_level.${level}") < Config::get("constants.log_level.${appLogLevel}")) {
            return false;
        }

        $name = $data['name'];
        $description = $data['description'];
        $eventSnippet = "${level}: ${name}. ${description}";
        if ($echo) {
            echo "\n${eventSnippet}\n\n";
        }
        call_user_func("Log::${level}", $eventSnippet);

        return self::create($data);
    }

    /**
     * Check the incoming data for completeness.
     *
     * @param  array|string $data
     * @return array
     */
    protected static function checkData($data)
    {
        // if a string is passed in, assume that is the event description.
        if (is_string($data)) {
            $data = [
                'name' => 'Event',
                'description' => $data,
            ];
        }

        return $data;
    }

}
