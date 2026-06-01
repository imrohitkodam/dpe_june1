<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('text');

class JFormFieldTypeahead extends JFormFieldText
{
	protected function getInput()
	{
		JHtml::_('stylesheet', 'com_rsticketspro/awesomplete.css', array('relative' => true, 'version' => 'auto'));
		JHtml::_('script', 'com_rsticketspro/awesomplete.min.js', array('relative' => true, 'version' => 'auto'));
		JHtml::_('script', 'com_rsticketspro/awesomplete.script.js', array('relative' => true, 'version' => 'auto'));

		$id = json_encode($this->id);
		$messageboxId = json_encode($this->getAttribute('messagebox'));

		$allowEditor = RSTicketsProHelper::getConfig('allow_rich_editor');
		JFactory::getDocument()->addScriptDeclaration("initAwesomplete({$id}, $allowEditor, {$messageboxId});");

		return parent::getInput();
	}
}