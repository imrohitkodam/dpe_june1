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
 * Layout tradicional para utilizar como tmpl de views de edicao de registros
 * 
 * ORIENTACOES DE USO:
 * 
 * Para chamar esse layout, utilize o codigo abaixo:
 *      echo JLayoutHelper::render('noboss.j3.edit.traditional', $this);
 * 
 * No arquivo de view siga o modelo da No Boss para garantir que tenha todas variaveis declaradas que sao necessarias para o funcionamento deste modelo de tmpl.
 * 
 * Mais informacoes podem ser obtidas em (TODO: colocar link aqui)
 */

JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');

$document = JFactory::getDocument();

// Obtem o formulario
$form = $displayData->getForm();

// Scripts de validacao do form
?>
<script type="text/javascript">
    Joomla.submitbutton = function(task)
    {
        if (task == '<?php echo $displayData->viewName; ?>.cancel' || document.formvalidator.isValid(document.id('form-<?php echo $displayData->viewName; ?>'))) {
            Joomla.submitform(task, document.getElementById('form-<?php echo $displayData->viewName; ?>'));
        }else{
            return false;
        }

    }
</script>

<form name="<?php echo "form-" . $displayData->viewName; ?>" id="<?php echo "form-" . $displayData->viewName; ?>" class="form-validate" method="post" action="<?php echo JRoute::_($displayData->actionForm); ?>" enctype="multipart/form-data">
    <?php
    // Exibe campos de titulo e alias
    echo JLayoutHelper::render('noboss.edit.title_alias', $displayData);
    ?>

    <div class="form-horizontal">
		<?php
        echo JHtml::_('bootstrap.startTabSet',  $displayData->viewName, array('active' => $displayData->defaultFieldSetName)); 
        
        // Renderiza os campos que nao devem ser exibidos e que estao no fieldset 'hidden'
        echo '<div style="display: none;">'.$form->renderFieldset('hidden').'</div>';

        // Percorre todos fieldsets
        foreach ($form->getFieldsets() as $fieldSet):
            // Fieldset esta entre os a serem ignorados na exibicao automatica
            if(in_array($fieldSet->name, $displayData->fieldsetsIgnore)){
                continue;
            }

            // Fieldset esta setado para ser exibido por blocos
            if((!empty($fieldSet->breakblock)) && ($fieldSet->breakblock == 1)){
                echo JLayoutHelper::render('noboss.j3.edit.fields_block', array('form' => $form, 'fieldset' => $fieldSet, 'viewName' => $displayData->viewName));
                continue;
            }

            // Inicia aba
            echo JHtml::_('bootstrap.addTab', $displayData->viewName, $fieldSet->name, JText::_($fieldSet->label), true);
            ?>
            
            <?php // Se for a aba default, declara classe span9 p/ poder exibir coluna de detalhes na direita. Demais casos declara como span12 que ocupa toda largura ?>
            <div class="span<?php echo (($displayData->defaultFieldSetName == $fieldSet->name) && (!empty($form->getFieldSet('details')))) ? '9' : '12'; ?>">
                <?php
                // Carrega description da aba (caso tenha)
                echo JLayoutHelper::render('noboss.edit.component_description', $fieldSet->description);

                // Percorre cada field
                foreach ($form->getFieldSet($fieldSet->name) as $field){
                    // Eh field de 'Atribuicao de menus' de modulo
                    if(!empty($field->getAttribute("module"))){
                        // Field de selecao de menus
                        if($field->getAttribute("module") == 'assigment'){
                            // Obtem o nome do campo p/ usar em seguida no proximo field
                            $assignmentName = $field->getAttribute("name");
                            continue;
                        } 
                        // Field que guarda os menus selecionados
                        elseif($field->getAttribute("module") == 'assigned'){
                            // Obtem o nome do campo
                            $assignedName = $field->getAttribute("name");
                            $item = $displayData->get('Item');
                            // Carrega arquivo que ira exibir o campo
                            require JPATH_ROOT."/libraries/noboss/forms/fields/nobossmodulesposition/assignment.php";
                            continue;
                        }
                    }

                    // Carrega o campo
                    echo $field->renderField();
                }

                ?>
            </div>
            <?php
            // Aba default: carrega coluna na direita com campos de detalhes
            if (($displayData->defaultFieldSetName == $fieldSet->name) && (!empty($form->getFieldSet('details')))){
                ?>  
                <div class="span3">
                    <fieldset class="form-vertical">
                        <div class="control-group">
                            <?php
                                // Carrega campos do fieldset details
                                echo $form->renderFieldset('details');
                            ?>
                        </div>
                    </fieldset>
                </div>
                <?php
            }
            ?>

            <?php
            // Encerra aba
            echo JHtml::_('bootstrap.endTab');
        endforeach;
             
        echo JHtml::_('bootstrap.endTabSet'); 
        ?>

        <input type="hidden" name="task" value="" />
        <input type="hidden" name="return" value="<?php echo JFactory::getApplication()->input->get('return', '', 'cmd');?>" />
        <?php echo JHtml::_('form.token'); ?>
    </div>
</form>
