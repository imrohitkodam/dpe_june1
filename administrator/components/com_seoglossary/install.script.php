<?php
/**
 * PHP install file for SEO Glossary.
 * Installes the plugins and do some upgrade stuff.
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	com_seoglossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

// Check to ensure this file is included in Joomla!
defined( '_JEXEC' ) or die( 'Restricted access' );
 
/**
 * Script file of Seo Glossary component
 */
class com_seoglossaryInstallerScript
{
        /**
         * method to install the component
         *
         * @return void
         */
        function install($parent) 
        {
			// $parent is the class calling this method
        }
 
        /**
         * method to uninstall the component
         *
         * @return void
         */
        function uninstall($parent) 
        {
			// $parent is the class calling this method
			 $db = JFactory::getDBO();
        $status = new stdClass;
        $status->modules = array();
        $status->plugins = array();
        $manifest = $parent->getManifest();
        $extensions = $manifest->extensions;
        
        foreach($extensions->children() as $plugin)
        {
            $group = (string)$plugin->attributes()->group;
	    $element=(string)$plugin->attributes()->element;
	    $type=(string)$plugin->attributes()->type;
            $query = "SELECT extension_id FROM #__extensions WHERE element = ".$db->Quote($element)." AND folder=".$db->Quote($group);
            $db->setQuery($query);
            $extensions = $db->loadColumn();
	    if (count($extensions))
            {
                foreach ($extensions as $id)
                {
                    $installer = new JInstaller();
                    $result = $installer->uninstall($type, $id);
                }
            
            }
            
        }
        }
 
        /**
         * method to update the component
         *
         * @return void
         */
        function update($parent) 
        {
			// $parent is the class calling this method
			
			$query = "SHOW COLUMNS FROM #__seoglossaries LIKE 'alphabet'";
			$db = JFactory::getDbo();
			$db->setQuery($query);
			$db->execute();
			$exists = ($db->getNumRows()) ? TRUE : FALSE;
			if (!$exists)
			{
				$query = "ALTER TABLE #__seoglossaries ADD COLUMN alphabet VARCHAR(255) NOT NULL DEFAULT 'A, B, C, D, E, F, G, H, I, J, K, L, M, O, P, Q, R, S, T, U, V, W, X, Y, Z';";
				$db->setQuery($query);
				$db->execute();
			}
			$query = "SHOW COLUMNS FROM #__seoglossary LIKE 'tmore'";
			$db->setQuery($query);
			$db->execute();
			$exists = ($db->getNumRows()) ? TRUE : FALSE;
			if (!$exists)
			{
				$query = "ALTER TABLE #__seoglossary ADD COLUMN tmore TEXT(65535)  NOT NULL;";
				$db->setQuery($query);
				$db->execute();
			}

            $query = "SHOW COLUMNS FROM #__seoglossary LIKE 'tsynonyms'";
            $db->setQuery($query);
            $db->execute();
            $exists = ($db->getNumRows()) ? TRUE : FALSE;
            if (!$exists)
            {
                $query = "ALTER TABLE #__seoglossary ADD COLUMN tsynonyms VARCHAR(255) NOT NULL;";
                $db->setQuery($query);
                $db->execute();
            }
			

        }
 
        /**
         * method to run before an install/update/uninstall method
         *
         * @return void
         */
        function preflight($type, $parent) 
        {
			// $parent is the class calling this method
			// $type is the type of change (install, update or discover_install)
        }
 
        /**
         * method to run after an install/update/uninstall method
         *
         * @return void
         */
        function postflight($type, $parent) 
        {
			// $parent is the class calling this method
			// $type is the type of change (install, update or discover_install)
			$manifest = $parent->getManifest();
          $extensions = $manifest->extensions;
           JLoader::register('InstallerHelper', JPATH_ADMINISTRATOR . '/components/com_seoglossary/helpers/installer.php');
		    	if ($type != 'uninstall') {
					$imgsrc = JURI::root() .'/components/com_seoglossary/assets/images/tick.png';
             $imgsno = JURI::root() .'/components/com_seoglossary/assets/images/exclamation.png';
		    echo ' <p style="font-family: Open Sans,Helvetica,Arial,sans-serif;">
                <h2><strong>Seo Glossary -Get the most advanced glossary to define terms, generate nice and automatic tooltips, showing definition in your Joomla content. </strong></h2><br /></p>
                
                <p style="font-family: Open Sans,Helvetica,Arial,sans-serif;"><a style="text-decoration: none; background-color: #2089c0; color: #fff; padding: 10px 20px 10px 20px; border-radius 2px;" href="index.php?option=com_seoglossary">LOAD THE DASHBOARD</a> </p>
                
                <p style="font-family: Open Sans,Helvetica,Arial,sans-serif;">
                    <br /><br /> <ul>';
				
           foreach($extensions->children() as $extension){
                        $folder = $extension->attributes()->folder;
                        $enable = $extension->attributes()->enable;
                        if(InstallerHelper::install(JPATH_ADMINISTRATOR.'/components/com_seoglossary/extensions/'.$folder,$enable)){
    //                        JFile::delete(JPATH_ADMINISTRATOR.'/components/com_sponsorshipreward/extensions/'.$folder);
                            echo '<li style="background-image: url(' .  $imgsrc. '); background-repeat: no-repeat; background-position: left center; padding-left: 24px;list-style-type: none; line-height: 25px; font-family: Open Sans,Helvetica,Arial,sans-serif;">'.JText::sprintf($folder).' : '.JText::sprintf('OK','').'</li>';
                        }else{
                            echo '<li style="background-image: url(' .  $imgsno. '); background-repeat: no-repeat; background-position: left center; padding-left: 24px;list-style-type: none; line-height: 25px; font-family: Open Sans,Helvetica,Arial,sans-serif;">'.JText::sprintf($folder).' : '.JText::sprintf('Not OK','').'</li>';
                        }
						}
					}
				    echo"</ul>";
		$db = JFactory::getDBO();
		$fields = $db->getTableColumns('#__seoglossaries');
		$query = "ALTER TABLE #__seoglossaries  CHANGE description description TEXT NOT NULL";
		    $db->setQuery($query);
		    $db->execute();
	if (!array_key_exists('alias', $fields)) {
		    $query = "ALTER TABLE #__seoglossaries ADD alias varchar(255) NOT NULL  AFTER name";
		    $db->setQuery($query);
		    $db->execute();
	}
			 if (!array_key_exists('disable_menu', $fields)) {
				 $query = "ALTER TABLE #__seoglossaries ADD disable_menu varchar(255) NOT NULL  AFTER alphabet";
				$db->setQuery($query);
				$db->execute();
			 }
			 if (!array_key_exists('disable_component', $fields)) {
				 $query = "ALTER TABLE #__seoglossaries ADD disable_component varchar(255) NOT NULL  AFTER alphabet";
				$db->setQuery($query);
				$db->execute();
			 }
			 if (!array_key_exists('disable_context', $fields)) {
				 $query = "ALTER TABLE #__seoglossaries ADD disable_context varchar(255) NOT NULL  AFTER alphabet";
				$db->setQuery($query);
				$db->execute();
			 }
			 if (!array_key_exists('disable_category', $fields)) {
				 $query = "ALTER TABLE #__seoglossaries ADD disable_category varchar(255) NOT NULL  AFTER alphabet";
				$db->setQuery($query);
				$db->execute();
			 }
	if (!array_key_exists('icon', $fields)) {
            $query = "ALTER TABLE  #__seoglossaries ADD icon VARCHAR(255)  NOT NULL AFTER alphabet";
            $db->setQuery($query);
            $db->execute();
        }
		$fields2 = $db->getTableColumns('#__seoglossary');
		if (!array_key_exists('alias', $fields2)) {
		    $query = "ALTER TABLE #__seoglossary ADD alias varchar(255) NOT NULL  AFTER tterm";
		    $db->setQuery($query);
		    $db->execute();
		  }
		if (!array_key_exists('extlink', $fields2)) {
            $query = "ALTER TABLE #__seoglossary ADD extlink char(7) NOT NULL  AFTER catid";
            $db->setQuery($query);
            $db->execute();
        }
        if (!array_key_exists('exturl', $fields2)) {
            $query = "ALTER TABLE #__seoglossary ADD exturl varchar(255) NOT NULL  AFTER catid";
            $db->setQuery($query);
            $db->execute();
        }
		if (!array_key_exists('metadesc', $fields2)) {
            $query = "ALTER TABLE #__seoglossary ADD metadesc text NOT NULL  AFTER tcomment";
            $db->setQuery($query);
            $db->execute();
        }
        if (!array_key_exists('metakey', $fields2)) {
            $query = "ALTER TABLE  #__seoglossary ADD metakey text NOT NULL  AFTER tcomment";
            $db->setQuery($query);
            $db->execute();
        }
		if (!array_key_exists('target', $fields2)) {
            $query = "ALTER TABLE  #__seoglossary ADD target char(7) NOT NULL AFTER tcomment";
            $db->setQuery($query);
            $db->execute();
        }
		if (!array_key_exists('nofollow', $fields2)) {
            $query = "ALTER TABLE  #__seoglossary ADD nofollow char(7) NOT NULL AFTER tcomment";
            $db->setQuery($query);
            $db->execute();
        }
	
		if (!array_key_exists('created_by', $fields2)) {
            $query = "ALTER TABLE  #__seoglossary ADD created_by INT(11)  NOT NULL AFTER tcomment";
            $db->setQuery($query);
            $db->execute();
        }
		if (!array_key_exists('created', $fields2)) {
            $query = "ALTER TABLE  #__seoglossary ADD created DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER created_by";
            $db->setQuery($query);
            $db->execute();
        }
		if (!array_key_exists('icon', $fields2)) {
            $query = "ALTER TABLE  #__seoglossary ADD icon VARCHAR(255)  NOT NULL AFTER tcomment";
            $db->setQuery($query);
            $db->execute();
        }
		if (!array_key_exists('publish_up', $fields2)) {
            $query = "ALTER TABLE  #__seoglossary ADD publish_up DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER created";
            $db->setQuery($query);
            $db->execute();
        }
		if (!array_key_exists('publish_down', $fields2)) {
            $query = "ALTER TABLE  #__seoglossary ADD publish_down DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER publish_up";
            $db->setQuery($query);
            $db->execute();
        }
		if (!array_key_exists('hits', $fields2)) {
            $query = "ALTER TABLE  #__seoglossary ADD hits INT(11)  NOT NULL AFTER publish_down";
            $db->setQuery($query);
            $db->execute();
        }
        $query = "ALTER TABLE  #__seoglossary   CHANGE `checked_out` `checked_out` INT NOT NULL DEFAULT '0'";
        $db->setQuery($query);
        $db->execute();
        $query = "ALTER TABLE  #__seoglossaries  CHANGE `checked_out` `checked_out` INT NOT NULL DEFAULT '0'";
        $db->setQuery($query);
        $db->execute();
			$query = "select * from #__content_types where type_title =".$db->Quote('Seoglossary');
            $db->setQuery($query);
            $db->execute();
            $num_rows = $db->getNumRows();
            
            if($num_rows==0){
            $content_type = new stdClass();
            $content_type->type_title = 'Seoglossary';
            $content_type->type_alias='com_seoglossary.glossary';
            $content_type->table='{"special":{"dbtable":"#__seoglossary","key":"id","type":"Glossary","prefix":"SeoglossaryTable"}}';
            $content_type->rules='';
            $content_type->field_mappings='{"common":{"core_content_item_id":"id","core_title":"tterm","core_state":"state","core_alias":"alias","core_created_time":"created","core_body":"tdefinition","core_access":"access","core_metadata":"metadesc", "core_language":"language","core_catid":"catid","core_hits":"hits","core_publish_up":"publish_up","core_publish_down":"publish_down"}}';
            $content_type->router='';
            $content_type->content_history_options='{"formFile":"administrator/components/com_seoglossary/models/forms/glossary.xml", "hideFields":["checked_out","checked_out_time","tmore"], "ignoreChanges":["checked_out", "checked_out_time"], "convertToInt":["publish_up", "publish_down"], "displayLookup":[{"sourceColumn":"catid","targetTable":"#__seoglossaries","targetColumn":"id","displayColumn":"name"},{"sourceColumn":"access","targetTable":"#__viewlevels","targetColumn":"id","displayColumn":"title"},{"sourceColumn":"created_by","targetTable":"#__users","targetColumn":"id","displayColumn":"name"}]}';
            $result = JFactory::getDbo()->insertObject('#__content_types', $content_type);
    
            }
        }
}