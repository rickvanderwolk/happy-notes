import './bootstrap';
import { startCelebration } from './celebration';
import { closeDialog, hasInput, isDialogOpen } from './dialog';

const appBaseUrl = document.querySelector('meta[name="app-base-url"]').getAttribute('content');
// Read on every key press: page navigation swaps the page without reloading this module,
// so a value read once would keep pointing at the first page that was opened.
const getCurrentRouteName = () => document.querySelector('meta[name="app-current-route-name"]')?.getAttribute('content');

const visit = (url) => window.Livewire.navigate(url);

// A title is a single line, so Enter never adds a newline to it. This lives here instead
// of an onkeydown attribute, which the content security policy does not allow.
document.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' && event.target.matches('textarea[data-single-line]')) {
        event.preventDefault();
    }
});

// Saving an empty new note means there was nothing to keep, so it just closes instead of
// asking for a title.
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-leave-if-empty]');
    if (!button || hasInput(button.form)) {
        return;
    }

    event.preventDefault();
    visit(button.dataset.leaveIfEmpty);
}, true);

// Single-key shortcuts can be turned off on the shortcuts page (WCAG 2.1.4), per device
// since they only matter where there is a keyboard.
const shortcutsStorageKey = 'keyboard-shortcuts';

const shortcutsEnabled = () => {
    try {
        return localStorage.getItem(shortcutsStorageKey) !== 'off';
    } catch {
        return true;
    }
};

const applyShortcutsPreference = () => {
    document.documentElement.classList.toggle('shortcuts-off', !shortcutsEnabled());
};

window.setKeyboardShortcuts = (enabled) => {
    try {
        localStorage.setItem(shortcutsStorageKey, enabled ? 'on' : 'off');
    } catch {
        // Without storage the shortcuts simply stay on.
    }
    applyShortcutsPreference();
};

window.keyboardShortcutsEnabled = shortcutsEnabled;

applyShortcutsPreference();

// Every internal link swaps the page instead of reloading it, without having to mark each
// link with wire:navigate. data-no-navigate opts a link out, for pages with another layout.
document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');
    if (
        !link
        || event.defaultPrevented
        || event.button !== 0
        || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey
        || link.target
        || link.hasAttribute('download')
        || link.hasAttribute('data-no-navigate')
    ) {
        return;
    }

    const url = new URL(link.href);
    if (url.origin !== window.location.origin) {
        return;
    }
    if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) {
        return;
    }

    event.preventDefault();
    visit(url.href);
});

// Back and forward restore a page from a snapshot taken when it was left. Forms and filters
// would then show what they held back then, so pages that opt out are fetched again.
const pagesNotToRestore = new Set();

document.addEventListener('livewire:navigated', () => {
    if (document.querySelector('meta[name="navigate-cache-control"][content="no-cache"]')) {
        pagesNotToRestore.add(window.location.pathname);
    }
});

document.addEventListener('livewire:navigate', (event) => {
    const { history, cached, url } = event.detail;
    if (history && cached && pagesNotToRestore.has(url.pathname)) {
        event.preventDefault();
        window.location.reload();
    }
});

function getRouteUrl(routeName, params = {}) {
    routeName = routeName.split(".").join("-");
    let route = document.querySelector(`meta[name="app-route-${routeName}"]`)?.getAttribute('content');

    if (route) {
        Object.keys(params).forEach(key => {
            route = route.replace(`:${key}`, params[key]);
        });

        return route;
    } else {
        route = document.querySelector('meta[name="app-route-dashboard"]')?.getAttribute('content');
        if (route) {
            return route;
        } else {
            console.log('Unable to find route');
        }
    }
}

const getUuidFromRoute = () => {
    let noteUuid = document.querySelector(`meta[name="app-note-uuid"]`)?.getAttribute('content');
    if (noteUuid) {
        return noteUuid;
    }
    return null;
}

const keydownListener = (event) => {
        // While a dialog is open, shortcuts would navigate away underneath it.
        if (isDialogOpen()) {
            if (event.key === 'Escape') {
                closeDialog();
            }
            return;
        }

        // Cmd+F, Ctrl+S and the like belong to the browser, not to the app's shortcuts.
        if (event.metaKey || event.ctrlKey || event.altKey) {
            return;
        }

        if (event.key.length === 1 && !shortcutsEnabled()) {
            return;
        }

        const appCurrentRouteName = getCurrentRouteName();

        // A half-written new note is lost on any navigation, so there only Escape
        // (an explicit cancel) does anything.
        if (appCurrentRouteName === 'note.create' && event.key !== 'Escape') {
            return;
        }

        const ignoreTags = ['INPUT', 'TEXTAREA', 'SELECT'];
        if (
            ignoreTags.includes(event.target.tagName)
            && event.key !== 'Escape'
            && event.key !== 'Enter'
        ) {
            return;
        }

        if (event.target.closest('#editorjs')) {
            return;
        }

        // Escape on a new note goes through its close button, which asks before
        // throwing away what was typed.
        if (appCurrentRouteName === 'note.create' && event.key === 'Escape') {
            document.querySelector('[data-confirm-if-filled]')?.click();
            return;
        }

        if (event.key === 'Escape') {
            if ([
                'note.title.show',
                'note.title.show',
                'note.emojis.show',
            ].includes(appCurrentRouteName)) {
                visit(getRouteUrl('note.show', {note: getUuidFromRoute()}));
            } else if ([
                'note.show',
            ].includes(appCurrentRouteName)) {
                visit(getRouteUrl('notes.show') + '#note-' + getUuidFromRoute());
            } else if ([
                'profile.show',
                'user-profile-information.update',
                'user-password.update',
                'two-factor.enable',
                'current-user-photo.destroy',
            ].includes(appCurrentRouteName)) {
                visit(getRouteUrl('notes.show'));
            } else {
                visit(getRouteUrl('notes.show'));
            }
        }

        if (event.key === 'Enter') {
            if ([
                    'filter.show',
                    'filter.exclude.show',
                    'filter.search.show',
                ].includes(appCurrentRouteName)) {
                visit(getRouteUrl('notes.show'))
            }
        }

        if (event.key === 'a') {
            if (appCurrentRouteName === 'menu.show') {
                visit(getRouteUrl('profile.show'))
            }
        }

        if (event.key === 'b') {
            if (appCurrentRouteName === 'note.show') {
                document.getElementById('editorjs')?.querySelector('[contenteditable="true"]')?.focus();
            }
        }

        if (event.key === 'e') {
            if ([
                'note.show',
                'note.title.show',
                'note.emojis.show',
            ].includes(appCurrentRouteName)) {
                visit(getRouteUrl('note.emojis.show', {note: getUuidFromRoute()}));
            } else {
                visit(getRouteUrl('filter.exclude.show'));
            }
        }

        if (event.key === 'f') {
            visit(getRouteUrl('filter.show'));
        }

        if (event.key === 's') {
            visit(getRouteUrl('filter.search.show'));
        }

        if (event.key === 'l') {
            if (appCurrentRouteName === 'menu.show') {
                document.getElementById('logout-button').click();
            }
        }

        if (event.key === 'm') {
            if (['notes.show', 'shortcuts.show'].includes(appCurrentRouteName)) {
                visit(getRouteUrl('menu.show'));
            }
        }

        if (event.key === 'n') {
            if (['notes.show', 'shortcuts.show'].includes(appCurrentRouteName)) {
                visit(getRouteUrl('note.create'));
            }
        }

        if (event.key === 't') {
            if ([
                'note.show',
                'note.title.show',
                'note.emojis.show',
            ].includes(appCurrentRouteName)) {
                visit(getRouteUrl('note.title.show', {uuid: getUuidFromRoute()}));
            }
        }

        if (event.key === '?' || event.key === '/') {
            visit(getRouteUrl('shortcuts.show'));
        }

        if (
            event.key >= '1'
            && event.key <= '9'
            && appCurrentRouteName === 'notes.show'
        ) {
            let noteList = document.getElementById('note-list');

            if (noteList) {
                let listItems = noteList.getElementsByClassName('note-card');
                let index = parseInt(event.key, 10) - 1;

                if (listItems.length > index) {
                    let link = listItems[index];
                    if (link) {
                        link.click();
                    }
                }
            }
        }
};

function setupKeyboardShortcuts() {
    window.removeEventListener('keyup', keydownListener);
    window.addEventListener('keyup', keydownListener);
}

document.addEventListener('DOMContentLoaded', setupKeyboardShortcuts);
document.addEventListener('livewire:navigated', setupKeyboardShortcuts);
document.addEventListener('livewire:navigated', startCelebration);

window.addEventListener('load', function() {
    var textarea = document.getElementById('titleTextarea');
    if (textarea) {
        textarea.focus();
        var len = textarea.value.length;
        textarea.setSelectionRange(len, len);
    }

    checkAndLoadForAnchor();
});

let page = 1;
let isLoading = false;
let isLoadingForAnchor = false;
let scrollListener = null;

// Fetch the next page while a few screens are still left, so a quick swipe on a phone
// rarely reaches the end before the new notes are in.
const isNearEnd = () => window.innerHeight + window.scrollY >= document.body.offsetHeight - window.innerHeight * 3;

document.addEventListener('livewire:navigated', function() {
    page = 1;
    isLoading = false;
    isLoadingForAnchor = false;

    if (scrollListener) {
        window.removeEventListener('scroll', scrollListener);
    }

    scrollListener = () => {
        if (isNearEnd()) {
            loadMoreData();
        }
    };

    window.addEventListener('scroll', scrollListener);

    checkAndLoadForAnchor();
});

// Back from a note, the list is as you left it: the note sits at the height on the screen
// where you tapped it. A note you changed has moved to the top of the list, so it shows
// there. Without a tap to go by (a link or a reload) the note sits just above the middle.
//
// It waits until the page has settled: Livewire scrolls to the fragment itself right after
// navigating (with the note at the very top), and the web font swapping in rewraps titles
// and shifts everything above the note. Scrolling before either lands a few notes off.
const returnToKey = 'return-to-note';

document.addEventListener('click', (event) => {
    const card = event.target.closest('#note-list .note-card');
    if (!card) {
        return;
    }
    try {
        sessionStorage.setItem(returnToKey, JSON.stringify({ id: card.id, top: card.getBoundingClientRect().top }));
    } catch {
        // Without storage the list falls back to showing the note around the middle
    }
});

const tappedAt = (element) => {
    try {
        const stored = JSON.parse(sessionStorage.getItem(returnToKey) ?? 'null');
        return stored?.id === element.id ? stored.top : null;
    } catch {
        return null;
    }
};

// A full page load asks twice (on load and on navigated); the note is placed once
let placingNote = null;

const showNote = (element) => {
    if (placingNote === element.id) {
        return;
    }
    placingNote = element.id;

    document.fonts.ready.then(() => {
        requestAnimationFrame(() => requestAnimationFrame(() => {
            const top = tappedAt(element);
            if (top === null) {
                element.scrollIntoView({ block: 'center', behavior: 'instant' });
            } else {
                window.scrollBy({ top: element.getBoundingClientRect().top - top, behavior: 'instant' });
            }
            try {
                sessionStorage.removeItem(returnToKey);
            } catch {
                // Nothing stored then either
            }
            placingNote = null;
        }));
    });
};

function checkAndLoadForAnchor() {
    const hash = window.location.hash;
    if (!hash || !hash.startsWith('#note-')) {
        return;
    }

    const targetId = hash.substring(1);
    const targetElement = document.getElementById(targetId);

    if (targetElement) {
        showNote(targetElement);
        return;
    }

    if (!document.getElementById('note-list')) {
        return;
    }

    loadMoreDataForAnchor(targetId);
}

function loadMoreDataForAnchor(targetId) {
    if (isLoading || isLoadingForAnchor) {
        return;
    }

    const noteList = document.getElementById('note-list');
    const loadingEl = document.getElementById('loading');

    if (!noteList || !loadingEl) {
        return;
    }

    isLoadingForAnchor = true;
    loadingEl.style.display = 'block';

    // One request for every page up to the note; the server says which page that ended
    // on, so infinite scroll picks up after it.
    const uuid = targetId.substring('note-'.length);
    fetch(`${appBaseUrl}/notes?page=${page + 1}&partial=1&until=${encodeURIComponent(uuid)}`)
        .then(response => {
            const lastPage = parseInt(response.headers.get('X-Last-Page'), 10);
            if (!Number.isNaN(lastPage)) {
                page = Math.max(page, lastPage);
            }
            return response.text();
        })
        .then(data => {
            // The endpoint returns just the cards, so parse a fragment instead of a
            // whole document.
            const fragment = document.createElement('div');
            fragment.innerHTML = data;
            fragment.querySelectorAll('.note-card').forEach(note => noteList.appendChild(note));
            loadingEl.style.display = 'none';
            isLoadingForAnchor = false;

            const targetElement = document.getElementById(targetId);
            if (targetElement) {
                showNote(targetElement);
            }
        })
        .catch(error => {
            console.error('Error loading more notes for anchor:', error);
            loadingEl.style.display = 'none';
            isLoadingForAnchor = false;
        });
}

function loadMoreData() {
    if (isLoading || isLoadingForAnchor) {
        return;
    }

    const noteList = document.getElementById('note-list');
    const loadingEl = document.getElementById('loading');

    if (!noteList || !loadingEl) {
        return;
    }

    isLoading = true;
    page++;
    loadingEl.style.display = 'block';

    fetch(`${appBaseUrl}/notes?page=${page}&partial=1`)
        .then(response => response.text())
        .then(data => {
            // The endpoint returns just the cards, so parse a fragment instead of a
            // whole document.
            const fragment = document.createElement('div');
            fragment.innerHTML = data;
            const newNotes = fragment.querySelectorAll('.note-card');

            newNotes.forEach(note => noteList.appendChild(note));
            loadingEl.style.display = 'none';
            isLoading = false;

            if (newNotes.length === 0) {
                isLoading = true;
                loadingEl.style.display = 'block';
                loadingEl.textContent = "That's all!";
                return;
            }

            if (isNearEnd()) {
                loadMoreData();
            }
        })
        .catch(error => {
            console.error('Error loading more notes:', error);
            loadingEl.style.display = 'none';
            isLoading = false;
        });
}
