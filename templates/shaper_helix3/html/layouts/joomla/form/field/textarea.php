<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
extract($displayData);
/**
 * Layout variables
 * -----------------
 * @var   string   $name            Name of the input field.
 * @var   string   $id              DOM id of the field.
 * @var   string   $value           Value attribute of the field.
 */
if ($charcounter) {
    // Load the js file
    /** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
    $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
    $wa->useScript('short-and-sweet');
    // Set the css class to be used as the trigger
    $charcounter = ' charcount';
    // Set the text
    $counterlabel = 'data-counter-label="' . $this->escape(Text::_('JFIELD_META_DESCRIPTION_COUNTER')) . '"';
}
$attributes = [
    $columns ?: '',
    $rows ?: '',
    !empty($class) ? 'class="form-control ' . $class . $charcounter . '"' : 'class="form-control' . $charcounter . '"',
    !empty($description) ? 'aria-describedby="' . ($id ?: $name) . '-desc"' : '',
    strlen($hint) ? 'placeholder="' . htmlspecialchars($hint, ENT_COMPAT, 'UTF-8') . '"' : '',
    $disabled ? 'disabled' : '',
    $readonly ? 'readonly' : '',
    $onchange ? 'onchange="' . $onchange . '"' : '',
    $onclick ? 'onclick="' . $onclick . '"' : '',
    $required ? 'required' : '',
    !empty($autocomplete) ? 'autocomplete="' . $autocomplete . '"' : '',
    $autofocus ? 'autofocus' : '',
    $spellcheck ? '' : 'spellcheck="false"',
    $maxlength ?: '',
    !empty($counterlabel) ? $counterlabel : '',
    $dataAttribute,
];
?>


<?php

// Extract field name if it's within brackets
if (strpos($name, 'jform[') !== false) {
        // Use regex to extract all bracketed values
    preg_match_all('/\[(.*?)\]/', $name, $matches);
        // Get the last extracted value
    if (!empty($matches[1])) {
            $fieldName= end($matches[1]); // Returns the last part inside brackets
        }
    }

    Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
    $getFieldId = Table::getInstance('field', 'TjfieldsTable');
    $getFieldId->load(array('name' => $fieldName));
    $type=$getFieldId->type;
    $fieldConditonValues = json_decode($getFieldId->params); 
    ?>
    
    <?php if($fieldConditonValues->texteditmode == 1)
    { ?>
        <div class="editor-container">
            <div class="editor-toolbar d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-light btn-sm" onclick="formatText('undo', this)"><i class="fas fa-undo"></i></button>
                <button type="button" class="btn btn-light btn-sm" onclick="formatText('redo', this)"><i class="fas fa-redo"></i></button>
                <button type="button" class="btn btn-light btn-sm" onclick="formatText('bold', this)"><i class="fas fa-bold"></i></button>
                <button type="button" class="btn btn-light btn-sm" onclick="formatText('italic', this)"><i class="fas fa-italic"></i></button>
                <button type="button" class="btn btn-light btn-sm" onclick="formatText('underline', this)"><i class="fas fa-underline"></i></button>
                <button type="button" class="btn btn-light btn-sm" onclick="formatText('justifyLeft', this)"><i class="fas fa-align-left"></i></button>
                <button type="button" class="btn btn-light btn-sm" onclick="formatText('justifyCenter', this)"><i class="fas fa-align-center"></i></button>
                <button type="button" class="btn btn-light btn-sm" onclick="formatText('justifyRight', this)"><i class="fas fa-align-right"></i></button>
                <button type="button" class="btn btn-light btn-sm" onclick="formatText('insertUnorderedList', this)"><i class="fas fa-list-ul"></i></button>
                <button type="button" class="btn btn-light btn-sm" onclick="formatText('insertOrderedList', this)"><i class="fas fa-list-ol"></i></button>
            </div>

            <div id="editor-<?php echo $id; ?>" class="editor-content" contenteditable="true" data-id="<?php echo $id; ?>" 
                <?php echo is_array($attributes) ? implode(' ', $attributes) : $attributes; ?>>
                <?php echo is_array($value) ? implode(' ', $value) : $value; ?>
            </div>
        </div>
    <?php } ?>

<!-- Hidden textarea to store the final value before submission -->
<textarea name="<?php echo $name; ?>" id="<?php echo $id; ?>" <?php
if (is_array($value)) {
    $value = implode(' ', $value);
}
echo is_array($attributes) ? implode(' ', $attributes) : $attributes; ?>
style="<?php echo ($fieldConditonValues->texteditmode == 1)?'display:none;':'';?>">
<?php echo htmlspecialchars($value, ENT_COMPAT, 'UTF-8'); ?>
</textarea>

<script>

// Function to apply formatting (e.g., bold, italic, underline) to the editor content
function formatText(command, button) 
{
    let editor = button.closest(".editor-container").querySelector(".editor-content");

    if (editor) {
       document.execCommand(command, false, null); // Apply formatting
       syncEditorToTextarea(editor); // Sync content after formatting
    }
}

// Function to synchronize editor content with the hidden textarea
function syncEditorToTextarea(editor) 
{
    let nextTextarea = editor.closest(".editor-container").nextElementSibling;
    if (nextTextarea && nextTextarea.tagName.toLowerCase() === "textarea") {
        nextTextarea.value = editor.innerHTML; // Sync content
    }
}


// Event listener to update the hidden textarea whenever text is typed in the editor
document.addEventListener("keyup", function (event) 
{
    let editor = event.target.closest(".editor-content");

    if (editor) {
        let nextTextarea = editor.closest(".editor-container").nextElementSibling;
        if (nextTextarea && nextTextarea.tagName.toLowerCase() === "textarea") {
                nextTextarea.value = editor.innerHTML; // Sync the content
            }
        }

});

</script>