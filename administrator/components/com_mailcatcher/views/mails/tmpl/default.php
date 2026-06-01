<?php
/*------------------------------------------------------------------------
  Mail Catcher - Email logging extension for Joomla
  ------------------------------------------------------------------------
  @Author    Solidres Team
  @Website   https://www.solidres.com
  @Copyright Copyright (C) 2016 - 2019 Solidres. All Rights Reserved.
  @License   GNU General Public License version 3, or later
------------------------------------------------------------------------*/

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory as CMSFactory;
use Joomla\CMS\Date\Date;


HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('formbehavior.chosen', 'select');
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->escape($this->state->get('list.direction'));
$baseLink  = 'index.php?option=com_mailcatcher&task=mail.attachment&file=';

CMSFactory::getDocument()->addStyleDeclaration('.unread-mail>td{background-color: #fffe82!important}');
?>
<form action="<?php echo Route::_('index.php?option=com_mailcatcher&view=mails', false); ?>" method="post"
      name="adminForm"
      id="adminForm">
    <div id="j-main-container">
		<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
		<?php if (empty($this->items)) : ?>
            <div class="alert alert-no-items">
				<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
            </div>
		<?php else : ?>
            <table class="table table-striped" id="mailList">
                <thead>
                <tr>
                    <th width="1%" class="center">
						<?php echo HTMLHelper::_('grid.checkall'); ?>
                    </th>
                    <th class="nowrap">
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_MAILCATCHER_CREATED_DATE', 'a.created_date', $listDirn, $listOrder); ?>
                    </th>
                    <th>
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_MAILCATCHER_SENT_FROM', 'a.sent_from_mail', $listDirn, $listOrder); ?>
                    </th>
                    <th>
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_MAILCATCHER_RECEIVERS', 'a.receivers', $listDirn, $listOrder); ?>
                    </th>
                    <th>
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_MAILCATCHER_SUBJECT', 'a.subject', $listDirn, $listOrder); ?>
                    </th>
                    <th>
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_MAILCATCHER_MESSAGE', 'a.message', $listDirn, $listOrder); ?>
                    </th>
                    <th width="1%" class="nowrap center">
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_MAILCATCHER_SUCCESS', 'a.success', $listDirn, $listOrder); ?>
                    </th>
                    <th width="5%" class="nowrap center hidden-phone">
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_MAILCATCHER_MAILER', 'a.mailer', $listDirn, $listOrder); ?>
                    </th>
                    <th style="width: 1px" class="hidden-phone">
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_MAILCATCHER_REFERER', 'a.referer', $listDirn, $listOrder); ?>
                    </th>
                    <th width="5%" class="hidden-phone">
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_MAILCATCHER_IP', 'a.ip', $listDirn, $listOrder); ?>
                    </th>
                    <th width="1%" class="nowrap center hidden-phone">
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_MAILCATCHER_ID', 'a.id', $listDirn, $listOrder); ?>
                    </th>
                </tr>
                </thead>
                <tbody>
				<?php foreach ($this->items as $i => $item): ?>
                    <tr<?php echo $item->unread ? ' class="unread-mail"' : ''; ?>>
                        <td>
							<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                        </td>
                        <td class="nowrap">
							<?php 

                            $date = new Date($item->created_date);
                            $item->created_date = $date->format('d/m/Y H:i:s'); // DPE hack


                                                        echo $item->created_date;
 ?>
                        </td>
                        <td>
							<?php echo $this->escape($item->sent_from_name) . ' (' . $this->escape($item->sent_from_mail) . ')'; ?>
                        </td>
                        <td>
							<?php echo $this->escape($item->receivers); ?>
                        </td>
                        <td>
							<?php
							echo $this->escape($item->subject);

							if (!empty($item->attachments))
							{
								$attachments = json_decode($item->attachments);
								$filePath    = JPATH_ROOT . '/media/com_mailcatcher/assets/attachments/' . $item->id;
								echo '<h6><i class="icon-attachment"></i> ' . Text::_('COM_MAILCATCHER_ATTACHMENTS') . '</h6><ol>';

								foreach ($attachments as $attachment)
								{
									$file = $filePath . '/' . basename($attachment[0]);

									if (is_file($file))
									{
										echo '<li><a href="' . Route::_($baseLink . base64_encode($file), false) . '" target="_blank">' . $attachment[2] . '</a></li>';
									}
								}

								echo '</ol>';
							}

							?>

                        </td>
                        <td>
                            <a href="<?php echo Route::_('index.php?option=com_mailcatcher&view=mails&layout=message&id=' . $item->id . '&tmpl=component', false); ?>"
                               target="_blank">
	                            <?php if ($item->is_html): ?>
		                            <?php echo Text::_('COM_MAILCATCHER_VIEW_AS_HTML'); ?>
	                            <?php else: ?>
		                            <?php echo JHtml::_('string.truncate', ($item->message), 99, false) ?>
	                            <?php endif; ?>
                            </a>
                        </td>
                        <td class="nowrap center">
                            <i class="icon-<?php echo $item->success ? 'publish' : 'unpublish'; ?>"></i>
                        </td>
                        <td class="nowrap center hidden-phone">
							<?php echo strtoupper($item->mailer); ?>
                        </td>
                        <td class="hidden-phone">
							<?php if (!empty($item->referer)): ?>
                                <div class="hasTooltip"
                                     title="<?php echo htmlspecialchars($item->referer, ENT_COMPAT, 'UTF-8'); ?>">
									<?php if ($listOrder == 'a.referer'): ?>
										<?php echo $this->escape($item->referer); ?>
									<?php else: ?>
										<?php echo substr($this->escape($item->referer), 0, 25) . '...'; ?>
									<?php endif; ?>
                                </div>
							<?php endif; ?>
                        </td>
                        <td class="hidden-phone">
							<?php echo $this->escape($item->ip); ?>
                        </td>
                        <td class="nowrap center hidden-phone">
							<?php echo $item->id; ?>
                        </td>
                    </tr>
				<?php endforeach; ?>
                </tbody>
            </table>
		<?php endif; ?>
		<?php echo $this->pagination->getListFooter(); ?>
    </div>
    <input type="hidden" name="task"/>
    <input type="hidden" name="boxchecked"/>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
