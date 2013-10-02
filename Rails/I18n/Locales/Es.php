<?php
namespace Rails\I18n\Locales;

class Es extends AbstractLocales
{
    protected $translations = ['es' => [
        'actionview' => [
            'helper' => [
                'date' => [
                    'less_than_a_minute'=> 'menos de un minuto',
                    'one_minute'        => '1 minuto',
                    'x_minutes'         => '%{t} minutos',
                    'about_one_hour'    => 'alrededor de una hora',
                    'about_x_hours'     => 'alrededor de %{t} horas',
                    'one_day'           => '1 día',
                    'x_days'            => '%{t} días',
                    'about_one_month'   => 'alrededor de 1 mes',
                    'x_months'          => '%{t} meses',
                    'about_one_year'    => 'alrededor 1 año',
                    'over_a_year'       => 'más de un año',
                    'almost_two_years'  => 'casi 2 años',
                    'about_x_years'     => 'alrededor de %{t} años'
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