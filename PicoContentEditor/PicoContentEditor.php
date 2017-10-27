<?php
require_once 'vendor/pixel418/markdownify/src/Converter.php'; 
require_once 'vendor/pixel418/markdownify/src/ConverterExtra.php';
/**
 * A content editor plugin for Pico, using ContentTools.
 *
 * Supports PicoUsers plugin for authentification
 * {@link https://github.com/nliautaud/pico-users}
 * 
 * @author	Nicolas Liautaud
 * @link	https://github.com/nliautaud/pico-content-editor
 * @link    http://picocms.org
 * @license http://opensource.org/licenses/MIT The MIT License
 * @version 0.2.3
 */
class PicoContentEditor extends AbstractPicoPlugin
{
    private $response;

    const ENDMARK = '<!--\s*end\s+editable\s*-->';

    /**
     * Triggered after Pico has read its configuration
     *
     * @see    Pico::getConfig()
     * @param  array &$config array of config variables
     * @return void
     */
    public function onConfigLoaded(array &$config)
    {
        if($this->getConfig('PicoContentEditor.debug')) {
            ini_set('display_startup_errors',1);
            ini_set('display_errors',1);
            error_reporting(-1); 
        }
    }
    /**
     * Remove the end mark of editable blocks in pages.
     * 
     * Triggered after Pico has read the contents of the file to serve
     *
     * @see    Pico::getRawContent()
     * @param  string &$rawContent raw file contents
     * @return void
     */
    public function onContentLoaded(&$rawContent)
    {
        $this->save = isset($_POST['PicoContentEditor']);
        $this->response = new stdClass();

        // check authentification with PicoUsers
        if (class_exists('PicoUsers')) {
            $PicoUsers = $this->getPlugin('PicoUsers');
            $canSave = $PicoUsers->hasRight('PicoContentEditor/save');
            if (!$canSave) {
                $this->setStatus(false, 'Authentification error');
            }
        }
        if ($this->save && $canSave) {
            $this->saveEdits();
        }
        $this->removeMark($rawContent);
    }
    public function saveEdits()
    {
        $regions = json_decode($_POST['PicoContentEditor']);
        $request = $this->getRequestUrl();

        // replace editable blocks in page file content
        $page = $this->getRequestFile();
        $rawPageContent = $this->getRawContent();
        $newPageContent = self::editRegions($rawPageContent, $regions, $editsCount);

        if ($editsCount) $this->saveFile($page, $newPageContent);
        else $this->setStatus(false, 'No corresponding block found');
        
        if ($this->getConfig('PicoContentEditor.debug')) {
            $this->response->debug = (object) array(
                'pagePath' => $page,
                'pageContentRaw' => $rawPageContent,
                'pageContentNew' => $newPageContent,
                'regions' => $regions,
                'request' => $request,
            );
        }
    }
    public function removeMark(&$content)
    {
        $mark = self::ENDMARK;
        $content = preg_replace("`$mark`", '', $content);
    }

    private function setStatus($status, $message)
    {
        $this->response->status = $status;
        $this->response->message = $message;
    }
    private function saveFile($path, $content)
    {
        if (file_put_contents($path, $content)) {
            $this->setStatus(true, 'File saved');
        } else $this->setStatus(false, 'Error writing file');
    }

    private static function editRegions($content, $regions, &$totalCount)
    {
        foreach($regions as $name => $value) {
            $before = "data-editable\s+data-name\s*=\s*['\"]\s*{$name}\s*['\"]";
            $before.= "((?:\s*markdown\s*=\s*['\"]?(?:1|true)['\"]?)?)";
            $before.= "\s*>\s*\r?\n?";
            $mark = self::ENDMARK;
            $after = "\r?\n?\s*</[^>]+>\s*$mark";
            $pattern = "`($before).*?($after)`s";
            $content = preg_replace_callback($pattern, function($matches) use ($value, &$totalCount) {
                list(, $before, $useMarkdown, $after) = $matches;
                if ($useMarkdown) {
                    $converter = new Markdownify\ConverterExtra;
                    $value = $converter->parseString($value);
                }
                $totalCount++;
                return "$before$value$after";
            }, $content);
        }
        return $content;
    }

    /**
     * Register `$this` in the Twig `{{ PagesList }}` variable.
     *
     * Triggered before Pico renders the page
     *
     * @see    Pico::getTwig()
     * @see    DummyPlugin::onPageRendered()
     * @param  Twig_Environment &$twig          twig template engine
     * @param  array            &$twigVariables template variables
     * @param  string           &$templateName  file name of the template
     * @return void
     */
    public function onPageRendering(Twig_Environment &$twig, array &$twigVariables, &$templateName)
    {
        $pluginurl = $this->getBaseUrl() . basename($this->getPluginsDir()) . '/PicoContentEditor';
        $twigVariables['content_editor'] = <<<EOF
        <link rel="stylesheet" type="text/css" href="$pluginurl/assets/contenttools/content-tools.min.css">
        <script src="$pluginurl/assets/contenttools/content-tools.min.js"></script>
        <script src="$pluginurl/assets/editor.js"></script>
EOF;
    }
    /**
     * Output the page data in the defined format.
     *
     * Triggered after Pico has rendered the page
     *
     * @param  string &$output contents which will be sent to the user
     * @return void
     */
    public function onPageRendered(&$output)
    {
        if (!$this->save) return;
        $output = json_encode($this->response);
    }
}
?>
