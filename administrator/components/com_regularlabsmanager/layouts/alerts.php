<?php
/**
 * @package         Regular Labs Extension Manager
 * @version         9.2.5
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2025 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication as JCMSApplication;

extract($displayData);

/**
 * @var   object $messages
 */

$alert = [
    JCMSApplication::MSG_EMERGENCY => 'danger',
    JCMSApplication::MSG_ALERT     => 'danger',
    JCMSApplication::MSG_CRITICAL  => 'danger',
    JCMSApplication::MSG_ERROR     => 'danger',
    JCMSApplication::MSG_WARNING   => 'warning',
    JCMSApplication::MSG_NOTICE    => 'info',
    JCMSApplication::MSG_INFO      => 'info',
    JCMSApplication::MSG_DEBUG     => 'info',
    'message'                      => 'success',
];

$grouped = [];

foreach ($messages as $message)
{
    $type             = $alert[$message['type']] ?? $message['type'];
    $grouped[$type][] = $message;
}

if ( ! empty($grouped['success']))
{
    $ordered = [];

    foreach ($grouped['success'] as $message)
    {
        $id = 1;

        if (str_contains($message['message'], 'Library'))
        {
            $id = 2;
        }

        if (str_contains($message['message'], 'Conditions'))
        {
            $id = 3;
        }

        $id .= '.' . str_pad(count($ordered), 4, '0', STR_PAD_LEFT);

        $ordered[$id] = $message;
    }

    ksort($ordered);

    $grouped['success'] = $ordered;
}
?>

<div id="system-message-container">
    <?php foreach ($grouped as $type => $messages): ?>
        <joomla-alert type="<?php echo $type; ?>" role="alert">
            <div class="alert-heading">
                <span class="<?php echo $type; ?>"></span>
                <span class="visually-hidden"><?php echo $type; ?></span>
            </div>
            <div class="alert-wrapper">
                <?php foreach ($messages as $message): ?>
                    <div class="alert-message"><?php echo $message['message']; ?></div>
                <?php endforeach; ?>
            </div>
        </joomla-alert>
    <?php endforeach; ?>
</div>
