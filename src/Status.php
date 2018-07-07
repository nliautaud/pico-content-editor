<?php
namespace PicoContentEditor;

/**
 * Status singleton for PicoContentEditor.
 *
 * @author  Nicolas Liautaud
 * @license http://opensource.org/licenses/MIT The MIT License
 * @link    https://github.com/nliautaud/pico-content-editor
 */
class Status
{
    /**
     * Array of status logs that are returned in the JSON response
     * and used to display status messages on the client.
     * @see self::addStatus()
     * @var array
     */
    private $status;

    /**
     * Return the class singleton.
     * @return Status
     */
    final public static function instance()
    {
        static $inst = null;
        if ($inst === null) {
            $inst = new Status();
        }
        return $inst;
    }

    /**
     * This class is a singleton.
     * @see self::Instance
     */
    private function __construct()
    {
        $this->status = array();
    }
    
    /**
     * Statically adds a status entry.
     *
     * @see self::$status;
     * @param bool $state
     * @param string $message
     * @return void
     */
    final public static function add($state, $message)
    {
        self::instance()->status[] = (object) array(
            'state' => $state,
            'message' => $message
        );
    }
    /**
     * Statically adds a status entry.
     *
     * @see self::$status;
     * @param bool $state
     * @param string $message
     * @return void
     */
    final public static function getStatus()
    {
        return self::instance()->status;
    }
}
