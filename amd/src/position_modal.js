define(['core/modal_factory', 'core/modal_events', 'core/notification'], function(ModalFactory, ModalEvents, Notification) {
    var closeMessageType = 'local_dutydesk_close_modal';
    var selectors = {
        trigger: [
            '[data-action="new-position"]',
            '[data-action="edit-position"]',
            '[data-action="edit-position-task"]',
            '[data-action="edit-position-subtask"]',
        ].join(', '),
        cancel: 'button[name="cancel"], input[name="cancel"]',
    };

    var buildIframeHtml = function(url, title, height) {
        var safeTitle = title || '';
        return '<iframe src="' + url + '" title="' + safeTitle.replace(/"/g, '&quot;') + '" '
            + 'style="width:100%; height:' + height + '; border:0;" loading="lazy"></iframe>';
    };

    var reloadWithFocus = function() {
        var target = window.localDutyDeskModalFocusTarget;
        if (!target || !target.id) {
            window.location.reload();
            return;
        }

        var url = new URL(window.location.href);
        url.searchParams.set('focus', target.id);
        url.searchParams.set('modalreload', Date.now().toString());
        url.hash = 'position-' + target.id;
        window.location.href = url.toString();
    };

    var registerCloseListener = function() {
        if (window.localDutyDeskModalCloseListenerInitialised) {
            return;
        }
        window.localDutyDeskModalCloseListenerInitialised = true;
        window.addEventListener('message', function(event) {
            if (!event || event.origin !== window.location.origin || !event.data) {
                return;
            }
            if (event.data.type !== closeMessageType) {
                return;
            }
            if (window.localDutyDeskActiveModal) {
                window.localDutyDeskActiveModal.hide();
            }
        });
    };

    var openModal = function(trigger) {
        if (!trigger) {
            return;
        }

        var url = trigger.dataset.modalUrl || trigger.getAttribute('href');
        if (!url) {
            return;
        }

        var title = trigger.dataset.modalTitle || trigger.textContent.trim();
        var isPositionEdit = trigger.dataset.action === 'edit-position'
            || trigger.getAttribute('data-action') === 'edit-position';
        var iframeHeight = isPositionEdit ? '78vh' : '84vh';
        var positionCard = trigger.closest('[id^="position-"]');
        var positionId = positionCard ? String(positionCard.id).replace(/^position-/, '') : '';
        window.localDutyDeskModalFocusTarget = positionId
            ? {type: 'position', id: positionId}
            : null;

        ModalFactory.create({
            type: ModalFactory.types.DEFAULT,
            title: title,
            body: buildIframeHtml(url, title, iframeHeight),
            large: true,
        }).then(function(modal) {
            window.localDutyDeskActiveModal = modal;
            modal.getRoot().addClass('local-dutydesk-task-editor-modal');
            if (isPositionEdit) {
                modal.getRoot().addClass('local-dutydesk-position-editor-modal');
            }
            modal.getRoot().on(ModalEvents.hidden, function() {
                window.localDutyDeskActiveModal = null;
                modal.destroy();
                reloadWithFocus();
            });
            modal.show();
            return modal;
        }).catch(Notification.exception);
    };

    var closeParentModal = function() {
        if (window.top === window || !window.parent) {
            return;
        }

        try {
            window.parent.postMessage({type: closeMessageType}, window.location.origin);
        } catch (error) {
            // Ignore cross-window errors. The embedded form remains visible.
        }
    };

    var initParent = function() {
        if (document.body.dataset.positionModalParentInitialised) {
            return;
        }
        document.body.dataset.positionModalParentInitialised = '1';
        registerCloseListener();
        document.addEventListener('click', function(event) {
            var trigger = event.target.closest(selectors.trigger);
            if (!trigger) {
                return;
            }
            event.preventDefault();
            openModal(trigger);
        });
    };

    var initEmbedded = function() {
        if (document.body.dataset.positionModalEmbeddedInitialised) {
            return;
        }
        document.body.dataset.positionModalEmbeddedInitialised = '1';

        document.addEventListener('click', function(event) {
            var trigger = event.target.closest(selectors.cancel);
            if (!trigger) {
                return;
            }
            event.preventDefault();
            closeParentModal();
        });

        document.addEventListener('submit', function(event) {
            var submitter = event.submitter || document.activeElement;
            if (!submitter || submitter.name !== 'cancel') {
                return;
            }
            event.preventDefault();
            closeParentModal();
        }, true);
    };

    return {
        initParent: initParent,
        initEmbedded: initEmbedded,
    };
});
