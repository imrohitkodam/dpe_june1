<?php
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Csv Exportable Controller Behavior
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\DOCman
 */
class ComDocmanControllerBehaviorExportableCsv extends ComDocmanControllerBehaviorExportable
{
    /**
     * The export file name (used as the filename of the exported data).
     *
     * @var string
     */
    protected $_filename;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_filename = $config->filename;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'filename' => 'documents'
        ));

        parent::_initialize($config);
    }

    protected function _write($config = array())
    {
        $config = new KObjectConfig($config);
        $config->append(array('target' => $this->_getTemporaryFile()));

        if (!$this->getRequest()->query->offset) {
            $config->data = $this->getView()->getHeader() . $config->data; // Append CSV header.
        }

        parent::_write($config);

        // Set the file location in the session.
        $this->getObject('user')->set($this->_getSessionContainer(), $config->target);
    }

    protected function _beforeRender(KControllerContextInterface $context)
    {
        $request = $this->getRequest();

        if ($request->query->export)
        {
            $file = $context->getUser()->get($this->_getSessionContainer(), null);

            if (!is_null($file))
            {
                // Clear session info.
                $context->getUser()->set($this->_getSessionContainer(), null);

                //Set the data in the response
                try
                {
                    $this->getResponse()
                        ->attachTransport('stream')
                        ->setContent($file, 'application/octet-stream')
                        ->getHeaders()->set('Content-Disposition', ['attachment' => ['filename' => '"' . $this->_filename . '.csv"']]);
                }
                catch (InvalidArgumentException $e) {
                    throw new KControllerExceptionResourceNotFound('File not found');
                }

                return false;
            }
            else throw new RuntimeException('Export file not found');
        }
        else parent::_beforeRender($context);

        return true;
    }

    /**
     * Session container getter.
     *
     * Provides a session variable for storing the temporary location of the exported file.
     *
     * @return string
     */
    protected function _getSessionContainer()
    {
        return 'session.' . $this->getMixer()->getIdentifier() . '.' . $this->_format . '.export';
    }
}