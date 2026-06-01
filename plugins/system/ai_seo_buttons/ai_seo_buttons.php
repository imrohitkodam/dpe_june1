<?php
// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

class PlgSystemAi_seo_buttons extends CMSPlugin
{
    protected $app;
    protected $autoloadLanguage = false;

    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
        $this->app = Factory::getApplication();
    }

    /**
     * Inject AI SEO buttons into article editor
     */
    public function onBeforeRender()
    {
        $app = $this->app;
        
        // Only in administrator
        if (!$app->isClient('administrator')) {
            return;
        }

        $input = $app->input;
        $option = $input->getCmd('option', '');
        $view = $input->getCmd('view', '');

        // Only on article edit page
        if ($option !== 'com_content' || $view !== 'article') {
            return;
        }

        // Load CSS and JS
        $doc = Factory::getDocument();
        $doc->addStyleSheet(Uri::root(true) . '/plugins/system/ai_seo_buttons/css/ai_seo_buttons.css');
        $doc->addScript(Uri::root(true) . '/plugins/system/ai_seo_buttons/js/ai_seo_buttons.js');

        // Pass AJAX URL to JS - group=content is required for content plugins
        $doc->addScriptOptions('ai_seo_buttons', [
            'ajaxUrl' => Uri::root(true) . '/administrator/index.php?option=com_ajax&plugin=ai_seo&group=content&format=raw'
        ]);
    }
}
