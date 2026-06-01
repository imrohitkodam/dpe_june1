<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Activity Controller Toolbar
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanControllerToolbarActivity extends ComKoowaControllerToolbarActionbar
{
    protected function _commandExport(KControllerToolbarCommand $command)
    {
        $command->icon = 'k-icon-share-boxed';
        $command->attribs->href = '#';
        $command->attribs->append(array(
            'class' => ['btn-outline-secondary'],
            'data-k-modal' => array(
                'items' => array(
                    'src' => '#logman-export',
                ),
                'type' => 'inline',
                'mainClass' => 'koowa_dialog_modal'
            )
        ));

        $this->_commandDialog($command);
    }

    protected function _afterBrowse(KControllerContextInterface $context)
    {
        parent::_afterBrowse($context);

        $this->removeCommand('new');
        $this->removeCommand('delete');

        $this->addExport();
    }
}