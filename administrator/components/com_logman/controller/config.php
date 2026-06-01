<?php
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComLogmanControllerConfig extends ComKoowaControllerModel
{
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->addCommandCallback('after.save',   '_setRedirect');
        $this->addCommandCallback('after.apply',  '_setRedirect');
        $this->addCommandCallback('after.cancel', '_setRedirect');
        $this->addCommandCallback('before.apply', '_fixData');
        $this->addCommandCallback('before.save', '_fixData');
    }

    /**
     * Avoid getting redirected to the configs view. It doesn't exist.
     */
    protected function _setRedirect(KControllerContextInterface $context)
    {
        $response = $context->getResponse();

        if ($response->isRedirect())
        {
            $url = $response->getHeaders()->get('Location');

            if (strpos($url, 'view=configs') !== false) {
                $response->setRedirect(str_replace('view=configs', 'view=activities', $url));
            }
        }
    }

    protected function _actionEditPlugin(KControllerContextInterface $context)
    {
        $request = $context->getRequest();

        $state = $request->data->state;
        $name  = $request->data->name;

        $query = "UPDATE #__extensions SET enabled = {$state} WHERE name = '{$name}'";

        $db = JFactory::getDBO();
        $db->setQuery($query);

        return $db->execute();
    }

    /**
     * Makes sure that selectors can be reset
     */
    protected function _fixData(KControllerContextInterface $context)
    {
        $data = $context->getRequest()->getData();

        if (!isset($data['ignored_groups'])) {
            $data['ignored_groups'] = array();
        }
    }

    protected function _actionRefresh_license(KControllerContextInterface $context)
    {
        $this->getObject('license')->refresh();

        return new KObjectConfigJson([
            'success' => true
        ]);
    }
}
