<?php
/**
 * @package         Articles Anywhere
 * @version         17.2.10PRO
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2025 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

namespace RegularLabs\Plugin\System\ArticlesAnywhere;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseDriver as JDatabaseDriver;
use Joomla\Database\DatabaseFactory as JDatabaseFactory;
use Joomla\Database\QueryInterface as JQueryInterface;
use RegularLabs\Library\Cache as RL_Cache;
use RegularLabs\Library\DB as RL_DB;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\DB;
use RegularLabs\Plugin\System\ArticlesAnywhere\Helpers\Params;

class Database
{
    public $name;
    public $settings;
    /**
     * @var JDatabaseDriver
     */
    private $db;

    public function __construct($name = '')
    {
        $this->name = $name;
        $this->settings = $this->getSettings();
        $this->db = $this->get();
    }

    /**
     * @return JDatabaseDriver|null
     */
    public function get()
    {
        if ( ! is_null($this->db))
        {
            return $this->db;
        }

        if (empty($this->settings) || empty($this->settings->user) || empty($this->settings->database))
        {
            $this->db = RL_DB::get();

            return $this->db;
        }

        $driver   = isset($this->settings->driver) ? preg_replace('#[^A-Z0-9_\.-]#i', '', $this->settings->driver) : 'mysqli';
        $this->db = (new JDatabaseFactory)->getDriver($driver, (array) $this->settings);

        return $this->db;
    }

    public function getResults(
        string|JQueryInterface $query,
        string                 $return_type = 'column',
        bool                   $allow_caching = true,
        mixed                  $attribute = null
    ): mixed
    {
        $query_cache_id = '';
        $params         = Params::get();

        if ($allow_caching)
        {
            $force_caching = (int) $params->use_query_cache === 2;

            $query_cache_id = [
                __METHOD__, $this->name, $return_type, self::getQueryCacheString($query),
            ];

            $cache = (new RL_Cache($query_cache_id));

            if ($params->use_query_cache)
            {
                $cache->useFiles(
                    DB::getQueryTime(),
                    $force_caching
                );
            }
        }

        if ($allow_caching && $cache->exists())
        {
            return $cache->get();
        }

        $method = 'load' . ucfirst($return_type);

        $use_query_log_cache = $allow_caching && $params->use_query_comments && $params->use_query_log_cache;

        if (JDEBUG || $params->use_query_comments)
        {
            $backtrace = DB::getQueryComment();
        }

        if ($use_query_log_cache)
        {
            $query_cache = ''
                . "\n\n" . 'QUERY:' . "\n==========\n" . trim((string) $query)
                . "\n\n" . 'METHOD: ' . "\n==========\n" . $method
                . "\n\n" . 'BACKTRACE:' . "\n==========\n" . str_replace(' => ', "\n", $backtrace)
                . "\n\n";
        }

        if (JDEBUG || $params->use_query_comments)
        {
            $query->select(
                $this->db->quote($backtrace) . ' as ' . $this->db->quote('query_comment')
            );
        }


        $result = $attribute
            ? $this->db->setQuery($query)->$method($attribute)
            : $this->db->setQuery($query)->$method();

        if ( ! $allow_caching)
        {
            return $result;
        }

        if ($use_query_log_cache)
        {
            (new RL_Cache($query_cache_id, 'regularlabs_query'))
                ->useFiles(
                    DB::getQueryTime() * 60,
                    true
                )
                ->set($query_cache);
        }

        return $cache->set($result);
    }

    public function getSetting($name)
    {
        $settings = $this->getSettings();

        return $settings->$name ?? '';
    }

    public function getSettings()
    {
        if ( ! is_null($this->settings))
        {
            return $this->settings;
        }

        $settings = Params::getDatabase($this->name ?? '');

        $this->settings = (object) [
            'driver'     => $settings->driver ?? $settings->dbtype ?? '',
            'host'       => $settings->host ?? '',
            'user'       => $settings->user ?? '',
            'password'   => $settings->password ?? '',
            'database'   => $settings->database ?? $settings->db ?? '',
            'prefix'     => $settings->prefix ?? $settings->dbprefix ?? '',
            'url_domain' => $settings->url_domain ?? '',
        ];

        return $this->settings;
    }

    private static function getQueryCacheString($query)
    {
        $nowDate = DB::getNowDate();
        $query   = (string) $query;

        $query = str_replace($nowDate, '??', $query);

        return $query;
    }
}
