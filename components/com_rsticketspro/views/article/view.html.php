<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewArticle extends JViewLegacy
{
	public function display($tpl = null)
	{
		$this->params	= JFactory::getApplication()->getParams('com_rsticketspro');
		$this->article	= $this->get('article');
		$this->user		= JFactory::getUser();
		
		if (!$this->article->id || !$this->article->published || (!RSTicketsProHelper::isStaff() && $this->article->private))
		{
			throw new Exception(JText::_('RST_CANNOT_VIEW_ARTICLE'));
		}
		
		$this->prepareDocument();

		parent::display($tpl);
	}
	
	protected function prepareDocument()
	{
		// Title
		$this->document->setTitle($this->article->name);
		
		// Add meta information from article
		if (!empty($this->article->meta_description))
		{
			$this->document->setMetaData('description', $this->article->meta_description);
		}
		if (!empty($this->article->meta_keywords))
		{
			$this->document->setMetaData('keywords', $this->article->meta_keywords);
		}
		
		// Get active menu item
		$active = JFactory::getApplication()->getMenu()->getActive();
		// If it's an article menu item, menu parameteres overwrite article meta.
		if ($active && strpos($active->link, '&view=article&id='))
		{
			// Description
			if ($this->params->get('menu-meta_description'))
			{
				$this->document->setDescription($this->params->get('menu-meta_description'));
			}
			// Keywords
			if ($this->params->get('menu-meta_keywords'))
			{
				$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
			}
			// Robots
			if ($this->params->get('robots'))
			{
				$this->document->setMetadata('robots', $this->params->get('robots'));
			}
		}
		
		// Pathway
		if ($path = $this->get('path'))
		{
			$pathway = JFactory::getApplication()->getPathway();
			foreach ($path as $item)
			{
				$pathway->addItem($item->name, $item->link);
			}
		}
	}
}