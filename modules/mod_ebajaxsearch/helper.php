<?php 
/**
 * @package Module EB Ajax Search for Joomla!
 * @version 1.52: mod_ebajaxsearch.php Dec 2023
 * @author url: https://www/extnbakers.com
 * @copyright Copyright (C) 2022 extnbakers.com. All rights reserved.
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
**/
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;
JHtml::_('jquery.framework');
jimport('joomla.application.component.helper');
class modEbajaxsearchHelper
{
    public static function getAjax()
    {
        if(JVERSION < 4){
            include_once JPATH_ROOT . '/components/com_content/helpers/route.php';
        }
        include('Pagination.php');
        $languagevar = JFactory::getLanguage()->getTag();
        $lower_language =  strtolower($languagevar);
        $correct_language = str_replace("-","_",$lower_language);
        $input = JFactory::getApplication()->input;
        $ajax_data = $input->get('data', array(), 'ARRAY');
        $searchword = $ajax_data['keyword'];
        $module_idd = isset($ajax_data['module_idd'])?$ajax_data['module_idd']:'';
        $catids = $ajax_data['catids'];
        $search_in_article = isset($ajax_data['search_in_article'])?$ajax_data['search_in_article']:'';
        $vmcatid = isset($ajax_data['vmcatid'])?$ajax_data['vmcatid']:'';
        $search_in_vm = isset($ajax_data['search_in_vm'])?$ajax_data['search_in_vm']:'';
        $k2catid = isset($ajax_data['k2catid'])?$ajax_data['k2catid']:'';
        $search_in_k2 = isset($ajax_data['search_in_k2'])?$ajax_data['search_in_k2']:'';
        $hikashopcatid = isset($ajax_data['hikashopcatid'])?$ajax_data['hikashopcatid']:'';
        $hikashop_shop_price = isset($ajax_data['hikashop_shop_price'])?$ajax_data['hikashop_shop_price']:'';
        $search_in_vm_show_price = isset($ajax_data['search_in_vm_show_price'])?$ajax_data['search_in_vm_show_price']:'';
        $search_in_hikashop = isset($ajax_data['search_in_hikashop'])?$ajax_data['search_in_hikashop']:'';
        $spcatid = isset($ajax_data['spcatid'])?$ajax_data['spcatid']:'';
        $search_in_sppage = isset($ajax_data['search_in_sppage'])?$ajax_data['search_in_sppage']:'';
        $pccatid = isset($ajax_data['pccatid'])?$ajax_data['pccatid']:'';
        $search_in_pc = isset($ajax_data['search_in_pc'])?$ajax_data['search_in_pc']:'';				
		
		$djcatid = isset($ajax_data['djcatid'])?$ajax_data['djcatid']:'';
		$search_in_dj = isset($ajax_data['search_in_dj'])?$ajax_data['search_in_dj']:'';
		
		$docmancatids = isset($ajax_data['docmancatids'])?$ajax_data['docmancatids']:'';
		$search_in_docman = isset($ajax_data['search_in_docman'])?$ajax_data['search_in_docman']:'';
		$docmanmenu_id = isset($ajax_data['docmanmenu'])?$ajax_data['docmanmenu']:'';
		
		$search_article_fields = isset($ajax_data['search_article_fields'])?$ajax_data['search_article_fields']:'';
        $search_k2_fields = isset($ajax_data['search_k2_fields'])?$ajax_data['search_k2_fields']:'';
        $search_hs_fields = isset($ajax_data['search_hs_fields'])?$ajax_data['search_hs_fields']:'';
        $search_sp_fields = isset($ajax_data['search_sp_fields'])?$ajax_data['search_sp_fields']:'';
        $search_vm_fields = isset($ajax_data['search_vm_fields'])?$ajax_data['search_vm_fields']:'';
        $search_pc_fields = isset($ajax_data['search_pc_fields'])?$ajax_data['search_pc_fields']:'';		        
		$search_dj_fields = isset($ajax_data['search_dj_fields'])?$ajax_data['search_dj_fields']:'';
		
		$search_docman_fields = isset($ajax_data['search_docman_fields'])?$ajax_data['search_docman_fields']:'';
		
        $result_limit = isset($ajax_data['result_limit'])?$ajax_data['result_limit']:'';
        $by_ordering = $ajax_data['order'];
        $show_title = $ajax_data['title'];
        $show_category = $ajax_data['show_category'];
        $show_description = $ajax_data['description'];
        $description_limit = $ajax_data['description_limit'];
        $show_image = $ajax_data['image'];
        if(JVERSION < 4 && $correct_language == 'el_gr'){
            JLoader::register('ContentHelperRoute', JPATH_SITE . '/components/com_content/helpers/route.php');
            JLoader::register('SearchHelper', JPATH_ADMINISTRATOR . '/components/com_search/helpers/search.php');
        }
        $field_array = array($search_in_article, $search_in_vm, $search_in_k2, $search_in_hikashop, $search_in_sppage, $search_in_pc, $search_in_dj, $search_in_docman);
        $counts_number = array_count_values($field_array);
        $counts_number = $counts_number['1'];
		
        if($counts_number === 1){
			
				$start = !empty($ajax_data['page'])?$ajax_data['page']:0;
				$perpage_limit = !empty($ajax_data['perpage_limit'])?$ajax_data['perpage_limit']:5;
				$redirect_search_url = !empty($ajax_data['redirect_search_url'])?$ajax_data['redirect_search_url']:0;
				// $rowCount = count($unique_array);
				$db = JFactory::getDbo(); 
				$results1=array();
				$results2 = array();
				$results3 = array();
				$results4 = array();
				$results5 = array();
				$results6 = array();			            
				$results7 = array();
				
				if($search_in_article==1){   
					$db->getQuery(true);
					$query = "SHOW CREATE TABLE #__content";
					$db->setQuery($query); 
					try {  
						$result = $db->loadResult();
						$cat_query = '';
						if(in_array("sf_category", $search_article_fields)){
							$cat_query = 'AND ' . $db->quoteName('catid') . ' IN('.$catids.')';
						}
						$article_search_query = [];
						$field_query = '';
						if(in_array("sf_customfield", $search_article_fields)){
							$field_query = ' OR ( fi.context = "com_content.article" AND fi.state = 1)';
							$article_search_query[] = 'fv.value LIKE "%' . $searchword . '%"';
						}
						if(in_array("sf_title", $search_article_fields)){
							$article_search_query[] = 'ca.title LIKE "%' . $searchword . '%"';
						}           
						if(in_array("sf_description", $search_article_fields)){
							$article_search_query[] = 'ca.introtext LIKE "%' . $searchword . '%" OR ca.fulltext LIKE "%' . $searchword . '%"';
						}
						$tag_query_p = '';
						if(in_array("sf_tags", $search_article_fields)){
							$tag_query_p = ' AND ti.published = 1 AND tci.type_alias = "com_content.article"';
							$article_search_query[] = 'ti.title LIKE "%' . $searchword . '%"';
						}
						$final_article_search_query = '';
						$final_article_search_query = join(" OR ",$article_search_query);
						if($by_ordering == 'newest'){
						   $order_by = 'ca.created DESC';
						} else if($by_ordering == 'oldest'){
						   $order_by = 'ca.created ASC';
						} else if($by_ordering == 'popular'){
						   $order_by = 'ca.hits DESC';
						} else if($by_ordering == 'alpha'){
						   $order_by = 'ca.title ASC';
						} else {
						   $order_by = 'ca.created DESC';
						}
						$query1 = $db->getQuery(true);
						$query1             
						->select(array('ca.id', 'ca.title', 'ca.catid', 'ca.images', 'ca.introtext', 'ca.fulltext', 'ca.ordering', 'ca.hits', 'ca.created'))
						->from($db->quoteName('#__content').'AS ca')
						->leftJoin($db->quoteName('#__fields_values').'AS fv ON ca.id = fv.item_id')
						->join('LEFT', $db->quoteName('#__fields').'AS fi ON fi.id = fv.field_id'.$field_query)
						->leftJoin($db->quoteName('#__contentitem_tag_map').'AS tci ON tci.content_item_id  = ca.id')
						->join('LEFT', $db->quoteName('#__tags').'AS ti ON ti.id = tci.tag_id'.$tag_query_p)       
						->where( '( '.$final_article_search_query.' ) '.$cat_query. ' AND (ca.language="'.$languagevar.'" OR ca.language="*") AND (ca.publish_down IS NULL OR ca.publish_down >= CURRENT_TIMESTAMP) AND ca.state = 1 ')
						->group('ca.id')
						->order($order_by);               
						$db->setQuery($query1,0,$result_limit);
						$db->execute();
						$num_rows = $db->getNumRows();
						$results1 = $db->loadObjectList();                                
						if(JVERSION < 4 && $correct_language == 'el_gr'){
						$results_101 = array();
						if(count($results1)){
							$new_row_101 = array();
							foreach ($results1 as $article_101){
								if(in_array("sf_customfield", $search_article_fields)){
									$db = JFactory::getDbo(); 
									$query = $db->getQuery(true);
									$query->select('fv.value')
										->from('#__fields_values as fv')
										->join('left', '#__fields as f on fv.field_id = f.id')
										->where('f.context = ' . $db->quote('com_content.article'))
										->where('fv.item_id = ' . $db->quote((int) $article_101->id));
									$db->setQuery($query);
									$article_101->jcfields = implode(',', $db->loadColumn());
								}
								if(in_array("sf_tags", $search_article_fields)){
									$db = JFactory::getDbo(); 
									$query = $db->getQuery(true);
									$query->select('t.title')
										->from('#__contentitem_tag_map as tv')
										->join('left', '#__tags as t on tv.tag_id = t.id')
										->where('tv.type_alias = ' . $db->quote('com_content.article'))
										->where('t.published = 1')
										->where('tv.content_item_id = ' . $db->quote((int) $article_101->id));
									$db->setQuery($query);
									$article_101->tags = implode(',', $db->loadColumn());
								}
								if (SearchHelper::checkNoHtml($article_101, $searchword, array('title', 'introtext', 'fulltext', 'jcfields','tags'))){
									$new_row_101[] = $article_101;
								}
							}
							$results_101 = array_merge($results_101, (array) $new_row_101);
						}
						} else {
							$results_101 = $results1;
						}
						if(!empty($results_101)){
							foreach($results_101 as $key => &$val){
								$val->ajaxsearchtype = 'joomla_article';
							}
						}
						$rowCount = count($results_101);
						$unique_array = json_decode( json_encode($results_101), true);
						$unique_array = array_slice( $unique_array, $start, $perpage_limit );
					} 
					catch (RuntimeException $e){
					}
				} else if($search_in_k2==1){ 
					$db->getQuery(true);
					$query = "SHOW CREATE TABLE #__k2_categories";
					$db->setQuery($query); 
					try                
					{  
						// If it fails, it will throw a RuntimeException
						$result = $db->loadResult(); 
						$db1 = JFactory::getDbo();
						$k2cat_query = '';
						if($k2catid != ''){
							$k2cat_query = 'AND ' . $db1->quoteName('catid') . ' IN('.$k2catid.')';
						}
						$k2_search_query = [];
						if(in_array("k2_title", $search_k2_fields)){
							$k2_search_query[] = 'k2.title LIKE '. $db1->quote('%' . $searchword . '%');
						}           
						if(in_array("k2_description", $search_k2_fields)){
							$k2_search_query[] = 'k2.introtext LIKE '. $db1->quote('%' . $searchword . '%'). ' OR k2.fulltext LIKE '. $db1->quote('%' . $searchword . '%');
						}
						$tag_query_p1 = '';
						if(in_array("k2_tags", $search_k2_fields)){
							$tag_query_p1 = ' AND k2t.published = 1';
							$k2_search_query[] = 'k2t.name LIKE "%' . $searchword . '%"';
						}
						$final_k2_search_query = '';
						$final_k2_search_query = join(" OR ",$k2_search_query);
						if($by_ordering == 'newest'){
						   $order_by = 'k2.created DESC';
						} else if($by_ordering == 'oldest'){
						   $order_by = 'k2.created ASC';
						} else if($by_ordering == 'popular'){
						   $order_by = 'k2.hits DESC';
						} else if($by_ordering == 'alpha'){
						   $order_by = 'k2.title ASC';
						} else {
						   $order_by = 'k2.created DESC';
						}
						$query2 = $db1->getQuery(true);
						$query2
						->select('k2.id , k2.title , k2.alias , k2.catid , k2.introtext , k2.fulltext , k2.hits ,k2.title as type, k2.created')
						->from($db1->quoteName('#__k2_items').'AS k2')
						->leftJoin($db1->quoteName('#__k2_tags_xref').'AS k2tx ON k2tx.itemID  = k2.id')
						->join('LEFT', $db1->quoteName('#__k2_tags').'AS k2t ON k2t.id = k2tx.tagID'.$tag_query_p1)
						->where('('.$final_k2_search_query.' ) '.$k2cat_query. ' AND ' . $db1->quoteName('k2.published') . ' = 1')
						->group($db->quoteName('k2.id'))
						->order($order_by);
						$db1->setQuery($query2,0,$result_limit);            
						$db1->execute();
						$num_rows = $db1->getNumRows();
						$results2 = $db1->loadObjectList();
						if(JVERSION < 4 && $correct_language == 'el_gr'){
						$results_201 = array();
						if(count($results2)){
							$new_row_201 = array();
							foreach ($results2 as $article_201){
								if(in_array("k2_tags", $search_k2_fields)){
									$db = JFactory::getDbo(); 
									$query = $db->getQuery(true);
									$query->select('t.name')
										->from('#__k2_tags_xref as tv')
										->join('left', '#__k2_tags as t on tv.tagID = t.id')
										->where('t.published = 1')
										->where('tv.itemID = ' . $db->quote((int) $article_201->id));
									$db->setQuery($query);
									$article_201->tags = implode(',', $db->loadColumn());
								}
								if (SearchHelper::checkNoHtml($article_201, $searchword, array('title', 'introtext', 'fulltext','tags'))){
									$new_row_201[] = $article_201;
								}
							}
							$results_201 = array_merge($results_201, (array) $new_row_201);
						}
						} else {
							$results_201 = $results2;
						}
						if(!empty($results_201)){
							foreach($results_201 as $key => &$val){
								$val->ajaxsearchtype = 'k2_items';
							}
						}
						$rowCount = count($results_201);
						$unique_array = json_decode( json_encode($results_201), true);
						$unique_array = array_slice( $unique_array, $start, $perpage_limit );
					} 
					catch (RuntimeException $e){
					}
			} else if($search_in_hikashop == 1){
				$db->getQuery(true);
				$query = "SHOW CREATE TABLE #__hikashop_product";
				$db->setQuery($query);
					try                
					{	
						$db2_c = JFactory::getDbo();
						$query_curr = $db2_c->getQuery(true);
						$query_curr
							->select('hkc.currency_symbol')
							->from($db2_c->quoteName('#__hikashop_config').'AS hkcrr')
							->innerJoin($db2_c->quoteName('#__hikashop_currency').'AS hkc ON hkc.currency_id = hkcrr.config_value')
							->where(' hkcrr.config_namekey = "main_currency"');
						$db2_c->setQuery($query_curr);
						$db2_c->execute();
						$currency_hk = $db2_c->loadObjectList();
						$hk_currency_sym = $currency_hk[0]->currency_symbol;
						// If it fails, it will throw a RuntimeException
						$result = $db->loadResult(); 
						$db2 = JFactory::getDbo();
						$hika_query = '';
						if($hikashopcatid != ''){
							$hika_query = 'AND hkc.category_id IN('.$hikashopcatid.') AND hkcm.category_type = "product"';
						}
						$hs_search_query = [];
						if(in_array("hs_title", $search_hs_fields)){
							$hs_search_query[] = 'hk.product_name LIKE "%' . $searchword . '%"';
						}           
						if(in_array("hs_description", $search_hs_fields)){
							$hs_search_query[] = 'hk.product_description LIKE "%'. $searchword . '%"';
						}
						$tag_query_p2 = '';
						if(in_array("hs_tags", $search_hs_fields)){
							$tag_query_p2 = ' AND ti.published = 1 AND tci.type_alias = "com_hikashop.product"';
							$hs_search_query[] = 'ti.title LIKE "%' . $searchword . '%"';
						}
						$final_hs_search_query = '';
						$final_hs_search_query = join(" OR ",$hs_search_query);
						//$rowCount = $num_rows004;
						if($by_ordering == 'newest'){
						   $order_by = 'hk.product_created DESC';
						} else if($by_ordering == 'oldest'){
						   $order_by = 'hk.product_created ASC';
						} else if($by_ordering == 'popular'){
						   $order_by = 'hk.product_hit DESC';
						} else if($by_ordering == 'alpha'){
						   $order_by = 'hk.product_name ASC';
						} else {
						   $order_by = 'hk.product_created DESC';
						}
						$query3 = $db2->getQuery(true);
						$query3
						  ->select('hk.product_id as id, hk.product_name as title, hk.product_description as introtext, hk.product_hit as hits, hk.product_created as created, hk.product_alias, hk.product_msrp, hkc.category_id as catid, hkf.file_path as images, hkf.file_ordering, hkcm.category_name, hkpr.price_value as price')
						  ->from($db2->quoteName('#__hikashop_product').'AS hk')
						  ->innerJoin($db2->quoteName('#__hikashop_product_category').'AS hkc ON hkc.product_id = hk.product_id')
						  ->leftJoin($db2->quoteName('#__hikashop_file').'AS hkf ON hkf.file_ref_id = hk.product_id AND hkf.file_ordering = 0 AND hkf.file_type = "product"')
						  ->leftJoin($db2->quoteName('#__hikashop_category').'AS hkcm ON hkcm.category_id = hkc.category_id')
						  ->leftJoin($db->quoteName('#__contentitem_tag_map').'AS tci ON tci.content_item_id  = hk.product_id')
						  ->leftJoin($db->quoteName('#__hikashop_price').'AS hkpr ON hkpr.price_product_id  = hk.product_id')
						  ->join('LEFT', $db->quoteName('#__tags').'AS ti ON ti.id = tci.tag_id'.$tag_query_p2)
						  ->where( '( '. $final_hs_search_query .' )  '.$hika_query.' AND hk.product_published = 1')
						  ->group('hk.product_id')
						  ->order($order_by);
						$db2->setQuery($query3,0,$result_limit);
						$db2->execute();
						$num_rows = $db2->getNumRows();
						$results3 = $db2->loadObjectList();
						if(JVERSION < 4 && $correct_language == 'el_gr'){
						$results_301 = array();
						if(count($results3)){
							$new_row_301 = array();
							foreach ($results3 as $article_301){
								if(in_array("hs_tags", $search_hs_fields)){
									$db = JFactory::getDbo(); 
									$query = $db->getQuery(true);
									$query->select('t.title')
										->from('#__contentitem_tag_map as tv')
										->join('left', '#__tags as t on tv.tag_id = t.id')
										->where('tv.type_alias = ' . $db->quote('com_hikashop.product'))
										->where('t.published = 1')
										->where('tv.content_item_id = ' . $db->quote((int) $article_301->id));
									$db->setQuery($query);
									$article_301->tags = implode(',', $db->loadColumn());
								}
								if (SearchHelper::checkNoHtml($article_301, $searchword, array('title', 'introtext','tags'))){
									$new_row_301[] = $article_301;
								}
							}
							$results_301 = array_merge($results_301, (array) $new_row_301);
						}
						} else {
							$results_301 = $results3;
						}
						if(!empty($results_301)){
							foreach($results_301 as $key => &$val){
								$dateInLocal = gmdate('Y-m-d H:i:s', $val->created);
								$val->created = $dateInLocal;
								$val->ajaxsearchtype = 'hikashop_product';
							}
						}
						$rowCount = count($results_301);
						$unique_array = json_decode( json_encode($results_301), true);
						$unique_array = array_slice( $unique_array, $start, $perpage_limit );
					}
					catch (RuntimeException $e){
					}
			} else if($search_in_sppage==1){   
				$db->getQuery(true);
				$query = "SHOW CREATE TABLE #__sppagebuilder";
				$db->setQuery($query);
					try                
					{
						// If it fails, it will throw a RuntimeException
						$result = $db->loadResult(); 
						$db7 = JFactory::getDbo();
						$spcat_query = '';
						if($spcatid != ''){
							$spcat_query = 'AND ' . $db7->quoteName('catid') . ' IN('.$spcatid.')';
						}
						$sp_search_query = [];
						if(in_array("sp_title", $search_sp_fields)){
							$sp_search_query[] = $db7->quoteName('title') . ' LIKE '. $db7->quote('%' . $searchword . '%');
						}           
						if(in_array("sp_description", $search_sp_fields)){
							$sp_search_query[] = $db7->quoteName('text') . ' LIKE '. $db7->quote('%' . $searchword . '%');
						}
						$final_sp_search_query = '';
						$final_sp_search_query = join(" OR ",$sp_search_query);
						if($by_ordering == 'newest'){
						   $order_by = 'created_on DESC';
						} else if($by_ordering == 'oldest'){
						   $order_by = 'created_on ASC';
						} else if($by_ordering == 'popular'){
						   $order_by = 'hits DESC';
						} else if($by_ordering == 'alpha'){
						   $order_by = 'title ASC';
						} else {
						   $order_by = 'created_on DESC';
						}
						$query4 = $db7->getQuery(true);
						$query4
						->select('`id`,`title`,`catid`, text as introtext,`hits`, created_on as created,`published`,`extension`, `language`')
						->from($db7->quoteName('#__sppagebuilder'))
						->where('('. $final_sp_search_query . ' ) '.$spcat_query. ' AND ' .'published = 1 AND extension = "com_sppagebuilder"')
						->group($db->quoteName('id'))
						->order($order_by);
						$db7->setQuery($query4,0,$result_limit);            
						$db7->execute();
						$num_rows = $db7->getNumRows();
						$results4 = $db7->loadObjectList();
						if(JVERSION < 4 && $correct_language == 'el_gr'){
						$results_401 = array();
						if(count($results4)){
							$new_row_401 = array();
							foreach ($results4 as $article_401){
								if (SearchHelper::checkNoHtml($article_401, $searchword, array('title', 'introtext'))){
									$new_row_401[] = $article_401;
								}
							}
							$results_401 = array_merge($results_401, (array) $new_row_401);
						}
						} else {
							$results_401 = $results4;
						}
						if(!empty($results_401)){
							foreach($results_401 as $key => &$val){
								$val->ajaxsearchtype = 'sppage';
							}
						}
						$rowCount = count($results_401);
						$unique_array = json_decode( json_encode($results_401), true);
						$unique_array = array_slice( $unique_array, $start, $perpage_limit );
					}
					catch (RuntimeException $e){
					}
			} else if($search_in_vm==1){   
				$db->getQuery(true);
				$query = "SHOW CREATE TABLE #__virtuemart_products";
				$db->setQuery($query);
					try                
					{   
						//require(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_virtuemart'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'config.php'); 
						//VmConfig::loadConfig(true);
						// If it fails, it will throw a RuntimeException
						$result = $db->loadResult();
						$db111 = JFactory::getDbo();
						$query111 = $db111->getQuery(true); 
						$query111
						->select('element, enabled')    
						->from($db111->quoteName('#__extensions'))
						->where('element = "com_virtuemart"');
						$db111->setQuery($query111); 
						$db111->execute();
						$num_rows = $db111->getNumRows();
						$results111 = $db111->loadObjectList();
						$table_name_vm_pro = '#__virtuemart_products_'.$correct_language;
						$table_name_vm_cat = '#__virtuemart_categories_'.$correct_language;
						if($results111[0]->enabled == 1){
							$db11 = JFactory::getDbo();
							$vmcatlis_query = '';
							if($vmcatid != ''){
								$vmcatlis_query = 'AND vpc.virtuemart_category_id IN('.$vmcatid.')';
							}
							$vm_search_query = [];
							if(in_array("vm_title", $search_vm_fields)){
								$vm_search_query[] = 'vpeg.product_name LIKE "%' . $searchword . '%"';
							}           
							if(in_array("vm_description", $search_vm_fields)){
								$vm_search_query[] = 'vpeg.product_s_desc LIKE "%'. $searchword . '%" OR vpeg.product_desc LIKE "%'. $searchword . '%"';
							}
							$final_vm_search_query = '';
							$final_vm_search_query = join(" OR ",$vm_search_query);
							if($by_ordering == 'newest'){
							   $order_by = 'vp.created_on DESC';
							} else if($by_ordering == 'oldest'){
							   $order_by = 'vp.created_on ASC';
							} else if($by_ordering == 'popular'){
							   $order_by = 'vp.hits DESC';
							} else if($by_ordering == 'alpha'){
							   $order_by = 'vpeg.product_name ASC';
							} else {
							   $order_by = 'vp.created_on DESC';
							}
							$query5 = $db11->getQuery(true);
							$query5
							  ->select('vp.virtuemart_product_id as id, vpeg.product_name as title, vpeg.product_s_desc as introtext, vpeg.product_desc as fulltext12, vp.created_on as created, vpeg.slug as alias, vpc.virtuemart_category_id as catid, vceg.category_name, vm.file_url images, vp.hits as hits, pri.product_price, vc.currency_symbol')
							  ->from($db11->quoteName('#__virtuemart_products').'AS vp')
							  ->innerJoin($db11->quoteName('#__virtuemart_product_categories').'AS vpc ON vp.virtuemart_product_id = vpc.virtuemart_product_id')
							  ->leftJoin($db11->quoteName($table_name_vm_cat).'AS vceg ON vpc.virtuemart_category_id = vceg.virtuemart_category_id')
							  ->leftJoin($db11->quoteName($table_name_vm_pro).'AS vpeg ON vp.virtuemart_product_id = vpeg.virtuemart_product_id')
							  ->leftJoin($db11->quoteName('#__virtuemart_product_medias').'AS vpm ON vpm.virtuemart_product_id = vp.virtuemart_product_id AND vpm.ordering = 1')
							  ->leftJoin($db11->quoteName('#__virtuemart_medias').'AS vm ON vpm.virtuemart_media_id = vm.virtuemart_media_id')
							  ->leftJoin($db11->quoteName('#__virtuemart_product_prices').'AS pri ON pri.virtuemart_product_id = vp.virtuemart_product_id')
							  ->leftJoin($db11->quoteName('#__virtuemart_currencies').'AS vc ON vc.virtuemart_currency_id = pri.product_currency')						
							  ->where( '( '.$final_vm_search_query.' )  '.$vmcatlis_query.' AND vp.published = 1 ')
							  ->group('vp.virtuemart_product_id')
							  ->order($order_by);
							$db11->setQuery($query5,0,$result_limit); 
							$db11->execute();                        
							$num_rows = $db11->getNumRows();
							$results5 = $db11->loadObjectList();
							if(JVERSION < 4 && $correct_language == 'el_gr'){
							$results_501 = array();
							if(count($results5)){
								$new_row_501 = array();
								foreach ($results5 as $article_501){
									if (SearchHelper::checkNoHtml($article_501, $searchword, array('title', 'introtext', 'fulltext12'))){
										$new_row_501[] = $article_501;
									}
								}
								$results_501 = array_merge($results_501, (array) $new_row_501);
							}  
							} else {
								$results_501 = $results5;
							}
							if(!empty($results_501)){
								foreach($results_501 as $key => &$val){
									$val->ajaxsearchtype = 'vm_product';
								}
							}
							$rowCount = count($results_501);
							$unique_array = json_decode( json_encode($results_501), true);
							$unique_array = array_slice( $unique_array, $start, $perpage_limit );
						}
					}
					catch (RuntimeException $e){
					}
			} else if($search_in_pc==1){   
				$db->getQuery(true);
				$query = "SHOW CREATE TABLE #__phocacart_categories";
				$db->setQuery($query); 
					try                
					{  
						// If it fails, it will throw a RuntimeException
						$result = $db->loadResult(); 
						$db13 = JFactory::getDbo();
						$pccat_query = '';
						if($pccatid != ''){
							$pccat_query = 'AND ( pcc.category_id IN ('.$pccatid.') AND pccm.published = 1)';
						}
						$pc_search_query = [];
						if(in_array("pc_title", $search_pc_fields)){
							$pc_search_query[] = 'pc.title LIKE '. $db13->quote('%' . $searchword . '%');
						}           
						if(in_array("pc_sort_description", $search_pc_fields)){
							$pc_search_query[] = 'pc.description LIKE '. $db13->quote('%' . $searchword . '%');
						}
						if(in_array("pc_long_description", $search_pc_fields)){
							$pc_search_query[] = 'pc.description_long LIKE '. $db13->quote('%' . $searchword . '%');
						}
						if(in_array("pc_features", $search_pc_fields)){
							$pc_search_query[] = 'pc.features LIKE '. $db13->quote('%' . $searchword . '%');
						}
						if(in_array("pc_sku", $search_pc_fields)){
							$pc_search_query[] = 'pc.sku LIKE '. $db13->quote('%' . $searchword . '%');
						}
						$tag_query_p1 = '';
						if(in_array("pc_tag", $search_pc_fields)){
							$tag_query_p1 = ' AND pt.published = 1';
							$pc_search_query[] = 'pt.title LIKE "%' . $searchword . '%"';
						}
						$tag_query_p2 = '';
						if(in_array("pc_label", $search_pc_fields)){
							$tag_query_p2 = ' AND ptl.published = 1';
							$pc_search_query[] = 'ptl.title LIKE "%' . $searchword . '%"';
						}
						$final_pc_search_query = '';
						$final_pc_search_query = join(" OR ",$pc_search_query);
						if($by_ordering == 'newest'){
						   $order_by = 'pc.created DESC';
						} else if($by_ordering == 'oldest'){
						   $order_by = 'pc.created ASC';
						} else if($by_ordering == 'popular'){
						   $order_by = 'pc.hits DESC';
						} else if($by_ordering == 'alpha'){
						   $order_by = 'pc.title ASC';
						} else {
						   $order_by = 'pc.created DESC';
						}
						$query6 = $db13->getQuery(true);
						$query6
						  ->select('pc.id as id, pc.title as title, pc.description as introtext, pc.description_long as fulltext23, pc.features as features, pc.hits as hits, pc.created as created, pc.alias, pc.image as images, pc.sku, pccm.title as cattitle, pccm.alias as catalias, pcc.category_id as catid')
						  ->from($db13->quoteName('#__phocacart_products').'AS pc')
						  ->innerJoin($db13->quoteName('#__phocacart_product_categories').'AS pcc ON pc.id = pcc.product_id')
						  ->leftJoin($db13->quoteName('#__phocacart_categories').'AS pccm ON pcc.category_id = pccm.id')
						  ->leftJoin($db13->quoteName('#__phocacart_tags_related').'AS tr ON tr.item_id  = pc.id')
						  ->leftJoin($db13->quoteName('#__phocacart_taglabels_related').'AS tlr ON tlr.item_id  = pc.id')
						  ->join('LEFT', $db13->quoteName('#__phocacart_tags').'AS pt ON pt.id = tr.tag_id'.$tag_query_p1)
						  ->join('LEFT', $db13->quoteName('#__phocacart_tags').'AS ptl ON ptl.id = tlr.tag_id'.$tag_query_p2)
						  ->where( '( '. $final_pc_search_query .' )  '.$pccat_query.' AND pc.published = 1')
						  ->group('pc.id')
						  ->order($order_by);
						$db13->setQuery($query6,0,$result_limit);            
						$db13->execute();
						$num_rows = $db13->getNumRows();
						$results6 = $db13->loadObjectList();
						if(JVERSION < 4 && $correct_language == 'el_gr'){
						$results_601 = array();
						if(count($results6)){
							$new_row_601 = array();
							foreach ($results6 as $article_601){
								if(in_array("pc_tag", $search_pc_fields)){
									$db = JFactory::getDbo(); 
									$query = $db->getQuery(true);
									$query->select('pt.title')
										->from('#__phocacart_tags_related as ptr')
										->join('left', '#__phocacart_tags as pt on ptr.tag_id = pt.id')
										->where('pt.published = 1')
										->where('ptr.item_id = ' . $db->quote((int) $article_601->id));
									$db->setQuery($query);
									$article_601->tags = implode(',', $db->loadColumn());
								}
								if(in_array("pc_label", $search_pc_fields)){
									$db = JFactory::getDbo(); 
									$query = $db->getQuery(true);
									$query->select('ppt.title')
										->from('#__phocacart_taglabels_related as pptr')
										->join('left', '#__phocacart_tags as ppt on pptr.tag_id = ppt.id')
										->where('ppt.published = 1')
										->where('pptr.item_id = ' . $db->quote((int) $article_601->id));
									$db->setQuery($query);
									$article_601->labels = implode(',', $db->loadColumn());
								}
								if (SearchHelper::checkNoHtml($article_601, $searchword, array('title', 'introtext', 'fulltext23', 'features','tags', 'labels'))){
									$new_row_601[] = $article_601;
								}
							}
							$results_601 = array_merge($results_601, (array) $new_row_601);
						}
						} else {
							$results_601 = $results6;
						}
						if(!empty($results_601)){
							foreach($results_601 as $key => &$val){
								$val->ajaxsearchtype = 'pc_items';
							}
						}
						$rowCount = count($results_601);
						$unique_array = json_decode( json_encode($results_601), true);
						$unique_array = array_slice( $unique_array, $start, $perpage_limit );
					} 
					catch (RuntimeException $e){
					}
					
			}else if($search_in_dj==1){             
				
				$db->getQuery(true);

				$query = "SHOW CREATE TABLE #__djev_events";

				$db->setQuery($query);

					try                

					{

						// If it fails, it will throw a RuntimeException
						
						$result = $db->loadResult(); 						
						$db_8 = JFactory::getDbo();
						$djcat_query = '';
						
						if($djcatid != ''){
							$djcat_query = 'AND ev.' . $db_8->quoteName('cat_id') . ' IN('.$djcatid.')';
						}

						$dj_search_query = [];
						if(in_array("dj_title", $search_dj_fields)){
							$dj_search_query[] = 'ev.title LIKE '. $db_8->quote('%' . $searchword . '%');
						}           

						if(in_array("dj_sort_description", $search_dj_fields)){
							$dj_search_query[] = 'ev.intro LIKE '. $db_8->quote('%' . $searchword . '%');
						}

						if(in_array("dj_description", $search_dj_fields)){
							$dj_search_query[] = 'ev.description LIKE '. $db_8->quote('%' . $searchword . '%');
						}
						
						$tag_query_p = '';
						if(in_array("dj_tag", $search_dj_fields)){
							$tag_query_p1 = ' AND ti.published = 1';
							$dj_search_query[] = 'ti.title LIKE "%' . $searchword . '%"';
						}

						$final_dj_search_query = '';
						$final_dj_search_query = join(" OR ",$dj_search_query);						
						
						if($by_ordering == 'newest'){
						   $order_by = 'ev.created DESC';
						} else if($by_ordering == 'oldest'){
						   $order_by = 'ev.created ASC';
						} else if($by_ordering == 'alpha'){
						   $order_by = 'ev.title ASC';
						} else {
						   $order_by = 'ev.created DESC';
						}

						$query8 = $db_8->getQuery(true);

						$query8
						->select('ev.id, ev.title, ev.alias, ev.cat_id as catid, ev.intro as introtext, ev.time, dem.image as poster, ev.description, ev.created, ev.published')
						->from($db_8->quoteName('#__djev_events').'AS ev')
						->leftJoin($db->quoteName('#__djev_jtags_xref').'AS etg ON etg.event_id  = ev.id')
						->join('LEFT', $db->quoteName('#__tags').'AS ti ON ti.id = etg.tag_id'.$tag_query_p)
						->leftJoin($db->quoteName('#__djev_events_media').'AS dem ON dem.event_id = ev.id AND dem.poster = 1')
						->where('('. $final_dj_search_query . ' ) '.$djcat_query . $tag_query_p1 . ' AND ' .'ev.published = 1 ')					
						->order($order_by);
					
						$db_8->setQuery($query8,0,$result_limit);            

						$db_8->execute();

						$num_rows = $db_8->getNumRows();

						$results8 = $db_8->loadObjectList();
						
						$results_801 = array();
						
						if(JVERSION < 4 && $correct_language == 'el_gr'){

						} else {

							$results_801 = $results8;

						}
						
						if(!empty($results_801)){

							foreach($results_801 as $key => &$val){

								$val->ajaxsearchtype = 'dj_items';

							}

						}

						$rowCount = count($results_801);

						$unique_array = json_decode( json_encode($results_801), true);

						$unique_array = array_slice( $unique_array, $start, $perpage_limit );

					}

					catch (RuntimeException $e){

					}
			
			}else if($search_in_docman==1){             
				
				$db->getQuery(true);
			
				$query = "SHOW CREATE TABLE #__docman_documents";

				$db->setQuery($query);

					try                

					{

						// If it fails, it will throw a RuntimeException
						
						$result = $db->loadResult(); 						
						$db_9 = JFactory::getDbo();
						$docmancat_query = '';
						
						if($docmancatids != ''){
							$docmancat_query = 'AND dd.' . $db_9->quoteName('docman_category_id') . ' IN('.$docmancatids.')';
						}

						$dc_search_query = [];
						if(in_array("docman_title", $search_docman_fields)){
							$dc_search_query[] = 'dd.title LIKE '. $db_9->quote('%' . $searchword . '%');
						}           

						if(in_array("docman_description", $search_docman_fields)){
							$dc_search_query[] = 'dd.description LIKE '. $db_9->quote('%' . $searchword . '%');
						}
						
						$final_dc_search_query = '';
						$final_dc_search_query = join(" OR ",$dc_search_query);						
						
						if($by_ordering == 'newest'){
						   $order_by = 'dd.created_on DESC';
						} else if($by_ordering == 'oldest'){
						   $order_by = 'dd.created_on ASC';
						} else if($by_ordering == 'alpha'){
						   $order_by = 'dd.title ASC';
						} else {
						   $order_by = 'dd.created_on DESC';
						}

						$query9 = $db_9->getQuery(true);

						$query9
						->select('dd.docman_document_id as id, dd.title, dd.slug as alias, dd.docman_category_id as catid, dd.description as introtext, dd.image as poster, dd.created_on, dd.publish_on')
						->from($db_9->quoteName('#__docman_documents').'AS dd')
						//->leftJoin($db->quoteName('#__docman_categories').'AS dc ON dc.docman_category_id = dd.docman_category_id')
						->where('('. $final_dc_search_query . ' ) '.$docmancat_query . ' AND ' .'dd.enabled = 1 ')					
						->order($order_by);
					
						$db_9->setQuery($query9,0,$result_limit);            

						$db_9->execute();

						$num_rows = $db_9->getNumRows();

						$results9 = $db_9->loadObjectList();
						
						$results_901 = array();
						
						if(JVERSION < 4 && $correct_language == 'el_gr'){

						} else {

							$results_901 = $results9;

						}
						
						if(!empty($results_901)){

							foreach($results_901 as $key => &$val){

								$val->ajaxsearchtype = 'dc_items';

							}

						}

						$rowCount = count($results_901);

						$unique_array = json_decode( json_encode($results_901), true);

						$unique_array = array_slice( $unique_array, $start, $perpage_limit );

					}

					catch (RuntimeException $e){

					}
			
			}
				
			
			$pagConfig = array(
                    'currentPage' => $start,
                    'totalRows' => $rowCount,
                    'perPage' => $perpage_limit,
                    'redirect_url' => $redirect_search_url,
                    'link_func' => 'searchFilter_'.$module_idd
                );
                $pagination =  new Pagination($pagConfig);
            // echo '<div class="pagination_wrap">'.$pagination->createLinks().'</div>';
        } else {
        $db = JFactory::getDbo();
        $results1=array();
        $results3=array();
        $results5=array();
        $results7=array();
        $results11=array();
        $results13=array();
        $results14=array();
        $results_101=array();
        $results_201=array();
        $results_301=array();
        $results_401=array();
        $results_501=array();
        $results_601=array();
        $results_701=array();
        if($search_in_article==1)
        {   
            $db->getQuery(true);
            $query = "SHOW CREATE TABLE #__content";
            $db->setQuery($query); 
            try {  
                $result = $db->loadResult();
                $cat_query = '';
                if(in_array("sf_category", $search_article_fields)){
                    $cat_query = 'AND ' . $db->quoteName('catid') . ' IN('.$catids.')';
                }
                $article_search_query = [];
                $field_query = '';
                if(in_array("sf_customfield", $search_article_fields)){
                    $field_query = ' OR ( fi.context = "com_content.article" AND fi.state = 1)';
                    $article_search_query[] = 'fv.value LIKE "%' . $searchword . '%"';
                }
                if(in_array("sf_title", $search_article_fields)){
                    $article_search_query[] = 'ca.title LIKE "%' . $searchword . '%"';
                }           
                if(in_array("sf_description", $search_article_fields)){
                    $article_search_query[] = 'ca.introtext LIKE "%' . $searchword . '%" OR ca.fulltext LIKE "%' . $searchword . '%"';
                }
                $tag_query_p = '';
                if(in_array("sf_tags", $search_article_fields)){
                    $tag_query_p = ' AND ti.published = 1 AND tci.type_alias = "com_content.article"';
                    $article_search_query[] = 'ti.title LIKE "%' . $searchword . '%"';
                }
                $final_article_search_query = '';
                $final_article_search_query = join(" OR ",$article_search_query);
                $query1 = $db->getQuery(true);
                $query1
                ->select(array('ca.id', 'ca.title', 'ca.catid', 'ca.images', 'ca.introtext', 'ca.fulltext', 'ca.ordering', 'ca.hits', 'ca.created'))
                ->from($db->quoteName('#__content').'AS ca')
                ->leftJoin($db->quoteName('#__fields_values').'AS fv ON ca.id = fv.item_id')
                ->join('LEFT', $db->quoteName('#__fields').'AS fi ON fi.id = fv.field_id'.$field_query)
                ->leftJoin($db->quoteName('#__contentitem_tag_map').'AS tci ON tci.content_item_id  = ca.id')
                ->join('LEFT', $db->quoteName('#__tags').'AS ti ON ti.id = tci.tag_id'.$tag_query_p)       
				->where( '( '.$final_article_search_query.' ) '.$cat_query. ' AND (ca.language="'.$languagevar.'" OR ca.language="*") AND (ca.publish_down IS NULL OR ca.publish_down >= CURRENT_TIMESTAMP) AND ca.state = 1 ')
                ->group('ca.id')
                ->order('ordering ASC');
                $db->setQuery($query1,0,$result_limit);
                $db->execute();
                $num_rows = $db->getNumRows();
                $results1 = $db->loadObjectList();
                if(JVERSION < 4 && $correct_language == 'el_gr'){
                $results_101 = array();
                    if(count($results1)){
                        $new_row_101 = array();
                        foreach ($results1 as $article_101){
                            if(in_array("sf_customfield", $search_article_fields)){
                                $db = JFactory::getDbo(); 
                                $query = $db->getQuery(true);
                                $query->select('fv.value')
                                    ->from('#__fields_values as fv')
                                    ->join('left', '#__fields as f on fv.field_id = f.id')
                                    ->where('f.context = ' . $db->quote('com_content.article'))
                                    ->where('fv.item_id = ' . $db->quote((int) $article_101->id));
                                $db->setQuery($query);
                                $article_101->jcfields = implode(',', $db->loadColumn());
                            }
                            if(in_array("sf_tags", $search_article_fields)){
                                $db = JFactory::getDbo(); 
                                $query = $db->getQuery(true);
                                $query->select('t.title')
                                    ->from('#__contentitem_tag_map as tv')
                                    ->join('left', '#__tags as t on tv.tag_id = t.id')
                                    ->where('tv.type_alias = ' . $db->quote('com_content.article'))
                                    ->where('t.published = 1')
                                    ->where('tv.content_item_id = ' . $db->quote((int) $article_101->id));
                                $db->setQuery($query);
                                $article_101->tags = implode(',', $db->loadColumn());
                            }
                            if (SearchHelper::checkNoHtml($article_101, $searchword, array('title', 'introtext', 'fulltext', 'jcfields','tags'))){
                                $new_row_101[] = $article_101;
                            }
                        }
                        $results_101 = array_merge($results_101, (array) $new_row_101);
                    }
                } else {
                        $results_101 = $results1;
                    }
                if(!empty($results_101)){
                    foreach($results_101 as $key => &$val){
                        $val->ajaxsearchtype = 'joomla_article';
                    }
                }
            } 
            catch (RuntimeException $e){
            }
        }
        if($search_in_k2==1)
        {   
            $db->getQuery(true);
            $query = "SHOW CREATE TABLE #__k2_categories";
            $db->setQuery($query); 
                try                
                {  
                    // If it fails, it will throw a RuntimeException
                    $result = $db->loadResult(); 
                    $db1 = JFactory::getDbo();
                    $k2cat_query = '';
                    if($k2catid != ''){
                        $k2cat_query = 'AND ' . $db1->quoteName('catid') . ' IN('.$k2catid.')';
                    }
                    $k2_search_query = [];
                    if(in_array("k2_title", $search_k2_fields)){
                        $k2_search_query[] = 'k2.title LIKE '. $db1->quote('%' . $searchword . '%');
                    }           
                    if(in_array("k2_description", $search_k2_fields)){
                        $k2_search_query[] = 'k2.introtext LIKE '. $db1->quote('%' . $searchword . '%'). ' OR k2.fulltext LIKE '. $db1->quote('%' . $searchword . '%');
                    }
                    $tag_query_p1 = '';
                    if(in_array("k2_tags", $search_k2_fields)){
                        $tag_query_p1 = ' AND k2t.published = 1';
                        $k2_search_query[] = 'k2t.name LIKE "%' . $searchword . '%"';
                    }
                    $final_k2_search_query = '';
                    $final_k2_search_query = join(" OR ",$k2_search_query);
                    $query3 = $db1->getQuery(true);
                    $query3
                    ->select('k2.id , k2.title , k2.alias , k2.catid , k2.introtext , k2.fulltext , k2.hits ,k2.title as type, k2.created')
                    ->from($db1->quoteName('#__k2_items').'AS k2')
                    ->leftJoin($db1->quoteName('#__k2_tags_xref').'AS k2tx ON k2tx.itemID  = k2.id')
                    ->join('LEFT', $db1->quoteName('#__k2_tags').'AS k2t ON k2t.id = k2tx.tagID'.$tag_query_p1)
                    ->where('('.$final_k2_search_query.' ) '.$k2cat_query. ' AND ' . $db1->quoteName('k2.published') . ' = 1')
                    ->group($db->quoteName('k2.id'))
                    ->order('ordering ASC');
                    $db1->setQuery($query3,0,$result_limit);            
                    $db1->execute();
                    $num_rows = $db1->getNumRows();
                    $results3 = $db1->loadObjectList();  
                    if(JVERSION < 4 && $correct_language == 'el_gr'){
                    $results_201 = array();
                    if(count($results3)){
                        $new_row_201 = array();
                        foreach ($results3 as $article_201){
                            if(in_array("k2_tags", $search_k2_fields)){
                                $db = JFactory::getDbo(); 
                                $query = $db->getQuery(true);
                                $query->select('t.name')
                                    ->from('#__k2_tags_xref as tv')
                                    ->join('left', '#__k2_tags as t on tv.tagID = t.id')
                                    ->where('t.published = 1')
                                    ->where('tv.itemID = ' . $db->quote((int) $article_201->id));
                                $db->setQuery($query);
                                $article_201->tags = implode(',', $db->loadColumn());
                            }
                            if (SearchHelper::checkNoHtml($article_201, $searchword, array('title', 'introtext', 'fulltext','tags'))){
                                $new_row_201[] = $article_201;
                            }
                        }
                        $results_201 = array_merge($results_201, (array) $new_row_201);
                    }
                    } else {
                        $results_201 = $results3;
                    }
                    if(!empty($results_201)){
                        foreach($results_201 as $key => &$val){
                            $val->ajaxsearchtype = 'k2_items';
                        }
                    }
                } 
                catch (RuntimeException $e){
                }
        }
        if($search_in_hikashop == 1){
            $db->getQuery(true);
            $query = "SHOW CREATE TABLE #__hikashop_product";
            $db->setQuery($query);
                try                
                {
					$db2_c = JFactory::getDbo();
					$query_curr = $db2_c->getQuery(true);
					$query_curr
						->select('hkc.currency_symbol')
						->from($db2_c->quoteName('#__hikashop_config').'AS hkcrr')
						->innerJoin($db2_c->quoteName('#__hikashop_currency').'AS hkc ON hkc.currency_id = hkcrr.config_value')
						->where(' hkcrr.config_namekey = "main_currency"');
					$db2_c->setQuery($query_curr);
					$db2_c->execute();
					$currency_hk = $db2_c->loadObjectList();
					$hk_currency_sym = $currency_hk[0]->currency_symbol;
                    $result = $db->loadResult(); 
                    $db2 = JFactory::getDbo();
                    $hika_query = '';
                    if($hikashopcatid != ''){
                        $hika_query = 'AND hkc.category_id IN('.$hikashopcatid.') AND hkcm.category_type = "product"';
                    }
                    $hs_search_query = [];
                    if(in_array("hs_title", $search_hs_fields)){
                        $hs_search_query[] = 'hk.product_name LIKE "%' . $searchword . '%"';
                    }           
                    if(in_array("hs_description", $search_hs_fields)){
                        $hs_search_query[] = 'hk.product_description LIKE "%'. $searchword . '%"';
                    }
                    $tag_query_p2 = '';
                    if(in_array("hs_tags", $search_hs_fields)){
                        $tag_query_p2 = ' AND ti.published = 1 AND tci.type_alias = "com_hikashop.product"';
                        $hs_search_query[] = 'ti.title LIKE "%' . $searchword . '%"';
                    }
                    $final_hs_search_query = '';
                    $final_hs_search_query = join(" OR ",$hs_search_query);
                    $query5 = $db2->getQuery(true);
                    $query5
                      ->select('hk.product_id as id, hk.product_name as title, hk.product_description as introtext, hk.product_hit as hits, hk.product_created as created, hk.product_alias, hk.product_msrp, hkc.category_id as catid, hkf.file_path as images, hkf.file_ordering, hkcm.category_name, hkpr.price_value as price')
                      ->from($db2->quoteName('#__hikashop_product').'AS hk')
                      ->innerJoin($db2->quoteName('#__hikashop_product_category').'AS hkc ON hkc.product_id = hk.product_id')
                      ->leftJoin($db2->quoteName('#__hikashop_file').'AS hkf ON hkf.file_ref_id = hk.product_id AND hkf.file_ordering = 0 AND hkf.file_type = "product"')
                      ->leftJoin($db2->quoteName('#__hikashop_category').'AS hkcm ON hkcm.category_id = hkc.category_id')
                      ->leftJoin($db->quoteName('#__contentitem_tag_map').'AS tci ON tci.content_item_id  = hk.product_id')
                      ->leftJoin($db->quoteName('#__hikashop_price').'AS hkpr ON hkpr.price_product_id  = hk.product_id')
                      ->join('LEFT', $db->quoteName('#__tags').'AS ti ON ti.id = tci.tag_id'.$tag_query_p2)
                      ->where( '( '. $final_hs_search_query .' )  '.$hika_query.' AND hk.product_published = 1')
                      ->group('hk.product_id')
                      ->order('ordering ASC');
                    $db2->setQuery($query5,0,$result_limit);
                    $db2->execute();
                    $num_rows = $db2->getNumRows();
                    $results5 = $db2->loadObjectList();
                    if(JVERSION < 4 && $correct_language == 'el_gr'){
                    $results_301 = array();
                    if(count($results5)){
                        $new_row_301 = array();
                        foreach ($results5 as $article_301){
                            if(in_array("hs_tags", $search_hs_fields)){
                                $db = JFactory::getDbo(); 
                                $query = $db->getQuery(true);
                                $query->select('t.title')
                                    ->from('#__contentitem_tag_map as tv')
                                    ->join('left', '#__tags as t on tv.tag_id = t.id')
                                    ->where('tv.type_alias = ' . $db->quote('com_hikashop.product'))
                                    ->where('t.published = 1')
                                    ->where('tv.content_item_id = ' . $db->quote((int) $article_301->id));
                                $db->setQuery($query);
                                $article_301->tags = implode(',', $db->loadColumn());
                            }
                            if (SearchHelper::checkNoHtml($article_301, $searchword, array('title', 'introtext','tags'))){
                                $new_row_301[] = $article_301;
                            }
                        }
                        $results_301 = array_merge($results_301, (array) $new_row_301);
                    }
                    } else {
                        $results_301 = $results5;
                    }
                    if(!empty($results_301)){
                        foreach($results_301 as $key => &$val){
                            $dateInLocal = gmdate('Y-m-d H:i:s', $val->created);
                            $val->created = $dateInLocal;
                            $val->ajaxsearchtype = 'hikashop_product';
                        }
                    }
                }
                catch (RuntimeException $e){
                }
        }
        if($search_in_sppage==1)
        {   
            $db->getQuery(true);
            $query = "SHOW CREATE TABLE #__sppagebuilder";
            $db->setQuery($query);
                try                
                {
                    // If it fails, it will throw a RuntimeException
                    $result = $db->loadResult(); 
                    $db7 = JFactory::getDbo();
                    $spcat_query = '';
                    if($spcatid != ''){
                        $spcat_query = 'AND ' . $db7->quoteName('catid') . ' IN('.$spcatid.')';
                    }
                    $sp_search_query = [];
                    if(in_array("sp_title", $search_sp_fields)){
                        $sp_search_query[] = $db7->quoteName('title') . ' LIKE '. $db7->quote('%' . $searchword . '%');
                    }           
                    if(in_array("sp_description", $search_sp_fields)){
                        $sp_search_query[] = $db7->quoteName('text') . ' LIKE '. $db7->quote('%' . $searchword . '%');
                    }
                    $final_sp_search_query = '';
                    $final_sp_search_query = join(" OR ",$sp_search_query);
                    $query7 = $db7->getQuery(true);
                    $query7
                    ->select('`id`,`title`,`catid`, text as introtext,`hits`, created_on as created,`published`,`extension`, `language`')
                    ->from($db7->quoteName('#__sppagebuilder'))
                    ->where('('. $final_sp_search_query . ' ) '.$spcat_query. ' AND ' .'published = 1 AND extension = "com_sppagebuilder"')
                    ->group($db->quoteName('id'))
                    ->order('ordering ASC');
                    $db7->setQuery($query7,0,$result_limit);            
                    $db7->execute();
                    $num_rows = $db7->getNumRows();
                    $results7 = $db7->loadObjectList();  
                    if(JVERSION < 4 && $correct_language == 'el_gr'){
                    $results_401 = array();
                    if(count($results7)){
                        $new_row_401 = array();
                        foreach ($results7 as $article_401){
                            if (SearchHelper::checkNoHtml($article_401, $searchword, array('title', 'introtext'))){
                                $new_row_401[] = $article_401;
                            }
                        }
                        $results_401 = array_merge($results_401, (array) $new_row_401);
                    }
                    } else {
                        $results_401 = $results7;
                    }
                    if(!empty($results_401)){
                        foreach($results_401 as $key => &$val){
                            $val->ajaxsearchtype = 'sppage';
                        }
                    }
                }
                catch (RuntimeException $e){
                }
        }
        if($search_in_vm==1)
        {   
            $db->getQuery(true);
            $query = "SHOW CREATE TABLE #__virtuemart_products";
            $db->setQuery($query);
                try                
                {   
                    // If it fails, it will throw a RuntimeException
                    $result = $db->loadResult();
                    $db111 = JFactory::getDbo();
                    $query111 = $db111->getQuery(true); 
                    $query111
                    ->select('element, enabled')    
                    ->from($db111->quoteName('#__extensions'))
                    ->where('element = "com_virtuemart"');
                    $db111->setQuery($query111); 
                    $db111->execute();
                    $num_rows = $db111->getNumRows();
                    $results111 = $db111->loadObjectList();
                    $table_name_vm_pro = '#__virtuemart_products_'.$correct_language;
                    $table_name_vm_cat = '#__virtuemart_categories_'.$correct_language;
                    if($results111[0]->enabled == 1){
                        $db11 = JFactory::getDbo();
                        $vmcatlis_query = '';
                        if($vmcatid != ''){
                            $vmcatlis_query = 'AND vpc.virtuemart_category_id IN('.$vmcatid.')';
                        }
                        $vm_search_query = [];
                        if(in_array("vm_title", $search_vm_fields)){
                            $vm_search_query[] = 'vpeg.product_name LIKE "%' . $searchword . '%"';
                        }           
                        if(in_array("vm_description", $search_vm_fields)){
                            $vm_search_query[] = 'vpeg.product_s_desc LIKE "%'. $searchword . '%" OR vpeg.product_desc LIKE "%'. $searchword . '%"';
                        }
                        $final_vm_search_query = '';
                        $final_vm_search_query = join(" OR ",$vm_search_query);
                        $query11 = $db11->getQuery(true);
                        $query11
                          ->select('vp.virtuemart_product_id as id, vpeg.product_name as title, vpeg.product_s_desc as introtext, vpeg.product_desc as fulltext12, vp.created_on as created, vpeg.slug as alias, vpc.virtuemart_category_id as catid, vceg.category_name, vm.file_url images, vp.hits as hits, vmpp.product_price, vc.currency_symbol, vmpp.product_override_price as override_price')
                          ->from($db11->quoteName('#__virtuemart_products').'AS vp')
                          ->innerJoin($db11->quoteName('#__virtuemart_product_categories').'AS vpc ON vp.virtuemart_product_id = vpc.virtuemart_product_id')
                          ->leftJoin($db11->quoteName('#__virtuemart_product_prices').'AS vmpp ON vmpp. virtuemart_product_id  = vp.virtuemart_product_id')
                          ->leftJoin($db11->quoteName($table_name_vm_cat).'AS vceg ON vpc.virtuemart_category_id = vceg.virtuemart_category_id')
                          ->leftJoin($db11->quoteName($table_name_vm_pro).'AS vpeg ON vp.virtuemart_product_id = vpeg.virtuemart_product_id')
                          ->leftJoin($db11->quoteName('#__virtuemart_product_medias').'AS vpm ON vpm.virtuemart_product_id = vp.virtuemart_product_id AND vpm.ordering = 1')
                          ->leftJoin($db11->quoteName('#__virtuemart_medias').'AS vm ON vpm.virtuemart_media_id = vm.virtuemart_media_id')
                          ->leftJoin($db11->quoteName('#__virtuemart_currencies').'AS vc ON vc.virtuemart_currency_id = vmpp.product_currency')
                          ->where( '( '.$final_vm_search_query.' )  '.$vmcatlis_query.' AND vp.published = 1 ')
                          ->group('vp.virtuemart_product_id');
                        $db11->setQuery($query11,0,$result_limit); 
                        $db11->execute();
                        $num_rows = $db11->getNumRows();
                        $results11 = $db11->loadObjectList();  
                        if(JVERSION < 4 && $correct_language == 'el_gr'){
                        $results_501 = array();
                        if(count($query11)){
                            $new_row_501 = array();
                            foreach ($query11 as $article_501){
                                if (SearchHelper::checkNoHtml($article_501, $searchword, array('title', 'introtext', 'fulltext12'))){
                                    $new_row_501[] = $article_501;
                                }
                            }
                            $results_501 = array_merge($results_501, (array) $new_row_501);
                        }
                        } else {
                            $results_501 = $results11;
                        }
                        if(!empty($results_501)){
                            foreach($results_501 as $key => &$val){
                                $val->ajaxsearchtype = 'vm_product';
                            }
                        }
                    }
                }
                catch (RuntimeException $e){
                }
        }
        if($search_in_pc==1)
        {   
            $db->getQuery(true);
            $query = "SHOW CREATE TABLE #__phocacart_categories";
            $db->setQuery($query); 
                try                
                {  
                    // If it fails, it will throw a RuntimeException
                    $result = $db->loadResult(); 
                    $db13 = JFactory::getDbo();
                    $pccat_query = '';
                    if($pccatid != ''){
                        $pccat_query = 'AND ( pcc.category_id IN ('.$pccatid.') AND pccm.published = 1)';
                    }
                    $pc_search_query = [];
                    if(in_array("pc_title", $search_pc_fields)){
                        $pc_search_query[] = 'pc.title LIKE '. $db13->quote('%' . $searchword . '%');
                    }           
                    if(in_array("pc_sort_description", $search_pc_fields)){
                        $pc_search_query[] = 'pc.description LIKE '. $db13->quote('%' . $searchword . '%');
                    }
                    if(in_array("pc_long_description", $search_pc_fields)){
                        $pc_search_query[] = 'pc.description_long LIKE '. $db13->quote('%' . $searchword . '%');
                    }
                    if(in_array("pc_features", $search_pc_fields)){
                        $pc_search_query[] = 'pc.features LIKE '. $db13->quote('%' . $searchword . '%');
                    }
                    if(in_array("pc_sku", $search_pc_fields)){
                        $pc_search_query[] = 'pc.sku LIKE '. $db13->quote('%' . $searchword . '%');
                    }
                    $tag_query_p1 = '';
                    if(in_array("pc_tag", $search_pc_fields)){
                        $tag_query_p1 = ' AND pt.published = 1';
                        $pc_search_query[] = 'pt.title LIKE "%' . $searchword . '%"';
                    }
                    $tag_query_p2 = '';
                    if(in_array("pc_label", $search_pc_fields)){
                        $tag_query_p2 = ' AND ptl.published = 1';
                        $pc_search_query[] = 'ptl.title LIKE "%' . $searchword . '%"';
                    }
                    $final_pc_search_query = '';
                    $final_pc_search_query = join(" OR ",$pc_search_query);
                    $query13 = $db13->getQuery(true);
                    $query13
                      ->select('pc.id as id, pc.title as title, pc.description as introtext, pc.description_long as fulltext23, pc.features as features, pc.hits as hits, pc.price, pc.price_original, pc.created as created, pc.alias, pc.image as images, pc.sku, pccm.title as cattitle, pccm.alias as catalias, pcc.category_id as catid')
                      ->from($db13->quoteName('#__phocacart_products').'AS pc')
                      ->innerJoin($db13->quoteName('#__phocacart_product_categories').'AS pcc ON pc.id = pcc.product_id')
                      ->leftJoin($db13->quoteName('#__phocacart_categories').'AS pccm ON pcc.category_id = pccm.id')
                      ->leftJoin($db13->quoteName('#__phocacart_tags_related').'AS tr ON tr.item_id  = pc.id')
                      ->leftJoin($db13->quoteName('#__phocacart_taglabels_related').'AS tlr ON tlr.item_id  = pc.id')
                      ->join('LEFT', $db13->quoteName('#__phocacart_tags').'AS pt ON pt.id = tr.tag_id'.$tag_query_p1)
                      ->join('LEFT', $db13->quoteName('#__phocacart_tags').'AS ptl ON ptl.id = tlr.tag_id'.$tag_query_p2)
                      ->where( '( '. $final_pc_search_query .' )  '.$pccat_query.' AND pc.published = 1')
                      ->group('pc.id')
                      ->order('pc.id ASC');
                    $db13->setQuery($query13,0,$result_limit);            
                    $db13->execute();
                    $num_rows = $db13->getNumRows();
                    $results13 = $db13->loadObjectList();  
                    if(JVERSION < 4 && $correct_language == 'el_gr'){
                    $results_601 = array();
                    if(count($results13)){
                        $new_row_601 = array();
                        foreach ($results13 as $article_601){
                            if(in_array("pc_tag", $search_pc_fields)){
                                $db = JFactory::getDbo(); 
                                $query = $db->getQuery(true);
                                $query->select('pt.title')
                                    ->from('#__phocacart_tags_related as ptr')
                                    ->join('left', '#__phocacart_tags as pt on ptr.tag_id = pt.id')
                                    ->where('pt.published = 1')
                                    ->where('ptr.item_id = ' . $db->quote((int) $article_601->id));
                                $db->setQuery($query);
                                $article_601->tags = implode(',', $db->loadColumn());
                            }
                            if(in_array("pc_label", $search_pc_fields)){
                                $db = JFactory::getDbo(); 
                                $query = $db->getQuery(true);
                                $query->select('ppt.title')
                                    ->from('#__phocacart_taglabels_related as pptr')
                                    ->join('left', '#__phocacart_tags as ppt on pptr.tag_id = ppt.id')
                                    ->where('ppt.published = 1')
                                    ->where('pptr.item_id = ' . $db->quote((int) $article_601->id));
                                $db->setQuery($query);
                                $article_601->labels = implode(',', $db->loadColumn());
                            }
                            if (SearchHelper::checkNoHtml($article_601, $searchword, array('title', 'introtext', 'fulltext23', 'features','tags', 'labels'))){
                                $new_row_601[] = $article_601;
                            }
                        }
                        $results_601 = array_merge($results_601, (array) $new_row_601);
                    } 
                    } else {
                        $results_601 = $results13;
                    }
                    if(!empty($results_601)){
                        foreach($results_601 as $key => &$val){
                            $val->ajaxsearchtype = 'pc_items';
                        }
                    }
                } 
                catch (RuntimeException $e){
                }
        }
	
		if($search_in_dj==1){             
			
			$db->getQuery(true);

			$query = "SHOW CREATE TABLE #__djev_events";

			$db->setQuery($query);

				try                

				{

					
					$result = $db->loadResult(); 						
					$db_8 = JFactory::getDbo();
					$djcat_query = '';

					if($djcatid != ''){
						$djcat_query = 'AND ev.' . $db_8->quoteName('cat_id') . ' IN('.$djcatid.')';
					}

					$dj_search_query = [];
					if(in_array("dj_title", $search_dj_fields)){
						$dj_search_query[] = 'ev.title LIKE '. $db_8->quote('%' . $searchword . '%');
					}           

					if(in_array("dj_sort_description", $search_dj_fields)){
						$dj_search_query[] = 'ev.intro LIKE '. $db_8->quote('%' . $searchword . '%');
					}

					if(in_array("dj_description", $search_dj_fields)){
						$dj_search_query[] = 'ev.description LIKE '. $db_8->quote('%' . $searchword . '%');
					}

					$tag_query_p = '';
					if(in_array("dj_tag", $search_dj_fields)){
						$tag_query_p1 = ' AND ti.published = 1';
						$dj_search_query[] = 'ti.title LIKE "%' . $searchword . '%"';
					}

					$final_dj_search_query = '';
					$final_dj_search_query = join(" OR ",$dj_search_query);						

					if($by_ordering == 'newest'){
					   $order_by = 'ev.created DESC';
					} else if($by_ordering == 'oldest'){
					   $order_by = 'ev.created ASC';
					} else if($by_ordering == 'alpha'){
					   $order_by = 'ev.title ASC';
					} else {
					   $order_by = 'ev.created DESC';
					}

					$query8 = $db_8->getQuery(true);

					$query8
						->select('ev.id, ev.title, ev.alias, ev.cat_id as catid, ev.intro as introtext, ev.time, dem.image as poster, ev.description, ev.created, ev.published')
						->from($db_8->quoteName('#__djev_events').'AS ev')
						->leftJoin($db->quoteName('#__djev_jtags_xref').'AS etg ON etg.event_id  = ev.id')
						->join('LEFT', $db->quoteName('#__tags').'AS ti ON ti.id = etg.tag_id'.$tag_query_p)
						->leftJoin($db->quoteName('#__djev_events_media').'AS dem ON dem.event_id = ev.id AND dem.poster = 1')
						->where('('. $final_dj_search_query . ' ) '.$djcat_query . $tag_query_p1 . ' AND ' .'ev.published = 1 ')						
						->order($order_by);
					
					$db_8->setQuery($query8,0,$result_limit);            

					$db_8->execute();

					$num_rows = $db_8->getNumRows();

					$results14 = $db_8->loadObjectList();
					
					$results_701 = array();
					
					if(JVERSION < 4 && $correct_language == 'el_gr'){
					}else{
						$results_701 = $results14;
					}
					//print_r($results_701);
					if(!empty($results_701)){
						foreach($results_701 as $key => &$val){
							$val->ajaxsearchtype = 'dj_items';
						}
					}

					$rowCount = count($results_701);
					$unique_array = json_decode( json_encode($results_701), true);
					$unique_array = array_slice( $unique_array, $start, $perpage_limit );

				}
				catch (RuntimeException $e){
				}

		}
		
		if($search_in_docman==1){             
			
			$db->getQuery(true);

			$query = "SHOW CREATE TABLE #__docman_documents";

			$db->setQuery($query);

				try                

				{

					
					$result = $db->loadResult(); 						
						$db_9 = JFactory::getDbo();
						$docmancat_query = '';
						
						if($docmancatids != ''){
							$docmancat_query = 'AND dd.' . $db_9->quoteName('docman_category_id') . ' IN('.$docmancatids.')';
						}

						$dc_search_query = [];
						if(in_array("docman_title", $search_docman_fields)){
							$dc_search_query[] = 'dd.title LIKE '. $db_9->quote('%' . $searchword . '%');
						}           

						if(in_array("docman_description", $search_docman_fields)){
							$dc_search_query[] = 'dd.description LIKE '. $db_9->quote('%' . $searchword . '%');
						}
						
						$final_dc_search_query = '';
						$final_dc_search_query = join(" OR ",$dc_search_query);						
						
						if($by_ordering == 'newest'){
						   $order_by = 'dd.created_on DESC';
						} else if($by_ordering == 'oldest'){
						   $order_by = 'dd.created_on ASC';
						} else if($by_ordering == 'alpha'){
						   $order_by = 'dd.title ASC';
						} else {
						   $order_by = 'dd.created_on DESC';
						}

						$query9 = $db_9->getQuery(true);

						$query9
						->select('dd.docman_document_id as id, dd.title, dd.slug as alias, dc.slug as cat_slug, dd.docman_category_id as catid, dd.description as introtext, dd.image as poster, dd.created_on, dd.publish_on')
						->from($db_9->quoteName('#__docman_documents').'AS dd')
						->leftJoin($db->quoteName('#__docman_categories').'AS dc ON dc.docman_category_id = dd.docman_category_id')
						->where('('. $final_dc_search_query . ' ) '.$docmancat_query . ' AND ' .'dd.enabled = 1 ')					
						->order($order_by);
					
					$db_9->setQuery($query9,0,$result_limit);            

					$db_9->execute();

					$num_rows = $db_9->getNumRows();

					$results14 = $db_9->loadObjectList();
					
					$results_708 = array();
					
					if(JVERSION < 4 && $correct_language == 'el_gr'){
					}else{
						$results_708 = $results14;
					}
					//print_r($results_701);
					if(!empty($results_708)){
						foreach($results_708 as $key => &$val){
							$val->ajaxsearchtype = 'dc_items';
						}
					}

					$rowCount = count($results_708);
					$unique_array = json_decode( json_encode($results_708), true);
					$unique_array = array_slice( $unique_array, $start, $perpage_limit );

				}
				catch (RuntimeException $e){
				}

		}
	
	
    $merged_results = array_merge($results_101, $results_201, $results_301, $results_401, $results_501, $results_601, $results_701, $results_708);   
    $unique_array = modEbajaxsearchHelper::array_multi_unique($merged_results);
    $unique_array = json_decode( json_encode($unique_array), true);
    $unique_array = array_slice( $unique_array, 0, $result_limit );
    if($by_ordering == 'newest'){
        array_multisort(array_map(function($element) {
            return $element['created'];
        }, $unique_array), SORT_DESC, $unique_array);
    } else if($by_ordering == 'oldest'){
        array_multisort(array_map(function($element) {
            return $element['created'];
        }, $unique_array), SORT_ASC, $unique_array);
    } else if($by_ordering == 'popular'){
        array_multisort(array_map(function($element) {
            return $element['hits'];
        }, $unique_array), SORT_DESC, $unique_array);
    } else if($by_ordering == 'alpha'){
        array_multisort(array_map(function($element) {
            return $element['title'];
        }, $unique_array), SORT_ASC, $unique_array);
    } else {
        array_multisort(array_map(function($element) {
            return $element['created'];
        }, $unique_array), SORT_DESC, $unique_array);
    }
        // echo "<pre>"; print_r($unique_array);echo "</pre>";
    $start = !empty($ajax_data['page'])?$ajax_data['page']:0;
    $perpage_limit = !empty($ajax_data['perpage_limit'])?$ajax_data['perpage_limit']:5;
    $redirect_search_url = !empty($ajax_data['redirect_search_url'])?$ajax_data['redirect_search_url']:0;
    $rowCount = count($unique_array);
    $pagConfig = array(
        'currentPage' => $start,
        'totalRows' => $rowCount,
        'perPage' => $perpage_limit,
        'redirect_url' => $redirect_search_url,
        'link_func' => 'searchFilter_'.$module_idd
    );
    $pagination =  new Pagination($pagConfig);
    $unique_array = array_slice( $unique_array, $start, $perpage_limit );
}
        // Get output
    $output = '';
    if($searchword != ''){
        if($unique_array){
            $count = 0;
            $output .= '<div class="result_wrap">';
            $output .= '<div class="pagination_wrap">'.$pagination->createLinks().'</div>';
            foreach($unique_array as $result){
                $itemurl = '';
                $cattitle = '';
                $image_intro_img = '';
                $result_type_img = '';
                $image_pro_img = '';
                $article_class = '';
				$product_price = '';
                if($result['ajaxsearchtype'] == 'joomla_article'){
                    $article_text = $result['introtext'];
                    $art_img = isset($result['images'])?$result['images']:'';
                    $allvalue = array();
                    if($art_img!=''){
                        $allvalue = json_decode($result['images']);
                    }
                    $image_intro_img = isset($allvalue->image_intro)?$allvalue->image_intro:'';
                    $image_intro_img_url = '';
                    if($image_intro_img!=''){
                        $arrParsedUrl = parse_url($image_intro_img);
                        if (!empty($arrParsedUrl['scheme']))
                        {
                            // Contains http:// schema
                            if ($arrParsedUrl['scheme'] === "http")
                            {
                                $image_intro_img_url = $image_intro_img;
                            }
                            // Contains https:// schema
                            else if ($arrParsedUrl['scheme'] === "https")
                            {
                                $image_intro_img_url = $image_intro_img;
                            }
                        }
                        // Don't contains http:// or https://
                        else
                        {   
                             $image_intro_img_url = JUri::base().$allvalue->image_intro;
                        }
                    }
                    $itemurl = ContentHelperRoute::getArticleRoute($result['id'],  $result['catid']);
                    $cattitle = modEbajaxsearchHelper::getCategoryName($result['id']);
                    if($image_intro_img != '' && $show_image != 0){
                        $article_class = 'result-products';
                    } else {
                        $article_class = 'desc_fullwidth';
                    }
                }else if($result['ajaxsearchtype'] == 'k2_items'){
                    $article_text = $result['introtext'];
                    $result_type_img = isset($result['type'])?$result['type']:'';
                    $k2itempath=JUri::base().'media/k2/items/cache/'.md5("Image".$result['id'])."_M.jpg";
                    require_once JPATH_SITE.'/components/com_k2/helpers/route.php';
                    $itemurl  = K2HelperRoute::getItemRoute($result['id'].':'.$result['alias'], $result['catid']);
                    $cattitle = modEbajaxsearchHelper::getK2CategoryName($result['catid']);
                    if($result_type_img!='' && $show_image != 0 && @getimagesize($k2itempath) != ''){
                        $article_class = 'result-products';
                    } else {
                        $article_class = 'desc_fullwidth';
                    }
                } else if($result['ajaxsearchtype'] == 'hikashop_product'){
                    $db = JFactory::getDbo();
                    $query_hk = $db->getQuery(true)
                            ->select('*')
                            ->from($db->qn('#__hikashop_config'))
                            ->where($db->qn('config_namekey').' = "uploadfolder"');
                    $query_hk->setLimit(1);
                    $db->setQuery($query_hk);
                    $db->execute();
                    $num_rows = $db->getNumRows();
                    $queryr_hk = $db->loadObjectList();
                    $uploadfolder_path = '';
                    if(!empty($queryr_hk)){
                        $uploadfolder_path = $queryr_hk[0]->config_value;
                    } else {
                        $uploadfolder_path = 'images/com_hikashop/upload/';
                    }
                    $article_text = $result['introtext'];
					if($hikashop_shop_price==1){
						$product_price = $result['product_msrp'];
						$product_price = JText::_('MOD_SEARCHAJAX_SEARCH_LIST_PRICE_TEXT').$hk_currency_sym.number_format((float)$product_price, 2, '.', '');
					}else{
						$product_price = '';
					}
                    $product_image = '';
                    $image_pro_img = isset($result['images'])?$result['images']:'';
                    $image_pro_img_url = '';
                    if($image_pro_img!=''){
                        $arrParsedUrl = parse_url($image_pro_img);
                        if (!empty($arrParsedUrl['scheme']))
                        {
                            // Contains http:// schema
                            if ($arrParsedUrl['scheme'] === "http")
                            {
                                $image_pro_img_url = $image_pro_img;
                            }
                            // Contains https:// schema
                            else if ($arrParsedUrl['scheme'] === "https")
                            {
                                $image_pro_img_url = $image_pro_img;
                            }
                        }
                        // Don't contains http:// or https://
                        else
                        {   
                            $image_pro_img_url = JUri::base().$uploadfolder_path.$result['images'];
                        }
                    }
                    require_once JPATH_SITE.'/components/com_hikashop/helpers/route.php';
                    $itemurl = hikashopTagRouteHelper::getProductRoute($result['id'].':'.$result['product_alias'],$result['catid'], '');
                    $cattitle_pro = isset($result['category_name'])?$result['category_name']:'';
                    $cattitle = ucfirst($cattitle_pro);
                    if($image_pro_img != '' && $show_image != 0){
                        $article_class = 'result-products';
                    } else {
                        $article_class = 'desc_fullwidth';
                    }
                } else if($result['ajaxsearchtype'] == 'sppage'){
					$db_sp = JFactory::getDbo();
					$query_sp = $db_sp->getQuery(true); 
					$query_sp
					->select('manifest_cache')    
					->from($db_sp->quoteName('#__extensions'))
					->where('element = "com_sppagebuilder"');
					$db_sp->setQuery($query_sp); 
					$db_sp->execute();
					$results_sp = $db_sp->loadObjectList();
					$obj_ver_sp = json_decode($results_sp[0]->manifest_cache);
					/**Check for version of SP**/	
					if($obj_ver_sp->version >= '5.0.0'){
						
						require_once JPATH_ROOT . '/administrator/components/com_sppagebuilder/editor/helpers/ApplicationHelper.php';						
						require_once (JPATH_SITE.'/components/com_sppagebuilder/parser/addon-parser.php');						
						require_once JPATH_ROOT . '/components/com_sppagebuilder/builder/classes/addon.php';
						require_once JPATH_SITE.'/components/com_sppagebuilder/helpers/route.php';
						
						$page_text = SpPageBuilderAddonHelper::__($result['introtext']);
						$content = json_decode($page_text);
						$content_marge=''; 
						foreach($content as $content_item){
							$columan = $content_item->columns;						
							foreach($columan as $columan_item){
								if(!empty($columan_item->addons)){
									foreach($columan_item->addons as $addons_item){
										$text_ob = $addons_item->settings->text;
										$content_marge.=$text_ob;
									}
								}
							} 
						}
						$pageName = 'page-'.$result['id']; 
						$article_text_tag = AddonParser::viewAddons( $content_marge, 0, $pageName );
						$article_text = strip_tags($article_text_tag);
						
					}elseif($obj_ver_sp->version >= '4.0.0'){
						require_once (JPATH_SITE.'/components/com_sppagebuilder/builder/classes/addon.php');
						require_once JPATH_SITE.'/components/com_sppagebuilder/helpers/route.php';
						$page_text = SpPageBuilderAddonHelper::__($result['introtext']);
						$content = json_decode($page_text);
						$content_marge=''; 
						foreach($content as $content_item){
							$columan = $content_item->columns;						
							foreach($columan as $columan_item){
								if(!empty($columan_item->addons)){
									foreach($columan_item->addons as $addons_item){
										if(isset($addons_item->settings->text)){
											$text_ob = $addons_item->settings->text;
											$content_marge.=$text_ob;
										}
									}
								}
							} 
						}
						$pageName = 'page-'.$result['id']; 
						$article_text_tag = AddonParser::viewAddons( $content_marge, 0, $pageName );
						$article_text = strip_tags($article_text_tag);
					}else{
						require_once (JPATH_ADMINISTRATOR.'/components/com_sppagebuilder/builder/classes/addon.php');
						require_once JPATH_SITE.'/components/com_sppagebuilder/helpers/route.php';
						$page_text = SpPageBuilderAddonHelper::__($result['introtext']);
						$content = json_decode($page_text);
						$pageName = 'page-'.$result['id']; 
						$article_text_tag = AddonParser::viewAddons( $content, 0, $pageName );
						$article_text = strip_tags($article_text_tag);
					}
					
                    $itemurl = SppagebuilderHelperRoute::getPageRoute($result['id'], $result['language']);
                    $spcattitle = modEbajaxsearchHelper::getSPCategoryName($result['catid']);
                    $cattitle = '';
                    if(!empty($spcattitle)){
                        $cattitle = $spcattitle->title;
                    }
                    // preg_match( '@src="([^"]+)"@' , $article_text_tag, $matches );
                    preg_match( "~<img.*src\s*=\s*[\"']([^\"']+)[\"'][^>]*>~i" , $article_text_tag, $matches );
                    $image_intro_img = isset($matches[1])?$matches[1]:'';
                    if($image_intro_img!=''){
                        $image_intro_img_url = $matches[1];
                    }
                    if($image_intro_img != '' && $show_image != 0){
                        $article_class = 'result-products';
                    } else {
                        $article_class = 'desc_fullwidth';
                    }
                } else if($result['ajaxsearchtype'] == 'vm_product'){
                    $article_text = strip_tags($result['fulltext12']);
                    $image_intro_img_url = '';
                    $image_intro_img = isset($result['images'])?$result['images']:'';
					if($search_in_vm_show_price==1){
						
						$displayCurrency = $result['currency_symbol'];
						
						$product_price = $result['product_price'];
						$product_price = JText::_('MOD_SEARCHAJAX_SEARCH_LIST_PRICE_TEXT').$displayCurrency.number_format((float)$product_price, 2, '.', '');
					}else{
						$product_price = '';
					}
                    if($image_intro_img!=''){
                        $arrParsedUrl = parse_url($image_intro_img);
                        if (!empty($arrParsedUrl['scheme']))
                        {
                            // Contains http:// schema
                            if ($arrParsedUrl['scheme'] === "http")
                            {
                                $image_intro_img_url = $image_intro_img;
                            }
                            // Contains https:// schema
                            else if ($arrParsedUrl['scheme'] === "https")
                            {
                                $image_intro_img_url = $image_intro_img;
                            }
                        }
                        // Don't contains http:// or https://
                        else
                        {   
                             $image_intro_img_url = JUri::base().$image_intro_img;
                        }
                    }
                    $itemurl = JRoute::_('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id='.$result['id'].'&virtuemart_category_id='.$result['catid']);
                    $cattitle = $result['category_name'];
                    if($image_intro_img != '' && $show_image != 0){
                        $article_class = 'result-products';
                    } else {
                        $article_class = 'desc_fullwidth';
                    }
                } else if($result['ajaxsearchtype'] == 'pc_items'){
                    JLoader::registerPrefix('Phocacart', JPATH_ADMINISTRATOR . '/components/com_phocacart/libraries/phocacart');
                    $introtext = strip_tags($result['introtext']);
                    $fulltext23 = strip_tags($result['fulltext23']);
                    $features = strip_tags($result['features']);
                    $article_text = '';
                    if(in_array("pc_sort_description", $search_pc_fields)){
                        $article_text = strip_tags($result['introtext']);
                    } else if(in_array("pc_long_description", $search_pc_fields)){
                        $article_text = strip_tags($result['fulltext23']);
                    } else if(in_array("pc_features", $search_pc_fields)){
                        $article_text = strip_tags($result['features']);
                    }
                    if($article_text == ''){
                        $article_text = ($introtext)?$introtext:$fulltext23;
                    }   
                    // $article_text = strip_tags($result['introtext']);
                    $image_intro_img_url = '';
                    $image_intro_img = isset($result['images'])?$result['images']:'';
                    if($image_intro_img!=''){
                        $arrParsedUrl = parse_url($image_intro_img);
                        if (!empty($arrParsedUrl['scheme']))
                        {
                            // Contains http:// schema
                            if ($arrParsedUrl['scheme'] === "http")
                            {
                                $image_intro_img_url = $image_intro_img;
                            }
                            // Contains https:// schema
                            else if ($arrParsedUrl['scheme'] === "https")
                            {
                                $image_intro_img_url = $image_intro_img;
                            }
                        }
                        // Don't contains http:// or https://
                        else
                        {   
                            // $image_intro_img_url = JUri::base().'images/phocacartproducts/thumbs/phoca_thumb_l_'.$image_intro_img;
                            $abs_path = dirname(__FILE__,3);
                            $org_abs_path = $abs_path."/images/phocacartproducts/";
                            $pathitem1 = array("orig_abs_ds"=> $org_abs_path, "orig_rel"=>"images/phocacartproducts", "orig_rel_ds"=>"images/phocacartproducts/");
                            $image_small_img = PhocacartImage::getThumbnailName($pathitem1, $image_intro_img, 'small');
                            $image_intro_img_url =  $image_small_img->rel;
                        }
                    }
                    $itemurl = "";
                    if(JVERSION < 4){
                        $itemurl = JRoute::_(PhocacartRoute::getItemRoute((int)$result['id'], (int)$result['catid']));
                    } else {
                        $itemurl = JRoute::_(PhocacartRoute::getItemRoute($result['id'], $result['catid'], $result['alias'], $result['catalias']));
                    }
                    $cattitle = $result['cattitle'];
                    if($image_intro_img != '' && $show_image != 0){
                        $article_class = 'result-products';
                    } else {
                        $article_class = 'desc_fullwidth';
                    }
                } else if($result['ajaxsearchtype'] == 'dj_items'){
					
					$introtext = strip_tags($result['introtext']);
                    $fulltext23 = strip_tags($result['description']);
                    
					$alias = $result['alias'];
					$id = $result['id'];
					$allvalue = json_decode($result['time']); 
					$url_time = $allvalue->start;
					
					$itemurl = JRoute::_('index.php?option=com_djevents&view=event&day='.$url_time.'&id='.$id.':'.$alias);
					//$itemurl = str_replace("component/djevents/","",$item_url);
					 
					$image_intro_img = $result['poster'];
					
                    $article_text = '';
					
                    if(in_array("dj_sort_description", $search_dj_fields)){
                        $article_text = strip_tags($result['introtext']);
                    } else if(in_array("dj_description", $search_dj_fields)){
                        $article_text = strip_tags($result['fulltext23']);
                    }
					
                    if($article_text == ''){
                        $article_text = ($introtext)?$introtext:$fulltext23;
                    } 
					
					if($image_intro_img!=''){
                        $arrParsedUrl = parse_url($image_intro_img);
                        if (!empty($arrParsedUrl['scheme']))
                        {
                            // Contains http:// schema
                            if ($arrParsedUrl['scheme'] === "http")
                            {
                                $image_intro_img_url = $image_intro_img;
                            }
                            // Contains https:// schema
                            else if ($arrParsedUrl['scheme'] === "https")
                            {
                                $image_intro_img_url = $image_intro_img;
                            }
                        }
                        // Don't contains http:// or https://
                        else
                        {   
                             $image_intro_img_url = JUri::base().$image_intro_img;
                        }
                    }
					
					if($image_intro_img != '' && $show_image != 0){
                        $article_class = 'result-products';
                    } else {
                        $article_class = 'desc_fullwidth';
                    }
				} else if($result['ajaxsearchtype'] == 'dc_items'){
					
					
                    $fulltext23 = strip_tags($result['introtext']);
                    
					$alias = $result['alias'];
					$cat_slug = $result['cat_slug'];
					$id = $result['id'];
					
					
					$itemurl = JRoute::_('index.php?option=com_docman&view=document&slug='.$alias.'&Itemid='.$docmanmenu_id);
					
					$image_intro_img = 'joomlatools-files/docman-images/'.$result['poster'];
					
                    $article_text = '';
					
                   
                   $article_text = $fulltext23;
                    
					
					if($image_intro_img!=''){
                        $arrParsedUrl = parse_url($image_intro_img);
                        if (!empty($arrParsedUrl['scheme']))
                        {
                            // Contains http:// schema
                            if ($arrParsedUrl['scheme'] === "http")
                            {
                                $image_intro_img_url = $image_intro_img;
                            }
                            // Contains https:// schema
                            else if ($arrParsedUrl['scheme'] === "https")
                            {
                                $image_intro_img_url = $image_intro_img;
                            }
                        }
                        // Don't contains http:// or https://
                        else
                        {   
                             $image_intro_img_url = JUri::base().$image_intro_img;
                        }
                    }
					
					if($image_intro_img != '' && $show_image != 0){
                        $article_class = 'result-products';
                    } else {
                        $article_class = 'desc_fullwidth';
                    }
				}
				
				
                $article_title = $result['title'];
                $alt_title = $result['title'];
                $highlighted_text = "<strong style='font-weight:bold;background-color:#ff0'>$searchword</strong>";
                $article_title1 = str_ireplace($searchword, $highlighted_text, $article_title);
                $article_title = modEbajaxsearchHelper::highlight_articlecontent($article_title, $searchword);
                $cattitle1 = str_ireplace($searchword, $highlighted_text, $cattitle);
                $limit = $description_limit;
                $article_content_text = strip_tags($article_text);
                if (strlen($article_content_text) > $limit) {
                    $article_content =  (substr($article_content_text, 0, $limit)).' ...';
                } else {
                    $article_content =  $article_content_text;
                }
                $article_content1 = str_ireplace($searchword, $highlighted_text, $article_content);
                $at_specialchars = mb_convert_encoding($article_content, 'UTF-8', mb_detect_encoding($article_content));
                $article_content = str_replace('?', ' ', $at_specialchars);
                $article_content = modEbajaxsearchHelper::highlight_articlecontent($article_content, $searchword);
                $output .= '<div class="result_box">';
                $output .= '<a class="result-element '.$article_class.'" href="'. $itemurl.'" target="">';
                if($show_image == 1){
                    if($result['ajaxsearchtype'] == 'joomla_article'){
                        if($image_intro_img != ''){
                            $output .= '<div class="result_img"><img alt="'.$alt_title.'" src="'.$image_intro_img_url.'"></div>';
                        }
                    }else if($result['ajaxsearchtype'] == 'k2_items'){
                        if($result_type_img!=''){
                            if(@getimagesize($k2itempath) != ''){
                                $output .= '<div class="result_img"><img alt="'.$result['title'].'" src="'.$k2itempath.'"></div>';
                            }                                                                                       
                        }
                    } else if($result['ajaxsearchtype'] == 'hikashop_product'){
                        if($image_pro_img != ''){
                            $output .= '<div class="result_img"><img alt="'.$alt_title.'" src="'.$image_pro_img_url.'"></div>';
                        }
                    } else if($result['ajaxsearchtype'] == 'sppage'){
                        if($image_intro_img != ''){
                            $output .= '<div class="result_img"><img alt="'.$alt_title.'" src="'.$image_intro_img_url.'"></div>';
                        }
                    } else if($result['ajaxsearchtype'] == 'vm_product'){
                        if($image_intro_img != ''){
                            $output .= '<div class="result_img"><img alt="'.$alt_title.'" src="'.$image_intro_img_url.'"></div>';
                        }
                    } else if($result['ajaxsearchtype'] == 'pc_items'){
                        if($image_intro_img != ''){
                            $output .= '<div class="result_img"><img alt="'.$alt_title.'" src="'.$image_intro_img_url.'"></div>';
                        }
                    } else if($result['ajaxsearchtype'] == 'dj_items'){
                        if($image_intro_img != ''){
                            $output .= '<div class="result_img"><img alt="" src="'.$image_intro_img_url.'"></div>';
                        }
                    }else if($result['ajaxsearchtype'] == 'dc_items'){
                        if($image_intro_img != ''){
                            $output .= '<div class="result_img DDDD"><img alt="" src="'.$image_intro_img_url.'"></div>';
                        }
                    }
					
                }
                if($show_title == 1 && $show_category == 1){
                    $output .= '<div class="result_content"><span class="small-title">'.$article_title.$product_price.'</span>';
                    if($cattitle!=''){
                        $output .= '<span class="small-cat">'.$cattitle.'</span>';
                    }
                    $output .= '</div>';
                } else if($show_title == 1){
                    $output .= '<div class="result_content"><span class="small-title">'.$article_title.$product_price.'</span></div>';
                } else if($show_category == 1){
                    $output .= '<div class="result_content"><span class="small-cat">'.$cattitle.'</span></div>';
                }else{
					if($product_price != ''){
						$output .= '<div class="result_content"><span class="small-title">'.$product_price.'</span></div>';
					}
				}
                if($show_description == 1){
                    $output .= '<span class="small-desc">'.$article_content.'</span>';
                }
                $output .= '<div class="ajax-clear"></div></a></div>';
                $count++; }
                $output .= '</div>';
            } else {
                // $output .= '<div class="result_wrap"><div class="is_noresult">Sorry! No results for your search.</div></div>';
                $output .= '<div class="result_wrap"><div class="is_noresult">'.JText::_('MOD_SEARCHAJAX_NO_RESULT_TEXT').'</div></div>';
            }
        } 
        return $output;
    }
    public static function getCategoryName($articleId)
    {
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);
        $query->select('c.title');
        $query->from('#__categories AS c');
        $query->join("INNER","#__content AS a ON c.id = a.catid");
        $query->where("a.id = '$articleId'");
        $db->setQuery($query);
        $row = $db->loadObject();
        return $row->title;
    }
    public static function getK2CategoryName($articleId)
    {
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);
        $query->select('name');
        $query->from('#__k2_categories');        
        $query->where("id = '$articleId'");
        $db->setQuery($query);
        $row = $db->loadObject();
        return $row->name;
    }
    public static function getSPCategoryName($catid)
    {
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);
        $query->select('title');
        $query->from('#__categories');
        $query->where("id = '$catid'");
        $db->setQuery($query);
        $row = $db->loadObject();
        return $row;
    }
    public static function array_multi_unique($multiArray){
        $uniqueArray = array();
        foreach($multiArray as $subArray){
            if(!in_array($subArray, $uniqueArray)){
                $uniqueArray[] = $subArray;
            }
        }
        return $uniqueArray;
    }
    public static function mbStringToArray($string){
        $strlen = mb_strlen($string);
        while($strlen)
        {
            $array[] = mb_substr($string, 0, 1, "UTF-8");
            $string = mb_substr($string, 1, $strlen, "UTF-8");
            $strlen = mb_strlen($string);
        }
        return $array;
    }
    public static function stripAccents($stripAccents){
        return mb_convert_encoding(strtr(mb_convert_encoding($stripAccents, 'ISO-8859-1', 'UTF-8'),mb_convert_encoding('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ', 'ISO-8859-1', 'UTF-8'),'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY'), 'UTF-8', 'ISO-8859-1');
    }
    public static function findRelatedAccents($char){
        $keyMatchArray = array (
            'a' => array ( 'à','á','â','ã','ä' ),
            'c' => array ( 'ç' ),
            'e' => array ( 'è','é','ê','ë' ),
            'i' => array ( 'ì','í','î','ï' ),
            'n' => array ( 'ñ' ),
            'o' => array ( 'ò','ó','ô','õ','ö' ),
            'u' => array ( 'ù','ú','û','ü' ),
            'y' => array ( 'ý','ÿ' ),
            'A' => array ( 'À','Á','Â','Ã','Ä' ),
            'C' => array ( 'Ç' ),
            'E' => array ( 'È','É','Ê','Ë' ),
            'I' => array ( 'Ì','Í','Î','Ï' ),
            'N' => array ( 'Ñ' ),
            'O' => array ( 'Ò','Ó','Ô','Õ','Ö' ),
            'U' => array ( 'Ù','Ú','Û','Ü' ),
            'Y' => array ( 'Ý' ),
        );
        $Matchedkey = isset($keyMatchArray[$char])?$keyMatchArray[$char]:$char;
        return $Matchedkey;
    }
    public static function highlight_articlecontent($text, $searchword){
        $searchwordNoAccent = modEbajaxsearchHelper::stripAccents($searchword);
        $searchwordArray = modEbajaxsearchHelper::mbStringToArray($searchword);
        foreach($searchwordArray as $pos => &$char){
            $charNA =$searchwordNoAccent[$pos];
            if($char != $charNA){
                $char = "(?:$char|$charNA|$charNA\p{M})";
            } else {
                $Matchedkey = modEbajaxsearchHelper::findRelatedAccents($char);
                if(is_array($Matchedkey)){
                    $Matchedkey_val = implode('|', $Matchedkey);
                    $char = "(?:$Matchedkey_val|$char|$char\p{M})";
                } else {
                    $Matchedkey_val = $Matchedkey;
                    $char = $Matchedkey_val;
                }
            }
        }
        $articleSearchPattern = implode($searchwordArray); // c(?:è|é|ê|ë|e|e\p{M})r(?:à|á|â|ã|ä|a|a\p{M})
	   /* Condiction for Bulgarian language*/		
	   $languagevar_wt = JFactory::getLanguage()->getTag();
		if($languagevar_wt == 'bg-BG' || $languagevar_wt == 'sl-SI' || $languagevar_wt == 'de-DE' || $languagevar_wt == 'el-GR' || $languagevar_wt == 'pl-PL' || $languagevar_wt == 'hr-HR'){ 
			//$search = preg_replace("/\p{L}*?".preg_quote($searchword)."\p{L}*/ui", '<mark class="highlight">$0</mark>', $text); 
		}else{
			//$search = preg_replace('/(.*?)(' . $articleSearchPattern . ')(.*?)/iu', '$1<mark class="highlight">$2</mark>$3', $text);
		}
		$search = preg_replace("/\p{L}*?".preg_quote($searchword)."\p{L}*/ui", '<mark class="highlight">$0</mark>', $text); 
        return $search;
    }
}