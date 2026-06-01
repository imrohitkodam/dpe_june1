<?php
/**    
 * SEO Glossary Glossaries model
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.modellist');
jimport('joomla.filesystem.folder');

/**
 * Methods supporting a list of Seoglossary records.
 */
class SeoglossaryModelGlossaries extends JModelList
{

    /**
     * Constructor.
     *
     * @param    array    An optional associative array of configuration settings.
     * @see        JController
     * @since    1.6
     */
    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'id', 'a.id',
                'ordering', 'a.ordering',
                'state', 'a.state',
                'tterm', 'a.tterm',
                'tsynonyms', 'a.tsynonyms',
                'tfirst', 'a.tfirst',
                'tdefinition', 'a.tdefinition',
                'tname', 'a.tname',
				'tmore', 'a.tmore',
                'tmail', 'a.tmail',
                'tcomment', 'a.tcomment',
                'tedit', 'a.tedit',
                'catid', 'a.catid',
				'catname', 'a.catname',
            );
        }

        parent::__construct($config);
    }


	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = JFactory::getApplication('administrator');
		
		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
		$this->setState('filter.search', $search);
		
		$catid = $app->getUserStateFromRequest($this->context.'.filter.catid', 'filter_catid');
		$this->setState('filter.catid', $catid);

		$published = $app->getUserStateFromRequest($this->context.'.filter.state', 'filter_published', '', 'string');
		$this->setState('filter.state', $published);

		// Load the parameters.
		$params = JComponentHelper::getParams('com_seoglossary');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('a.tterm', 'asc');
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param	string		$id	A prefix for the store id.
	 * @return	string		A store id.
	 * @since	1.6
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id.= ':' . $this->getState('filter.search');
		$id.= ':' . $this->getState('filter.state');

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	JDatabaseQuery
	 * @since	1.6
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db		= $this->getDbo();
		$query	= $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				'a.*'
			)
		);
		$query->from('#__seoglossary AS a');


		// Join over the users for the checked out user.
		$query->select('uc.name AS editor');
		$query->join('LEFT', '#__users AS uc ON uc.id=a.checked_out');
		
		$query->select('cat.name AS catname');
		$query->join('LEFT', '#__seoglossaries AS cat ON cat.id = a.catid');
	

		// Filter by category
		$catid = $this->getState('filter.catid');
		if (is_numeric($catid)) {
			$query->where('a.catid = '. (int)$catid);
		}
	
	
		// Filter by published state
		$published = $this->getState('filter.state');
		if (is_numeric($published)) {
			$query->where('a.state = '.(int) $published);
		} else if ($published === '') {
			$query->where('(a.state IN (0, 1))');
		}
                    

		// Filter by search in title
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			if (stripos($search, 'id:') === 0) {
				$query->where('a.id = '.(int) substr($search, 3));
			} else {
				$search = $db->Quote('%'.$db->escape($search, true).'%');
                $query->where('( a.tterm LIKE '.$search.' OR a.tsynonyms LIKE '.$search.'  OR  a.tfirst LIKE '.$search.'  OR  a.tdefinition LIKE '.$search.'  OR  a.tname LIKE '.$search.'  OR  a.tmail LIKE '.$search.'  OR  a.tcomment LIKE '.$search.'  OR  a.tedit LIKE '.$search.' )');
			}
		}

		// Add the list ordering clause.
		$orderCol	= $this->state->get('list.ordering');
		$orderDirn	= $this->state->get('list.direction');
        if ($orderCol && $orderDirn) {
		    $query->order($db->escape($orderCol.' '.$orderDirn));
        }
		else
		{
			$query->order('a.id');
		}

		return $query;
	}
	public function saveCsv()
        {
            $file = JFactory::getApplication()->input->files->get('file_csv', null, 'files', 'array');
            $app = JFactory::getApplication();
            jimport('joomla.filesystem.file');
            //Clean up filename to get rid of strange characters like spaces etc
            $filename = JFile::makeSafe($file['name']);
            $src = $file['tmp_name'];
            JFolder::create(JPATH_SITE ."/tmp/seoglossary/");
            $dest = JPATH_SITE ."/tmp/seoglossary/".$filename;
            $glossary = array();
            $operation=JFactory::getApplication()->input->getString('operation',0);
            if ( strtolower(JFile::getExt($filename) ) == 'csv') {
			if ( JFile::upload($src, $dest) ) {
        	$db=JFactory::getDbo();
                $sql = "SELECT id,name FROM #__seoglossaries WHERE state = '1' ORDER By id desc";
                $db->setQuery( $sql );
                $catrows=$db->loadAssocList('id','name');
                
			    require_once JPATH_COMPONENT_ADMINISTRATOR . '/assets/parsecsv.lib.php';
				JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_seoglossary/tables');
				$csv = new parseCSV();
                $csv->auto($dest);
                $q=0;
                $l=0;
                foreach ($csv->data as $key => $row)
				{
				$glossary = array();
				$type = 'glossary';
				$prefix = 'SeoglossaryTable';
				$config = array();
				$table = JTable::getInstance($type, $prefix, $config);
                 $values="";
                  $c=0;
                foreach ($row as $key2 =>$value)
				{
				if($key2=='id')
				{
					$value=(int)$value;
				}
              
                if($key2=='catid')
                {
                    if(in_array($value,$catrows))
                    {
                        $cat_data=array_search($value, $catrows);
                        $value=$cat_data;
                    }
                    else if(is_int($value))
                    {
                        $value=$value;
                    }
                    else
                    {
                        $value=array_key_first($catrows);
                    }
                }
				if($key2=='tterm')
				{
					
					$glossary['alias'] = JApplicationHelper::stringURLSafe($value);	
				}
                $values= $value;
                 $col=$csv->titles[$c];
				 
                 $glossary[$col]=$values;
				  if(($operation==0)&&($key2=='id'))
                {
                   $glossary['id']=0;
                }
                 $c++;
				}
				if($glossary['id']!=0)
				{
				$table->load($glossary['id']);
				if(!$table->id)
				{
					$glossary['id']=0;
				}
				}
				$table->bind($glossary);
				if (!$table->check()) {

            JFactory::getApplication()->enqueueMessage($table->getError(), 'error');
			}
			if (!$table->store()) {
            JFactory::getApplication()->enqueueMessage($table->getError(), 'error');
			}           
				}
				
			
        
		JFolder::delete(JPATH_SITE ."/tmp/seoglossary/");
        JFactory::getApplication()->enqueueMessage('All entries have been properly imported,congratulations');
          //Redirect to a page of your choice
       } else {
		JFactory::getApplication()->enqueueMessage('ERROR IN UPLOAD', 'error');
          //Redirect and throw an error message
       }
        } else {
			JFactory::getApplication()->enqueueMessage('File format dont seems to be correct,sorry', 'error');
          $msg = JText::_('FILE_TYPE_INVALID');
          
         //Redirect and notify user file is not right extension
        }
          $app->redirect('index.php?option=com_seoglossary&view=import');
        }
}
