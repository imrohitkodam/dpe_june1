<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Http Dispatcher
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanDispatcherHttp extends ComKoowaDispatcherHttp
{
	protected function _initialize(KObjectConfig $config)
	{
		$config->append(array(
			'controller' => 'activity',
			'behaviors'  => array('docman')
		));

		parent::_initialize($config);
	}

	public function getRequest()
	{
		if (!$this->_request instanceof KDispatcherRequestInterface)
		{
			$request = parent::getRequest();

			$query = $request->getQuery();

			$query->context = 'administrator';
			$query->levels  = $this->getUser()->getRoles();

			if ($query->view == 'impressions')
            {
                $date = $this->getObject('date');

                if (!$query->end_date) {
                    $query->end_date = $date->format('Y-m-d');
                }

                if (!$query->start_date || $query->start_date > $query->end_date)
                {
                    $date = $this->getObject('date', array('date' => $query->end_date));

                    $query->start_date = $date->modify('-1 month')->format('Y-m-d');
                }
            }

            if ($query->view == 'activities' && !$query->has('read')) {
                $query->read = 0;
            }
		}

		return $this->_request;
	}
}