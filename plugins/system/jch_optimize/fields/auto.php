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

use JchOptimize\Core\Admin;

//Autoloader may have already been loaded
if (!class_exists('JchOptimize\\Core\\Admin'))
{
	include_once dirname(dirname(__FILE__)) . '/autoload.php';
}

abstract class JFormFieldAuto extends JFormField {

	protected $bResources = FALSE;

	public function setup(SimpleXMLElement $element, $value, $group = NULL) {
		return parent::setup($element, $value, $group);
	}

	/**
	 * 
	 * @return string
	 */
	protected function getInput() {
		//JCH_DEBUG ? Profiler::mark('beforeGetInput - ' . $this->type) : null;

		$aButtons = $this->getButtons();
		$sField = Admin::generateIcons($aButtons);

		// JCH_DEBUG ? Profiler::mark('beforeGetInput - ' . $this->type) : null;

		return $sField;
	}

	/**
	 * 
	 * @param type $oController
	 */
	protected static function display($oController) {
		$oUri = clone JUri::getInstance();
		$oUri->delVar('jchtask');
		$oUri->delVar('jchdir');
		$oUri->delVar('status');
		$oUri->delVar('msg');
		$oUri->delVar('dir');
		$oUri->delVar('cnt');
		$oController->setRedirect($oUri->toString());
		$oController->redirect();
	}

	abstract protected function getButtons();
}
