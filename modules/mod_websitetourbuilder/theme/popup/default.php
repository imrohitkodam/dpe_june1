<?php
/**
 * @version     1.5
 * @package     mod_websitetourbuilder
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      JoomlaForce Team <support@joomlaforce.com> - http://www.joomlaforce.com
 */
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;

for ($i = 0; $i < $totalSteps; $i++) 
{
    $item = $items[$i];
?>
    <div id="<?php echo $item->stepcontent; ?>" style="display: none;">
        <p class="tooltipTitle"><?php echo $item->steptitle; ?></p>
        <p class="tooltipText"><?php echo $item->steptext; ?></p>
        <?php
        if (($i > 0)&& ($totalSteps-1))
        {
        ?>
            <a href="javascript:;" class="prev-step" style="float:left;"><?php echo Text::_('MOD_WEBSITETOUR_PREV'); ?></a>
        <?php    
        }
        if ($i < $totalSteps-1)
        {
        ?>
            <a href="javascript:;" class="next-step" style="float:right;"><?php echo Text::_('MOD_WEBSITETOUR_NEXT'); ?></a>
        <?php    
        }
        ?>
         <?php
        if($item->time!="" && is_numeric($item->time) && $item->time > 0)
        {
        ?>
            
            <?php
            if($params->get('show_timer_controls',0))
            {
            ?>
                <br /><br /><div id="websitetour-time-wrap" class="clearfix">
                    <div id="websitetour-time-ctrls">
                        <a id="time-prev" href="#" title="prev"></a> 
                        <a id="time-stop" href="#" title="stop"></a> 
                        <a id="time-pause" href="#" title="pause"></a> 
                        <a id="time-play" href="#" title="play"></a> 
                        <a id="time-next" href="#" title="next"></a>
                    </div>
                    <div id="websitetour_timer"><?php echo Text::_('MOD_WEBSITETOUR_NEXT_STEP_STARTS_AT'); ?>: <div id="time_progress"></div></div>
                </div>
                <div class="clearfix"></div>
            <?php
            }
            ?>
            
        <?php    
        }
        ?>
    </div>
<?php    
}
?>