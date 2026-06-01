<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2018 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('JPATH_PLATFORM') or die;

class JFormFieldRSTicketsProAnonymiseButton extends JFormField
{
	protected $type = 'RSTicketsProAnonymiseButton';
	
	protected function getInput()
    {
        JHtml::_('jquery.framework');
        JHtml::_('script', 'com_rsticketspro/anonymise.js', array('relative' => true, 'version' => 'auto'));
        JText::script('COM_RSTICKETSPRO_ARE_YOU_SURE_YOU_WANT_TO_ANONYMISE');
        JText::script('SUCCESS');
        JText::script('ERROR');

	    return '<div class="alert alert-danger">' . JText::_('PLG_SYSTEM_RSTICKETSPRO_ANONYMISE_INSTRUCTIONS') . '</div>' . '<button type="button" class="btn btn-danger btn-large" id="rst_anonymise_button">' . JText::_('PLG_SYSTEM_RSTICKETSPRO_ANONYMISE_BUTTON') . '</button>';
	}
}