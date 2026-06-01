<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;

class PlgSystemRobotsmanagerHelper
{
    /**
     * Validate unique access levels (no duplicates allowed)
     *
     * @param   array  $mappings  The mappings array to validate
     *
     * @return  bool
     *
     * @throws  Exception
     *
     * @since   1.0.0
     */
    public static function validateMappings($mappings)
    {
        // Support nested structure: ['rules' => [...]] as produced by some subform configs
        if (isset($mappings['rules']) && is_array($mappings['rules']))
        {
            $mappings = $mappings['rules'];
        }

        $accessSeen = [];

        foreach ($mappings as $map)
        {
            // Skip completely empty rows
            if (!is_array($map) || (empty($map['access']) && empty($map['robots'])))
            {
                continue;
            }

            // If access is missing or empty, skip row for duplicate check but flag as invalid when saving if needed
            if (!isset($map['access']) || $map['access'] === '' || $map['access'] === null)
            {
                // Allow saving as long as there are no duplicates; frontend already marks required
                // But if you want to be strict, uncomment the next line
                // throw new \Exception('Each rule must include an Access Level.');
                continue;
            }

            $accessValue = (int) $map['access'];
            if ($accessValue <= 0)
            {
                // Treat non-positive as empty; skip
                continue;
            }

            if (in_array($accessValue, $accessSeen, true))
            {
                throw new \Exception('Duplicate access level found: ' . $accessValue);
            }
            $accessSeen[] = $accessValue;
        }

        return true;
    }
}
