<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2024 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Layout tradicional para utilizar como tmpl de views de listagem de registros
 * 
 * ORIENTACOES DE USO:
 * 
 * Para chamar esse layout, utilize o codigo abaixo:
 *      echo JLayoutHelper::render('noboss.j4.list.traditional', $this);
 * 
 * No arquivo de view siga o modelo da No Boss para garantir que tenha todas variaveis declaradas que sao necessarias para o funcionamento deste modelo de tmpl.
 * 
 * Mais informacoes podem ser obtidas em TODO: colocar link aqui
 */

HTMLHelper::_('behavior.multiselect');

$user		= Factory::getUser();
$userId		= $user->get('id');
$listOrder	= $displayData->escape($displayData->state->get('list.ordering'));
$listDirn	= $displayData->escape($displayData->state->get('list.direction'));
$saveOrder =  $listOrder == $displayData->prefixColumns.'.'.$displayData->orderingColumn;

if ($saveOrder){
	$saveOrderingUrl = "index.php?option={$displayData->componentAlias}&task={$displayData->viewName}.saveOrderAjax&tmpl=component&" . Session::getFormToken() . '=1';
	HTMLHelper::_('draggablelist.draggable');
}
?>

<form  name="adminForm" id="adminForm"  method="post" action="<?php echo Route::_("index.php?option={$displayData->componentAlias}&view={$displayData->viewName}"); ?>">
    <div class="row">
		<div class="col-md-12">
			<div id="j-main-container" class="j-main-container">
                <?php   
                // Definido texto de introducao para exibir dentro de um notice
                if(!empty($displayData->noticeIntro)){
                ?>
                    <div class="alert alert-info">
						<?php echo $displayData->noticeIntro; ?>
					</div>
                <?php            
                }

                // Layout para busca de registros
                echo LayoutHelper::render('joomla.searchtools.default', array('view' => $displayData));

                // Nenhum registro a exibir
                if (empty($displayData->items)) {
                ?>
                    <div class="alert alert-info">
						<span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
						<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
					</div>
                <?php 
                } else {
                ?>
                    <table class="table" id="articleList">
                        <?php // Cabecalho da tabela ?>
                        <thead>
                            <tr>
                               <?php
                                // Checkbox 
                                ?>
                                <th class="w-1 text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </th>

                                <?php
                                // Ordenacao
                                if (!empty($displayData->orderingColumn)) { 
                                ?>
                                    <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                                        <?php echo HTMLHelper::_('searchtools.sort', '', $displayData->prefixColumns.'.'.$displayData->orderingColumn, $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
                                    </th>
                                <?php
                                }

                                // Status
                                if (!empty($displayData->statusColumn)) { 
                                ?>
                                    <th scope="col" class="w-5 text-center">
                                        <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', $displayData->prefixColumns.'.'.$displayData->statusColumn, $listDirn, $listOrder); ?>
                                    </th>
                                <?php
                                }

                                // Coluna principal (exibe titulo, nome ou algo similar)
                                if (!empty($displayData->mainColumn)) { 
                                ?>
                                    <th scope="col" class="title" width="<?php echo (!empty($displayData->mainColumnWidth)) ? $displayData->mainColumnWidth : ''; ?>">
                                        <?php echo HTMLHelper::_('searchtools.sort', $displayData->mainColumnName, $displayData->prefixColumns.'.'.$displayData->mainColumn, $listDirn, $listOrder); ?>
                                    </th>
                                <?php
                                }

                                // Percorre as colunas customizada a serem exibidas
                                foreach ($displayData->customColumns as $column) {
                                    ?>
                                    <th scope="col" width="<?php echo (!empty($column->width)) ? $column->width : '20%'; ?>" class="d-none d-md-table-cell">
                                        <?php
                                        // Exibir com ordenacao
                                        if(!empty($column->allowOrdering) && $column->allowOrdering==1){
                                            echo HTMLHelper::_('searchtools.sort',  $column->title, $column->alias, $listDirn, $listOrder);
                                        }
                                        // Exibir sem ordenacao
                                        else{
                                            echo Text::_($column->title);
                                        }
                                    ?>
                                    </th>
                                    <?php
                                }

                                // Autor
                                if (!empty($displayData->nameCreation)) { 
                                ?>
                                    <th scope="col" class="w-18 d-none d-md-table-cell">
                                        <?php echo HTMLHelper::_('searchtools.sort',  'JAUTHOR', $displayData->prefixColumns.'.'.$displayData->nameCreation, $listDirn, $listOrder); ?>
                                    </th>
                                <?php
                                }

                                // Data de criacao
                                if (!empty($displayData->creationDate)) { 
                                ?>
                                    <th scope="col" class="w-10 d-none d-md-table-cell">
                                        <?php echo HTMLHelper::_('searchtools.sort',  'JGLOBAL_FIELD_CREATED_LABEL', $displayData->prefixColumns.'.'.$displayData->creationDate, $listDirn, $listOrder); ?>
                                    </th>
                                <?php
                                }

                                // Revisado por
                                if (!empty($displayData->nameModification)) { 
                                ?>
                                    <th scope="col" class="w-18 d-none d-md-table-cell">
                                        <?php echo HTMLHelper::_('searchtools.sort',  'JGLOBAL_FIELD_MODIFIED_BY_LABEL', $displayData->prefixColumns.'.'.$displayData->nameModification, $listDirn, $listOrder); ?>
                                    </th>
                                <?php
                                }

                                // Data de modificacao
                                if (!empty($displayData->modificationDate)) { 
                                ?>
                                    <th scope="col" class="w-10 d-none d-md-table-cell">
                                        <?php echo HTMLHelper::_('searchtools.sort',  'JGLOBAL_FIELD_MODIFIED_LABEL', $displayData->prefixColumns.'.'.$displayData->modificationDate, $listDirn, $listOrder); ?>
                                    </th>
                                <?php
                                }                              

                                // Idioma
                                if (!empty($displayData->languageColumn)) { 
                                ?>
                                    <th scope="col" class="w-10 d-none d-md-table-cell">
                                        <?php echo HTMLHelper::_('searchtools.sort',  'JGRID_HEADING_LANGUAGE', $displayData->prefixColumns.'.'.$displayData->languageColumn, $listDirn, $listOrder); ?>
                                    </th>
                                <?php
                                }

                                // ID
                                if (!empty($displayData->recordIdAlias)) { 
                                ?>
                                    <th scope="col" class="w-5 d-none d-md-table-cell">
                                        <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', $displayData->prefixColumns.'.'.$displayData->recordIdAlias, $listDirn, $listOrder); ?>
                                    </th>
                                <?php
                                }
                                ?>
                            </tr>
                        </thead>

                        <tbody <?php if ($saveOrder) :?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="true"<?php endif; ?>>
                            <?php
                            // Percorre todos itens a exibir
                            foreach ($displayData->items as $i => $item) {
                                $ordering  = ($listOrder == $displayData->prefixColumns.'.'.$displayData->orderingColumn);
                                $canCreate  = $user->authorise('core.create',     $displayData->componentAlias);
                                $canEdit    = $user->authorise('core.edit',       $displayData->componentAlias);                               
                                $canCheckin = $user->authorise('core.manage',     'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
                                //$canEditOwn = $user->authorise('core.edit.own',   'com_newsfeeds') && $item->created_by == $user->id;
                                $canChange  = $user->authorise('core.edit.state', $displayData->componentAlias) && $canCheckin;
                                ?>
                                <tr class="row<?php echo $i % 2; ?>" data-draggable-group="1">
                                    <?php
                                    // Checkbox
                                    ?>
                                    <td class="text-center">
                                        <?php                                        
                                        echo HTMLHelper::_('grid.id', $i, $item->{$displayData->recordIdAlias}, false, 'cid', 'cb', (isset($item->{$displayData->mainColumn}) ? $item->{$displayData->mainColumn} : '')); ?>
                                    </td>
                                    
                                    <?php
                                    // Ordenacao (se definido)
                                    if (!empty($displayData->orderingColumn)) {
                                    ?>
                                        <td class="text-center d-none d-md-table-cell">
                                            <?php
                                            $iconClass = '';
                                            if (!$canChange)
                                            {
                                                $iconClass = ' inactive';
                                            }
                                            elseif (!$saveOrder)
                                            {
                                                $iconClass = ' inactive" title="' . Text::_('JORDERINGDISABLED');
                                            }
                                            ?>
                                            <span class="sortable-handler<?php echo $iconClass ?>">
                                                <span class="icon-ellipsis-v" aria-hidden="true"></span>
                                            </span>
                                            <?php if ($canChange && $saveOrder) : ?>
                                                <input type="text" name="order[]" size="5" value="<?php echo $item->{$displayData->orderingColumn}; ?>" class="width-20 text-area-order hidden">
                                            <?php endif; ?>
                                        </td>
                                    <?php
                                    }

                                    // Status (se definido)
                                    if (!empty($displayData->statusColumn)) { 
                                    ?>
                                        <td class="text-center">
                                            <?php
                                            // Existem os campos padroes de inicio e fim de publicacao
                                            if (!empty($displayData->publishUpColumn) && (!empty($displayData->publishDownColumn))){
                                                echo HTMLHelper::_('jgrid.published', $item->{$displayData->statusColumn}, $i, $displayData->viewName.'.', $canChange, 'cb', $item->{$displayData->publishUpColumn}, $item->{$displayData->publishDownColumn});
                                            }
                                            // Existe apenas o campo de status da publicacao
                                            else{
                                                echo HTMLHelper::_('jgrid.published', $item->{$displayData->statusColumn}, $i, $displayData->viewName.'.', $canChange);
                                            }
                                            ?>
                                        </td>
                                    <?php
                                    }

                                    // Coluna principal (exibe titulo, nome ou algo similar)
                                    if (!empty($displayData->mainColumn)) { 
                                    ?>
                                        <th scope="row" class="has-context">
									        <div>
                                                <?php if (isset($item->checked_out) && $item->checked_out) : ?>
                                                    <?php echo HTMLHelper::_('jgrid.checkedout', $i, '', $item->checked_out_time, $displayData->viewName.'.', $canCheckin); ?>
                                                <?php endif; ?>
                                                <?php if ($canEdit) : ?>
                                                    <a href="<?php echo Route::_('index.php?option='.$displayData->componentAlias.'&task='.$displayData->createViewAlias.'.edit&'.$displayData->recordIdAlias.'='.(int) $item->{$displayData->recordIdAlias}); ?>" title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $this->escape($item->{$displayData->mainColumn}); ?>">
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
                                            </div>
								        </th>
                                    <?php
                                    }

                                    // Percorre as colunas customizada a serem exibidas
                                    foreach ($displayData->customColumns as $column) {
                                        ?>
                                        <td class="small d-none d-md-table-cell">
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
                                        <td class="small d-none d-md-table-cell">
                                        <?php echo Factory::getUser($item->created_by)->name; ?>
                                        </td>
                                    <?php
                                    }

                                    // Data de criacao
                                    if (!empty($displayData->creationDate)) { 
                                    ?>
                                        <td class="small d-none d-md-table-cell">
                                            <?php echo date_format(date_create($item->created),'Y.m.d'); ?>
                                        </td>
                                    <?php
                                    }

                                    // Revisado por
                                    if (!empty($displayData->nameModification)) { 
                                    ?>
                                        <td class="small d-none d-md-table-cell">
                                            <?php echo Factory::getUser($item->modified_by)->name; ?>
                                        </td>
                                    <?php
                                    }

                                    // Data de modificacao
                                    if (!empty($displayData->modificationDate)) { 
                                    ?>
                                        <td class="small d-none d-md-table-cell">
                                            <?php echo date_format(date_create($item->modified),'Y.m.d'); ?>
                                        </td>
                                    <?php
                                    }

                                    // Idioma
                                    if (!empty($displayData->languageColumn)) { 
                                    ?>
                                        <td class="small d-none d-md-table-cell">
                                            <?php echo LayoutHelper::render('joomla.content.language', $item); ?>
                                        </td>
                                        <?php
                                    }

                                    // ID
                                    if (!empty($displayData->recordIdAlias)) { 
                                    ?>
                                        <td class="d-none d-md-table-cell">
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
                    // Paginacao
                    echo $displayData->pagination->getListFooter();
                }
                ?>
                <input type="hidden" name="task" value="" />
                <input type="hidden" name="boxchecked" value="0" />
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>
