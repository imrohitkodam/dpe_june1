<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
use Joomla\CMS\Language\Language;
/**
 * LOGman Activity Translator
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanActivityTranslator extends ComActivitiesActivityTranslator
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'fallback_catalogue' => 'com://admin/logman.activity.translator.catalogue',
        ));

        parent::_initialize($config);
    }

    public function onDecorate($delegate)
    {
        KObjectDecorator::onDecorate($delegate);

        $urls = $delegate->getLoaded();

        // Load previosly loaded files on fallback catalogue.
        if ($urls)
        {
            if (!$this->getCatalogue() instanceof ComActivitiesActivityTranslatorCatalogueInterface) {
                $this->_switchCatalogues();
            }

            $catalogue = $this->getCatalogue();
            $language  = $catalogue->getLanguage();
            $locale    = $this->getLocaleFallback();

            foreach ($urls as $url)
            {
                foreach ($this->find($url) as $extension => $base)
                {
                    if (!$language->load($extension, $base, $locale, true, false))
                    {
                        $file = glob(sprintf('%s/language/%s.*', $base, $locale));

                        if ($file) {
                            ComLogmanTranslatorLanguage::loadFile(current($file), $extension, $this, $language);
                        }
                    }

                }
            }

            $this->_switchCatalogues();
        }
    }

    protected function _getFallbackCatalogue()
    {
        if (!$this->_fallback_catalogue instanceof KTranslatorCatalogueInterface)
        {
            $config = array('language' => Language::getInstance($this->getLocaleFallback()));

            $catalogue = $this->getObject($this->_fallback_catalogue, $config);

            $this->_setFallbackCatalogue($catalogue);
        }

        return $this->_fallback_catalogue;
    }

    /**
     * Loads translations from a url
     *
     * @param string $url      The translation url
     * @param bool   $override If TRUE override previously loaded translations. Default FALSE.
     * @return bool TRUE if translations are loaded, FALSE otherwise
     */
    public function load($url, $override = false)
    {
        $loaded = array();

        if (!$this->isLoaded($url))
        {
            for ($i = 0; $i < 2; $i++)
            {
                $locales = array($this->getLocaleFallback());

                $catalogue = $this->getCatalogue();

                if (!$catalogue instanceof ComActivitiesActivityTranslatorCatalogueInterface)
                {
                    $language = JFactory::getLanguage();
                    $locales[] = $this->getLocale();
                }
                else $language = $catalogue->getLanguage();

                foreach($this->find($url) as $extension => $base)
                {
                    foreach ($locales as $locale)
                    {
                        if (!$language->load($extension, $base, $locale, true, false))
                        {
                            $file = glob(sprintf('%s/language/%s.*', $base, $locale));

                            if ($file) {
                                $loaded[] = ComLogmanTranslatorLanguage::loadFile(current($file), $extension, $this, $language);
                            }
                        }
                        else $loaded[] = true;
                    }
                }

                // Switch catalogue for loading translations on fallback locale.
                $this->_switchCatalogues();
            }

            $this->setLoaded($url);
        }

        return in_array(true, $loaded);
    }

    public function find($url)
    {
        // Handle URLs containing translation files
        if (is_string($url) && strpos($url, '/') === 0 && file_exists($url) && !is_dir($url))
        {
            $file = $url;

            // Calculate extension
            $filename = basename($file);
            $parts    = explode('.', $filename);
            array_pop($parts); // Remove ini extension
            array_shift($parts); // Remove locale
            $extension = implode('.', $parts);

            // Calculate base path
            $path  = dirname($file);
            $parts = explode('/', $path);
            array_splice($parts, -2);
            $base_path = implode('/', $parts);

            $result = array($extension => $base_path);
        }
        else $result = parent::find($url);

        return $result;
    }

    /**
     * Loads application language files.
     *
     * @param string      $extension The extension name.
     * @param string|null $client    The client (admin or site).
     * @return bool Returns true if a system language file was found and loaded, false otherwise.
     */
    public static function loadSysIni($extension, $client = null)
    {
        $result = false;

        $parts = explode('_', $extension);

        $type  = $parts[0];

        if (!isset($client)) $client = 'admin';

        $base_path = $client == 'admin' ? JPATH_ADMINISTRATOR : JPATH_SITE;

        $translator = KObjectManager::getInstance()->getObject('translator');

        $locales = array($translator->getLocale(), $translator->getLocaleFallback());

        switch ($type)
        {
            case 'com':
                $paths = array(
                    $base_path,
                    sprintf('%s/components/%s', $base_path, $extension)
                );
                break;
            case 'mod':
                $paths = array(
                    $base_path,
                    sprintf('%s/modules/%s', $base_path, $extension)
                );
                break;
            case 'plg':
                $paths = array(JPATH_ADMINISTRATOR);

                if (count($parts) === 3) {
                    $paths[] = sprintf('%s/%s/%s', JPATH_PLUGINS, $parts[1], $parts[2]);
                }
                break;
            default:
                $paths = array();
        }

        foreach ($locales as $locale)
        {
            $types = array('sys.ini', 'ini');

            foreach ($types as $type)
            {
                if (version_compare(JVERSION, '4', '<')) {
                    $filename = sprintf('%s.%s.%s', $locale, $extension, $type);
                } else {
                    if ($type === 'sys.ini') {
                        $type = 'ini';
                    }

                    $filename = sprintf('%s.%s', $extension, $type);
                }


                foreach ($paths as $path)
                {
                    $file = $path . '/language/' . $locale . '/' . $filename;

                    if (file_exists($file))
                    {
                        if (!$translator instanceof ComActivitiesActivityTranslatorInterface)
                        {
                            $parts = explode('.', sprintf('%s.%s', $extension, $type));

                            array_pop($parts);

                            $translations = implode('.', $parts);

                            $result = JFactory::getLanguage()->load($translations, $path, $locale, false, false);

                            if ($result) {
                                $translator->setLoaded($file);
                            }
                        }
                        else $result = $translator->load($file);
                    }

                    if ($result) break;
                }
            }

            if ($result) break;
        }

        return $result;
    }
}

/**
 * Translator Language Class
 *
 * Extends Language for accessing protected properties.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Koowa\Component\Koowa\Translator
 */
class ComLogmanTranslatorLanguage extends Language
{
    /**
     * Associative array containing the list of loaded translations.
     *
     * @var array
     */
    static protected $_paths;

    /**
     * Adds file translations to the Language catalogue.
     *
     * @param string               $file       The file containing translations.
     * @param string               $extension  The name of the extension containing the file.
     * @param KTranslatorInterface $translator The Translator object.
     * @param Language            $language   The languge object.
     *
     * @return bool True if translations where loaded, false otherwise.
     */
    static public function loadFile($file, $extension, KTranslatorInterface $translator, Language $language)
    {
        $result = false;

        if (!isset(self::$_paths[$extension][$file]))
        {
            $strings = self::parseFile($file, $translator);

            if (count($strings))
            {
                ksort($strings, SORT_STRING);

                $language->strings = array_merge($language->strings, $strings);

                if (!empty($language->override)) {
                    $language->strings = array_merge($language->strings, $language->override);
                }

                $result = true;
            }

            // Record the result of loading the extension's file.
            if (!isset($language->paths[$extension])) {
                $language->paths[$extension] = array();
            }

            self::$_paths[$extension][$file] = $result;
        }

        return $result;
    }

    /**
     * Parses a translations file and returns an array of key/values entries.
     *
     * @param string               $file       The file to parse.
     * @param KTranslatorInterface $translator The translator object.
     * @return array The parse result.
     */
    static public function parseFile($file, KTranslatorInterface $translator)
    {
        $strings   = array();
        $catalogue = $translator->getCatalogue();

        // Catch exceptions if any.
        try {
            $translations = $translator->getObject('object.config.factory')->fromFile($file);
        }  catch (Exception $e) {
            $translations = array();
        }

        foreach ($translations as $key => $value) {
            $strings[$catalogue->getPrefix() . $catalogue->generateKey($key)] = $value;
        }

        return $strings;
    }
}