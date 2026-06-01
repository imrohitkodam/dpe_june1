<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	com_nobossfaq
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2020 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

// Evita acesso direto ao arquivo.
defined('_JEXEC') or die;

// Carrega helper de menus para a classe MenusHelper.
JLoader::register('MenusHelper', JPATH_ADMINISTRATOR . '/components/com_menus/helpers/menus.php');
// Pega tipos de menu.
$menuTypes = MenusHelper::getMenuLinks();

$clientId = $this->moduleClientId;
$assignment = $this->moduleAssignment;
$assigned = $this->moduleAssigned;

$script = "
	jQuery(document).ready(function()
	{
		menuHide".ucwords($this->nameModuelAssignment)."(jQuery('#jform_" . $this->nameModuelAssignment ."').val());
		jQuery('#jform_" . $this->nameModuelAssignment ."').change(function()
		{
			menuHide".ucwords($this->nameModuelAssignment)."(jQuery(this).val());
		})
	});
	function menuHide".ucwords($this->nameModuelAssignment)."(val)
	{
		if (val == 0 || val == '-')
		{
			jQuery('#menuselect_" . $this->nameModuelAssignment ."-group').hide();
		}
		else
		{
			jQuery('#menuselect_" . $this->nameModuelAssignment ."-group').show();
		}
	}
";

// Adiciona o script para head do documento.
JFactory::getDocument()->addScriptDeclaration($script);

?>
<div class="control-group">
	<label id="jform_menus_<?php echo $this->nameModuelAssignment; ?>-lbl" class="control-label" for="jform_menus_<?php echo $this->nameModuelAssignment; ?>">
		<?php echo JText::_('COM_NOBOSSFAQ_MODULE_ASSIGN_LABEL'); ?>
	</label>
	<div id="jform_menus_<?php echo $this->nameModuelAssignment; ?>" class="controls">
		<select name="jform[<?php echo $this->nameModuelAssignment; ?>]" id="jform_<?php echo $this->nameModuelAssignment; ?>">
			<?php echo JHtml::_('select.options', NobossfaqHelper::getAssignmentOptions($this->item->client_id), 'value', 'text', $assignment, true); ?>
		</select>
	</div>
</div>
<?php
// Obtem o model
$model = $this->getModel("Group", "NobosstestimonialsModel");
// Obtem total de items de menus
$totalMenus = $model->getTotalMenus();

// Armazena maximo de itens aceitaveis
// TODO: criar parametro nas configuracoes globais para permitir alterar esse valor
$limiteItens = 3000;

// Se tiver mais do que xx itens, impede de usar o campo para não ter problema de performance
if($totalMenus > $limiteItens){
    echo '<div class="alert alert-info" style="display: table; font-weight: normal;"><span class="icon-joomla icon-info"></span> '.
        JText::sprintf("LIB_NOBOSS_FIELD_NOBOSSITEMMENU_MESSAGE_LIMIT", $limiteItens).'</div>';
}
else{
    // Pega tipos de menu.
    $menuTypes = MenusHelper::getMenuLinks();
    ?>
    <div id="menuselect_<?php echo $this->nameModuelAssignment; ?>-group" class="control-group">
        <label id="jform_menuselect_<?php echo $this->nameModuelAssignment; ?>-lbl" class="control-label" for="jform_menuselect_<?php echo $this->nameModuelAssignment; ?>">
            <?php echo JText::_('JGLOBAL_MENU_SELECTION'); ?>
        </label>
        <div id="jform_menuselect_<?php echo $this->nameModuelAssignment; ?>" class="controls">
            <?php if (!empty($menuTypes)) : ?>
            <?php $id = 'jform_menuselect_' . $this->nameModuelAssignment; ?>

            <div class="well well-small">
                <div class="form-inline">
                    <span class="small"><?php echo JText::_('JSELECT'); ?>:
                        <a id="treeCheckAll" data-name="treeCheckAll" href="javascript://"><?php echo JText::_('JALL'); ?></a>,
                        <a id="treeUncheckAll" data-name="treeUncheckAll" href="javascript://"><?php echo JText::_('JNONE'); ?></a>
                    </span>
                    <span class="width-20">|</span>
                    <span class="small"><?php echo JText::_('COM_NOBOSSFAQ_EXPAND'); ?>:
                        <a id="treeExpandAll" data-name="treeExpandAll" href="javascript://"><?php echo JText::_('JALL'); ?></a>,
                        <a id="treeCollapseAll" data-name="treeCollapseAll" href="javascript://"><?php echo JText::_('JNONE'); ?></a>
                    </span>
                    <input type="text" id="treeselectfilter" name="treeselectfilter" class="input-medium search-query pull-right" size="16"
                        autocomplete="off" placeholder="<?php echo JText::_('JSEARCH_FILTER'); ?>" aria-invalid="false" tabindex="-1">
                </div>

                <div class="clearfix"></div>

                <hr class="hr-condensed" />

                <ul class="treeselect">
                    <?php foreach ($menuTypes as &$type) : ?>
                    <?php if (count($type->links)) : ?>
                        <?php $prevlevel = 0; ?>
                        <li>
                            <div class="treeselect-item pull-left">
                                <label class="pull-left nav-header"><?php echo $type->title; ?></label></div>
                        <?php foreach ($type->links as $i => $link) : ?>
                            <?php
                            if ($prevlevel < $link->level)
                            {
                                echo '<ul class="treeselect-sub">';
                            } elseif ($prevlevel > $link->level)
                            {
                                echo str_repeat('</li></ul>', $prevlevel - $link->level);
                            } else {
                                echo '</li>';
                            }
                            $selected = 0;
                            if ($assignment == 0)
                            {
                                $selected = 1;
                            } elseif ($assignment < 0)
                            {
                                $selected = in_array(-$link->value, $assigned);
                            } elseif ($assignment > 0)
                            {
                                $selected = in_array($link->value, $assigned);
                            }
                            ?>
                                <li>
                                    <div class="treeselect-item pull-left">
                                        <input type="checkbox" class="pull-left novalidate" name="jform[<?php echo $this->nameModuleAssigned ?>][]" id="<?php echo $id . $link->value; ?>" value="<?php echo (int) $link->value; ?>"<?php echo $selected ? ' checked="checked"' : ''; ?> />
                                        <label for="<?php echo $id . $link->value; ?>" class="pull-left">
                                            <?php echo $link->text; ?> <span class="small"><?php echo JText::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($link->alias));?></span>
                                            <?php if (JLanguageMultilang::isEnabled() && $link->language != '' && $link->language != '*')
                                            {
                                                echo JHtml::_('image', 'mod_languages/' . $link->language_image . '.gif', $link->language_title, array('title' => $link->language_title), true);
                                            }
                                            if ($link->published == 0)
                                            {
                                                echo ' <span class="label">' . JText::_('JUNPUBLISHED') . '</span>';
                                            }
                                            ?>
                                        </label>
                                    </div>
                            <?php

                            if (!isset($type->links[$i + 1]))
                            {
                                echo str_repeat('</li></ul>', $link->level);
                            }
                            $prevlevel = $link->level;
                            ?>
                            <?php endforeach; ?>
                        </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <div id="noresultsfound" style="display:none;" class="alert alert-no-items">
                    <?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                </div>
                <div style="display:none;" id="treeselectmenu">
                    <div class="pull-left nav-hover treeselect-menu">
                        <div class="btn-group">
                            <a href="#" data-toggle="dropdown" class="dropdown-toggle btn btn-micro">
                                <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li class="nav-header"><?php echo JText::_('COM_NOBOSSFAQ_SUBITEMS'); ?></li>
                                <li class="divider"></li>
                                <li class=""><a class="checkall" href="javascript://"><span class="icon-checkbox"></span> <?php echo JText::_('JSELECT'); ?></a>
                                </li>
                                <li><a class="uncheckall" href="javascript://"><span class="icon-checkbox-unchecked"></span> <?php echo JText::_('COM_NOBOSSFAQ_DESELECT'); ?></a>
                                </li>
                                <div class="treeselect-menu-expand">
                                <li class="divider"></li>
                                <li><a class="expandall" href="javascript://"><span class="icon-plus"></span> <?php echo JText::_('COM_NOBOSSFAQ_EXPAND'); ?></a></li>
                                <li><a class="collapseall" href="javascript://"><span class="icon-minus"></span> <?php echo JText::_('COM_NOBOSSFAQ_COLLAPSE'); ?></a></li>
                                </div>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
<?php
}
?>
