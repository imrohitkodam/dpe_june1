<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('JPATH_PLATFORM') or die;

require_once JPATH_ADMINISTRATOR.'/components/com_rsticketspro/helpers/rsticketspro.php';

if (!RSTicketsProHelper::getConfig('allow_predefined_subjects')) {
	JFormHelper::loadFieldClass('text');

	class JFormFieldSubject extends JFormFieldText
	{
		protected $type = 'Subject';
	}
} else {
	JFormHelper::loadFieldClass('list');

	class JFormFieldSubject extends JFormFieldList
	{
		protected $type = 'Subject';

		protected function getOptions() {
			$options = array();
			$db 	= JFactory::getDbo();

			$query 	= $db->getQuery(true);
			$query->select($db->qn('id'))
				->select($db->qn('predefined_subjects'))
				->from($db->qn('#__rsticketspro_departments'))
				->where($db->qn('published').'='.$db->q(1))
				->order($db->qn('ordering').' '.$db->escape('asc'));
			$db->setQuery($query);
			$departments = $db->loadObjectList();

			$doc = JFactory::getDocument();
			$script  = "RSTicketsPro.showPredefinedSubjects = function(department) {\n";
			$script .= "var subjects = {};\n";
			$script .= "subjects[0] = {'':'".JText::_('RST_PLEASE_SELECT_SUBJECT', true)."'};\n";
			foreach ($departments as $department) {
				$subjects 	= RSTicketsProHelper::getJSSubjects($department->predefined_subjects);

				$script 	.= "subjects[".$department->id."] = {".implode(',', $subjects)."};\n";
			}
			$script .= "if (typeof subjects[department.id] != 'undefined') {\n";
			$script .= "RSTicketsPro.populateSelect(document.getElementById('jform_subject'), subjects[department.id]);\n";
			if ($this->value) {
				$script .= "if (typeof jQuery != 'undefined') { jQuery(document.getElementById('jform_subject')).val(".json_encode($this->value)."); }\n";
			}
			$script .= "if (typeof jQuery != 'undefined') { jQuery(document.getElementById('jform_subject')).trigger('liszt:updated'); }\n";
			$script .= "}\n";
			$script .= "}\n";

			$doc->addScriptDeclaration($script);

			return $options;
		}
	}
}