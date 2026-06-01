<?php
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

?>

<div class="format_types tjBs3">
<?php
$lesson_formats_array = array('scorm','htmlzips','tincanlrs','video','document','textmedia','externaltool','event', 'survey','form', 'quiz','exercise','feedback');

$quizFormatsArray = array('quiz','exercise','feedback');


$ind = 0;
foreach($lesson_formats_array as $formatName)
{
	/* Check if any of the plugin of provided lesson format is enabled*/
	$plugformat = 'tj' . $formatName;
	PluginHelper::importPlugin($plugformat);

	// Call the plugin and get the result
	$results = Factory::getApplication()->triggerEvent('onGetSubFormat_tj' . $formatName . 'ContentInfo');

		if(!empty($results))
		{
			if(empty($formattoset))
			{
				$formattoset = $formatName;
			}

			$allowToAddexisting = isset($results[0]['allow_to_add_existing']) ? $results[0]['allow_to_add_existing'] : 0;
			
			$temp = " href=";
			$onClick = "";
	
			if ($allowToAddexisting)
			{
				//$onClickSelectLink = $temp . "'index.php?option=com_tmt&view=tests&tmpl=component&gradingtype=" . $formatName . "&insertin=" . $this->insertIn . "'";
				
				//$onClickCreateLink = $temp . "'index.php?option=com_tmt&view=test&layout=edit&tmpl=component&gradingtype=" . $formatName . "&insertin=" . $this->insertIn . "'";
			}
			else 
			{
				if (!in_array($formatName, $quizFormatsArray))
				{
					$onClick = $temp . "'index.php?option=com_tjlms&view=lesson&layout=edit&ptype=" . $formatName . "&cid=" . $this->cid . "&mid=" . $this->mid . "'";
				}
				else
				{
					$onClick = $temp . "'index.php?option=com_tmt&task=test.edit&gradingtype=" . $formatName . "&cid=" . $this->cid . "&mid=" . $this->mid . "'";
				}
			
			}
		?>
			<?php if ($ind == 0):?>
			<div class="row">
			<?php endif;?>
				<div class="col-md-4 text-center my-15">
					<a <?php echo $onClick;?> class="bg-rep d-inline-block lecture-icons <?php echo $formatName ?>" data-type="<?php echo ucfirst($formatName)?>">

						<?php
							$var = strtoupper($formatName);
							$lang_var = "COM_TJLMS_" . $var . "_LESSON";
						?>

						<span><?php echo  Text::_($lang_var); ?></span>
					</a>
				</div>
			<?php if ($ind == 2):?>
				<?php $ind = 0; ?>
			</div>
			<?php else : ?>
				<?php $ind++; ?>
			<?php endif;?>
		

	<?php } ?>
<?php } ?>
</div>
