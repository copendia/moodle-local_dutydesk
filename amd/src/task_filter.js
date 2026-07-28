define(['core/notification'], function(Notification) {
    var selectors = {
        results: '[data-region="task-results"]',
        form: '[data-region="task-search-form"]',
        input: '[data-region="task-search-input"]',
        cards: '[data-region="task-card"]',
        empty: '[data-region="task-search-empty"]',
    };

    var normalize = function(value) {
        return value ? value.toString().toLocaleLowerCase() : '';
    };

    var applyFilter = function(input, cards, emptyInfo) {
        var term = normalize(input.value).trim();
        var matches = 0;

        cards.forEach(function(card) {
            var text = normalize(card.getAttribute('data-search'));
            var isMatch = term === '' || text.indexOf(term) !== -1;
            card.classList.toggle('d-none', !isMatch);
            if (isMatch) {
                matches += 1;
            }
        });

        if (emptyInfo) {
            emptyInfo.classList.toggle('d-none', matches !== 0);
        }
    };

    var debounce = function(callback, delay) {
        delay = delay || 400;
        var timer = null;
        return function() {
            var args = arguments;
            if (timer) {
                window.clearTimeout(timer);
            }
            timer = window.setTimeout(function() {
                callback.apply(null, args);
            }, delay);
        };
    };

    var initClientFilter = function() {
        var input = document.querySelector(selectors.input);
        if (!input) {
            return;
        }

        var cards = Array.from(document.querySelectorAll(selectors.cards));
        var emptyInfo = document.querySelector(selectors.empty);
        var handleInput = function() {
            applyFilter(input, cards, emptyInfo);
        };

        input.addEventListener('input', handleInput);
        handleInput();
    };

    var buildRequestUrl = function(form, endpoint, sesskey) {
        var formData = new FormData(form);
        formData.set('ajax', '1');
        if (sesskey) {
            formData.set('sesskey', sesskey);
        }
        var params = new URLSearchParams(formData);
        var url = new URL(endpoint, window.location.origin);
        url.search = params.toString();
        return url.toString();
    };

    var reinitializeDynamicModules = function() {
        require(['local_dutydesk/subtasks_toggle', 'local_dutydesk/task_history'], function(toggle, history) {
            if (toggle && typeof toggle.init === 'function') {
                toggle.init();
            }
            if (history && typeof history.init === 'function') {
                history.init();
            }
        });
    };

    var initServerSearch = function() {
        var results = document.querySelector(selectors.results);
        if (!results) {
            return;
        }

        var captureFocusState = function() {
            var active = document.activeElement;
            if (!active || !active.matches(selectors.input)) {
                return null;
            }
            return {
                selectionStart: active.selectionStart,
                selectionEnd: active.selectionEnd,
            };
        };

        var restoreFocusState = function(state) {
            if (!state) {
                return;
            }
            var input = results.querySelector(selectors.input);
            if (!input) {
                return;
            }
            input.focus({preventScroll: true});
            if (typeof input.setSelectionRange === 'function' && state.selectionStart !== null) {
                input.setSelectionRange(
                    state.selectionStart,
                    state.selectionEnd !== null ? state.selectionEnd : state.selectionStart
                );
            }
        };

        var getForm = function() {
            return results.querySelector(selectors.form);
        };
        var getPageInput = function(form) {
            return form ? form.querySelector('input[name="page"]') : null;
        };
        var getEndpoint = function(form) {
            return results.dataset.searchEndpoint || (form ? form.action : '');
        };
        var getSesskey = function() {
            return results.dataset.sesskey || '';
        };

        var abortController = null;

        var fetchResults = function(form) {
            var activeForm = form || getForm();
            if (!activeForm) {
                return;
            }
            var endpoint = getEndpoint(activeForm);
            if (!endpoint) {
                return;
            }

            if (abortController) {
                abortController.abort();
            }
            abortController = new AbortController();

            var focusState = captureFocusState();
            var url = buildRequestUrl(activeForm, endpoint, getSesskey());
            fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                signal: abortController.signal,
            })
                .then(function(response) {
                    return response.text().then(function(text) {
                        if (!response.ok) {
                            throw new Error(text || response.statusText);
                        }
                        return text;
                    });
                })
                .then(function(html) {
                    results.innerHTML = html || '';
                    reinitializeDynamicModules();
                    restoreFocusState(focusState);
                })
                .catch(function(error) {
                    if (error.name === 'AbortError') {
                        return;
                    }
                    Notification.exception(error);
                });
        };

        var resetPageAndFetch = function(form, handler) {
            var pageInput = getPageInput(form);
            if (pageInput) {
                pageInput.value = 0;
            }
            handler(form);
        };

        var debouncedFetch = debounce(function(form) {
            fetchResults(form);
        }, 300);

        results.addEventListener('input', function(event) {
            if (!event.target.matches(selectors.input)) {
                return;
            }
            var form = event.target.closest(selectors.form) || getForm();
            if (!form) {
                return;
            }
            resetPageAndFetch(form, debouncedFetch);
        });

        results.addEventListener('change', function(event) {
            if (!event.target || event.target.name !== 'vacantonly') {
                return;
            }
            var form = event.target.closest(selectors.form) || getForm();
            if (!form) {
                return;
            }
            resetPageAndFetch(form, fetchResults);
        });

        results.addEventListener('click', function(event) {
            var option = event.target.closest('[data-action="task-filter-option"]');
            if (!option) {
                return;
            }
            event.preventDefault();
            var form = option.closest(selectors.form) || getForm();
            if (!form) {
                return;
            }
            var filtername = option.dataset.filterName;
            if (!filtername) {
                return;
            }
            var targetinput = form.querySelector('input[name="' + filtername + '"]');
            if (!targetinput) {
                return;
            }
            targetinput.value = option.dataset.filterValue || '';

            var menu = option.closest('.dropdown-menu');
            if (menu) {
                menu.querySelectorAll('[data-action="task-filter-option"]').forEach(function(item) {
                    item.removeAttribute('aria-current');
                });
                option.setAttribute('aria-current', 'true');
                var activeText = menu.parentElement ? menu.parentElement.querySelector('[data-active-item-text]') : null;
                if (activeText) {
                    activeText.textContent = option.textContent.trim();
                }
            }

            var chipGroup = option.closest('.local-dutydesk-filter-chip-group');
            if (chipGroup) {
                chipGroup.querySelectorAll('[data-action="task-filter-option"]').forEach(function(item) {
                    item.classList.remove('local-dutydesk-filter-chip--active');
                    item.removeAttribute('aria-current');
                });
                option.classList.add('local-dutydesk-filter-chip--active');
                option.setAttribute('aria-current', 'true');
            }

            resetPageAndFetch(form, fetchResults);
        });

        results.addEventListener('submit', function(event) {
            var form = event.target.closest(selectors.form);
            if (!form) {
                return;
            }
            event.preventDefault();
            fetchResults(form);
        });

        results.addEventListener('click', function(event) {
            var link = event.target.closest('.paging a');
            if (!link) {
                return;
            }
            event.preventDefault();
            var form = getForm();
            if (!form) {
                return;
            }
            var pageInput = getPageInput(form);
            if (pageInput) {
                var url = new URL(link.href, window.location.origin);
                pageInput.value = url.searchParams.get('page') || '0';
            }
            fetchResults(form);
        });
    };

    var init = function() {
        var form = document.querySelector(selectors.form);
        if (form) {
            var behaviour = (form.dataset.behavior || '').toLowerCase();
            if (behaviour === 'server') {
                initServerSearch();
                return;
            }
        }

        initClientFilter();
    };

    return {
        init: init,
    };
});
