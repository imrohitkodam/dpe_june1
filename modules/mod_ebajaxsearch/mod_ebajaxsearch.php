<?php 

/**

 * @package Module EB Ajax Search for Joomla!
 
 * @version 1.52: mod_ebajaxsearch.php Dec 2023

 * @author url: https://www/extnbakers.com

 * @copyright Copyright (C) 2022 extnbakers.com. All rights reserved.

 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 

**/

defined('_JEXEC') or die;

include_once __DIR__ . '/helper.php';

$search_in_article= $params->get('search_in_article');

$catids           = $params->get('catid');



$search_in_k2     = $params->get('search_in_k2');

$k2catid          = $params->get('k2catid');

if(JVERSION >= 4){
    $joom_ver = 4;
} else {
    $joom_ver = 3;
}

$search_in_hikashop     = $params->get('search_in_hikashop');
$hikashop_shop_price     = $params->get('hikashop_shop_price');

$hikashopcatid          = $params->get('hikashopcatid');


$search_in_sppage = $params->get('search_in_sppage', 0);

$spcatid           = $params->get('spcatid');


$search_in_vm     = $params->get('search_in_vm');
$search_in_vm_show_price     = $params->get('search_in_vm_show_price');

$vmcatid          = $params->get('vmcatid');
$search_in_pc     = $params->get('search_in_pc');
$pccatid          = $params->get('pccatid');

$search_in_dj    = $params->get('search_in_dj');
$djcatid         = $params->get('djcatid');

$search_in_docman = $params->get('search_in_docman');
$docmancatid = $params->get('docmancatid');
$docmanmenu = $params->get('docmanmenu');

$by_ordering      = $params->get('by_ordering', 'newest');

$show_title       = $params->get('show_title', 1);

$show_category    = $params->get('show_category', 1);

$show_description = $params->get('show_description', 1);

$description_limit= $params->get('description_limit', 100);

$show_image       = $params->get('show_image', 1);

$result_limit     = $params->get('result_limit', 25);

$perpage_limit    = $params->get('perpage_limit', 5);

$redirect_search_url = $params->get('redirect_search_url', 0);

$trigger_search_characters = $params->get('trigger_search_characters', 3);





$search_article_fields = $params->get('search_article_fields');

if($search_article_fields != ''){

    $search_article_fields = json_encode($params->get('search_article_fields'));

}



$search_k2_fields = $params->get('search_k2_fields');

if($search_k2_fields != ''){

    $search_k2_fields = json_encode($params->get('search_k2_fields'));

}



$search_hs_fields = $params->get('search_hs_fields');

if($search_hs_fields != ''){

    $search_hs_fields = json_encode($params->get('search_hs_fields'));

}



$search_sp_fields = $params->get('search_sp_fields');

if($search_sp_fields != ''){

    $search_sp_fields = json_encode($params->get('search_sp_fields'));

}



$search_vm_fields = $params->get('search_vm_fields');

if($search_vm_fields != ''){

    $search_vm_fields = json_encode($params->get('search_vm_fields'));

}



$search_pc_fields = $params->get('search_pc_fields');

if($search_pc_fields != ''){

    $search_pc_fields = json_encode($params->get('search_pc_fields'));

}

$search_dj_fields = $params->get('search_dj_fields');
if($search_dj_fields != ''){    
	$search_dj_fields = json_encode($params->get('search_dj_fields'));
}

$search_docman_fields1 = '';
//$search_docman_fields = $params->get('search_docman_fields'); 
$search_docman_fields1 = json_encode($params->get('search_docman_fields'));

if(empty($search_docman_fields1)){
$search_docman_fields1 = '["docman_title"] ';
//echo $search_docman_fields1;
}

$djcatids = '';
if(isset($djcatid)){    
	if(count($djcatid)>0){        
		$djcatids = implode(',' , $djcatid);    
	}
}
 
$docmancatids = '';
if(isset($docmancatid)){    
	if(count($docmancatid)>0){        
		$docmancatids = implode(',' , $docmancatid);    
	}
} 
 
$catidss = '';

if(isset($catids)){

    if(count($catids)>0){

        $catidss = implode(',' , $catids);

    }

}

$vmcatids = '';

if(isset($vmcatid)!=''){

    if(count($vmcatid)>0){

      $vmcatids = implode(',' , $vmcatid);

    }

}

$k2catids = '';

if(isset($k2catid)!=''){

    if(count($k2catid)>0){

      $k2catids = implode(',' , $k2catid);

    }

}

$hikashopcatids = '';

if(isset($hikashopcatid)!=''){

    if(count($hikashopcatid)>0){

      $hikashopcatids = implode(',' , $hikashopcatid);

    }

}



$spcatids = '';

if(isset($spcatid)!=''){

    if(count($spcatid)>0){

      $spcatids = implode(',' , $spcatid);

    }

}

$pccatids = '';

if(isset($pccatid)!=''){

    if(count($pccatid)>0){

      $pccatids = implode(',' , $pccatid);

    }

}



$button       = $params->get('button', 0);

$module_id = $module->id;

$base_url = JURI::base();

// $max_results  = (int) $params->get('max_results', 5);



$doc = JFactory::getDocument();

$js='';



$js .= <<<JS

    function searchFilter_$module_id(page_num){

        var page_num = page_num?page_num:0;

        //console.log('page_num');

        var div_id = jQuery('#mod-ajaxsearch-searchword_$module_id');

        //jQuery(this)[tog(this.value)]('x');

        var value = jQuery('#mod-ajaxsearch-searchword_$module_id').val();


		var jooml_ver = $joom_ver;
		var value_parm_cst = jQuery('#ajaxsearch_$module_id .search_class').attr("data-url");
		
		if(jooml_ver == 4){			
			jQuery('#ajaxsearch_$module_id .search_class').attr("href", value_parm_cst + '&q=' + value);					
		}else{
			jQuery('#ajaxsearch_$module_id .search_class').attr("href", value_parm_cst + '?searchword=' + value + '&q=' + value + '&ordering=newest&searchphrase=all');
		}	
		

        // var value   = jQuery(this).val();

        // console.log(value);
		
		jQuery('#mod-ajaxsearch-searchword_$module_id').removeClass('x');
        jQuery('#mod-ajaxsearch-searchword_$module_id').addClass('loading');



        if(value.length > $trigger_search_characters){ 

            request = {

                    'option' : 'com_ajax',

                    'module' : 'ebajaxsearch',

                    'data'   : { module_idd: $module_id, search_in_article:"$search_in_article", keyword: value, order: "$by_ordering", title: "$show_title", show_category: "$show_category", description: "$show_description", description_limit: "$description_limit", image: "$show_image", catids: "$catidss", search_in_vm: "$search_in_vm", search_in_vm_show_price: "$search_in_vm_show_price", vmcatid: "$vmcatids", search_in_k2: "$search_in_k2", k2catid: "$k2catids", search_in_hikashop: "$search_in_hikashop", hikashop_shop_price: "$hikashop_shop_price", hikashopcatid: "$hikashopcatids", search_in_sppage: "$search_in_sppage", spcatid: "$spcatids", search_in_pc: "$search_in_pc", pccatid: "$pccatids", djcatid: "$djcatids", search_in_dj: "$search_in_dj", docmancatids: "$docmancatids", docmanmenu: "$docmanmenu", search_in_docman: "$search_in_docman", page: page_num, result_limit: $result_limit, search_article_fields: $search_article_fields, search_k2_fields: $search_k2_fields, search_hs_fields: $search_hs_fields, search_sp_fields: $search_sp_fields, search_vm_fields: $search_vm_fields, search_pc_fields: $search_pc_fields, search_dj_fields: $search_dj_fields, search_docman_fields: $search_docman_fields1, perpage_limit: $perpage_limit,  redirect_search_url: $redirect_search_url},

                    'format' : 'raw'

                };

            jQuery.ajax({

				url   : '$base_url',

                type   : 'POST',

                data   : request,

                success: function (response) {


				  jQuery('#mod-ajaxsearch-searchword_$module_id').addClass('x');
                  jQuery('#mod-ajaxsearch-searchword_$module_id').removeClass('loading');

                  var data_response = replaceNbsps(response);

                  jQuery('.is_ajaxsearch_result_$module_id').html(data_response);

                  // jQuery('.is_ajaxsearch_result_$module_id').ebajaxsearchhighlight( value );

                }

            });

            return false;

        } else {

            jQuery('.is_ajaxsearch_result_$module_id .result_wrap').hide();

			jQuery('#mod-ajaxsearch-searchword_$module_id').removeClass('loading');

        }

    }

JS;

/* } */

$js .= <<<JS



jQuery(document).on("click", '.eb_viewall', function(){

    //limit.value = '';

    document.getElementById('mod-ajaxsearch-form-$module_id').submit();

});



function tog(v){return v?'addClass':'removeClass';} 

	jQuery(document).on('input', '.clearable', function(){

    //jQuery(this)[tog(this.value)]('x');

    }).on('mousemove', '.x', function( e ){

        jQuery(this)[tog(this.offsetWidth-18 < e.clientX-this.getBoundingClientRect().left)]('onX');   

    }).on('click', '.onX', function( ev ){

            ev.preventDefault();

            var form_id = jQuery(this).closest('form').attr('id');

            var div_id = jQuery("#"+form_id).parent('div').attr('id');

            jQuery('#'+div_id+' .is_ajaxsearch_result_$module_id .result_wrap').hide();

            jQuery(this).removeClass('x onX').val('').change();

            var value   = jQuery(this).val();

            request = {

                'option' : 'com_ajax',

                'module' : 'ebajaxsearch',

                'data'   : { module_idd: $module_id, search_in_article:"$search_in_article", keyword: value, order: "$by_ordering", title: "$show_title", show_category: "$show_category", description: "$show_description", description_limit: "$description_limit", image: "$show_image", catids: "$catidss", search_in_vm: "$search_in_vm", search_in_vm_show_price: "$search_in_vm_show_price", vmcatid: "$vmcatids", search_in_k2: "$search_in_k2", k2catid: "$k2catids", search_in_hikashop: "$search_in_hikashop", hikashop_shop_price: "$hikashop_shop_price", hikashopcatid: "$hikashopcatids", search_in_sppage: "$search_in_sppage", spcatid: "$spcatids", search_in_pc: "$search_in_pc", pccatid: "$pccatids",  djcatid: "$djcatids", search_in_dj: "$search_in_dj", docmancatids: "$docmancatids", docmanmenu: "$docmanmenu", search_in_docman: "$search_in_docman", result_limit: $result_limit, search_article_fields: $search_article_fields, search_k2_fields: $search_k2_fields, search_hs_fields: $search_hs_fields, search_sp_fields: $search_sp_fields, search_vm_fields: $search_vm_fields, search_pc_fields: $search_pc_fields, search_dj_fields: $search_dj_fields, search_docman_fields: $search_docman_fields1, perpage_limit: $perpage_limit, redirect_search_url: $redirect_search_url},

                'format' : 'raw'

            };

            jQuery.ajax({

                url   : '$base_url',			                

				type   : 'POST',

                data   : request,

                success: function (response) {

                    // alert(response);

                    jQuery('#'+div_id+' .is_ajaxsearch_result_$module_id').html(response);

                }

            });

            return false;

    });

JS;



$doc->addScriptDeclaration($js);

require JModuleHelper::getLayoutPath('mod_ebajaxsearch');
