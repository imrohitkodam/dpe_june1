<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Linker Controller
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanControllerLinker extends ComKoowaControllerView
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'resources' => array(
                'content' => 'article',
                'docman'  => 'document',
                'fileman' => 'file'
            ),
            'behaviors' => array(
                'restrictable'
            )
        ));

        parent::_initialize($config);
    }

    /**
     * Passes the e_view parameter that Joomla sends in the request for the editor name.
     *
     * @see KControllerResource::getView()
     */
    public function getView()
    {
        $view = parent::getView();

        if ($view) {
            $view->editor = $this->getRequest()->query->e_name;
        }

        $request = $this->getRequest();

        if ($request->getFormat() == 'json')
        {
            $model = $this->getObject('com:activities.model.resources');

            $query = clone $request->getQuery();

            unset($query->levels);

            $state_values = $query->toArray();

            if ($resources = $this->getConfig()->resources)
            {
                $package_name = array();

                foreach ($resources as $package => $name) {
                    $package_name[] = sprintf('%s.%s', $package, $name);
                }

                $state_values['package_name'] = $package_name;
            }

            $model->getState()->setValues($state_values);

            $view->setModel($model);
        }

        return $view;
    }
}
