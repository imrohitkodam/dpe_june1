<?php
/**
 * Joomlatools DOCman
 *
 * @package     DOCman
 * @copyright   Copyright (C) 2020 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/docman for the canonical source repository
 */

$loader = KObjectManager::getInstance()->getObject('manager')->getClassLoader();

if ($path = KObjectManager::getInstance()->getObject('object.bootstrapper')->getApplicationPath('site')) {
    $loader->setBasePath($path);
}

#[\AllowDynamicProperties]
class ComDocmanControllerActivity extends ComLogmanControllerFiltered
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array('behaviors' => array('restrictable' => array('redirect_url' => 'index.php?option=com_docman&view=config'))));

        parent::_initialize($config);
    }

    public function getModel()
    {
        if (!$this->_model instanceof KModelInterface)
        {
            $this->getIdentifier('com://admin/logman.model.activities')
                 ->getConfig()
                 ->append(array('behaviors' => array('com://admin/docman.model.behavior.activities.searchable')));
        }

        return parent::getModel();
    }
}