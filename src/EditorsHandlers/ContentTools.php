<?php
namespace PicoContentEditor\EditorsHandlers;

class ContentTools extends AbstractEditorHandler
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
        $libUrl = rtrim($contentEditor->getPluginSetting(
            'ContentToolsUrl',
            "$assetsUrl/ContentTools"
        ));
        $lang = $contentEditor->getPluginSetting('Language');
        $langData = self::getLanguageContent($lang, $libUrl);

        return <<<EOF
<link href="$libUrl/build/content-tools.min.css" rel="stylesheet">
<script src="$libUrl/build/content-tools.min.js"></script>
<link href="$assetsUrl/style.css" rel="stylesheet">
<script id="ContentToolsLanguage" type="application/json" data-lang="$lang">$langData</script>
<script src="$assetsUrl/editor.js"></script>
EOF;
    }
    
    /**
     * Return a ContentTools translation.
     *
     * @param string $lang The language code.
     * @return string The JSON data.
     */
    private static function getLanguageContent($lang, $libUrl)
    {
        if (!$lang) {
            return;
        }
        $path = "$libUrl/translations/$lang.json";
        return file_get_contents($path);
    }

    /**
     * process the edit input and return the content that will replace the existing one.
     *
     * @param object $edit The edit data object.
     * @return string
     */
    public static function getOutput($edit, $markdown)
    {
        if ($markdown) {
            $converter = new \Markdownify\Converter;
            return $converter->parseString($edit->html);
        }
        return $edit->html;
    }
}
