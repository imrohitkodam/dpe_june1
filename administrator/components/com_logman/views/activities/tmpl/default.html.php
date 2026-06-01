<?
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('_JEXEC') or die; ?>


<?= helper('ui.load', array('package' => 'logman')) ?>
<?= helper('behavior.jquery') ?>
<?= helper('behavior.modal') ?>

<? // RSS feed ?>
<link href="<?=route('format=rss');?>" rel="alternate" type="application/rss+xml" title="RSS 2.0" />

<?= import('purge.html'); ?>
<?= import('export.html'); ?>

<script>
    kQuery(function($) {

        var end_date = $('#end_date');

        if (!end_date.val()) {
            end_date.val(<?= json_encode(helper('date.format', array('format' => translate('DATE_FORMAT_LC4')))); ?>);
        }

        $('#logman-filters').on('reset', function(e) {
            e.preventDefault();

            $(this).find('input').each(function(i, el) {
                if ($.inArray($(el).attr('name'), ['day_range','end_date', 'user']) !== -1) {
                    $(el).val('');
                }
            });

            $('select[name="usergroup[]"]').val(null).trigger('change');

            $(this).append('<input type="hidden" name="usergroup" value="" />');

            $(this).submit();
        });

        $('#activities-filter').on('submit', function(e) {
            if (!$('select[name="usergroup[]"]').val()) {
                $(this).append('<input type="hidden" name="usergroup" value="" />');
            }
        });
    });
</script>


<!-- Wrapper -->
<div class="k-wrapper k-js-wrapper">

    <!-- Title when sidebar is inivisible -->
    <ktml:toolbar type="titlebar" title="<?= translate('Activities'); ?>" mobile>

    <!-- Content wrapper -->
    <div class="k-content-wrapper">

        <?= import('default_sidebar.html'); ?>

        <!-- Content -->
        <div class="k-content k-js-content">

            <!-- Toolbar -->
            <ktml:toolbar type="actionbar">

            <!-- Component wrapper -->
            <div class="k-component-wrapper">

                <!-- Component -->
                <form class="k-component k-js-component k-js-grid-controller " action="" method="get">

                    <?= import('default_scopebar.html'); ?>

                    <?= import('default_table.html'); ?>

                </form><!-- .k-component -->

            </div><!-- .k-component-wrapper -->

        </div><!-- k-content -->

    </div><!-- .k-content-wrapper -->

</div><!-- .k-wrapper -->
