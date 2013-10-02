<?php
namespace Rails\ActionView\Helper;

/**
 * This class shouldn't be extended.
 * Having all these methods in a separed class will make
 * possible to override them in other helpers.
 * Calling one of these methods within a method with the same name
 * can be done by calling base(), which will return the instance of
 * this class.
 */
class Base extends \Rails\ActionView\Helper
{
    use Methods\Form, Methods\Date, Methods\FormTag, Methods\Header,
        Methods\Html, Methods\Number, Methods\Tag, Methods\Text,
        Methods\JavaScript, Methods\Inflections, Methods\Assets;
}
