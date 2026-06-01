<?php
/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        https://www.extly.com
 */
defined('_JEXEC') || exit;

use XTP_BUILD\Extly\Infrastructure\Service\Cms\Joomla\ScriptHelper;

ScriptHelper::addStyleDeclaration('.table-hover tbody tr:hover > td {
	cursor: pointer;
	cursor: hand;
}

.table-hover tbody tr .xt-req-editor {
	display: none;
}

.table-hover tbody tr:hover .xt-req-editor {
	display: inline-block;
}');

?>
<form name="adminForm" method="post"
	ng-controller="RequestsController as requestsCtrl">
	<input type="hidden" name="option" value="com_autotweet" /> <input
		type="hidden" name="view" value="composer" /> <input type="hidden"
		name="task" value="" /> <input type="hidden" name="returnurl"
		value="<?php

        echo base64_encode(JRoute::_('index.php?option=com_autotweet&view=cpanels'));

        ?>" />
<?php
echo EHtml::renderRoutingTags();
?>
	<fieldset>

		<p class="text-center" ng-if="requestsCtrl.waiting"><span class="loaderspinner72 loading72">
			<?php echo JText::_('COM_AUTOTWEET_LOADING'); ?>
		</span></p>

        <table ng-table="requestsCtrl.requestsTable" class="table table-hover ng-table-rowselected">
        	<thead>
	            <tr>
	                <th><?php echo JText::_('COM_AUTOTWEET_VIEW_MSGLOG_POSTDATE_TITLE'); ?></th>
	                <th>
	                	<?php echo JText::_('COM_AUTOTWEET_REQUESTS_FIELD_MESSAGE'); ?>
	                </th>
	                <th>&nbsp;</th>
	                <th><?php echo JText::_('COM_AUTOTWEET_VIEW_SOURCE_TITLE'); ?></th>
	                <?php

// <th><?php echo JText::_('JGLOBAL_FIELD_ID_LABEL'); </th>

?>
	            </tr>
	        </thead>
        	<tbody>
				<tr ng-repeat="request in $data"
					ng-click="requestsCtrl.requestsTable.selectRow(request)"
					ng-class="{'info': request.$selected}">

					<td class="xt-col-span-3">
<?php
                            if ($this->get('editown')) {
                                echo '<a ng-click="requestsCtrl.requestsTable.editRow(request)">';
                            }
?>
							{{::request.publish_up | date:'d MMM H:mm':'UTC'}}
<?php
                            if ($this->get('editown')) {
                                echo '</a>';
                            }
?>
					</td>

					<td class="xt-col-span-8">
						<p>
	<?php
                            if ($this->get('editown')) {
                                echo '<a ng-click="requestsCtrl.requestsTable.editRow(request)">';
                            }
    ?>
							{{::request.description}}
	<?php
                            if ($this->get('editown')) {
                                echo '</a>';
                            }
    ?>
	 						<span> - </span>

                             <a title="<?php echo JText::_('COM_AUTOTWEET_COMPOSER_VIEW_URL'); ?>"
								href="{{::request.url}}" target="_blank" ng-if="request.url != null"><i class="xticon fas fa-globe"
								></i></a>

							<a ng-click="request.previewImage = !request.previewImage" title="<?php echo JText::_('COM_AUTOTWEET_COMPOSER_VIEW_IMAGE'); ?>"
								target="_blank" ng-if="request.image_url != null"><i class="xticon far fa-image"></i></a>

							<span ng-if="request.published == 1"> <i class="xticon fas fa-check text-success"></i></span>

							<span ng-if="request.published == 0"> <i class="xticon far fa-clock text-warning"></i></span>
						<p>
						<p ng-if="request.previewImage">
							<img ng-src="{{::request.image_url}}" alt="{{::request.description}}" class="img-polaroid xt-col-span-12">
						</p>
					</td>

					<td class="xt-col-span-1">
							<span ng-if="!request.$selected"><a class="btn btn-mini"
								href="#"><i class="xticon fas fa-ellipsis-h"></i></a></span>

							<div class="btn-group" ng-if="request.$selected">
	<?php
                            if ($this->get('editown')) {
                                ?>
								<a class="btn btn-mini"
									ng-click="requestsCtrl.requestsTable.editRow(request)"><i
										class="xticon fas fa-pencil-alt"></i></a>
	<?php
                            }

                            if ($this->get('editstate')) {
                                ?>
								<a ng-if="request.published == 0" class="btn btn-mini"
									ng-click="requestsCtrl.requestsTable.publishRow(request)"><i class="xticon fas fa-check"></i></a>
								<a ng-if="request.published == 0" class="btn btn-inverse btn-mini"
									ng-click="requestsCtrl.requestsTable.cancelRow(request)"><i class="xticon fas fa-times"></i></a>

								<a ng-if="request.published == 1" class="btn btn-mini"
									ng-click="requestsCtrl.requestsTable.backtoQueueRow(request)"><i class="xticon fas fa-retweet"></i></a>
	<?php
                            }
    ?>
							</div>
					</td>

					<td class="xt-col-span-1 nowrap">{{::request.plugin_simple_name}}

						<span ng-if="request.is_evergreen == 1"> <i class="xticon fas fa-leaf"></i></span>

						<span ng-if="request.is_immediate == 1"> <i class="xticon fas fa-bolt"></i></span>
					</td>
<?php

// <td class="xt-col-span-1">{{::request.id}}</td>

?>
					<!-- ID: {{::request.id}} -->
				</tr>
			</tbody>
		</table>

		<p class="xt-text-right"><a data-original-title="<?php echo JText::_('JGLOBAL_HELPREFRESH_BUTTON'); ?>" rel="tooltip"
			ng-click="requestsCtrl.requestsTable.doRefresh()">
			<i class="xticon fas fa-sync"></i>
		</a></p>

	</fieldset>
</form>
