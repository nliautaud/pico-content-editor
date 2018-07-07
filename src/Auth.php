<?php
namespace PicoContentEditor;

/**
 * Auth manager for PicoContentEditor.
 *
 * @author  Nicolas Liautaud
 * @license http://opensource.org/licenses/MIT The MIT License
 * @link    https://github.com/nliautaud/pico-content-editor
 */
class Auth
{
    const EDIT = 'edit';
    const SAVE = 'save';
    const UPLOAD = 'upload';

    /**
     * Right to show the content editor.
     * @var bool
     */
    private $canEdit = true;
    /**
     * Right to save pages or themes files.
     * @var bool
     */
    private $canSave = true;
    /**
     * Right to upload files.
     * @var bool
     */
    private $canUpload = true;

    /**
     * Return the class singleton.
     * @return Auth
     */
    final public static function instance()
    {
        static $inst = null;
        if ($inst === null) {
            $inst = new Auth();
        }
        return $inst;
    }
    /**
     * Define rights depending on Pico install.
     *
     * @param Pico $pico
     * @return void
     */
    public static function init($pico)
    {
        // check authentification with PicoUsers
        if (class_exists('PicoUsers')) {
            $PicoUsers = $pico->getPlugin('PicoUsers');
            self::instance()->canEdit = $PicoUsers->hasRight('PicoContentEditor', true);
            self::instance()->canSave = $PicoUsers->hasRight('PicoContentEditor/save');
            self::instance()->canUpload = $PicoUsers->hasRight('PicoContentEditor/upload');
        }
    }

    /**
     * Return if the user has the given right.
     *
     * @param self::EDIT|self::SAVE|self::UPLOAD $right
     * @return boolean
     */
    public static function can($right)
    {
        switch ($right) {
            case self::EDIT:
                return self::instance()->canEdit;
            case self::SAVE:
                return self::instance()->canSave;
            case self::UPLOAD:
                return self::instance()->canUpload;
            default:
                return false;
        }
    }

    /**
     * Set a given right.
     *
     * @param self::EDIT|self::SAVE|self::UPLOAD $right
     * @param boolean $status
     * @return void
     */
    public static function set($right, $status)
    {
        switch ($right) {
            case self::EDIT:
                self::instance()->canEdit = $status;
                break;
            case self::SAVE:
                self::instance()->canSave = $status;
                break;
            case self::UPLOAD:
                self::instance()->canUpload = $status;
                break;
            default:
                return false;
        }
    }
}
