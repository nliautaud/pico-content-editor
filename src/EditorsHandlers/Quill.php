<?php
namespace PicoContentEditor\EditorsHandlers;

class Quill extends AbstractEditorHandler
{
    /**
     * Return the HTML links and scripts tags needed by the editor,
     * that will be included with {{ content_editor }}
     *
     * @param \PicoContentEditor $contentEditor
     * @param string $assetsUrl The URL to the plugin assets directory.
     * @return string
     */
    public static function assets($contentEditor, $assetsUrl)
    {
        return <<<EOF
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<link href="$assetsUrl/quill/themes/quill.bubble.wysiwyg.css" rel="stylesheet">
<script src="$assetsUrl/quill/modules/image-resize.min.js"></script>
<script src="$assetsUrl/quill/quill-editor.js"></script>
EOF;
    }

    /**
     * process the edit input and return the content that will replace the existing one.
     *
     * @param object $edit The edit data object.
     * @return string
     */
    public static function getOutput($edit, $markdown)
    {
        $format = $markdown ?
            \DBlackborough\Quill\Options::FORMAT_MARKDOWN :
            \DBlackborough\Quill\Options::FORMAT_HTML;
        $quill = new \DBlackborough\Quill\Render($edit->deltas, $format);
        return $quill->render();
    }
}
