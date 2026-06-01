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

$isRequest = false;
$isManualMsg = ('autotweetpost' === $this->item->plugin);
$readonlyNotManual = (!$isManualMsg ? 'readonly="readonly"' : '');
$labelDisabledNotManual = (!$isManualMsg ? 'disabled' : '');

if (($isManualMsg) && (!(bool) $this->item->get('id'))) {
    $this->item->set('pubstate', PostShareManager::POST_APPROVE);
}

$alert_style = 'alert-info';

if ('error' === $this->item->pubstate) {
    $alert_style = 'alert-error';
}

$alert_message = JText::_($this->item->resultmsg);

?>

<div class="extly">
	<div class="xt-body">

		<form name="adminForm" id="adminForm" action="index.php" method="post" class="form form-horizontal form-validate">
			<input type="hidden" name="option" value="com_autotweet" />
			<input type="hidden" name="view" value="posts" />
			<input type="hidden" name="task" value="" />
			<?php

                echo EHtml::renderRoutingTags();

            ?>
			<div class="xt-grid">

				<div class="xt-col-span-6">

					<fieldset class="details">

						<div class="control-group">
							<label class=" required control-label" for="postdate" id="postdate-lbl" rel="tooltip" data-original-title="<?php
                            echo JText::_('COM_AUTOTWEET_POST_PUBLICATION_DATE_DESC');
                            ?>"><?php echo JText::_('COM_AUTOTWEET_POST_PUBLICATION_DATE'); ?></label>
							<div class="controls">
								<?php

                                $postdate = JHtml::_('date', $this->item->postdate, JText::_('COM_AUTOTWEET_DATE_FORMAT'));
                                echo JHTML::_('calendar', $postdate, 'postdate', 'postdate', JText::_('COM_AUTOTWEET_DATE_VIEW_FORMAT'), ['class' => 'input', 'required' => 'required']);

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
							<label for="channel_id" class="control-label required" rel="tooltip" data-original-title="<?php
                            echo JText::_('COM_AUTOTWEET_POST_CHANNEL_DESC');
                            ?>"><?php echo JText::_('COM_AUTOTWEET_POST_CHANNEL'); ?> <span class="star">&#160;*</span></label>
							<div class="controls">
								<?php echo SelectControlHelper::channels($this->item->channel_id, 'channel_id', ['class' => 'input required', 'required' => 'required']); ?>
							</div>
						</div>

						<div class="control-group">
							<label for="plugin" class="control-label required" rel="tooltip" data-original-title="<?php
                            echo JText::_('COM_AUTOTWEET_POST_PLUGIN_DESC');
                            ?>"><?php echo JText::_('COM_AUTOTWEET_POST_PLUGIN'); ?> <span class="star">&#160;*</span></label>
							<div class="controls">
								<?php echo SelectControlHelper::plugins($this->item->plugin, 'plugin', ['class' => 'input required', 'required' => 'required']); ?>
							</div>
						</div>

						<div class="control-group">
							<label for="ref_id" class="control-label required" rel="tooltip" data-original-title="<?php
                            echo JText::_('COM_AUTOTWEET_POST_REFERENCE_DESC');
                            ?>"><?php echo JText::_('COM_AUTOTWEET_POST_REFERENCE'); ?> <span class="star">&#160;*</span>
							</label>
							<div class="controls">
								<input type="text" name="ref_id" id="ref_id" value="<?php echo $this->item->ref_id; ?>" class="input required" maxlength="64" required="required"/>
							</div>
						</div>

						<div class="control-group">
							<label for="title" class="control-label required" rel="tooltip" data-original-title="<?php
                            echo JText::_('COM_AUTOTWEET_POST_TITLE_DESC');
                            ?>"><?php echo JText::_('COM_AUTOTWEET_POST_TITLE'); ?> <span class="star">&#160;*</span>
							</label>
							<div class="controls">
								<textarea name="title" id="title" class="input required" maxlength="512" rows="2" required="required"><?php
                                    echo htmlentities($this->item->title, \ENT_COMPAT, 'UTF-8');
                                ?></textarea>
							</div>
						</div>

						<div class="control-group">
							<label for="message" class="control-label required" rel="tooltip" data-original-title="<?php
                            echo JText::_('COM_AUTOTWEET_POST_MESSAGE_DESC');
                            ?>"><?php echo JText::_('COM_AUTOTWEET_POST_MESSAGE'); ?> <span class="star">&#160;*</span></label>
							<div class="controls">
								<textarea name="message" id="message" rows="5" cols="40" maxlength="512" class="input required"><?php
                                    echo htmlentities($this->item->message, \ENT_COMPAT, 'UTF-8');
                                    ?></textarea>
							</div>
						</div>

						<div class="control-group">
							<label for="url" class="control-label" rel="tooltip" data-original-title="<?php
                            echo JText::_('COM_AUTOTWEET_POST_SHORT_URL_DESC');
                            ?>"><?php echo JText::_('COM_AUTOTWEET_POST_SHORT_URL'); ?></label>
							<div class="controls">
								<input type="text" name="url" id="url" value="<?php echo TextUtil::renderUrl($this->item->url); ?>" maxlength="512" />
							</div>
						</div>

						<div class="control-group">
							<label for="org_url" class="control-label" rel="tooltip" data-original-title="<?php
                            echo JText::_('COM_AUTOTWEET_POST_ORIGINAL_URL_DESC');
                            ?>"><?php echo JText::_('COM_AUTOTWEET_POST_ORIGINAL_URL'); ?></label>
							<div class="controls">
								<input type="text" name="org_url" id="org_url" value="<?php echo TextUtil::renderUrl($this->item->org_url); ?>" maxlength="512" />
							</div>
						</div>

						<?php

                        echo EHtml::imageControl(
                            TextUtil::renderUrl($this->item->image_url),
                            'image_url',
                            'COM_AUTOTWEET_POST_IMAGE_URL',
                            'COM_AUTOTWEET_POST_IMAGE_URL_DESC',
                            null,
                            true
                        );

                        ?>

						<div class="control-group">
							<label for="show_url" class="control-label required" rel="tooltip" data-original-title="<?php
                            echo JText::_('COM_AUTOTWEET_POST_SHOW_URL_DESC');
                            ?>"><?php echo JText::_('COM_AUTOTWEET_POST_SHOW_URL'); ?> <span class="star">&#160;*</span></label>
							<div class="controls">
								<?php echo SelectControlHelper::showurl($this->item->show_url, 'show_url', ['class' => 'input required', 'required' => 'required']); ?>
							</div>
						</div>

<?php
                        echo SelectControlHelper::pubstatesControl(
                                $this->item->pubstate,
                                'pubstate',
                                JText::_('COM_AUTOTWEET_POST_STATE').' <span class="star">&#160;*</span>',
                                'COM_AUTOTWEET_POST_STATE_DESC',
                                ['class' => 'input required', 'required' => 'required']
                            );
?>

						<div class="control-group">
							<label for="post_id" class="control-label"
								rel="tooltip" data-original-title="<?php echo JText::_('JGLOBAL_FIELD_ID_DESC'); ?>"><?php
                                echo JText::_('JGLOBAL_FIELD_ID_LABEL'); ?> </label>
							<div class="controls">
								<input type="text" name="id" id="post_id" value="<?php echo $this->item->id; ?>" class="disabled" readonly="readonly">
							</div>
						</div>

					</fieldset>

				</div>

				<?php

                require __DIR__.'/../../request/tmpl/right-side.php';

                ?>

			</div>

			<?php
				echo '<input type="hidden" name="serialized_params" value="'.
					htmlentities($this->item->xtform->toString(), \ENT_COMPAT, 'UTF-8').'"/>';
			?>
		</form>
	</div>
</div>
