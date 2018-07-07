<?php
namespace PicoContentEditor;

/**
 * Upload manager for PicoContentEditor.
 *
 * @author  Nicolas Liautaud
 * @license http://opensource.org/licenses/MIT The MIT License
 * @link    https://github.com/nliautaud/pico-content-editor
 */
class Uploads
{
    /**
     * PicoContentEditor
     * @var PicoContentEditor
     */
    private $contentEditor;
    private $uploadedFile;
    private $output = null;

    public function beenReceived()
    {
        return $this->uploadedFile !== null;
    }
    public function output()
    {
        return $this->output;
    }

    /**
     * Set @see{self::$edited} according to data sent by the editor.
     *
     * @return void
     */
    public function __construct($contentEditor)
    {
        $this->contentEditor = $contentEditor;

        $key = 'PicoContentEditorUpload';
        $this->uploadedFile = isset($_FILES[$key]) ? $_FILES[$key] : null;

        if (!$this->uploadedFile) {
            return;
        }
        if (Auth::can(Auth::UPLOAD)) {
            $root = $contentEditor->getRootDir();
            $path = $contentEditor->getPluginSetting('uploadpath', 'images');
            $this->saveFile($root, $path);
        } else {
            Status::add(false, 'You don\'t have the rights to upload files');
        }
    }

    /**
     * Save the uploaded file to the given location.
     *
     * @return void
     */
    public function saveFile($root, $path)
    {
        if (!$this->uploadedFile) {
            return;
        }

        $realpath = realpath($root.$path);
        if ($realpath === false || strpos($realpath, $root) !== 0) {
            Status::add(false, "The upload directory \"$path\" is missing or invalid");
            return;
        }

        $filename =  '/' . basename($this->uploadedFile['name']);
        if (move_uploaded_file($this->uploadedFile['tmp_name'], $realpath.$filename)) {
            Status::add(true, 'The file have been uploaded');
            $this->output = (object) [
                'name' => $filename,
                'path' => $this->contentEditor->getBaseUrl().$path.$filename,
                'size' => getimagesize($realpath.$filename)
            ];
            return;
        }
        Status::add(false, 'The file coundn\'t be uploaded');
    }
}
