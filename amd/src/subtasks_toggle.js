// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Toggle handling for subtask lists.
 *
 * @package
 * @copyright  2026 onwards Copendia GmbH
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {
    var DEFAULT_COLLAPSED_HEIGHT = 224;

    var selectors = {
        container: '[data-region="subtasks"]',
        toggle: '.local-dutydesk-subtask-toggle',
        toggleAll: '.local-dutydesk-subtasks-toggleall',
        list: '.local-dutydesk-subtasks-list',
    };

    var getPanel = function(button) {
        var targetId = button.getAttribute('data-target');
        if (targetId) {
            return document.getElementById(targetId);
        }

        var section = button.closest('.local-dutydesk-subtask');
        if (section) {
            return section.querySelector('.local-dutydesk-subtask-panel');
        }

        return null;
    };

    var setExpandedState = function(button, panel, expanded) {
        var isExpanded = expanded === true;
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

    var togglePanel = function(button) {
        var panel = getPanel(button);
        if (!panel) {
            return;
        }

        var isExpanded = button.getAttribute('aria-expanded') === 'true';
        setExpandedState(button, panel, !isExpanded);
    };

    var getCollapsedHeight = function(list) {
        var attributeValue = parseInt(list.getAttribute('data-collapsed-height'), 10);
        if (Number.isFinite(attributeValue) && attributeValue > 0) {
            return attributeValue;
        }
        return DEFAULT_COLLAPSED_HEIGHT;
    };

    var updateToggleAllLabel = function(container, toggleAll, collapsed) {
        var expandLabel = container.getAttribute('data-expand-label')
            || toggleAll.dataset.expandLabel
            || toggleAll.getAttribute('aria-label')
            || '';
        var collapseLabel = container.getAttribute('data-collapse-label')
            || toggleAll.dataset.collapseLabel
            || expandLabel;

        toggleAll.dataset.expandLabel = expandLabel;
        toggleAll.dataset.collapseLabel = collapseLabel;

        toggleAll.setAttribute('aria-label', collapsed ? expandLabel : collapseLabel);
        toggleAll.setAttribute('title', collapsed ? expandLabel : collapseLabel);
        toggleAll.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        toggleAll.classList.toggle('is-expanded', !collapsed);
    };

    var collapseList = function(container, list, toggleAll) {
        var collapsedHeight = getCollapsedHeight(list);
        list.classList.add('is-transitioning');
        list.classList.add('is-collapsed');
        list.style.maxHeight = collapsedHeight + 'px';
        updateToggleAllLabel(container, toggleAll, true);
        window.setTimeout(function() {
            list.classList.remove('is-transitioning');
        }, 300);
    };

    var expandList = function(container, list, toggleAll) {
        list.classList.add('is-transitioning');
        var targetHeight = list.scrollHeight;
        list.style.maxHeight = targetHeight + 'px';
        list.classList.remove('is-collapsed');
        updateToggleAllLabel(container, toggleAll, false);

        var handleTransitionEnd = function(event) {
            if (event.target !== list) {
                return;
            }
            list.style.maxHeight = '';
            list.classList.remove('is-transitioning');
            list.removeEventListener('transitionend', handleTransitionEnd);
        };

        list.addEventListener('transitionend', handleTransitionEnd);
        window.setTimeout(function() {
            list.classList.remove('is-transitioning');
            list.removeEventListener('transitionend', handleTransitionEnd);
        }, 350);
    };

    var toggleList = function(container, list, toggleAll) {
        var isCollapsed = list.classList.contains('is-collapsed');
        if (isCollapsed) {
            expandList(container, list, toggleAll);
        } else {
            collapseList(container, list, toggleAll);
        }
    };

    var prepareList = function(container, list, toggleAll) {
        if (!list || !toggleAll || !list.dataset.collapsible) {
            return;
        }

        if (!list.dataset.listPrepared) {
            list.dataset.listPrepared = 'true';
            list.style.maxHeight = '';
            collapseList(container, list, toggleAll);
        }

        toggleAll.addEventListener('click', function(event) {
            event.preventDefault();
            toggleList(container, list, toggleAll);
        });
    };

    var enhanceContainer = function(container) {
        if (container.dataset.subtasksToggleInitialised) {
            return;
        }
        container.dataset.subtasksToggleInitialised = 'true';

        container.querySelectorAll(selectors.toggle).forEach(function(button) {
            if (button.dataset.subtaskToggleInitialised) {
                return;
            }
            button.dataset.subtaskToggleInitialised = 'true';

            var panel = getPanel(button);
            setExpandedState(button, panel, false);

            button.addEventListener('click', function(event) {
                event.preventDefault();
                togglePanel(button);
            });
        });

        var list = container.querySelector(selectors.list);
        var toggleAll = container.querySelector(selectors.toggleAll);
        prepareList(container, list, toggleAll);
    };

    var init = function() {
        document.querySelectorAll(selectors.container).forEach(enhanceContainer);
    };

    return {
        init: init,
    };
});
