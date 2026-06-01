<?php
/**    
 * SEO Glossary Glossary view
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

// No direct access
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('behavior.multiselect');
JHtml::_('dropdown.init');


JHTML::_('script', 'system/multiselect.js', false, true);
JHtml::_('jquery.framework');
$document = JFactory::getDocument();
$document->addStyleSheet(JURI::root() . '/components/com_seoglossary/assets/css/materialize.css');

?>
<style type="text/css">
	#loading {
		background: rgba(255, 255, 255, .8) url('<?php echo JURI::base();?>/components/com_seoglossary/assets/ajax-loader.gif') 50% 15% no-repeat;
		position: fixed;
		opacity: 0.8;
		-ms-filter: progid:DXImageTransform.Microsoft.Alpha(Opacity = 80);
		filter: alpha(opacity = 80);
	}
	 .btn.btn-primary.btn-large
	{
      margin-top:10px;
   }
</style>
<?php if (!empty($this->sidebar)): ?>
        <div id="j-sidebar-container" class="span2">
        <?php echo $this->sidebar; ?>
        </div>
        <div id="j-main-container" class="span10">
        <?php else : ?>
            <div id="j-main-container">
        <?php endif; ?>

<div class="row-fluid row" id="ju-form">
	<div class="span4 col-md-4">
		<h1>Export</h1>
		<form enctype="multipart/form-data" id="exportadminForm" name="adminForm" method="post" action="index.php">
			
		    <p><button class="btn btn-primary btn-large" type="submit">Export</button></p>
		    <input type="hidden" value="export_csv" name="task">
		    <input type="hidden" value="com_seoglossary" name="option">
		</form>
	
	</div>
	<div class="span8 col-md-8">
		<h1>CSV Import</h1>
		
		<p>Select your <i>.csv</i> file and click "Import" button below to start the import process. Here's a sample file <a href="#" onclick="document.getElementById('exportadminForm').submit();">to download</a></p>
		
		<div id="loading"></div>
	<form enctype="multipart/form-data" id="csvForm" name="csvForm" method="post" action="index.php">
	    <hr>
		CSV File: <input type="file" name="file_csv"  class="form-control required valid form-control-success" aria-describedby="file_csv" required="" >
                <p> <fieldset id="jform_operation_type" class="radio btn-group btn-group-yesno" ><input type="radio" id="jform_operation_type0" name="operation" value="0" checked="checked"/><label for="jform_operation_type0" >Insert</label><input type="radio" id="jform_operation_type1" name="operation" value="1" /><label for="jform_operation_type1" >Insert/Update</label></fieldset></p>
    	<p><button class="btn btn-primary btn-large" type="submit">Import</button></p>
	<input type="hidden" value="import_csv" name="task">
	<input type="hidden" value="com_seoglossary" name="option">
	</form>
	
</div>


	</div>