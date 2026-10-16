const CANCEL_EVENT = 'app-dialog:cancel';

let confirmedTrigger = null;

function trapFocus(overlay, event) {
    const buttons = [...overlay.querySelectorAll('button')];
    if (event.key !== 'Tab' || buttons.length === 0) {
        return;
    }

    const first = buttons[0];
    const last = buttons[buttons.length - 1];

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
}

function createButton(label, className, dataCy) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = className;
    button.textContent = label;
    button.dataset.cy = dataCy;
    return button;
}

export function isDialogOpen() {
    return document.querySelector('.app-dialog') !== null;
}

/**
 * Asks the open dialog to close as if it was cancelled. Escape is handled here rather
 * than in each dialog, so the global shortcuts never see it while a dialog is open.
 */
export function closeDialog() {
    document.querySelector('.app-dialog')?.dispatchEvent(new CustomEvent(CANCEL_EVENT));
}

export function onDialogCancel(overlay, callback) {
    overlay.addEventListener(CANCEL_EVENT, callback);
}

/**
 * Shows a dialog with the given buttons and resolves to the value of the one picked.
 * Escape, a click next to the card and leaving the page resolve to cancelValue.
 */
function openDialog(message, buttons, { focusIndex = buttons.length - 1, cancelValue } = {}) {
    return new Promise((resolve) => {
        const previousFocus = document.activeElement;

        const overlay = document.createElement('div');
        overlay.className = 'app-dialog';
        overlay.setAttribute('role', 'alertdialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.dataset.cy = 'dialog';

        const card = document.createElement('div');
        card.className = 'app-dialog-card';

        const text = document.createElement('p');
        text.className = 'app-dialog-message';
        text.id = 'app-dialog-message';
        text.textContent = message;
        overlay.setAttribute('aria-labelledby', text.id);

        const actions = document.createElement('div');
        actions.className = 'app-dialog-actions';

        const close = (value) => {
            overlay.remove();
            previousFocus?.focus?.();
            resolve(value);
        };

        const elements = buttons.map(({ label, className, dataCy, value }) => {
            const button = createButton(label, className, dataCy);
            button.addEventListener('click', () => close(value));
            return button;
        });

        actions.append(...elements);
        card.append(text, actions);
        overlay.append(card);

        onDialogCancel(overlay, () => close(cancelValue));
        overlay.addEventListener('keydown', (event) => trapFocus(overlay, event));
        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) {
                close(cancelValue);
            }
        });

        document.body.append(overlay);
        elements[focusIndex].focus();
    });
}

/**
 * The app's replacement for the browser's confirm dialog. Resolves to true when
 * confirmed.
 */
export function confirmDialog(message, { confirmLabel = 'OK', cancelLabel = 'Cancel', danger = false } = {}) {
    return openDialog(message, [
        { label: cancelLabel, className: 'btn btn-secondary', dataCy: 'dialog-cancel', value: false },
        { label: confirmLabel, className: danger ? 'btn btn-danger' : 'btn btn-primary', dataCy: 'dialog-confirm', value: true },
    ], {
        // A destructive action should never be one stray Enter away.
        focusIndex: danger ? 0 : 1,
        cancelValue: false,
    });
}

/**
 * The app's replacement for the browser's alert dialog. Resolves once it is closed.
 */
export function alertDialog(message, { okLabel = 'OK' } = {}) {
    return openDialog(message, [
        { label: okLabel, className: 'btn btn-primary', dataCy: 'dialog-ok', value: undefined },
    ]);
}

// Any button or link with data-confirm asks first. The listener runs in the capture
// phase so it also stops wire:click and form submits before they happen; once
// confirmed, the original click is simply repeated.
document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-confirm]');
    if (!trigger) {
        return;
    }

    if (trigger === confirmedTrigger) {
        confirmedTrigger = null;
        return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();

    confirmDialog(trigger.dataset.confirm, {
        confirmLabel: trigger.dataset.confirmLabel,
        danger: trigger.hasAttribute('data-confirm-danger'),
    }).then((confirmed) => {
        if (confirmed) {
            confirmedTrigger = trigger;
            trigger.click();
        }
    });
}, true);

document.addEventListener('turbolinks:before-cache', () => {
    document.querySelectorAll('.app-dialog').forEach((overlay) => {
        overlay.dispatchEvent(new CustomEvent(CANCEL_EVENT));
    });
});
