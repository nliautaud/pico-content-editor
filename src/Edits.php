<?php
namespace PicoContentEditor;

/**
 * Files edits manager for PicoContentEditor.
 *
 * @author  Nicolas Liautaud
 * @license http://opensource.org/licenses/MIT The MIT License
 * @link    https://github.com/nliautaud/pico-content-editor
 */
class Edits
{
    /**
     * Key used for $_POST data.
     */
    const KEY = 'PicoContentEditor';
    
    private $meta;
    private $edits;

    /**
     * Editable region blocks parsed from files content.
     * @var array
     */
    private $regions = array();

    /**
     * HTML comment used in pages content to define the end of an editable block.
     *
     * @see self::onContentLoaded()
     * @see self::parseEditableRegions()
     */
    const ENDMARK = '<!--\s*end\s+editable\s*-->';

    /**
     * Decode edited data from POST, if any.
     *
     * @return void
     */
    public function __construct()
    {
        if (!isset($_POST[self::KEY])) {
            return;
        }
        $query = json_decode($_POST[self::KEY]);
        if ($query === null) {
            return 'Can\'t decode editor data';
        }

        $this->meta = isset($query->meta) ? $query->meta : null;
        if (isset($query->regions)) {
            $this->edits = $query->regions;
        }
    }

    /**
     * Look for editable regions in the given string and save those who have been edited.
     *
     * @see self::parseEditableRegions()
     * @see self::saveRegion()
     * @param string $content
     * @param PicoContentEditor $contentEditor
     * @return void
     */
    public function saveRegions($content, $contentEditor, $editorHandler)
    {
        if (!$this->edits) {
            return null;
        }
        $allSaved = null;
        $this->parseEditableRegions($content);
        foreach ($this->regions as $editableRegion) {
            if ($editableRegion->saved === null && isset($this->edits->{$editableRegion->name})) {
                $edit = $this->edits->{$editableRegion->name};
                $editedContent = $editorHandler->getOutput($edit, $editableRegion->markdown);
                $editableRegion->save($editedContent, $contentEditor);
                $allSaved = ($allSaved !== false) && $editableRegion->saved;
            }
        }
        return $allSaved;
    }
    /**
     * Return the list of editable blocks found in the given content.
     *
     * @param string $content
     * @return array Array of objects with : before, name, content, after
     */
    private function parseEditableRegions($content)
    {
        $before = '<[^>]+data-(?P<type>editable|fixture)\s+';
        $before .= 'data-name\s*=\s*[\'"]\s*(?P<name>[^\'"]+?)\s*[\'"][^>]*>\s*\R*';
        $inner = '(?:(?!data-editable|data-fixture).)*?';
        $after = '\R*\s*</[^>]+>\s*\R*';
        $after .= self::ENDMARK;
        $pattern = "`(?P<before>$before)(?P<inner>$inner)(?P<after>$after)`s";
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $this->regions[] = new EditableRegion($match);
        }
    }
    /**
     * Save the edited page meta if there is one.
     *
     * @param string $rawContent The raw page content.
     * @param string $rawMeta The raw page meta.
     * @param string $fileLocation The page file location.
     * @return void
     */
    public function saveMeta($rawContent, $rawMeta, $fileLocation)
    {
        if (!$this->meta) {
            return;
        }
        $rawContent = str_replace($rawMeta, $this->meta, $rawContent, $count);

        if (!$count) {
            Status::add(false, 'Error replacing page meta');
            return;
        }

        // save the source file
        if (!file_put_contents($fileLocation, $rawContent)) {
            Status::add(false, 'Error writing file');
            return;
        }
        Status::add(true, 'Page meta saved<br>Reload the page to see changes.');
    }

    /**
     * Return regions that have been saved (successfully or not).
     * @return array
     */
    public function savedRegions()
    {
        return array_values(array_filter($this->regions, function ($region) {
            return $region->saved !== null;
        }));
    }
    /**
     * Return true if edits have been received.
     */
    public function beenReceived()
    {
        return $this->edits || $this->meta;
    }
    /**
     * Return infos about saved meta and regions
     * that will be included to the server response.
     */
    public function output()
    {
        return (object)[
            'meta' => $this->meta,
            'regions' => $this->savedRegions(),
        ];
    }
}
