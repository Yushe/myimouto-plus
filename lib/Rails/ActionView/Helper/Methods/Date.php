<?php
namespace Rails\ActionView\Helper\Methods;

trait Date
{
    public function timeAgoInWords($fromTime, $includeSeconds = false)
    {
        return $this->distanceOfTimeInWords($fromTime, time(), $includeSeconds);
    }
    
    public function distanceOfTimeInWords($fromTime, $toTime = 'now', $includeSeconds = false)
    {
        if (!is_int($fromTime)) {
            $fromTime = strtotime($fromTime);
        }
        if (!is_int($toTime)) {
            $toTime = strtotime($toTime);
        }
        
        $distanceInSeconds = round($toTime - $fromTime);
        
        if ($distanceInSeconds < 0) {
            $distanceInSeconds = round($fromTime - $toTime);
        }
        
        $distanceInMinutes = ceil($distanceInSeconds/60);
        
        if ($distanceInSeconds < 30)
            $t = 'less_than_a_minute';
        elseif ($distanceInSeconds < 90)
            $t = 'one_minute';
        elseif ($distanceInSeconds < 2670)
            $t = ['x_minutes', 't' => $distanceInMinutes];
        elseif ($distanceInSeconds < 5370)
            $t = 'about_one_hour';
        elseif ($distanceInSeconds < 86370)
            $t = ['about_x_hours', 't' => ceil($distanceInMinutes/60)];
        elseif ($distanceInSeconds < 151170)
            $t = 'one_day';
        elseif ($distanceInSeconds < 2591970)
            $t = ['x_days', 't' => ceil(($distanceInMinutes/60)/24)];
        elseif ($distanceInSeconds < 5183970)
            $t = 'about_one_month';
        elseif ($distanceInSeconds < 31536059)
            $t = ['x_months', 't' => ceil((($distanceInMinutes/60)/24)/31)];
        elseif ($distanceInSeconds < 39312001)
            $t = 'about_one_year';
        elseif ($distanceInSeconds < 54864001)
            $t = 'over_a_year';
        elseif ($distanceInSeconds < 31536001)
            $t = 'almost_two_years';
        else
            $t = ['about_x_years', 't' => ceil($distanceInMinutes/60/24/365)];
        
        if (is_array($t))
            $t[0] = 'actionview.helper.date.' . $t[0];
        else
            $t = 'actionview.helper.date.' . $t;
        
        return $this->t($t);
    }
}
