<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Filtered Controller Toolbar
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanControllerToolbarFiltered extends ComKoowaControllerToolbarActionbar
{
    protected function _commandPurge(KControllerToolbarCommand $command)
    {
        $command->attribs->href = '#';
        $command->attribs->append(array(
            'data-k-modal' => array(
                'items' => array(
                    'src' => '#logman-purge',
                ),
                'type' => 'inline',
                'mainClass' => 'koowa_dialog_modal'
            )
        ));
        $command->icon = 'k-icon-box';

        $this->_commandDialog($command);
    }

    protected function _commandExport(KControllerToolbarCommand $command)
    {
        $command->icon = 'k-icon-share-boxed';
        $command->attribs->href = '#';
        $command->attribs->append(array(
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

        $controller = $this->getController();

        if ($controller->canPurge()) {
            $this->addPurge();
        }

        $this->addExport();
    }
}