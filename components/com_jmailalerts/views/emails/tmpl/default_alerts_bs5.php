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

$k = array();
$i = 1;

$model          = $this->getModel();
$qryConcat      = $this->qryConcat;
$option         = $this->defaultoption;
$defaultSetting = $this->defaultSetting;
$altid          = $this->altid;

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
		$bstyle            = 'class = "text text-success"';
		$subStatusMsg      = Text   :: _('JMA_UNCHECK_SUB_MSG');
		$showPlugins       = "block";
		$checkHiddenPlugin = 0;
	}
	else
	{
		$pluginName        = $model->getData($altid[$s]);
		$alertchk          = "";
		$bstyle            = 'class = "text text-info"';
		$subStatusMsg      = Text   :: _('JMA_CHECK_SUB_MSG');
		$showPlugins       = 'none';
		$checkHiddenPlugin = 1;
	}

	// Get frequency
	$altdata = $model->getFreq($altid[$s]);

	$allowuser = "block";

	echo '
<div class="mb-3 card">
	<div class="card-header">
		<div class="form-check">
			<input type="checkbox" name="alt[]" id="alert_' . $altid[$s] . '"
				value="' . $altid[$s] . '" onclick="divhide(this);" ' . $alertchk . '
				class="form-check-input" />

			<label for="alert_' . $altid[$s] . '" class="form-check-label">
				<strong ' . $bstyle . '>' . $altdata[1] . '</strong>
				<span class="small text-muted text-lowercase">' . $subStatusMsg . '</span>
			</label>
		</div>
	</div>

	<div class="card-body">
		<div class="m-1">' . $altdata[2] . '</div>

		<div id="' . $altid[$s] . '" style="display:' . $showPlugins . '">
			<div style="display:' . $allowuser . '">

				<div class="mb-3 row">
					<div class="col-sm-4">
						<label for="c' . $altid[$s] . '" class="col-form-label">
							<strong>' . Text::_("CURRENT_SETTING") . '</strong>
						</label>
					</div>

					<div class="col-sm-8">' .
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
								<div class="mb-3 mt-1 row">
									<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
										<div class="card">
											<?php
											if ($checkHiddenPlugin)
											{
												$checked = "checked";
											}

											echo '
											<div class="card-header" >
												<div class="form-check">
													<input type="checkbox" name="ch' . $altid[$s] . '[]"
														id="plg_' . $singlePluginName->element . '_' . $altid[$s] . '"
														value="' . $singlePluginName->element . '_' . $altid[$s] . '"
														onclick="divhide(this);" ' . $checked . '
														class="form-check-input" />

													<label for="plg_' . $singlePluginName->element . '_' . $altid[$s] . '" class="form-check-label">
														<strong>' . Text::_($plugtitle) . '</strong>
													</label>
												</div>
											</div>';

											if ($altdata[3] == 1)
											{
											?>
											<div class="card-body"
												id="<?php echo $singlePluginName->element . '_' . $altid[$s] . $disp ?>">
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
													<?php // If the fieldset has a label set, display it as the legend. ?>
													<?php
													if (isset($fieldset->label))
													{
														?>
														<legend><?php echo Text::_($fieldset->label); ?></legend>
														<?php
													}
													?>

													<?php // Iterate through the fields in the set and display them. ?>
													<?php 
													foreach ($fields as $field)
													{
														?>
														<div class="mb-3 row">
															<?php // If the field is hidden, just display the input. ?>
															<?php
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
													<?php }?>
												</fieldset>

													<?php } ?>
												<?php } ?>
											</div>
											<?php
											}?>
											<!--jmail-expand ends-->
											</div>
										<!--card-->
									</div>
								</div>
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
	</div>';

	unset($k);
	$k = array();
	$i = 1;

	echo '
</div>';
}
