<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2018 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewRemovedata extends JViewLegacy
{
	public function display($tpl = null)
    {
        $user       = JFactory::getUser();
        $this->app	= JFactory::getApplication();

        // not logged in?
        if (strtolower($this->getLayout()) == 'default' && !$user->get('id'))
        {
            $link = base64_encode((string) JUri::getInstance());
            $this->app->redirect(JRoute::_('index.php?option=com_users&view=login&return='.$link, false));
        }

        $this->globalMessage	        = JText::_(RSTicketsProHelper::getConfig('global_message'));
	    $this->globalMessagePosition	= RSTicketsProHelper::getConfig('global_message_position');

        $this->params			        = $this->app->getParams('com_rsticketspro');
        $this->show_footer		        = RSTicketsProHelper::getConfig('rsticketspro_link');
        $this->footer			        = RSTicketsProHelper::getFooter();
        $this->allow_self_anonymisation = RSTicketsProHelper::getConfig('allow_self_anonymisation') && !$user->authorise('core.admin');
        $this->anonymise_joomla_data    = RSTicketsProHelper::getConfig('anonymise_joomla_data');
        $this->email                    = $user->email;

		$this->prepareDocument();

		parent::display($tpl);
	}

    protected function prepareDocument()
    {
        // Description
        if ($desc = $this->params->get('menu-meta_description'))
        {
            $this->document->setDescription($desc);
        }
        // Keywords
        if ($keywords = $this->params->get('menu-meta_keywords'))
        {
            $this->document->setMetadata('keywords', $keywords);
        }
        // Robots
        if ($robots = $this->params->get('robots'))
        {
            $this->document->setMetadata('robots', $robots);
        }
    }
}