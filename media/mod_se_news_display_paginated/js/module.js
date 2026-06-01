/**
 * SE News Display Paginated - Client-Side Pagination
 * All article data is embedded as JSON by PHP.
 * This script slices the data per page and re-renders the grid in-place.
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.mod-se-news-display-paginated').forEach(initModule);
    });

    /* ── Bootstrap one module instance ── */
    function initModule(module) {
        // Read the embedded JSON
        var jsonEl = module.querySelector('script.news-articles-data');
        if (!jsonEl) return;

        var articles;
        try {
            articles = JSON.parse(jsonEl.textContent);
        } catch (e) {
            return;
        }
        if (!articles || !articles.length) return;

        // Config from data attributes
        var perPage       = parseInt(module.dataset.itemsPerPage, 10) || 12;
        var pagType       = module.dataset.paginationType || 'numbers';
        var showImage     = module.dataset.showImage === '1';
        var showTitle     = module.dataset.showTitle === '1';
        var showIntro     = module.dataset.showIntro === '1';
        var showDate      = module.dataset.showDate === '1';
        var showAuthor    = module.dataset.showAuthor === '1';
        var showCategory  = module.dataset.showCategory === '1';
        var showReadmore  = module.dataset.showReadmore === '1';
        var readmoreText  = module.dataset.readmoreText || 'Read More';
        var aspectClass   = module.dataset.aspectRatioClass || '';
        var animation     = module.dataset.animation || 'none';
        var overlayEnabled = module.dataset.overlayEnabled === '1';

        var totalPages = Math.max(1, Math.ceil(articles.length / perPage));
        var currentPage = 1;

        var grid       = module.querySelector('.news-grid');
        var pagWrapper = module.querySelector('.news-pagination');

        if (!grid) return;

        // Build pagination controls (replaces any server-rendered fallback)
        if (pagWrapper) {
            if (totalPages > 1) {
                renderPagination();
            } else {
                pagWrapper.innerHTML = '';
            }
        }

        // If animation is set, apply to initial items
        if (animation && animation !== 'none') {
            applyAnimations();
        }

        /* ── Render the article grid for the current page ── */
        function renderPage() {
            var start = (currentPage - 1) * perPage;
            var end   = start + perPage;
            var slice = articles.slice(start, end);

            // Build HTML
            var html = '';
            for (var i = 0; i < slice.length; i++) {
                html += buildArticleHtml(slice[i]);
            }
            grid.innerHTML = html;

            // Scroll to top of module smoothly
            module.scrollIntoView({ behavior: 'smooth', block: 'start' });

            // Re-render pagination controls
            if (pagWrapper && totalPages > 1) {
                renderPagination();
            }

            // Re-apply animations to new items
            if (animation && animation !== 'none') {
                applyAnimations();
            }
        }

        /* ── Build HTML for one article card ── */
        function buildArticleHtml(art) {
            var h = '<article class="news-item">';

            if (showImage && art.image) {
                h += '<div class="news-image ' + escAttr(aspectClass) + '">'
                   + '<img src="' + escAttr(art.image) + '" alt="' + escAttr(art.title) + '" loading="lazy">';

                // Overlay: title, category, meta inside image
                if (overlayEnabled) {
                    h += '<div class="news-overlay">';
                    if (showCategory && art.category_title) {
                        h += '<a href="' + escAttr(art.category_link) + '" class="news-overlay-category">'
                           + escHtml(art.category_title) + '</a>';
                    }
                    if (showTitle) {
                        h += '<h3 class="news-overlay-title"><a href="' + escAttr(art.link) + '">'
                           + escHtml(art.title) + '</a></h3>';
                    }
                    if (showDate || showAuthor) {
                        h += '<div class="news-overlay-meta">';
                        if (showDate && art.created_fmt) {
                            h += '<time datetime="' + escAttr(art.created) + '">'
                               + escHtml(art.created_fmt) + '</time>';
                        }
                        if (showAuthor && art.author) {
                            h += '<span>By ' + escHtml(art.author) + '</span>';
                        }
                        h += '</div>';
                    }
                    h += '</div>';
                }

                h += '</div>';
            }

            h += '<div class="news-content">';

            // When overlay is off, show category/title/meta in the content area
            if (!overlayEnabled) {
                if (showCategory && art.category_title) {
                    h += '<a href="' + escAttr(art.category_link) + '" class="news-category">'
                       + escHtml(art.category_title) + '</a>';
                }

                if (showTitle) {
                    h += '<h3 class="news-title"><a href="' + escAttr(art.link) + '">'
                       + escHtml(art.title) + '</a></h3>';
                }

                if (showDate || showAuthor) {
                    h += '<div class="news-meta">';
                    if (showDate && art.created_fmt) {
                        h += '<time class="news-date" datetime="' + escAttr(art.created) + '">'
                           + escHtml(art.created_fmt) + '</time>';
                    }
                    if (showAuthor && art.author) {
                        h += '<span class="news-author">By ' + escHtml(art.author) + '</span>';
                    }
                    h += '</div>';
                }
            }

            if (showIntro && art.introtext) {
                h += '<div class="news-intro">' + escHtml(art.introtext) + '</div>';
            }

            if (showReadmore) {
                h += '<a href="' + escAttr(art.link) + '" class="news-readmore">'
                   + escHtml(readmoreText) + '</a>';
            }

            h += '</div></article>';
            return h;
        }

        /* ── Render pagination controls ── */
        function renderPagination() {
            if (!pagWrapper) return;
            var html = '';

            if (pagType === 'prev_next') {
                html = buildPrevNext();
            } else {
                // Default: numbers
                html = buildNumbers();
            }

            pagWrapper.innerHTML = html;
            bindPaginationEvents();
        }

        /* ── Previous / Next buttons ── */
        function buildPrevNext() {
            var h = '';
            if (currentPage > 1) {
                h += '<a href="#" class="pagination-btn" data-page="' + (currentPage - 1) + '">&larr; Previous</a>';
            }
            h += '<span class="pagination-info">Page ' + currentPage + ' of ' + totalPages + '</span>';
            if (currentPage < totalPages) {
                h += '<a href="#" class="pagination-btn" data-page="' + (currentPage + 1) + '">Next &rarr;</a>';
            }
            return h;
        }

        /* ── Numbered pagination ── */
        function buildNumbers() {
            var h = '<div class="pagination-numbers">';
            var i, start, end;

            if (totalPages <= 8) {
                for (i = 1; i <= totalPages; i++) {
                    h += pageLink(i);
                }
            } else {
                // First page
                h += pageLink(1);

                // Calculate visible range
                start = Math.max(2, currentPage - 2);
                end   = Math.min(totalPages - 1, currentPage + 2);

                if (currentPage <= 4) {
                    end = Math.min(7, totalPages - 1);
                }
                if (currentPage >= totalPages - 3) {
                    start = Math.max(2, totalPages - 6);
                }

                if (start > 2) {
                    h += '<span class="pagination-ellipsis">...</span>';
                }

                for (i = start; i <= end; i++) {
                    h += pageLink(i);
                }

                if (end < totalPages - 1) {
                    h += '<span class="pagination-ellipsis">...</span>';
                }

                // Last page
                h += pageLink(totalPages);

                // Dropdown for direct page selection
                h += '<select class="pagination-dropdown js-page-dropdown">';
                h += '<option value="">Go to page...</option>';
                for (i = 1; i <= totalPages; i++) {
                    h += '<option value="' + i + '"' + (i === currentPage ? ' selected' : '') + '>Page ' + i + '</option>';
                }
                h += '</select>';
            }

            h += '</div>';
            return h;
        }

        function pageLink(num) {
            var cls = 'pagination-number' + (num === currentPage ? ' active' : '');
            return '<a href="#" class="' + cls + '" data-page="' + num + '">' + num + '</a>';
        }

        /* ── Attach click handlers to pagination links ── */
        function bindPaginationEvents() {
            if (!pagWrapper) return;

            // Number and prev/next links
            var links = pagWrapper.querySelectorAll('[data-page]');
            for (var i = 0; i < links.length; i++) {
                links[i].addEventListener('click', onPageClick);
            }

            // Dropdown
            var dropdown = pagWrapper.querySelector('.js-page-dropdown');
            if (dropdown) {
                dropdown.addEventListener('change', function () {
                    var page = parseInt(this.value, 10);
                    if (!isNaN(page) && page >= 1 && page <= totalPages && page !== currentPage) {
                        currentPage = page;
                        renderPage();
                    }
                });
            }
        }

        function onPageClick(e) {
            e.preventDefault();
            var page = parseInt(this.getAttribute('data-page'), 10);
            if (isNaN(page) || page < 1 || page > totalPages || page === currentPage) return;
            currentPage = page;
            renderPage();
        }

        /* ── Scroll-triggered entry animations ── */
        function applyAnimations() {
            if (!('IntersectionObserver' in window)) return;

            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry, idx) {
                    if (entry.isIntersecting) {
                        var el = entry.target;
                        setTimeout(function () {
                            el.classList.add('animate-' + animation);
                        }, idx * 50);
                        observer.unobserve(el);
                    }
                });
            }, { threshold: 0.1 });

            module.querySelectorAll('.news-item').forEach(function (item) {
                observer.observe(item);
            });
        }

        /* ── Tiny HTML/attribute escapers ── */
        function escHtml(str) {
            if (!str) return '';
            var d = document.createElement('div');
            d.appendChild(document.createTextNode(str));
            return d.innerHTML;
        }

        function escAttr(str) {
            if (!str) return '';
            return str.replace(/&/g, '&amp;')
                      .replace(/"/g, '&quot;')
                      .replace(/'/g, '&#39;')
                      .replace(/</g, '&lt;')
                      .replace(/>/g, '&gt;');
        }
    }

})();
