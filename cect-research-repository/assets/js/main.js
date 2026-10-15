function debounce(fn, delay) {
    let timer = null;
    return function () {
        const args = arguments;
        const context = this;
        clearTimeout(timer);
        timer = setTimeout(function () { fn.apply(context, args); }, delay);
    };
}

/**
 * Wires a text input to a JSON suggestions endpoint (search-as-you-type
 * autocomplete), with keyboard navigation and click-to-select.
 * suggestUrl must return JSON: [{ id, title, meta }, ...]
 */
function initSuggestions(options) {
    const input = options.input;
    const list = options.list;
    const suggestUrl = options.suggestUrl;
    const onSelect = options.onSelect;
    if (!input || !list) {
        return;
    }

    let items = [];
    let activeRequest = null;

    function hide() {
        list.classList.add('d-none');
        list.innerHTML = '';
        items = [];
    }

    function select(item) {
        onSelect(item);
        hide();
    }

    function render(newItems) {
        items = newItems || [];
        if (!items.length) {
            hide();
            return;
        }

        list.innerHTML = '';
        items.forEach(function (item, i) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'search-suggest-item';
            btn.dataset.index = String(i);

            const title = document.createElement('span');
            title.className = 'search-suggest-title';
            title.textContent = item.title;
            btn.appendChild(title);

            if (item.meta) {
                const meta = document.createElement('span');
                meta.className = 'search-suggest-meta';
                meta.textContent = item.meta;
                btn.appendChild(meta);
            }

            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
                select(item);
            });

            list.appendChild(btn);
        });

        list.classList.remove('d-none');
    }

    const fetchSuggestions = debounce(function () {
        const q = input.value.trim();
        if (q.length < 2) {
            hide();
            return;
        }
        if (activeRequest) {
            activeRequest.abort();
        }
        const controller = new AbortController();
        activeRequest = controller;

        fetch(suggestUrl + '?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
            .then(function (response) { return response.json(); })
            .then(render)
            .catch(function (err) {
                if (err.name !== 'AbortError') {
                    hide();
                }
            });
    }, 120);

    input.addEventListener('input', fetchSuggestions);
    input.addEventListener('focus', function () {
        if (input.value.trim().length >= 2) {
            fetchSuggestions();
        }
    });
    input.addEventListener('blur', function () {
        setTimeout(hide, 150);
    });

    input.addEventListener('keydown', function (e) {
        const buttons = list.querySelectorAll('.search-suggest-item');
        if (!buttons.length) {
            return;
        }
        let idx = Array.prototype.findIndex.call(buttons, function (b) { return b.classList.contains('active'); });

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            idx = Math.min(idx + 1, buttons.length - 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            idx = Math.max(idx - 1, 0);
        } else if (e.key === 'Enter' && idx >= 0) {
            e.preventDefault();
            select(items[idx]);
            return;
        } else if (e.key === 'Escape') {
            hide();
            return;
        } else {
            return;
        }

        buttons.forEach(function (b) { b.classList.remove('active'); });
        buttons[idx].classList.add('active');
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const searchForm = document.getElementById('searchForm');
    const resultsContainer = document.getElementById('resultsContainer');
    const liveIndicator = document.getElementById('qLiveIndicator');

    if (!searchForm || !resultsContainer) {
        return;
    }

    let activeRequest = null;

    function loadResults(page) {
        const params = new URLSearchParams(new FormData(searchForm));
        params.set('ajax', '1');
        if (page) {
            params.set('page', page);
        }

        if (activeRequest) {
            activeRequest.abort();
        }
        const controller = new AbortController();
        activeRequest = controller;

        if (liveIndicator) liveIndicator.classList.remove('d-none');

        fetch('home.php?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
            .then(response => response.text())
            .then(html => {
                resultsContainer.innerHTML = html;
                if (liveIndicator) liveIndicator.classList.add('d-none');

                const url = new URL(window.location.href);
                params.delete('ajax');
                url.search = params.toString();
                window.history.replaceState({}, '', url);
            })
            .catch(err => {
                if (err.name !== 'AbortError' && liveIndicator) {
                    liveIndicator.classList.add('d-none');
                }
            });
    }

    searchForm.addEventListener('submit', function (e) {
        e.preventDefault();
        loadResults(1);
    });

    resultsContainer.addEventListener('click', function (e) {
        const link = e.target.closest('#pagination a[data-page]');
        if (link) {
            e.preventDefault();
            loadResults(link.dataset.page);
        }
    });

    ['course', 'research_type', 'category', 'year'].forEach(function (id) {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', function () {
                loadResults(1);
            });
        }
    });

    const qInput = document.getElementById('q');
    if (qInput) {
        qInput.addEventListener('input', function () {
            loadResults(1);
        });
    }

    initSuggestions({
        input: qInput,
        list: document.getElementById('qSuggestions'),
        suggestUrl: 'suggest.php',
        onSelect: function (item) {
            qInput.value = item.title;
            loadResults(1);
        },
    });
});
