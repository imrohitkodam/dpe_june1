<?
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('KOOWA') or die; ?>


<? // Loading necessary Markup, CSS and JS ?>
<?= helper('ui.load') ?>


<?= helper('behavior.keepalive'); ?>
<?= helper('behavior.validator'); ?>
<?= helper('behavior.modal'); ?>


<? // Setting up 'translations' to be used in JavaScript ?>
<?= helper('translator.script', array('strings' => array(
    'Folder names can only contain letters, numbers, dash, underscore or colons',
    'Audio files',
    'Archive files',
    'Documents',
    'Images',
    'Video files',
    'Add another extension...'
))); ?>


<? // Loading JavaScript ?>
<ktml:script src="media://com_logman/js/admin/config.default.js" />
<script>
    kQuery(function($) {
        $('#advanced-permissions-toggle').on('click', function(e)
        {
            e.preventDefault();

            $.magnificPopup.open({
                items: {
                    src: $('#advanced-permissions'),
                    type: 'inline'
                }
            });
        });

        $('#plugins-list-toggle').on('click', function(e)
        {
            e.preventDefault();

            $.magnificPopup.open({
                items: {
                    src: $('#plugins-list'),
                    type: 'inline'
                }
            });
        });

        $('#plugins-list input').click(function(e)
        {
            var el = $(this);

            var url = <?= json_encode(route('', true, false)->toString()) ?>;
            var token = <?= json_encode($token) ?>;

            var state = el.attr('value');
            var parent = el.parents('tr');

            // Only change the state
            if (parent.length)
            {
                $.ajax(url, {
                    method: 'POST',
                    data: {csrf_token: token, _action: 'editPlugin', state: state, name: parent.data('identifier')}
                });
            }
        });
    });
</script>


<!-- Wrapper -->
<div class="k-wrapper k-js-wrapper">

    <!-- Content wrapper -->
    <div class="k-content-wrapper">

        <!-- Content -->
        <div class="k-content k-js-content">

            <!-- Toolbar -->
            <ktml:toolbar type="actionbar">

            <!-- Component wrapper -->
            <div class="k-component-wrapper">

                <!-- Component -->
                <form class="k-component k-js-form-controller" action="" method="post">

                    <!-- Container -->
                    <div class="k-container">

                        <!-- Main information -->
                        <div class="k-container__main">

                            <fieldset>

                                <div class="k-form-group">
                                    <label><?= translate('COM_LOGMAN_LOG_LOGIN_EVENTS');?></label>
                                    <?= helper('select.booleanlist', array('name' => 'log_login_events', 'selected' => $config->log_login_events)); ?>
                                    <p class="k-form-info"><?=translate('COM_LOGMAN_LOG_LOGIN_EVENTS_DESC')?></p>
                                </div>

                                <div class="k-form-group">
                                    <label for="maximum_age"><?= translate('COM_LOGMAN_MAXIMUM_AGE') ?></label>
                                    <input type="text" class="k-form-control" value="<?= escape($config->maximum_age) ?>" id="maximum_age" name="maximum_age" />
                                    <p class="k-form-info"><?=translate('COM_LOGMAN_MAXIMUM_AGE_DESC')?></p>
                                </div>

                            </fieldset>

                            <fieldset>

                                <legend><?= translate('Analytics and SEO'); ?> <span class="label label-warning">BETA</span></legend>

                                <? if (!$sef_on): ?>

                                    <div class="k-alert k-alert--warning">
                                        <button type="button" class="k-alert__close k-js-alert-close" title="Close" aria-label="Close">Close</button>
                                        <?= translate('COM_LOGMAN_SEF_DISABLED') ?>
                                    </div>

                                <? endif ?>

                                <div class="k-form-group">
                                    <label><?= translate('COM_LOGMAN_LOG_ROUTES');?></label>
                                    <?= helper('select.booleanlist', array('name' => 'log_routes', 'selected' => $config->log_routes)); ?>
                                    <p class="k-form-info"><?=translate('COM_LOGMAN_LOG_ROUTES_DESC')?></p>
                                </div>

                                <div class="k-form-group">
                                    <label><?= translate('COM_LOGMAN_LOG_IMPRESSIONS');?></label>
                                    <?= helper('select.booleanlist', array('name' => 'log_impressions', 'selected' => $config->log_impressions)); ?>
                                    <p class="k-form-info"><?=translate('COM_LOGMAN_LOG_IMPRESSIONS_DESC')?></p>
                                </div>
                            </fieldset>

                        </div><!-- .k-container__main -->

                        <!-- Other information -->
                        <div class="k-container__sub">

                            <fieldset class="k-form-block">

                                <div class="k-form-block__header">
                                    <?= translate('License');?>
                                </div>

                                <div class="k-form-block__content">

                                    <div class="k-form-group">
                                        <? if ($license_error): ?>
                                            <p class="k-alert--danger">Invalid license: <?= $license_error ?></p>
                                        <? else: ?>
                                            <p>Licensed to <a href="https://dashboard.joomlatools.com/my-account" target="_blank">
                                                    <?= $license->getToken()->getClaim('sub')['name']; ?></a></p>
                                            <? foreach ($license->getSubscriptions() as $subscription): ?>
                                                <p><?= $subscription['title'] ?>
                                                    <? if (isset($subscription['end'])): ?>
                                                        valid until <?= helper('date.format', ['date' => $subscription['end']]); ?>
                                                    <? endif ?>
                                                </p>
                                            <? endforeach; ?>
                                            <? if (false && $has_connect): ?>
                                                <p><?= translate('Joomlatools Connect is active') ?></p>
                                            <? endif; ?>
                                        <? endif; ?>

                                        <p>
                                            <a class="k-button k-button--default k-js-refresh-license">
                                                <?= translate('Refresh support license')?>
                                            </a>
                                        </p>
                                    </div>

                                </div>

                            </fieldset>

                            <fieldset class="k-form-block">

                                <div class="k-form-block__header">
                                    <?= translate('Users and groups');?>
                                </div>

                                <div class="k-form-block__content">

                                    <div class="k-form-group">
                                        <label><?= translate('COM_LOGMAN_LOG_GUEST_ACTIONS');?></label>
                                        <?= helper('select.booleanlist', array('name' => 'log_guest_actions', 'selected' => $config->log_guest_actions)); ?>
                                        <p class="k-form-info"><?=translate('COM_LOGMAN_LOG_GUEST_ACTIONS_DESC')?>. <em><?= translate( 'COM_LOGMAN_LOG_GUEST_ACTIONS_WARNING')?></em></p>
                                    </div>

                                    <div class="k-form-group">
                                        <label for="document_path"><?= translate('COM_LOGMAN_IGNORED_GROUPS') ?></label>
                                        <?= helper('listbox.usergroups', array('name' => 'ignored_groups', 'mutiple' => true, 'selected' => $config->ignored_groups)); ?>
                                        <p class="k-form-info"><?=translate('COM_LOGMAN_IGNORED_GROUPS_DESC')?></p>
                                    </div>

                                </div>

                            </fieldset>

                            <fieldset class="k-form-block">

                                <div class="k-form-block__header">
                                    <?= translate('Permissions');?>
                                </div>

                                <div class="k-form-block__content">

                                    <?= import('modal_permissions.html'); ?>

                                    <div class="k-form-group">
                                        <p>
                                            <a class="k-button k-button--default" id="advanced-permissions-toggle" href="#advanced-permissions">
                                                <?= translate('Change permissions')?>
                                            </a>
                                        </p>
                                        <p class="k-form-info k-color-error">
                                            <?= translate('For advanced use only'); ?>
                                        </p>
                                    </div>

                                </div>
                            </fieldset>

                            <fieldset class="k-form-block">

                                <div class="k-form-block__header">
                                    <?= translate('Integrations');?>
                                </div>

                                <div class="k-form-block__content">

                                    <div class="k-form-group">
                                        <p>
                                            <a class="k-button k-button--default" id="plugins-list-toggle" href="#plugins-list">
                                                <?= translate('Manage')?>
                                            </a>
                                        </p>
                                    </div>

                                </div>

                            </fieldset>

                        </div><!-- .k-container__sub -->

                    </div><!-- .k-container -->

                </form><!-- .k-component -->

            </div><!-- .k-component-wrapper -->

        </div><!-- .k-content -->

    </div><!-- .k-content-wrapper -->

</div><!-- .k-wrapper -->


<?= import('modal_integrations.html'); ?>

