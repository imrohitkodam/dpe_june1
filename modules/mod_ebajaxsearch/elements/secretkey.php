<?php 
/**
 * @package Module EB Ajax Search for Joomla!
 * @version 1.52: mod_ebajaxsearch.php Dec 2023
 * @author url: https://www/extnbakers.com
 * @copyright Copyright (C) 2022 extnbakers.com. All rights reserved.
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
**/
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;

class JFormFieldSecretkey extends JFormField {
	protected $type = 'Secretkey';

	public function getInput() {
		
		/*Module  Name*/
		$module_name = $this->form->getValue('module');
		$info = simplexml_load_file('../modules/'.$module_name.'/'.$module_name.'.xml');
		$extn_name = (string)$info->updateservers->server[0]->attributes()->name;

		/*Domain Name*/
		$site_url =  JUri::root();
		$siteurl_components = parse_url($site_url); 
		$site_url_domain = isset($siteurl_components['host'])?$siteurl_components['host']:'';

		/*Purchase Key*/
		$eb_ajaxsearch_purchase_key = $this->form->getValue('eb_ajaxsearch_purchase_key', 'params');
		if($eb_ajaxsearch_purchase_key == ''){
			$eb_ajaxsearch_purchase_key_final = '';
		} else {
			$eb_ajaxsearch_purchase_key_final = "purchasekey=".$eb_ajaxsearch_purchase_key."&domain=".$site_url_domain;
		}
	   	$db = JFactory::getDbo();
	    $query = $db->getQuery(true)
	            ->select('*')
	            ->from($db->qn('#__update_sites'))
	            ->where($db->qn('name').' = "'.$extn_name.'"');
	    $query->setLimit(1);
	    $db->setQuery($query);

	    $update_site = $db->loadResult();

	    if(!empty($update_site)){
		    $upd= "UPDATE `#__update_sites` SET `extra_query`= '".$eb_ajaxsearch_purchase_key_final."' WHERE `update_site_id` = ".$update_site;
		    $db->setQuery($upd);
		    $db->execute();
		}	

	    $attribs = "style='border: 1px solid #ccc; padding: 5px; width: 40%; border-radius: 4px;'";
		return '<input type="secretkey" name="'.$this->name.'" id="'.$this->id.'"' .
                ' value="'.$this->value.'" '.$attribs.'>';    
	}

}