<?php
defined('_JEXEC') or die;

use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;

$form         = $displayData['form'];
$fieldsetName = $displayData['fieldset'];
$layouts      = $displayData['layouts'];
$copyRecId    = $displayData['copyRecId'];
$contentId    = $displayData['content_id'];
$client       = $displayData['client'];

HTMLHelper::script('media/com_dpe/js/tjucm.js');
Text::script('COM_TJUCM_ROP_ITEM_FORM_NEXT_DATE_REVIEW_VALIDATION_MESSAGE');
$ucmConfigs = ComponentHelper::getParams('com_tjucm');
$useTooltip = $ucmConfigs->get('enable_custom_tooltip');
// Call js file to update the link to ticket 
HTMLHelper::script('media/com_dpe/js/logsticket.js');
$fieldsets_counter = 0;
$app        = Factory::getApplication();
$app->input->set('extralayout', "rop");
$baseUrl    = $app->input->server->get('REQUEST_URI', '', 'STRING');
$calledFrom = (strpos($baseUrl, 'administrator')) ? 'backend' : 'frontend';
$layouts    = new FileLayout('feedbackformfield', JPATH_SITE . '/templates/shaper_helix3/html/layouts/com_tjucm/form');
Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/tables');
$ucmDataTable = Table::getInstance('data', 'TjucmTable', array('dbo', $db));
$ucmDataTable->load(array('id' => $contentId));
$clusterId = $ucmDataTable->cluster_id;

JLoader::register('TjucmHelpersTjucm', JPATH_SITE . '/components/com_tjucm/helpers/tjucm.php');

JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
$params = DPE::config();
 $genericClusterId = (String) $params->get('cluster_id');

 $user = Factory::getUser();	
 $params     			    = ComponentHelper::getParams('com_multiagency');
 $orgAdmin           		= (int) $params->get('multiagency_school_admin_group', '0', 'INT');
 $orgAdmin 		   			= in_array($orgAdmin, $user->groups);
 $orgStaff           		=  in_array((int) $params->get('member_role_id', '0', 'INT'), $user->groups);

if(($clusterId == $genericClusterId) && ($orgAdmin))
{
	?>

<script>
	jQuery('document').ready(function(){

		jQuery("#jform_com_tjucm_rop_clusterclusterid_chosen .chosen-single").empty();
		jQuery("#jform_com_tjucm_rop_clusterclusterid_chosen .chosen-drop").empty();
		jQuery("input[name='jform[com_tjucm_rop_clusterclusterid]']").val("<?php echo $genericClusterId;?>");
		jQuery('input, :radio, :checkbox, select, textarea').prop('disabled', true);
		jQuery('.chosen-results').css('display', 'none');
		jQuery('.search-choice-close').css('display', 'none');
		jQuery('button').removeAttr('disabled');

	})
</script>

<style>

.chosen-container {
  min-width: 220px;
  max-width: 100%;
}
</style>

<?php }
$fieldArray = [];

// Then safely use the helper
$tjUcmFrontendHelper = new TjucmHelpersTjucm;

if (!$form instanceof JForm || !$fieldsetName) {
    echo '<div class="alert alert-danger">Form is not a JForm object</div>';
    return;
  }
if (!$fieldsetName)
{
	echo '<div class="alert alert-warning">No valid fieldset found.</div>';
	return;
}

$fields = $form->getFieldset($fieldset);

if (empty($fields))
{
	echo '<div class="alert alert-info">No fields found for this tab.</div>';
	return;
}

Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
$tjFieldFieldTable = Table::getInstance('field', 'TjfieldsTable');




?>
<div class="form-horizontal clear-both pull-left pb-10 pt-25 w-100 dp-rop-form row px-3">
<?php
foreach ($form->getFieldset($fieldsetName) as $field)
{

    
    if (!empty($field->getAttribute('tags')))
    {
        $temp     = new TagsHelper;
        $tagnames = $temp->getTagNames(array(
            $field->getAttribute('tags')
        ));

        if (array_key_exists($fieldsArray, (array) $tagnames[0]))
        {
            $fieldArray[$tagnames[0]][] = $field;
        }
        else
         {
            $fieldArray[$tagnames[0]][] = $field;
          }
    }
    else
    {
        $fieldArray[] = $field;
    }

}

foreach ($fieldArray as $key=> $field)
{?>

    <?php
    if (is_array($field))
    {

    $i =0;
    ?>
    <div class="clearfix"></div>
    <div class="accordion" id="accordion<?php echo $i++; ?>"><?php echo  ucwords(str_replace('_', ' ', $key)); ?></div>
    <div id="pan" class="panel row" style="display: none;">
    <?php foreach($field as $fieldTag)
    {
        $description = $fieldTag->description;

        $tjFieldFieldTable->load(array('name' => $fieldTag->fieldname));

        $isUcmsubform = 0;

        if ($field->type == 'Ucmsubform')
        {
            $customColClass = 'col-md-12 ucmsubform';
            if($field->max == 1)
        {
        ?>

        <script>
            jQuery(document).ready(function(){

                jQuery('.btn-toolbar').hide();
            })
        </script>

    <?php }
        }
        elseif (!empty($tjFieldFieldTable->validation_class) && strpos(trim($tjFieldFieldTable->validation_class), 'ucm-full-width') !== false)
        {
            $customColClass = 'col-md-12';
        }
        else
        {
            $customColClass = 'col-md-4';
        }

        if (strpos($fieldTag->class, 'twoColumnUcmsubform') !== false)
        {
            $isUcmsubform   = 0;
        }


        if (!$fieldTag->hidden)
        {
            $className = ($fieldTag->type == 'Spacer') ? 'w-100' : '';

            if ($fieldTag->type == 'Note' && $fieldsetName == 'Legal & Summary')
            {
                echo '<div class="legal-summary-fieldset mb-20 mx-15"><div class="row">';
            }

            if ($fieldTag->type == 'Spacer' && $fieldsetName == 'Legal & Summary')
            {
                echo '</div></div>';
                continue;
            }
        ?>
        <div class="col-xs-12 <?php echo $customColClass . ' ' . $className;  ?> custom-form-style dataflow-tab">
            <div class="form-group">
                <?php
                if($useTooltip)
                {
                    $fieldTag->description = '';
                }

              if ($fieldTag->type != 'Note'): ?>
                            <?php if($useTooltip && $description && $fieldTag->type != 'Ucmsubform'){
                            $fieldTag->description = $description;
                    }?>

                    <?php
                    if ($fieldTag->type != 'Ucmsubform')
                    {
                        echo $fieldTag->renderField(); 
                    }
                    else
                    {
                        ?>
                        <div class="col-sm-12 rop-inputs w-100">
                        <?php echo $fieldTag->input; ?>
                        </div>
                        <?php
                    }
                    ?>
                    
                    <script>
                        <?php if ($fieldTag->type == 'Checkbox'){?>
                            jQuery('#<?php echo $fieldTag->id;?>').addClass('d-block');
                        <?php } ?>
                    </script>
                    
                    <div class="<?php echo 'col-sm-10';?>">
                          <?php 
                                echo $layouts->render($fieldTag);
                           ?>
                    </div>

                    <?php elseif ($fieldTag->type == 'Note' && $fieldsetName == 'Legal & Summary'):

                        switch ($fieldTag->fieldname)
                        {
                            case "com_tjucm_rop_Lawfulbasis":
                                echo '<div class="col-12 custom-form-style "  id="'.$fieldTag->value.'"><h4><strong>Lawful basis</strong></h4></div>';
                                break;
                            case "com_tjucm_rop_DataSubjects":
                                echo '<div class="col-12 custom-form-style "  id="'.$fieldTag->value.'"><h4><strong>'.'Data Subjects'.'</strong></h4></div>';
                                break;
                            case "com_tjucm_rop_Accuracy":
                                echo '<div class="col-12 custom-form-style "  id="'.$fieldTag->value.'"><h4><strong>'.'Accuracy'.'</strong></h4></div>';
                                break;
                            case "com_tjucm_rop_Destination":
                                echo '<div class="col-12 custom-form-style "  id="'.$fieldTag->value.'"><h4><strong>'.'Destination'.'</strong></h4></div>';
                                break;
                            case "com_tjucm_rop_Retention":
                                echo '<div class="col-12 custom-form-style "  id="'.$fieldTag->value.'"><h4><strong>'.'Retention'.'</strong></h4></div>';
                                break;
                            case "com_tjucm_rop_ImpactAssessment":
                                echo '<div class="col-12 custom-form-style "  id="'.$fieldTag->value.'"><h4><strong>'.'Impact Assessment'.'</strong></h4></div>';
                                break;
                            case "com_tjucm_rop_StatusandAttachment":
                                echo '<div class="col-12 custom-form-style "  id="'.$fieldTag->value.'"><h4><strong>'.'Status and Attachment'.'</strong></h4></div>';
                                break;
                            case "com_tjucm_rop_FileUpload":
                                echo '<div class="col-12 custom-form-style "  id="'.$fieldTag->value.'"><h4><strong>'.'File Upload'.'</strong></h4></div>';
                                break;
                        }

                        ?>
                    <?php endif; ?>

                    <?php
                    // TODO :- Check and remove
                    if ($fieldTag->type == 'File')
                    {
                        if ($copyRecId)
                        {
                            $fieldTag->setValue('');
                        }

                        ?>
                        <script type="text/javascript">
                            jQuery(document).ready(function ()
                            {
                                var fieldValue = "<?php echo $fieldTag->value; ?>";
                                var AttrRequired = jQuery('#<?php echo $fieldTag->id;?>').attr('required');
                                if (typeof AttrRequired !== typeof undefined && AttrRequired !== false)
                                {
                                    if (fieldValue)
                                    {
                                        jQuery('#<?php echo $fieldTag->id;?>').removeAttr("required");
                                        jQuery('#<?php echo $fieldTag->id;?>').removeClass("required");
                                    }
                                }
                            });
                        </script>
                    <?php
                    }
                    ?>
<!--
                    <?php //if ($fieldTag->type != 'Note'): ?>
                    <div class="col-sm-12 rop-inputs w-100">
                        <?php //echo $fieldTag->input; ?>
                    </div>
-->
                    
                    <?php //endif; ?>
                    
            </div>
        </div>

    <?php
    }

}?>
</div>
<div class="clearfix"></div>
<div class="clearfix"></div>

<?php
    }?>


    <?php
    if (!is_array($field))
    {?>

    <?php
    $description = $field->description;

    $tjFieldFieldTable->load(array('name' => $field->fieldname));

    $isUcmsubform = 0;

    if ($field->type == 'Ucmsubform')
    {
        $customColClass = 'col-md-12 ucmsubform';
        if($field->max == 1)
        {
        ?>

        <script>
            jQuery(document).ready(function(){

                jQuery('.btn-toolbar').hide();
            })
        </script>

    <?php }
    }
    elseif (!empty($tjFieldFieldTable->validation_class) && strpos(trim($tjFieldFieldTable->validation_class), 'ucm-full-width') !== false)
    {
        $customColClass = 'col-md-12';
    }
    else
    {
        $customColClass = 'col-md-4';
    }

    if (strpos($field->class, 'twoColumnUcmsubform') !== false)
    {
        $isUcmsubform   = 0;
    }


    if (!$field->hidden)
    {
        $className = ($field->type == 'Spacer') ? 'w-100' : '';

        if ($field->type == 'Note' && $fieldsetName == 'Legal & Summary')
        {
            echo '<div class="legal-summary-fieldset mb-20 mx-15"><div class="row">';
        }

        if ($field->type == 'Spacer' && $fieldsetName == 'Legal & Summary')
        {
            echo '</div></div>';
            continue;
        }
    ?>
        <div class="col-xs-12 <?php echo $customColClass . ' ' . $className;  ?> custom-form-style dataflow-tab">
            <div class="form-group">
                    <?php

          if($useTooltip)
        {
            $field->description = '';
        }

      if ($field->type != 'Note'): ?>
                     <?php if($useTooltip && $description && $field->type != 'Ucmsubform'){
                            $field->description = $description;
                    }?>

                    <?php
                    if ($field->type != 'Ucmsubform')
                    {
                        echo $field->renderField(); 
                    }
                    else
                    {
                        ?>
                        <div class="col-sm-12 rop-inputs w-100">
                        <?php echo $field->input; ?>
                        </div>
                        <?php
                    }
                    ?>
                    
                    <script>
                        <?php if ($field->type == 'Checkbox'){?>
                            jQuery('#<?php echo $field->id;?>').addClass('d-block');
                        <?php } ?>
                    </script>
                    
                    <div class="<?php echo 'col-sm-10';?>">
                          <?php 
                                echo $layouts->render($field);
                           ?>
                    </div>

                    <?php elseif ($field->type == 'Note' && $fieldsetName == 'Legal & Summary'):

                        switch ($field->fieldname)
                        {
                            case "com_tjucm_rop_Lawfulbasis":
                                echo '<div class="col-12 custom-form-style "  id="'.$field->value.'"><h4><strong>Lawful basis</strong></h4></div>';
                                break;
                            case "com_tjucm_rop_DataSubjects":
                                echo '<div class="col-12 custom-form-style "  id="'.$field->value.'"><h4><strong>'.'Data Subjects'.'</strong></h4></div>';
                                break;
                            case "com_tjucm_rop_Accuracy":
                                echo '<div class="col-12 custom-form-style "  id="'.$field->value.'"><h4><strong>'.'Accuracy'.'</strong></h4></div>';
                                break;
                            case "com_tjucm_rop_Destination":
                                echo '<div class="col-12 custom-form-style "  id="'.$field->value.'"><h4><strong>'.'Destination'.'</strong></h4></div>';
                                break;
                            case "com_tjucm_rop_Retention":
                                echo '<div class="col-12 custom-form-style "  id="'.$field->value.'"><h4><strong>'.'Retention'.'</strong></h4></div>';
                                break;
                            case "com_tjucm_rop_ImpactAssessment":
                                echo '<div class="col-12 custom-form-style "  id="'.$field->value.'"><h4><strong>'.'Impact Assessment'.'</strong></h4></div>';
                                break;
                            case "com_tjucm_rop_StatusandAttachment":
                                echo '<div class="col-12 custom-form-style "  id="'.$field->value.'"><h4><strong>'.'Status and Attachment'.'</strong></h4></div>';
                                break;
                            case "com_tjucm_rop_FileUpload":
                                echo '<div class="col-12 custom-form-style "  id="'.$fieldTag->value.'"><h4><strong>'.'File Upload'.'</strong></h4></div>';
                                break;
                        }

                        ?>
                    <?php endif; ?>

                    <?php
                    // TODO :- Check and remove
                    if ($field->type == 'File')
                    {
                        if ($copyRecId)
                        {
                            $field->setValue('');
                        }

                        ?>
                        <script type="text/javascript">
                            jQuery(document).ready(function ()
                            {
                                var fieldValue = "<?php echo $field->value; ?>";
                                var AttrRequired = jQuery('#<?php echo $field->id;?>').attr('required');
                                if (typeof AttrRequired !== typeof undefined && AttrRequired !== false)
                                {
                                    if (fieldValue)
                                    {
                                        jQuery('#<?php echo $field->id;?>').removeAttr("required");
                                        jQuery('#<?php echo $field->id;?>').removeClass("required");
                                    }
                                }
                            });
                        </script>
                    <?php
                    }
                    ?>
<!--
                    <?php //if ($field->type != 'Note'): ?>
                    <div class="col-sm-12 rop-inputs w-100">
                        <?php //echo $field->input; ?>
                    </div>
-->
                    
                    <?php //endif; ?>
                    
            </div>
        </div>

    <?php
    }
}
}




?>


<?php
// DPE - Hack - To copy the record
if ($copyRecId)
{
?>
<script type="text/javascript">
	jQuery(document).ready(function ()
	{
		// Check record id is empty and user tried to copy record
		if (jQuery.trim(jQuery('#recordId').val()) == '' || jQuery('#recordId').val() == undefined)
		{
			// Find the all parent contentid fields of subforms
			jQuery('.ucmsubform').find("input[name*='_contentid']").each(function(){
				// Check the field type is hidden and confirm its parent reference number
				if (jQuery(this).attr('type') == 'hidden')
				{
					// Reset the field value if trying to copy the record
					jQuery(this).val('');
				}
			});
		}
	});
</script>
<?php
}

?>

<script>

jQuery(document).off('click', '.group-add');
jQuery(document).on('subform-row-add', function (evt, row) {
  var $row = jQuery(evt && evt.detail && evt.detail.row ? evt.detail.row : row);

  if (!$row || !$row.length) {
    return;
  }

  // Defer to ensure the row is fully attached and visible enough for width calc
  setTimeout(function () {
    if (jQuery().chosen) {
      $row.find('select').each(function () {
        var $s = jQuery(this);
        // Remove any existing chosen container next to this select
        if ($s.next('.chosen-container').length) {
          $s.next('.chosen-container').remove();
        }
        // Clear previous plugin data and re-init
        $s.removeData('chosen');
        $s.chosen({ width: '100%' }).trigger('chosen:updated');
      });
    }
  }, 0);
});
jQuery(function ($) {
  var $root = $('.dp-rop-form').last();

  function loadScriptOnce(src) {
    return new Promise(function (resolve) {
      if (document.querySelector('script[src="' + src + '"]')) return resolve();
      var s = document.createElement('script');
      s.src = src;
      s.async = true;
      s.onload = resolve;
      document.head.appendChild(s);
    });
  }

  function ensureCalendarAssets() {
    var hasCalendar = window.JoomlaCalendar && typeof window.JoomlaCalendar.init === 'function';
    var hasDateHelper = window.Date && typeof window.Date.parseFieldDate === 'function';
    if (hasCalendar && hasDateHelper) return Promise.resolve();

    var root = (window.Joomla?.getOptions?.('system.paths')?.root)
      || document.querySelector('base[href]')?.getAttribute('href')
      || '/';
    if (!root.endsWith('/')) root += '/';

    var promises = [];
    if (!hasDateHelper) promises.push(loadScriptOnce(root + 'media/system/js/fields/calendar-locales/date/gregorian/date-helper.min.js'));
    if (!hasCalendar) promises.push(loadScriptOnce(root + 'media/system/js/fields/calendar.min.js'));

    return Promise.all(promises);
  }

  function initCalendars(scope) {
    $(scope).find('button[data-inputfield]').each(function () {
      var $btn = $(this);
      var $wrap = $btn.closest('.field-calendar');
      if (!$wrap.length) {
        $wrap = $btn.closest('.input-append').addClass('field-calendar');
      }

      if (!$wrap.data('dpcalInit') && $wrap.find('> .calendar-container').length === 0) {
        try {
          window.JoomlaCalendar.init($wrap[0]);
          $wrap.data('dpcalInit', 1);
        } catch (e) {}
      }
    }).off('mousedown.dpcal').on('mousedown.dpcal', function () {
      var $wrap = $(this).closest('.field-calendar');
      if (!$wrap.data('dpcalInit') && !$wrap.find('> .calendar-container').length) {
        try {
          window.JoomlaCalendar.init($wrap[0]);
          $wrap.data('dpcalInit', 1);
        } catch (e) {}
      }
    });
  }

  ensureCalendarAssets().then(function () {
    initCalendars($root);
  });

  if ($.fn.chosen) {
    $root.find('select').each(function () {
      if (!$(this).closest('.calendar-container').length && !$(this).next('.chosen-container').length) {
        $(this).chosen();
      }
    });
  }
});
</script>
