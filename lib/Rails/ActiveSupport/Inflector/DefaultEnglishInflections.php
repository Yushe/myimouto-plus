<?php
namespace Rails\ActiveSupport\Inflector;

class DefaultEnglishInflections extends Inflections
{
    protected $plurals = [
        '/(quiz)$/i' => '\1zes',
        '/^(oxen)$/i' => '\1',
        '/^(ox)$/i' => '\1en',
        '/^(m|l)ice$/i' => '\1ice',
        '/^(m|l)ouse$/i' => '\1ice',
        '/(matr|vert|ind)(?:ix|ex)$/i' => '\1ices',
        '/(x|ch|ss|sh)$/i' => '\1es',
        '/([^aeiouy]|qu)y$/i' => '\1ies',
        '/(hive)$/i' => '\1s',
        '/(?:([^f])fe|([lr])f)$/i' => '\1\2ves',
        '/sis$/i' => 'ses',
        '/([ti])a$/i' => '\1a',
        '/([ti])um$/i' => '\1a',
        '/(buffal|tomat)o$/i' => '\1oes',
        '/(alias|status)$/i' => '\1es',
        '/(bu)s$/i' => '\1ses',
        '/(octop|vir)i$/i' => '\1i',
        '/(octop|vir)us$/i' => '\1i',
        '/^(ax|test)is$/i' => '\1es',
        '/s$/i' => 's',
        '/$/' => 's',
    ];
    
    protected $singulars = [
        '/(database)s$/i' => '\1',
        '/(quiz)zes$/i' => '\1',
        '/(matr)ices$/i' => '\1ix',
        '/(vert|ind)ices$/i' => '\1ex',
        '/^(ox)en/i' => '\1',
        '/(alias|status)(es)?$/i' => '\1',
        '/(octop|vir)(us|i)$/i' => '\1us',
        '/^(a)x[ie]s$/i' => '\1xis',
        '/(cris|test)(is|es)$/i' => '\1is',
        '/(shoe)s$/i' => '\1',
        '/(o)es$/i' => '\1',
        '/(bus)(es)?$/i' => '\1',
        '/^(m|l)ice$/i' => '\1ouse',
        '/(x|ch|ss|sh)es$/i' => '\1',
        '/(m)ovies$/i' => '\1ovie',
        '/(s)eries$/i' => '\1eries',
        '/([^aeiouy]|qu)ies$/i' => '\1y',
        '/([lr])ves$/i' => '\1f',
        '/(tive)s$/i' => '\1',
        '/(hive)s$/i' => '\1',
        '/([^f])ves$/i' => '\1fe',
        '/(^analy)(sis|ses)$/i' => '\1sis',
        '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)(sis|ses)$/i' => '\1sis',
        '/([ti])a$/i' => '\1um',
        '/(n)ews$/i' => '\1ews',
        '/(ss)$/i' => '\1',
        '/s$/i' => '',
    ];
    
    protected $irregulars = [
        'person' => 'people',
        'man' => 'men',
        'child' => 'children',
        'sex' => 'sexes',
        'move' => 'moves',
        'cow' => 'kine',
        'zombie' => 'zombies',
    ];
    
    protected $uncountables = [
        'equipment',
        'information',
        'rice',
        'money',
        'species',
        'series',
        'fish',
        'sheep',
        'jeans',
        'police',
    ];
}