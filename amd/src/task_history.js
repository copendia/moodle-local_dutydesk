// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

define(['core/modal_factory', 'core/notification'], function(ModalFactory, Notification) {
    var SELECTOR_CONTAINER = '.local-dutydesk-task-list';
    var SELECTOR_BUTTON = '[data-action="view-task-history"]';

    var fetchHistory = function(endpoint, params) {
        var url = new URL(endpoint, M.cfg.wwwroot);
        Object.keys(params).forEach(function(key) {
            url.searchParams.append(key, params[key]);
        });
        return fetch(url.toString(), {
            method: 'GET',
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).then(function(response) {
            if (!response.ok) {
                throw new Error(response.statusText);
            }
            return response.json();
        });
    };

    var openModal = function(data) {
        return ModalFactory.create({
            type: ModalFactory.types.DEFAULT,
            title: data.modaltitle,
            body: data.bodyhtml,
        }).then(function(modal) {
            modal.show();
            return modal;
        });
    };

    var init = function() {
        var container = document.querySelector(SELECTOR_CONTAINER);
        if (!container) {
            return;
        }
        var endpoint = container.dataset.historyEndpoint;
        var sesskey = container.dataset.sesskey;

        container.addEventListener('click', function(event) {
            var button = event.target.closest(SELECTOR_BUTTON);
            if (!button) {
                return;
            }
            event.preventDefault();

            var taskid = button.dataset.taskId;
            if (!taskid) {
                return;
            }

            fetchHistory(endpoint, {taskid: taskid, sesskey: sesskey})
                .then(function(data) {
                    return openModal(data);
                })
                .catch(Notification.exception);
        });
    };

    return {init: init};
});
