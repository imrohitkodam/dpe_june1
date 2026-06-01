<?
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2017 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('_JEXEC') or die; ?>

<script>
    kQuery(function($) {
        var end_date = $('#end_date');

        if (!end_date.val()) {
            end_date.val(<?= json_encode(helper('date.format', array('format' => translate('DATE_FORMAT_LC4')))); ?>);
        }
    });
</script>

<div class="k-js-filters k-dynamic-content-holder">
    <div data-filter data-title="<?= translate('Users'); ?>"
         data-count="<?= parameters()->user ? 1 : 0 ?>"
    >
        <?=helper('listbox.users', array(
            'sort' => 'name',
            'attribs' => array(
                'id' => 'user',
                'class' => 'k-form-control', 'placeholder' => translate('Enter a name&hellip;')
            )
        ))?>
    </div>
    <div data-filter data-title="<?= translate('Groups'); ?>"
         data-count="<?= !empty(parameters()->usergroup) ? count(parameters()->usergroup) : 0 ?>"
    >
        <?= helper('com://site/logman.listbox.usergroups', array('selected' => parameters()->usergroup)) ?>
    </div>
    <div data-filter data-title="<?= translate('Date'); ?>"
        <?= parameters()->end_date ? 'data-label="1"' : '' ?>
    >
        <div class="k-form-group">
            <label for="end_date"><?=translate( 'Show events until' )?></label>
            <?= helper('behavior.calendar',
                array(
                    'attribs' => array('class' => 'input-small'),
                    'value' => parameters()->end_date,
                    'name' => 'end_date',
                    'format' => '%Y-%m-%d'
                )); ?>
        </div>

        <div class="k-form-group">
            <label for="end_date"><?=translate( 'Going back' )?></label>

            <div class="k-input-group">
                <input class="k-form-control" type="text" size="5" id="day_range" name="day_range" value="<?=parameters()->day_range?>" placeholder="&nbsp;&nbsp;&infin;" />
                <span class="k-input-group__addon"><?= translate('days'); ?></span>
            </div>

        </div>

    </div>
</div>


<!-- Scopebar -->
<div class="k-scopebar k-js-scopebar">

    <!-- Scopebar filters -->
    <div class="k-scopebar__item k-scopebar__item--filters">

        <!-- Filters wrapper -->
        <div class="k-scopebar__filters-content">

            <!-- Filters -->
            <div class="k-scopebar__filters k-js-filter-container">

                <!-- Filter -->
                <div style="display: none;" class="k-scopebar__item--filter k-scopebar-dropdown k-js-filter-prototype k-js-dropdown">
                    <button type="button" class="k-scopebar-dropdown__button k-js-dropdown-button">
                        <span class="k-scopebar__item--filter__title k-js-dropdown-title"></span>
                        <span class="k-scopebar__item--filter__icon k-icon-chevron-bottom" aria-hidden="true"></span>
                        <span class="k-scopebar__item-label k-js-dropdown-label"></span>
                    </button>
                    <div class="k-scopebar-dropdown__body k-js-dropdown-body">
                        <div class="k-scopebar-dropdown__body__buttons">
                            <button type="button" class="k-button k-button--default k-js-clear-filter"><?= translate('Clear') ?></button>
                            <button type="button" class="k-button k-button--primary k-js-apply-filter"><?= translate('Apply filter') ?></button>
                        </div>
                    </div>
                </div>

            </div><!-- .k-scopebar__filters -->

        </div><!-- .k-scopebar__filters-content -->

    </div><!-- .k-scopebar__item--filters -->

    <!-- Search -->
    <div class="k-scopebar__item k-scopebar__item--search">
        <?= helper('grid.search', array('submit_on_clear' => true)) ?>
    </div><!-- .k-scopebar__item--search -->

</div><!-- .k-scopebar -->
