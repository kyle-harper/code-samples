<?php

/**
 * Unix Timestamp Conversion.
 */

namespace App\Classes;

use Carbon\Carbon;

class UnixTimestamp {

    /**
     * @param  string $timezone
     */
    protected $timezone;

    /**
     * Constructor
     */
    public function __construct($timezone)
    {
        $this->timezone = $timezone;
    }

    /**
     * Wrapper for Carbon, allows one-liner to convert a datetime string
     * to a Unix timestamp, accounting for the user's timezone.
     *
     * @param  string $dateString
     * @param  string $inTz (optional)
     * @param  string $outTz (optional)
     * @return int
     */
    public static function createFromString($dateString, $inTz='UTC', $outTz='UTC')
    {
        $dt = new Carbon($dateString, $inTz);
        if ($inTz != $outTz) {
            $dt->tz = $outTz;
        }

        // return strtotime($dt->toDateTimeString());
        return $dt->timestamp;
    }

    /**
     * Add a certain amount of time to the Unix Timestamp.
     *
     * @param  int $unixTimestamp
     * @param  string $addString
     * @return int
     */
    public static function add($unixTimestamp, $addString)
    {
        $offset = (new Carbon($addString))->timestamp - (new Carbon())->timestamp;

        return $unixTimestamp + $offset;
    }

    /**
     * Wrapper for Carbon, allows one-liner to convert a unix timestamp
     * to a datetime string, accounting for the user's timezone.
     *
     * @param  int $timestamp
     * @param  string $inTz (optional)
     * @param  string $outTz (optional)
     * @param  string $format (optional)
     * @return int
     */
    public static function toDateTimeString($timestamp, $inTz='UTC', $outTz='UTC', $format="Y-m-d H:i:s")
    {
        $dt = Carbon::createFromTimestamp($timestamp, $inTz);
        if ($inTz != $outTz) {
            $dt->tz = $outTz;
        }

        return $dt->format($format);
    }

    /**
     * Convert a 'human readable' date range into actual dates of the specified format.
     *
     * @param  \App\User $user
     * @param  string $timePeriod
     * @param  string $startDate (optional, only needed if $timePeriod = 'date range')
     * @param  string $endDate (optional, only needed if $timePeriod = 'date range')
     * @param  string $format (optional, defaults to standard date format Y-m-d H:i:s)
     * @return array
     */
    public static function getDateRange($user, $timePeriod, $startDate = null, $endDate = null, $format = 'Y-m-d H:i:s')
    {
        switch ($timePeriod) {
            case 'week to date':
                if ($format === 'unixTimestamp') {
                    $startTime = UnixTimestamp::createFromString(date('Y-m-d H:i:s', strtotime('this week midnight')), $user->profile->timezone, 'UTC');
                } else {
                    $startTime = date($format, strtotime('this week midnight'));
                }
                break;
            case 'month to date':
                if ($format === 'unixTimestamp') {
                    $startTime = UnixTimestamp::createFromString(date('Y-m-d H:i:s', strtotime('first day of this month midnight')), $user->profile->timezone, 'UTC');
                } else {
                    $startTime = date($format, strtotime('first day of this month midnight'));
                }
                break;
            case 'quarter to date':
                // quarters are Jan 1, Apr 1, Jul 1, Oct 1
                $quarters = ['January 1 midnight', 'April 1 midnight', 'July 1 midnight', 'October 1 midnight'];
                $today = new Carbon();
                foreach ($quarters as $quarter) {
                    $quarterDate = new Carbon($quarter);
                    if ($quarterDate < $today && $today->diffInDays($quarter) < 100) {
                        if ($format === 'unixTimestamp') {
                            $startTime = UnixTimestamp::createFromString(date('Y-m-d H:i:s', strtotime($quarter)), $user->profile->timezone, 'UTC');
                        } else {
                            $startTime = date($format, strtotime($quarter));
                        }
                        break;
                    }
                }
                break;
            case 'year to date':
                if ($format === 'unixTimestamp') {
                    $startTime = UnixTimestamp::createFromString(date('Y-m-d H:i:s', strtotime('first day of January midnight')), env('USER_DEFAULT_TZ'), 'UTC');
                } else {
                    $startTime = date($format, strtotime('first day of January midnight'));
                }
                break;
            case 'date range':
                if ($startDate) {
                    if ($format === 'unixTimestamp') {
                        $startTime = UnixTimestamp::createFromString($startDate, $user->profile->timezone, 'UTC');
                    } else {
                        $startTime = date($format, strtotime($startDate));
                    }
                }
                if ($endDate) {
                    if ($format === 'unixTimestamp') {
                        $endTime = UnixTimestamp::createFromString($endDate, $user->profile->timezone, 'UTC');
                    } else {
                        $startTime = date($format, strtotime($endDate));
                    }
                }
                break;
            default:
                if ($format === 'unixTimestamp') {
                    $startTime = UnixTimestamp::createFromString(date('Y-m-d H:i:s', strtotime($timePeriod . ' midnight')), $user->profile->timezone, 'UTC');
                } else {
                    $startTime = date($format, strtotime($timePeriod . ' midnight'));
                }
        }

        $dateRange = [];
        if (isset($startTime)) {
            $dateRange['start'] = $startTime;
        }
        if (isset($endTime)) {
            $dateRange['end'] = $endTime;
        }

        return $dateRange;
    }
}
