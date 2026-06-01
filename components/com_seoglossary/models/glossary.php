<?php
/**
 * SEO Glossary Glossary Model
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

// No direct access.
defined( '_JEXEC' ) or die ;

jimport( 'joomla.application.component.modeladmin' );

/**
 * Seoglossary model.
 */
class SeoglossaryModelGlossary extends JModelAdmin
{
	/**
	 * @var		string	The prefix to use with controller messages.
	 * @since	1.6
	 */
	protected $text_prefix = 'COM_SEOGLOSSARY';

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param	type	The table type to instantiate
	 * @param	string	A prefix for the table class name. Optional.
	 * @param	array	Configuration array for model. Optional.
	 * @return	JTable	A database object
	 * @since	1.6
	 */
	public function getTable( $type = 'Glossary', $prefix = 'SeoglossaryTable', $config = array() )
	{
		return JTable::getInstance( $type, $prefix, $config );
	}

	/**
	 * Method to get the record form.
	 *
	 * @param	array	$data		An optional array of data for the form to interogate.
	 * @param	boolean	$loadData	True if the form is to load its own data (default case), false if not.
	 * @return	JForm	A JForm object on success, false on failure
	 * @since	1.6
	 */
	public function getForm( $data = array(), $loadData = true )
	{
		// Initialise variables.
		$app = JFactory::getApplication( );

		// Get the form.
		jimport( 'joomla.form.form' );
		JForm::addFieldPath( 'JPATH_ADMINISTRATOR/components/com_seoglossary/models/fields' );
		$form = $this->loadForm( 'com_seoglossary.glossary', 'glossary', array(
			'control' => 'jform',
			'load_data' => $loadData
		) );
		if ( empty( $form ) )
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	The data for the form.
	 * @since	1.6
	 */
	protected function loadFormData( )
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication( )->getUserState( 'com_seoglossary.edit.glossary.data', array( ) );

		if ( empty( $data ) )
		{
			$data = $this->getItem( );
		}

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param	integer	The id of the primary key.
	 *
	 * @return	mixed	Object on success, false on failure.
	 * @since	1.6
	 */
	public function getItem( $pk = null )
	{
		if ( $item = parent::getItem( $pk ) )
		{
			if (!empty($item->id))
		{
			$item->tags = new JHelperTags;
			$item->tags->getTagIds($item->id, 'com_seoglossary.glossary');
		}
			//Do any procesing on fields here if needed

		}

		return $item;
	}

	/**
	 * Prepare and sanitise the table prior to saving.
	 *
	 * @since	1.6
	 */
	protected function prepareTable( $table )
	{
		jimport( 'joomla.filter.output' );

		if ( empty( $table->id ) )
		{

			// Set ordering to the last item if not set
			if ( @$table->ordering === '' )
			{
				$db = JFactory::getDbo( );
				$db->setQuery( 'SELECT MAX(ordering) FROM #__seogallery_glossary' );
				$max = $db->loadResult( );
				$table->ordering = $max + 1;
			}

		}
	}
	
	public function validate  ($form, $data, $group = null) {
		$user = JFactory::getUser();
		if (!$user->guest) {
			$data['tname'] = $user->name;
		}
		if (!$user->guest) {
			$data['tmail'] = $user->email;
		}
		
		return parent::validate($form, $data, $group);
	}
	
	/**
	 * Increment the hit counter for the glossary
	 *
	 * @param   integer  $pk  Optional primary key of the article to increment.
	 *
	 * @return  boolean  True if successful; false otherwise and internal error set.
	 */
	public function hit($pk = 0)
	{
			if($pk!=0)
			{
			$table = $this->getTable();
			$table->load($pk);
			$table->hit($pk);
			}
			return true;
	}

	public function save( $data )
	{
		$params = JComponentHelper::getParams( 'com_seoglossary' );
		$config = JFactory::getConfig();
		if ($params->get('auto_publish')==0) {
			$data['state'] = 0;
		} else {
			$data['state'] = 1;
		}
		
		$db = JFactory::getDbo();
		$catid = $db->escape((int)$data['catid']);
		$sql = "SELECT state FROM #__seoglossaries WHERE id = '{$catid}';";
		$db->setQuery($sql);
		$state = $db->loadResult();
		
		if ($state != 1) {
			$this->setError( 'Fatal error. Try again.' );
			return false;
		}

		$table = $this->getTable( );
		if ( !$table->load( $data['id'] ) )
		{
			return false;
		}
		if ( !$table->save( $data ) )
		{
			
			return false;
		}
		
		if ( $params->get( 'notify_mail', 0 ) == 1 )
		{
			$email = $params->get( 'email',$config->get( 'mailfrom') );
			$fromname = $config->get( 'fromname' );
			$mailfrom = $config->get( 'mailfrom' );
			$sitename = $config->get( 'sitename' );
			$subject = JText::sprintf( 'COM_SEOGLOSSARY_NEWENTRY' );
			$body = JText::sprintf( 'An entry has been created in ' . $config->get( 'sitename' ) . '  you can view the entry by following link ' . JURI::base( ) . 'administrator/index.php?option=com_seoglossary&task=glossary.edit&id=' . $table->id );
			$mailer = JFactory::getMailer();
			$return = $mailer->sendMail( $mailfrom, $fromname, $email, $subject, $body );

		}

		if ( $params->get( 'thank_user', 0 ) == 1 )
		{
			$email = $table->tmail;
			if($email)
			{
			$mailfrom = $params->get( 'email_from' );

			if ( empty( $mailfrom ) )
			{
				$mailfrom = $config->get( 'mailfrom' );
			}
			$fromname = $config->get( 'fromname' );
			$sitename = $config->get( 'sitename' );
			$subject = JText::sprintf( 'COM_SEOGLOSSARY_THANK' );
			$body = JText::sprintf( $params->get( 'message' ,'Thank you for your entry in our glossary') );
			$mailer = JFactory::getMailer();
			$return = $mailer->sendMail( $mailfrom, $fromname, $email, $subject, $body );
			}
		}
		return true;

	}

}
