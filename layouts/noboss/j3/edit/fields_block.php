<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	Layouts
 * @author			No Boss Technology <contato@noboss.com.br>
 * @copyright		Copyright (C) 2021 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

defined('_JEXEC') or die;

/* Layout para exibir os campos de um fiedset por blocos
    - Para utilizaresse layout, voce precisa chamar o codigo abaixo dentro do foreach que percorre os fieldsets a exibir
        // Fieldset esta setado para ser exibido por blocos
        if((!empty($fieldSet->breakblock)) && ($fieldSet->breakblock == 1)){
            echo LayoutHelper::render('noboss.j3.edit.fields_block', array('form' => $this->form, 'fieldset' => $fieldSet, 'viewName' => $displayData->viewName));
            continue;
        }
    - Para funcionar corretamente a exibicao por blocos, alem de chamar o codigo acima, eh necessario:
        * Adicionar o atributo breakblock="1" na declaracao do fieldset no xml
        * Colocar no xml um campo note antes do primeiro campo de cada bloco
        * Colocar no xml um campo 'spacer' sempre que desejar fechar um bloco e iniciar outro
*/

// Inicia aba
echo JHtml::_('bootstrap.addTab', $displayData['viewName'], $displayData['fieldset']->name, JText::_($displayData['fieldset']->label), true);

// Carrega description da aba (caso tenha)
echo JLayoutHelper::render('noboss.edit.component_description', $displayData['fieldset']->description);

?>
<div class="row-fluid fields_block">
    <div class="span6">
        <?php
        // Percorre os fields
        foreach ($displayData['form']->getFieldSet($displayData['fieldset']->name) as $field){
            // Field spacer: fecha bloco atual e abre novo
            if($field->getAttribute("type") == 'spacer'){
                ?>
                </div>
                <div class="span6">
                <?php
            }
            // Field normal: exibe o field
            else{
                echo $field->renderField();
            }
        }
        ?>                           
    </div>
</div>
<?php

// Encerra aba
echo JHtml::_('bootstrap.endTab');
