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

// Add script
if ($multiple)
{
    JHtml::_('jquery.ui', array('core', 'sortable'));
    
    if(version_compare(JVERSION, '3.8.0', '<=')){
        $doc->addScript(JURI::root()."media/system/js/subform-repeatable.js");
    }else{
        JHtml::_('script', 'system/subform-repeatable.js', array('version' => 'auto', 'relative' => true));
    }
}

// adiciona o css do campo
$doc->addStylesheet(JURI::root()."/libraries/noboss/forms/fields/assets/stylesheets/css/nobosssubform.min.css");

$sublayout = 'section';

?>

<div class="row-fluid" data-subform-single-wrapper>
	<div class="subform-single-wrapper subform-layout">
        <?php
        foreach ($forms as $k => $form) :
			echo $this->sublayout($sublayout, array('form' => $form, 'basegroup' => $fieldname, 'group' => $fieldname . $k, 'buttons' => $buttons)); 
        endforeach;
		?>
		<?php if ($multiple) : ?>
		<script type="text/subform-repeatable-template-section" class="subform-repeatable-template-section">
		<?php echo $this->sublayout($sublayout, array('form' => $tmpl, 'basegroup' => $fieldname, 'group' => $fieldname . 'X', 'buttons' => $buttons)); ?>
		</script>
		<?php endif; ?>
    </div>
</div>
