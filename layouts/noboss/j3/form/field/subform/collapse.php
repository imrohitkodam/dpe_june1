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
 * Make thing clear
 *
 * @var JForm   $tmpl             The Empty form for template
 * @var array   $forms            Array of JForm instances for render the rows
 * @var bool    $multiple         The multiple state for the form field
 * @var int     $min              Count of minimum repeating in multiple mode
 * @var int     $max              Count of maximum repeating in multiple mode
 * @var string  $fieldname        The field name
 * @var string  $control          The forms control
 * @var string  $label            The field label
 * @var string  $description      The field description
 * @var array   $buttons          Array of the buttons that will be rendered
 * @var bool    $groupByFieldset  Whether group the subform fields by it`s fieldset
 */
extract($displayData);

// pega o documento
$doc = JFactory::getDocument();

// Versoes anteriores ao 3.9.0: não possuem $unique_subform_id
if(version_compare(JVERSION, '3.9.0', '<')){
    $unique_subform_id = 'nbversionold';
}

// Add script
if ($multiple)
{
    JHtml::_('jquery.ui', array('core', 'sortable'));
    
    if(version_compare(JVERSION, '3.8.0', '<=')){
        $doc->addScript(JURI::base()."../media/system/js/subform-repeatable.js");
    }else{
        JHtml::_('script', 'system/subform-repeatable.js', array('version' => 'auto', 'relative' => true));
    }
}

// adiciona o js do campo
$doc->addScript(JURI::base()."../libraries/noboss/forms/fields/assets/js/min/nobosssubform.min.js");
// adiciona o css do campo
$doc->addStylesheet(JURI::base()."../libraries/noboss/forms/fields/assets/stylesheets/css/nobosssubform.min.css");
$doc->addStylesheet(JURI::base()."../libraries/noboss/assets/plugins/stylesheets/css/material-icons.css");

$sublayout = empty($groupByFieldset) ? 'section' : 'section-byfieldsets';
//$sublayout = empty($groupByFieldset) ? 'section' : 'section-byfieldsets';

$identifier = $displayData['field']->identifier;
$htmlButtons = $displayData['field']->htmlButtons;
?>

<div class="row-fluid" data-subform-collapse-wrapper>
	<div class="subform-repeatable-wrapper subform-layout">
		<div class="subform-repeatable"
            data-bt-add="a.group-add-<?php echo $unique_subform_id; ?>"
            data-bt-remove="a.group-remove-<?php echo $unique_subform_id; ?>"
            data-bt-move="a.group-move-<?php echo $unique_subform_id; ?>"
            data-repeatable-element="div.subform-repeatable-group-<?php echo $unique_subform_id; ?>"
            data-minimum="<?php echo $min; ?>"
            data-maximum="<?php echo $max; ?>">

			<?php if (!empty($buttons['add'])) : ?>
			<div class="btn-toolbar">
				<div class="btn-group">
                    <a class="btn btn-mini button btn-success group-add group-add-<?php echo $unique_subform_id;?>" aria-label="<?php echo JText::_('JGLOBAL_FIELD_ADD'); ?>">
                        <span class="icon-plus" aria-hidden="true"></span>
                    </a>
                    <?php echo $htmlButtons; ?>
				</div>
			</div>
			<?php endif; ?>

        <?php
        $fieldIsEditor = false;
        foreach ($forms as $k => $form) :

            $formData = $form->getData();
            $formDataArray = $formData->toArray();

            //extrai o valor do identificador que vem como objeto
            if($identifier !== null){
                $identifier = (array) $identifier;
                $identifier = implode('', $identifier);
            }

            //caso o identificador nao seja valido
            if(!$formData->exists($identifier) && $identifier !== "none"){
                //pega os fields
                $fieldset = $form->getFieldset();
                //para cada field verifica se o tipo eh text ou textarea
                $defaultField = array_filter($fieldset, function($field){
                    //caso seja, retorna o field
                    if(strtolower($field->type) == 'text' || strtolower($field->type) == 'textarea' || strtolower($field->type) == 'nobosseditor'){
                        return $field;
                    }
                });

                //caso nao tenha sido possivel achar um campo de texto
                if(!$defaultField){
                    //seta o identifier como none
                    $identifier = 'none';
                }else{
                    //pega o name do elemento que servira como default
                    $identifier = reset($defaultField)->getAttribute('name');
                }
            }else{
                if($identifier !== "none"){
                    $fieldIsEditor = $form->getField($identifier)->type === 'nobosseditor';
                }
            }          
            
            echo $this->sublayout(
				$sublayout,
				array(
					'form' => $form,
					'basegroup' => $fieldname,
					'group' => $fieldname . $k,
					'buttons' => $buttons,
					'unique_subform_id' => $unique_subform_id,
				)
			);
        endforeach;
        ?>
        
        <?php if ($multiple) : ?>
            <?php
            // Versoes anteriores ao 3.9.0: script chamado de forma diferente
            if(version_compare(JVERSION, '3.9.0', '<')){
                ?>
                <script type="text/subform-repeatable-template-section" class="subform-repeatable-template-section">
            
                    <?php echo $this->sublayout(
                            $sublayout, 
                            array(
                                'form' => $tmpl,
                                'basegroup' => $fieldname,
                                'group' => $fieldname . 'X',
                                'buttons' => $buttons,
                                'unique_subform_id' => $unique_subform_id,
                                )
                            ); 
                    ?>
                </script>
                <?php
            }
            // Versao superior ou igual a 3.9.0
            else{
            ?>
                <template class="subform-repeatable-template-section">
                    <?php echo trim(
                        $this->sublayout(
                            $sublayout,
                            array(
                                'form' => $tmpl,
                                'basegroup' => $fieldname,
                                'group' => $fieldname . 'X',
                                'buttons' => $buttons,
                                'unique_subform_id' => $unique_subform_id,
                            )
                        )
                    ); ?>
                </template>
            <?php
            }
            ?>           

		<?php endif; ?>
		</div>
    </div>
    <span class='hidden' data-is-editor="<?php echo $fieldIsEditor?>" data-collapse-label='<?php echo $identifier;?>' data-collapse-default-value='<?php echo JText::_('LIB_NOBOSS_FIELD_NOBOSSSUBFORM_COLLAPSE_DEFAULT_VALUE_TEXT'); ?>'></span>
</div>
