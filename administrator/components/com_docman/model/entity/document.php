<?php
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComDocmanModelEntityDocument extends KModelEntityRow
{
    public static $extension_type_map = array(
        'archive'     => array('7z','gz','rar','tar','zip'),
        'audio'       => array('mp3', '3gp', 'act', 'aiff', 'aac', 'amr', 'au', 'awb', 'dct', 'dss', 'dvf', 'flac', 'gsm', 'm4a', 'm4p', 'ogg', 'oga', 'ra', 'rm', 'raw', 'tta', 'vox', 'wav', 'wma', 'wv', 'webm'),
        'document'    => array('pdf', 'csv', 'doc','docx','odc','odg','odp','ods', 'odt', 'otc','otg', 'otp','ott', 'rtf','txt','ppt','pptx','pps','tsv', 'tab','xls', 'xlsx','xml'),
        'image'       => array('ai','bmp','cr2','crw','eps','erf','gif','jpg','jpeg','nef','orf','png','pbm','pgm', 'ppm','psd','svg','tif','tiff','x3f','xbm'),
        'video'       => array('webm','mkv','flv','vob','ogv','ogg','avi','rm','rmvb','mp4','m4p','m4v','asf','mpg','mpeg','mpv','mpe','3gp','3g2','roq','nsv'),
        'executable'  => array('cmd', 'exe','bat','bin','apk','msi', 'dmg')
    );

    /**
     * viewable extensions
     *
     * @var array
     */
    public static $viewable_extensions = array('mp3','ogg','mp4','wav','webm','mse', 'jpg', 'jpeg', 'gif', 'png', 'tiff', 'tif', 'xbm', 'bmp');

    /**
     * Initializes the options for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param  KObjectConfig $config An optional ObjectConfig object with configuration options.
     * @return void
     */
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'file_creation_date' => false
        ));

        parent::_initialize($config);
    }

    public function save()
    {
        $this->storage_path = trim($this->storage_path);

        if ($this->isNew() && empty($this->storage_type)) {
            $this->storage_type = 'file';
        }

        if (!in_array($this->storage_type, array('file', 'remote')))
        {
            $this->setStatusMessage($this->getObject('translator')->translate('Storage type is not available'));
            $this->setStatus(KDatabase::STATUS_FAILED);

            return false;
        }

        if ($this->storage_type == 'remote')
        {
            $schemes = $this->getSchemes();
            $scheme  = parse_url($this->storage_path, PHP_URL_SCHEME);

            if (isset($schemes[$scheme]) && $schemes[$scheme] === false)
            {
                $this->setStatusMessage($this->getObject('translator')->translate('Storage type is not allowed'));
                $this->setStatus(KDatabase::STATUS_FAILED);

                return false;
            }
        }

        if (empty($this->docman_category_id))
        {
            if ($this->isNew())
            {
                $this->setStatusMessage($this->getObject('translator')->translate('Category cannot be empty'));
                $this->setStatus(KDatabase::STATUS_FAILED);

                return false;
            }
            else
            {
                unset($this->docman_category_id);
                unset($this->_modified['docman_category_id']);
            }
        }

        if (!$this->getParameters()->icon)
        {
            $icon = $this->getIcon($this->extension);

            if (empty($icon)) {
                $icon = 'default';
            }

            $this->getParameters()->icon = $icon;
        }

        // If enabled, use the file modified date for document's creation date
        if ($this->isNew() && $this->storage_type == 'file' && $this->getConfig()->file_creation_date)
        {
            $fullpath = $this->getObject('com:files.model.containers')->slug('docman-files')->fetch()->fullpath;

            $this->created_on = $this->getObject('com://admin/docman.controller.behavior.syncable')->getModifiedTime("{$fullpath}/{$this->storage_path}");
        }

        $result = parent::save();

        if (!$this->isNew() && isset($this->contents))
        {
            try {
                $model = $this->getObject('com://admin/docman.model.document_contents');
                $contents = $model->id($this->id)->fetch();
    
                if ($contents->isNew()) {
                    $contents = $model->create();
                    $contents->id = $this->id;
                }
    
                $contents->contents = $this->contents;
                $contents->save();
            } catch (Exception $e) {
                if (JDEBUG) {
                    throw $e;
                }
            }
        }

        return $result;
    }

    public function toArray()
    {
        $data              = parent::toArray();
        $data['extension'] = $this->extension;
        $data['size']      = $this->size;
        $data['kind']      = $this->kind;

        unset($data['storage']);

        return $data;
    }

    public function getStorageInfo()
    {
        if (!isset($this->_data['storage']))
        {
            if (!empty($this->_data['storage_type']))
            {
                $this->_data['storage'] = $this->getObject('com://admin/docman.model.storages')
                    ->container('docman-files')
                    ->storage_type($this->_data['storage_type'])
                    ->storage_path($this->_data['storage_path'])
                    ->fetch();
            }
            else  $this->_data['storage'] = null;
        }

        return $this->_data['storage'];
    }

    /**
     * Get a list of the supported streams.
     *
     * We use a whitelist approach to be secure against unknown streams
     *
     * @return array
     */
    public function getSchemes()
    {
        $streams = stream_get_wrappers();
        $allowed  = array(
            'http'  => true,
            'https' => true,
            'file'  => false,
            'ftp'   => false,
            'sftp'  => false,
            'php'   => false,
            'zlib'  => false,
            'data'  => false,
            'glob'  => false,
            'expect'=> false
        );

        if (in_array('file', $streams)) {
            $allowed['file'] = true;
        }

        // Following streams depend on allow_url_fopen
        if (ini_get('allow_url_fopen'))
        {
            foreach (array('ftp', 'sftp') as $stream)
            {
                if (in_array($stream, $streams)) {
                    $allowed[$stream] = true;
                }
            }
        }

        return $allowed;
    }

    public function getIcon($extension)
    {
        $extension = strtolower($extension);

        foreach (ComFilesTemplateHelperIcon::getIconExtensionMap() as $type => $extensions)
        {
            if (in_array($extension, $extensions)) {
                return $type;
            }
        }

        return false;
    }

    public function getProperty($name)
    {
        if ($name === 'alias') {
            return isset($this->_data['alias']) ? $this->_data['alias'] : $this->id.'-'.$this->slug;
        }

        if ($name === 'contents') {
            if (isset($this->_data['contents'])) {
                return $this->_data['contents'];
            }
            elseif (!$this->isNew()) {
                $model = $this->getObject('com://admin/docman.model.document_contents');

                return $model->id($this->id)->fetch()->contents;
            }
        }

        return parent::getProperty($name);
    }

    public function getPropertyImagePath()
    {
        if ($this->image)
        {
            $path = $this->getObject('com:files.model.containers')->slug('docman-images')->fetch()->path;

            $image = implode('/', array_map('rawurlencode', explode('/', $this->image)));

            return $this->getObject('request')->getSiteUrl().'/'.$path.'/'.$image;
        }

        return null;
    }

    public function getPropertyIcon()
    {
        $icon = $this->getParameters()->get('icon', 'default');

        // Backwards compatibility: remove .png from old style icons
        if (substr($icon, 0, 5) !== 'icon:' && substr($icon, -4) === '.png') {
            $icon = substr($icon, 0, strlen($icon)-4);
        }

        return $icon;
    }

    public function getPropertyIconPath()
    {
        $path = $this->icon;

        if (substr($path, 0, 5) === 'icon:')
        {
            $path  = $this->getObject('com:files.model.containers')->slug('docman-icons')->fetch()->path;
            $icon = implode('/', array_map('rawurlencode', explode('/', substr($path, 5))));
            $path = $this->getObject('request')->getSiteUrl().'/'.$path.'/'.$icon;
        } else {
            $path = null;
        }

        return $path;
    }

    public function getPropertyStorage()
    {
        return $this->getStorageInfo();
    }

    public function getPropertyCategory()
    {
        return $this->getObject('com://admin/docman.model.categories')->id($this->docman_category_id)->fetch();
    }

    public function getPropertyDescriptionSummary()
    {
        $description = $this->description;

        if ($description) 
        {
            $position    = strpos($description, $this->_getReadMore($description));
            if ($position !== false) {
                return substr($description, 0, $position);
            }
        }

        return $description;
    }

    public function getPropertyDescriptionFull()
    {
        return str_replace($this->_getReadMore($this->description), '', $this->description ?: '');
    }

    public function getPropertySize()
    {
        if ($this->getStorageInfo()) {
            return $this->storage->size;
        }

        return null;
    }

    public function getPropertyExtension()
    {
        if ($this->getStorageInfo()) {
            return $this->storage->extension;
        }

        return null;
    }

    public function getPropertyMimetype()
    {
        $result = null;

        if ($this->getStorageInfo())
        {
            $result = $this->storage->mimetype;

            if (!$result && $this->extension)
            {
                $entity = $this->getObject('com:files.model.mimetypes')
                    ->extension($this->extension)
                    ->fetch();

                if ($entity && $entity->mimetype) {
                    $result = $entity->mimetype;
                }
            }
        }

        return $result;
    }

    public function getPropertyFiletype()
    {
        $result = null;

        if ($this->getStorageInfo())
        {
            $extension = strtolower($this->extension);

            foreach (static::$extension_type_map as $type => $extensions) {
                if (in_array($extension, $extensions)) {
                    $result = $type;
                    break;
                }
            }
        }

        return $result;
    }

    public function getPropertyPermalink()
    {
        return $this->getPermalink();
    }

    public function isArchive()
    {
        return in_array(strtolower($this->extension), static::$extension_type_map['archive']);
    }

    public function isAudio()
    {
        return in_array(strtolower($this->extension), static::$extension_type_map['audio']);
    }

    public function isDocument()
    {
        return in_array(strtolower($this->extension), static::$extension_type_map['document']);
    }

    public function isExecutable()
    {
        return in_array(strtolower($this->extension), static::$extension_type_map['executable']);
    }

    public function isImage()
    {
        return in_array(strtolower($this->extension), static::$extension_type_map['image']);
    }

    public function isPreviewableImage()
    {
        return $this->isImage() && in_array($this->extension, self::$viewable_extensions);
    }

    public function isVideo()
    {
        return in_array(strtolower($this->extension ?: ''), static::$extension_type_map['video']);
    }

    public function isYoutube()
    {
        if (strpos($this->storage->path, 'youtube.com/watch') === false
            && strpos($this->storage->path, 'youtu.be') === false) {
            return false;
        }

        return true;
    }

    public function isVimeo()
    {
        if (strpos($this->storage->path, 'vimeo.com') === false) {
            return false;
        }

        return true;
    }

    public function isPlayable()
    {
        if ($this->isVideo()) {
            return true;
        }

        if ($this->isAudio()) {
            return true;
        }

        if ($this->isVimeo()) {
            return true;
        }

        if ($this->isYoutube()) {
            return true;
        }

        return false;
    }

    /**
     * The document is viewable in a viewer such as img, pdf, gdocs viewer, and connect is available
     * 
     * @return boolean
     */
    public function isViewable()
    {
        return ($this->isImage() || $this->isDocument() || $this->gDocsPreviewable()) && $this->getObject('com://admin/docman.model.entity.config')->connectAvailable();
    }

    public function isTopSecret()
    {
        return false;
    }

    /**
     * Returns the kind of the file
     *
     * Used in RSS:Media (audio, image, video, executable, document)
     *
     * @return string
     */
    public function getPropertyKind()
    {
        $result = null;

        if ($this->getStorageInfo())
        {
            $result = 'document';

            if ($this->isAudio()) {
                $result = 'audio';
            }
            elseif ($this->isVideo()) {
                $result = 'video';
            }
            elseif ($this->isImage()) {
                $result = 'image';
            }
            elseif ($this->isExecutable()) {
                $result = 'executable';
            }
        }

        return $result;
    }

    /**
    *  Show text if extension is previewable in google docs
    *
    * @return bool
    */
    public function gDocsPreviewable()
    {
        $pattern = '/https?:\/\/(docs|drive)\.google.com\/\S+/';

        if ($this->storage_type == 'remote' && preg_match($pattern, $this->storage_path)) {
            return true;
        }

        return false;
    }

    /**
     * Permalink for sharing protected files
     * 
     * @see ComDocmanEventSubscriberPermalink for re-routing the permalink
     * 
     * @param  array $claims An optional array containing additional claims
     * @return string
     */
    public function getPermalink($claims = array())
    {
        // Generate token
        $token  = $this->getObject('lib:http.token')->setSubject($this->slug);

        foreach ($claims as $key => $value) {
            $token->setClaim($key, $value);
        }

        $secret = JFactory::getConfig()->get('secret');
        $token  = $token->sign($secret);

        // Build the URL
        $root_url = $this->getObject('request')->getSiteUrl();

        if (!JFactory::getConfig()->get('sef') || (JFactory::getConfig()->get('sef') && !JFactory::getConfig()->get('sef_rewrite'))) {
            $root_url .= '/index.php';
        }

        return "{$root_url}/doclink/{$this->slug}/{$token}";
    }

    public function isLocal()
    {
        return $this->storage_type === 'file';
    }

    /**
     * Get the proper readmore element
     * @param  string $text The text to search for the readmore element
     * @return string|null The readmore element if found, null otherwise
     */
    protected function _getReadMore($text)
    {
         $result = '<hr id="system-readmore" />';

        if (preg_match('/<hr\s+id="system-readmore"\s*\/?>/', $text, $matches)) {
            $result = array_shift($matches);
        }
        
        return $result;
    }
}
