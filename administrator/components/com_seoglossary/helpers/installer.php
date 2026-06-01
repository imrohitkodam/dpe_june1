<?php
/** 
 * Droptables
 * 
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and to customize.
 * Otherwise, please feel free to contact us at contact@joomunited.com *
 * @package Droptables
 * @copyright Copyright (C) 2014 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @copyright Copyright (C) 2014 Damien Barrère (http://www.crac-design.com). All rights reserved.
 * @license GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */


// no direct access
defined('_JEXEC') or die;

class InstallerHelper{
    
    /**
     * Install an extension from a folder
     * @param string $folder
     * @param boolean $enable
     * @return boolean
     */
    static public function install($folder,$enable=false){
        // Get an installer instance
        $installer = new JInstaller();
        // Install the package
        if (!$installer->install($folder)) {
                // There was an error installing the package
                $result = false;
        } else {
                // Package installed sucessfully
                $result = true;
                //enable plugin or module
                if($enable){
                    $group = $installer->manifest->attributes()->group;
                    $name = $installer->manifest->name;
                    $dbo = JFactory::getDbo();
                    $query = 'UPDATE #__extensions SET enabled=1 WHERE name='.$dbo->quote($name).' AND folder='.$dbo->quote($group);
                    $dbo->setQuery($query);
                    $dbo->execute();
                }
                //Unset the last plugin message
                $installer->set('message','');
        }
        return $result;
    }
    
    /**
     * Enable an extension
     * @param type $name
     * @param type $type
     * @return type
     */
    static public function enableExtension($element, $type='',$folder=''){
        $dbo = JFactory::getDbo();
        $query = 'UPDATE #__extensions SET enabled=1 WHERE element='.$dbo->quote($element);
        if($type!=''){
            $query .= ' AND type='.$dbo->quote($type);
        }
        if($folder!=''){
            $query .= ' AND folder='.$dbo->quote($folder);
        }
        $dbo->setQuery($query);
        return $dbo->execute();
    }
    
    static public function uninstall($folder){
//        $installer = new JInstaller();
//        
//        // Install the package
//        if ($installer-> uninstall()) {
//            return true;
//        }
//        return false;
    }

}
?>