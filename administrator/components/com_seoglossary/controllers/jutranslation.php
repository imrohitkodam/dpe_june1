<?php
/**    
 * SEO Glossary Seoglossary com_seoglossary
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */
// No direct access

defined( '_JEXEC' ) or die;

jimport( 'joomla.filesystem.file' );
JLoader::register('JuupdaterHelper', JPATH_SITE . '/plugins/installer/juupdater/helper.php');
/**
 * Class DropfilesControllerJutranslation
 */
class SeoglossaryControllerJutranslation extends JControllerLegacy {

    private $extension = 'com_seoglossary';
    private $extension_slug = 'seoglossary';
    private $extension_name = 'Seoglossary';

    /**
     * Save a post translation for a given language
     */
    public function saveStrings(){
        //Include JUTranslation com_seoglossary
        include_once(JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_seoglossary' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'jutranslation.php');

        //Check if user has permissions
        $canDo = SeoglossaryBackendHelper::getActions();
        if(!$canDo->get('core.admin')){
            echo json_encode(array('status'=>'error','message'=>'unhautorized'));
            die();
        }

        //Security check
        JSession::checkToken() or die( 'Invalid Token' );

        //Get and check language
        $language = JFactory::getApplication()->input->getCmd('language');
        if(!$language){
            echo json_encode(array('status'=>'error','message'=>'language empty'));
            die();
        }
        if(!preg_match('/^[a-z]{2,3}(?:-[a-zA-Z]{4})?(?:-[A-Z]{2,3})?$/',$language)){
            echo json_encode(array('status'=>'error','message'=>'invalid language code'));
            die();
        }

        //Get the file to write to
        $destination = JFactory::getApplication()->input->getCmd('destination');
        $file = $file = JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . $this->extension . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . $language . '.' . $this->extension;
        switch ($destination){
            case 'override':
                $file .= '.override.ini';
                break;
            case 'edition':
                //Disallow editing main en-GB file
                if($language === 'en-GB'){
                    echo json_encode(array('status'=>'error','message'=>'editing main reference file not allowed'));
                    die();
                }

                $file .= '.ini';

                //Get informations about previous installed file
                if(file_exists($file)){
                    $content = file_get_contents($file);
                    $informations   = Jutranslation::extractInformations($content);
                }else{
                    $informations = array();
                }

                //Get the file version
                if(isset($informations['version']) && $informations['version']){
                    $extension_version = $informations['version'];
                }else{
                    //Use the current extension version
                    $extension_version = Jutranslation::getComponentVersion();
                }

                //Get the file revision
                if(isset($informations['revision'])){
                    $revision = $informations['revision'];
                }else{
                    //Use the current extension version
                    $revision = JFactory::getApplication()->input->getInt('revision',1);
                }
                break;
            default: //Case new language version installation from Joomunited
                //Get the version
                $extension_version = JFactory::getApplication()->input->getCmd('extension_version');
                if(!$extension_version){
                    echo json_encode(array('status'=>'error','message'=>'version empty'));
                    die();
                }

                //Get revision
                $revision = JFactory::getApplication()->input->getInt('revision',1);

                $file .= '.ini';
                break;
        }

        //Check revision number
        if($destination!=='override' && !preg_match('/^([0-9]+)\.([0-9]+)(\.([0-9]+))?(\.([0-9]+))?$/',$extension_version)){
            echo json_encode(array('status'=>'error','message'=>'invalid version number'));
            die();
        }

        $modified = JFactory::getApplication()->input->getBool('modified',0);

        //Get strings
        $strings = JFactory::getApplication()->input->get('strings',false,'raw');

        //Check if strings is a valid array
        $strings = json_decode($strings);
        if($strings === false || !is_object($strings) || !count((array)$strings)){
            $strings = new \stdClass();
        }

        //Generate the file header
        if($destination === 'override'){
            $content = ';' . $this->extension_name . ' language override file' . PHP_EOL;
        }else{
            $content = ';' . $this->extension_name . ' language file' . PHP_EOL;
            $content .= ';Please do not edit directly this file, use the dedicated tool in the component options page' . PHP_EOL;
            $content .= ';version="' . $extension_version . '"' . PHP_EOL;
            $content .= ';revision="' . $revision . '"' . PHP_EOL;
            $content .= ';modified="'.(int)$modified.'"' . PHP_EOL;
        }

        foreach ($strings as $code => $string) {
            //Only save reference language empty strings
            if($string !== '' || $language === 'en-GB') {
                $content .= $code . '="' . str_replace('"','"QQ"',$string) .'"' . PHP_EOL;
            }
        }

        //Write the language file
        if(JFile::write($file, $content)){
            echo json_encode(array('status'=>'success','message'=>'file created'));
            die();
        }

        echo json_encode(array('status'=>'error','message'=>'writing file failed'));
        die();
    }

    /**
     * Return the existing strings for a given language
     * containing reference string in en-GB and
     * overrides strings
     */
    public function getTranslation(){
        //Check if user has permissions
        $canDo = SeoglossaryBackendHelper::getActions();
        if(!$canDo->get('core.admin')){
            echo json_encode(array('status'=>'error','message'=>'unhautorized'));
            die();
        }

        //Get and check language
        $language = JFactory::getApplication()->input->getCmd('language');
        if(!$language){
            echo json_encode(array('status'=>'error','message'=>'language empty'));
            die();
        }
        if(!preg_match('/^[a-z]{2,3}(?:-[a-zA-Z]{4})?(?:-[A-Z]{2,3})?$/',$language)){
            echo json_encode(array('status'=>'error','message'=>'invalid language code'));
            die();
        }

        //Include JUTranslation com_seoglossary
        include_once(JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_seoglossary' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'jutranslation.php');

        //Get the language file for reference language en-GB
        $reference_content = file_get_contents(JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . $this->extension . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR . 'en-GB' . DIRECTORY_SEPARATOR . 'en-GB.' . $this->extension . '.ini');
        if($reference_content == false){
            echo json_encode(array('status'=>'error','message'=>'language file en-GB not found'));
            die();
        }
        //Retrieve reference the strings
        $reference_strings = Jutranslation::extractLanguageStrings($reference_content);

        //Get the default language file for this language
        $base_file = JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . $this->extension . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . $language . '.' . $this->extension . '.ini';
        if(file_exists($base_file)){
            $base_content = file_get_contents($base_file);
            //Retrieve default the strings
            $language_strings = Jutranslation::extractLanguageStrings($base_content);
        }else{
            $language_strings = array();
        }


        //Get the override file content if exists
        $override_file = JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . $this->extension . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . $language . '.' . $this->extension . '.override.ini';
        if(file_exists($override_file)){
            $override_content = file_get_contents($override_file);
            //Retrieve the strings
            $override_strings = Jutranslation::extractLanguageStrings($override_content);
        }else{
            $override_strings = array();
        }

        //Generate the final variable cotaining all strings
        $final_result = array();
        foreach (array('reference_strings' => $reference_strings, 'language_strings' => $language_strings, 'override_strings' => $override_strings) as $variable => $strings) {
            foreach ($strings as $constant => $value) {
                if(empty($final_result[$constant])) {
                    $obj = new stdClass();
                    $obj->key = $constant;
                    $obj->reference = '';
                    $obj->language = '';
                    $obj->override = '';
                    $final_result[$constant] = $obj;
                }
                switch ($variable) {
                    case 'reference_strings' :
                        $final_result[$constant]->reference = $value;
                        break;
                    case 'language_strings' :
                        $final_result[$constant]->language = $value;
                        break;
                    case 'override_strings' :
                        $final_result[$constant]->override = $value;
                        break;
                }
            }
        }

        echo json_encode(array('status'=>'success','datas'=> array('language'=> $language, 'strings' => $final_result)));
        die();
    }

    /**
     * Show submit form to share translation
     * @throws Exception
     */
    public function showViewForm(){
        //Check if user has permissions
        $canDo = SeoglossaryBackendHelper::getActions();
        if(!$canDo->get('core.admin')){
            echo json_encode(array('status'=>'error','message'=>'unhautorized'));
            die();
        }
        JHtml::_('jquery.framework');
        //No extra decoration
        JFactory::getApplication()->input->set('tmpl','component');

        //Get and check language
        $language = JFactory::getApplication()->input->getCmd('language');
        if(!$language){
            throw new Exception('language empty');
        }
        if(!preg_match('/^[a-z]{2,3}(?:-[a-zA-Z]{4})?(?:-[A-Z]{2,3})?$/',$language)){
            throw new Exception('invalid language code');
        }

        $file = JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . $this->extension . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . $language . '.' . $this->extension . '.ini';
        if(!file_exists($file)){
            throw new Exception('language file doesn\'t exist');
        }

        //Include JUTranslation com_seoglossary
        include_once(JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_seoglossary' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'jutranslation.php');

        $file_content = file_get_contents($file);

        //Get informations
        $informations = Jutranslation::extractInformations($file_content);

        //Check if the file has been modified by the user
        if(isset($informations['modified']) && $informations['modified']!=='1'){
            throw new Exception('language file not modified');
        }

        $strings = Jutranslation::extractLanguageStrings($file_content);
        if(!count($strings)){
            throw new Exception('no string found');
        }
        $strings = json_encode($strings);

        $version = Jutranslation::getComponentVersion();
        
         $user_token = Jutranslation::juGetToken();

        //Remove the scripts automatically add by component and keep only jquery
        JFactory::getDocument()->_scripts = array();
        JHtml::_('script', 'jui/jquery.min.js', false, true, false, false);

        //Content of the form to post to Joomunited
        $html = '';
        //Submit automatically the form on page loading
        $html .= '<script type="text/javascript">jQuery(document).ready(function($){$("form").submit();});</script>';
        //todo: replace by joomunited address
        $html .= '<form method="POST" action="https://www.joomunited.com/index.php?option=com_jutranslation&task=contribution.share">';
        $html .= '<input type="hidden" name="extension" value="' . $this->extension_slug . '" />';
        $html .= '<input type="hidden" name="extension_language" value="' . $language . '" />';
        $html .= '<input type="hidden" name="extension_version" value="' . $version . '" />';
        $html .= '<input type="hidden" name="user_token" value="' . $user_token . '" />';
        $html .= '<input type="hidden" name="site_url" value="' . JUri::base() . '" />';
        $html .= '<input type="hidden" name="extension_updated" value="1" />';
        $html .= '<textarea style="display: none" name="strings">' . $strings . '</textarea>';
        $html .= '</form>';
        //Add waiting image
        $html .= '<div style="text-align:center"><img src="' . JUri::root() . '/administrator/components/'. $this->extension.'/assets/images/preview_loader.gif"</div>';

        echo $html;
    }
      /**
     * Save Ju Token
     *
     * @return void
     * @throws \Exception Throw when application can not start
     * @since  version
     */
    public function saveJuToken()
    {
        //Check if user has permissions
        $canDo = SeoglossaryBackendHelper::getActions();
        if (!$canDo->get('core.admin')) {
            echo json_encode(array('status' => 'error', 'message' => 'unhautorized'));
            die();
        }

        //Security check
        JSession::checkToken() || die('Invalid Token');

        //Add jutoken into client site
        JuupdaterHelper::juAddToken();
    }
}
