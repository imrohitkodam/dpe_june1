<?php

/**
 * JCH Optimize - Performs several front-end optimizations for fast downloads
 *
 * @package   jchoptimize/joomla-platform
 * @author    Samuel Marshall <samuel@jch-optimize.net>
 * @copyright Copyright (c) 2020 Samuel Marshall / JCH Optimize
 * @license   GNU/GPLv3, or later. See LICENSE file
 *
 * If LICENSE file missing, see <http://www.gnu.org/licenses/>.
 */

defined('_JEXEC') or die;

use JchOptimize\Platform\Utility;
use JchOptimize\Core\FileRetriever;

include_once dirname(dirname(__FILE__)) . '/autoload.php';

/**
 * 
 */
class JFormFieldJchmenuitem extends JFormFieldMenuitem
{

        public $type = 'jchmenuitem';

        /**
         * 
         * @param SimpleXMLElement $element
         * @param type $value
         * @param type $group
         * @return boolean
         */
        public function setup(SimpleXMLElement $element, $value, $group = NULL)
        {
                //$this->loadResources();

                if (!$value)
                {
                        $value = $this->getHomePageLink();
                }

                try
                {
                        $this->checkPcreVersion();
                        $oFileRetriever = FileRetriever::getInstance();
                }
                catch (Exception $ex)
                {
                        $GLOBALS['bTextArea'] = TRUE;

                        JFactory::getApplication()->enqueueMessage($ex->getMessage(), 'warning');

                        return FALSE;
                }

                if (!$oFileRetriever->isHttpAdapterAvailable())
                {
                        return FALSE;
                }

                return parent::setup($element, $value, $group);
        }

        /**
         * 
         * @throws Exception
         */
        protected function checkPcreVersion()
        {
                $pcre_version = preg_replace('#(^\d++\.\d++).++$#', '$1', PCRE_VERSION);

                if (version_compare($pcre_version, '7.2', '<'))
                {
                        throw new Exception('This plugin requires PCRE Version 7.2 or higher to run. Your installed version is ' . PCRE_VERSION);
                }
        }

        /**
         * 
         * @return type
         */
        public static function getHomePageLink()
        {
                $oMenu            = JFactory::getApplication()->getMenu('site');
                $oDefaultMenuItem = $oMenu->getDefault();

                return $oDefaultMenuItem->id;
        }

        /**
         * 
         */
        protected function loadResources()
        {
		if (!defined('JCH_VERSION'))
		{
			define('JCH_VERSION', '5.0.5.22');
		}

                JHtml::script('jui/jquery.min.js', FALSE, TRUE);

                $oDocument = JFactory::getDocument();
                $sScript   = '';

                $oDocument->addStyleSheetVersion(JUri::root(true) . '/media/plg_jchoptimize/css/admin.css', JCH_VERSION);
                $oDocument->addScriptVersion(JUri::root(true) . '/media/plg_jchoptimize/js/admin-joomla.js', JCH_VERSION);
                $oDocument->addScriptVersion(JUri::root(true) . '/media/plg_jchoptimize/js/admin-utility.js', JCH_VERSION);

                $uri         = clone JUri::getInstance();
                $domain      = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port')) . Helper::getBaseFolder();
                $plugin_path = 'plugins/system/jch_optimize/';

                $ajax_url = JURI::getInstance()->toString() . '&jchajax=1';

                $sScript .= <<<JCHSCRIPT
function submitJchSettings(){
        Joomla.submitbutton('plugin.apply');
}                        
jQuery(document).ready(function() {
    jQuery('.collapsible').collapsible();
  });
                        
var jch_form_id = 'jform_params'; 
var jch_observers = [];        
var jch_ajax_url = '$ajax_url';
JCHSCRIPT;

                $oDocument->addScriptDeclaration($sScript);
                $oDocument->addStyleSheet('//netdna.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.css');
                JHtml::script('plg_jchoptimize/jquery.collapsible.js', FALSE, TRUE);

##<procode>##   

                $domain = str_replace('/administrator', '', $domain);

                $ajax_optimizeimages = $ajax_url . '&action=optimizeimages';
                $ajax_filetree       = $ajax_url . '&action=filetree';

                $oDocument->addStyleSheetVersion(JUri::root(true) . '/media/plg_jchoptimize/css/pro-jquery.filetree.css', JCH_VERSION);
                $oDocument->addScriptVersion(JUri::root(true) . '/media/plg_jchoptimize/js/pro-jquery.filetree.js', JCH_VERSION);
                $oDocument->addScriptVersion(JUri::root(true) . '/media/plg_jchoptimize/js/pro-admin-utility.js', JCH_VERSION);

                JHtml::script('jui/jquery.ui.core.js', FALSE, TRUE);

//                $ajax_url     = $domain . '/administrator/index.php?option=com_ajax&plugin=optimizeimages&format=raw';
//                $fileTreePath = $domain . '/administrator/index.php?option=com_ajax&plugin=filetree&format=raw';

                JHtml::stylesheet('plg_jchoptimize/pro-jquery-ui-progressbar.css', array(), TRUE);

                JHtml::script('plg_jchoptimize/pro-jquery.ui.progressbar.js', FALSE, TRUE);

                $message = addslashes(Utility::translate('Please select files or subfolders to optimize'));
                $noproid = addslashes(Utility::translate('Please enter your Download ID on the Pro Options tab'));

                $sScript = <<<JCHSCRIPT
                
jQuery(document).ready( function() {
        jQuery("#file-tree-container").fileTree({
                root: "",
                script: "$ajax_filetree",
                expandSpeed: 100,
                collapseSpeed: 100,
                multiFolder: false
        }, function(file) {});
});

var jch_ajax_optimizeimages = '$ajax_optimizeimages';                        
var jch_message = '$message';   
var jch_noproid = '$noproid';        
                        
JCHSCRIPT;
                $oDocument->addScriptDeclaration($sScript);

##</procode>##                
        }

}
