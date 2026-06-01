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

// No direct access
defined('_JEXEC') || exit('Restricted access');

/**
 * Form Class for the Extly Library.
 *
 * @since       11.1
 */
class ETable
{
    /**
     * copy.
     *
     * @param XTF0FTable &$table Param
     * @param array      &$cid   Param
     *
     * @return bool
     */
    public function copy(&$table, &$cid = null)
    {
        \Joomla\Utilities\ArrayHelper::toInteger($cid);
        $k = $table->getKeyName();

        if (count($cid) < 1) {
            if ($table->{$k}) {
                $cid = [
                    $table->{$k},
                ];
            } else {
                $table->setError('No items selected.');

                return false;
            }
        }

        $created_by = $table->getColumnAlias('created_by');
        $created_on = $table->getColumnAlias('created_on');
        $modified_by = $table->getColumnAlias('modified_by');
        $modified_on = $table->getColumnAlias('modified_on');

        $locked_byName = $table->getColumnAlias('locked_by');
        $checkin = in_array($locked_byName, array_keys($table->getProperties()), true);

        foreach ($cid as $item) {
            // Prevent load with id = 0
            if (!$item) {
                continue;
            }

            $table->load($item);

            if ($checkin) {
                // We're using the checkin and the record is used by someone else
                if ($table->isCheckedOut($item)) {
                    continue;
                }
            }

            if (!$table->onBeforeCopy($item)) {
                continue;
            }

            $table->{$k} = null;
            $table->{$created_by} = null;
            $table->{$created_on} = null;
            $table->{$modified_on} = null;
            $table->{$modified_by} = null;

            // Let's fire the event only if everything is ok
            if ($table->store()) {
                $table->onAfterCopy($item);
            }

            $table->reset();
        }

        return true;
    }

    /**
     * hasUTF8mb4Support.
     *
     * @return bool
     */
    public static function hasUTF8mb4Support()
    {
        $db = JFactory::getDbo();

        try {
            $hasUtf8mb4Support = $db->hasUTF8mb4Support();
        } catch (Exception $e) {
            $hasUtf8mb4Support = false;
        }

        return $hasUtf8mb4Support;
    }

    /**
     * convertUTF8mb4.
     *
     * @param string $file Param
     */
    public static function convertUTF8mb4($file)
    {
        $buffer = file_get_contents($file);

        $db = JFactory::getDBO();
        $queries = JDatabaseDriver::splitSql($buffer);

        if (0 === count($queries)) {
            // No queries to process
            return;
        }

        // Process each query in the $queries array (split out of sql file).
        foreach ($queries as $query) {
            $query = trim($query);

            if (!empty($query) && '#' !== $query[0]) {
                $db->setQuery($query);

                if (!$db->execute()) {
                    $msg = 'Error convertUTF8mb4 '.$db->stderr(true);
                    JFactory::getApplication()->enqueueMessage($msg, 'error');

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get the collation of a table. Uses an internal cache for efficiency.
     *
     * @param string $tableName The name of the table
     *
     * @return string The collation, e.g. "utf8_general_ci"
     */
    public static function getTableCollation($tableName)
    {
        static $cache = [];

        $db = JFactory::getDbo();
        $tableName = $db->replacePrefix($tableName);

        if (!isset($cache[$tableName])) {
            $cache[$tableName] = self::realGetTableCollation($tableName);
        }

        return $cache[$tableName];
    }

    /**
     * Get the collation of a table. This is the internal method used by getTableCollation.
     *
     * @param string $tableName The name of the table
     *
     * @return string The collation, e.g. "utf8_general_ci"
     */
    public static function realGetTableCollation($tableName)
    {
        $db = JFactory::getDbo();

        try {
            $utf8Support = $db->hasUTFSupport();
        } catch (Exception $e) {
            $utf8Support = false;
        }

        try {
            $utf8mb4Support = $utf8Support && $db->hasUTF8mb4Support();
        } catch (Exception $e) {
            $utf8mb4Support = false;
        }

        $collation = $utf8mb4Support ? 'utf8mb4_unicode_ci' : ($utf8Support ? 'utf_general_ci' : 'latin1_swedish_ci');

        $query = 'SHOW TABLE STATUS LIKE '.$db->q($tableName);

        try {
            $row = $db->setQuery($query)->loadAssoc();
        } catch (Exception $e) {
            return $collation;
        }

        if (empty($row)) {
            return $collation;
        }

        if (!isset($row['Collation'])) {
            return $collation;
        }

        if (empty($row['Collation'])) {
            return $collation;
        }

        return $row['Collation'];
    }
}
