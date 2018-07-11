<?php
namespace PicoContentEditor;

/**
 * Editable region of PicoContentEditor.
 *
 * @author  Nicolas Liautaud
 * @license http://opensource.org/licenses/MIT The MIT License
 * @link    https://github.com/nliautaud/pico-content-editor
 */
class EditableRegion
{
    private $before;
    private $inner;
    private $after;

    public $name = null;
    public $source = null;
    public $type = null;
    public $markdown = false;
    public $saved = null;
    public $message = null;

    public function __construct($match)
    {
        $this->before = $match['before'];
        $this->inner = $match['inner'];
        $this->after = $match['after'];
        $this->type = $match['type'];
        $this->name = $match['name'];

        $hasSource = preg_match(
            "`\s+data-src\s*=\s*['\"]([^'\"]*?)['\"]`",
            $this->before,
            $matches
        );
        if ($hasSource) {
            $this->source = $matches[1];
        }

        $this->markdown = preg_match(
            "`\s+markdown\s*=\s*['\"]?\s*(?:1|true)\s*['\"]?`",
            $this->before
        );
    }
    /**
     * Save the region.
     *
     * @param string $inner The new region content.
     * @param PicoContentEditor $contentEditor
     * @return void
     */
    public function save($inner, $contentEditor)
    {
        $this->saved = false;

        // get the source file path as given by a src attribute, or the current page
        if ($this->source) {
            $file = $contentEditor->getRootDir() . $this->source;
        } else {
            $file = $contentEditor->getRequestFile();
        }

        if (!file_exists($file)) {
            $this->message = 'Source file not found';
            return;
        }

        // load the source file and replace the block with new content
        $content = file_get_contents($file);
        $content = preg_replace('`\R`', "\n", $content); // EOL normalization
        $content = str_replace(
            $this->before . $this->inner . $this->after,
            $this->before . $inner . $this->after,
            $content,
            $count
        );
        if (!$count) {
            $this->message = 'Error replacing region content';
            return;
        }
        // save the source file
        $this->saved = file_put_contents($file, $content);
        if (!$this->saved) {
            $this->message = 'Error writing file';
            return;
        }
        if ($this->markdown) {
            $this->message = 'Saved to markdown';
        } else {
            $this->message = 'Saved';
        }
    }
}
