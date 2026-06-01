<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Cursorable Controller Behavior
 *
 * Provides support for cursor based pagination.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanControllerBehaviorCursorable extends KControllerBehaviorAbstract
{
    /**
     * The pagination limit.
     *
     * @var int
     */
    protected $_limit;

    /**
     * The cursorable database behavior.
     *
     * @var mixed
     */
    protected $_behavior;

    /**
     * Response Link header parts
     *
     * @var array
     */
    protected $_headers;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);


        $direction = $config->direction;
        $limit = $config->limit;

        if ($config->request_overrides)
        {
            $query = $this->getMixer()->getRequest()->getQuery();

            if (isset($query->direction)) {
                $direction = $query->direction;
            }

            if (isset($query->limit)) {
                $limit = $query->limit;
            }
        }

        $this->_headers   = $config->headers;
        $this->_direction = $direction;
        $this->_limit     = (int) $limit;
        $this->_behavior  = $config->behavior;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'headers'           => array('next'),
            'request_overrides' => true,
            'limit'             => 5,
            'direction'         => 'ASC',
            'behavior'          => 'com://admin/logman.database.behavior.cursorable'
        ));

        parent::_initialize($config);
    }

    protected function _beforeRender(KControllerContextInterface $context)
    {
        $table = $this->getModel()->getTable();

        if (is_string($this->_behavior) || $this->_behavior instanceof KObjectIdentifier)
        {
            $query = $context->getRequest()->getQuery();

            $this->_behavior = $this->getObject((string) $this->_behavior,
                array(
                    'direction' => $query->direction ? $query->direction : 'ASC',
                    'limit'     => $this->_limit,
                    'offset'    => $query->offset,
                    'mixer'     => $table
                ));
        }

        if (!$this->_behavior instanceof ComLogmanDatabaseBehaviorCursorable) {
            throw new UnexpectedValueException('Behavior must be an instance of ComLogmanDatabaseBehaviorCursorable');
        }

        $table->addBehavior($this->_behavior);
    }

    protected function _afterRender(KControllerContextInterface $context)
    {
        $context->getResponse()->getHeaders()->add($this->_getHeaders());
    }

    protected function _getHeaders()
    {
        $headers = array();

        $request = $this->getRequest();
        $query   = clone $request->getQuery();
        $model   = $this->getModel();
        $table   = $model->getTable();

        $query->limit = $this->_limit;

        // Set the total header.
        $headers['X-Total-Count'] = $model->count();

        $headers['Link'] = array();

        foreach ($this->_headers as $header)
        {
            $method = 'get' . ucfirst($header);

            if (in_array($method, $table->getMethods()) && $result = $table->$method())
            {
                $query->offset    = key($result);
                $query->direction = current($result);

                $route = $this->getObject('com:koowa.dispatcher.router.route')->setQuery($query);

                $route->setUrl($request->getUrl()->toString(KHttpUrl::AUTHORITY));

                $headers['Link'][] = sprintf('<%s>; rel="%s"', $route->toString(), $header);
            }
        }

        if (!empty($headers['Link'])) {
            $headers['Link'] = implode(', ', $headers['Link']);
        }
        else unset($headers['Link']);

        return $headers;
    }
}