<?php

/**
 * @version     1.0.35
 * @package     com_simplesharing
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @author      NYC HelpDesk.co LLC <support@nychelpdesk.co> - nychelpdesk.co
 */

// no direct access
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

// Import CSS
$document = JFactory::getDocument();
$document->addStyleSheet('components/com_simplesharing/assets/css/simplesharing.css');

$user = JFactory::getUser();
$userId = $user->get('id');

?>
<div class="modal hide fade" id="collapseModal" role="dialog">
	<div class="modal-dialog modal-lg">

    <!-- Modal content-->
    <div class="modal-content">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&#215;</button>
        <h3><?php echo JText::_('COM_SIMPLESHARING_ARTICLES_SELECT_WEBSITES'); ?></h3>
    </div>
    <div class="modal-body" style="overflow-y:auto;">
        <!--<form action="<?php echo JRoute::_('index.php?option=com_simplesharing&view=articles'); ?>" method="post" name="adminForm" id="adminFormShare">-->                    
                    <table class="table table-striped" id="slavewebsiteList">
                        <thead>
                            <tr>                                
                                <th width="1%" class="hidden-phone">
                                    <input type="checkbox" name="checkall-toggle" value="" title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this, 'wcb')" />
                                </th>                                
                                <th class='left'>
                                    <?php echo JText::_('COM_SIMPLESHARING_SLAVEWEBSITES_WEBSITE_NAME'); ?>
                                </th>
                                <th class='left'>
                                    <?php echo JText::_('COM_SIMPLESHARING_ARTICLE_CATEGORY'); ?>
                                </th>                    

                                <?php if (isset($this->websites[0]->id)): ?>
                                    <th width="1%" class="nowrap center hidden-phone">
                                        <?php echo JText::_('JGRID_HEADING_ID'); ?>
                                    </th>
                                <?php endif; ?>
                            </tr>
                        </thead>
<!--                        <tfoot>
                            <?php
                            if (isset($this->websites[0])) {
                                $colspan = count(get_object_vars($this->websites[0]));
                            } else {
                                $colspan = 4;
                            }
                            ?>
                            <tr>
                                <td colspan="<?php echo $colspan ?>">
                                    <?php echo $this->pagination->getListFooter(); ?>
                                </td>
                            </tr>
                        </tfoot>-->
                        <tbody>
                            <?php
                            foreach ($this->websites as $i => $item) :  
                                if(!$item->state) continue;
                                $response = SimplesharingRestHelper::processRestRequest($item);
                                if($response->code == 200 || $response->code == 301){
                                    $categoriesHtml =  JHtml::_('select.genericlist', json_decode($response->body),'destCat_'.$item->id, 'size=1');
                                } else {
                                    $categoriesHtml = $response->body;
                                }
                                ?>
                                <tr class="row<?php echo $i % 2; ?>">                                    
                                    <td class="center hidden-phone">
                                        <input type="checkbox" id="wcb<?php echo $i; ?>" name="wcid[]" value="<?php echo $item->id; ?>" onclick="Joomla.isChecked(this.checked);" />                                    
                                    </td>                                    
                                    <td>								
                                        <?php echo $this->escape($item->website_name); ?>
                                    </td>
                                    <td>
                                        <?php echo $categoriesHtml; ?>
                                    </td>
                                    <?php if (isset($this->websites[0]->id)): ?>
                                        <td class="center hidden-phone">
                                            <?php echo (int) $item->id; ?>                                            
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    
<!--                    <input type="hidden" name="task" value="" />
                    <input type="hidden" name="boxchecked" value="0" />
                    <input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
                    <input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
                    <?php echo JHtml::_('form.token'); ?>                -->
        <!--</form>-->
    </div>
    <div class="modal-footer">
        <button class="btn" type="button" onclick="document.id('batch-position-id').value = '';
                document.id('batch-access').value = '';
                document.id('batch-language-id').value = ''" data-dismiss="modal">
            <?php echo JText::_('JCANCEL'); ?>
        </button>
        <button class="btn btn-primary" type="submit" onclick="if(jQuery('input[name=\'wcid[]\']:checked').length > 0) {Joomla.submitbutton('articles.share');} else {alert('Please check at least one webiste to share articles with'); return false;}">
            <?php echo JText::_('JGLOBAL_BATCH_PROCESS'); ?>
        </button>
    </div>
    </div>
    </div>
</div>        


