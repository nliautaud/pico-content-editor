<?php
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
ini_set('display_startup_errors',1);
ini_set('display_errors',1);
error_reporting(-1); 
class PicoContentEditor extends AbstractPicoPlugin
{
    private $response;

    /**
     * Triggered after Pico has read all known pages
     *
     * See {@link DummyPlugin::onSinglePageLoaded()} for details about the
     * structure of the page data.
     *
     * @see    Pico::getPages()
     * @see    Pico::getCurrentPage()
     * @see    Pico::getPreviousPage()
     * @see    Pico::getNextPage()
     * @param  array[]    &$pages        data of all known pages
     * @param  array|null &$currentPage  data of the page being served
     * @param  array|null &$previousPage data of the previous page
     * @param  array|null &$nextPage     data of the next page
     * @return void
     */
    public function onPagesLoaded(
        array &$pages,
        array &$currentPage = null,
        array &$previousPage = null,
        array &$nextPage = null
    ) {
        $this->save = isset($_POST['PicoContentEditor']);
        if (!$this->save) return;

        $this->response = new stdClass();

        // check authentification with PicoUsers
        if (class_exists('PicoUsers')) {
            $PicoUsers = $this->getPlugin('PicoUsers');
            $canSave = $PicoUsers->hasRight('PicoContentEditor/save');
            if (!$canSave) {
                $this->setStatus(false, 'Authentification error');
                return;
            }
        }

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
            $before = "(data-editable\s+data-name\s*=\s*['\"]\s*{$name}\s*['\"]\s*>[ \t]*\r?\n?)";
            $after = "(\r?\n?[ \t]*</[^>]+>\s*<!--\s*end\s+editable\s*-->)";
            $pattern = "`$before(.*?)$after`s";
            $content = preg_replace($pattern, "$1$value$3", $content, -1, $count);
            $totalCount += $count;
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
