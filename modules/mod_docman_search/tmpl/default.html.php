<?
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('KOOWA') or die; ?>


<?= helper('ui.load', array(
    'package' => 'docman',
    'wrapper' => false
)); ?>

<div class="k-ui-namespace">
    <div class="mod_docman mod_docman--search">
        <form action="<?= route('option=com_docman&view=search&itemless=1&layout=' . $params->layout) ?>" method="get" class="k-js-grid-controller">
            <div class="form-group">
                <input
                    class="form-control input-block-level"
                    type="search"
                    name="search"
                    placeholder="<?= translate('Search') ?>"
                    value="<?= object('request')->query->search ?>" />
            </div>
        </form>
    </div>
</div>