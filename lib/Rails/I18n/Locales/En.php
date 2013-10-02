<?php
namespace Rails\I18n\Locales;

class En extends AbstractLocales
{
    protected $translations = ['en' => [
        'actionview' => [
            'helper' => [
                'date' => [
                    'less_than_a_minute'=> 'Less than a minute',
                    'one_minute'        => '1 minute',
                    'x_minutes'         => '%{t} minutes',
                    'about_one_hour'    => 'about one hour',
                    'about_x_hours'     => 'about %{t} hours',
                    'one_day'           => '1 day',
                    'x_days'            => '%{t} days',
                    'about_one_month'   => 'about 1 month',
                    'x_months'          => '%{t} months',
                    'about_one_year'    => 'about 1 year',
                    'over_a_year'       => 'over a year',
                    'almost_two_years'  => 'almost 2 years',
                    'about_x_years'     => 'about %{t} years'
                ]
            ]
        ],
        
        'errors' => [
            'messages' => [
                "inclusion" => "is not included in the list",
                "exclusion" => "is reserved",
                "invalid" => "is invalid",
                "confirmation" => "doesn't match confirmation",
                "accepted" => "must be accepted",
                "empty" => "can't be empty",
                "blank" => "can't be blank",
                "too_long" => "is too long (maximum is %{count} characters)",
                "too_short" => "is too short (minimum is %{count} characters)",
                "wrong_length" => "is the wrong length (should be %{count} characters)",
                "not_a_number" => "is not a number",
                "not_an_integer" => "must be an integer",
                "greater_than" => "must be greater than %{count}",
                "greater_than_or_equal_to" => "must be greater than or equal to %{count}",
                "equal_to" => "must be equal to %{count}",
                "less_than" => "must be less than %{count}",
                "less_than_or_equal_to" => "must be less than or equal to %{count}",
                "odd" => "must be odd",
                "even" => "must be even",
                
                # These belong to activerecord
                "uniqueness" => "must be unique"
            ]
        ]
    ]];
}