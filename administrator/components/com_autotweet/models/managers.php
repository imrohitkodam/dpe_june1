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
 * AutotweetModelManagers.
 *
 * @since       1.0
 */
class AutotweetModelManagers extends ExtlyModelExtensions
{
    /**
     * Public class constructor.
     *
     * @param array $config The configuration array
     */
    public function __construct($config = [])
    {
        $config['name'] = 'Extensions';
        $config['table'] = 'Extension';

        parent::__construct($config);
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param string $name    The table name. Optional.
     * @param string $prefix  The class prefix. Optional.
     * @param array  $options Configuration array for model. Optional.
     *
     * @throws Exception
     *
     * @return XTF0FTable A XTF0FTable object
     */
    public function getTable($name = '', $prefix = null, $options = [])
    {
        return parent::getTable($this->table, 'ExtlyTable', $options);
    }

    /**
     * This method runs before the $data is saved to the $table. Return false to
     * stop saving.
     *
     * @param array  &$data  Param
     * @param JTable &$table Param
     *
     * @return bool
     */
    protected function onBeforeSave(&$data, &$table)
    {
        if (!TextUtil::isValidCronjobExpr($data['xtform']['evergreen_freq_mhdmd'])) {
            $this->setError(JText::_('COM_AUTOTWEET_MANAGERS_ERR_CRONEXPR'));

            return false;
        }

        $result = parent::onBeforeSave($data, $table);

        $params = json_decode($data['params']);

        $params->start_time = EParameter::convertLocalUTC($params->start_time);
        $params->start_time = EParameter::getTimePart($params->start_time);

        $params->end_time = EParameter::convertLocalUTC($params->end_time);
        $params->end_time = EParameter::getTimePart($params->end_time);

        $data['params'] = json_encode($params);

        return $result;
    }
}
