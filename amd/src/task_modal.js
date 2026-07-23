define(['core/modal_factory', 'core/modal_events', 'core/notification'], function(ModalFactory, ModalEvents, Notification) {
    const closeMessageType = 'local_dutydesk_close_modal';
    const selectors = {
        trigger: '[data-action="new-task"], [data-action="edit-task"], [data-action="edit-subtask"], [data-action="new-subtask"]',
        cancel: 'button[name="cancel"], input[name="cancel"]',
    };

    let initialised = false;
    let closeListenerInitialised = false;
    let activeModal = null;
    let focusTarget = null;

    const buildIframeHtml = function(url, title) {
        const safeTitle = title || '';
        return '<iframe src="' + url + '" title="' + safeTitle.replace(/"/g, '&quot;') + '" '
            + 'style="width:100%; height:84vh; border:0;" loading="lazy"></iframe>';
    };

    const reloadWithFocus = function() {
        if (!focusTarget || !focusTarget.id) {
            window.location.reload();
            return;
        }

        const url = new URL(window.location.href);
        url.searchParams.set('focus', focusTarget.id);
        url.searchParams.set('forcefirst', '1');
        url.hash = 'task-' + focusTarget.id;

        const targetUrl = url.toString();
        if (targetUrl === window.location.href) {
            window.location.reload();
            return;
        }
        window.location.href = targetUrl;
    };

    const registerCloseListener = function() {
        if (closeListenerInitialised) {
            return;
        }
        closeListenerInitialised = true;
        window.addEventListener('message', function(event) {
            if (!event || event.origin !== window.location.origin || !event.data) {
                return;
            }
            if (event.data.type !== closeMessageType) {
                return;
            }
            if (activeModal) {
                activeModal.hide();
            }
        });
    };

    const openModal = function(trigger) {
        const url = trigger.dataset.modalUrl || trigger.getAttribute('href');
        if (!url) {
            return;
        }

        const title = trigger.dataset.modalTitle || trigger.textContent.trim();
        const taskCard = trigger.closest('[data-task-id]');
        focusTarget = taskCard
            ? {type: 'task', id: taskCard.getAttribute('data-task-id')}
            : null;

        ModalFactory.create({
            type: ModalFactory.types.DEFAULT,
            title: title,
            body: buildIframeHtml(url, title),
            large: true,
        }).then(function(modal) {
            activeModal = modal;
            modal.getRoot().addClass('local-dutydesk-task-editor-modal');
            modal.getRoot().on(ModalEvents.hidden, function() {
                activeModal = null;
                modal.destroy();
                reloadWithFocus();
            });
            modal.show();
            return modal;
        }).catch(Notification.exception);
    };

    const closeParentModal = function() {
        if (window.top === window || !window.parent) {
            return;
        }

        try {
            window.parent.postMessage({type: closeMessageType}, window.location.origin);
        } catch (error) {
            // Ignore cross-window errors. The embedded form remains visible.
        }
    };

    const init = function() {
        if (initialised) {
            return;
        }
        initialised = true;
        registerCloseListener();

        document.addEventListener('click', function(event) {
            const trigger = event.target.closest(selectors.trigger);
            if (!trigger) {
                return;
            }
            event.preventDefault();
            openModal(trigger);
        });
    };

    return {
        init: init,
        initEmbedded: function() {
            document.addEventListener('click', function(event) {
                const trigger = event.target.closest(selectors.cancel);
                if (!trigger) {
                    return;
                }
                event.preventDefault();
                closeParentModal();
            });

            document.addEventListener('submit', function(event) {
                const submitter = event.submitter || document.activeElement;
                if (!submitter || submitter.name !== 'cancel') {
                    return;
                }
                event.preventDefault();
                closeParentModal();
            }, true);
        },
    };
});
