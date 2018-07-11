<?php
namespace PicoContentEditor\EditorsHandlers;

abstract class AbstractEditorHandler
{
    /**
     * Return the HTML links and scripts tags needed by the editor,
     * that will be included with {{ content_editor }}
     *
     * @param \PicoContentEditor $contentEditor
     * @param string $assetsUrl The URL to the plugin assets directory.
     * @return string
     */
    abstract public static function assets($contentEditor, $assetsUrl);
    
    /**
     * Given a list of edit objects containing some input sent by the editor, populate
     * their `value` field with content that would replace the existing region content.
     *
     * @param array $edits The array of edit objects.
     * @return void
     */
    abstract public static function getOutput($edit, $markdown);

    /**
     * Return an editor handler.
     *
     * @param string $type The editor name.
     * @return AbstractEditorHandler
     */
    public static function getEditor($type)
    {
        switch ($type) {
            case 'quill':
                return new Quill();
            default:
                return new ContentTools();
        }
    }
}
