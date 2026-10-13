<?php namespace Hampel\LocationExplain;

class Listener
{
    /**
     * Declares which of this add-on's settings are secrets, for an add-on that reports a forum's
     * configuration with the secrets removed. The one option is not: its text is printed on the
     * registration and account details pages. The add-on reads no config.php keys.
     *
     * Revisit this when an option is added or renamed.
     *
     * @param array $declarations
     */
    public static function adminApiRedaction(array &$declarations)
    {
        $declarations['Hampel/LocationExplain'] = [];
    }
}
