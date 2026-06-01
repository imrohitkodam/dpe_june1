<?php
/**
 * Droptables
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and to customize.
 * Otherwise, please feel free to contact us at contact@joomunited.com *
 *
 * @package   Droptables
 * @copyright Copyright (C) 2014 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @copyright Copyright (C) 2014 Damien Barrère (http://www.crac-design.com). All rights reserved.
 * @license   GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') || die;

$msgList = $displayData['msgList'];

$alert = array('error' => 'alert-error', 'warning' => '', 'notice' => 'alert-info', 'message' => 'alert-success');
?>
<div id="system-message-container">
    <?php if (is_array($msgList) && !empty($msgList)) : ?>
        <div id="system-message">
            <?php foreach ($msgList as $type => $msgs) : ?>
                <div class="alert <?php echo $alert[$type]; ?>">
                    <?php // This requires JS so we should add it trough JS. Progressive enhancement and stuff. ?>
                    <a class="close" data-dismiss="alert">×</a>

                    <?php if (!empty($msgs)) : ?>
                        <h4 class="alert-heading"><?php echo JText::_($type); ?></h4>
                        <div>
                            <?php foreach ($msgs as $msg) : ?>
                                <p class="alert-message"><?php echo $msg; ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
