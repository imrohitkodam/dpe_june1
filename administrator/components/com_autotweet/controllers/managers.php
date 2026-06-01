<?php

/*
 * @package     Perfect Publisher
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2022 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see         https://www.extly.com
 */

defined('_JEXEC') || exit;

/**
 * AutoTweetControllerManagers.
 *
 * @since       1.0
 */
class AutoTweetControllerManagers extends ExtlyController
{
    /**
     * Save the incoming data and then return to the Edit task.
     */
    public function apply()
    {
        $this->input->set('returnurl', '');
        parent::apply();
    }

    /**
     * Creates a new model object.
     *
     * @param string $name   The name of the model class, e.g. Items
     * @param string $prefix The prefix of the model class, e.g. FoobarModel
     * @param array  $config The configuration parameters for the model class
     *
     * @return XTF0FModel The model object
     */
    protected function createModel($name, $prefix = '', $config = [])
    {
        $result = null;

        $modelName = 'Extensions';
        $classPrefix = 'ExtlyModel';

        // Just to load the class
        XTF0FModel::getAnInstance($modelName, $classPrefix, $config);

        $modelName = 'Managers';
        $classPrefix = 'AutoTweetModel';

        $result = XTF0FModel::getAnInstance($modelName, $classPrefix, $config);

        return $result;
    }

    /**
     * Execute something after applySave has run.
     *
     * @return bool True to allow normal return, false to cause a 403 error
     */
    protected function onAfterApplySave()
    {
        EExtensionHelper::cleanCache();

        return parent::onAfterApplySave();
    }
}
