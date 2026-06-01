<?
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2011 - 2014 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('KOOWA') or die; ?>

<?= helper('behavior.koowa'); ?>

<?= helper('player.load'); ?>

<div class="docman_player">
    <div
        data-plyr-provider="<?= $service ?>"
        data-plyr-config="<?= htmlentities(json_encode(['controls' => $controls])) ?>"
        data-plyr-embed-id="<?= $id ?>"
        data-media-id="<?= $document->id ?>"
        data-title="<?= escape($document->title) ?>"
        data-category="docman"
        data-plyr-config='{ "controls": ["play-large","play","progress","current-time","mute","volume","fullscreen"<?= $document->canPerform('download') ? ',"download"' : '' ?>] }'
    ></div>
</div>