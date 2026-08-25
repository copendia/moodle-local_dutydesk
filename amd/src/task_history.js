// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

define(['core/modal_factory', 'core/notification', 'core/ajax'], function(ModalFactory, Notification, Ajax) {
    var SELECTOR_CONTAINER = '.local-dutydesk-task-list';
    var SELECTOR_BUTTON = '[data-action="view-task-history"]';

    var fetchHistory = function(taskid) {
        return Ajax.call([{
            methodname: 'local_dutydesk_get_task_history',
            args: {
                taskid: parseInt(taskid, 10),
            },
        }])[0];
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

            fetchHistory(taskid)
                .then(function(data) {
                    return openModal(data);
                })
                .catch(Notification.exception);
        });
    };

    return {init: init};
});
