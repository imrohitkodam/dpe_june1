<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;



HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('formbehavior.chosen', 'select');

$k = array();
$i = 1;

$model          = $this->getModel();
$qryConcat      = $this->qryConcat;
$option         = $this->defaultoption;
$defaultSetting = $this->defaultSetting;
$altid          = $this->altid;
?>
<style>
	input[type="radio"] {
  display: none;
}
</style>
<?php
// $allow_user_select_plugin = $this->allow_user_select_plugin;
// For loop for alert types
for ($s = 0; $s < count($qryConcat); $s++)
{
	$pluginData = $model->getPluginData($qryConcat[$s]);

	// Checking alert id with default selected alert for checking checkbox
	// Changed in 2.4.3 //if(in_array($altid[$s],$option))
	if (isset($option[$altid[$s]]) && isset($option[$altid[$s]]['state']) && $option[$altid[$s]]['state'] == 1)
	{
		$pluginName        = $model->getData($altid[$s]);
		$alertchk          = "checked";
		$bstyle            = 'class = "subscribed_alert"';
		$subStatusMsg      = Text   :: _('JMA_UNCHECK_SUB_MSG');
		$showPlugins       = "block";
		$checkHiddenPlugin = 0;
	}
	else
	{
		$pluginName        = $model->getData($altid[$s]);
		$alertchk          = "";
		$bstyle            = 'class = "unsubscribed_alert"';
		$subStatusMsg      = Text   :: _('JMA_CHECK_SUB_MSG');
		$showPlugins       = 'none';
		$checkHiddenPlugin = 1;
	}

	// Get frequency
	$altdata = $model->getFreq($altid[$s]);

	$allowuser = "display";

	echo '
	<div>
		<div class="well">
			<div class="control-group">
				<label for="alert_' . $altid[$s] . '">
					<input type="checkbox"
						name="alt[]"
						id="alert_' . $altid[$s] . '"
						value="' . $altid[$s] . '"
						onclick="divhide(this);" ' . $alertchk . ' />
					<strong ' . $bstyle . '>' . $altdata[1] . '</strong>
					<span class="sub_status_msg">' . $subStatusMsg . '</span>
				</label>
			</div>

			<div>
				<div class="jma_alert_desc">' . $altdata[2] . '</div>
				<div id="' . $altid[$s] . '" style="display:' . $showPlugins . '">
					<div style="display:' . $allowuser . '">
						<div class="control-group">
							<div class="alert_frequncy control-label">
								<label for="c' . $altid[$s] . '"><strong>' . Text::_("CURRENT_SETTING") . '</strong></label>
							</div>

							<div class="controls">' .
								$altdata[0] .
							'</div>
						</div>';

						// Let users set plugin settings too
						if ($pluginData != false)
						{
							foreach ($pluginData as $singlePluginName)
							{
								$plugtitleparm = explode(':', $singlePluginName->params);
								$plugtitltlex  = explode(',', $plugtitleparm[1]);
								$plugtitle     = str_replace('"', '', $plugtitltlex[0]);
								$flag          = 0;

								if (!empty($pluginName))
								{
									foreach ($pluginName as $key => $v)
									{
										if ($singlePluginName->element == $key)
										{
											$params = implode("\n", $v);
											$params = str_replace(',', '|', $params);
											$disp   = '';
											$chk    = $v[count($v) - 1];

											if ($chk == "checked=''")
											{
												$checked = '';
											}
											else
											{
												$checked = "checked";
											}

											$flag = 1;
											break;
										}
										else
										{
											$disp    = 'style="display:none"';
											$flag    = 0;
											$checked = "";
										}
									}
								}

								if ($flag == 1)
								{
									if (!in_array($singlePluginName->element, $k))
									{
										$k[] = $singlePluginName->element;
										?>

										<div class="jmail-blocks">
											<div class="well jma_plugin_background">
												<?php
												if ($checkHiddenPlugin)
												{
													$checked = "checked";
												}

												echo '
												<div class="jma_alert" >
													<div class="control-group">
														<label for="plg_' . $singlePluginName->element . '_' . $altid[$s] . '">
															<input type="checkbox" name="ch' . $altid[$s] . '[]"
																id="plg_' . $singlePluginName->element . '_' . $altid[$s] . '"
																value="' . $singlePluginName->element . '_' . $altid[$s] . '" onclick="divhide(this);" ' . $checked . '/>
															<strong>' . Text::_($plugtitle) . '</strong>
														</label>
													</div>
												</div>';

												if ($altdata[3] == 1)
												{
												?>
												<div class="jmail-expands" id="<?php echo $singlePluginName->element . '_' . $altid[$s] . $disp ?>">
													<?php
													$form = null;
													$formPath = JPATH_SITE . DS . 'plugins' . DS . 'emailalerts' . DS . $singlePluginName->element . DS . $singlePluginName->element . DS . 'form' . DS . 'form_' . $singlePluginName->element . '.xml';
													$test = $singlePluginName->element . '_' . $altid[$s];

													$form = Form::getInstance($test, $formPath);
													$params = explode("\n", $params);

													foreach ($params as $param)
													{
														$par      = explode('=', $param);
														$parName = $par[0];
														$parVal  = $par[1];

														if (strpos($parVal, '|'))
														{
															$arrayParVal                                  = explode('|', $parVal);
															$array[$singlePluginName->element][$parName] = $arrayParVal;
														}
														else
														{
															$array[$singlePluginName->element][$parName] = $parVal;
														}
													}

													$form->bind($array);

													// Iterate through the form fieldsets and display each one.
													foreach ($form->getFieldsets() as $fieldset)
													{
														$fields = '';
														$fields = $form->getFieldset($fieldset->name);

														if (count($fields))
														{
														?>
															<fieldset>
																<?php
																// If the fieldset has a label set, display it as the legend.
																if (isset($fieldset->label))
																{
																?>
																	<legend>
																	<?php
																		echo Text::_($fieldset->label);
																	?>
																	</legend>
																<?php }?>

																	<?php
																	// Iterate through the fields in the set and display them.
																	foreach ($fields as $field)
																	{
																	?>
																		<div class="control-group">
																			<?php
																			// If the field is hidden, just display the input.
																			if ($field->hidden)
																			{
																				$in = str_replace($singlePluginName->element, $test, $field->input);
																				echo $in;
																			}
																			else
																			{
																				?>
																				<div>
																					<?php 
																					if (!$field->required && (!$field->type == "spacer"))
																					{
																						?>
																						<span class="optional">
																							<?php echo Text::_('COM_USERS_OPTIONAL');?>
																						</span>
																						<?php
																					}

																					// Use render field so that showon attribute can work
																					echo  str_replace($singlePluginName->element, $test, $field->renderField());
																					?>
																				</div>
																				<?php
																			}?>
																		</div>
																		<?php
																	}?>
															</fieldset>
															<?php 
														}?>
													<?php } ?>
												</div>
												<?php
												}?>
												<!--jmail-expand ends-->
											</div>
											<!--well-->


										</div>
										<!--jmail-blocks-->
										<?php
									}
								}

								unset($plugtitle);
								unset($plugtitltlex);
								unset($plugtitleparm);
							}
						}
						// Do not let users set plugin settings
						elseif ($pluginData == false && $altdata[3] == 0)
						{
							echo '<div class="clearfix">&nbsp;</div>';
							echo '<div class="pull-left">' . Text::_('NO_PLUGINS_ENABLED_OR_INSTALLED') . '</div>';
							echo '<div class="clearfix">&nbsp;</div>';
						}

						echo '
						</div>
					</div>
				</div>
			</div>';

			unset($k);
			$k = array();
			$i = 1;

		echo '
		</div>
	</div>';
}
