<?php
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Response\JsonResponse;

class PlgSystemRobotsmanager extends CMSPlugin
{
    protected $app;

    /**
     * Auto apply robots when article is saved
     *
     * @param   string  $context  The context of the content being saved
     * @param   object  $article  The article object
     * @param   bool    $isNew    True if this is a new article
     * @param   array   $data     The data being saved
     *
     * @return  bool
     *
     * @since   1.0.0
     */
    public function onContentAfterSave($context, $article, $isNew, $data = [])
    {
        if ($context === 'com_content.article')
        {
            $this->applyRobotsToArticle($article->id, $article->access);
        }

        if ($context === 'com_menus.item')
        {
            $this->applyRobotsToMenu($article->id, $article->access);
        }

        return true;
    }

    /**
     * Validate plugin params before saving to prevent duplicate access levels
     *
     * @param   string  $context  The context of the extension being saved
     * @param   object  $table    The table object
     * @param   bool    $isNew    True if this is a new extension
     * @param   array   $data     The data being saved
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onExtensionBeforeSave($context, $table, $isNew, $data)
    {
        // Only validate when saving THIS plugin
        if ($context === 'com_plugins.plugin' && $data['element'] === 'robotsmanager')
        {
            // Validate article rules
            if (!empty($data['params']['rules']))
            {
                $seen = [];

                foreach ($data['params']['rules'] as $rule)
                {
                    // print_r($rule['rules']);
                    if (empty($rule['rules']['access'])) {
                        continue;
                    }
                    if (in_array($rule['rules']['access'], $seen)) {
                        throw new \RuntimeException(
                            'Duplicate Access Level "' . $rule['rules']['access'] . '" found in Article Rules. Each access level must be unique.'
                        );
                    }

                    $seen[] = $rule['rules']['access'];
                }
            }

            // Validate menu rules
            if (!empty($data['params']['menu_rules']))
            {
                $seen = [];

                foreach ($data['params']['menu_rules'] as $rule)
                {
                    if (empty($rule['rules']['access'])) {
                        continue;
                    }
                    if (in_array($rule['rules']['access'], $seen)) {
                        throw new \RuntimeException(
                            'Duplicate Access Level "' . $rule['rules']['access'] . '" found in Menu Rules. Each access level must be unique.'
                        );
                    }

                    $seen[] = $rule['rules']['access'];
                }
            }
        }
    }

    /**
     * Call a function after this plugin is saved in Extensions → Plugins
     *
     * @param   string  $context  The context of the extension being saved
     * @param   object  $table    The table object
     * @param   bool    $isNew    True if this is a new extension
     * @param   array   $data     The data being saved
     *
     * @return  bool
     *
     * @since   1.0.0
     */
    public function onExtensionAfterSave($context, $table, $isNew, $data = [])
    {
        if ($context !== 'com_plugins.plugin')
        {
            return true;
        }

        // Ensure we only react to this specific plugin
        $element = isset($table->element) ? $table->element : (isset($data['element']) ? $data['element'] : null);
        if ($element !== 'robotsmanager')
        {
            return true;
        }

        try
        {
            $this->onPluginSaved($data);
        }
        catch (\Throwable $e)
        {
            Factory::getApplication()->enqueueMessage('Robots Manager post-save action failed: ' . $e->getMessage(), 'warning');
        }

        return true;
    }

    /**
     * Custom logic to run after plugin save
     * You can toggle behavior via params (e.g., apply_on_save, batch_size)
     *
     * @param   array  $data  The plugin data being saved
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function onPluginSaved($data): void
    {
        $app = Factory::getApplication();

        // Get the NEW parameters from the saved data, not the old ones
        $newParams = isset($data['params']) ? $data['params'] : [];
        
        // If admin chose to update articles now, process ALL articles in batches
        $updateNow = isset($newParams['update_now']) ? (int) $newParams['update_now'] : 0;
        if ($updateNow === 1)
        {
            $batchSize = isset($newParams['batch_size']) ? (int) $newParams['batch_size'] : 50;
            
            // Temporarily update the plugin params so applyRobotsToArticle can use the new rules
            $this->params->set('rules', $newParams['rules'] ?? []);
            
            $result = $this->runBatchUpdateAll($batchSize);
            if ($result['updated'] > 0) {
            $app->enqueueMessage(
                sprintf('Robots Manager: Completed! Processed %d articles, updated %d articles.', 
                    $result['processed'], 
                    $result['updated']
                ), 
                'message'
            );
        }
        }

        // If admin chose to update menus now, process ALL menus in batches
        $updateMenusNow = isset($newParams['update_menus_now']) ? (int) $newParams['update_menus_now'] : 0;
        if ($updateMenusNow === 1)
        {
            $batchSize = isset($newParams['batch_size']) ? (int) $newParams['batch_size'] : 50;
            
            // Temporarily update the plugin params so applyRobotsToMenu can use the new menu rules
            $this->params->set('menu_rules', $newParams['menu_rules'] ?? []);
            
            $result = $this->runBatchUpdateMenus($batchSize);
            if ($result['updated'] > 0) {
            $app->enqueueMessage(
                sprintf('Robots Manager: Completed! Processed %d menus, updated %d menus.', 
                    $result['processed'], 
                    $result['updated']
                ), 
                'message'
            );
        }
        }

        // Legacy/optional behavior: run full batch update on save if enabled
        $applyOnSave = isset($newParams['apply_on_save']) ? (int) $newParams['apply_on_save'] : 0;
        if ($applyOnSave === 1)
        {
            $batchSize = isset($newParams['batch_size']) ? (int) $newParams['batch_size'] : 50;
            
            // Temporarily update the plugin params so applyRobotsToArticle can use the new rules
            $this->params->set('rules', $newParams['rules'] ?? []);
            
            $result = $this->runBatchUpdate($batchSize);
            
            $app->enqueueMessage(
                sprintf('Robots Manager: Processed %d articles, updated %d', 
                    $result['processed'], 
                    $result['updated']
                ), 
                'message'
            );
        }

        // Always provide a confirmation message
        $app->enqueueMessage('Robots Manager settings saved.', 'message');
    }

    /**
     * Process ALL articles in batches - no AJAX, direct processing
     *
     * @param   int  $batchSize  The number of articles to process per batch
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public function runBatchUpdateAll($batchSize = 50)
    {
        $db = Factory::getDbo();
        $totalProcessed = 0;
        $totalUpdated = 0;
        $start = 0;

        // Get total count first
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from('#__content')
            ->where('state = 1')
            ->where('access = 1');
        $db->setQuery($query);
        $totalArticles = (int) $db->loadResult();

        if ($totalArticles === 0) {
            return ['processed' => 0, 'updated' => 0];
        }

        // Process in batches
        while ($start < $totalArticles) {
            $query = $db->getQuery(true)
                ->select('id, access')
                ->from('#__content')
                ->where('state = 1')
                ->where('access = 1')
                ->order('id ASC');
            $db->setQuery($query, $start, $batchSize);
            $articles = $db->loadObjectList();

            if (empty($articles)) {
                break;
            }

            foreach ($articles as $article) {
                $totalProcessed++;
                if ($this->applyRobotsToArticle($article->id, $article->access)) {
                    $totalUpdated++;
                }
            }

            $start += $batchSize;
        }

        return [
            'processed' => $totalProcessed,
            'updated' => $totalUpdated
        ];
    }

    /**
     * AJAX endpoint: index.php?option=com_ajax&plugin=robotsmanager&group=system&format=json
     *
     * @return  JsonResponse
     *
     * @since   1.0.0
     */
    public function onAjaxRobotsmanager()
    {
        $app   = Factory::getApplication();
        $db    = Factory::getDbo();
        $start = (int) $app->input->getInt('start', 0);
        $limit = (int) $app->input->getInt('limit', (int) $this->params->get('batch_size', 50));

        // Total articles count
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__content'))
            ->where($db->quoteName('state') . ' = 1')
            ->where($db->quoteName('access') . ' = 1');
        $db->setQuery($query);
        $total = (int) $db->loadResult();

        // Compute total eligible (articles whose access appears in rules)
        $rules = (array) $this->params->get('rules', []);
        $accessIds = [];
        foreach ($rules as $r)
        {
            if (isset($r['access']))
            {
                $accessIds[] = (int) $r['access'];
            }
        }
        $accessIds = array_values(array_unique(array_filter($accessIds)));
        $eligible = 0;
        if (!empty($accessIds))
        {
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__content'))
                ->where($db->quoteName('access') . ' IN (' . implode(',', $accessIds) . ')');
            $db->setQuery($query);
            $eligible = (int) $db->loadResult();
        }

        if ($total === 0)
        {
            return new JsonResponse(['total' => 0, 'processed' => 0, 'updated' => 0, 'eligible' => 0, 'done' => true]);
        }

        // Get batch of articles
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'access']))
            ->from($db->quoteName('#__content'))
            ->where($db->quoteName('state') . ' = 1')
            ->where($db->quoteName('access') . ' = 1')
            ->order($db->quoteName('id') . ' ASC');
        $db->setQuery($query, $start, $limit);
        $rows = (array) $db->loadObjectList();

        $updatedInBatch = 0;
        foreach ($rows as $row)
        {
            if ($this->applyRobotsToArticle((int) $row->id, (int) $row->access))
            {
                $updatedInBatch++;
            }
        }

        $processedCount = count($rows);
        $done           = ($start + $processedCount) >= $total;

        return new JsonResponse([
            'total'     => $total,
            'eligible'  => $eligible,
            'processed' => $processedCount,
            'updated'   => $updatedInBatch,
            'done'      => $done,
        ]);
    }

    /**
     * Manual batch process - triggered via backend (you can hook into toolbar later)
     *
     * @param   int  $batchSize  The number of articles to process per batch
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public function runBatchUpdate($batchSize = 20)
    {
        $db = Factory::getDbo();

        $query = $db->getQuery(true)
            ->select('id, access')
            ->from('#__content');
        $db->setQuery($query);
        $articles = $db->loadObjectList();

        $processed = 0;
        $updated = 0;
        
        foreach ($articles as $article)
        {
            $processed++;
            if ($this->applyRobotsToArticle($article->id, $article->access))
            {
                $updated++;
            }
        }
        
        return [
            'processed' => $processed,
            'updated' => $updated
        ];
    }

    /**
     * Apply robots to one article safely (updates only JSON robots key)
     * Returns true if an update was applied, false otherwise
     *
     * @param   int  $articleId  The article ID
     * @param   int  $access     The access level
     *
     * @return  bool
     *
     * @since   1.0.0
     */
    protected function applyRobotsToArticle($articleId, $access)
    {
        $db       = Factory::getDbo();
        $mappings = $this->params->get('rules', []);

        if (empty($mappings)) return false;

        $robots = null;
        foreach ($mappings as $map)
        {
            // Handle both array and object formats
            $mapAccess = null;
            $mapRobots = null;
            
            if (is_array($map)) {
                // Check if it's the nested structure like in validation
                if (isset($map['rules']['access'])) {
                    $mapAccess = $map['rules']['access'];
                    $mapRobots = $map['rules']['robots'] ?? null;
                } else {
                    // Direct structure
                    $mapAccess = $map['access'] ?? null;
                    $mapRobots = $map['robots'] ?? null;
                }
            } elseif (is_object($map)) {
                // Handle stdClass objects
                if (isset($map->rules->access)) {
                    $mapAccess = $map->rules->access;
                    $mapRobots = $map->rules->robots ?? null;
                } else {
                    $mapAccess = $map->access ?? null;
                    $mapRobots = $map->robots ?? null;
                }
            }
            
            if ((int) $mapAccess === (int) $access)
            {
                $robots = $mapRobots;
                break;
            }
        }

        if (!$robots) return false;

        // Get current metadata JSON
        $query = $db->getQuery(true)
            ->select('metadata')
            ->from('#__content')
            ->where('id = ' . (int) $articleId);
        $db->setQuery($query);
        $metadataJson = $db->loadResult();

        $metadata = json_decode($metadataJson, true);
        if (!is_array($metadata)) $metadata = [];

        // If already same, skip write
        if (isset($metadata['robots']) && $metadata['robots'] === $robots)
        {
            return false;
        }

        // Update only robots key
        $metadata['robots'] = $robots;

        $query = $db->getQuery(true)
            ->update('#__content')
            ->set('metadata = ' . $db->quote(json_encode($metadata)))
            ->where('id = ' . (int) $articleId);
        $db->setQuery($query)->execute();

        return true;
    }

    /**
     * Process ALL menus in batches - no AJAX, direct processing
     *
     * @param   int  $batchSize  The number of menus to process per batch
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public function runBatchUpdateMenus($batchSize = 50)
    {
        $db = Factory::getDbo();
        $totalProcessed = 0;
        $totalUpdated = 0;
        $start = 0;

        // Get total count first
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from('#__menu')
            ->where('client_id = 0')
            ->where('published = 1')
            ->where('access = 1');
        $db->setQuery($query);
        $totalMenus = (int) $db->loadResult();

        if ($totalMenus === 0) {
            return ['processed' => 0, 'updated' => 0];
        }

        // Process in batches
        while ($start < $totalMenus) {
            $query = $db->getQuery(true)
                ->select('id, access')
                ->from('#__menu')
                ->where('client_id = 0')
                ->where('published = 1')
                ->where('access = 1')
                ->order('id ASC');
            $db->setQuery($query, $start, $batchSize);
            $menus = $db->loadObjectList();

            if (empty($menus)) {
                break;
            }

            foreach ($menus as $menu) {
                $totalProcessed++;
                if ($this->applyRobotsToMenu($menu->id, $menu->access)) {
                    $totalUpdated++;
                }
            }

            $start += $batchSize;
        }

        return [
            'processed' => $totalProcessed,
            'updated' => $totalUpdated
        ];
    }

    /**
     * Apply robots to one menu safely (updates only JSON robots key in params)
     * Returns true if an update was applied, false otherwise
     *
     * @param   int  $menuId  The menu ID
     * @param   int  $access  The access level
     *
     * @return  bool
     *
     * @since   1.0.0
     */
    protected function applyRobotsToMenu($menuId, $access)
    {
        $db       = Factory::getDbo();
        $mappings = $this->params->get('menu_rules', []);

        if (empty($mappings)) return false;

        $robots = null;
        foreach ($mappings as $map)
        {
            // Handle both array and object formats
            $mapAccess = null;
            $mapRobots = null;
            
            if (is_array($map)) {
                // Check if it's the nested structure like in validation
                if (isset($map['rules']['access'])) {
                    $mapAccess = $map['rules']['access'];
                    $mapRobots = $map['rules']['robots'] ?? null;
                } else {
                    // Direct structure
                    $mapAccess = $map['access'] ?? null;
                    $mapRobots = $map['robots'] ?? null;
                }
            } elseif (is_object($map)) {
                // Handle stdClass objects
                if (isset($map->rules->access)) {
                    $mapAccess = $map->rules->access;
                    $mapRobots = $map->rules->robots ?? null;
                } else {
                    $mapAccess = $map->access ?? null;
                    $mapRobots = $map->robots ?? null;
                }
            }
            
            if ((int) $mapAccess === (int) $access)
            {
                $robots = $mapRobots;
                break;
            }
        }

        if (!$robots) return false;

        // Get current params JSON
        $query = $db->getQuery(true)
            ->select('params')
            ->from('#__menu')
            ->where('id = ' . (int) $menuId);
        $db->setQuery($query);
        $paramsJson = $db->loadResult();

        $params = json_decode($paramsJson, true);
        if (!is_array($params)) $params = [];

        // If already same, skip write
        if (isset($params['robots']) && $params['robots'] === $robots)
        {
            return false;
        }

        // Update only robots key in params
        $params['robots'] = $robots;

        $query = $db->getQuery(true)
            ->update('#__menu')
            ->set('params = ' . $db->quote(json_encode($params)))
            ->where('id = ' . (int) $menuId);
        $db->setQuery($query)->execute();

        return true;
    }
}
