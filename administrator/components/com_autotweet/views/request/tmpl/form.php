<?php
/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        https://www.extly.com
 */
defined('_JEXEC') || exit;

$this->loadHelper('select');

JHtml::_('behavior.formvalidator');

if (version_compare(JVERSION, '3.999.999', 'le')) {
	JHtml::_('behavior.calendar');
}

if (!class_exists('JFormFieldImagelist')) {
    require_once JPATH_LIBRARIES.'/joomla/form/fields/imagelist.php';
}

$alert_style = 'alert-info';
$alert_message = '';

$native_object = TextUtil::json_decode($this->item->native_object);

if ((isset($native_object->error)) && ($native_object->error)) {
    $alert_style = 'alert-error';
    $alert_message = JText::_($native_object->error_message);
}

$isRequest = true;
$isManualMsg = ('autotweetpost' === $this->item->plugin);

?>
<div class="extly request-edit">
	<div class="xt-body">

		<form name="adminForm" id="adminForm" action="index.php" method="post" class="form form-horizontal form-validate">
			<input type="hidden" name="option" value="com_autotweet" />
			<input type="hidden" name="view" value="requests" />
			<input type="hidden" name="task" value="" />
			<?php

                echo EHtml::renderRoutingTags();

            ?>

			<div class="xt-grid">

				<div class="xt-col-span-6">

					<fieldset class="details">

						<div class="control-group">
							<label class="required control-label" for="publish_up_time" id="publish_up-lbl" rel="tooltip" data-original-title="<?php
                            echo JText::_('COM_AUTOTWEET_REQ_SCHEDULED_DATE_DESC');
                            ?>"><?php echo JText::_('COM_AUTOTWEET_REQ_SCHEDULED_DATE'); ?> <span class="star">&#160;*</span>
							</label>
							<div class="controls">
								<?php

                                $publish_up = JHtml::_('date', $this->item->publish_up, JText::_('COM_AUTOTWEET_DATE_FORMAT'));
                                echo JHTML::_('calendar', $publish_up, 'publish_up', 'publish_up', JText::_('COM_AUTOTWEET_DATE_VIEW_FORMAT'), ['class' => 'input required']);

                                ?>
							</div>
						</div>

						<div class="control-group">
							<label></label>
							<div class="controls">
								<?php

                                echo $this->showWorldClockLink();

                                ?>
							</div>
						</div>

						<div class="control-group">
							<label for="plugin required" class="control-label" rel="tooltip" data-original-title="<?php
                            echo JText::_('COM_AUTOTWEET_REQ_PLUGIN_DESC');
                            ?>"><?php echo JText::_('COM_AUTOTWEET_REQ_PLUGIN'); ?> <span class="star">&#160;*</span>
							</label>
							<div class="controls">
								<?php echo SelectControlHelper::plugins($this->item->plugin, 'plugin', ['class' => 'input required']); ?>
							</div>
						</div>

						<div class="control-group">
							<label for="ref_id" class="control-label required" rel="tooltip" data-original-title="<?php
                            echo JText::_('COM_AUTOTWEET_REQ_REFERENCE_DESC');
                            ?>"><?php echo JText::_('COM_AUTOTWEET_REQ_REFERENCE'); ?> <span class="star">&#160;*</span>
							</label>
							<div class="controls">
								<input type="text" name="ref_id" id="ref_id" value="<?php echo empty($this->item->ref_id) ? \Joomla\CMS\Factory::getDate()->toUnix() : $this->item->ref_id; ?>" class="required" maxlength="64" />
							</div>
						</div>

						<div class="control-group">
							<label for="name" class="control-label required" rel="tooltip" data-original-title="<?php
                            echo JText::_('COM_AUTOTWEET_REQ_TITLE_DESC');
                            ?>"><?php echo JText::_('COM_AUTOTWEET_REQ_TITLE'); ?> <span class="star">&#160;*</span>
							</label>
							<div class="controls">
								<textarea name="description" id="description" class="input required" maxlength="512" rows="2" required="required"><?php
                                    echo htmlentities($this->item->description, \ENT_COMPAT, 'UTF-8');
                                ?></textarea>
							</div>
						</div>

						<div class="control-group">
							<label for="text" class="control-label" rel="tooltip" data-original-title="<?php
                            echo JText::_('COM_AUTOTWEET_REQ_LINK_DESC');
                            ?>"><?php echo JText::_('COM_AUTOTWEET_REQ_LINK'); ?>
							</label>
							<div class="controls">
								<input type="text" name="url" id="url" value="<?php echo TextUtil::renderUrl($this->item->url); ?>" maxlength="512"/>
							</div>
						</div>

						<?php

                        echo EHtml::imageControl(
                            TextUtil::renderUrl($this->item->image_url),
                            'image_url',
                            'COM_AUTOTWEET_REQ_IMAGE',
                            'COM_AUTOTWEET_REQ_IMAGE_DESC',
                            null,
                            true
                        );

                        ?>

						<div class="control-group">
							<label class="control-label" for="published" id="published-lbl" rel="tooltip" data-original-title="<?php
                            echo JText::_('COM_AUTOTWEET_REQ_PUBLISHED_DESC');
                            ?>"><?php echo JText::_('COM_AUTOTWEET_REQ_PUBLISHED_TITLE'); ?> </label>
							<div class="inline controls">
								<?php

                                echo EHtmlSelect::published($this->item->get('published', 1), 'published', [], 'JYES', 'JNO');

                                ?>
							</div>
						</div>

						<div class="control-group">
							<label for="request_id" class="control-label" rel="tooltip" data-original-title="<?php echo JText::_('JGLOBAL_FIELD_ID_DESC'); ?>"><?php
                            echo JText::_('JGLOBAL_FIELD_ID_LABEL'); ?> </label>
							<div class="controls">
								<input type="text" name="id" id="request_id" value="<?php echo $this->item->id; ?>" class="disabled" readonly="readonly">
							</div>
						</div>

					</fieldset>

				</div>

				<?php

                require __DIR__.'/right-side.php';

                ?>

			</div>
		</form>
	</div>
</div>

