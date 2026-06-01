<?
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('KOOWA') or die; ?>


<div id="category-batch-modal" class="k-ui-namespace k-small-inline-modal-holder mfp-hide">
    <div class="k-inline-modal">
        <form class="k-js-batch-form">
            <h3 class="k-inline-modal__title">
                <?= translate('Batch process the selected categories') ?>
            </h3>

            <div class="k-form-group">
                <label><?= translate('Owner');?>:</label>
                <script>
                    function categorySetOwnerDropdownParent(options) {
                        options.dropdownParent = kQuery('#category-batch-modal');

                        return options;
                    }
                </script>
                <?= helper('listbox.users', array(
                    'name' => 'created_by',
                    'prompt'   => translate('- Keep original owner -'),
                    'attribs'  => array(
                        'id' => 'js-owner-selector'
                    ),
                    'options_callback' => 'categorySetOwnerDropdownParent'
                ))?>
            </div>

            <div class="k-form-group">
                <button class="k-button k-button--primary" ><?= translate('Save'); ?></button>
            </div>
        </form>
    </div>
</div>
