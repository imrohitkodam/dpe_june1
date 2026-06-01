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

include_once dirname(__FILE__) . '/auto.php';

class JFormFieldOptimizeimages extends JFormFieldAuto
{

	public $type = 'optimizeimages';

	/**
	 * 
	 * @return type
	 */
	protected function getInput()
	{
		$curl_enabled = function_exists('curl_version') && curl_version();
		// $allow_url_fopen = (bool) ini_get('allow_url_fopen');

		if ($curl_enabled)// && $allow_url_fopen)
		{
			if (JFactory::getApplication()->input->get('jchtask') == 'optimizeimages')
			{
				$this->optimizeImages();
			}

			$field = '<div id="optimize-images-container" >'
				. '<div id="file-tree-container"></div>';

			$field .= '<div id="files-container"></div>';

			$field .= parent::getInput();
			$field .= '<div style="clear:both"></div>';
			$field .= '</div>';
		}
		else
		{
			$header  = JText::_('Error');
			//$message = !$allow_url_fopen ? JText::_('JCH_OPTIMIZE_IMAGE_NO_URL_FOPEN_MESSAGE') : '';
			$message = !$curl_enabled ? JText::_('JCH_OPTIMIZE_IMAGE_NO_CURL_MESSAGE'): $message;

			if (version_compare(JVERSION, '3.0', '<'))
			{
				$field = '<dl id="system-message">
					<dt class="message">' . $header . '</dt>
					<dd class="message warning">
					<ul>
					<li>' . $message . '</li>
					</ul>
					</dd>
					</dl>';
			}
			else
			{
				$field = '<div class="alert">
					<h4 class="alert-heading">' . $header . '</h4>
					<p>' . $message . '</p>
					</div>';
			}
		}

		return $field;
	}

	/**
	 * 
	 * @return string
	 */
	protected function getButtons()
	{
		$page = JURI::getInstance()->toString() . '&jchtask=optimizeimages';

		$aButton              = array();
		$aButton[0]['link']   = '';
		$aButton[0]['icon']   = 'fa-compress';
		$aButton[0]['color']  = '#278EB1';
		$aButton[0]['text']   = Utility::translate('Optimize Images');
		$aButton[0]['script'] = 'onclick="jchOptimizeImages(\'' . $page . '\'); return false;"';
		$aButton[0]['class']  = 'enabled';

		return $aButton;
	}

	/**
	 * 
	 */
	protected function optimizeImages()
	{
		$arr = JFactory::getApplication()->input->getArray(
			array('dir' => 'string', 'cnt' => 'int', 'status' => 'string', 'msg' => 'string'));

		$oController = new JControllerLegacy();

		if ($arr['status'] == 'fail')
		{
			$oController->setMessage(JText::_('The Optimize Image function failed with message "' . $arr['msg'] . '"'),
				'error');
		}
		else
		{
			//$dir = Utility::decrypt($arr['dir']);
			$dir = $arr['dir'];

			$oController->setMessage(sprintf(JText::_('%1$d images optimized in %2$s'), $arr['cnt'], $dir));
		}

		$this->display($oController);
	}

}


?>
