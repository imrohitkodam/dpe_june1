<?php
/**
 * @package         Better Preview
 * @version         6.7.1PRO
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://regularlabs.com
 * @copyright       Copyright © 2022 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Language\Text as JText;
use RegularLabs\Library\Document as RL_Document;
use RegularLabs\Library\Extension as RL_Extension;
use RegularLabs\Library\SystemPlugin as RL_SystemPlugin;
use RegularLabs\Plugin\System\BetterPreview\Component;
use RegularLabs\Plugin\System\BetterPreview\Document;
use RegularLabs\Plugin\System\BetterPreview\Params;
use RegularLabs\Plugin\System\BetterPreview\PreLoader;
use RegularLabs\Plugin\System\BetterPreview\Sefs;

// Do not instantiate plugin on install pages
// to prevent installation/update breaking because of potential breaking changes
$input = JFactory::getApplication()->input;
if (in_array($input->get('option'), ['com_installer', 'com_regularlabsmanager']) && $input->get('action') != '')
{
    return;
}

if ( ! is_file(__DIR__ . '/vendor/autoload.php'))
{
    return;
}

require_once __DIR__ . '/vendor/autoload.php';

if ( ! is_file(JPATH_LIBRARIES . '/regularlabs/autoload.php')
    || ! is_file(JPATH_LIBRARIES . '/regularlabs/src/SystemPlugin.php')
)
{
    JFactory::getLanguage()->load('plg_system_betterpreview', __DIR__);
    JFactory::getApplication()->enqueueMessage(
        JText::sprintf('BP_EXTENSION_CAN_NOT_FUNCTION', JText::_('BETTERPREVIEW'))
        . ' ' . JText::_('BP_REGULAR_LABS_LIBRARY_NOT_INSTALLED'),
        'error'
    );

    return;
}

require_once JPATH_LIBRARIES . '/regularlabs/autoload.php';

if ( ! RL_Document::isJoomlaVersion(3, 'BETTERPREVIEW'))
{
    RL_Extension::disable('betterpreview', 'plugin');

    RL_Document::adminError(
        JText::sprintf('RL_PLUGIN_HAS_BEEN_DISABLED', JText::_('BETTERPREVIEW'))
    );

    return;
}

if (true)
{
    class PlgSystemBetterPreview extends RL_SystemPlugin
    {
        public $_lang_prefix     = 'BP';
        public $_enable_in_admin = true;
        public $_page_types      = ['html'];
        public $_jversion        = 3;

        private $_is_preview = false;
        private $class       = null;

        public function __construct(&$subject, $config = [])
        {
            parent::__construct($subject, $config);

            $this->_is_preview = JFactory::getApplication()->input->get('bp_preview');
        }

        protected function handleOnAfterRoute()
        {
            if (JFactory::getApplication()->input->get('bp_generatesefs'))
            {
                require_once 'src/GenerateSefs.php';

                return;
            }

            // only in admin and not on preview pages
            if ( ! ($this->_is_admin || $this->_is_preview))
            {
                return;
            }

            if (JFactory::getApplication()->input->get('bp_purgesefs'))
            {
                Sefs::purge();

                return;
            }

            if (JFactory::getApplication()->input->get('bp_preloader'))
            {
                PreLoader::_();

                return;
            }

            $params = Params::get();

            if ($this->_is_admin && ! $params->display_title_link && ! $params->display_status_link)
            {
                return;
            }

            if ( ! $class = $this->getClass())
            {
                return;
            }

            switch (true)
            {
                case ($this->_is_preview):
                    // Check for request forgeries.
                    $class->checkSession() or jexit(JText::_('JINVALID_TOKEN'));
                    $class->purgeCache();
                    $class->setLanguage();
                    $class->states();
                    break;

                case ($this->_is_admin) :
                    Document::loadScriptsAndCSS();
                    break;
            }
        }

        protected function handleOnContentPrepare($area, $context, &$article, &$params, $page = 0)
        {
            if ( ! $this->_is_preview)
            {
                return false;
            }

            if ( ! $class = $this->getClass())
            {
                return false;
            }

            $class->render($article, $context);

            return true;
        }

        protected function handleOnAfterRender()
        {
            if ( ! $class = $this->getClass())
            {
                return;
            }

            switch (true)
            {
                case ($this->_is_preview):
                    $class->restoreStates();
                    $class->addMessages();
                    break;

                case ($this->_is_admin) :
                    $class->convertLinks();
                    break;
            }
        }

        private function getClass()
        {
            if ( ! is_null($this->class))
            {
                return $this->class;
            }

            $type        = $this->_is_preview ? 'Preview' : 'Link';
            $this->class = Component::getClass($type);

            return $this->class;
        }
    }
}
