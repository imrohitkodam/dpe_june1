<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Activity Translator Catalogue.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanActivityTranslatorCatalogue extends ComKoowaTranslatorCatalogueAbstract implements ComActivitiesActivityTranslatorCatalogueInterface
{
    /**
     * The language object
     *
     * @var JLanguage
     */
    protected $_language;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->setLanguage($config->language);
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array('language' => JLanguage::getInstance(JFactory::getConfig()->get('language'))));
        parent::_initialize($config);
    }

    /**
     * Language object setter
     *
     * @param JLanguage $language The language object
     * @return ComLogmanActivityTranslatorCatalogue
     */
    public function setLanguage(JLanguage $language)
    {
        $this->_language = $language;
        return $this;
    }

    /**
     * Language object getter
     *
     * @param JLanguage The language object
     */
    public function getLanguage()
    {
        return $this->_language;
    }

    /**
     * Get a string from the catalogue
     *
     * @param string $string
     * @return string
     */
    public function get($string)
    {
        $lowercase = strtolower($string);

        if (!KTranslatorCatalogueAbstract::has($lowercase))
        {
            if (isset($this->_aliases[$lowercase])) {
                $key = $this->_aliases[$lowercase];
            }
            else if(!$this->getLanguage()->hasKey($string))
            {
                if (substr($string, 0, strlen($this->getPrefix())) === $this->getPrefix()) {
                    $key = $string;
                } else {
                    //Gets a key from the catalogue and prefixes it
                    $key = $this->getPrefix().$this->generateKey($string);
                }
            }
            else $key = $string;

            $this->set($lowercase, $this->getLanguage()->_($key));
        }

        return KTranslatorCatalogueAbstract::get($lowercase);
    }

    /**
     * Check if a string exists in the catalogue
     *
     * @param  string $string
     * @return boolean
     */
    public function has($string)
    {
        $lowercase = strtolower($string);

        if (!KTranslatorCatalogueAbstract::has($lowercase) && !$this->getLanguage()->hasKey($string))
        {
            if (isset($this->_aliases[$lowercase])) {
                $key = $this->_aliases[$lowercase];
            }
            elseif (substr($string, 0, strlen($this->getPrefix())) === $this->getPrefix()) {
                $key = $string;
            } else {
                //Gets a key from the catalogue and prefixes it
                $key = $this->getPrefix().$this->generateKey($string);
            }

            $result = $this->getLanguage()->hasKey($key);
        }
        else $result = true;

        return $result;
    }
}