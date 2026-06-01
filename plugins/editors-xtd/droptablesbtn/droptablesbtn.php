<?php
/**
 * Droptables
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and to customize.
 * Otherwise, please feel free to contact us at contact@joomunited.com *
 *
 * @package   Droptables
 * @copyright Copyright (C) 2014 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @copyright Copyright (C) 2014 Damien Barrère (http://www.crac-design.com). All rights reserved.
 * @license   GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

// no direct access
defined('_JEXEC') || die;

jimport('joomla.plugin.plugin');
/**
 * Class plgButtonDroptablesbtn
 */
class plgButtonDroptablesbtn extends JPlugin  //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps -- Class name not change
{
    /**
     * Check value
     *
     * @var boolean
     */
    protected $do = true;

    /**
     * Constructor
     *
     * @param object $subject Object to observe
     * @param array  $config  An array that holds the plugin configuration
     *
     * @since 1.5
     *
     * @return void
     */
    public function __construct(&$subject, $config)
    {
//        var_dump($subject);
//        die();
        parent::__construct($subject, $config);

        JLoader::register('DroptablesBase', JPATH_ADMINISTRATOR . '/components/com_droptables/classes/droptablesBase.php');
        if (!class_exists('DroptablesBase')) {
            $this->do = false;
        }
        $lang = JFactory::getLanguage();
        $lang->load('plg_editors-xtd_droptablesbtn', JPATH_PLUGINS . '/editors-xtd/droptablesbtn', null, true);
        $lang->load('plg_editors-xtd_droptablesbtn.sys', JPATH_PLUGINS . '/editors-xtd/droptablesbtn', null, true);

        // Access check.
        if (!JFactory::getUser()->authorise('core.manage', 'com_droptables')) {
            $this->do = false;
        }
    }


    /**
     * Display the button
     *
     * @param string $name Name
     *
     * @return array A four element array of (code)
     */
    public function onDisplay($name)
    {
        if (!$this->do) {
            return '';
        }
        /*
         * Javascript to insert the link
         * View element calls jSelectArticle when an article is clicked
         * jSelectArticle creates the link tag, sends it to the editor,
         * and closes the select frame.
         */
        $js = "
		function jInsertTable(html) {
			jInsertEditorText(html, '" . $name . "');
			console.log(123);
			SqueezeBox.close();
		}";

        $doc = JFactory::getDocument();
        $doc->addScriptDeclaration($js);

        $doc->addStyleDeclaration('.modal-title {font-size: 16px; line-height: 30px;} .btn-close.novalidate{background-size: 12px;} .page-title .icon-droptables{
            position:relative;
            top: -1px;
            width: 40px;
            height: 40px;
            vertical-align: middle;
            } 
            .icon-droptables:before {
            content: "\e600";
            }
            .page-title .icon-droptables:before {
		    content: "";
            position: absolute;
            background: transparent;
            background-image: url(' . JUri::root(true) . '/components/com_droptables/assets/images/listTables/sidebar-icon.svg);
            background-repeat: no-repeat;
            background-size: 40px 40px;
            width: 40px;
            height: 40px;
            color: #161616;
            top: 0;
            right: 0;
            font-size: 12px;
		}');
        // set modal height in J4
        $css = '#'.$name.'_droptables_modal .modal-body { padding: 0; height: 90vh} #'.$name.'_droptables_modal .modal-body iframe { height: 100%;} ';
        $doc->addStyleDeclaration($css);

        $doc->addStyleSheet(JUri::root(true) . '/components/com_droptables/assets/css/font.css');

//        JHtml::_('behavior.modal');

        /*
         * Use the built-in element view to select the article.
         * Currently uses blank class.
         */
        $path = urlencode(JURI::root(true));

        $link = 'index.php?option=com_droptables&amp;view=droptables&amp;tmpl=component&amp;' . JSession::getFormToken() . '=1&caninsert=1&e_name=' . $name . '&template=system&path=' . $path;


        $button = new JObject();
        $button->set('modal', true);
        $button->set('link', $link);
        $button->set('class', 'btn');
        $button->set('text', JText::_('PLG_DROPTABLES_BUTTON'));
        $button->set('name', 'droptables');
        $button->set('icon', 'table');
        if (droptablesBase::isJoomla40()) {
            $btnOptions = array(
                'width'       => '800px',
                'bodyHeight'  => '90',
                'modalWidth'  => '90',
            );
        } else {
            $btnOptions = "{handler: 'iframe', size: {x: (window.getSize().x*90/100), y: (window.getSize().y-200)}}";
        }
        $button->set('options', $btnOptions);

        return $button;
    }
}
