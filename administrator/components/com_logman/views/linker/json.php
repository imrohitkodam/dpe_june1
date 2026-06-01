<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Resources JSON View
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanViewLinkerJson extends KViewJson
{
    /**
     * Get the entity data
     *
     * @link http://activitystrea.ms/specs/json/1.0/#json See JSON serialization.
     *
     * @param KModelEntityInterface $entity The model entity.
     * @return array The array with data to be encoded to JSON.
     */
    protected function _getEntity(KModelEntityInterface $entity)
    {
        $data = $entity->getData();

        $activity = $this->getObject('com://admin/logman.model.activities')
                         ->create(array(
                             'row'      => $entity->resource_id,
                             'package'  => $entity->package,
                             'name'     => $entity->name,
                             'context'  => 'site',
                             'metadata' => $data->count() ? $data : array()
                         ));

        $translator = $this->getObject('translator');

        ComLogmanActivityTranslator::loadSysIni(sprintf('com_%s', $entity->package), 'admin');

        $extension = $translator->translate('com_' . $entity->package);
        $resource  = ucfirst($translator->translate($entity->name));
        $title     = $entity->title;

        $text = sprintf('%s (%s: %s)', $title, $extension, $resource);

        $url = $activity->getActivityObject()->getUrl();

        $item = array(
            'disabled' => !isset($url),
            'url'      => isset($url) ? sprintf('index.php?%s', $url->getQuery(false, true)) : null,
            'text'     => $text,
            'title'    => $title
        );

        return $item;
    }
}