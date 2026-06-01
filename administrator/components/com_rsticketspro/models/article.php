<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelArticle extends JModelLegacy
{
	protected $article;
	
	public function getArticle()
	{
		if ($this->article === null)
		{
			$this->article = JTable::getInstance('Kbcontent','RsticketsproTable');

			if ($this->article->load(JFactory::getApplication()->input->getInt('cid')) && $this->article->id)
			{
				$this->article->categories = array();
				
				if ($this->article->category_id)
				{
					// Get the article categories (recursive)
					$this->getArticleCategories($this->article->category_id);
					
					if ($this->article->categories)
					{
						// Check if the categories are published or private (this article will inherit these properties)
						foreach ($this->article->categories as $category)
						{
							if ($category->private)
							{
								$this->article->private = 1;
							}
							if (!$category->published)
							{
								$this->article->published = 0;
							}
						}
						
						// Sort them the natural way (reverse them)
						krsort($this->article->categories);
					}
				}
				
				// Check if article is draft
				$this->article->is_draft = (JFactory::getApplication()->input->getInt('draft') !== null && $this->article->draft) ? true : false;
				
				// Increment article hits
				$this->article->hit();
				
				// Add the comments section
				$this->article->text .= $this->getCommentsBlock();
			}
		}
		
		return $this->article;
	}

	protected function getArticleCategories($id)
	{
		$category = JTable::getInstance('Kbcategories', 'RsticketsproTable');

		if ($category->load($id))
		{
			$this->article->categories[] = $category;
			
			if ($category->parent_id)
			{
				$this->getArticleCategories($category->parent_id);
			}
		}
	}
	
	protected function getCommentsBlock()
	{
		$article =& $this->article;

		switch (RSTicketsProHelper::getConfig('kb_comments'))
		{
			// RSComments!
			case 'com_rscomments':
				if (file_exists(JPATH_SITE.'/components/com_rscomments/helpers/rscomments.php'))
				{
					require_once JPATH_SITE.'/components/com_rscomments/helpers/rscomments.php';

					return '{rscomments option="com_rsticketspro" id="'.$article->id.'"}';
				}
			break;

			// JComments
			case 'com_jcomments':
				if (file_exists(JPATH_SITE.'/components/com_jcomments/jcomments.php'))
				{
					require_once JPATH_SITE.'/components/com_jcomments/jcomments.php';

					return JComments::showComments($article->id, 'com_rsticketspro', $article->name);
				}
			break;

			// JomComment
			case 'com_jomcomment':
				if (file_exists(JPATH_SITE.'/plugins/content/jom_comment_bot.php'))
				{
					require_once JPATH_SITE.'/plugins/content/jom_comment_bot.php';

					return jomcomment($article->id, 'com_rsticketspro');
				}
			break;
			
			// Facebook
			case 'facebook':
				return '<script src="https://connect.facebook.net/en_US/all.js#xfbml=1"></script><div id="fb-root"></div><fb:comments href="'.RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=article&cid='.$article->id.':'.JFilterOutput::stringURLSafe($article->name), true, 0, true).'" num_posts="5" width="700"></fb:comments>';
			break;
		}
		
		return '';
	}
	
	public function getPath()
	{
		$path = array();
		if ($this->article->categories)
		{
			foreach ($this->article->categories as $category)
			{
				$path[] = (object) array(
					'name' => $category->name,
					'link' => RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=knowledgebase&cid='.$category->id.':'.JFilterOutput::stringURLSafe($category->name))
				);
			}
		}
		
		// Add the article as the last child
		$path[] = (object) array(
			'name' => $this->article->name,
			'link' => ''
		);
		
		return $path;
	}
	
	public function sendfeedback($data)
	{
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_rsticketspro/tables');
		
		$feedback = JTable::getInstance('Kbcontentfeedbacks', 'RsticketsproTable');
		
		$feedback->article_id 		= $data['cid'];
		$feedback->user_id 			= $data['user_id'];
		$feedback->ip 				= $data['ip'];
		$feedback->date_submitted 	= $data['date_submitted'];
		
		$db		= JFactory::getDbo();
		$query	= $db->getQuery(true);

		$query	->update($db->qn('#__rsticketspro_kb_content'))
				->set($db->qn('positive_feedback').'='.$db->qn('positive_feedback').' + 1')
				->where($db->qn('id') .' = ' .(int) $feedback->article_id);
		
		$db->setQuery($query);
		$db->execute();
		
		return $feedback->store();
	}
}