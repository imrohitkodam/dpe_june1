<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;

/**
 * Model class for Lessons Modal
 *
 * Handles fetching, filtering, and duplicating lessons.
 *
 * @since  1.0.0
 */
class TjlmsModelLessonsmodal extends ListModel
{
    /**
     * Build the query for retrieving lesson list
     *
     * @return  \JDatabaseQuery
     *
     * @since   1.0.0
     */
    protected function getListQuery()
    {
        $db    = $this->getDbo();
        $query = $db->getQuery(true);

        $query->select('l.*')
              ->from($db->qn('#__tjlms_lessons', 'l'))
              ->where('l.course_id > 0')
              ->where('l.format != ' . $db->quote('externaltool'));
              

        // Filter: search
        $search = $this->getState('filter.search');
        if (!empty($search))
        {
            $search = '%' . $db->escape($search, true) . '%';
            $query->where('l.title LIKE ' . $db->quote($search));
        }

        // Filter: format
        $format = $this->getState('filter.format');
        if (!empty($format))
        {
            $query->where('l.format = ' . $db->quote($format));
        }

        // Ordering
        $orderCol  = $this->state->get('list.ordering', 'l.ordering');
        $orderDirn = $this->state->get('list.direction', 'ASC');
        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }

    /**
     * Auto-populate model state
     *
     * @param   string  $ordering   Default ordering column
     * @param   string  $direction  Default ordering direction
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function populateState($ordering = null, $direction = null)
    {
        $app = Factory::getApplication();

        $search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
        $this->setState('filter.search', $search);

        $format = $app->getUserStateFromRequest($this->context . '.filter.format', 'filter_format', '', 'string');
        $this->setState('filter.format', $format);

        $limit = $app->getUserStateFromRequest(
            $this->context . '.list.limit',
            'limit',
            $app->get('list_limit', 5),
            'int'
        );
        $this->setState('list.limit', $limit);

        $limitstart = $app->input->getInt('limitstart', 0);
        $this->setState('list.start', $limitstart);

        parent::populateState('l.ordering', 'ASC');
    }

    /**
     * Load form filter data
     *
     * @return  array  Data with filters and list settings
     *
     * @since   1.0.0
     */
    protected function loadFormData()
    {
        $app = Factory::getApplication();

        $data = [];
        $data['filter'] = (array) $app->getUserState($this->context . '.filter', []);
        $data['list'] = [
            'limit'     => $this->getState('list.limit'),
            'start'     => $this->getState('list.start'),
            'ordering'  => $this->getState('list.ordering'),
            'direction' => $this->getState('list.direction'),
        ];

        return $data;
    }

    /**
     * Duplicate a lesson and assign it to course/module
     *
     * @param   int  $lessonId  The lesson ID to duplicate
     * @param   int  $courseId  The course ID to assign
     * @param   int  $moduleId  The module ID to assign
     *
     * @return  int  New lesson ID
     *
     * @throws  \Exception  If the lesson is not found or insert fails
     *
     * @since   1.0.0
     */
    public function createLessonFromExisting($lessonId, $courseId, $moduleId)
    {
        $db = Factory::getDbo();
        $user = Factory::getUser();

        // Fetch lesson
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__tjlms_lessons'))
            ->where('id = ' . (int) $lessonId);
        $db->setQuery($query);
        $lesson = $db->loadAssoc();

        if (!$lesson) {
            throw new \Exception('Lesson not found');
        }

        // Reset primary key
        unset($lesson['id']);

        // Generate unique alias
        $lesson['alias']     = $this->generateUniqueAlias($lesson['alias']);
        $lesson['course_id'] = $courseId;
        $lesson['mod_id']    = $moduleId;
        $lesson['created_by'] = (int) $user->id;

        // Insert duplicate
        $columns = array_keys($lesson);
        $values  = array_map([$db, 'quote'], array_values($lesson));

        $query = $db->getQuery(true)
            ->insert($db->quoteName('#__tjlms_lessons'))
            ->columns($db->quoteName($columns))
            ->values(implode(',', $values));

        $db->setQuery($query)->execute();

        $newLessonId =(int) $db->insertid();

        if($lesson['format'] == 'scorm'){
        
                // Fetch lesson
                $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__tjlms_scorm'))
                ->where('lesson_id = ' . (int) $lessonId);
            $db->setQuery($query);
            $scromLesson = $db->loadAssoc();



            if (!$scromLesson) {
                throw new \Exception('Scrom Lesson not found');
            }

            unset($scromLesson['id']);

            $scromLesson['lesson_id'] = $newLessonId;

            $columns = array_keys($scromLesson);
            $values  = array_map([$db, 'quote'], array_values($scromLesson));

            $query = $db->getQuery(true)
            ->insert($db->quoteName('#__tjlms_scorm'))
            ->columns($db->quoteName($columns))
            ->values(implode(',', $values));

        $db->setQuery($query)->execute();

        return (int) $db->insertid();
        }
        else{
            return $newLessonId;
        }
    }

    /**
     * Generate unique alias for duplicated lesson
     *
     * @param   string  $baseAlias  The original alias
     *
     * @return  string  Unique alias
     *
     * @since   1.0.0
     */
    public function generateUniqueAlias($baseAlias)
    {
        $db = Factory::getDbo();

        $alias = $baseAlias;
        $i     = 1;
        $newAlias = $alias;

        while (true)
        {
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__tjlms_lessons'))
                ->where($db->quoteName('alias') . ' = ' . $db->quote($newAlias));

            $db->setQuery($query);
            $exists = (int) $db->loadResult();

            if ($exists === 0) {
                return $newAlias;
            }

            $newAlias = $alias . '-' . $i;
            $i++;
        }
    }
}
