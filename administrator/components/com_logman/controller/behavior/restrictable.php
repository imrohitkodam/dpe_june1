<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Restrictable Controller Behavior.
 *
 * Avoids the creation of duplicate resource entries during a request by comparing computed signatures from request data.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */

class ComLogmanControllerBehaviorRestrictable extends KControllerBehaviorAbstract
{
    /**
     * Signatures of resources already added.
     *
     * @var array
     */
    protected $_signatures = array();

    /**
     * Properties to use for computing signatures.
     *
     * @var array
     */
    protected $_properties;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_signatures = array();

        $this->_properties = $config->properties;
    }

    protected function _initialize(KObjectConfig $config)
    {
        if (!$config->properties) {
            $config->properties = array('package', 'name', 'action', 'row');
        }

        parent::_initialize($config);
    }

    protected function _beforeAdd(KControllerContextInterface $context)
    {
        $result = false;

        $data = $context->getRequest()->getData()->toArray();

        if ($signature = $this->_getSignature($data))
        {
            if (!in_array($signature, $this->_signatures))
            {
                $this->_signatures[] = $signature;
                $result              = true;
            }
        }
        else $result = true;

        return $result;
    }

    /**
     * Signature getter.
     *
     * @param mixed $data The data to compute the signature with.
     * @return null|string The signature, null if there's no data to compute the signature with.
     */
    protected function _getSignature($data)
    {
        $signature = null;

        $data = (array) $data;

        if (!empty($data))
        {
            $properties = KObjectConfig::unbox($this->_properties);

            $data = array_intersect_key($data, array_combine($properties, $properties));

            $signature = md5(implode('', array_values($data)));
        }

        return $signature;
    }
}