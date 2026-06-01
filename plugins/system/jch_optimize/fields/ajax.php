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

use JchOptimize\Core\Helper;
use JchOptimize\Platform\Plugin;
use JchOptimize\Platform\Utility;
use Joomla\CMS\HTML\HTMLHelper;

include_once dirname(dirname(__FILE__)) . '/autoload.php';

class JFormFieldAjax extends JFormField
{
	protected $type = 'ajax';


	public function setup(SimpleXMLElement $element, $value, $group = NULL)
	{

		if (!defined('JCH_VERSION'))
		{
			define('JCH_VERSION', '6.2.1');
		}

		$params = Plugin::getPluginParams();

                if (!defined('JCH_DEBUG'))
                {
                        define('JCH_DEBUG', ($params->get('debug', 0) && JDEBUG));
                }

		static $cnt = 1;

		if($cnt == 1)
		{
			$script_options = array('framework' => false, 'relative' => true);

			if (version_compare(JVERSION, '4.0', 'lt'))
			{
				JHtml::script('jui/jquery.min.js', $script_options);
			}
			else
			{
				HTMLHelper::_('script', 'vendor/jquery/jquery.min.js', $script_options);
			}

			$oDocument = JFactory::getDocument();
			$sScript   = '';

			$options = array('version' => JCH_VERSION);
			$oDocument->addStyleSheet(JUri::root(true) . '/media/plg_jchoptimize/css/admin.css', $options);
			//$oDocument->addStyleSheet(JUri::root(true) . '/media/plg_jchoptimize/css/admin-joomla.css', $options);
			$oDocument->addScript(JUri::root(true) . '/media/plg_jchoptimize/js/admin-joomla.js', $options);
			$oDocument->addScript(JUri::root(true) . '/media/plg_jchoptimize/js/admin-utility.js', $options);

			$uri         = clone JUri::getInstance();
			$domain      = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port')) . Helper::getBaseFolder();
			$plugin_path = 'plugins/system/jch_optimize/';

			$ajax_url = $domain . 'administrator/index.php?option=com_jch_optimize';

			$sScript .= <<<JCHSCRIPT
function submitJchSettings(){
	Joomla.submitbutton('plugin.apply');
}                        
jQuery(document).ready(function() {
    jQuery('.collapsible').collapsible();
  });
			
var jch_observers = [];        
var jch_ajax_url = '$ajax_url';
JCHSCRIPT;

			$oDocument->addScriptDeclaration($sScript);
			$oDocument->addStyleSheet('//netdna.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.css');
			JHtml::script('plg_jchoptimize/jquery.collapsible.js', $script_options);

	##<procode>##   

			$ajax_optimizeimages = $ajax_url . '&action=optimizeimages';
			$ajax_filetree       = $ajax_url . '&action=filetree';

			$oDocument->addStyleSheet(JUri::root(true) . '/media/plg_jchoptimize/css/pro-jquery.filetree.css', $options);
			$oDocument->addScript(JUri::root(true) . '/media/plg_jchoptimize/js/pro-jquery.filetree.js', $options);
			$oDocument->addScript(JUri::root(true) . '/media/plg_jchoptimize/js/pro-admin-utility.js', $options);

			JHtml::stylesheet('plg_jchoptimize/pro-jquery-ui-progressbar.css', $script_options);

			if (version_compare(JVERSION, '4.0', 'lt'))
			{
				JHtml::script('jui/jquery.ui.core.js', $script_options);
			}
			else
			{
				HTMLHelper::_('script', 'vendor/jquery-ui/jquery.ui.core.js', $script_options);
			}

			JHtml::script('plg_jchoptimize/pro-jquery.ui.progressbar.js', $script_options);

			$message = addslashes(Utility::translate('Please select files or subfolders to optimize'));
			$noproid = addslashes(Utility::translate('Please enter your Download ID on the Advanced Features tab'));

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

		$cnt++;
	 
		return false;
	}

        protected function getInput()
	{
		return false;
	}
}
