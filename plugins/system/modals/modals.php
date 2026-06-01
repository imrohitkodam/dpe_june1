<?php
/**
 * @package         Modals
 * @version         12.3.5PRO
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://regularlabs.com
 * @copyright       Copyright © 2023 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Language\Text as JText;
use RegularLabs\Library\Document as RL_Document;
use RegularLabs\Library\Extension as RL_Extension;
use RegularLabs\Library\Html as RL_Html;
use RegularLabs\Library\SystemPlugin as RL_SystemPlugin;
use RegularLabs\Plugin\System\Modals\Document;
use RegularLabs\Plugin\System\Modals\Clean;
use RegularLabs\Plugin\System\Modals\Replace;

// Do not instantiate plugin on install pages
// to prevent installation/update breaking because of potential breaking changes
$input = JFactory::getApplication()->input;
if (in_array($input->get('option', ''), ['com_installer', 'com_regularlabsmanager']) && $input->get('action', '') != '')
{
    return;
}

if ( ! is_file(JPATH_LIBRARIES . '/regularlabs/regularlabs.xml')
    || ! class_exists('RegularLabs\Library\Parameters')
    || ! class_exists('RegularLabs\Library\DownloadKey')
    || ! class_exists('RegularLabs\Library\SystemPlugin')
)
{
    JFactory::getLanguage()->load('plg_system_modals', __DIR__);
    JFactory::getApplication()->enqueueMessage(
        JText::sprintf('MDL_EXTENSION_CAN_NOT_FUNCTION', JText::_('MODALS'))
        . ' ' . JText::_('MDL_REGULAR_LABS_LIBRARY_NOT_INSTALLED'),
        'error'
    );

    return;
}

if ( ! RL_Document::isJoomlaVersion(4, 'MODALS'))
{
    RL_Extension::disable('modals', 'plugin');

    RL_Document::adminError(
        JText::sprintf('RL_PLUGIN_HAS_BEEN_DISABLED', JText::_('MODALS'))
    );

    return;
}

if (true)
{
    class PlgSystemModals extends RL_SystemPlugin
    {
        public $_lang_prefix = 'MDL';
        public $_has_tags    = true;
        public $_jversion    = 4;

        public function processArticle(&$string, $area = 'article', $context = '', $article = null, $page = 0)
        {
            Replace::replaceTags($string, $area, $context);
        }

        protected function loadStylesAndScripts(&$buffer)
        {
            Document::loadStylesAndScripts();
        }

        protected function changeDocumentBuffer(&$buffer)
        {
            if (JFactory::getApplication()->input->getInt('ml', 0) && ! JFactory::getApplication()->input->getInt('fullpage', 0))
            {
                Document::setTemplate();
            }

            return Replace::replaceTags($buffer, 'component');
        }

        protected function changeModulePositionOutput(&$buffer, &$params)
        {
            Replace::replaceTags($buffer, 'body');
        }

        protected function changeFinalHtmlOutput(&$html)
        {
            [$pre, $body, $post] = RL_Html::getBody($html);

            Replace::replaceTags($body, 'html');
            Clean::cleanFinalHtmlOutput($pre);

            $html = $pre . $body . $post;

            return true;
        }

        protected function cleanFinalHtmlOutput(&$html)
        {
            Document::removeHeadStuff($html);

            return true;
        }
    }
}
