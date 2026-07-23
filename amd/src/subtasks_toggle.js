define([], function() {
    const DEFAULT_COLLAPSED_HEIGHT = 224;

    const selectors = {
        container: '[data-region="subtasks"]',
        toggle: '.local-dutydesk-subtask-toggle',
        toggleAll: '.local-dutydesk-subtasks-toggleall',
        list: '.local-dutydesk-subtasks-list',
    };

    const getPanel = (button) => {
        const targetId = button.getAttribute('data-target');
        if (targetId) {
            return document.getElementById(targetId);
        }

        const section = button.closest('.local-dutydesk-subtask');
        if (section) {
            return section.querySelector('.local-dutydesk-subtask-panel');
        }

        return null;
    };

    const setExpandedState = (button, panel, expanded) => {
        const isExpanded = expanded === true;
        button.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
        button.classList.toggle('collapsed', !isExpanded);

        if (!panel) {
            return;
        }

        if (isExpanded) {
            panel.classList.add('show');
            panel.removeAttribute('hidden');
        } else {
            panel.classList.remove('show');
            panel.setAttribute('hidden', '');
        }
    };

    const togglePanel = (button) => {
        const panel = getPanel(button);
        if (!panel) {
            return;
        }

        const isExpanded = button.getAttribute('aria-expanded') === 'true';
        setExpandedState(button, panel, !isExpanded);
    };

    const getCollapsedHeight = (list) => {
        const attributeValue = parseInt(list.getAttribute('data-collapsed-height'), 10);
        if (Number.isFinite(attributeValue) && attributeValue > 0) {
            return attributeValue;
        }
        return DEFAULT_COLLAPSED_HEIGHT;
    };

    const updateToggleAllLabel = (container, toggleAll, collapsed) => {
        const expandLabel = container.getAttribute('data-expand-label')
            || toggleAll.dataset.expandLabel
            || toggleAll.getAttribute('aria-label')
            || '';
        const collapseLabel = container.getAttribute('data-collapse-label')
            || toggleAll.dataset.collapseLabel
            || expandLabel;

        toggleAll.dataset.expandLabel = expandLabel;
        toggleAll.dataset.collapseLabel = collapseLabel;

        toggleAll.setAttribute('aria-label', collapsed ? expandLabel : collapseLabel);
        toggleAll.setAttribute('title', collapsed ? expandLabel : collapseLabel);
        toggleAll.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        toggleAll.classList.toggle('is-expanded', !collapsed);
    };

    const collapseList = (container, list, toggleAll) => {
        const collapsedHeight = getCollapsedHeight(list);
        list.classList.add('is-transitioning');
        list.classList.add('is-collapsed');
        list.style.maxHeight = collapsedHeight + 'px';
        updateToggleAllLabel(container, toggleAll, true);
        window.setTimeout(() => {
            list.classList.remove('is-transitioning');
        }, 300);
    };

    const expandList = (container, list, toggleAll) => {
        list.classList.add('is-transitioning');
        const targetHeight = list.scrollHeight;
        list.style.maxHeight = targetHeight + 'px';
        list.classList.remove('is-collapsed');
        updateToggleAllLabel(container, toggleAll, false);

        const handleTransitionEnd = (event) => {
            if (event.target !== list) {
                return;
            }
            list.style.maxHeight = '';
            list.classList.remove('is-transitioning');
            list.removeEventListener('transitionend', handleTransitionEnd);
        };

        list.addEventListener('transitionend', handleTransitionEnd);
        window.setTimeout(() => {
            list.classList.remove('is-transitioning');
            list.removeEventListener('transitionend', handleTransitionEnd);
        }, 350);
    };

    const toggleList = (container, list, toggleAll) => {
        const isCollapsed = list.classList.contains('is-collapsed');
        if (isCollapsed) {
            expandList(container, list, toggleAll);
        } else {
            collapseList(container, list, toggleAll);
        }
    };

    const prepareList = (container, list, toggleAll) => {
        if (!list || !toggleAll || !list.dataset.collapsible) {
            return;
        }

        if (!list.dataset.listPrepared) {
            list.dataset.listPrepared = 'true';
            list.style.maxHeight = '';
            collapseList(container, list, toggleAll);
        }

        toggleAll.addEventListener('click', (event) => {
            event.preventDefault();
            toggleList(container, list, toggleAll);
        });
    };

    const enhanceContainer = (container) => {
        if (container.dataset.subtasksToggleInitialised) {
            return;
        }
        container.dataset.subtasksToggleInitialised = 'true';

        container.querySelectorAll(selectors.toggle).forEach((button) => {
            if (button.dataset.subtaskToggleInitialised) {
                return;
            }
            button.dataset.subtaskToggleInitialised = 'true';

            const panel = getPanel(button);
            setExpandedState(button, panel, false);

            button.addEventListener('click', (event) => {
                event.preventDefault();
                togglePanel(button);
            });
        });

        const list = container.querySelector(selectors.list);
        const toggleAll = container.querySelector(selectors.toggleAll);
        prepareList(container, list, toggleAll);
    };

    const init = () => {
        document.querySelectorAll(selectors.container).forEach(enhanceContainer);
    };

    return {
        init,
    };
});
