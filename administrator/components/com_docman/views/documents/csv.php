<?php
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * CSV View
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\DOCman
 */
class ComDocmanViewDocumentsCsv extends KViewCsv
{
    protected $_fields = array();

    /**
     * @var ComDocmanTemplateHelperRoute
     */
    protected $_route_helper;

    /**
     * Constructor
     *
     * @param   KObjectConfig $config Configuration options
     */
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_fields = KObjectConfig::unbox($config->fields);

        if (empty($this->_route_helper))
        {
            $this->_route_helper = $this->getObject('com:docman.template.helper.route');
            $this->_route_helper->setRouter(array($this, 'getRoute'));
        }
    }

    protected function _initialize(KObjectConfig $config)
    {
        $settings = $this->getObject('com://admin/docman.model.configs')->fetch();

        $config->append(array(
            'fields' => $settings->csv_fields
        ));

        parent::_initialize($config);
    }

    /**
     * Return the views output
     *
     * @return string    The output of the view
     */
    protected function _actionRender(KViewContext $context)
    {
        $rows    = '';

        $model = $this->getModel();
        $model->page('all');
        $documents = $model->fetch();
        $model->setPage($documents); // Sets the itemid for each document

        //Create the rows
        foreach ($documents as $document)
        {
            $data = array();

            foreach ($this->_fields as $value)
            {
                $field = key($value);
                $state = current($value);

                if ($state == 1)
                {
                    switch ($field)
                    {
                        case 'enabled':
                            $data[$field] = $this->getObject('com:docman.template.helper.string')->state(['state' => $document->{$field}]);
                            break;

                        case 'created_by':
                            $data[$field] = $this->getObject('com:docman.template.helper.string')->author(['entity' => $document]);
                            break;

                        case 'document_url':
                            $data[$field] = $this->_getLink(['entity'=> $document, 'layout' => 'default', 'Itemid' => $document->itemid]);
                            break;
                        
                        case 'download_url':
                            $data[$field] = $this->_getLink(['entity'=> $document, 'view' => 'download', 'force-download' => 1, 'Itemid' => $document->itemid]);
                            break;

                        default:
                            $data[$field] = $document->{$field};
                            break;
                    }
                }
            }

            $rows .= $this->_arrayToString(array_values($data)) . $this->eol;
        }

        // Set the output
        $this->setContent($rows);
        return $this->_content;
    }

    /**
     * Generates a frontend route for a document
     *
     * @param array|KObjectConfig $config
     * @return string Routed URL
     */
    protected function _getLink($config)
    {
        $config['admin_link'] = false;
        
        $route = $this->_route_helper->document($config, true);
        $route->setApplication('site');
        unset($route->query['format']);

        return $route;
    }

    public function getHeader()
    {
        $headers = array();

        foreach ($this->_fields as $value)
        {
            $state = current($value);

            if ($state) {
                $headers[] = key($value);
            }
        }

        return implode(',', $headers) . $this->eol;
    }
}