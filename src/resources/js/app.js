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

document.addEventListener('DOMContentLoaded', function () {
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
            } else {
                window.location.href = getRouteUrl('dashboard');
            }
        }

        if (event.key === 'Enter') {
            if ([
                    'filter.show',
                    'filter.exclude.show',
                    'filter.search.show',
                ].includes(appCurrentRouteName)) {
                window.location.href = getRouteUrl('dashboard')
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
            if (appCurrentRouteName === 'dashboard') {
                window.location.href = getRouteUrl('menu.show');
            }
        }

        if (event.key === 'n') {
            if (appCurrentRouteName === 'dashboard') {
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

        if (
            event.key >= '1'
            && event.key <= '9'
            && appCurrentRouteName === 'dashboard'
        ) {
            let noteList = document.getElementById('note-list');

            if (noteList) {
                let listItems = noteList.getElementsByClassName('list-group-item');
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

    window.addEventListener('keyup', keydownListener);
});

window.addEventListener('load', function() {
    var textarea = document.getElementById('titleTextarea');
    if (textarea) {
        textarea.focus();
        var len = textarea.value.length;
        textarea.setSelectionRange(len, len);
    }
});

let page = 1;
let isLoading = false;

window.addEventListener('scroll', () => {
    if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 100) {
        loadMoreData();
    }
});

function loadMoreData() {
    if (isLoading) {
        return;
    }
    if (!document.getElementById('note-list')) {
        return;
    }

    isLoading = true;
    page++;
    document.getElementById('loading').style.display = 'block';

    fetch(`${appBaseUrl}/notes?page=${page}`)
        .then(response => response.text())
        .then(data => {
            const parser = new DOMParser();
            const htmlDoc = parser.parseFromString(data, 'text/html');
            const newNotes = htmlDoc.querySelectorAll('#note-list .list-group-item');

            newNotes.forEach(note => document.getElementById('note-list').appendChild(note));
            document.getElementById('loading').style.display = 'none';
            isLoading = false;

            if (newNotes.length === 0) {
                isLoading = true;
                document.getElementById('loading').textContent = 'No more notes to load.';
            }
        })
        .catch(error => {
            console.error(error);
            document.getElementById('loading').style.display = 'none';
            isLoading = false;
        });
}
