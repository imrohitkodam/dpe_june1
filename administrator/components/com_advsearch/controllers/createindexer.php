<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_Advsearch
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (c) 2009-2017 TechJoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>.
 * @link        http://techjoomla.com.
 */
// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controllerform');

/**
 * Class Createsearchindexer controller class.
 *
 * @since  3.3
 */
class AdvsearchControllerCreateindexer extends JControllerForm
{
	protected $view_list;
	/**
	 * Constructor
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->view_list = 'createindexer';
		parent::__construct();
	}

	/**
	 * Method to save Indexer
	 *
	 * @return void
	 *
	 * @since   1.6
	 */
	public function saveIndexer()
	{
		$model	= $this->getModel('createindexer');
		$result = $model->saveData();
		$link = JURI::Base() . "index.php?option=com_advsearch&view=searchindexer";
		$msg = "Search Indexer saved successfully";
		$this->setRedirect($link, $msg);
	}

	/**
	 * Method to cancel
	 *
	 * @return void
	 *
	 * @since   1.6
	 */
	public function Cancel()
	{
		$link = JURI::Base() . "index.php?option=com_advsearch&view=searchindexer";
		$this->setRedirect($link);
	}

	/**
	 * Method to delete
	 *
	 * @return void
	 *
	 * @since   1.6
	 */
	public function delete()
	{
		$model	= $this->getModel('createindexer');
		$result = $model->deleteIndexer();
		$link = JURI::Base() . "index.php?option=com_advsearch&view=searchindexer";
		$msg = "Search Indexer Removed successfully";
		$this->setRedirect($link, $msg);
	}

	/**
	 * Method to edit
	 *
	 * @return void
	 *
	 * @since   1.6
	 */
	public function edit()
	{
		$jinput   = JFactory::getApplication()->input;
		$post       = $jinput->get('post');

		if ($post['cid'][0])
		{
			$link = JURI::Base() . "index.php?option=com_advsearch&view=createindexer&layout=edit&id=" . $post['cid'][0];
			$msg = "Search Indexer Edit successfully";
			$this->setRedirect($link, $msg);
		}
	}

	/**
	 * Method to add.
	 *
	 * @return void
	 *
	 * @since   1.6
	 */
	public function add()
	{
			$link = JURI::Base() . "index.php?option=com_advsearch&view=createindexer";
			$msg = "Search Indexer Added successfully";
			$this->setRedirect($link, $msg);
	}
}
