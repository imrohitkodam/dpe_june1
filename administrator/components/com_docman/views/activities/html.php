<?php
/**
 * @package     FILEman
 * @copyright   Copyright (C) 2020 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com/fileman for the canonical source repository
 */

$loader = KObjectManager::getInstance()->getObject('manager')->getClassLoader();

if ($path = KObjectManager::getInstance()->getObject('object.bootstrapper')->getApplicationPath('site')) {
    $loader->setBasePath($path);
}

class ComDocmanViewActivitiesHtml extends ComLogmanViewFilteredHtml
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'decorator'  => $config->layout === 'select' ? 'koowa' : 'joomla'
        ]);

        parent::_initialize($config);
    }
}