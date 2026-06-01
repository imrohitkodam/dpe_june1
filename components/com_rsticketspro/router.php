<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined( '_JEXEC' ) or die( 'Restricted access' );

class RsticketsproRouter extends JComponentRouterBase
{
	public function preprocess($query)
	{
		if (!isset($query['Itemid']))
		{
			if ($item = JFactory::getApplication()->getMenu()->getActive())
			{
				$query['Itemid'] = $item->id;
			}
		}

		return $query;
	}

	public function build(&$query)
	{
		JFactory::getLanguage()->load('com_rsticketspro', JPATH_SITE);
		
		$advanced = RSTicketsProHelper::getConfig('kb_advanced_sef');
		$segments = array();

		// get a menu item based on Itemid or currently active
		$menu = JFactory::getApplication()->getMenu();
		$item = $menu->getItem($query['Itemid']);
		
		if (!empty($query['Itemid']) && $item)
		{
			if (isset($item->query['view']) && isset($query['view']) && $item->query['view'] == $query['view'] && !isset($query['cid']) && !isset($query['layout']))
			{
				unset($query['view']);
				return $segments;
			}
		}
		
		if ($advanced) {
			if (!empty($query['view'])) {
				static $cache = array();
				
				if ($query['view'] == 'article') {
					$category = RsticketsproHelperRouter::getCategory($query['cid']);
					
					if (isset($item->query['view']) && $item->query['view'] != 'article') {
						if (!isset($cache[$category])) {
							$query['categories'] = RsticketsproHelperRouter::getCategoryPath($category);
						}
					}
				} elseif ($query['view'] == 'knowledgebase') {
					if (!empty($query['cid'])) {
						$category = (int) $query['cid'];
						if (!isset($cache[$category])) {
							$query['categories'] = RsticketsproHelperRouter::getCategoryPath($category);
						}
					}
				}
			}
		}

		if (!empty($query['view']))
		{
			switch ($query['view'])
			{
				case 'tickets':
					$segments[] = JText::_('RST_SEF_TICKETS');
					break;

				case 'predefinedsearches':
					$segments[] = JText::_('RST_SEF_SEARCHES');
					break;

				case 'users':
					$segments[] = JText::_('RST_SEF_SELECT_USER_FROM_LIST');
					break;

				case 'submit':
					$segments[] = JText::_('RST_SEF_SUBMIT_TICKET');
					break;

				case 'dashboard':
					$segments[] = JText::_('RST_SEF_DASHBOARD');
					break;

				case 'predefinedsearch':
					if (!empty($query['id']))
					{
						$segments[] = JText::_('RST_SEF_EDIT_PREDEFINED_SEARCH');
						$segments[] = $query['id'];
						unset($query['id']);
					}
					else
					{
						$segments[] = JText::_('RST_SEF_NEW_PREDEFINED_SEARCH');
					}
					break;

				case 'knowledgebase':
					if ($advanced) {
						if (!isset($query['layout'])) {
							if (isset($query['categories'])) {
								foreach ($query['categories'] as $category) {
									$segments[] = $category;
								}
								
								unset($query['categories']);
							}
							
							unset($query['view']);
							unset($query['cid']);
							
							return $segments;
						}
					}
					
					if (!isset($query['layout']))
					{
						$query['layout'] = 'default';
					}

					if ($query['layout'] == 'default')
					{
						$segments[] = JText::_('RST_SEF_KB');
						if (!empty($query['cid']))
						{
							$segments[] = $query['cid'];
							unset($query['cid']);
						}
					}
					else
					{
						$segments[] = JText::_('RST_SEF_KB_RESULTS');
					}
					break;

				case 'article':
					if ($advanced) {
						if (isset($query['categories'])) {
							foreach ($query['categories'] as $category) {
								$segments[] = $category;
							}
							
							unset($query['categories']);
						}
						
						if (strpos($query['cid'], ':') !== false) {
							list($id, $alias) = explode(':', $query['cid']);
							$segments[] = $alias;
						}
						
						if (isset($query['draft'])) {
							$segments[] = 'draft';
							unset($query['draft']);
						}
						
						unset($query['view']);
						unset($query['cid']);
						
						return $segments;
					}
					
					$segments[] = JText::_('RST_SEF_KB_ARTICLE');
					if (isset($query['cid']))
					{
						$segments[] = $query['cid'];
						unset($query['cid']);
					}
					if (isset($query['draft']))
					{
						$segments[] = 'draft';
						unset($query['draft']);
					}

					break;

				case 'search':
					if (!empty($query['advanced']))
					{
						$segments[] = JText::_('RST_SEF_ADVANCED_SEARCH');
						unset($query['advanced']);
					}
					else
					{
						$segments[] = JText::_('RST_SEF_SEARCH');
					}
					break;

				case 'ticket':
					if (!empty($query['print']))
					{
						$segments[] = JText::_('RST_SEF_PRINT_TICKET');
						unset($query['print']);
					}
					else
					{
						$segments[] = JText::_('RST_SEF_TICKET');
					}
					if (isset($query['id']))
					{
						$segments[] = $query['id'];
						unset($query['id']);
					}
					break;

				case 'signature':
					$segments[] = JText::_('RST_SEF_SIGNATURE');
					break;

				case 'history':
					$segments[] = JText::_('RST_SEF_HISTORY');
					if (isset($query['id']))
					{
						$segments[] = $query['id'];
						unset($query['id']);
					}
					break;

				case 'notes':
					$segments[] = JText::_('RST_SEF_NOTES');
					if (isset($query['ticket_id']))
					{
						$segments[] = $query['ticket_id'];
						unset($query['ticket_id']);
					}
					break;

				case 'note':
					if (!empty($query['id']))
					{
						$segments[] = JText::_('RST_SEF_EDIT_NOTE');
						if (isset($query['ticket_id']))
						{
							$segments[] = $query['ticket_id'];
							unset($query['ticket_id']);
						}
						$segments[] = $query['id'];
						unset($query['id']);
					}
					else
					{
						$segments[] = JText::_('RST_SEF_ADD_NOTE');
						if (isset($query['ticket_id']))
						{
							$segments[] = $query['ticket_id'];
							unset($query['ticket_id']);
						}
					}
					break;

				case 'ticketmessage':
					$segments[] = JText::_('RST_SEF_EDIT_TICKET_MESSAGE');
					if (isset($query['id']))
					{
						$segments[] = $query['id'];
						unset($query['id']);
					}
					break;

				case 'removedata':
					if (isset($query['layout']) && $query['layout'] == 'success')
					{
						$segments[] = JText::_('RST_SEF_REMOVE_DATA_SUCCESS');
					}
					else
					{
						$segments[] = JText::_('RST_SEF_REMOVE_DATA');
					}
					break;
			}
		}

		if (!empty($query['task']))
		{
			switch ($query['task'])
			{
				case 'predefinedsearch.perform':
					$segments[] = JText::_('RST_SEF_PREDEFINED_SEARCH');
					$segments[] = $query['id'];
					unset($query['task'], $query['id']);
					break;

				case 'ticket.downloadfile':
					$segments[] = JText::_('RST_SEF_DOWNLOAD');
					$segments[] = $query['id'];
					unset($query['task'], $query['id']);
					break;

				case 'ticket.notify':
					$segments[] = JText::_('RST_SEF_NOTIFY_TICKET');
					$segments[] = $query['cid'];
					unset($query['task'], $query['cid']);
					break;

				case 'ticket.close':
					$segments[] = JText::_('RST_SEF_CLOSE_TICKET');
					$segments[] = $query['id'];
					unset($query['task'], $query['id']);
					break;

				case 'ticket.reopen':
					$segments[] = JText::_('RST_SEF_REOPEN_TICKET');
					$segments[] = $query['id'];
					unset($query['task'], $query['id']);
					break;

				case 'ticketmessages.deleteattachment':
					$segments[] = JText::_('RST_SEF_DELETE_ATTACHMENT');
					$segments[] = $query['ticket_id'];
					$segments[] = $query['cid'];
					unset($query['task'], $query['ticket_id'], $query['cid']);
					break;

				case 'resetsearch':
					$segments[] = JText::_('RST_SEF_RESET_SEARCH');
					unset($query['task']);
					break;

				case 'captcha':
					$segments[] = 'captcha';
					unset($query['task']);
					break;

				case 'removedata.process':
					$segments[] = JText::_('RST_SEF_REMOVE_DATA_PROCESS');
					unset($query['task']);
					break;
			}
		}

		unset($query['view'], $query['controller'], $query['file_id']);
		unset($query['tmpl']);
		unset($query['layout']);

		return $segments;
	}

	public function parse(&$segments)
	{
		$lang = JFactory::getLanguage();

		$lang->load('com_rsticketspro', JPATH_SITE, 'en-GB', true);
		$lang->load('com_rsticketspro', JPATH_SITE, $lang->getDefault(), true);
		$lang->load('com_rsticketspro', JPATH_SITE, null, true);
		
		$advanced	= RSTicketsProHelper::getConfig('kb_advanced_sef');
		$query		= array();

		$segments[0] = str_replace(':', '-', $segments[0]);

		switch ($segments[0])
		{
			default:
				if ($advanced) {
					$draft = false;
					
					if ($end = end($segments)) {
						if ($end == 'draft') {
							$draft = true;
							array_pop($segments);
						}
					}
					
					$count = count($segments);
					
					if ($count == 1) {
						$category_id = RsticketsproHelperRouter::getCategoryId($segments[0]);
						$article_id = RsticketsproHelperRouter::getArticleId($segments[0]);
						
						// It can be a category or an article
						if ($category_id) {
							$query['view'] = 'knowledgebase';
							$query['cid']  = $category_id;
						} elseif ($article_id) {
							$query['view'] = 'article';
							$query['cid']  = $article_id;
						}
					} else {
						$last = array_pop($segments);
						
						$category_parent_id = RsticketsproHelperRouter::getCategoryByAlias($segments);
						$category_id = RsticketsproHelperRouter::getCategoryId($last, $category_parent_id);
						$article_id = RsticketsproHelperRouter::getArticleId($last, $category_parent_id);
						
						// Both last and previous $segments are from categories
						if ($category_parent_id && $category_id) {
							$query['view'] = 'knowledgebase';
							$query['cid']  = $category_id;
						} else if ($category_parent_id && $article_id) {
							// The last one is an article
							$query['view'] = 'article';
							$query['cid']  = $article_id;
						}
					}
					
					if ($draft) {
						$query['draft']  = 1;
					}
				}
			break;
			
			case JText::_('RST_SEF_TICKETS'):
				$query['view'] = 'tickets';
				break;

			case JText::_('RST_SEF_SEARCHES'):
				$query['view'] = 'predefinedsearches';
				break;

			case JText::_('RST_SEF_SELECT_USER_FROM_LIST'):
				$query['view'] = 'users';
				$query['layout'] = 'modal';
				$query['tmpl'] = 'component';
				break;

			case JText::_('RST_SEF_SUBMIT_TICKET'):
				$query['view'] = 'submit';
				break;

			case JText::_('RST_SEF_DASHBOARD'):
				$query['view'] = 'dashboard';
				break;

			case JText::_('RST_SEF_EDIT_PREDEFINED_SEARCH'):
				$query['view'] = 'predefinedsearch';
				$query['layout'] = 'edit';
				if (isset($segments[1]))
				{
					$query['id'] = $segments[1];
				}
				break;

			case JText::_('RST_SEF_NEW_PREDEFINED_SEARCH'):
				$query['view'] = 'predefinedsearch';
				$query['layout'] = 'edit';
				break;

			case JText::_('RST_SEF_KB'):
				$query['view'] = 'knowledgebase';
				if (!empty($segments[1]))
				{
					$query['cid'] = $segments[1];
				}
				break;

			case JText::_('RST_SEF_KB_RESULTS'):
				$query['view']   = 'knowledgebase';
				$query['layout'] = 'results';
				break;

			case JText::_('RST_SEF_KB_ARTICLE'):
				$query['view'] = 'article';
				if (!empty($segments[1]))
				{
					$query['cid'] = $segments[1];
				}
				if (!empty($segments[2]))
				{
					$query['draft'] = 1;
				}
				break;

			case JText::_('RST_SEF_ADVANCED_SEARCH'):
				$query['view'] = 'search';
				$query['advanced'] = 'true';
				break;

			case JText::_('RST_SEF_SEARCH'):
				$query['view'] = 'search';
				break;

			case JText::_('RST_SEF_PRINT_TICKET'):
				$query['view'] = 'ticket';
				if (!empty($segments[1]))
				{
					$query['id'] = $segments[1];
				}
				$query['tmpl'] = 'component';
				$query['print'] = 1;
				break;

			case JText::_('RST_SEF_TICKET'):
				$query['view'] = 'ticket';
				if (!empty($segments[1]))
				{
					$query['id'] = $segments[1];
				}
				break;

			case JText::_('RST_SEF_SIGNATURE'):
				$query['view'] = 'signature';
				break;

			case JText::_('RST_SEF_HISTORY'):
				$query['view'] = 'history';
				$query['tmpl'] = 'component';
				if (!empty($segments[1]))
				{
					$query['id'] = $segments[1];
				}
				break;

			case JText::_('RST_SEF_NOTES'):
				$query['view'] = 'notes';
				$query['tmpl'] = 'component';
				if (!empty($segments[1]))
				{
					$query['ticket_id'] = $segments[1];
				}
				break;

			case JText::_('RST_SEF_ADD_NOTE'):
				$query['view'] = 'note';
				$query['layout'] = 'edit';
				$query['tmpl'] = 'component';
				if (!empty($segments[1]))
				{
					$query['ticket_id'] = $segments[1];
				}
				break;

			case JText::_('RST_SEF_EDIT_NOTE'):
				$query['view'] = 'note';
				$query['layout'] = 'edit';
				$query['tmpl'] = 'component';
				if (!empty($segments[1]))
				{
					$query['ticket_id'] = $segments[1];
				}
				if (!empty($segments[2]))
				{
					$query['id'] = $segments[2];
				}
				break;

			case JText::_('RST_SEF_EDIT_TICKET_MESSAGE'):
				$query['view'] = 'ticketmessage';
				$query['tmpl'] = 'component';
				if (!empty($segments[1]))
				{
					$query['id'] = $segments[1];
				}
				break;

			case JText::_('RST_SEF_RESET_SEARCH'):
				$query['task'] = 'resetsearch';
				break;

			case JText::_('RST_SEF_REMOVE_DATA'):
				$query['view'] = 'removedata';
				$query['layout'] = 'default';
				break;

			case JText::_('RST_SEF_REMOVE_DATA_SUCCESS'):
				$query['view'] = 'removedata';
				$query['layout'] = 'success';
				break;

			case JText::_('RST_SEF_REMOVE_DATA_PROCESS'):
				$query['task'] = 'removedata.process';
				break;

			case 'captcha':
				$query['task'] = 'captcha';
				break;

			case JText::_('RST_SEF_DELETE_ATTACHMENT'):
				$query['task'] = 'ticketmessages.deleteattachment';
				$query['ticket_id'] = $segments[1];
				$query['cid'] = $segments[2];
				break;

			case JText::_('RST_SEF_CLOSE_TICKET'):
				$query['task'] = 'ticket.close';
				$query['id'] = $segments[1];
				break;

			case JText::_('RST_SEF_REOPEN_TICKET'):
				$query['task'] = 'ticket.reopen';
				$query['id'] = $segments[1];
				break;

			case JText::_('RST_SEF_DOWNLOAD'):
				$query['task'] = 'ticket.downloadfile';
				$query['id'] = $segments[1];
				break;

			case JText::_('RST_SEF_NOTIFY_TICKET'):
				$query['task'] = 'ticket.notify';
				$query['cid'] = $segments[1];
				break;

			case JText::_('RST_SEF_PREDEFINED_SEARCH'):
				$query['task'] = 'predefinedsearch.perform';
				$query['id'] = $segments[1];
				break;
		}

		$segments = array();

		return $query;
	}
}

class RsticketsproHelperRouter {
	
	public static function getCategoryId($alias, $parent_id = 0) {
		// Fix the alias
		$alias = self::fix($alias);
		
		// Get db object
		$db = JFactory::getDbo();
		
		// Build the query
		$query = $db->getQuery(true)->clear()
			->select($db->qn('id'))
			->from($db->qn('#__rsticketspro_kb_categories'))
			->where($db->qn('alias').' = '.$db->q($alias))
			->where($db->qn('parent_id').' = '.$db->q($parent_id));
		
		// Set the query
		$db->setQuery($query);
		
		// Return the result
		return $db->loadResult();
	}
	
	public static function getArticleId($alias, $category_id = null) {
		// Fix the alias
		$alias = self::fix($alias);
		
		// Get db object
		$db = JFactory::getDbo();
		
		// Build the query
		$query = $db->getQuery(true)->clear()
			->select($db->qn('id'))
			->from($db->qn('#__rsticketspro_kb_content'))
			->where($db->qn('alias').' = '.$db->q($alias));
		
		if ($category_id !== null) {
			$query->where($db->qn('category_id').' = '.$db->q($category_id));
		}
		
		// Set the query
		$db->setQuery($query);
		
		// Return the result
		return $db->loadResult();
	}
	
	public static function getCategory($id) {
		$db = JFactory::getDbo();
		
		$query = $db->getQuery(true)->clear()
			->select($db->qn('category_id'))
			->from($db->qn('#__rsticketspro_kb_content'))
			->where($db->qn('id').' = '.$db->q($id));
		
		$db->setQuery($query);
		return $db->loadResult();
	}
	
	public static function getCategoryByAlias($segments) {
		$db		= JFactory::getDbo();
		$query	= $db->getQuery(true);
		$parent = 0;
		
		foreach ($segments as $segment) {
			$query->clear()
				->select($db->qn('id'))
				->from($db->qn('#__rsticketspro_kb_categories'))
				->where($db->qn('parent_id').' = '.$db->q($parent))
				->where($db->qn('alias').' = '.$db->q($segment));
			$db->setQuery($query);
			$parent = (int) $db->loadResult();
		}
		
		return $parent;
	}
	
	public static function getCategoryPath($id) {
		$db 		= JFactory::getDbo();
		$categories = array();
		
		while ($id > 0) {
			$query = $db->getQuery(true)->clear()
				->select($db->qn('parent_id'))->select($db->qn('alias'))
				->from($db->qn('#__rsticketspro_kb_categories'))
				->where($db->qn('id').' = '.$db->q($id));
			
			$db->setQuery($query);
			if ($category = $db->loadObject()) {
				array_unshift($categories, $category->alias);
				$id = $category->parent_id;
			} else {
				$id = 0;
			}
		}
		
		return $categories;
	}
	
	protected static function fix($url) {
		return str_replace(':', '-', $url);
	}
}