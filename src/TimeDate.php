<?php

/**
 * A class representing a timestamp
 */
class TimeDate extends Carbon\Carbon
{
    const DATE_SHORT = "Y-m-d";
    const DATE_FULL = "F d, Y";
    const TIMESTAMP = "H:i";
    const TIMESTAMP_FULL = "H:i:s";
    const FULL = "Y-m-d H:i:s";
    const MYSQL = "Y-m-d H:i:s";

    /**
     * Get the time difference in a human readable format.
     *
     * @param \Carbon\Carbon|\TimeDate $other
     *
     * @return string The time as a human readable string
     */
    public function diffForHumans(Carbon\Carbon $other = null)
    {
        if (self::diffInSeconds($other, true) < 4)
                return "now";
        return parent::diffForHumans($other);
    }

    /**
     * Format the timestamp so that it can be used in mysql queries
     * @return string The formatted time
     */
    public function toMysql()
    {
        return $this->format(self::MYSQL);
    }

    /**
     * Create a timestamp from a string or another object
     * @param  string|DateTime $time
     * @return TimeDate
     */
    public static function from($time)
    {
        if ($time instanceof DateTime)
            return self::instance($time);

        return new self($time);
    }
}