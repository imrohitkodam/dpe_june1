<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComDocmanTemplateHelperBehavior extends ComKoowaTemplateHelperBehavior
{
    public function multidownload($config = []) {
        $config = new KObjectConfigJson($config);
        $config->append([]);

        $html = '';

        if (!static::isLoaded('docman-multidownload'))
        {
            $html = $this->jquery($config);
            $html .= $this->tooltip($config);
            $html .= $this->getTemplate()->helper('translator.script', ['strings' => [
                'Preparing download',
                'You are not authorized to download the selected file',
                'Remote files cannot be downloaded in batch'
            ]]);
            $html .= '<ktml:script src="media://com_docman/js/multidownload.js" />';
            $html .= '
            <style>
                /* get rid of the red-on-white button on hover */
                .k-js-multi-download.disabled:hover {
                    background-color: inherit !important;
                    color: inherit !important;
                }
            </style>
            <script>
            
                if (typeof Docman === "undefined") {
                    Docman = {};
                }
            
                Docman.token = '.json_encode($this->getObject('user')->getSession()->getToken()).';
            </script>
            ';

            static::setLoaded('docman-multidownload');
        }

        return $html;
    }

    /**
     * A feature to tick multiple checkboxes at the same time
     * Tick a range of checkboxes by holding the 'shift' key
     *
     * @param mixed $config
     * @return string
     */
    public function multiselect($config = [])
    {
        $config = new KObjectConfigJson($config);
        $config->append([
            'selector' => '.k-js-item-select'
        ]);

        $html = '';

        if (!static::isLoaded('docman-multiselect'))
        {
            $html .= '<ktml:script src="media://com_docman/js/multiselect.js" />';
            $html .= "<script>
                kQuery(function($){
                    $('{$config->selector}').multiselect();
                });
                </script>";

            static::setLoaded('docman-multiselect');
        }

        return $html;
    }

    public function downloadlabel($config = array())
    {
        $config = new KObjectConfigJson($config);
        $config->append([
            'params' => []
        ])->append([
            'force_download' => $config->params->force_download,
            'gdocs_supported_extensions' => $this->getObject('com://site/docman.controller.behavior.previewable')->getGooglePreviewExtensions(),
            'gdocs_preview' => (int) $config->params->preview_with_gdocs
        ]);

        $html = '';

        unset($config->params);

        $signature = md5(serialize(array($config->gdocs_preview, $config->gdocs_supported_extensions)));
        if (empty($config->force_download) && !static::isLoaded($signature)) {

            unset($config->params);

            $html .= $this->getTemplate()->helper('translator.script', array('strings' => array('Play', 'View', 'Open')));

            $html .= "
            <ktml:script src=\"media://com_docman/js/site/downloadlabel.js\" />
            <script>
                kQuery(function($) {
                    $('a.docman_download__button').downloadLabel($config);
                });
            </script>
            ";

            static::setLoaded($signature);
        }

        return $html;
    }

    public function photoswipe($config = array())
    {
        $config = new KObjectConfigJson($config);
        $config->append(array(
            'template' => 'com://site/docman.document.photoswipe.html'
        ));

        $html = $this->getTemplate()
                     ->loadFile($config->template)
                     ->render(array('config' => $config));

        return $html;
    }

    /**
     * Shorthand to use in template files in frontend
     *
     * @param array $config
     * @return string
     */
    public function thumbnail_modal($config = array())
    {
        $config = new KObjectConfigJson($config);
        $config->append(array(
            'selector' => '.k-ui-namespace .thumbnail',
            'options'  => array(
                'type' => 'image'
            )
        ));

        return $this->modal($config);
    }

    /**
     * Uses Google Analytics to track download events in frontend
     * @param array $config
     * @return string
     */
    public function download_tracker($config = array())
    {
        $config = new KObjectConfigJson($config);
        $config->append(array(
            'selector' => 'docman_track_download',
            'category' => 'DOCman',
            'action'   => 'Download'
        ));

        $html = $this->jquery();

        $signature = md5(serialize(array($config->selector, $config->category, $config->action)));
        if (!static::isLoaded($signature)) {
            $html .= "
            <script>
            kQuery(function($) {
                $('.{$config->selector}').on('click', function() {
                    var el = $(this);
                    
                    if (typeof gtag !== 'undefined') {
                        gtag('event', '{$config->action}', {
                            'event_category': '{$config->category}',
                            'event_label': el.data('title'),
                            'name': el.data('title'),
                            'value': parseInt(el.data('id'), 10)
                        });
                    }
                    else if (typeof window.GoogleAnalyticsObject !== 'undefined' && typeof window[window.GoogleAnalyticsObject] !== 'undefined') {
                        window[window.GoogleAnalyticsObject]('send', 'event', '{$config->category}', '{$config->action}', el.data('title'), parseInt(el.data('id'), 10));
                    } 
                    else if (typeof _gaq !== 'undefined' && typeof _gat !== 'undefined') {
                        if (_gat._getTrackers().length) {
                            _gaq.push(function() {
                                var tracker = _gat._getTrackers()[0];
                                tracker._trackEvent('{$config->category}', '{$config->action}', el.data('title'), parseInt(el.data('id'), 10));
                            });
                        }
                    }
                });
            });
            </script>
            ";
            static::setLoaded($signature);
        }

        return $html;
    }

    /**
     * Makes links delete actions
     *
     * Used in frontend delete buttons
     *
     * @param array $config
     * @return string
     */
    public function deletable($config = array())
    {
        $config = new KObjectConfigJson($config);
        $config->append(array(
            'selector' => '.docman-deletable',
            'confirm_message' => $this->getObject('translator')->translate('You will not be able to bring this item back if you delete it. Would you like to continue?'),
        ));

        $html = $this->koowa();

        $signature = md5(serialize(array($config->selector,$config->confirm_message)));
        if (!static::isLoaded($signature)) {
            $html .= "
            <script>
            kQuery(function($) {
                $('{$config->selector}').on('click', function(event){
                    event.preventDefault();

                    var target = $(event.target);

                    if (!target.hasClass('k-is-disabled') && confirm('{$config->confirm_message}')) {
                        new Koowa.Form($.parseJSON(target.prop('rel'))).submit();
                    }
                });
            });
            </script>
            ";

            static::setLoaded($signature);
        }

        return $html;
    }

    /**
     * Widget for picking an icon
     *
     * Renders as a button that toggles a dropdown menu, with a list over selectable icon thumbs at the top
     * and a Choose Custom button that triggers a modal popup with a file browser for choosing a custom image.
     *
     * Used in document and category forms next to the title input element
     *
     * @param array $config
     * @return string
     */
    public function icon($config = array())
    {
        $config = new KObjectConfigJson($config);
        $config->append(array(
            'name' => '',
            'attribs' => array(),
            'visible' => true,
            'link' => '',
            'link_text' => $this->getObject('translator')->translate('Select custom icon&hellip;'),

        ))->append(array(
            'options' => array(
                'custom_icon_path'  => 'icon://',
                'blank_icon_path'   => 'media://system/images/blank.png'
            ),
            'icons' => array(
                'archive', 'audio', 'default', 'document', 'folder',
                'image', 'pdf', 'spreadsheet', 'video'
            ),
            'id' => $config->name,
            'value' => $config->name
        ))->append(array(
            'callback' => 'select_'.$config->id,
            'options' => array(
                'id' => $config->id
            )
        ));

        if ($config->callback)
        {
            $config->options->callback = $config->callback;
            //This value is passed to the modal.icon helper
            $config->callback = 'Docman.Modal.request_map.'.$config->callback;
        }

        $image = $config->value;
        $font_icon = true;

        if (!$image) {
            $image = 'default';
        }

        if (substr($image, 0, 5) === 'icon:') {
            $image = 'icon://'.substr($image, 5);
            $font_icon = false;
        }

        $html = '<ktml:script src="media://com_docman/js/modal.js" />';

        $html .= '<div class="k-dropdown k-input-group__button">
                        <a class="k-button k-button--default k-dropdown__toggle" data-k-toggle="dropdown" href="javascript:void(0)">
                            <span id="'.$config->id.'-font-preview"
                                  class="k-icon-document-'.($font_icon ? $image : '').'"
                                  style="display:'.($font_icon ? 'inline-block' : 'none').'"
                            ></span>
                            <img
                                id="'.$config->id.'-preview"
                                data-src="'.$image.'"
                                '.($font_icon ? '' : 'src="'.$image.'"').'
                                onerror="this.src=\''.$config->options->blank_image_path.'\'"
                                style="display:'.($font_icon ? 'none' : 'inline-block').'"
                            />
                            <span class="k-caret"></span>
                        </a>
                        <ul class="k-dropdown__menu k-dropdown__menu--grid">';

        foreach($config->icons as $icon)
        {
            $html .= '<li><a class="k-js-document-icon-selector" href="#" title="'.$this->getObject('translator')->translate($icon).'" data-value="'.$icon.'">';
            $html .= '<span class="k-icon-document-'.$icon.' k-icon--size-default"></span>';
            $html .= '<span class="k-visually-hidden">'.$this->getObject('translator')->translate($icon).'</span>';
            $html .= '</a></li>';
        }

        $html .= '
                    <li class="k-dropdown__block-item">';

        $html .= $this->getTemplate()->helper('modal.icon', $config->toArray());
        $html .= '</li>
                        </ul>
                    </div>';

        /**
         * str_replace helps convert the paths before the template filter transform media:// to full path
         */
        $options = str_replace('\/', '/', $config->options->toString());

        $html .= $this->icon_map();

        /**
         * str_replace helps convert the paths before the template filter transform media:// to full path
         */
        $html .= "<script>kQuery(function($){new Docman.Modal.Icon(".$options.");});</script>";


        return $html;
    }

    public function icon_map($config = array())
    {
        $icon_map = json_encode(ComFilesTemplateHelperIcon::getIconExtensionMap());

        $html = "
            <script>
            if (typeof Docman === 'undefined') Docman = {};

            Docman.icon_map = $icon_map;
            </script>";

        return $html;
    }

    public function scanner($config = array())
    {
        $connect    = $this->getObject('com://admin/docman.model.entity.config')->connectAvailable();
        $extensions = \ComDocmanControllerBehaviorScannable::$ocr_extensions;

        $config = new KObjectConfigJson($config);
        $config->append([
            'options'  => array(
                'scannableExtensions' => $extensions,
                'isAdmin'             => $this->getObject('user')->authorise('core.manage', 'com_docman') === true,
                'isConnectEnabled'    => $connect,
            )
        ]);

        $html = $this->getTemplate()
                     ->loadFile('com://admin/docman.document.scanner.html')
                     ->render(array('config' => $config));

        return $html;
    }

    /**
     * Widget for selecting an thumbnail image
     *
     * @param array $config
     * @return string
     */
    public function thumbnail($config = array())
    {
        $thumbnail_controller = $this->getObject('com://admin/docman.controller.thumbnail');

        $path = $this->getObject('com:files.model.containers')->slug('docman-images')->fetch()->path;

        $connect = $this->getObject('com://admin/docman.model.entity.config')->connectAvailable();

        $config = new KObjectConfigJson($config);
        $config->append(array(
            'entity' => null
        ))->append([
            'entity_type' => KStringInflector::singularize($config->entity->getIdentifier()->name),
        ])->append([
            'options'  => array(
                'isAdmin'           => $this->getObject('user')->authorise('core.manage', 'com_docman') === true,
                'hasConnectSupport' => $connect,
                'connect_token'     => $connect ? PlgKoowaConnect::generateToken() : false,
                'csrf_token' => $this->getObject('user')->getSession()->getToken(),
                'automatic'  => [
                    'exists'     => is_file(JPATH_ROOT.'/'.$path.'/'.$thumbnail_controller->getDefaultFilename($config->entity)),
                    'path'       => $thumbnail_controller->getDefaultFilename($config->entity),
                    'enabled'    => ($config->entity_type === 'document' && $this->getObject('com://admin/docman.model.configs')->fetch()->thumbnails),
                    'extensions' => $thumbnail_controller->getSupportedExtensions(),
                ],
                'image_container'      => 'docman-images',
                'image_folder'         => 'root://'.$path.'/',
                'links' => [
                    'web'    => 'https://connect.joomlatools.com/image-picker/',
                    'custom' => (string)$this->getTemplate()->route('option=com_docman&view=files&layout=select&types[]=image', false, false),
                    'save_web_image' => (string)$this->getTemplate()->route('option=com_docman&view=file&format=json&routed=1', false, false),
                    'preview_automatic_image' => (string)$this->getTemplate()->route('option=com_docman&view=file&container=docman-files&routed=1', false, false)
                ]
            )
        ])->append([
            'options'  => [
                'editor' => [
                    'site' => (string)$this->getObject('request')->getSiteUrl()
                ]
            ]
        ]);

        $html = $this->getTemplate()
                     ->loadFile('com://admin/docman.document.thumbnail.html')
                     ->render(array('config' => $config));

        return $html;
    }


    public function calendar($config = array())
    {
        $config = new KObjectConfigJson($config);
        $config->append(array(
            'format' => '%Y-%m-%d'
        ));

        return parent::calendar($config);
    }

    /**
     * Loading js necessary to render a jqTree sidebar navigation of document categories
     *
     * @param array|KObjectConfig $config
     * @return string	The html output
     */
    public function category_tree($config = array())
    {
        $config = new KObjectConfigJson($config);
        $config->append(array(
            'debug'          => JFactory::getApplication()->getCfg('debug'),
            'element'        => '.k-js-category-tree',
            'selected'       => '',
            'selectable'     => true,
            'state'          => array(),
            'document_count' => false,
            'base_url'       => null,
            'options'        => array(
                'lang' => array(
                    'root' => $this->getObject('translator')->translate('All Categories')
                ),
                'base_url' => $config->base_url
            ),
        ))->append(array(
            'options' => array(
                'selected'   => $config->selected,
                'selectable' => $config->selectable
            )
        ));

        $map = function(&$data, $category, $config, $categories) {

            if ($config->document_count) {
                $categories->setDocumentCount();
            }

            $parts = explode('/', $category->path);
            array_pop($parts); // remove current id
            $data[] = array(
                'label'  => $category->title.(isset($category->document_count) ? ' ('.$category->document_count.')' : ''),
                'id'     => (int)$category->id,
                'slug'   => $category->slug,
                'path'   => $category->path,
                'parent' => (int)array_pop($parts)
            );
        };

        $data = $this->getObject('com://admin/docman.template.helper.listbox')->fetchCategories($config, $map);

        $config->options->append(array('data' => $data));

        // Load assets by calling parent tree behavior
        $html = parent::tree(array('debug' => $config->debug));

        if (!static::isLoaded('category_tree'))
        {
            $html .= '<ktml:script src="media://com_docman/js/admin/category.tree.js" />';
            $html .= '<script>
                kQuery(function($){
                new Docman.Tree.Categories('.json_encode($config->element).', '.$config->options.');
            });</script>';

            static::setLoaded('category_tree');
        }

        return $html;
    }

    public function category_tree_site($config = array())
    {
        $config = new KObjectConfigJson($config);
        $config->append(array(
            'debug'   => \Joomla\CMS\Factory::getConfig()->get('debug'),
            'element' => '.k-js-category-tree',
            'selected'  => '',
            'state'   => array(),
            'identifier' => 'com://site/docman.model.categories',
        ))->append(array(
            'options' => array(
                'selected' => $config->selected
            )
        ));

        $helper  = $this->getTemplate()->createHelper('com://admin/docman.template.helper.route');
        $page    = $config->state->page;

        $map = function(&$data, $category, $config) use ($helper, $page) {
            $parts = explode('/', $category->path);
            array_pop($parts); // remove current id
            $data[] = array(
                'label'  => $category->title,
                'id'     => (int)$category->id,
                'route'  => (string)$helper->category(array(
                    'entity' => $category,
                    'Itemid' => $page
                ), false, false),
                'slug'   => $category->slug,
                'path'   => $category->path,
                'parent' => (int)array_pop($parts)
            );
        };

        $data = $this->getObject('com://admin/docman.template.helper.listbox')->fetchCategories($config, $map, null, true);

        $config->options->append(array('data' => $data));

        // Load assets by calling parent tree behavior
        $html = parent::tree(array('debug' => $config->debug));

        if (!static::isLoaded('category_tree_site'))
        {
            $html .= '<ktml:script src="media://com_docman/js/site/category.tree.js" />';
            $html .= '<script>
                        kQuery(function($){
                            new Docman.Tree.CategoriesSite('.json_encode($config->element).', '.$config->options.');
                        });</script>';

            static::setLoaded('category_tree_site');
        }

        return $html;
    }

    /**
     * Loading js necessary for doclink to render a jqTree sidebar and the other UI features
     *
     * @param array|KObjectConfig $config
     * @return string	The html output
     */
    public function doclink($config = array())
    {
        $translator = $this->getObject('translator');

        $connect = $this->getObject('com://admin/docman.model.entity.config')->connectAvailable();

        $config = new KObjectConfigJson($config);
        $config->append(array(
            'debug'   => \Joomla\CMS\Factory::getConfig()->get('debug'),
            'list'    => array(), // com://admin/docman.model.pages list, preprocessed by com://admin/docman.view.doclink.html
            'options' => array(
                'hasConnectSupport' => $connect,
                'lang' => array(
                    'empty_folder_text' => $translator->translate('No documents found.'),
                    'insert_category' => $translator->translate('Insert category link'),
                    'insert_document' => $translator->translate('Insert document link'),
                    'insert_menu' => $translator->translate('Insert menu link'),
                    'embed_document' => $translator->translate('Embed document'),
                    'embed_not_supported' => $translator->translate('Embed not supported'),
                    'embed_requires_connect' => $translator->translate('Embed requires connect', ['link' => 'https://www.joomlatools.com/connect/']),
                )
            )
        ))->append(array(
            'options' => array(
                'editor' => $config->editor
            )
        ));

        $data = array();

        foreach($config->list as $page)
        {
            $target = ($page->params->get('document_title_link') === 'download' && $page->params->get('download_in_blank_page')) ? 'blank' : '';
            $tag    = '';

            if ($page->language && JLanguageMultilang::isEnabled())
            {
                $length = strlen($page->language);
                if ($length == 5 || $length == 6) {
                    $tag = substr($page->language, 0, $length-3);
                }
            }

            $entity = array(
                'label'    => $page->title.(!empty($page->language) ? ' ('.$page->language.')' : ''),
                'tag'      => $tag,
                'id'       => 'page'.$page->id,
                'itemid'   => (int)$page->id,
                'view'     => $page->query['view'],
                'target'   => $target,
                'children' => array()
            );

            if (in_array($page->query['view'], array('tree', 'list')))
            {
                $map = function(&$data, $category, $config) use ($page, $tag, $target) {
                    $parts = explode('/', $category->path);
                    array_pop($parts); // remove current id
                    $parent = (int)array_pop($parts);
                    $data[] = array(
                        'label'       => $category->title,
                        'tag'         => $tag,
                        'slug'        => $category->slug,
                        'itemid'      => $page->id,
                        'id'          => 'page'.$page->id.'category'.$category->id,
                        'category_id' => (int)$category->id,
                        'path'        => $category->path,
                        'parent'      => 'page'.$page->id.'category'.$parent,
                        'target'      => $target
                    );
                };

                $entity['children'] = $this->getObject('com://admin/docman.template.helper.listbox')->fetchCategories(array(
                    'state' => array(
                        'page' => $page->id,
                        'sort' => 'custom'
                    )
                ), $map);
                /*
                                $count  = $model->page($page->id)->count();
                                $offset = 0;

                                while ($offset < $count)
                                {
                                    $categories = $model->page($page->id)->limit(100)->offset($offset)->sort('title')->fetch();

                                    foreach($categories as $category)
                                    {
                                        $parts = explode('/', $category->path);
                                        array_pop($parts); // remove current id
                                        $parent = (int)array_pop($parts);
                                        $entity['children'][] = array(
                                            'label'       => $category->title,
                                            'tag'         => $tag,
                                            'slug'        => $category->slug,
                                            'itemid'      => $page->id,
                                            'id'          => 'page'.$page->id.'category'.$category->id,
                                            'category_id' => (int)$category->id,
                                            'path'        => $category->path,
                                            'parent'      => 'page'.$page->id.'category'.$parent,
                                            'target'      => $target
                                        );
                                    }

                                    $offset += 100;
                                }*/
            }

            $data[] = $entity;
        }
        $config->options->append(array('data' => $data));

        // Load assets by calling parent tree behavior
        $html = parent::tree(array('debug' => $config->debug));

        if (!static::isLoaded('doclink'))
        {
            $html .= '<ktml:script src="media://com_docman/js/admin/category.tree.js" />';
            $html .= '<ktml:script src="media://com_docman/js/doclink.js" />';
            $html .= '<ktml:script src="media://com_docman/js/footable.sort.js" />';

            $html .= '<script>
            kQuery(function($){
                new Docman.Doclink('.$config->options.');
            });</script>';

            static::setLoaded('doclink');
        }

        return $html;
    }

    /**
     * Attaches Bootstrap Affix to the sidebar along with custom code making it responsive
     *
     * @NOTE Also contains j!3.0 specific fixes
     *
     * @TODO requires bootstrap-affix!
     *
     * @param array|KObjectConfig $config
     * @return string	The html output
     */
    public function sidebar($config = array())
    {
        $config = new KObjectConfigJson($config);
        $config->append(array(
            'sidebar'   => '',
            'target'    => ''
        ));

        $html = '';
        // Load the necessary files if they haven't yet been loaded
        if (!static::isLoaded('sidebar'))
        {
            $html .= $this->jquery();
            //@TODO requires bootstrap-affix!
            //helper('bootstrap.load', array('javascript' => true))
            $html .= '<ktml:script src="media://com_docman/js/sidebar.js" />';

            static::setLoaded('sidebar');
        }

        $html .= '<script>kQuery(function($){new Docman.Sidebar('.$config.');});</script>';

        return $html;
    }

    /**
     * JS Behavior to button-groups
     *
     * @param array|KObjectConfig $config
     * @return string	The html output
     */
    public function buttongroup($config = array())
    {
        $config = new KObjectConfigJson($config);
        $config->append(array(
            'element'   => ''
        ));

        $html = '';
        // Load the necessary files if they haven't yet been loaded
        if (!static::isLoaded('buttongroup'))
        {
            $html .= $this->jquery();
            $html .= $this->koowa();
            $html .= '<ktml:script src="media://com_docman/js/buttongroup.js" />';

            static::setLoaded('buttongroup');
        }

        $html .= '<script>kQuery(function($){new Docman.Buttongroup('.$config.');});</script>';

        return $html;
    }

    /**
     * Override autocomplete method to be able to modify the fields
     */
    public function autocomplete($config = array())
    {
        $config = new KObjectConfigJson($config);
        $config->append(array(
            'element'  => null,
            'options_callback' => null, // wraps the call to select2 options in JavaScript, can be used to add JS code
            'options'  => array(
                'minimumInputLength' => 2,
                'validate'      => false, //Toggle if the forms validation helper is loaded
                'queryVarName'  => 'search',
                'width'         => 'resolve',
                'model'		    => $config->model,
                'placeholder'   => $config->prompt,
                'allowClear'    => $config->deselect,
                'value'         => $config->value,
                'text'          => $config->text,
                'selected'      => $config->selected,
                'url'           => $config->url,
                'multiple'      => false,
                'fields'        => $config->fields
            )
        ))->append(array(
            'options' => array(
                'label' => $config->text
            )
        ));

        $html ='';

        if (!$config->options->url instanceof KHttpUrl) {
            $config->options->url = $this->getObject('lib:http.url', array('url' => $config->options->url));
        }

        $fields = is_null($config->options->fields) ? $config->value.','.$config->text : $config->options->fields;
        $config->options->url->setQuery(array('fields' => $fields), true);

        $config->options->url = (string)$config->options->url;

        $options   = $config->options;
        $signature = md5('autocomplete-'.$config->element.$config->options_callback.$options);

        if($config->element && !static::isLoaded($signature))
        {
            $options = (string) $options;

            if ($config->options_callback) {
                $options = $config->options_callback.'('.$options.')';
            }

            $html .= $this->select2(array('element' => false));

            $html .= '<script>
            kQuery(function($){
                $("'.$config->element.'").select2(Koowa.getSelect2Options('.$options.'));
            });</script>';

            static::setLoaded($signature);
        }

        return $html;
    }

    /**
     * Uses Google Analytics to track search events in the frontend
     *
     * @param array $config
     * @return string
     */
    public function search_tracker($config = array())
    {
        $config = new KObjectConfigJson($config);
        $config->append(array(
            'selector'         => 'k-js-search-submit', // Selector for trigger element
            'keyword_selector' => null,                 // Optional selector for keyword element
            'category'         => 'DOCman',
            'action'           => 'Search'
        ));

        $html = $this->jquery();

        $signature = md5(serialize(array($config->selector, $config->category, $config->action)));
        if (!static::isLoaded($signature)) {
            $html .= "
            <script>
            kQuery(function($) {
                $('.{$config->selector}').on('click', function() {
                    var search, el = $(this), keyword_selector = '{$config->keyword_selector}';
                    
                    if (!keyword_selector) {
                        var filter = $(el[0].form).find('input[name=\"filter[search]\"]').val();
                        var search = filter == undefined ? $(el[0].form).find('input[name=search]').val() : filter;
                    } else {
                        search = $('{$config->keyword_selector}').val();
                    }
                    
                    if (typeof gtag !== 'undefined') {
                        gtag('event', '{$config->action}', {
                            'event_category': '{$config->category}',
                            'event_label': search,
                            'name': search
                        });
                    }
                    else if (typeof window.GoogleAnalyticsObject !== 'undefined' && typeof window[window.GoogleAnalyticsObject] !== 'undefined') {
                        window[window.GoogleAnalyticsObject]('send', 'event', '{$config->category}', '{$config->action}', search);
                    } 
                    else if (typeof _gaq !== 'undefined' && typeof _gat !== 'undefined') {
                        if (_gat._getTrackers().length) {
                            _gaq.push(function() {
                                var tracker = _gat._getTrackers()[0];
                                tracker._trackEvent('{$config->category}', '{$config->action}', search);
                            });
                        }
                    }
                });
            });
            </script>
            ";
            static::setLoaded($signature);
        }

        return $html;
    }

    /**
     * Override jquery asset in Config view
     * to explicitly load kquery in koowa decorator
     *
     * @param $config
     * @return string
     */
    public function jquery($config = array())
    {
        $query = $this->getTemplate()->url()->query;
        $html  = '';

        if (isset($query['view']) && $query['view'] == 'config' && class_exists('JHtml') && !static::isLoaded('jquery'))
        {
            JHtml::_('jquery.framework');

            // Can't use JHtml here as it makes a file_exists call on koowa.kquery.js?version
            $path = JUri::root(true).'/media/koowa/framework/js/koowa.kquery.js?'.substr(md5(Koowa::VERSION), 0, 8);
            JFactory::getDocument()->addScript($path);

            static::setLoaded('jquery');
        }
        else $html .= parent::jquery($config);

        return $html;
    }

    /**
     * A feature to resize split view
     *
     * @param mixed $config
     * @return string
     */
    public function split($config = [])
    {
        $config = new KObjectConfigJson($config);
        $config->append([
            'selectors' => ['#split-0', '#split-1']
        ]);

        $html = '';

        if (!static::isLoaded('docman-split'))
        {
            $html .= '<ktml:style src="media://com_docman/css/splitjs.css" />';
            $html .= '<ktml:script src="media://com_docman/js/splitjs/split.min.js" />';
            $html .= "<script>
                kQuery(document).ready(function($) {
                    Split({$config->selectors}, {
                        gutterAlign: 'start',
                        sizes: [25, 75],
                    });
                });
                </script>";

            static::setLoaded('docman-split');
        }

        return $html;
    }
}
