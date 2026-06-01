<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Activity Template Helper
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanTemplateHelperActivity extends ComActivitiesTemplateHelperActivity
{
    /**
     * Holds a list of loaded scripts.
     *
     * @var bool
     */
    static protected $_scripts_loaded;

    /**
     * Constructor.
     *
     * @param   KObjectConfig $config Configuration options
     */
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        self::$_scripts_loaded = array();
    }

    /**
     * Renders an activity date
     *
     * @param ComActivitiesActivityInterface $activity The activity object.
     * @param  array                         $config   An optional configuration array.
     * @return string The rendered activity.
     */
    public function when($config = array())
    {
        $config = new KObjectConfigJson($config);
        $config->append(array(
            'humanize' => false,
            'format'   => 'H:i:s'
        ));

        $activity = $config->entity;
        $helper   = $this->getObject('com://admin/logman.template.helper.date');

        if ($config->humanize)
        {
            $time = $helper->humanize(array(
                'date' => $activity->created_on
            ));
        }
        else
        {
            $time = $helper->format(array(
                'date'   => $activity->created_on,
                'format' => $config->format
            ));
        }

        return $time;
    }

    /**
     * Renders an activity.
     *
     *  Overridden for setting activity scripts.
     *
     * @param ComActivitiesActivityInterface $activity The activity object.
     * @param  array                         $config   An optional configuration array.
     * @return string The rendered activity.
     */
    public function render(ComActivitiesActivityInterface $activity, $config = array())
    {
        $config = new KObjectConfig($config);

        $config->append(array(
            'scripts' => false
        ));

        // Render the activity.
        $output = parent::render($activity, $config);

        if (JFactory::getConfig()->get('debug_lang'))
        {
            $catalogue = $this->getObject('translator')->getCatalogue();
            $formats_map = array_flip($catalogue->toArray());

            $format = $activity->getActivityFormat();

            if (isset($formats_map[$format]))
            {
                if ($length = $catalogue->getConfig()->key_length) {
                    $catalogue->getConfig()->key_length = false;
                }

                $key = $catalogue->getPrefix() . $catalogue->generateKey($formats_map[$format]);

                if ($length) {
                    $catalogue->getConfig()->key_length = $length;
                }

                $output .= sprintf('<div class="logman-short-format">%s="%s"</div>', $key, $format);
            }
        }

        if ($config->scripts)
        {
            $identifier = (string) $config->entity->getIdentifier();

            if (!in_array($identifier, self::$_scripts_loaded))
            {
                if ($scripts = $config->entity->scripts) {
                    $output .= $scripts;
                }

                self::$_scripts_loaded[] = $identifier;
            }
        }

        return $output;
    }
}