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
    <audio
        data-media-id="<?= $document->id ?>"
        data-plyr-config="<?= htmlentities(json_encode(['controls' => $controls])) ?>"
        data-title="<?= escape($document->title) ?>"
        data-category="docman"
        preload="none"
        data-plyr-config='{ "controls": ["play-large","play","progress","current-time","mute","volume","fullscreen"<?= $document->storage_type == 'file' && $document->canPerform('download') ? ',"download"' : '' ?>] }'
        controls>
        <source src="<?= helper('player.link', ['document' => $document]) ?>" type="audio/<?= $document->extension ?>" />
    </audio>
</div>