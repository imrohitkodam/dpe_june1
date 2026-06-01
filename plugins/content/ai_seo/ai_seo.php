<?php
// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;

class PlgContentAi_seo extends CMSPlugin
{
    protected $app;
    protected $db;
    protected $autoloadLanguage = true;

	/**
	 * Constructor
	 *
	 * @param   string  &$subject  subject
	 * @param   array   $config    config
	 */
    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
        $this->db = Factory::getDbo();
        $this->app = Factory::getApplication();
        $this->loadLanguage('plg_content_ai_seo', __DIR__);
    }

	/**
	 * Admin AJAX entrypoints (for direct admin URL access)
	 *
	 * @return  void
	 */
    public function onAfterInitialise()
    {
        $app = $this->app;
        if (!$app->isClient('administrator')) {
            return;
        }

        $input = $app->input;
        $task = $input->getCmd('ai_seo_task', '');

        if ($task === 'adminUI') {
            $this->renderAdminUI();
            $app->close();
        }

        if ($task === 'batchKeywords') {
            $this->handleBatchKeywords();
            $app->close();
        }

        if ($task === 'generateMeta') {
            $this->handleGenerateMeta();
            $app->close();
        }

        if ($task === 'generateAlt') {
            $this->handleGenerateAlt();
            $app->close();
        }

        if ($task === 'suggestContent') {
            $this->handleSuggestContent();
            $app->close();
        }
    }

	/**
	 * Prepare form and inject assets
	 *
	 * @param   object  $form  form
	 * @param   object  $data  data
	 *
	 * @return  boolean
	 */
    public function onContentPrepareForm($form, $data)
    {
        $app = $this->app;
        
        // Only in administrator
        if (!$app->isClient('administrator')) {
            return true;
        }

        $input = $app->input;
        $option = $input->getCmd('option', '');
        $view = $input->getCmd('view', '');

        // Only on article edit page
        if ($option !== 'com_content' || ($view !== 'article' && $view !== 'form')) {
            return true;
        }

        // Check if the form is for article

        if ($form->getName() !== 'com_content.article') {
            return true;
        }



        // Load CSS and JS with versioning to bust cache
        $ver = date('YmdHis') . '_v8';
        $doc = Factory::getDocument();
        $doc->addStyleSheet(Uri::root(true) . '/media/plg_content_ai_seo/css/ai_seo.css?v=' . $ver);
        $doc->addScript(Uri::root(true) . '/media/plg_content_ai_seo/js/ai_seo.js?v=' . $ver);

        // Pass AJAX URL and labels to JS
        $doc->addScriptOptions('ai_seo', [
            'ajaxUrl' => Uri::root(true) . '/administrator/index.php?option=com_ajax&plugin=ai_seo&group=content&format=raw',
            'labels'  => [
                'title'                   => Text::_('PLG_CONTENT_AI_SEO_TITLE'),
                'analyze_content'        => Text::_('PLG_CONTENT_AI_SEO_ANALYZE_CONTENT'),
                'optimize_content'       => Text::_('PLG_CONTENT_AI_SEO_OPTIMIZE_CONTENT'),
                'generate_article'        => Text::_('PLG_CONTENT_AI_SEO_GENERATE_ARTICLE'),
                'optimize_title'          => Text::_('PLG_CONTENT_AI_SEO_OPTIMIZE_TITLE'),
                'generate_alt'            => Text::_('PLG_CONTENT_AI_SEO_GENERATE_ALT'),
                'scan_images'             => Text::_('PLG_CONTENT_AI_SEO_SCAN_IMAGES'),
                'generate_meta'           => Text::_('PLG_CONTENT_AI_SEO_GENERATE_META'),
                'autofill_schema'         => Text::_('PLG_CONTENT_AI_SEO_AUTOFILL_SCHEMA'),
                'custom_prompt'           => Text::_('PLG_CONTENT_AI_SEO_CUSTOM_PROMPT_LABEL'),
                'placeholder'             => Text::_('PLG_CONTENT_AI_SEO_CUSTOM_PROMPT_PLACEHOLDER'),
                'generate'                => Text::_('PLG_CONTENT_AI_SEO_GENERATE'),
                'generating'              => Text::_('PLG_CONTENT_AI_SEO_GENERATING'),
                'optimizing_title'        => Text::_('PLG_CONTENT_AI_SEO_OPTIMIZING_TITLE'),
                'title_optimized'         => Text::_('PLG_CONTENT_AI_SEO_TITLE_OPTIMIZED'),
                'meta_filled'             => Text::_('PLG_CONTENT_AI_SEO_META_FILLED'),
                'content_rewritten'       => Text::_('PLG_CONTENT_AI_SEO_CONTENT_REWRITTEN'),
                'apply_success'           => Text::_('PLG_CONTENT_AI_SEO_APPLY_SUCCESS'),
                'replace_confirm'         => Text::_('PLG_CONTENT_AI_SEO_REPLACE_CONFIRM'),
                'err_unauthorized'        => Text::_('PLG_CONTENT_AI_SEO_ERROR_UNAUTHORIZED'),
                'err_no_content'          => Text::_('PLG_CONTENT_AI_SEO_ERROR_NO_CONTENT'),
                'err_fetch_failed'        => Text::_('PLG_CONTENT_AI_SEO_ERROR_FETCH_FAILED'),
                'err_no_editor'           => Text::_('PLG_CONTENT_AI_SEO_ERROR_NO_EDITOR'),
                'may_take_time'           => Text::_('PLG_CONTENT_AI_SEO_MAY_TAKE_TIME'),
                'rewriting_content'       => Text::_('PLG_CONTENT_AI_SEO_REWRITING_CONTENT'),
                'gen_article'             => Text::_('PLG_CONTENT_AI_SEO_GENERATING_ARTICLE'),
                'gen_meta'                => Text::_('PLG_CONTENT_AI_SEO_GENERATING_META'),
                'gen_schema'              => Text::_('PLG_CONTENT_AI_SEO_GENERATING_SCHEMA'),
                'gen_alt_multiple'        => Text::_('PLG_CONTENT_AI_SEO_GENERATING_ALT_MULTIPLE'),
                'no_images'               => Text::_('PLG_CONTENT_AI_SEO_NO_IMAGES'),
                'all_images_alt'          => Text::_('PLG_CONTENT_AI_SEO_ALL_IMAGES_HAVE_ALT'),
                'gen_alt_for'             => Text::_('PLG_CONTENT_AI_SEO_GENERATED_ALT_FOR'),
                'schema_type_none_err'    => Text::_('PLG_CONTENT_AI_SEO_SCHEMA_TYPE_NONE_ERROR'),
                'schema_gen_heading'      => Text::_('PLG_CONTENT_AI_SEO_SCHEMA_GENERATED'),
                'schema_name_label'       => Text::_('PLG_CONTENT_AI_SEO_SCHEMA_NAME_LABEL'),
                'schema_desc_label'       => Text::_('PLG_CONTENT_AI_SEO_SCHEMA_DESC_LABEL'),
                'schema_applied'          => Text::_('PLG_CONTENT_AI_SEO_SCHEMA_APPLIED'),
                'schema_copy_manual'      => Text::_('PLG_CONTENT_AI_SEO_SCHEMA_COPY_MANUAL'),
                'custom_prompt_err'       => Text::_('PLG_CONTENT_AI_SEO_CUSTOM_PROMPT_ERROR'),
                'copy_field_success'      => Text::_('PLG_CONTENT_AI_SEO_COPY_FIELD_SUCCESS'),
                'copy_field_err'          => Text::_('PLG_CONTENT_AI_SEO_COPY_FIELD_ERROR'),
                'copy_clip_success'       => Text::_('PLG_CONTENT_AI_SEO_COPY_CLIP_SUCCESS'),
                'copy_clip_err'           => Text::_('PLG_CONTENT_AI_SEO_COPY_CLIP_ERROR'),
                'scan_results_heading'    => Text::_('PLG_CONTENT_AI_SEO_SCAN_RESULTS'),
                'found_images_msg'        => Text::_('PLG_CONTENT_AI_SEO_FOUND_IMAGES'),
                'missing_alt_text'        => Text::_('PLG_CONTENT_AI_SEO_MISSING_ALT_TEXT'),
                'no_alt_text_em'          => Text::_('PLG_CONTENT_AI_SEO_NO_ALT_TEXT_EM'),
                'scan_prompt'             => Text::_('PLG_CONTENT_AI_SEO_SCAN_PROMPT'),
                'alt_gen_heading'         => Text::_('PLG_CONTENT_AI_SEO_ALT_GENERATED_HEADING'),
                'alt_gen_success_toast'   => Text::_('PLG_CONTENT_AI_SEO_ALT_GEN_SUCCESS_TOAST'),
                'type_intro'              => Text::_('PLG_CONTENT_AI_SEO_TYPE_INTRO'),
                'type_full'               => Text::_('PLG_CONTENT_AI_SEO_TYPE_FULL'),
                'type_content'            => Text::_('PLG_CONTENT_AI_SEO_TYPE_CONTENT'),
                'type_intro_banner'       => Text::_('PLG_CONTENT_AI_SEO_TYPE_INTRO_BANNER'),
                'type_full_banner'        => Text::_('PLG_CONTENT_AI_SEO_TYPE_FULL_BANNER'),
                'type_content_banner'     => Text::_('PLG_CONTENT_AI_SEO_TYPE_CONTENT_BANNER'),
                'alt_label'               => Text::_('PLG_CONTENT_AI_SEO_ALT_LABEL'),
                'opt_content_ready'       => Text::_('PLG_CONTENT_AI_SEO_OPTIMIZED_CONTENT_READY'),
                'rewrite_summary'         => Text::_('PLG_CONTENT_AI_SEO_REWRITE_SUMMARY'),
                'apply_to_editor'         => Text::_('PLG_CONTENT_AI_SEO_APPLY_TO_EDITOR'),
                'cancel'                  => Text::_('PLG_CONTENT_AI_SEO_CANCEL'),
                'view_raw_html'           => Text::_('PLG_CONTENT_AI_SEO_VIEW_RAW_HTML'),
                'analysis_heading'        => Text::_('PLG_CONTENT_AI_SEO_ANALYSIS_HEADING'),
                'no_suggestions_avail'    => Text::_('PLG_CONTENT_AI_SEO_NO_SUGGESTIONS_AVAILABLE'),
                'gen_alt_btn_loading'     => Text::_('PLG_CONTENT_AI_SEO_GENERATING_ALT_BTN'),
                'gen_alt_btn'             => Text::_('PLG_CONTENT_AI_SEO_GENERATE_ALT_BTN'),
                'auto_fill_both'          => Text::_('PLG_CONTENT_AI_SEO_AUTO_FILL_BOTH'),
                'auto_fill_keywords'      => Text::_('PLG_CONTENT_AI_SEO_AUTO_FILL_KEYWORDS'),
                'auto_fill_desc'          => Text::_('PLG_CONTENT_AI_SEO_AUTO_FILL_DESC'),
                'meta_keywords_label'     => Text::_('PLG_CONTENT_AI_SEO_KEYWORDS_LABEL'),
                'meta_desc_label'         => Text::_('PLG_CONTENT_AI_SEO_DESCRIPTION_LABEL'),
                'char_count_msg'          => Text::_('PLG_CONTENT_AI_SEO_CHAR_COUNT'),
                'copy_to_clipboard'       => Text::_('PLG_CONTENT_AI_SEO_COPY_TO_CLIPBOARD'),
                'err_prefix'              => Text::_('PLG_CONTENT_AI_SEO_ERROR_PREFIX'),
                'fetch_err_prefix'        => Text::_('PLG_CONTENT_AI_SEO_FETCH_ERROR_PREFIX'),
                'http_err_prefix'         => Text::_('PLG_CONTENT_AI_SEO_HTTP_ERROR_PREFIX'),
            ]
        ]);
        
        return true;
    }

	/**
	 * AJAX Handler - Called via com_ajax
	 *
	 * @return  string
	 */
    public function onAjaxAi_seo()
    {
        $app = $this->app;
        $input = $app->input;
        $task = $input->getCmd('ai_seo_task', '');

        // Route to appropriate handler
        switch ($task) {
            case 'adminUI':
                return $this->renderAdminUIContent();
            case 'batchKeywords':
                return $this->handleBatchKeywordsAjax();
            case 'generateMeta':
                return $this->handleGenerateMetaAjax();
            case 'generateAlt':
                return $this->handleGenerateAltAjax();
            case 'suggestContent':
                return $this->handleSuggestContentAjax();
            case 'rewriteContent':
                return $this->handleRewriteContentAjax();
            case 'generateArticle':
                return $this->handleGenerateArticleAjax();
            case 'generateCustomContent':
                return $this->handleGenerateCustomContentAjax();
            case 'generateSchemaData':
                return $this->handleGenerateSchemaDataAjax();
            case 'optimizeTitle':
                return $this->handleOptimizeTitleAjax();
            default:
                return json_encode(['error' => Text::_('PLG_CONTENT_AI_SEO_ERROR_UNKNOWN_TASK') . ': ' . $task]);
        }
    }

    /**
     * AJAX: Generate Meta (returns JSON string)
     */
	/**
	 * Helper: Validate AI Config
	 *
	 * @return  string|null
	 */
    protected function validateAIConfig()
    {
        $provider = $this->params->get('ai_provider', 'groq');
        $apiKey = '';

        switch ($provider) {
            case 'openai':
                $apiKey = trim($this->params->get('openai_api_key', ''));
                if (empty($apiKey)) return 'OpenAI API key not configured.';
                break;
            case 'claude':
                $apiKey = trim($this->params->get('claude_api_key', ''));
                if (empty($apiKey)) return 'Claude API key not configured.';
                break;
            case 'gemini':
                $apiKey = trim($this->params->get('gemini_api_key', ''));
                if (empty($apiKey)) return 'Gemini API key not configured.';
                break;
            case 'groq':
            default:
                $apiKey = trim($this->params->get('groq_api_key', ''));
                if (empty($apiKey)) return 'Groq API key not configured.';
                break;
        }
        return null;
    }

	/**
	 * AJAX: Generate Meta
	 *
	 * @return  string
	 */
    protected function handleGenerateMetaAjax()
    {
        $user = Factory::getUser();
        if (!$user->authorise('core.edit', 'com_content')) {
            return json_encode(['error' => 'Unauthorized']);
        }

        // Check if feature is enabled
        if (!$this->params->get('enabled_keywords', 1) && !$this->params->get('enabled_meta_description', 0)) {
            return json_encode(['error' => 'Generate Meta features are disabled in plugin settings.']);
        }

        $input = $this->app->input;
        $title = $input->getString('title', '');
        $body = $input->getString('body', '');
        
        if ($error = $this->validateAIConfig()) {
            return json_encode(['error' => $error]);
        }

        if (empty($title) && empty($body)) {
            return json_encode(['error' => 'Title or body is required']);
        }

        try {
            $maxK = (int)$this->params->get('max_keywords', 10);
            $metaDescLength = (int)$this->params->get('meta_desc_length', 160);
            
            $keywords = $this->generateKeywords($title, $body, $maxK);
            
            if (!$keywords) {
                return json_encode(['error' => 'Failed to generate keywords.']);
            }
            
            $description = $this->generateMetaDescription($title, $body, $metaDescLength);
            
            if (!$description) {
                $description = 'Unable to generate description';
            }

            return json_encode([
                'success' => true,
                'keywords' => $keywords,
                'description' => $description
            ]);
        } catch (\Exception $e) {
            return json_encode(['error' => 'Error: ' . $e->getMessage()]);
        }
    }

	/**
	 * AJAX: Generate Alt Text
	 *
	 * @return  string
	 */
    protected function handleGenerateAltAjax()
    {
        $user = Factory::getUser();
        if (!$user->authorise('core.edit', 'com_content')) {
            return json_encode(['error' => 'Unauthorized']);
        }

        // Check if feature is enabled
        if (!$this->params->get('enabled_alt', 0)) {
            return json_encode(['error' => 'Generate ALT tags is disabled in plugin settings.']);
        }

        $input = $this->app->input;
        $imageUrl = $input->getString('image_url', '');
        $articleTitle = $input->getString('title', '');
        $articleContent = $input->getString('body', '');
        
        if ($error = $this->validateAIConfig()) {
            return json_encode(['error' => $error]);
        }

        if (empty($imageUrl)) {
            return json_encode(['error' => 'Image URL is required']);
        }

        $altText = $this->generateAltText(
            $imageUrl,
            $articleTitle,
            $articleContent
        );

        return json_encode([
            'success' => true,
            'alt_text' => $altText ?: 'Unable to generate alt text'
        ]);
    }

	/**
	 * AJAX: Suggest Content
	 *
	 * @return  string
	 */
    protected function handleSuggestContentAjax()
    {
        // Check if feature is enabled
        if (!$this->params->get('enabled_content_analysis', 1)) {
            return json_encode(['error' => 'Content Analysis is disabled in plugin settings.']);
        }

        $input = $this->app->input;
        $title = $input->getString('title', '');
        $body = $input->get('body', '', 'raw'); // Get raw HTML
        
        if ($error = $this->validateAIConfig()) return json_encode(['error' => $error]);
        if (empty($body)) return json_encode(['error' => 'No content to analyze']);

        $suggestions = $this->generateContentSuggestions($title, $body);

        return json_encode([
            'success' => true,
            'suggestions' => $suggestions ?: 'No suggestions generated'
        ]);
    }

	/**
	 * AJAX: Rewrite Content
	 *
	 * @return  string
	 */
    protected function handleRewriteContentAjax()
    {
        // Check if feature is enabled
        if (!$this->params->get('enabled_content_rewrite', 1)) {
            return json_encode(['error' => 'Content Rewrite is disabled in plugin settings.']);
        }

        $input = $this->app->input;
        $title = $input->getString('title', '');
        $body = $input->get('body', '', 'raw'); // Get raw HTML
        
        if ($error = $this->validateAIConfig()) return json_encode(['error' => $error]);
        if (empty($body)) return json_encode(['error' => 'No content to rewrite']);

        $newContent = $this->rewriteContentForSeo($title, $body);

        return json_encode([
            'success' => true,
            'new_content' => $newContent ?: ''
        ]);
    }

	/**
	 * Rewrite content for improved SEO
	 *
	 * @param   string  $title  title
	 * @param   string  $body   body
	 *
	 * @return  string
	 */
    protected function rewriteContentForSeo($title, $body)
    {
        $customPrompt = trim($this->params->get('rewrite_prompt', ''));
        $length = $this->params->get('article_length', '1500-2500');
        $style = $this->params->get('writing_style', 'creative');
        $tone = $this->params->get('writing_tone', 'neutral');
        $lang = $this->params->get('target_language', 'English');
        
        if (!empty($customPrompt)) {
            $prompt = str_replace(
                ['{title}', '{body}', '{length}', '{style}', '{tone}', '{language}'], 
                [$title, $body, $length, $style, $tone, $lang], 
                $customPrompt
            );
        } else {
            $prompt = <<<PROMPT
You are an expert SEO Copywriter. Rewrite and EXPAND the following article content to improve its SEO performance, readability, and engagement.

**Target Language:** {$lang}
**Writing Style:** {$style}
**Writing Tone:** {$tone}

**Article Title:** {$title}

... (rest of the requirements) ...

**IMPORTANT:** Return **ONLY** the full raw HTML code. Do not include markdown. Just the clean HTML code in {$lang}.
PROMPT;
            // I will use a more compressed version of the default prompt to keep it efficient while including the new requirements.
            $prompt = "You are an expert SEO Copywriter. Rewrite and EXPAND the following HTML article content to improve SEO, readability, and engagement.\n\n"
                    . "Target Language: {$lang}\n"
                    . "Writing Style: {$style}\n"
                    . "Writing Tone: {$tone}\n\n"
                    . "Title: {$title}\n\n"
                    . "Original Content (HTML):\n{$body}\n\n"
                    . "Requirements:\n"
                    . "1. Preserve ALL URLs and Links exactly.\n"
                    . "2. Maintain HTML structure (images, divs, classes).\n"
                    . "3. Expand to {$length} words with valuable info.\n"
                    . "4. Improve readability and SEO heading hierarchy.\n"
                    . "5. Return ONLY clean HTML code in {$lang}. No markdown.";
        }

        $maxTokens = (int)$this->params->get('max_tokens_rewrite', 5000);
        return $this->callAI($prompt, $maxTokens);
    }


	/**
	 * AJAX: Generate Article
	 *
	 * @return  string
	 */
    protected function handleGenerateArticleAjax()
    {
        // Check if feature is enabled
        if (!$this->params->get('enabled_article_generation', 1)) {
            return json_encode(['error' => 'Article Generation is disabled in plugin settings.']);
        }

        $input = $this->app->input;
        $title = $input->getString('title', '');
        
        if ($error = $this->validateAIConfig()) return json_encode(['error' => $error]);
        if (empty($title)) return json_encode(['error' => 'Article title is required']);

        $content = $this->generateArticleContent($title);

        return json_encode([
            'success' => true,
            'content' => $content ?: ''
        ]);
    }

    protected function generateArticleContent($title)
    {
        $customPrompt = trim($this->params->get('article_prompt', ''));
        $length = $this->params->get('article_length', '1500-2500');
        $style = $this->params->get('writing_style', 'creative');
        $tone = $this->params->get('writing_tone', 'neutral');
        $lang = $this->params->get('target_language', 'English');

        if (!empty($customPrompt)) {
            $prompt = str_replace(
                ['{title}', '{length}', '{style}', '{tone}', '{language}'], 
                [$title, $length, $style, $tone, $lang], 
                $customPrompt
            );
        } else {
            $prompt = "You are an expert SEO content writer. Write a comprehensive, SEO-optimized article in {$lang}.\n\n"
                    . "Title: {$title}\n"
                    . "Target Language: {$lang}\n"
                    . "Writing Style: {$style}\n"
                    . "Writing Tone: {$tone}\n"
                    . "Length: {$length} words.\n\n"
                    . "Requirements:\n"
                    . "- HTML only (h2, h3, p, ul, li).\n"
                    . "- No h1.\n"
                    . "- Professional E-E-A-T tone.\n"
                    . "- Return ONLY the full HTML in {$lang}. No markdown.";
        }

        $maxTokens = (int)$this->params->get('max_tokens_article', 5000);
        return $this->callAI($prompt, $maxTokens);
    }


	/**
	 * AJAX: Generate Schema Data
	 *
	 * @return  string
	 */
    protected function handleGenerateSchemaDataAjax()
    {
        // Check if feature is enabled
        if (!$this->params->get('enabled_schema', 1)) {
            return json_encode(['error' => 'Schema Generation is disabled in plugin settings.']);
        }

        $input = $this->app->input;
        $title = $input->getString('title', '');
        $schemaType = $input->getString('schema_type', 'BlogPosting'); // Default to BlogPosting
        
        if ($error = $this->validateAIConfig()) return json_encode(['error' => $error]);
        if (empty($title)) return json_encode(['error' => 'Article title is required']);

        $schemaData = $this->generateSchemaData($title, $schemaType);

        return json_encode([
            'success' => true,
            'schema' => $schemaData
        ]);
    }

	/**
	 * Generate Schema data based on type
	 *
	 * @param   string  $title  title
	 * @param   string  $type   type
	 *
	 * @return  array
	 */
    protected function generateSchemaData($title, $type)
    {
        $customPrompt = trim($this->params->get('schema_prompt', ''));
        $lang = $this->params->get('target_language', 'English');
        
        $fields = 'headline and description';
        $jsonStruct = '{"headline": "", "description": ""}';
        
        if ($type === 'Event') {
            $fields = 'name and description';
            $jsonStruct = '{"name": "", "description": ""}';
        }

        if (!empty($customPrompt)) {
            $prompt = str_replace(['{title}', '{language}'], [$title, $lang], $customPrompt);
        } else {
            $prompt = "Generate SEO-optimized {$type} schema data ({$fields}) for: \"{$title}\" in {$lang}.\n\n"
                    . "Requirements:\n"
                    . "- Description: 50-60 words.\n"
                    . "- JSON format ONLY.\n"
                    . "- Language: {$lang}.\n\n"
                    . "Output JSON structure:\n{$jsonStruct}";
        }

        $response = $this->callAI($prompt, 1000);
        $data = json_decode($response, true);
        
        if (!$data) {
            if ($type === 'Event') {
                return ['name' => $title, 'description' => $this->shorten($title . ' Event details.', 160)];
            }
            return ['headline' => $title, 'description' => $this->shorten($title . ' - Learn more.', 160)];
        }
        
        return $data;
    }

	/**
	 * AJAX: Optimize Title
	 *
	 * @return  string
	 */
    protected function handleOptimizeTitleAjax()
    {
        $input = $this->app->input;
        $title = $input->getString('title', '');
        $body = $input->get('body', '', 'raw');
        $lang = $this->params->get('target_language', 'English');
        $style = $this->params->get('writing_style', 'creative');
        $tone = $this->params->get('writing_tone', 'neutral');

        if ($error = $this->validateAIConfig()) return json_encode(['error' => $error]);
        if (empty($title) && empty($body)) return json_encode(['error' => 'Title or content is required to optimize']);

        $customPrompt = trim($this->params->get('title_prompt', ''));
        if (!empty($customPrompt)) {
            $prompt = str_replace(
                ['{TOPIC}', '{LANGUAGE}', '{STYLE}', '{TONE}'], 
                [$title, $lang, $style, $tone], 
                $customPrompt
            );
        } else {
            $prompt = "You are an expert SEO Copywriter. Optimize the following article title for High-CTR in {$lang}.\n\n"
                    . "Current Title: {$title}\n"
                    . "Target Language: {$lang}\n"
                    . "Style: {$style}\n"
                    . "Tone: {$tone}\n\n"
                    . "Requirements:\n"
                    . "1. Length: 50-60 characters.\n"
                    . "2. Return ONLY the new title text in {$lang}. No quotes.";
        }

        $newTitle = trim($this->callAI($prompt, 100));
        $newTitle = trim($newTitle, '"\'');

        return json_encode([
            'success' => true,
            'new_title' => $newTitle ?: $title
        ]);
    }

	/**
	 * AJAX: Generate Custom Content
	 *
	 * @return  string
	 */
    protected function handleGenerateCustomContentAjax()
    {
        $input = $this->app->input;
        $promptText = $input->getString('prompt', '');

        if ($error = $this->validateAIConfig()) return json_encode(['error' => $error]);
        if (empty($promptText)) return json_encode(['error' => 'Prompt is required']);

        // Build robust prompt with style/tone
        $style = $this->params->get('writing_style', 'creative');
        $tone = $this->params->get('writing_tone', 'neutral');
        $lang = $this->params->get('target_language', 'English');

        $fullPrompt = <<<PROMPT
You are an expert content writer.
Main Task: {$promptText}

Guidelines (unless the Task specified otherwise):
- Primary Language: {$lang}
- Writing Style: {$style}
- Emotional Tone: {$tone}
- Format: Clean HTML (h2, h3, p, ul/li)
- Depth: Professional and Comprehensive

IMPORTANT: If the user explicitly requested a specific language, style, or length in the 'Main Task' above, PRIORitize that over the guidelines.
Return ONLY the HTML content.
PROMPT;

        $content = $this->callAI($fullPrompt, 2000);

        return json_encode([
            'success' => true,
            'content' => $content ?: ''
        ]);
    }

	/**
	 * AJAX: Batch Keywords
	 *
	 * @return  string
	 */
    protected function handleBatchKeywordsAjax()
    {
        // Reuse existing method logic but return instead of echo
        ob_start();
        $this->handleBatchKeywords();
        return ob_get_clean();
    }

	/**
	 * Admin UI content
	 *
	 * @return  string
	 */
    protected function renderAdminUIContent()
    {
        ob_start();
        $this->renderAdminUI();
        return ob_get_clean();
    }

	/**
	 * Renders a small Admin UI
	 *
	 * @return  void
	 */
    protected function renderAdminUI()
    {
        $user = Factory::getUser();
        if (!$user->authorise('core.admin')) {
            echo '<div style="padding:20px">Unauthorized</div>';
            return;
        }

        // Joomla token to include in POST if needed
        $token = Session::getFormToken();

        // Basic HTML + JS. Uses com_ajax endpoint to call batchKeywords repeatedly.
        echo '<div style="font-family:Arial,Helvetica,sans-serif;padding:12px;max-width:900px">';
        echo '<h2>AI SEO — Batch Keyword Updater</h2>';
        echo '<p>This UI will iterate existing articles and generate meta keywords for articles that do not have them. Start with <strong>Dry-run</strong> to preview suggestions.</p>';

        // Controls
        echo '<div style="margin:10px 0">';
        echo '<label>Chunk size: <input id="ai_chunk" type="number" value="25" min="1" style="width:80px"/></label> ';
        echo '<label style="margin-left:12px">Pause between chunks (ms): <input id="ai_pause" type="number" value="500" min="0" style="width:80px"/></label> ';
        echo '<label style="margin-left:12px"><input id="ai_dryrun" type="checkbox" checked/> Dry-run (do not save)</label> ';
        echo '</div>';

        echo '<div style="margin:10px 0">';
        echo '<button id="ai_start" class="btn">Start</button> ';
        echo '<button id="ai_pause_btn" class="btn" disabled>Pause</button> ';
        echo '<button id="ai_cancel" class="btn" disabled>Cancel</button>';
        echo '</div>';

        // Progress
        echo '<div style="margin:14px 0">';
        echo '<div style="background:#eee;border-radius:6px;padding:6px;"><div id="ai_progress_bar" style="height:18px;background:#2b8aef;width:0%;border-radius:4px;"></div></div>';
        echo '<div id="ai_status" style="margin-top:8px">Idle</div>';
        echo '</div>';

        // Log area
        echo '<pre id="ai_log" style="height:260px;overflow:auto;background:#111;color:#eee;padding:10px;border-radius:6px"></pre>';

        // JS (uses fetch)
        $ajaxUrlBase = 'index.php?option=com_ajax&plugin=ai_seo&format=raw&ai_seo_task=batchKeywords';
        echo <<<HTML
<script>
(function(){
  let running = false;
  let paused = false;
  let cancelled = false;
  let offset = 0;
  let totalProcessed = 0;
  let estimatedTotal = null;
  const startBtn = document.getElementById('ai_start');
  const pauseBtn = document.getElementById('ai_pause_btn');
  const cancelBtn = document.getElementById('ai_cancel');
  const logEl = document.getElementById('ai_log');
  const statusEl = document.getElementById('ai_status');
  const progressBar = document.getElementById('ai_progress_bar');

  function appendLog(s){
    const time = new Date().toLocaleTimeString();
    logEl.textContent = time + ' — ' + s + "\\n" + logEl.textContent;
  }

  function updateProgress(pct){
    progressBar.style.width = pct + '%';
  }

  function setStatus(s){
    statusEl.innerText = s;
  }

  async function processChunk(limit, dryRun) {
    const url = `${'$ajaxUrlBase'}&limit=${limit}&offset=${offset}&dry_run=${dryRun ? 1 : 0}`;
    appendLog('Requesting offset ' + offset + ' (limit ' + limit + ')');
    try {
      const resp = await fetch(url, { credentials: 'same-origin' });
      if (!resp.ok) {
        appendLog('HTTP error: ' + resp.status);
        return { error: 'http ' + resp.status };
      }
      const data = await resp.json();
      return data;
    } catch (err) {
      appendLog('Fetch error: ' + err);
      return { error: err.toString() };
    }
  }

  async function run() {
    running = true;
    paused = false;
    cancelled = false;
    offset = 0;
    totalProcessed = 0;
    appendLog('--- STARTING BATCH ---');
    startBtn.disabled = true;
    pauseBtn.disabled = false;
    cancelBtn.disabled = false;

    const chunk = Math.max(1, parseInt(document.getElementById('ai_chunk').value || 25));
    const pauseMs = Math.max(0, parseInt(document.getElementById('ai_pause').value || 500));
    const dryRun = document.getElementById('ai_dryrun').checked;

    setStatus('Running (dryRun=' + dryRun + ')');

    while (!cancelled) {
      if (paused) {
        setStatus('Paused at offset ' + offset);
        await new Promise(resolve => {
          const id = setInterval(() => { if (!paused) { clearInterval(id); resolve(); } }, 300);
        });
      }

      const result = await processChunk(chunk, dryRun);

      if (result.error) {
        appendLog('Error: ' + JSON.stringify(result.error));
        setStatus('Error occurred');
        break;
      }

      // Process result
      const processed = result.processed || 0;
      totalProcessed += processed;
      offset = result.nextOffset || (offset + processed);

      appendLog('Processed ' + processed + ' rows. Saved: ' + result.results.filter(r=>r.saved).length + ', skipped: ' + result.results.filter(r=>r.skipped).length);

      // update progress: we don't know total articles; use heuristic
      // if done flag true then finish
      if (result.done) {
        appendLog('Batch done. Total processed: ' + totalProcessed);
        setStatus('Completed. Total processed: ' + totalProcessed);
        updateProgress(100);
        break;
      } else {
        // progress unknown; show spinner-like progress by increasing width slightly
        const pct = Math.min(95, (totalProcessed % 100) + 5);
        updateProgress(pct);
      }

      // small pause to avoid API bursts
      await new Promise(r => setTimeout(r, pauseMs));
    }

    startBtn.disabled = false;
    pauseBtn.disabled = true;
    cancelBtn.disabled = true;
    running = false;
    paused = false;
    cancelled = false;
    appendLog('--- FINISHED ---');
  }

  startBtn.addEventListener('click', function(){
    if (!running) {
      run();
    }
  });

  pauseBtn.addEventListener('click', function(){
    if (!running) return;
    paused = !paused;
    pauseBtn.innerText = paused ? 'Resume' : 'Pause';
    appendLog(paused ? 'Paused by user' : 'Resumed by user');
  });

  cancelBtn.addEventListener('click', function(){
    if (!running) return;
    cancelled = true;
    appendLog('Cancellation requested');
    setStatus('Cancelling...');
  });
})();
</script>
HTML;
        echo '</div>'; // container
    }

	/**
	 * Handles a single chunk for batch keyword generation
	 *
	 * @return  void
	 */
    protected function handleBatchKeywords()
    {
        $app = $this->app;
        $input = $app->input;
        $user = Factory::getUser();
        if (!$user->authorise('core.admin')) {
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $limit = max(1, (int)$input->getInt('limit', 25));
        $offset = max(0, (int)$input->getInt('offset', 0));
        $dryRun = (bool)$input->getInt('dry_run', (int)$this->params->get('dry_run', 1));
        
        if ($error = $this->validateAIConfig()) {
            echo json_encode(['error' => $error]);
            return;
        }

        $db = $this->db;
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id','title','introtext','fulltext','metakey']))
            ->from($db->quoteName('#__content'))
            ->where($db->quoteName('state') . ' >= 0') // adjust as needed
            ->order('id ASC')
            ->setLimit($limit, $offset);

        $db->setQuery($query);
        try {
            $rows = $db->loadAssocList();
        } catch (\Exception $e) {
            echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
            return;
        }

        $results = [];
        foreach ($rows as $row) {
            $articleId = (int)$row['id'];
            $title = $row['title'];
            $body = trim($row['introtext'] . "\n\n" . $row['fulltext']);
            $currentKeywords = trim($row['metakey'] ?? '');

            if (!empty($currentKeywords)) {
                $results[] = [
                    'id' => $articleId,
                    'skipped' => true,
                    'reason' => 'metakey_exists'
                ];
                continue;
            }

            // small throttling to be safe
            usleep(200000);

            $maxK = (int)$this->params->get('max_keywords', 10);
            $suggested = $this->generateKeywords($title, $body, $maxK);

            if (!$suggested) {
                $results[] = [
                    'id' => $articleId,
                    'error' => 'no_suggestion'
                ];
                continue;
            }

            if ($dryRun) {
                $results[] = [
                    'id' => $articleId,
                    'suggested_metakey' => $suggested,
                    'saved' => false
                ];
                continue;
            }

            try {
                $table = Table::getInstance('content');
                if (!$table->load($articleId)) {
                    $results[] = ['id' => $articleId, 'error' => 'load_failed'];
                    continue;
                }

                $old = $table->metakey;
                $table->metakey = $suggested;
                if (!$table->store()) {
                    $results[] = ['id' => $articleId, 'error' => 'store_failed'];
                    continue;
                }

                $logEntry = [
                    'article_id' => $articleId,
                    'old_metakey' => $old,
                    'new_metakey' => $suggested,
                    'timestamp' => time()
                ];
                $this->appendLog($logEntry);

                $results[] = [
                    'id' => $articleId,
                    'saved' => true,
                    'new_metakey' => $suggested
                ];
            } catch (\Exception $e) {
                $results[] = ['id' => $articleId, 'error' => $e->getMessage()];
            }
        }

        $nextOffset = $offset + count($rows);
        $done = count($rows) < $limit;

        echo json_encode([
            'limit' => $limit,
            'offset' => $offset,
            'processed' => count($rows),
            'results' => $results,
            'done' => $done,
            'nextOffset' => $nextOffset
        ]);
    }

	/**
	 * onContentAfterSave per-article keywords generation
	 *
	 * @param   string   $context  context
	 * @param   object   $article  article
	 * @param   boolean  $isNew    isNew
	 *
	 * @return  boolean
	 */
    public function onContentAfterSave($context, $article, $isNew)
    {
        if ($context !== 'com_content.article') {
            return true;
        }

        $user = Factory::getUser();
        if ($user->guest) {
            return true;
        }

        $params = $this->params;
        if ($this->validateAIConfig()) {
            return true;
        }

        $articleId = is_object($article) ? $article->id : ($article['id'] ?? 0);
        $title = is_object($article) ? $article->title : $article['title'];
        $intro = is_object($article) ? ($article->introtext ?? '') : ($article['introtext'] ?? '');
        $full = is_object($article) ? ($article->fulltext ?? '') : ($article['fulltext'] ?? '');
        $metaKeywords = is_object($article) ? ($article->metakey ?? '') : ($article['metakey'] ?? '');
        $metaDesc = is_object($article) ? ($article->metadesc ?? '') : ($article['metadesc'] ?? '');
        
        $body = trim($intro . "\n\n" . $full);

        if (empty(trim($title)) && empty(trim($body))) {
            return true;
        }

        $table = Table::getInstance('content');
        if (!$table->load($articleId)) {
            return true;
        }

        // AI SEO Plugin: Ensure tags are preserved during re-save
        if (!$params->get('dry_run')) {
        if (is_object($article)) {
            if (isset($article->newTags) && !empty($article->newTags)) {
                $table->newTags = $article->newTags;
            } elseif (isset($article->tags) && !empty($article->tags)) {
                $table->newTags = $article->tags;
            }
        } elseif (is_array($article) && isset($article['tags']) && !empty($article['tags'])) {
            $table->newTags = $article['tags'];
        }
    }
        $somethingChanged = false;
        
        // 1. Meta Keywords
        if ($params->get('enabled_keywords', 1) && empty(trim($table->metakey))) {
            $maxK = (int)$params->get('max_keywords', 10);
            $suggested = $this->generateKeywords($title, $body, $maxK);

            if ($suggested) {
                if ($params->get('dry_run', 1)) {
                    $this->logDryRun($articleId, 'keywords', $title, $suggested);
                } else {
                    $table->metakey = $suggested;
                    $somethingChanged = true;
                }
            }
        }

        // 2. Meta Description
        if ($params->get('enabled_meta_description', 0) && empty(trim($table->metadesc))) {
            $descLen = (int)$params->get('meta_desc_length', 160);
            $suggestedDesc = $this->generateMetaDescription($title, $body, $descLen);

            if ($suggestedDesc) {
                if ($params->get('dry_run', 1)) {
                    $this->logDryRun($articleId, 'meta_description', $title, $suggestedDesc);
                } else {
                    $table->metadesc = $suggestedDesc;
                    $somethingChanged = true;
                }
            }
        }

        // 3. Alt Text (Images)
        if ($params->get('enabled_alt', 0)) {
             // We need to parse Intro and Full text from the TABLE, to ensure we edit the latest
             $fullBody = $table->introtext . ' ' . $table->fulltext;
             
             // Simple check if there are images without alt tags to avoid expensive calls
             if (preg_match('/<img[^>]+>/', $fullBody)) {
                 $updatedIntro = $this->processImagesForAlt($table->introtext, $title, $fullBody, $params->get('dry_run', 1), $articleId);
                 $updatedFull = $this->processImagesForAlt($table->fulltext, $title, $fullBody, $params->get('dry_run', 1), $articleId);
                 
                 if (!$params->get('dry_run', 1) && ($updatedIntro !== $table->introtext || $updatedFull !== $table->fulltext)) {
                     $table->introtext = $updatedIntro;
                     $table->fulltext = $updatedFull;
                     $somethingChanged = true;
                 }
             }
        }

        // Save if needed
        if ($somethingChanged) {
            try {
                $table->store();
                $this->appendLog([
                    'article_id' => $articleId,
                    'action' => 'auto_update',
                    'timestamp' => time()
                ]);
            } catch (\Exception $e) {
                $this->app->enqueueMessage('AI SEO plugin error: ' . $e->getMessage(), 'warning');
            }
        }

        return true;
    }

	/**
	 * Process content to add missing ALT tags
	 *
	 * @param   string   $content      content
	 * @param   string   $title        title
	 * @param   string   $contextBody  contextBody
	 * @param   boolean  $dryRun       dryRun
	 * @param   integer  $articleId    articleId
	 *
	 * @return  string
	 */
    protected function processImagesForAlt($content, $title, $contextBody, $dryRun, $articleId)
    {
        if (empty($content)) return $content;

        // Find all images
        if (!preg_match_all('/<img[^>]+>/', $content, $matches)) {
            return $content;
        }

        $newContent = $content;
        foreach ($matches[0] as $imgTag) {
            // Skip if alt exists and is not empty
            if (preg_match('/alt\s*=\s*["\']([^"\']+)["\']/', $imgTag, $m)) {
                if (!empty(trim($m[1]))) continue;
            }
            
            // Extract src
             if (preg_match('/src\s*=\s*["\']([^"\']+)["\']/', $imgTag, $mSrc)) {
                 $src = $mSrc[1];
                 
                 // Generate Alt
                 $alt = $this->generateAltText($src, $title, $contextBody);
                 if ($alt) {
                     if ($dryRun) {
                         $this->logDryRun($articleId, 'alt_text', $src, $alt);
                     } else {
                         // Inject ALT. If alt="" exists, replace it. Else add it.
                         if (strpos($imgTag, 'alt=') !== false) {
                             $newTag = preg_replace('/alt\s*=\s*["\'][^"\']*["\']/', 'alt="' . htmlspecialchars($alt) . '"', $imgTag);
                         } else {
                             $newTag = str_replace('<img', '<img alt="' . htmlspecialchars($alt) . '"', $imgTag);
                         }
                         $newContent = str_replace($imgTag, $newTag, $newContent);
                     }
                 }
             }
        }
        return $newContent;
    }

	/**
	 * Log dry run actions
	 *
	 * @param   integer  $id      id
	 * @param   string   $type    type
	 * @param   string   $input   input
	 * @param   string   $output  output
	 *
	 * @return  void
	 */
    protected function logDryRun($id, $type, $input, $output) {
        $this->writeLog([
            'article_id' => $id,
            'type' => $type,
            'input_sample' => substr($input, 0, 50),
            'suggested_output' => $output,
            'timestamp' => time(),
            'status' => 'dry-run'
        ]);
        $this->app->enqueueMessage("AI SEO ({$type}): Suggestion logged (Dry Run).", 'notice');
    }

	/**
	 * Generate comma-separated keywords using AI completion
	 *
	 * @param   string   $title  title
	 * @param   string   $body   body
	 * @param   integer  $max    max
	 *
	 * @return  string|null
	 */
    protected function generateKeywords($title, $body, $max = 10)
    {
        $lang = $this->params->get('target_language', 'English');
        $customPrompt = trim($this->params->get('keywords_prompt', ''));
        $contextLen = (int)$this->params->get('context_length', 5000);

        if (!empty($customPrompt)) {
            $prompt = str_replace(['{TITLE}', '{LANGUAGE}'], [$title, $lang], $customPrompt);
        } else {
            $prompt = <<<PROMPT
Extract up to {$max} highly relevant, SEO-optimized keywords in {$lang} for the article.

Title: "{$title}"
Content: "{$this->shorten($body, $contextLen)}"

Requirements:
- Output ONLY comma-separated keywords in {$lang}.
- No numbering or bullets.
- Return ONLY the keywords.
PROMPT;
        }

        $maxTokens = (int)$this->params->get('max_tokens_meta', 1000);
        $resp = $this->callAI($prompt, $maxTokens);
        if (!$resp) return null;

        $text = preg_replace('/\s+/', ' ', trim($resp));
        $parts = array_map('trim', array_filter(explode(',', $text)));

        if (count($parts) > $max) $parts = array_slice($parts, 0, $max);
        return implode(', ', $parts);
    }


	/**
	 * Simple Groq API call
	 *
	 * @param   string  $apiKey  apiKey
	 * @param   string  $model   model
	 * @param   string  $prompt  prompt
	 *
	 * @return  string|null
	 */
    protected function callGroqAPI($apiKey, $model, $prompt, $maxTokens = 3000)
    {
        $endpoint = 'https://api.groq.com/openai/v1/chat/completions';
        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful SEO assistant. Provide concise outputs as requested.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.2,
            'max_tokens' => (int)$maxTokens
        ];

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        $timeout = (int)$this->params->get('curl_timeout', 100);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $res = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Log the request for debugging
        $debugLog = [
            'timestamp' => time(),
            'endpoint' => $endpoint,
            'model' => $model,
            'http_code' => $code,
            'error' => $err,
            'response_length' => strlen($res)
        ];

        if ($err) {
            $debugLog['curl_error'] = $err;
            $this->writeLog($debugLog);
            return null;
        }

        if ($code >= 400) {
            $debugLog['response'] = substr($res, 0, 500);
            $this->writeLog($debugLog);
            return null;
        }

        if (empty($res)) {
            $debugLog['error'] = 'Empty response';
            $this->writeLog($debugLog);
            return null;
        }

        $json = json_decode($res, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $debugLog['json_error'] = json_last_error_msg();
            $debugLog['response'] = substr($res, 0, 500);
            $this->writeLog($debugLog);
            return null;
        }

        if (!isset($json['choices'][0]['message']['content'])) {
            $debugLog['error'] = 'Invalid response structure';
            $debugLog['response'] = json_encode($json);
            $this->writeLog($debugLog);
            return null;
        }

        return trim($json['choices'][0]['message']['content']);
    }

	/**
	 * Writes log entry to file
	 *
	 * @param   array  $entry  entry
	 *
	 * @return  void
	 */
    protected function writeLog($entry)
    {
        if (!$this->params->get('log_changes', 1)) return;
        $file = JPATH_SITE . '/tmp/ai_seo_log.json';
        $existing = [];
        if (file_exists($file)) {
            $c = @file_get_contents($file);
            $existing = $c ? json_decode($c, true) : [];
        }
        $existing[] = $entry;
        @file_put_contents($file, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

	/**
	 * Appends log entry
	 *
	 * @param   array  $entry  entry
	 *
	 * @return  void
	 */
    protected function appendLog(array $entry)
    {
        $this->writeLog($entry);
    }

	/**
	 * Shorten text for prompts
	 *
	 * @param   string   $text    text
	 * @param   integer  $maxLen  maxLen
	 *
	 * @return  string
	 */
    protected function shorten($text, $maxLen = 1000)
    {
        $text = strip_tags($text);
        if (strlen($text) <= $maxLen) return $text;
        return substr($text, 0, $maxLen);
    }

	/**
	 * Handle on-demand meta generation (non-Ajax version)
	 *
	 * @return  void
	 */
    protected function handleGenerateMeta()
    {
        $user = Factory::getUser();
        if (!$user->authorise('core.edit', 'com_content')) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $input = $this->app->input;
        $articleId = (int)$input->getInt('article_id', 0);
        $title = $input->getString('title', '');
        $body = $input->getString('body', '');
        
        header('Content-Type: application/json');
        
        if ($error = $this->validateAIConfig()) {
            echo json_encode(['error' => $error]);
            return;
        }

        if (empty($title) && empty($body)) {
            echo json_encode(['error' => 'Title or body is required']);
            return;
        }

        try {
            $maxK = (int)$this->params->get('max_keywords', 10);
            
            $keywords = $this->generateKeywords($title, $body, $maxK);
            
            if (!$keywords) {
                echo json_encode(['error' => 'Failed to generate keywords. Check API key and try again.']);
                return;
            }
            
            // Generate meta description
            $description = $this->generateMetaDescription($title, $body);
            
            if (!$description) {
                $description = 'Unable to generate description';
            }

            echo json_encode([
                'success' => true,
                'keywords' => $keywords,
                'description' => $description
            ]);
        } catch (\Exception $e) {
            echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
        }
    }

	/**
	 * Handle on-demand alt text generation (non-Ajax version)
	 *
	 * @return  void
	 */
    protected function handleGenerateAlt()
    {
        $user = Factory::getUser();
        if (!$user->authorise('core.edit', 'com_content')) {
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $input = $this->app->input;
        $imageUrl = $input->getString('image_url', '');
        if ($error = $this->validateAIConfig()) {
            echo json_encode(['error' => $error]);
            return;
        }

        if (empty($imageUrl)) {
            echo json_encode(['error' => 'Image URL is required']);
            return;
        }

        $altText = $this->generateAltText($imageUrl, '', '');

        echo json_encode([
            'success' => true,
            'alt_text' => $altText
        ]);
    }

	/**
	 * Handle content suggestion (non-Ajax version)
	 *
	 * @return  void
	 */
    protected function handleSuggestContent()
    {
        $user = Factory::getUser();
        if (!$user->authorise('core.edit', 'com_content')) {
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $input = $this->app->input;
        $title = $input->getString('title', '');
        $body = $input->getString('body', '');
        
        header('Content-Type: application/json');
        
        if ($error = $this->validateAIConfig()) {
            echo json_encode(['error' => $error]);
            return;
        }

        if (empty($title) && empty($body)) {
            echo json_encode(['error' => 'Title or body is required']);
            return;
        }

        $suggestions = $this->generateContentSuggestions($title, $body);

        echo json_encode([
            'success' => true,
            'suggestions' => $suggestions
        ]);
    }

	/**
	 * Generate meta description using AI
	 *
	 * @param   string   $title      title
	 * @param   string   $body       body
	 * @param   integer  $maxLength  maxLength
	 *
	 * @return  string
	 */
    protected function generateMetaDescription($title, $body, $maxLength = 160)
    {
        $minLength = max(100, $maxLength - 20);
        $lang = $this->params->get('target_language', 'English');
        $customPrompt = trim($this->params->get('meta_description_prompt', ''));
        $contextLen = (int)$this->params->get('context_length', 5000);

        if (!empty($customPrompt)) {
            $prompt = str_replace(['{TITLE}', '{LANGUAGE}'], [$title, $lang], $customPrompt);
        } else {
            $prompt = <<<PROMPT
Write an SEO-optimized meta description in {$lang} for the webpage below.

Title: "{$title}"
Content excerpt: "{$this->shorten($body, $contextLen)}"

Requirements:
- Length: {$minLength}-{$maxLength} characters.
- Language: {$lang}.
- Return ONLY the final meta description in {$lang}. No quotes.
PROMPT;
        }

        $maxTokens = (int)$this->params->get('max_tokens_meta', 1000);
        return $this->callAI($prompt, $maxTokens);
    }


	/**
	 * Generate alt text for images
	 *
	 * @param   string  $imageUrl        imageUrl
	 * @param   string  $articleTitle    articleTitle
	 * @param   string  $articleContent  articleContent
	 *
	 * @return  string
	 */
      protected function generateAltText($imageUrl, $articleTitle = '', $articleContent = '')
    {
        $filename = basename(parse_url($imageUrl, PHP_URL_PATH));
        $filename = preg_replace('/[-_]/', ' ', pathinfo($filename, PATHINFO_FILENAME));
        $lang = $this->params->get('target_language', 'English');

        $contextSection = (!empty($articleTitle) || !empty($articleContent))
            ? "Article Title: {$articleTitle}\nContent: {$this->shorten($articleContent, 500)}"
            : "";

        $prompt = <<<PROMPT
Generate SEO-friendly alt text in {$lang} for an article image.

Image URL: {$imageUrl}
Filename context: {$filename}
Target Language: {$lang}

{$contextSection}

Requirements:
- Length: 80–125 characters.
- Describe the image in {$lang}.
- Return ONLY the alt text in {$lang}. No quotes.
PROMPT;

        return $this->callAI($prompt, 500);
    }


	/**
	 * Generate content improvement suggestions
	 *
	 * @param   string  $title  title
	 * @param   string  $body   body
	 *
	 * @return  string
	 */
    protected function generateContentSuggestions($title, $body)
    {
        $wordCount = str_word_count(strip_tags($body));
        $charCount = strlen(strip_tags($body));
        $lang = $this->params->get('target_language', 'English');

        preg_match_all('/<h([1-6])[^>]*>/', $body, $headings);
        $headingInfo = !empty($headings[1]) ? implode(',', $headings[1]) : 'none';

        preg_match_all('/<img[^>]+>/', $body, $images);
        $imageCount = count($images[0]);
        $missingAlt = 0;
        foreach ($images[0] as $img) {
            if (!preg_match('/alt\s*=\s*["\'][^"\']+["\']/', $img))
                $missingAlt++;
        }

        preg_match_all('/<a[^>]+href[^>]+>/', $body, $links);
        $linkCount = count($links[0]);

        $prompt = <<<PROMPT
You are an expert Google SEO content auditor. Review the article below and provide actionable improvement recommendations in {$lang}.

Article Title: "{$title}"
Target Language: {$lang}

Metrics:
- Word Count: {$wordCount}
- Characters: {$charCount}
- Heading Levels: {$headingInfo}
- Images: {$imageCount} (Missing alt: {$missingAlt})
- Links: {$linkCount}

Content:
"{$this->shorten($body, 2000)}"

Provide a numbered list in {$lang} covering:
1. Content depth, completeness, missing sections.
2. Heading structure improvements.
3. Keyword usage & LSI suggestions.
4. Readability & clarity.
5. Internal/external linking opportunities.
6. Missing SEO elements (FAQ, CTA).
7. E-E-A-T improvements.

Response MUST be in {$lang}.
PROMPT;

        return $this->callAI($prompt, 2000);
    }


	/**
	 * Universal AI caller - routes to correct provider
	 *
	 * @param   string   $prompt     prompt
	 * @param   integer  $maxTokens  maxTokens
	 *
	 * @return  string
	 */
    protected function callAI($prompt, $maxTokens = 2000)
    {
        $provider = $this->params->get('ai_provider', 'groq');
        $response = '';
        
        switch ($provider) {
            case 'openai':
                $response = $this->callOpenAI($prompt, $maxTokens);
                break;
            case 'claude':
                $response = $this->callClaude($prompt, $maxTokens);
                break;
            case 'gemini':
                $response = $this->callGemini($prompt, $maxTokens);
                break;
            case 'groq':
            default:
                $apiKey = trim($this->params->get('groq_api_key', ''));
                $model = $this->params->get('groq_model', 'llama-3.3-70b-versatile');
                if ($model === 'auto') {
                    $model = 'llama-3.3-70b-versatile';
                }
                $groqMax = (int)$this->params->get('groq_max_tokens', 3000);
                $response = $this->callGroqAPI($apiKey, $model, $prompt, $groqMax);
                break;
        }

        return $this->cleanAIResponse($response);
    }

	/**
	 * Clean AI response by removing markdown artifacts
	 *
	 * @param   string  $text  text
	 *
	 * @return  string
	 */
    private function cleanAIResponse($text)
    {
        if (empty($text)) return '';

        // Remove opening markdown code blocks (e.g., ```html, ```xml, ```json, ```)
        $text = preg_replace('/^```[a-z]*\s*/i', '', trim($text));
        
        // Remove closing markdown code blocks
        $text = preg_replace('/\s*```$/', '', $text);

        return trim($text);
    }

	/**
	 * Call OpenAI GPT-4 API
	 *
	 * @param   string   $prompt     prompt
	 * @param   integer  $maxTokens  maxTokens
	 *
	 * @return  string
	 */
    protected function callOpenAI($prompt, $maxTokens = 2000)
    {
        $apiKey = trim($this->params->get('openai_api_key', ''));
        $model = $this->params->get('openai_model', 'gpt-4o');
        if ($model === 'auto') {
            $model = 'gpt-4o';
        }
        
        if (empty($apiKey)) {
            return 'OpenAI API key not configured. Please add your API key in plugin settings.';
        }
        
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => $maxTokens,
            'temperature' => 0.7
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        $timeout = (int)$this->params->get('curl_timeout', 100);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return 'OpenAI API connection error: ' . $error;
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['error']['message'] ?? 'Unknown error';
            return 'OpenAI API error (' . $httpCode . '): ' . $errorMsg;
        }
        
        $result = json_decode($response, true);
        return $result['choices'][0]['message']['content'] ?? 'No response from OpenAI';
    }

	/**
	 * Call Anthropic Claude API
	 *
	 * @param   string   $prompt     prompt
	 * @param   integer  $maxTokens  maxTokens
	 *
	 * @return  string
	 */
    protected function callClaude($prompt, $maxTokens = 2000)
    {
        $apiKey = trim($this->params->get('claude_api_key', ''));
        $model = $this->params->get('claude_model', 'claude-3-5-sonnet-latest');
        if ($model === 'auto') {
            $model = 'claude-3-5-sonnet-latest';
        }
        
        if (empty($apiKey)) {
            return 'Claude API key not configured. Please add your API key in plugin settings.';
        }
        
        $url = 'https://api.anthropic.com/v1/messages';
        
        $data = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01'
        ]);
        $timeout = (int)$this->params->get('curl_timeout', 100);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return 'Claude API connection error: ' . $error;
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['error']['message'] ?? 'Unknown error';
            return 'Claude API error (' . $httpCode . '): ' . $errorMsg;
        }
        
        $result = json_decode($response, true);
        return $result['content'][0]['text'] ?? 'No response from Claude';
    }

	/**
	 * Call Google Gemini API
	 *
	 * @param   string   $prompt     prompt
	 * @param   integer  $maxTokens  maxTokens
	 *
	 * @return  string
	 */
    protected function callGemini($prompt, $maxTokens = 2000)
    {
        $apiKey = trim($this->params->get('gemini_api_key', ''));
        $model = trim($this->params->get('gemini_model', 'gemini-2.5-flash'));
        if ($model === 'auto') {
            $model = 'gemini-2.5-flash';
        }
        
        if (empty($apiKey)) {
            return Text::_('PLG_CONTENT_AI_SEO_ERROR_API_KEY_NOT_CONFIGURED', 'Gemini');
        }

        // Try v1beta as it generally has better support for latest aliases
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        
        $data = [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'maxOutputTokens' => (int)$maxTokens,
                'temperature'     => 0.7
            ]
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $timeout = (int)$this->params->get('curl_timeout', 100);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) return 'Gemini API connection error: ' . $error;
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['error']['message'] ?? 'Unknown error';
            
            if ($httpCode === 404) {
                return "Gemini API error (404): The model '{$model}' was not found on the v1beta endpoint. Please try a different model (e.g., gemini-1.5-pro-002) in the plugin settings. Detailed error: " . $errorMsg;
            }
            
            return 'Gemini API error (' . $httpCode . '): ' . $errorMsg;
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return $result['candidates'][0]['content']['parts'][0]['text'];
        }

        return 'No response from Gemini. Status Code: ' . $httpCode;
    }
}
