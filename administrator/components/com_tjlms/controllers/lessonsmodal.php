<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;

/**
 * Lessons Modal Controller
 *
 * Handles AJAX requests and modal view rendering for lessons.
 *
 * @since  1.0.0
 */
class TjlmsControllerLessonsModal extends FormController
{
    /**
     * Display the Lessons Modal view
     *
     * Overrides the default display method to ensure
     * the correct view is loaded.
     *
     * @param   boolean  $cachable   If true, the view output will be cached.
     * @param   array    $urlparams  An array of safe url parameters and their variable types.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function display($cachable = false, $urlparams = [])
    {
        $view = $this->input->getCmd('view', 'lessonsmodal');
        $this->input->set('view', $view);

        parent::display($cachable, $urlparams);
    }

    /**
     * AJAX method to duplicate and assign a lesson to a course/module
     *
     * Called via AJAX when a user selects a lesson from the modal.
     *
     * @return  void  JSON response is echoed and application terminated.
     *
     * @since   1.0.0
     */
    public function addLessonAjax()
    {
        $app   = Factory::getApplication();
        $input = $app->input;

        $lessonId = $input->post->getInt('lesson_id');
        $courseId = $input->post->getInt('course_id');
        $moduleId = $input->post->getInt('module_id');

        // Validate input
        if (!$lessonId || !$courseId) {
            echo new JsonResponse(null, 'Missing parameters', true);
            $app->close();
        }

        try {
            /** @var \TjlmsModelLessonsmodal $model */
            $model = $this->getModel('Lessonsmodal');

            // Duplicate lesson for the given course & module
            $newId = $model->createLessonFromExisting($lessonId, $courseId, $moduleId);

            echo new JsonResponse(
                ['new_id' => $newId],
                'Lesson added successfully!',
                true
            );
        } catch (\Exception $e) {
            echo new JsonResponse(
                null,
                $e->getMessage(),
                false
            );
        }

        $app->close();
    }
}
