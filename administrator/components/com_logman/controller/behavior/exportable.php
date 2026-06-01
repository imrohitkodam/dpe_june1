<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Exportable Controller Behavior
 *
 * Provides a pluggable mechanism for exporting data from a view. By default, data gets written to
 * a temporary location within the filesystem. It also supports incremental exports, i.e. using multiple
 * requests, making it less prone to timeouts and memory errors.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanControllerBehaviorExportable extends ComLogmanControllerBehaviorCursorable
{
    /**
     * The export format (used for determining the export view).
     *
     * @var string
     */
    protected $_format;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_format = $config->format;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array('format'   => 'csv'));

        parent::_initialize($config);
    }

    public function isSupported()
    {
        $result = false;

        $request = $this->getMixer()->getRequest();

        if (($request->getFormat() == $this->_format || $request->getQuery()->export)) {
            $result = true;
        }

        return $result;
    }

    protected function _afterRender(KControllerContextInterface $context)
    {
        parent::_afterRender($context);

        if ($this->getRequest()->getFormat() == $this->_format) {
            $this->_write(array('data' => $context->result));
        }
    }

    /**
     * Writes the data provided by the view, aka exported data.
     *
     * @param array $config An optional configuration array.
     *
     * @throws RuntimeException If a problem is encountered.
     */
    protected function _write($config = array())
    {
        $config = new KObjectConfig($config);

        $config->append(array(
            'data'   => '',
            'target' => $this->_getTemporaryFile()
        ));

        if ($this->_doCleanup())
        {
            jimport('joomla.filesystem.file');

            if (file_exists($config->target) && !JFile::delete($config->target)) {
                throw new RuntimeException('Unable to delete temporary file during cleanup.');
            }
        }

        $file_obj = new SplFileObject($config->target, 'a');
        $file_obj->fwrite($config->data);
    }

    /**
     * Determines if a cleanup (delete last exported file) must be performed.
     *
     * @return bool True if a cleanup must be performed, false otherwise.
     */
    protected function _doCleanup()
    {
        return (bool) !$this->getRequest()->query->offset;
    }

    /**
     * Returns a temporary file location.
     *
     * Additionally checks for Joomla tmp folder if the system directory is not writable
     *
     * @param array $config An optional configuration array.
     *
     * @throws RuntimeException
     * @return string Folder path
     */
    protected function _getTemporaryFile($config = array())
    {
        static $file;

        if (!isset($file))
        {
            $name = $this->getMixer()->getIdentifier()->name;

            $config = new KObjectConfig($config);
            $config->append(array(
                    'name' => KStringInflector::pluralize($name) . '.' . $this->_format)
            );

            $path = false;

            $candidates = array(
                JPATH_ROOT . '/tmp',
                ini_get('upload_tmp_dir'),
            );

            if (function_exists('sys_get_temp_dir')) {
                array_push($candidates, sys_get_temp_dir());
            }

            foreach ($candidates as $folder)
            {
                if ($folder && @is_dir($folder) && is_writable($folder))
                {
                    $path = rtrim($folder, '\\/');
                    break;
                }
            }

            if ($path === false) {
                throw new RuntimeException('Cannot find a writable temporary directory');
            }

            $file = $path . '/' . $config->name;
        }

        return $file;
    }
}