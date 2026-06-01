<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	Layouts
 * @author			No Boss Technology <contato@noboss.com.br>
 * @copyright		Copyright (C) 2021 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

defined('_JEXEC') or die;

/**
 * Layout tradicional para utilizar como tmpl de views de listagem de registros
 * 
 * ORIENTACOES DE USO:
 * 
 * Para chamar esse layout, utilize o codigo abaixo:
 *      echo JLayoutHelper::render('noboss.j3.list.traditional', $this);
 * 
 * No arquivo de view siga o modelo da No Boss para garantir que tenha todas variaveis declaradas que sao necessarias para o funcionamento deste modelo de tmpl.
 * 
 * Mais informacoes podem ser obtidas em TODO: colocar link aqui
 */

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$user		= JFactory::getUser();
$userId		= $user->get('id');
$listOrder	= $displayData->escape($displayData->state->get('list.ordering'));
$listDirn	= $displayData->escape($displayData->state->get('list.direction'));
$saveOrder = $listOrder == $displayData->prefixColumns.'.'.$displayData->orderingColumn;

if ($saveOrder){
	$saveOrderingUrl = "index.php?option={$displayData->componentAlias}&task={$displayData->viewName}.saveOrderAjax&tmpl=component";
	JHtml::_('sortablelist.sortable', 'articleList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}

$totalColumns = 0;
?>

<form  name="adminForm" id="adminForm"  method="post" action="<?php echo JRoute::_("index.php?option={$displayData->componentAlias}&view={$displayData->viewName}"); ?>">
    <?php 
    // Definida barra lateral de navegacao
    if(!empty($displayData->sidebar)){
    ?>
		<div id="j-sidebar-container" class="span2">
			<?php echo $displayData->sidebar; ?>
		</div>
		<div id="j-main-container" class="span10">
        <?php
    }
    // Nao tem barra lateral
    else{
        ?>
        <div id="j-main-container">
        <?php
    }

        // Definio texto de introducao para exibir dentro de um notice
        if(!empty($displayData->noticeIntro)){
            ?>
            <div class='alert alert-info'>
                <span><?php echo $displayData->noticeIntro; ?></span>
            </div>
            <?php            
        }

        // Layout para busca de registros
        echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $displayData));

        ?>
        <div class="clearfix"></div>
        <?php

        // Nenhum registro a exibir
        if (empty($displayData->items)) {
        ?>
            <div class="alert alert-no-items">
                <?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
            </div>
        <?php 
        } else {
        ?>
            <table class="table table-striped" id="articleList">
                <?php // Cabecalho da tabela ?>
                <thead>
                    <tr>
                        <?php
                        // Ordenacao
                        if (!empty($displayData->orderingColumn)) { 
                            $totalColumns++;
                        ?>
                            <th width="1%" class="nowrap center hidden-phone">
                                <?php echo JHtml::_('searchtools.sort', '', $displayData->prefixColumns.'.'.$displayData->orderingColumn, $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
                            </th>
                        <?php
                        }

                        // Checkbox 
                        $totalColumns++;
                        ?>
                        <th width="1%">
                            <?php echo JHtml::_('grid.checkall'); ?>
                        </th>

                        <?php
                        // Status
                        if (!empty($displayData->statusColumn)) { 
                            $totalColumns++;
                        ?>
                            <th width="1%" class="nowrap center">
                                <?php echo JHtml::_('searchtools.sort', 'JSTATUS', $displayData->prefixColumns.'.'.$displayData->statusColumn, $listDirn, $listOrder); ?>
                            </th>
                        <?php
                        }

                        // Coluna principal (exibe titulo, nome ou algo similar)
                        if (!empty($displayData->mainColumn)) {
                            $totalColumns++; 
                        ?>
                            <th width="<?php echo (!empty($displayData->mainColumnWidth)) ? $displayData->mainColumnWidth : ''; ?>">
                                <?php echo JHtml::_('searchtools.sort', $displayData->mainColumnName, $displayData->prefixColumns.'.'.$displayData->mainColumn, $listDirn, $listOrder); ?>
                            </th>
                        <?php
                        }

                        // Percorre as colunas customizada a serem exibidas
                        foreach ($displayData->customColumns as $column) {
                            $totalColumns++;
                            ?>
                            <th width="<?php echo (!empty($column->width)) ? $column->width : ''; ?>" class="nowrap hidden-phone">
                                <?php
                                // Exibir com ordenacao
                                if(!empty($column->allowOrdering) && $column->allowOrdering==1){
                                    echo JHtml::_('searchtools.sort',  $column->title, $column->alias, $listDirn, $listOrder);
                                }
                                // Exibir sem ordenacao
                                else{
                                    echo JText::_($column->title);
                                }
                            ?>
                            </th>
                            <?php
                        }

                        // Autor
                        if (!empty($displayData->nameCreation)) { 
                            $totalColumns++;
                        ?>
                            <th width="15%" class="nowrap hidden-phone">
                                <?php echo JHtml::_('searchtools.sort',  'JAUTHOR', $displayData->prefixColumns.'.'.$displayData->nameCreation, $listDirn, $listOrder); ?>
                            </th>
                        <?php
                        }

                        // Data de criacao
                        if (!empty($displayData->creationDate)) { 
                            $totalColumns++;
                        ?>
                            <th width="10%" class="nowrap hidden-phone">
                                <?php echo JHtml::_('searchtools.sort',  'JGLOBAL_FIELD_CREATED_LABEL', $displayData->prefixColumns.'.'.$displayData->creationDate, $listDirn, $listOrder); ?>
                            </th>
                        <?php
                        }

                        // Revisado por
                        if (!empty($displayData->nameModification)) { 
                            $totalColumns++;
                        ?>
                            <th width="15%" class="nowrap hidden-phone">
                                <?php echo JHtml::_('searchtools.sort',  'JGLOBAL_FIELD_MODIFIED_BY_LABEL', $displayData->prefixColumns.'.'.$displayData->nameModification, $listDirn, $listOrder); ?>
                            </th>
                        <?php
                        }

                        // Data de modificacao
                        if (!empty($displayData->modificationDate)) { 
                            $totalColumns++;
                        ?>
                            <th width="10%" class="nowrap hidden-phone">
                                <?php echo JHtml::_('searchtools.sort',  'JGLOBAL_FIELD_MODIFIED_LABEL', $displayData->prefixColumns.'.'.$displayData->modificationDate, $listDirn, $listOrder); ?>
                            </th>
                        <?php
                        }

                        // Idioma
                        if (!empty($displayData->languageColumn)) { 
                            $totalColumns++;
                        ?>
                            <th width="15%" class="nowrap hidden-phone">
                                <?php echo JHtml::_('searchtools.sort',  'JGRID_HEADING_LANGUAGE', $displayData->prefixColumns.'.'.$displayData->languageColumn, $listDirn, $listOrder); ?>
                            </th>
                        <?php
                        }

                        // ID
                        if (!empty($displayData->recordIdAlias)) { 
                            $totalColumns++;
                        ?>
                            <th width="1%" class="nowrap hidden-phone">
                                <?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', $displayData->prefixColumns.'.'.$displayData->recordIdAlias, $listDirn, $listOrder); ?>
                            </th>
                        <?php
                        }
                        ?>
                    </tr>
                </thead>

                <?php // Rodape com paginacao ?>
                <tfoot>
					<tr>
						<td colspan="<?php echo $totalColumns; ?>">
							<?php echo $displayData->pagination->getListFooter(); ?>
						</td>
					</tr>
				</tfoot>

                <tbody>
                    <?php
                    // Percorre todos itens a exibir
                    foreach ($displayData->items as $i => $item) {
                        $ordering  = ($listOrder == $displayData->prefixColumns.'.'.$displayData->orderingColumn);
                        $canCreate  = $user->authorise('core.create',     $displayData->componentAlias);
                        $canEdit    = $user->authorise('core.edit',       $displayData->componentAlias);
                        $canCheckin = $user->authorise('core.manage',     'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
                        $canEditOwn = $user->authorise('core.edit.own',   'com_newsfeeds') && $item->created_by == $user->id;
                        $canChange  = $user->authorise('core.edit.state', $displayData->componentAlias) && $canCheckin;
                        ?>
                        <tr class="row<?php echo $i % 2; ?>" >
                            <?php
                            // Ordenacao (se definido)
                            if (!empty($displayData->orderingColumn)) {
                            ?>
                                <td class="order nowrap center hidden-phone">
                                    <?php
                                    $iconClass = '';
                                    if (!$canChange)
                                    {
                                        $iconClass = ' inactive';
                                    }
                                    elseif (!$saveOrder)
                                    {
                                        $iconClass = ' inactive tip-top hasTooltip" title="' . JHtml::_('tooltipText', 'JORDERINGDISABLED');
                                    }
                                    ?>
                                    <span class="sortable-handler<?php echo $iconClass ?>">
                                        <span class="icon-menu"></span>
                                    </span>
                                    <?php if ($canChange && $saveOrder) : ?>
                                        <input type="text" style="display:none" name="order[]" size="5" value="<?php echo $item->{$displayData->orderingColumn}; ?>" class="width-20 text-area-order" />
                                    <?php endif; ?>
                                </td>
                            <?php
                            }
                            
                            // Checkbox
                            ?>
                            <td class="center ">
                                <?php echo JHtml::_('grid.id', $i, $item->{$displayData->recordIdAlias}); ?>
                            </td>

                            <?php
                            // Status (se definido)
                            if (!empty($displayData->statusColumn)) { 
                            ?>
                                <td class="center">
                                    <div class="btn-group">
                                        <?php

                                        // Existem os campos padroes de inicio e fim de publicacao
                                        if (!empty($displayData->publishUpColumn) && (!empty($displayData->publishDownColumn))){
                                            echo JHtml::_('jgrid.published', $item->{$displayData->statusColumn}, $i, $displayData->viewName.'.', $canChange, 'cb', $item->{$displayData->publishUpColumn}, $item->{$displayData->publishDownColumn});
                                        }
                                        // Existe apenas o campo de status da publicacao
                                        else{
                                            echo JHtml::_('jgrid.published', $item->{$displayData->statusColumn}, $i, $displayData->viewName.'.', $canChange);
                                        }
                                        // Exibe dropdown com opcoes para arquivar e enviar para lixeira
                                        if ($canChange){
                                            JHtml::_('actionsdropdown.' . ((int) $item->{$displayData->statusColumn} === 2 ? 'un' : '') . 'archive', 'cb' . $i, 'newsfeeds');
                                            JHtml::_('actionsdropdown.' . ((int) $item->{$displayData->statusColumn} === -2 ? 'un' : '') . 'trash', 'cb' . $i, 'newsfeeds');
                                            echo JHtml::_('actionsdropdown.render', $displayData->escape($item->{$displayData->mainColumn}));
                                        }
                                        ?>
                                    </div>
                                </td>
                            <?php
                            }

                            // Coluna principal (exibe titulo, nome ou algo similar)
                            if (!empty($displayData->mainColumn)) { 
                            ?>
                                <td>
                                    <?php if ($item->checked_out) : ?>
                                        <?php echo JHtml::_('jgrid.checkedout', $i, '', $item->checked_out_time, $displayData->viewName.'.', $canCheckin); ?>
                                    <?php endif; ?>
                                    <?php if ($canEdit) : ?>
                                        <a href="<?php echo JRoute::_('index.php?option='.$displayData->componentAlias.'&task='.$displayData->createViewAlias.'.edit&'.$displayData->recordIdAlias.'='.(int) $item->{$displayData->recordIdAlias}); ?>">
                                            <?php echo $displayData->escape($item->{$displayData->mainColumn}); ?>
                                        </a>
                                    <?php else : ?>
                                        <?php echo $displayData->escape($item->{$displayData->mainColumn}); ?>
                                    <?php endif; ?>

                                    <?php
                                    // Texto adicional pequeno (small) exibido ao lado do conteudo da coluna principal
                                    if (!empty($displayData->mainColumnSmall)) { 
                                    ?>
                                        <span class="small">
                                            <?php echo $displayData->mainColumnSmall; ?>
                                        </span>
                                    <?php
                                    }
                                    ?>
                                </td>
                            <?php
                            }

                            // Percorre as colunas customizada a serem exibidas
                            foreach ($displayData->customColumns as $column) {
                                ?>
                                <td>
                                    <?php
                                    // Existe valor definido
                                    if(!empty($item->{$column->alias})){
                                        // Existe '%VALUE%' no retorno que deve ser trocado pelo valor do item
                                        if (strpos($column->returnHtml, '%VALUE%') !== true) {
                                            echo str_replace('%VALUE%', $item->{$column->alias}, $column->returnHtml);
                                        }
                                        // Exibe direto o valor do item
                                        else{
                                            echo $item->{$column->alias};
                                        }
                                    }
                                    // Nao existe valor definido: seta valor default
                                    else{
                                        echo $column->returnHtmlWhenNull;
                                    }
                                ?>
                                </td>
                                <?php
                            }

                            // Autor
                            if (!empty($displayData->nameCreation)) { 
                            ?>
                                <td class="small nowrap hidden-phone">
                                <?php echo JFactory::getUser($item->created_by)->name; ?>
                                </td>
                            <?php
                            }

                            // Data de criacao
                            if (!empty($displayData->creationDate)) { 
                            ?>
                                <td class="small nowrap hidden-phone">
                                    <?php echo date_format(date_create($item->created),'Y.m.d'); ?>
                                </td>
                            <?php
                            }

                            // Revisado por
                            if (!empty($displayData->nameModification)) { 
                            ?>
                                <td class="small nowrap hidden-phone">
                                    <?php echo JFactory::getUser($item->modified_by)->name; ?>
                                </td>
                            <?php
                            }

                            // Data de modificacao
                            if (!empty($displayData->modificationDate)) { 
                            ?>
                                <td class="small nowrap hidden-phone">
                                    <?php echo date_format(date_create($item->modified),'Y.m.d'); ?>
                                </td>
                            <?php
                            }

                            // Idioma
                            if (!empty($displayData->languageColumn)) { 
                            ?>
                                <td class="small nowrap hidden-phone">
                                    <?php echo JLayoutHelper::render('joomla.content.language', $item); ?>
                                </td>
                                <?php
                            }

                            // ID
                            if (!empty($displayData->recordIdAlias)) { 
                            ?>
                                <td class="center ">
                                    <?php echo $item->{$displayData->recordIdAlias}; ?>
                                </td>
                            <?php
                            }
                            ?>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
        <?php
        }
        ?>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
