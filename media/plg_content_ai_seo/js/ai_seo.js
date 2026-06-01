/**
 * AI Content & SEO Assist
 * Shows context-specific buttons based on the active editor tab
 */
document.addEventListener('DOMContentLoaded', function () {
    const options = Joomla.getOptions('ai_seo');

    // Helper to make each request title unique
    function modifyTitleForRequest(originalTitle) {
        return originalTitle;
    }
    if (!options || !options.ajaxUrl) return;

    const ajaxUrl = options.ajaxUrl;
    const labels = options.labels || {};

    // Helper to get localized labels with fallback
    function _t(key, fallback) {
        return labels[key] || fallback;
    }


    // Tab selectors for Joomla article editor
    const TAB_SELECTORS = {
        content: '#content, [data-content], a[href="#attrib-content"]',
        images: '#images, [data-images], a[href="#attrib-images"]',
        publishing: '#publishing, [data-publishing], a[href="#attrib-publishing"]',
        schema: '#schema, [data-schema], a[href="#attrib-schema"]'
    };

    // Create button containers for each tab section
    const contentButtons = createButtonContainer('content');
    const imagesButtons = createButtonContainer('images');
    const publishingButtons = createButtonContainer('publishing');
    const schemaButtons = createButtonContainer('schema');

    // Initialize buttons
    initializeButtons();

    function createButtonContainer(tabType) {
        const container = document.createElement('div');
        container.className = 'ai-seo-buttons-container';
        container.id = `ai-seo-${tabType}`;
        container.style.display = 'none';

        let buttonHtml = `<h4><span class="ai-icon">✨</span> ${_t('title', 'AI SEO Assistant')}</h4><div class="ai-seo-button-group">`;


        switch (tabType) {
            case 'content':
                buttonHtml += `
                    <button type="button" class="ai-seo-btn ai-seo-btn-primary" id="ai-suggest-content" title="${_t('analyze_content', 'Analyze Content')}">
                        <span class="ai-icon">📊</span> ${_t('analyze_content', 'Analyze Content')}
                    </button>
                    <button type="button" class="ai-seo-btn ai-seo-btn-success" id="ai-optimize-content" title="${_t('optimize_content', 'Optimize Content')}">
                        <span class="ai-icon">🚀</span> ${_t('optimize_content', 'Optimize Content')}
                    </button>
                    <button type="button" class="ai-seo-btn ai-seo-btn-info" id="ai-generate-article" title="${_t('generate_article', 'Generate Article')}">
                        <span class="ai-icon">✍️</span> ${_t('generate_article', 'Generate Article')}
                    </button>
                    <button type="button" class="ai-seo-btn ai-seo-btn-outline" id="ai-optimize-title" title="${_t('optimize_title', 'Optimize Title')}">
                        <span class="ai-icon">🎯</span> ${_t('optimize_title', 'Optimize Title')}
                    </button>
                    
                    <div style="width: 100%; border-top: 1px solid rgba(0,0,0,0.08); margin: 16px 0 12px 0;"></div>
                    
                    <div style="width: 100%;">
                        <label style="display:block; margin-bottom:8px; font-weight:600; color:#475569; font-size:13px;">${_t('custom_prompt', 'Or ask AI to generate custom content:')}</label>
                        <div style="display:flex; gap:10px; align-items:center;">
                            <input type="text" id="ai-custom-prompt" class="form-control" placeholder="${_t('placeholder', 'E.g. Write a clear introduction...')}" style="margin-bottom: 0; flex-grow: 1;">
                            <button type="button" class="ai-seo-btn ai-seo-btn-primary" id="ai-generate-custom" title="${_t('generate', 'Generate')}" style="margin-top:0;">
                                <span class="ai-icon">✨</span> ${_t('generate', 'Generate')}
                            </button>
                        </div>
                    </div>
                `;
                break;
            case 'images':
                buttonHtml += `
                    <button type="button" class="ai-seo-btn ai-seo-btn-secondary" id="ai-generate-alt" title="${_t('generate_alt', 'Generate Image Alt Text')}">
                        <span class="ai-icon">🖼️</span> ${_t('generate_alt', 'Generate Image Alt Text')}
                    </button>
                    <button type="button" class="ai-seo-btn ai-seo-btn-outline" id="ai-scan-images" title="${_t('scan_images', 'Scan Images in Content')}">
                        <span class="ai-icon">🔍</span> ${_t('scan_images', 'Scan Images in Content')}
                    </button>
                `;
                break;
            case 'publishing':
                buttonHtml += `
                    <button type="button" class="ai-seo-btn ai-seo-btn-success" id="ai-generate-meta" title="${_t('generate_meta', 'Generate Meta Data')}">
                        <span class="ai-icon">🏷️</span> ${_t('generate_meta', 'Generate Meta Data')}
                    </button>
                `;
                break;
            case 'schema':
                buttonHtml += `
                    <button type="button" class="ai-seo-btn ai-seo-btn-primary" id="ai-autofill-schema" title="${_t('autofill_schema', 'Auto-Fill Schema Data')}">
                        <span class="ai-icon">📋</span> ${_t('autofill_schema', 'Auto-Fill Schema Data')}
                    </button>
                `;
                break;
        }

        buttonHtml += '</div><div class="ai-seo-results" id="ai-seo-results-' + tabType + '"></div>';
        container.innerHTML = buttonHtml;

        return container;
    }

    function initializeButtons() {
        // Try to inject into tab panels
        injectIntoTabPanel('content', contentButtons);
        injectIntoTabPanel('images', imagesButtons);
        injectIntoTabPanel('publishing', publishingButtons);
        injectIntoTabPanel('schema', schemaButtons);

        // Setup event listeners
        setupContentButton();
        setupOptimizeButton();
        setupGenerateArticleButton();
        setupImagesButtons();
        setupPublishingButton();
        setupSchemaButton();
        setupCustomGenButton();
        setupOptimizeTitleButton();

        // Setup tab change detection
        setupTabChangeDetection();
    }

    function injectIntoTabPanel(tabType, buttonContainer) {
        // Joomla 4 uses joomla-tab with panels
        let targetPanel = null;

        // Try joomla-tab-element first (Joomla 4)
        const joomlaTab = document.querySelector('joomla-tab');
        if (joomlaTab) {
            const panels = joomlaTab.querySelectorAll('joomla-tab-element');
            panels.forEach(panel => {
                const panelId = panel.getAttribute('id') || '';
                const panelName = panel.getAttribute('name') || '';
                if (panelId.toLowerCase().includes(tabType) || panelName.toLowerCase().includes(tabType)) {
                    targetPanel = panel;
                }
            });
        }

        // Try standard tab-content for Joomla 3
        if (!targetPanel) {
            const tabContent = document.querySelector('.tab-content');
            if (tabContent) {
                const panes = tabContent.querySelectorAll('.tab-pane');
                panes.forEach(pane => {
                    const paneId = pane.getAttribute('id') || '';
                    if (paneId.toLowerCase().includes(tabType)) {
                        targetPanel = pane;
                    }
                });
            }
        }

        // Try finding by fieldset legend
        if (!targetPanel) {
            const fieldsets = document.querySelectorAll('fieldset');
            fieldsets.forEach(fs => {
                const legend = fs.querySelector('legend');
                if (legend && legend.textContent.toLowerCase().includes(tabType)) {
                    targetPanel = fs;
                }
            });
        }

        if (targetPanel) {
            targetPanel.insertBefore(buttonContainer, targetPanel.firstChild);
            buttonContainer.style.display = 'block';
        } else {
            // Fallback: create floating button container
            createFloatingContainer(tabType, buttonContainer);
        }
    }

    function createFloatingContainer(tabType, buttonContainer) {
        // If we can't find specific panels, create a single container with all buttons
        // and use tab detection to show/hide them
        let mainContainer = document.getElementById('ai-seo-main-container');
        if (!mainContainer) {
            mainContainer = document.createElement('div');
            mainContainer.id = 'ai-seo-main-container';
            mainContainer.className = 'ai-seo-main-container';

            // Find the form and insert after title
            const titleField = document.querySelector('[name="title"]');
            if (titleField && titleField.closest('.control-group')) {
                titleField.closest('.control-group').parentNode.insertBefore(
                    mainContainer,
                    titleField.closest('.control-group').nextSibling
                );
            } else {
                const form = document.querySelector('form');
                if (form) form.insertBefore(mainContainer, form.firstChild);
            }
        }

        buttonContainer.classList.add('ai-seo-tab-buttons');
        buttonContainer.dataset.tabType = tabType;
        mainContainer.appendChild(buttonContainer);
    }

    function setupTabChangeDetection() {
        // Observe tab clicks
        const tabLinks = document.querySelectorAll('[data-bs-toggle="tab"], [data-toggle="tab"], .nav-tabs a, joomla-tab-element');
        tabLinks.forEach(link => {
            link.addEventListener('click', () => {
                setTimeout(updateVisibleButtons, 100);
            });
        });

        // Also observe joomla-tab changes
        const joomlaTab = document.querySelector('joomla-tab');
        if (joomlaTab) {
            const observer = new MutationObserver(updateVisibleButtons);
            observer.observe(joomlaTab, { attributes: true, subtree: true });
        }

        // Initial visibility update
        updateVisibleButtons();
    }

    function updateVisibleButtons() {
        const floatingButtons = document.querySelectorAll('.ai-seo-tab-buttons');
        if (floatingButtons.length === 0) return;

        const activeTab = getCurrentActiveTab();

        floatingButtons.forEach(btn => {
            const tabType = btn.dataset.tabType;
            if (activeTab === tabType || activeTab === 'all') {
                btn.style.display = 'block';
            } else {
                btn.style.display = 'none';
            }
        });
    }

    function getCurrentActiveTab() {
        // Check for active joomla-tab-element
        const activeJoomlaTab = document.querySelector('joomla-tab-element[active]');
        if (activeJoomlaTab) {
            const id = activeJoomlaTab.getAttribute('id') || '';
            const name = activeJoomlaTab.getAttribute('name') || '';
            if (id.includes('content') || name.toLowerCase().includes('content')) return 'content';
            if (id.includes('images') || name.toLowerCase().includes('images')) return 'images';
            if (id.includes('publishing') || name.toLowerCase().includes('publishing')) return 'publishing';
        }

        // Check for active bootstrap tab pane
        const activePane = document.querySelector('.tab-pane.active');
        if (activePane) {
            const id = activePane.getAttribute('id') || '';
            if (id.includes('content')) return 'content';
            if (id.includes('images')) return 'images';
            if (id.includes('publishing')) return 'publishing';
        }

        return 'content'; // default to content
    }

    function setupContentButton() {
        const btn = document.getElementById('ai-suggest-content');
        if (!btn) return;

        btn.addEventListener('click', function () {
            const title = document.querySelector('[name="title"]')?.value || '';
            const body = getEditorContent();
            const resultsDiv = document.getElementById('ai-seo-results-content');

            if (!title && !body) {
                showResult(resultsDiv, _t('err_no_content', 'Please enter a title or content first'), 'error');
                return;
            }

            setLoading(btn, true);

            const modifiedTitle = modifyTitleForRequest(title);
            fetch(ajaxUrl + '&ai_seo_task=suggestContent', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ title: modifiedTitle, body })
            })
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        showResult(resultsDiv, _t('err_prefix', 'Error: ') + data.error, 'error');
                    } else {
                        showSeoAnalysis(resultsDiv, data.suggestions);
                    }
                })
                .catch(err => showResult(resultsDiv, _t('fetch_err_prefix', 'Request failed: ') + err, 'error'))
                .finally(() => setLoading(btn, false));
        });
    }

    function setupOptimizeButton() {
        const btn = document.getElementById('ai-optimize-content');
        if (!btn) return;

        btn.addEventListener('click', function () {
            const title = document.querySelector('[name="title"]')?.value || '';
            const body = getEditorContent();
            const resultsDiv = document.getElementById('ai-seo-results-content');

            if (!body) {
                showResult(resultsDiv, _t('err_no_content', 'Please enter some content first'), 'error');
                return;
            }

            if (!confirm(_t('replace_confirm', '⚠️ This will rewrite your article content using AI. \n\nWe recommend expecting the result before saving. \n\nContinue?'))) {
                return;
            }

            setLoading(btn, true);
            showResult(resultsDiv, `${_t('rewriting_content', '🔄 Rewriting content for better SEO...')} ${_t('may_take_time', 'This may take up to 30 seconds.')}`, 'info');

            const modifiedTitle = modifyTitleForRequest(title);
            fetch(ajaxUrl + '&ai_seo_task=rewriteContent', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ title: modifiedTitle, body })
            })
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        showResult(resultsDiv, _t('err_prefix', 'Error: ') + data.error, 'error');
                    } else if (data.success) {
                        showRewriteResult(resultsDiv, data.new_content, _t('opt_context_rewrite', 'Rewrite & Optimize Article'));
                    }
                })
                .catch(err => showResult(resultsDiv, _t('fetch_err_prefix', 'Request failed: ') + err, 'error'))
                .finally(() => setLoading(btn, false));
        });
    }

    function setupGenerateArticleButton() {
        const btn = document.getElementById('ai-generate-article');
        if (!btn) return;

        btn.addEventListener('click', function () {
            const title = document.querySelector('[name="jform[title]"]')?.value ||
                document.querySelector('#jform_title')?.value ||
                document.querySelector('[name="title"]')?.value || '';
            const resultsDiv = document.getElementById('ai-seo-results-content');

            if (!title || title.trim() === '') {
                showResult(resultsDiv, _t('err_no_content', 'Please enter an article title first'), 'error');
                return;
            }

            const currentBody = getEditorContent();
            if (currentBody && currentBody.trim() !== '') {
                if (!confirm(_t('replace_confirm', '⚠️ This will replace your current content with AI-generated content. \n\nContinue?'))) {
                    return;
                }
            }

            setLoading(btn, true);
            showResult(resultsDiv, `${_t('gen_article', '🤖 Generating article content...')} ${_t('may_take_time', 'This may take up to 30 seconds.')}`, 'info');

            const modifiedTitle = modifyTitleForRequest(title);
            fetch(ajaxUrl + '&ai_seo_task=generateArticle', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ title: modifiedTitle })
            })
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        showResult(resultsDiv, _t('err_prefix', 'Error: ') + data.error, 'error');
                    } else if (data.success) {
                        showRewriteResult(resultsDiv, data.content, _t('gen_article_context', 'Generate Fully Unique Article for: ') + title);
                    }
                })
                .catch(err => showResult(resultsDiv, _t('fetch_err_prefix', 'Request failed: ') + err, 'error'))
                .finally(() => setLoading(btn, false));
        });
    }

    function setupImagesButtons() {
        // Main button: Auto-scan and generate alt for images
        const altBtn = document.getElementById('ai-generate-alt');
        if (altBtn) {
            altBtn.addEventListener('click', async function () {
                const resultsDiv = document.getElementById('ai-seo-results-images');

                // Collect all images from different sources
                const allImages = collectAllImages();

                if (allImages.length === 0) {
                    showResult(resultsDiv, _t('no_images', 'No images found in article.'), 'info');
                    return;
                }

                // Find images without alt text
                const imagesWithoutAlt = allImages.filter(img => !img.alt || img.alt.trim() === '');

                if (imagesWithoutAlt.length === 0) {
                    showResult(resultsDiv, _t('all_images_alt', '✅ All images already have alt text!'), 'success');
                    return;
                }

                setLoading(altBtn, true);
                let genMsg = _t('gen_alt_multiple', '🔄 Generating alt text for %s image(s)...').replace('%s', imagesWithoutAlt.length);
                showResult(resultsDiv, genMsg, 'info');

                // Get article context for better alt text
                const articleTitle = document.querySelector('[name="title"]')?.value || '';
                const articleBody = getEditorContent();

                let results = [];
                for (let i = 0; i < imagesWithoutAlt.length; i++) {
                    const imgInfo = imagesWithoutAlt[i];

                    try {
                        const modifiedTitle = modifyTitleForRequest(articleTitle);
                        const response = await fetch(ajaxUrl + '&ai_seo_task=generateAlt', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({
                                image_url: imgInfo.src,
                                title: modifiedTitle,
                                body: articleBody
                            })
                        });
                        const data = await response.json();

                        if (data.success && data.alt_text) {
                            // Update the appropriate field based on image type
                            updateImageAlt(imgInfo.type, imgInfo.index, data.alt_text);
                            results.push({ src: imgInfo.src, alt: data.alt_text, type: imgInfo.type, success: true });
                        } else {
                            results.push({ src: imgInfo.src, error: data.error || 'Failed', type: imgInfo.type, success: false });
                        }
                    } catch (err) {
                        results.push({ src: imgInfo.src, error: err.message, type: imgInfo.type, success: false });
                    }
                }

                // Show results
                let resultHtml = '<div class="ai-seo-image-list">';
                let successCount = results.filter(r => r.success).length;
                resultHtml += `<h5>${_t('gen_alt_for', '✅ Generated alt text for %s/%s images:').replace('%s', successCount).replace('%s', imagesWithoutAlt.length)}</h5><ul>`;
                results.forEach((r, i) => {
                    const typeLabel = r.type === 'intro' ? _t('type_intro', '(Intro Image)') : r.type === 'full' ? _t('type_full', '(Full Article)') : _t('type_content', '(Content)');
                    if (r.success) {
                        resultHtml += `<li class="has-alt"><strong>${typeLabel}</strong> ${r.src.substring(0, 35)}...<br><span class="alt-status">✅ ${_t('alt_label', 'Alt:')} "${r.alt}"</span></li>`;
                    } else {
                        resultHtml += `<li class="no-alt"><strong>${typeLabel}</strong> ${r.src.substring(0, 35)}...<br><span class="alt-status">❌ ${_t('err_prefix', 'Error: ')} ${r.error}</span></li>`;
                    }
                });
                resultHtml += '</ul></div>';

                showResult(resultsDiv, resultHtml, 'success', _t('alt_gen_heading', 'Alt Text Generated & Applied'));
                showToast(_t('alt_gen_success_toast', '✓ Generated alt text for %s images!').replace('%s', successCount));

                setLoading(altBtn, false);
            });
        }

        // Scan button: Just show current image status
        const scanBtn = document.getElementById('ai-scan-images');
        if (scanBtn) {
            scanBtn.addEventListener('click', function () {
                const resultsDiv = document.getElementById('ai-seo-results-images');
                const allImages = collectAllImages();

                if (allImages.length === 0) {
                    showResult(resultsDiv, _t('no_images', 'No images found in article.'), 'info');
                    return;
                }

                const withAlt = allImages.filter(img => img.alt?.trim());
                const withoutAlt = allImages.length - withAlt.length;

                let imagesFoundMsg = _t('found_images_msg', 'Found %s image(s) (%s missing alt text):').replace('%s', allImages.length).replace('%s', withoutAlt);
                let imageList = `<div class="ai-seo-image-list"><h5>${imagesFoundMsg}</h5><ul>`;
                allImages.forEach((img, i) => {
                    const typeLabel = img.type === 'intro' ? _t('type_intro_banner', '🖼️ Intro Image') : img.type === 'full' ? _t('type_full_banner', '📄 Full Article Image') : _t('type_content_banner', '📝 Content Image');
                    const hasAlt = img.alt?.trim();
                    const statusClass = hasAlt ? 'has-alt' : 'no-alt';
                    const altDisplay = hasAlt ? img.alt.substring(0, 50) + (img.alt.length > 50 ? '...' : '') : _t('no_alt_text_em', '<em>No alt text</em>');
                    imageList += `
                        <li class="${statusClass}">
                            <strong>${typeLabel}:</strong> ${img.src.substring(0, 40)}${img.src.length > 40 ? '...' : ''}<br>
                            <span class="alt-status">${hasAlt ? '✅ ' : '⚠️ '}${_t('alt_label', 'Alt:')} ${altDisplay}</span>
                        </li>
                    `;
                });
                imageList += '</ul></div>';

                if (withoutAlt > 0) {
                    imageList += `<p style="margin-top:10px"><strong>${_t('scan_prompt', 'Click "Generate Image Alt Text" to auto-generate alt text for %s image(s).').replace('%s', withoutAlt)}</strong></p>`;
                }

                showResult(resultsDiv, imageList, 'info', _t('scan_results_heading', 'Image Scan Results'));
            });
        }
    }

    // Collect all images from Joomla article (intro image, full image, content images)
    function collectAllImages() {
        const images = [];

        // 1. Check Intro Image field
        const introImageField = document.querySelector('#jform_images_image_intro, [name="jform[images][image_intro]"]');
        const introAltField = document.querySelector('#jform_images_image_intro_alt, [name="jform[images][image_intro_alt]"]');
        if (introImageField && introImageField.value && introImageField.value.trim()) {
            images.push({
                type: 'intro',
                src: introImageField.value,
                alt: introAltField ? introAltField.value : '',
                index: 0
            });
        }

        // 2. Check Full Article Image field
        const fullImageField = document.querySelector('#jform_images_image_fulltext, [name="jform[images][image_fulltext]"]');
        const fullAltField = document.querySelector('#jform_images_image_fulltext_alt, [name="jform[images][image_fulltext_alt]"]');
        if (fullImageField && fullImageField.value && fullImageField.value.trim()) {
            images.push({
                type: 'full',
                src: fullImageField.value,
                alt: fullAltField ? fullAltField.value : '',
                index: 0
            });
        }

        // 3. Check article content for inline images
        const body = getEditorContent();
        if (body) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = body;
            const contentImages = tempDiv.querySelectorAll('img');
            contentImages.forEach((img, i) => {
                images.push({
                    type: 'content',
                    src: img.getAttribute('src') || '',
                    alt: img.getAttribute('alt') || '',
                    index: i,
                    element: img
                });
            });
        }

        // Check for Intro and Full Text images in Joomla
        const introImg = document.querySelector('#jform_images_image_intro_preview img, #jform_images_image_intro');
        const fullImg = document.querySelector('#jform_images_image_full_preview img, #jform_images_image_full');

        if (introImg) {
            const src = introImg.src || introImg.value;
            if (src && src.length > 5) {
                images.push({
                    src: src,
                    alt: document.getElementById('jform_images_image_intro_alt')?.value || '',
                    source: _t('intro_image', 'Intro Image')
                });
            }
        }

        if (fullImg) {
            const src = fullImg.src || fullImg.value;
            if (src && src.length > 5) {
                images.push({
                    src: src,
                    alt: document.getElementById('jform_images_image_full_alt')?.value || '',
                    source: _t('full_image', 'Full Article Image')
                });
            }
        }

        return images;
    }

    // Update alt text for the appropriate image type
    function updateImageAlt(type, index, altText) {
        switch (type) {
            case 'intro':
                const introAltField = document.querySelector('#jform_images_image_intro_alt, [name="jform[images][image_intro_alt]"]');
                if (introAltField) {
                    introAltField.value = altText;
                    introAltField.dispatchEvent(new Event('change', { bubbles: true }));
                }
                break;
            case 'full':
                const fullAltField = document.querySelector('#jform_images_image_fulltext_alt, [name="jform[images][image_fulltext_alt]"]');
                if (fullAltField) {
                    fullAltField.value = altText;
                    fullAltField.dispatchEvent(new Event('change', { bubbles: true }));
                }
                break;
            case 'content':
                // For content images, we need to update the editor
                const body = getEditorContent();
                if (body) {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = body;
                    const contentImages = tempDiv.querySelectorAll('img');
                    if (contentImages[index]) {
                        contentImages[index].setAttribute('alt', altText);
                        updateEditorContent(tempDiv.innerHTML);
                    }
                }
                break;
        }
    }

    // Helper function to update editor content
    function updateEditorContent(newContent) {
        // Try TinyMCE first
        if (typeof tinymce !== 'undefined') {
            const editor = tinymce.get('jform_articletext') || tinymce.activeEditor;
            if (editor) {
                editor.setContent(newContent);
                return;
            }
        }

        // Fallback to textarea
        const textarea = document.querySelector('[name="jform[articletext]"]') ||
            document.querySelector('#jform_articletext');
        if (textarea) {
            textarea.value = newContent;
            textarea.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    function setupPublishingButton() {
        const btn = document.getElementById('ai-generate-meta');
        if (!btn) return;

        btn.addEventListener('click', function () {
            const title = document.querySelector('[name="title"]')?.value || '';
            const body = getEditorContent();
            const resultsDiv = document.getElementById('ai-seo-results-publishing');

            if (!title && !body) {
                showResult(resultsDiv, _t('err_no_content', 'Please enter a title or content first'), 'error');
                return;
            }

            setLoading(btn, true);

            fetch(ajaxUrl + '&ai_seo_task=generateMeta', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ title, body })
            })
                .then(r => {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.text();
                })
                .then(text => {
                    if (!text) throw new Error('Empty response');
                    return JSON.parse(text);
                })
                .then(data => {
                    if (data.error) {
                        showResult(resultsDiv, _t('err_prefix', 'Error: ') + data.error, 'error');
                    } else if (data.success) {
                        showMetaResult(resultsDiv, data);
                    }
                })
                .catch(err => showResult(resultsDiv, _t('fetch_err_prefix', 'Request failed: ') + err.message, 'error'))
                .finally(() => setLoading(btn, false));
        });
    }

    function setupSchemaButton() {
        const btn = document.getElementById('ai-autofill-schema');
        if (!btn) return;

        btn.addEventListener('click', function () {
            const title = document.querySelector('[name="jform[title]"]')?.value ||
                document.querySelector('#jform_title')?.value ||
                document.querySelector('[name="title"]')?.value || '';
            const body = getEditorContent();
            const resultsDiv = document.getElementById('ai-seo-results-schema');


            // Check schema type - MUST be BlogPosting
            const schemaTypeField = document.querySelector('[name="jform[attribs][schemaType]"]') ||
                document.querySelector('#jform_attribs_schemaType') ||
                document.querySelector('select[id*="schemaType"]');

            let schemaType = schemaTypeField?.value || '';

            // If no schema type or "None", show error
            if (!schemaType || schemaType === '' || schemaType === 'None') {
                showResult(resultsDiv, _t('schema_type_none_err', 'Please select a Schema Type first, then click this button again.'), 'error');
                return;
            }

            if (!title && !body) {
                showResult(resultsDiv, _t('err_no_content', 'Please enter an article title or content first'), 'error');
                return;
            }

            setLoading(btn, true);
            showResult(resultsDiv, `${_t('gen_schema', '🤖 Generating schema data...').replace('${schemaType}', schemaType)}`, 'info');

            const modifiedTitle = modifyTitleForRequest(title || 'Untitled');
            fetch(ajaxUrl + '&ai_seo_task=generateSchemaData', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ title: modifiedTitle, body: body || '', schema_type: schemaType })
            })
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        showResult(resultsDiv, _t('err_prefix', 'Error: ') + data.error, 'error');
                    } else if (data.success && data.schema) {
                        // Enhanced field detection - Dynamic based on type
                        const headlineSelectors = [
                            // Exact selectors for known schemas (Prioritized)
                            `input[name="jform[schema][${schemaType}][headline]"]`, // Generic dynamic
                            `input[name="jform[schema][${schemaType}][name]"]`,     // Generic dynamic
                            `input[name="jform[schema][BlogPosting][headline]"]`,
                            `input[name="jform[schema][Article][headline]"]`,
                            `input[name="jform[schema][Event][name]"]`,

                            // Old selectors (Fallbacks)
                            `input[name="jform[attribs][schema][${schemaType}][headline]"]`,
                            `textarea[name="jform[attribs][schema][${schemaType}][headline]"]`,
                            `input[name="jform[attribs][schema][${schemaType}][name]"]`,
                            `textarea[name="jform[attribs][schema][${schemaType}][name]"]`,
                            `input#jform_attribs_schema_${schemaType}_headline`,
                            `textarea#jform_attribs_schema_${schemaType}_headline`,
                            `input#jform_attribs_schema_${schemaType}_name`,
                            `textarea#jform_attribs_schema_${schemaType}_name`,
                            'input.headline',
                            'input.name'
                        ];

                        const descSelectors = [
                            // Exact selectors for known schemas (Prioritized)
                            `textarea[name="jform[schema][${schemaType}][description]"]`, // Generic dynamic
                            `input[name="jform[schema][${schemaType}][description]"]`,    // Generic dynamic
                            `input[name="jform[schema][BlogPosting][description]"]`,
                            `input[name="jform[schema][Article][description]"]`,
                            `input[name="jform[schema][Event][description]"]`,

                            // Old selectors (Fallbacks)
                            `textarea[name="jform[attribs][schema][${schemaType}][description]"]`,
                            `input[name="jform[attribs][schema][${schemaType}][description]"]`,
                            `textarea#jform_attribs_schema_${schemaType}_description`,
                            `input#jform_attribs_schema_${schemaType}_description`,
                            'textarea.description'
                        ];

                        const authorTypeSelectors = [
                            'select[name="jform[schema][BlogPosting][author][@type]"]',
                            'input[name="jform[schema][BlogPosting][author][@type]"]',
                            '#jform_schema__BlogPosting__author___type',
                            'select[id*="BlogPosting"][id*="author"][id*="type"]',
                            'input[id*="BlogPosting"][id*="author"][id*="type"]'
                        ];

                        const authorNameSelectors = [
                            'input[name="jform[schema][BlogPosting][author][name]"]',
                            '#jform_schema__BlogPosting__author__name',
                            'input[id*="BlogPosting"][id*="author"][id*="name"]'
                        ];

                        const authorUrlSelectors = [
                            'input[name="jform[schema][BlogPosting][author][url]"]',
                            '#jform_schema__BlogPosting__author__url',
                            'input[id*="BlogPosting"][id*="author"][id*="url"]'
                        ];

                        const authorLogoSelectors = [
                            'input[name="jform[schema][BlogPosting][author][logo][url]"]',
                            '#jform_schema__BlogPosting__author__logo__url',
                            'input[id*="BlogPosting"][id*="author"][id*="logo"]'
                        ];

                        const authorEmailSelectors = [
                            'input[name="jform[schema][BlogPosting][author][email]"]',
                            '#jform_schema__BlogPosting__author__email',
                            'input[id*="BlogPosting"][id*="author"][id*="email"]'
                        ];

                        let headlineField = null;
                        let descField = null;
                        let authorTypeField = null;
                        let authorNameField = null;
                        let authorUrlField = null;
                        let authorLogoField = null;
                        let authorEmailField = null;

                        // Find headline/name field
                        for (const selector of headlineSelectors) {
                            headlineField = document.querySelector(selector);
                            if (headlineField) {
                                break;
                            }
                        }

                        // Find description field
                        for (const selector of descSelectors) {
                            descField = document.querySelector(selector);
                            if (descField) {
                                break;
                            }
                        }

                        // Populate fields
                        if (headlineField) {
                            // API returns 'headline' or 'name' based on type, but let's handle generic response
                            headlineField.value = data.schema.headline || data.schema.name || '';
                            headlineField.dispatchEvent(new Event('change', { bubbles: true }));
                        } else {
                        }

                        if (descField) {
                            descField.value = data.schema.description || '';
                            descField.dispatchEvent(new Event('change', { bubbles: true }));
                        }

                        // Auto-fill author fields ONLY for BlogPosting
                        if (schemaType === 'BlogPosting') {
                            // Find Author fields
                            for (const selector of authorTypeSelectors) {
                                authorTypeField = document.querySelector(selector);
                                if (authorTypeField) break;
                            }
                            for (const selector of authorNameSelectors) {
                                authorNameField = document.querySelector(selector);
                                if (authorNameField) break;
                            }
                            for (const selector of authorUrlSelectors) {
                                authorUrlField = document.querySelector(selector);
                                if (authorUrlField) break;
                            }
                            for (const selector of authorLogoSelectors) {
                                authorLogoField = document.querySelector(selector);
                                if (authorLogoField) break;
                            }
                            for (const selector of authorEmailSelectors) {
                                authorEmailField = document.querySelector(selector);
                                if (authorEmailField) break;
                            }

                            // Populate Author fields
                            if (authorTypeField) {
                                authorTypeField.value = 'organization';
                                authorTypeField.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                            if (authorNameField) {
                                authorNameField.value = 'Data Protection Education';
                                authorNameField.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                            if (authorUrlField) {
                                authorUrlField.value = 'https://dataprotection.education/';
                                authorUrlField.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                            if (authorLogoField) {
                                const logoPath = 'images/2026/DPE_logo_3000x809.png#joomlaImage://local-images/2026/DPE_logo_3000x809.png?width=3000&height=809';
                                authorLogoField.value = logoPath;
                                authorLogoField.dispatchEvent(new Event('input', { bubbles: true }));
                                authorLogoField.dispatchEvent(new Event('change', { bubbles: true }));

                                // Manually update Joomla media preview if it exists
                                try {
                                    const container = authorLogoField.closest('.control-group') || authorLogoField.closest('.mb-3') || authorLogoField.parentElement;
                                    const previewContainer = container ? container.querySelector('.field-media-preview') : null;

                                    if (previewContainer) {
                                        let previewImg = previewContainer.querySelector('img');
                                        const cleanPath = logoPath.split('#')[0];
                                        const fullPath = (window.Joomla && Joomla.getOptions && Joomla.getOptions('system.paths')?.rootFull || '') + '/' + cleanPath;

                                        if (!previewImg) {
                                            // Create img element if it doesn't exist
                                            previewImg = document.createElement('img');
                                            previewImg.alt = '';

                                            // Hide or remove the icon span
                                            const iconSpan = previewContainer.querySelector('.field-media-preview-icon');
                                            if (iconSpan) iconSpan.style.display = 'none';

                                            previewContainer.appendChild(previewImg);
                                        }

                                        previewImg.src = fullPath;
                                        previewImg.style.display = 'block';
                                    }
                                } catch (e) {
                                    console.error('AI SEO: Error updating media preview:', e);
                                }
                            }
                            if (authorEmailField) {
                                authorEmailField.value = 'info@dataprotection.education';
                                authorEmailField.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        }

                        showResult(resultsDiv, `
                            <div class="ai-seo-result success">
                                <div class="ai-seo-result-label">${_t('schema_gen_heading', '✅ Schema Generated:')}</div>
                                <div class="ai-seo-result-content">
                                    <p><strong>${_t('schema_name_label', 'Name/Headline:')}</strong> ${data.schema.headline || data.schema.name}</p>
                                    <p><strong>${_t('schema_desc_label', 'Description:')}</strong> ${data.schema.description}</p>
                                   
                                </div>
                            </div>
                        `, 'success');
                        showToast(headlineField && descField ? _t('schema_applied', '✅ Schema data filled!') : _t('schema_copy_manual', '⚠️ Please copy manually'));
                    }
                })
                .catch(err => showResult(resultsDiv, _t('fetch_err_prefix', 'Request failed: ') + err, 'error'))
                .finally(() => setLoading(btn, false));
        });
    }

    function setupCustomGenButton() {
        const btn = document.getElementById('ai-generate-custom');
        if (!btn) return;

        btn.addEventListener('click', function () {
            const prompt = document.getElementById('ai-custom-prompt')?.value || '';
            const resultsDiv = document.getElementById('ai-seo-results-content');

            if (!prompt || prompt.trim() === '') {
                showResult(resultsDiv, _t('custom_prompt_err', 'Please enter a topic or question in the text box first'), 'error');
                return;
            }

            const currentBody = getEditorContent();
            if (currentBody && currentBody.trim() !== '') {
                if (!confirm(_t('replace_confirm', '⚠️ This will replace the current editor content with the generated result. \n\nContinue?'))) {
                    return;
                }
            }

            setLoading(btn, true);
            showResult(resultsDiv, `${_t('generating', 'Generating...')}...`, 'info');

            fetch(ajaxUrl + '&ai_seo_task=generateCustomContent', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ prompt })
            })
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        showResult(resultsDiv, _t('err_prefix', 'Error: ') + data.error, 'error');
                    } else if (data.success) {
                        showRewriteResult(resultsDiv, data.content, _t('custom_prompt_context', 'Custom AI Request: ') + prompt);
                    }
                })
                .catch(err => showResult(resultsDiv, _t('fetch_err_prefix', 'Request failed: ') + err, 'error'))
                .finally(() => setLoading(btn, false));
        });
    }

    function setupOptimizeTitleButton() {
        const btn = document.getElementById('ai-optimize-title');
        if (!btn) return;

        btn.addEventListener('click', function () {
            const titleField = document.querySelector('[name="jform[title]"]') ||
                document.querySelector('#jform_title') ||
                document.querySelector('[name="title"]');

            const title = titleField?.value || '';
            const body = getEditorContent();
            const resultsDiv = document.getElementById('ai-seo-results-content');

            if (!title && !body) {
                showResult(resultsDiv, _t('err_no_content', 'Please enter a title or content first'), 'error');
                return;
            }

            setLoading(btn, true);
            showToast(_t('optimizing_title', '🤖 Optimizing title...'), 'info');

            fetch(ajaxUrl + '&ai_seo_task=optimizeTitle', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ title, body })
            })
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        showResult(resultsDiv, _t('err_prefix', 'Error: ') + data.error, 'error');
                    } else if (data.success && data.new_title) {
                        const newTitle = data.new_title;

                        // Update all potential title fields
                        const titleFields = [
                            document.querySelector('[name="jform[title]"]'),
                            document.querySelector('#jform_title'),
                            document.querySelector('[name="title"]')
                        ];

                        titleFields.forEach(field => {
                            if (field) {
                                field.value = newTitle;
                                field.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        });

                        showToast(_t('title_optimized', '✅ Title optimized and filled!'));
                    }
                })
                .catch(err => showResult(resultsDiv, 'Request failed: ' + err, 'error'))
                .finally(() => setLoading(btn, false));
        });
    }

    function getEditorContent() {
        let content = '';

        // Try TinyMCE (most common in Joomla)
        if (typeof tinymce !== 'undefined') {
            const editor = tinymce.get('jform_articletext') || tinymce.activeEditor;
            if (editor) content = editor.getContent();
        }

        // Fallback to textarea
        if (!content) {
            const textarea = document.querySelector('[name="jform[articletext]"]') ||
                document.querySelector('#jform_articletext');
            if (textarea) content = textarea.value;
        }

        return content;
    }

    function setLoading(btn, isLoading) {
        btn.disabled = isLoading;
        btn.classList.toggle('loading', isLoading);
    }

    function showResult(container, content, type = 'success', label = '') {
        if (!container) return;
        container.innerHTML = `
            <div class="ai-seo-result ${type}">
                ${label ? `<div class="ai-seo-result-label">${label}</div>` : ''}
                <div class="ai-seo-result-content">${content}</div>
            </div>
        `;
    }

    function showMetaResult(container, data) {
        if (!container) return;
        const keywords = data.keywords || '';
        const description = data.description || '';

        // Auto-fill the fields
        const keywordsField = document.getElementById('jform_metakey');
        const descField = document.getElementById('jform_metadesc');

        let autoFilledKeywords = false;
        let autoFilledDesc = false;

        if (keywordsField) {
            keywordsField.value = keywords;
            keywordsField.dispatchEvent(new Event('change', { bubbles: true }));
            autoFilledKeywords = true;
        }

        if (descField) {
            descField.value = description;
            descField.dispatchEvent(new Event('change', { bubbles: true }));
            autoFilledDesc = true;
        }

        // Show success message
        const autoFillStatus = (autoFilledKeywords && autoFilledDesc)
            ? _t('auto_fill_both', '✅ <strong>Auto-filled both fields!</strong>')
            : (autoFilledKeywords ? _t('auto_fill_keywords', '✅ Keywords auto-filled') : '') + (autoFilledDesc ? _t('auto_fill_desc', '✅ Description auto-filled') : '');

        container.innerHTML = `
            <div class="ai-seo-result success">
                <div class="ai-seo-auto-status">${autoFillStatus}</div>
                <div class="ai-seo-meta-section">
                    <div class="ai-seo-result-label">${_t('meta_keywords_label', '🏷️ Meta Keywords:')}</div>
                    <div class="ai-seo-result-content">
                        <div class="ai-seo-meta-value">${keywords}</div>
                    </div>
                </div>
                <div class="ai-seo-meta-section">
                    <div class="ai-seo-result-label">${_t('meta_desc_label', '📝 Meta Description:')}</div>
                    <div class="ai-seo-result-content">
                        <div class="ai-seo-meta-value">${description}</div>
                        <span class="ai-seo-char-count">${_t('char_count_msg', '%s/160 characters').replace('%s', description.length)}</span>
                    </div>
                </div>
            </div>
        `;

        showToast(_t('meta_filled', '✓ Meta data auto-filled into fields!'));
    }

    function showSeoAnalysis(container, suggestions) {
        if (!container) return;
        container.innerHTML = `
            <div class="ai-seo-result success">
                <div class="ai-seo-result-label">${_t('analysis_heading', '📊 SEO Analysis & Suggestions:')}</div>
                <div class="ai-seo-result-content ai-seo-suggestions">
                    ${formatSuggestions(suggestions)}
                </div>
            </div>
        `;
    }

    function showRewriteResult(container, newContent, requestContext = '') {
        if (!container) return;

        // Strip markdown backticks if they somehow reached the frontend
        newContent = newContent.replace(/^```[a-z]*\s*/i, '').replace(/\s*```$/, '').trim();

        // Store content temporarily in a global object to avoid escaping hell
        window.aiSeoTempContent = newContent;

        let contextHtml = '';
        if (requestContext) {
            contextHtml = `
                <div class="ai-seo-request-context" style="margin-bottom:12px; padding:10px; background:rgba(0,0,0,0.03); border-radius:6px; border-left:3px solid #6366f1;">
                    <strong style="display:block; font-size:11px; text-transform:uppercase; color:#6366f1; margin-bottom:4px;">${_t('your_request', 'Your Request')}</strong>
                    <div style="font-size:13px; color:#1e293b;">${escapeHtml(requestContext)}</div>
                </div>
            `;
        }

        container.innerHTML = `
            <div class="ai-seo-result success">
                <div class="ai-seo-result-label">${_t('opt_content_ready', '✨ AI Result Ready:')}</div>
                <div class="ai-seo-result-content">
                    ${contextHtml}
                    <div class="ai-seo-actions" style="margin-top:15px">
                        <button type="button" class="ai-seo-btn ai-seo-btn-success" onclick="applyRewrittenContent()">
                            ${_t('apply_to_editor', '✅ Apply to Editor')}
                        </button>
                        <button type="button" class="ai-seo-btn ai-seo-btn-outline" onclick="this.closest('.ai-seo-result').remove()">
                            ${_t('cancel', '❌ Cancel')}
                        </button>
                    </div>
                    <details style="margin-top:10px; cursor:pointer">
                        <summary>${_t('view_raw_html', 'View raw HTML preview')}</summary>
                        <textarea readonly style="width:100%; height:150px; margin-top:5px; font-family:monospace; font-size:11px;">${escapeHtml(newContent)}</textarea>
                    </details>
                </div>
            </div>
        `;

        showToast(_t('content_rewritten', '✨ Content rewritten! Click Apply to save.'));
    }

    function showAltTextResult(container, altText, imageUrl) {
        if (!container) return;
        const escapedAlt = escapeHtml(altText || '');
        container.innerHTML = `
            <div class="ai-seo-result success">
                <div class="ai-seo-result-label">${_t('generate_alt', '🖼️ Generated Alt Text:')}</div>
                <div class="ai-seo-result-content">
                    <div class="ai-seo-meta-value">"${altText}"</div>
                    <small class="ai-seo-image-url">For: ${imageUrl.substring(0, 60)}${imageUrl.length > 60 ? '...' : ''}</small>
                    <button type="button" class="ai-seo-copy-btn" onclick="copyToClipboard('${escapedAlt}')">
                        ${_t('copy_to_clipboard', '📋 Copy to Clipboard')}
                    </button>
                </div>
            </div>
        `;
    }

    function formatSuggestions(text) {
        if (!text) return _t('no_suggestions_avail', '<em>No suggestions available</em>');
        // Convert numbered list to HTML
        return text
            .replace(/(\d+)\.\s+/g, '<li>')
            .replace(/\n/g, '</li>')
            .replace(/<li>/, '<ol><li>')
            .replace(/$/, '</li></ol>')
            .replace(/<\/li><\/li>/g, '</li>');
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
});

// Global functions for copy buttons
function copyToField(fieldId, text) {
    const field = document.getElementById(fieldId);
    if (field) {
        field.value = text.replace(/&quot;/g, '"').replace(/&#039;/g, "'").replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>');
        field.focus();
        field.dispatchEvent(new Event('change', { bubbles: true }));
        showToast(_t('copy_field_success', '✓ Copied to field!'));
    } else {
        showToast(_t('copy_field_err', 'Field not found. Please copy manually.'), 'error');
    }
}

function copyToClipboard(text) {
    const decodedText = text.replace(/&quot;/g, '"').replace(/&#039;/g, "'").replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>');
    navigator.clipboard.writeText(decodedText).then(() => {
        showToast(_t('copy_clip_success', '✓ Copied to clipboard!'));
    }).catch(() => {
        showToast(_t('copy_clip_err', 'Failed to copy. Please copy manually.'), 'error');
    });
}

function generateAltForImage(encodedSrc, index) {
    const src = decodeURIComponent(encodedSrc);
    const options = Joomla.getOptions('ai_seo_buttons');
    if (!options || !options.ajaxUrl) return;

    const btn = event.target;
    btn.disabled = true;
    btn.textContent = _t('gen_alt_btn_loading', 'Generating...');

    fetch(options.ajaxUrl + '&ai_seo_task=generateAlt', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ image_url: src })
    })
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                showToast(_t('err_prefix', 'Error: ') + data.error, 'error');
            } else {
                showToast(_t('alt_label', 'Alt text: ') + data.alt_text);
                copyToClipboard(data.alt_text);
            }
        })
        .catch(err => showToast(_t('err_fetch_failed', 'Request failed'), 'error'))
        .finally(() => {
            btn.disabled = false;
            btn.textContent = _t('gen_alt_btn', 'Generate Alt');
        });
}

function showToast(message, type = 'success') {
    const existing = document.querySelector('.ai-seo-toast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = 'ai-seo-toast ' + type;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function applyRewrittenContent() {
    if (!window.aiSeoTempContent) return;

    const newContent = window.aiSeoTempContent;

    // Try TinyMCE first
    if (typeof tinymce !== 'undefined') {
        const editor = tinymce.get('jform_articletext') || tinymce.activeEditor;
        if (editor) {
            editor.setContent(newContent);
            showToast(_t('apply_success', '✅ Content updated successfully!'));
            // Remove the result box
            const resultBox = document.querySelector('#ai-seo-results-content .ai-seo-result');
            if (resultBox) resultBox.remove();
            return;
        }
    }

    // Fallback to textarea
    const textarea = document.querySelector('[name="jform[articletext]"]') ||
        document.querySelector('#jform_articletext');

    if (textarea) {
        textarea.value = newContent;
        textarea.dispatchEvent(new Event('change', { bubbles: true }));
        showToast(_t('apply_success', '✅ Content updated successfully!'));
        // Remove the result box
        const resultBox = document.querySelector('#ai-seo-results-content .ai-seo-result');
        if (resultBox) resultBox.remove();
    } else {
        showToast(_t('err_no_editor', 'Could not find editor to update. Please copy manually.'), 'error');
    }
}


