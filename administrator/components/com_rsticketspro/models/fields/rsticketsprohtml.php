<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('JPATH_PLATFORM') or die;

class JFormFieldRsticketsprohtml extends JFormField
{
	protected function getInput()
	{
		$value = $this->value;
		if (!empty($this->element['escape']))
		{
			$value = htmlspecialchars($this->value, ENT_COMPAT, 'utf-8');
		}

		return $value;
	}
}