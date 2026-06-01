<?php
use Joomla\CMS\Language\Text;

$toolsData     = $displayData['toolsData'];
$title         = $displayData['title'];
$widgetData    = $toolsData['data']['count']['widgetdata'];
$campaignCount = $widgetData['Campaigns'] ? $widgetData['Campaigns'] : 0;
$groupCount    = $widgetData['Groups'] ? $widgetData['Groups'] : 0;
?>
<?php if ($toolsData['data']['count']) { ?>
<div class="pie-layout">
   <div class="widget-data panel panel-info dashboard-widget-cover-65 undefined">
      <div class="widget-title panel-heading">
         <span class="fs-20 pr-5 fa fa-wpforms" aria-hidden="true"></span>
         <span class="ml-10 fs-18 font-600"><?php echo $title;?></span>
      </div>
      <div class="filter-widget">
         <div class="panel-body">
            <div class="row">
               <div class="col-12">
                  <div class="huge font-600">
                     <a href="<?php echo $toolsData['data']['link']['Campaigns'];?>" target="_blank" class="row">
                        <div class="col-3 text-center"><?php echo $campaignCount;?></div>
                        <div class="col-8"><?php echo Text::_('COM_DPE_STAFF_DASHBOARD_CAMPAIGNS');?></div>
                     </a>
                  </div>
                  <div class="huge font-600">
                     <a href="<?php echo $toolsData['data']['link']['Groups'];?>" target="_blank" class="row">
                        <div class="col-3 text-center"><?php echo $groupCount;?></div>
                        <div class="col-8"><?php echo Text::_('COM_DPE_STAFF_DASHBOARD_GROUPS');?></div>
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
