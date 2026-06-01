<?php
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComDocmanControllerBatchscan extends KControllerView implements KControllerModellable
{
    /**
     * Model object or identifier (com://APP/COMPONENT.model.NAME)
     *
     * @var	string|object
     */
    protected $_model;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        // Set the model identifier
        $this->_model = $config->model;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'formats'   => array('json', 'binary'),
            'behaviors' => ['com://admin/docman.controller.behavior.script.scan', 'restrictable'],
            'model'     => 'model.empty',
        ));

        parent::_initialize($config);
    }

    protected function _actionRender(KControllerContextInterface $context)
    {
        if (!$this->getObject('com://admin/docman.controller.behavior.scannable')->isSupported()) {
            $error = $this->getObject('translator')->translate('Scanning documents is only available with Joomlatools Connect');
            $context->response->addMessage($error);

            return;
        }
        
        return parent::_actionRender($context);
    }

    protected function _actionScan(KControllerContextInterface $context)
    {
        try {
            $id = $this->getObject('request')->query->get('id', 'int');
            $controller = $this->getObject('com://admin/docman.controller.document');
    
            $entity = $controller->id($id)->read();
    
            if ($entity->isNew()) {
                throw new KControllerExceptionRequestInvalid('Invalid document id');
            }
    
            $response = $controller->id($id)->edit(['force_scan' => 1, 'force_scan_response' => 1]);

            try {
                if (!is_string($response)) {
                    throw new Exception('Invalid response');    
                }
                $response = json_decode($response, true);
            } catch (Exception $e) {
                $response = ["error" => "unknown response"];
            }

            // get document again to return updated data
            $docs = $this->_getDocuments([$id]);
            $doc = !empty($docs) ? $docs[0] : null;

            $context->response->setContent(json_encode(['result' => true, 'document' => $doc, 'response' => $response])); 
        } catch (Exception $e) {
            $context->response->setContent(json_encode(['result' => false, 'error' => $e->getMessage()])); 
        }
    }

    protected function _unhalt()
    {
        try {
            $query = $this->getObject('database.query.select')
                ->columns(['state'])
                ->table('scheduler_jobs')
                ->where('identifier = :identifier')
                ->bind(['identifier' => 'com://admin/docman.job.scans']);
            
            $state = $this->getObject('database.adapter.mysqli')->select($query, KDatabase::FETCH_FIELD);

            $json   = $state ? json_decode($state, true) : [];

            if (isset($json['halt']) && $json['halt'] === true) {
                unset($json['halt']);

                $jsonString = json_encode($json);

                if ($jsonString === "[]" || !$jsonString) {
                    $jsonString = '{}';
                }

                $query = $this->getObject('database.query.update')
                    ->table('scheduler_jobs')
                    ->values('state = :state')
                    ->where('identifier = :identifier')
                    ->bind(['identifier' => 'com://admin/docman.job.scans', 'state' => $jsonString]);

                $this->getObject('database.adapter.mysqli')->update($query);
            }
        } catch (Exception $e) {
            if (JDEBUG) {
                throw $e;
            }
        }
    }

    protected function _actionStartup(KControllerContextInterface $context)
    {
        $show_scanned = $this->getObject('request')->query->get('show_scanned', 'boolean');

        $this->_unhalt();
        $this->_clearScans();
        $this->execute('clear_thumbnails', $context);

        $ids = $this->_findDocuments($show_scanned);

        $result = $this->_getDocuments($ids);

        $context->response->setContent(json_encode(['result' => true, 'documents' => $result]));
    }

    protected function _getDocuments($ids)
    {
        $query = $this->getObject('database.query.select')
            ->columns([
                'tbl.docman_document_id', 'tbl.title', 'tbl.storage_path', 'tbl.storage_type', 'tbl.created_on', 'tbl.image',
                'categoryTitle' => 'category.title',
                'categoryId' => 'tbl.docman_category_id',
                'ocrable' => 'SUBSTRING_INDEX(tbl.storage_path, ".", -1) IN :ocr_extensions',
                'thumbnailable' => '("1" = :automatic_thumbnails AND SUBSTRING_INDEX(tbl.storage_path, ".", -1) IN :thumbnail_extensions)',
                'hasOcr' => 'contents.docman_document_id',
                'hasThumbnail' => '(tbl.image <> :image)',
                'isScannedBefore' => '(tbl.params LIKE :scanned)',
                ])
            ->table(['tbl' => 'docman_documents'])
            ->where('tbl.docman_document_id IN :ids')
            ->join(['contents' => 'docman_document_contents'], 'contents.docman_document_id = tbl.docman_document_id')
            ->join(['category' => 'docman_categories'], 'category.docman_category_id = tbl.docman_category_id')
            ->bind([
                'ids' => $ids ?: [0],
                'image' => '',
                'automatic_thumbnails' => $this->getObject('com://admin/docman.model.configs')->fetch()->thumbnails ? '1' : '2',
                'storage_type' => 'file',
                'thumbnail_extensions' => ComDocmanControllerBehaviorScannable::$thumbnail_extensions,
                'ocr_extensions' => ComDocmanControllerBehaviorScannable::$ocr_extensions,
                'scanned' => '%"scanned"%',
            ])
            ->order('tbl.created_on', 'desc');
            
        $docs = $this->getObject('database.adapter.mysqli')->select($query, KDatabase::FETCH_OBJECT_LIST);

        $fullpath = $this->getObject('com:files.model.containers')->slug('docman-files')->fetch()->fullpath;
        foreach ($docs as $doc) {
            $doc->fileExists = file_exists($fullpath.'/'.$doc->storage_path);
            $doc->fileSize = $doc->fileExists ? @filesize($fullpath.'/'.$doc->storage_path) : 0;
            $doc->scannable = $doc->fileExists && $doc->fileSize <= ComDocmanControllerBehaviorScannable::MAXIMUM_FILE_SIZE;

            foreach ($doc as $key => &$value) {
                if ($key === "docman_document_id") {
                    $value = (int) $value;
                    continue; 
                }

                if ($key === "hasOcr") {
                    if ($value === null) {
                        $value = false;
                    }

                    if ($value === "") { // empty contents even though the file was scanned
                        $value = true;
                    }
                    
                    $value = (bool) $value;
                }

                if ($value === "0" || $value === 0) {
                    $value = false;
                } else if ($value === "1" || $value === 1) {
                    $value = true;
                }
            }
            unset($value);
        }

        return $docs;
    }
    
    protected function _findDocuments($show_scanned = false) 
    {
        $retry_failed = true;

        $query = $this->getObject('database.query.select')
            ->columns(['tbl.docman_document_id'])
            ->table(['tbl' => 'docman_documents'])
            ->join(['scan' => 'docman_scans'], 'scan.identifier = tbl.uuid')
            //->where('scan.identifier IS NULL')
            ->join(['contents' => 'docman_document_contents'], 'contents.docman_document_id = tbl.docman_document_id')
            ->where('tbl.storage_type = :storage_type')
            ->bind([
                'image' => '',
                'automatic_thumbnails' => $this->getObject('com://admin/docman.model.configs')->fetch()->thumbnails ? '1' : '2',
                'storage_type' => 'file',
                'thumbnail_extensions' => ComDocmanControllerBehaviorScannable::$thumbnail_extensions,
                'ocr_extensions' => ComDocmanControllerBehaviorScannable::$ocr_extensions
            ]);

        if (!$show_scanned) {
            $query->where('((contents.docman_document_id IS NULL AND SUBSTRING_INDEX(tbl.storage_path, ".", -1) IN :ocr_extensions)')
                ->where('("1" = :automatic_thumbnails AND tbl.image = :image AND SUBSTRING_INDEX(tbl.storage_path, ".", -1) IN :thumbnail_extensions))', 'OR');
        } else {
            $query->where('SUBSTRING_INDEX(tbl.storage_path, ".", -1) IN :ocr_extensions OR SUBSTRING_INDEX(tbl.storage_path, ".", -1) IN :thumbnail_extensions');
        }

        if (!$retry_failed) {
            $query->where('tbl.params NOT LIKE :scanned')
                ->bind(['scanned' => '%"scanned"%',]);
        }
        
        return $this->getObject('database.adapter.mysqli')->select($query, KDatabase::FETCH_FIELD_LIST);
    }

    protected function _clearScans() 
    {
        try {
            $query = $this->getObject('database.query.update')
            ->table('docman_scans')
            ->values('status = :status')
            ->where('status <> :status')
            ->bind(['status' => ComDocmanControllerBehaviorScannable::STATUS_PENDING]);

            $this->getObject('database.adapter.mysqli')->update($query);
        } catch (Exception $e) {
            if (JDEBUG) {
                throw $e;
            }
        }
    }


    public function getView()
    {
        $view = parent::getView();
        $view->setModel($this->getModel());

        $view->scannerData = [
            'imagePath' => $this->getObject('request')->getSiteUrl().'/'.$this->getObject('com:files.model.containers')->slug('docman-images')->fetch()->path,
            'maximumFileSize' => ComDocmanControllerBehaviorScannable::MAXIMUM_FILE_SIZE,
            'thumbnailExtensions' => ComDocmanControllerBehaviorScannable::$thumbnail_extensions,
            'ocrExtensions' => ComDocmanControllerBehaviorScannable::$ocr_extensions,
        ];
        $view->docmanVersion = ComDocmanVersion::VERSION;

        return $view;
    }

    /**
     * Get the model object attached to the controller
     *
     * @throws	\UnexpectedValueException	If the model doesn't implement the ModelInterface
     * @return	KModelInterface
     */
    public function getModel()
    {
        if(!$this->_model instanceof KModelInterface)
        {
            //Make sure we have a model identifier
            if(!($this->_model instanceof KObjectIdentifier)) {
                $this->setModel($this->_model);
            }

            $this->_model = $this->getObject($this->_model);

            if(!$this->_model instanceof KModelInterface)
            {
                throw new UnexpectedValueException(
                    'Model: '.get_class($this->_model).' does not implement KModelInterface'
                );
            }

            //Inject the request into the model state
            $this->_model->getState()->insert('status', 'cmd');
            $this->_model->setState($this->getRequest()->query->toArray());
        }

        return $this->_model;
    }

    /**
     * Method to set a model object attached to the controller
     *
     * @param	mixed	$model An object that implements KObjectInterface, KObjectIdentifier object
     * 					       or valid identifier string
     * @return	KControllerView
     */
    public function setModel($model)
    {
        if(!($model instanceof KModelInterface))
        {
            if(is_string($model) && strpos($model, '.') === false )
            {
                // Model names are always plural
                if(KStringInflector::isSingular($model)) {
                    $model = KStringInflector::pluralize($model);
                }

                $identifier			= $this->getIdentifier()->toArray();
                $identifier['path']	= array('model');
                $identifier['name']	= $model;

                $identifier = $this->getIdentifier($identifier);
            }
            else $identifier = $this->getIdentifier($model);

            $model = $identifier;
        }

        $this->_model = $model;

        return $this->_model;
    }
}