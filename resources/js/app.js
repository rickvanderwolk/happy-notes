import './bootstrap';
import Turbolinks from "turbolinks";

const appBaseUrl = document.querySelector('meta[name="app-base-url"]').getAttribute('content');
const appCurrentRouteName = document.querySelector('meta[name="app-current-route-name"]').getAttribute('content');

Turbolinks.start();

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

Livewire.on('emojisChanged', (emojis) => {
    const inputField = document.getElementById('selectedEmojis');
    if (inputField) {
        inputField.value = JSON.stringify(emojis);
    }
});

const keydownListener = (event) => {
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

        if (event.key === 'Escape') {
            if ([
                'note.title.show',
                'note.title.show',
                'note.emojis.show',
            ].includes(appCurrentRouteName)) {
                window.location.href = getRouteUrl('note.show', {note: getUuidFromRoute()});
            } else if ([
                'note.show',
            ].includes(appCurrentRouteName)) {
                window.location.href = getRouteUrl('notes.show') + '#note-' + getUuidFromRoute();
            } else if ([
                'profile.show',
                'user-profile-information.update',
                'user-password.update',
                'two-factor.enable',
                'current-user-photo.destroy',
            ].includes(appCurrentRouteName)) {
                window.location.href = getRouteUrl('notes.show');
            } else {
                window.location.href = getRouteUrl('notes.show');
            }
        }

        if (event.key === 'Enter') {
            if ([
                    'filter.show',
                    'filter.exclude.show',
                    'filter.search.show',
                ].includes(appCurrentRouteName)) {
                window.location.href = getRouteUrl('notes.show')
            }
        }

        if (event.key === 'a') {
            if (appCurrentRouteName === 'menu.show') {
                window.location.href = getRouteUrl('profile.show')
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
                window.location.href = getRouteUrl('note.emojis.show', {note: getUuidFromRoute()});
            } else {
                window.location.href = getRouteUrl('filter.exclude.show');
            }
        }

        if (event.key === 'f') {
            window.location.href = getRouteUrl('filter.show');
        }

        if (event.key === 's') {
            window.location.href = getRouteUrl('filter.search.show');
        }

        if (event.key === 'l') {
            if (appCurrentRouteName === 'menu.show') {
                document.getElementById('logout-button').click();
            }
        }

        if (event.key === 'm') {
            if (['notes.show', 'shortcuts.show'].includes(appCurrentRouteName)) {
                window.location.href = getRouteUrl('menu.show');
            }
        }

        if (event.key === 'n') {
            if (['notes.show', 'shortcuts.show'].includes(appCurrentRouteName)) {
                window.location.href = getRouteUrl('note.create');
            }
        }

        if (event.key === 't') {
            if ([
                'note.show',
                'note.title.show',
                'note.emojis.show',
            ].includes(appCurrentRouteName)) {
                window.location.href = getRouteUrl('note.title.show', {uuid: getUuidFromRoute()});
            }
        }

        if (event.key === '?' || event.key === '/') {
            window.location.href = getRouteUrl('shortcuts.show');
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
document.addEventListener('turbolinks:load', setupKeyboardShortcuts);

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

document.addEventListener('turbolinks:load', function() {
    page = 1;
    isLoading = false;
    isLoadingForAnchor = false;

    if (scrollListener) {
        window.removeEventListener('scroll', scrollListener);
    }

    scrollListener = () => {
        if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 100) {
            loadMoreData();
        }
    };
    
    window.addEventListener('scroll', scrollListener);

    checkAndLoadForAnchor();
});

function checkAndLoadForAnchor() {
    const hash = window.location.hash;
    if (!hash || !hash.startsWith('#note-')) {
        return;
    }

    const targetId = hash.substring(1);
    const targetElement = document.getElementById(targetId);

    if (targetElement) {
        targetElement.scrollIntoView({ behavior: 'smooth' });
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
    page++;
    loadingEl.style.display = 'block';

    fetch(`${appBaseUrl}/notes?page=${page}`)
        .then(response => response.text())
        .then(data => {
            const parser = new DOMParser();
            const htmlDoc = parser.parseFromString(data, 'text/html');
            const newNotes = htmlDoc.querySelectorAll('#note-list .note-card');

            newNotes.forEach(note => noteList.appendChild(note));
            loadingEl.style.display = 'none';
            isLoadingForAnchor = false;

            const targetElement = document.getElementById(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({ behavior: 'smooth' });
                return;
            }

            if (newNotes.length === 0) {
                console.log('Note with anchor not found and no more notes to load');
                return;
            }

            setTimeout(() => loadMoreDataForAnchor(targetId), 100);
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

    fetch(`${appBaseUrl}/notes?page=${page}`)
        .then(response => response.text())
        .then(data => {
            const parser = new DOMParser();
            const htmlDoc = parser.parseFromString(data, 'text/html');
            const newNotes = htmlDoc.querySelectorAll('#note-list .note-card');

            newNotes.forEach(note => noteList.appendChild(note));
            loadingEl.style.display = 'none';
            isLoading = false;

            if (newNotes.length === 0) {
                isLoading = true;
                loadingEl.style.display = 'block';
                loadingEl.textContent = 'No more notes to load.';
            }
        })
        .catch(error => {
            console.error('Error loading more notes:', error);
            loadingEl.style.display = 'none';
            isLoading = false;
        });
}
