define(['core/notification'], function(Notification) {
    const selectors = {
        results: '[data-region="task-results"]',
        form: '[data-region="task-search-form"]',
        input: '[data-region="task-search-input"]',
        cards: '[data-region="task-card"]',
        empty: '[data-region="task-search-empty"]',
    };

    const normalize = (value) => value ? value.toString().toLocaleLowerCase() : '';

    const applyFilter = (input, cards, emptyInfo) => {
        const term = normalize(input.value).trim();
        let matches = 0;

        cards.forEach((card) => {
            const text = normalize(card.getAttribute('data-search'));
            const isMatch = term === '' || text.indexOf(term) !== -1;
            card.classList.toggle('d-none', !isMatch);
            if (isMatch) {
                matches += 1;
            }
        });

        if (emptyInfo) {
            emptyInfo.classList.toggle('d-none', matches !== 0);
        }
    };

    const debounce = (callback, delay = 400) => {
        let timer = null;
        return (...args) => {
            if (timer) {
                window.clearTimeout(timer);
            }
            timer = window.setTimeout(() => {
                callback(...args);
            }, delay);
        };
    };

    const initClientFilter = () => {
        const input = document.querySelector(selectors.input);
        if (!input) {
            return;
        }

        const cards = Array.from(document.querySelectorAll(selectors.cards));
        const emptyInfo = document.querySelector(selectors.empty);
        const handleInput = () => applyFilter(input, cards, emptyInfo);

        input.addEventListener('input', handleInput);
        handleInput();
    };

    const buildRequestUrl = (form, endpoint, sesskey) => {
        const formData = new FormData(form);
        formData.set('ajax', '1');
        if (sesskey) {
            formData.set('sesskey', sesskey);
        }
        const params = new URLSearchParams(formData);
        const url = new URL(endpoint, window.location.origin);
        url.search = params.toString();
        return url.toString();
    };

    const reinitializeDynamicModules = () => {
        require(['local_dutydesk/subtasks_toggle', 'local_dutydesk/task_history'], function(toggle, history) {
            if (toggle && typeof toggle.init === 'function') {
                toggle.init();
            }
            if (history && typeof history.init === 'function') {
                history.init();
            }
        });
    };

    const initServerSearch = () => {
        const results = document.querySelector(selectors.results);
        if (!results) {
            return;
        }

        const captureFocusState = () => {
            const active = document.activeElement;
            if (!active || !active.matches(selectors.input)) {
                return null;
            }
            return {
                selectionStart: active.selectionStart,
                selectionEnd: active.selectionEnd,
            };
        };

        const restoreFocusState = (state) => {
            if (!state) {
                return;
            }
            const input = results.querySelector(selectors.input);
            if (!input) {
                return;
            }
            input.focus({preventScroll: true});
            if (typeof input.setSelectionRange === 'function' && state.selectionStart !== null) {
                input.setSelectionRange(state.selectionStart, state.selectionEnd ?? state.selectionStart);
            }
        };

        const getForm = () => results.querySelector(selectors.form);
        const getPageInput = (form) => form ? form.querySelector('input[name="page"]') : null;
        const getEndpoint = (form) => results.dataset.searchEndpoint || (form ? form.action : '');
        const getSesskey = () => results.dataset.sesskey || '';

        let abortController = null;

        const fetchResults = (form = null) => {
            const activeForm = form || getForm();
            if (!activeForm) {
                return;
            }
            const endpoint = getEndpoint(activeForm);
            if (!endpoint) {
                return;
            }

            if (abortController) {
                abortController.abort();
            }
            abortController = new AbortController();

            const focusState = captureFocusState();
            const url = buildRequestUrl(activeForm, endpoint, getSesskey());
            fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                signal: abortController.signal,
            })
                .then((response) => response.text().then((text) => {
                    if (!response.ok) {
                        throw new Error(text || response.statusText);
                    }
                    return text;
                }))
                .then((html) => {
                    results.innerHTML = html || '';
                    reinitializeDynamicModules();
                    restoreFocusState(focusState);
                })
                .catch((error) => {
                    if (error.name === 'AbortError') {
                        return;
                    }
                    Notification.exception(error);
                });
        };

        const resetPageAndFetch = (form, handler) => {
            const pageInput = getPageInput(form);
            if (pageInput) {
                pageInput.value = 0;
            }
            handler(form);
        };

        const debouncedFetch = debounce((form) => fetchResults(form), 300);

        results.addEventListener('input', (event) => {
            if (!event.target.matches(selectors.input)) {
                return;
            }
            const form = event.target.closest(selectors.form) || getForm();
            if (!form) {
                return;
            }
            resetPageAndFetch(form, debouncedFetch);
        });

        results.addEventListener('change', (event) => {
            if (!event.target || event.target.name !== 'vacantonly') {
                return;
            }
            const form = event.target.closest(selectors.form) || getForm();
            if (!form) {
                return;
            }
            resetPageAndFetch(form, fetchResults);
        });

        results.addEventListener('click', (event) => {
            const option = event.target.closest('[data-action="task-filter-option"]');
            if (!option) {
                return;
            }
            event.preventDefault();
            const form = option.closest(selectors.form) || getForm();
            if (!form) {
                return;
            }
            const filtername = option.dataset.filterName;
            if (!filtername) {
                return;
            }
            const targetinput = form.querySelector(`input[name="${filtername}"]`);
            if (!targetinput) {
                return;
            }
            targetinput.value = option.dataset.filterValue || '';

            const menu = option.closest('.dropdown-menu');
            if (menu) {
                menu.querySelectorAll('[data-action="task-filter-option"]').forEach((item) => {
                    item.removeAttribute('aria-current');
                });
                option.setAttribute('aria-current', 'true');
                const activeText = menu.parentElement ? menu.parentElement.querySelector('[data-active-item-text]') : null;
                if (activeText) {
                    activeText.textContent = option.textContent.trim();
                }
            }

            const chipGroup = option.closest('.local-dutydesk-filter-chip-group');
            if (chipGroup) {
                chipGroup.querySelectorAll('[data-action="task-filter-option"]').forEach((item) => {
                    item.classList.remove('local-dutydesk-filter-chip--active');
                    item.removeAttribute('aria-current');
                });
                option.classList.add('local-dutydesk-filter-chip--active');
                option.setAttribute('aria-current', 'true');
            }

            resetPageAndFetch(form, fetchResults);
        });

        results.addEventListener('submit', (event) => {
            const form = event.target.closest(selectors.form);
            if (!form) {
                return;
            }
            event.preventDefault();
            fetchResults(form);
        });

        results.addEventListener('click', (event) => {
            const link = event.target.closest('.paging a');
            if (!link) {
                return;
            }
            event.preventDefault();
            const form = getForm();
            if (!form) {
                return;
            }
            const pageInput = getPageInput(form);
            if (pageInput) {
                const url = new URL(link.href, window.location.origin);
                pageInput.value = url.searchParams.get('page') || '0';
            }
            fetchResults(form);
        });
    };

    const init = () => {
        const form = document.querySelector(selectors.form);
        if (form) {
            const behaviour = (form.dataset.behavior || '').toLowerCase();
            if (behaviour === 'server') {
                initServerSearch();
                return;
            }
        }

        initClientFilter();
    };

    return {
        init,
    };
});
