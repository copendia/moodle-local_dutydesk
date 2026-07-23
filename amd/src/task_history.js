// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

define(['core/modal_factory', 'core/notification'], function(ModalFactory, Notification) {
    const SELECTOR_CONTAINER = '.local-dutydesk-task-list';
    const SELECTOR_BUTTON = '[data-action="view-task-history"]';

    const fetchHistory = (endpoint, params) => {
        const url = new URL(endpoint, M.cfg.wwwroot);
        Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));
        return fetch(url.toString(), {
            method: 'GET',
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).then(response => {
            if (!response.ok) {
                throw new Error(response.statusText);
            }
            return response.json();
        });
    };

    const openModal = (data) => {
        return ModalFactory.create({
            type: ModalFactory.types.DEFAULT,
            title: data.modaltitle,
            body: data.bodyhtml,
        }).then(modal => {
            modal.show();
            return modal;
        });
    };

    const init = () => {
        const container = document.querySelector(SELECTOR_CONTAINER);
        if (!container) {
            return;
        }
        const endpoint = container.dataset.historyEndpoint;
        const sesskey = container.dataset.sesskey;

        container.addEventListener('click', event => {
            const button = event.target.closest(SELECTOR_BUTTON);
            if (!button) {
                return;
            }
            event.preventDefault();

            const taskid = button.dataset.taskId;
            if (!taskid) {
                return;
            }

            fetchHistory(endpoint, {taskid, sesskey})
                .then(data => openModal(data))
                .catch(Notification.exception);
        });
    };

    return {init};
});
