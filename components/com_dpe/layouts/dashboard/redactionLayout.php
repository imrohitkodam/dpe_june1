<?php
use Joomla\CMS\Language\Text;

$toolsData     = $displayData['toolsData'];

?>
<?php if ($toolsData['data']['titleLink']) { ?>
<div class="pie-layout">
   <div class="widget-data panel panel-info dashboard-widget-cover-65 undefined">
      <div class="widget-title panel-heading">
         <span class="fs-20 pr-5 fa fa-wpforms" aria-hidden="true"></span>
         <span class="ml-10 fs-18 font-600"><?php echo Text::_('COM_DPE_PDF_TRON_HEADER_TITLE');?></span>
      </div>
      <div class="filter-widget">
         <div class="panel-body">
            <div class="row">
               <div class="col-12">
                  <div class="huge font-600">
                     <a href="<?php echo $toolsData['data']['titleLink']?>" target="_blank" class="row">
                      <div class="col-8"><?php echo Text::_('COM_DPE_PDF_TRON_HEADER_TITLE');?></div>

                     </a>
                  </div>
                
               </div>
            </div>
         </div>
         <div class="clearfix"></div>
      </div>
   </div>
</div>
<?php } ?>
