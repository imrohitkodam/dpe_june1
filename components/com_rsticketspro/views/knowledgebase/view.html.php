<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewKnowledgebase extends JViewLegacy
{
	protected $hot_hits = 0;
	
	public function display($tpl = null)
	{
		$mainframe		= JFactory::getApplication();
		$this->params	= $mainframe->getParams('com_rsticketspro');
		$layout			= $this->getLayout();
		
		if ($layout == 'results')
		{
			$this->items		= $this->get('results');
			$this->pagination	= $this->get('resultspagination');
			$this->word			= $this->get('resultsword');
		}
		else
		{
			$this->model			= $this->getModel('knowledgebase');
			$this->sortColumn		= $this->get('sortcolumn');
			$this->sortOrder		= $this->get('sortorder');
			$this->filter_word		= $this->get('filterword');
			$this->filter_tag		= $this->get('filtertag');
			$this->category			= $this->model->getCategory();
			$this->parent_category	= $this->model->getCategory(array('inherited' => false, 'id' => $this->category->parent_id));
			$this->cid				= $mainframe->input->getInt('cid', 0);
			$this->show_thumbs		= RSTicketsProHelper::getConfig('kb_show_thumbs');
			$this->subcats_limit	= $this->params->get('subcategories_list', -1);
			$this->is_filter_active = (strlen($this->filter_word) > 0);
			$this->category_class	= $this->category->name ? 'rst-kb-' . strtolower(str_replace(' ', '-' , preg_replace('/[^a-zA-Z0-9\s-]/', '', $this->category->name))) : 'rst-kb';
			$this->category_layout	= $this->category->layout ? $this->category->layout : 'list';
			$this->category_columns	= (int) $this->category->columns;
			$this->cat_tag_sorting	= $this->category->tag_sorting;
			
			if (!$this->cid) {
				if ($this->params->get('split_to_tabs', 0) && $this->params->get('top_category_id', 0)) {
					$this->categories	= $this->model->getCategories(array('inherited' => false, 'id' => $this->params->get('top_category_id', 0)));
					$this->items		= $this->model->getContent(array('inherited' => false, 'id' => $this->params->get('top_category_id', 0)));
				} else {
					$this->categories	= $this->model->getCategories();
					$this->items		= $this->model->getContent();
				}
			} else {
				$this->categories	= $this->model->getCategories();
				$this->items		= $this->model->getContent();
			}
			
			$this->pagination		= $this->get('contentpagination');
			
			$mainframe->triggerEvent('onRSTicketsProKnowledgebaseView', array(array('view' => $this)));
		}

		$this->prepareDocument();

		parent::display($tpl);
	}
	
	protected function prepareDocument()
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
		
		// Add meta information from category, if one has been requested
		if (!empty($this->category))
		{
			if (!empty($this->category->meta_description))
			{
				$this->document->setMetaData('description', $this->category->meta_description);
			}
			if (!empty($this->category->meta_keywords))
			{
				$this->document->setMetaData('keywords', $this->category->meta_keywords);
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
	
	public function isHot($hits)
	{
		if (empty($this->hot_hits))
		{
			$this->hot_hits = RSTicketsProHelper::getConfig('kb_hot_hits');
		}
		
		return $hits >= $this->hot_hits;
	}
}