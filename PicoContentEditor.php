<?php
require __DIR__ . '/vendor/autoload.php';

use PicoContentEditor\Status;
use PicoContentEditor\Auth;
use PicoContentEditor\Edits;
use PicoContentEditor\Uploads;
use PicoContentEditor\EditorsHandlers\AbstractEditorHandler;

/**
 * A content editor plugin for Pico 2, using ContentTools.
 *
 * Supports PicoUsers plugin for authentification
 * {@link https://github.com/nliautaud/pico-users}
 *
 * @author  Nicolas Liautaud
 * @license http://opensource.org/licenses/MIT The MIT License
 * @link    https://github.com/nliautaud/pico-content-editor
 * @link    http://picocms.org
 */
class PicoContentEditor extends AbstractPicoPlugin
{
    /**
     * Pico API version.
     * @var int
     */
    const API_VERSION = 2;

    /**
     * EditorHandler (ContentToolsHandler, QuillHandler...)
     * @var AbstractEditorHandler
     */
    private $editor;
    /**
     * Files edits manager.
     * @var Edits
     */
    private $edits;
    /**
     * Uploads manager.
     * @var Uploads
     */
    private $uploads;

    /**
     * Init plugin, process page edits and file uploads.
     *
     * The end-editable mark @see{Edits::ENDMARK} need to be
     * striped away because it's somewhat breaking the page rendering,
     * and thus @see{Edits::saveRegions()}  has to be done here
     * in addition to @see{self::onPageRendered()}.
     *
     * Triggered after Pico has read the contents of the file to serve
     *
     * @see    Pico::getRawContent()
     * @param  string &$rawContent raw file contents
     * @return void
     */
    public function onContentLoaded(&$rawContent)
    {
        if ($this->getPluginSetting('debug')) {
            ini_set('display_startup_errors', 1);
            ini_set('display_errors', 1);
            error_reporting(-1);
        }

        Auth::init($this);
        $editorName = $this->getPluginSetting('editor');
        $this->editor = AbstractEditorHandler::getEditor($editorName);
        $this->uploads = new Uploads($this);
        $this->edits = new Edits();
        
        $this->processPageEdits($rawContent);

        // remove the end-editable mark
        $rawContent = preg_replace('`'.Edits::ENDMARK.'`', '', $rawContent);
    }
    /**
     * Register `{{ content_editor }}` who outputs the editor scripts
     * and `{{ content_editor_meta }}` who outputs the metadata editor.
     *
     * Triggered before Pico renders the page
     *
     * @see DummyPlugin::onPageRendered()
     * @param string &$templateName  file name of the template
     * @param array  &$twigVariables template variables
     * @return void
     */
    public function onPageRendering(&$templateName, array &$twigVariables)
    {
        if (!$this->getPluginSetting('show', true) || !Auth::can(Auth::EDIT)) {
            return;
        }

        $pluginDirUrl = $this->getBaseUrl() . basename($this->getPluginsDir());
        $assetsUrl = "$pluginDirUrl/PicoContentEditor/src/assets/";
       
        $twigVariables['content_editor'] = <<<EOF
        <link href="$assetsUrl/noty/lib/noty.css" rel="stylesheet">
        <link href="$assetsUrl/noty/themes/mint.css" rel="stylesheet">
        <script src="$assetsUrl/noty/lib/noty.min.js" type="text/javascript"></script>
EOF;
        $twigVariables['content_editor'] .= $this->editor::assets($this, $assetsUrl);

        $twigVariables['content_editor_meta'] = <<<EOF
        <div class="ContentEditor">
            <pre class="ContentEditor_Meta" data-fixture data-meta>{$this->getRawMeta()}</pre>
        </div>
EOF;
    }
    /**
     * If the call is a save query, save the edited regions and output the JSON response.
     *
     * Triggered after Pico has rendered the page
     *
     * @param  string &$output contents which will be sent to the user
     * @return void
     */
    public function onPageRendered(&$output)
    {
        $this->processOverallEdits($output);

        if (!$this->edits->beenReceived() && !$this->uploads->beenReceived()) {
            return;
        }

        // output response
        $response = new stdClass();
        $response->status = Status::getStatus();
        $response->edited = $this->edits->output();
        $response->file = $this->uploads->output();
        $response->debug = $this->getPluginSetting('debug', false);
        $output = json_encode($response);
    }
    /**
     * Save page metadata and edited regions if authorized.
     *
     * @param  string $rawContent raw file contents
     * @return void
     */
    public function processPageEdits($rawContent)
    {
        if (!$this->edits->beenReceived()) {
            return;
        }
        if (Auth::can(Auth::SAVE)) {
            $this->edits->saveMeta(
                $rawContent,
                $this->getRawMeta(),
                $this->getRequestFile()
            );
            $saved = $this->edits->saveRegions($rawContent, $this, $this->editor);
            if ($saved) {
                Status::add(true, 'The page have been saved');
            }
        } else {
            Status::add(false, 'You don\'t have the rights to save content');
        }
    }
    /**
     * Save edited regions from overall output if authorized, including themes files.
     *
     * @param  string $output contents which will be sent to the user
     * @return void
     */
    public function processOverallEdits($output)
    {
        if (!$this->edits->beenReceived()) {
            return;
        }
        if (Auth::can(Auth::SAVE)) {
            $saved = $this->edits->saveRegions($output, $this, $this->editor);
            if ($saved) {
                Status::add(true, 'The theme files have been saved');
            }
        }
    }
    /**
     * Return the current page raw metadata.
     *
     * @return string
     */
    private function getRawMeta()
    {
        $pattern = "/^(\/(\*)|---)[[:blank:]]*(?:\r)?\n"
            . "(?:(.*?)(?:\r)?\n)?(?(2)\*\/|---)[[:blank:]]*(?:(?:\r)?\n|$)/s";
        if (preg_match($pattern, $this->getRawContent(), $rawMetaMatches)
        && isset($rawMetaMatches[3])) {
            return $rawMetaMatches[3];
        }
    }
    
    /**
     * Return a plugin setting, either on the page metadata or on the pico config file.
     *
     * @param string $name   name of a setting
     * @param mixed $default optional default value to return when the setting doesn't exist
     * @return mixed  return the setting value from the page metadata, or from the config file,
     *                or the given default value, or NULL
     */
    public function getPluginSetting($name, $default = null, $caseSensitive = false)
    {
        if ($name === null) {
            return null;
        }
        if (!$caseSensitive) {
            $name = strtolower($name);
        }

        static $c;
        static $clow;
        if (!$c) {
            $c = get_called_class();
            if (!$caseSensitive) {
                $clow = strtolower($c);
            }
        }
        
        // from page metadata
        static $pageMeta;
        if (!$pageMeta) {
            $pageMeta = $this->getFileMeta();
            if ($pageMeta && !$caseSensitive) {
                $pageMeta = self::deepArrayKeyCase($pageMeta, CASE_LOWER);
            }
        }
        if ($pageMeta && isset($pageMeta[$clow]) && isset($pageMeta[$clow][$name])) {
            return $pageMeta[$clow][$name];
        }

        // from config file
        static $pluginConfig;
        if (!$pluginConfig) {
            $pluginConfig = $this->getConfig($c, array());
            if (!$caseSensitive) {
                $pluginConfig = self::deepArrayKeyCase($pluginConfig, CASE_LOWER);
            }
        }
        return isset($pluginConfig[$name]) ? $pluginConfig[$name] : $default;
    }

    /**
     * Change the case of every array keys, recursively.
     *
     * @param array $arr
     * @param CASE_UPPER|CASE_LOWER $case
     * @return array
     */
    private static function deepArrayKeyCase($arr, $case)
    {
        return array_map(function ($item) use ($case) {
            if (is_array($item)) {
                $item = self::deepArrayKeyCase($item, $case);
            }
            return $item;
        }, array_change_key_case($arr, $case));
    }
}
