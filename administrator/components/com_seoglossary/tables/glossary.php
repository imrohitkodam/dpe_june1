<?php
/**    
 * SEO Glossary Glossary table
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\Utilities\ArrayHelper;

/**
 * glossary Table class
 */
class SeoglossaryTableGlossary extends JTable
{
	protected $tagsHelper = null;
	/**
	 * Constructor
	 *
	 * @param JDatabase A database connector object
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__seoglossary', 'id', $db);
		$this->setColumnAlias('published', 'state');
		 if (version_compare(JVERSION, '4.0', 'ge'))
				{ $this->tagsHelper = new JHelperTags();
                    $this->tagsHelper->typeAlias = 'com_seoglossary.glossary';
					}else{
		JTableObserverTags::createObserver($this, array('typeAlias' => 'com_seoglossary.glossary'));
		JTableObserverContenthistory::createObserver($this, array('typeAlias' => 'com_seoglossary.glossary'));
				}
	}

	/**
	 * Overloaded bind function to pre-process the params.
	 *
	 * @param	array		Named array
	 * @return	null|string	null is operation was satisfactory, otherwise returns an error
	 * @see		JTable:bind
	 * @since	1.5
	 */
	public function bind($array, $ignore = '')
	{
		if (isset($array['params']) && is_array($array['params'])) {
			$registry = new JRegistry();
			$registry->loadArray($array['params']);
			$array['params'] = (string)$registry;
		}
		  if (!isset($array['checked_out'])||$array['checked_out']=='')
         {
            $array['checked_out'] = 0;
         }
		 if (!isset($array['tfirst']))
         {
            $array['tfirst'] = "";
         }
		if (!isset($array['tcomment']))
         {
            $array['tcomment'] ="";
         }
		 if (!isset($array['hits'])||$array['hits']=='')
         {
            $array['hits'] = 0;
         }
		 if (!isset($array['tedit']))
         {
            $array['tedit'] = "";
         }
		if (isset($array['metadata']) && is_array($array['metadata'])) {
			$registry = new JRegistry();
			$registry->loadArray($array['metadata']);
			$array['metadata'] = (string)$registry;
		}
		 if (!isset($array['icon'])||$array['icon']==null)
        {
            $array['icon']="";
        }
		 if (!isset($array['nofollow'])||$array['nofollow']==null)
        {
            $array['nofollow']="";
        }
		if (!isset($array['target'])||$array['target']==null)
        {
            $array['target']="";
        }
		if (!isset($array['metakey'])||$array['metakey']==null)
        {
            $array['metakey']="";
        }
		if (!isset($array['metadesc'])||$array['metadesc']==null)
        {
            $array['metadesc']="";
        }
		if (!isset($array['exturl'])||$array['exturl']==null)
        {
            $array['exturl']="";
        }
		if (!isset($array['extlink'])||$array['extlink']==null)
        {
            $array['extlink']="";
        }
		  if (!isset($array['created_by'])||$array['created_by']=='')
         {
            $array['created_by'] = JFactory::getUser()->id;
         }
		return parent::bind($array, $ignore);
	}

    /**
    * Overloaded check function
    */
    public function check() {

        //If there is an ordering column and this is a new row then get the next ordering value
        if (property_exists($this, 'ordering') && $this->id == 0) {
            $this->ordering = self::getNextOrder();
        }
        // Generate a valid alias
		$this->generateAlias();
		
        return parent::check();
    }
	public function store($updateNulls = false)
	{
		
		$date   = JFactory::getDate()->toSql();
		
		$userId = JFactory::getUser()->id;
		if ($this->id)
		{
			// Existing item
				if (!(int) $this->created)
			{
				$this->created = $date;
			}

			if (empty($this->created_by))
			{
				$this->created_by = $userId;
			}
		}
		else
		{
			// so we don't touch either of these if they are set.
			if (!(int) $this->created)
			{
				$this->created = $date;
			}

			if (empty($this->created_by))
			{
				$this->created_by = $userId;
			}
		}
		if (!$this->tname)
		{
			$this->tname = JFactory::getUser()->name;
		}
		if (!$this->tmail)
		{
			$this->tmail = JFactory::getUser()->email;
		}
		
		if (!$this->publish_up)
		{
			$this->publish_up = $this->_db->getNullDate();
		}

		// Set publish_down to null date if not set
		if (!$this->publish_down)
		{
			$this->publish_down = $this->_db->getNullDate();
		}
		// Verify that the alias is unique
		$table = JTable::getInstance('Glossary', 'SeoglossaryTable');

		if ($table->load(array('alias' => $this->alias)) && ($table->id != $this->id || $this->id == 0))
		{
			$this->setError(JText::_($this->tterm.' alias Must Be Unique'));

			return false;
		}
		if (version_compare(JVERSION, '4.0', 'ge'))
				{
                    $this->tagsHelper->preStoreProcess($this);
                    $result = parent::store($updateNulls);
                	return $result && $this->tagsHelper->postStoreProcess($this);	
                }
                else
                {
                        $store= parent::store($updateNulls);
    
                }
		return $store;
	}

    /**
     * Method to set the publishing state for a row or list of rows in the database
     * table.  The method respects checked out rows by other users and will attempt
     * to checkin rows that it can after adjustments are made.
     *
     * @param    mixed    An optional array of primary key values to update.  If not
     *                    set the instance property value is used.
     * @param    integer The publishing state. eg. [0 = unpublished, 1 = published]
     * @param    integer The user id of the user performing the operation.
     * @return    boolean    True on success.
     * @since    1.0.4
     */
    public function publish($pks = null, $state = 1, $userId = 0)
    {
        // Initialise variables.
        $k = $this->_tbl_key;

        // Sanitize input.
        ArrayHelper::toInteger($pks);
        $userId = (int) $userId;
        $state  = (int) $state;

        // If there are no primary keys set check to see if the instance key is set.
        if (empty($pks))
        {
            if ($this->$k) {
                $pks = array($this->$k);
            }
            // Nothing to set publishing state on, return false.
            else {
                $this->setError(JText::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'));
                return false;
            }
        }

        // Build the WHERE clause for the primary keys.
        $where = $k.'='.implode(' OR '.$k.'=', $pks);

        // Determine if there is checkin support for the table.
        if (!property_exists($this, 'checked_out') || !property_exists($this, 'checked_out_time')) {
            $checkin = '';
        }
        else {
            $checkin = ' AND (checked_out = 0 OR checked_out = '.(int) $userId.')';
        }

        // Update the publishing state for rows with the given primary keys.
        $this->_db->setQuery(
            'UPDATE '.$this->_tbl.'' .
            ' SET state = '.(int) $state .
            ' WHERE ('.$where.')' .
            $checkin
        );
       try{
				 $this->_db->execute();
				}
						catch (Exception $e)
						{
						    $this->setError($this->_db->getErrorMsg());
						    return false;
        						
						}

        // If checkin is supported and all rows were adjusted, check them in.
        if ($checkin && (count($pks) == $this->_db->getAffectedRows()))
        {
            // Checkin the rows.
            foreach($pks as $pk)
            {
                $this->checkin($pk);
            }
        }

        // If the JTable instance value is in the list of primary keys that were set, set the instance.
        if (in_array($this->$k, $pks)) {
            $this->state = $state;
        }

        $this->setError('');
        return true;
    }
    /**
	 * Generate a valid alias from title / date.
	 * Remains public to be able to check for duplicated alias before saving
	 *
	 * @return  string
	 */
	public function generateAlias()
	{
		if (empty($this->alias))
		{
			$this->alias = $this->tterm;
		}

		$this->alias = JApplicationHelper::stringURLSafe($this->alias);

		if (trim(str_replace('-', '', $this->alias)) == '')
		{
			$this->alias = $this->id." ".JFactory::getDate()->format("Y-m-d-H-i-s");
		}

		return $this->alias;
	}

 /**
	 * Override parent delete method to delete tags information.
	 *
	 * @param   integer  $pk  Primary key to delete.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   3.1
	 * @throws  UnexpectedValueException
	 */
    public function delete($pk = null)
	{
		$result = parent::delete($pk);
        if (version_compare(JVERSION, '4.0', 'ge'))
				{
                    $this->tagsHelper->deleteTagData($this, $pk);
                }
		return $result;
	}


}
