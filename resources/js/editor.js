import EditorJS from '@editorjs/editorjs'
import Header from '@editorjs/header'
import Checklist from '@editorjs/checklist'
import List from '@editorjs/list'
import Code from '@editorjs/code'
import Delimiter from '@editorjs/delimiter'
import Table from '@editorjs/table'

/**
 * This module is evaluated only once per browser session, because that is how ES modules
 * work: Turbolinks re-inserts the script tag on every visit, but the browser will not run
 * it again. So the editor is built from a turbolinks:load listener rather than at module
 * scope, otherwise opening a second note leaves an empty body until a hard refresh.
 */

let editor = null
let attachedTo = null

const destroyEditor = () => {
    try {
        editor?.destroy?.()
    } catch {
        // The old DOM is already gone after a Turbolinks swap; nothing to clean up.
    }
    editor = null
    attachedTo = null
}

const buildEditor = (container, placeholder) => {
    const saveBodyUrl = container.dataset.saveBodyUrl
    const initialData = JSON.parse(container.dataset.initialData)

    const autoSave = () => {
        editor.save().then((outputData) => {
            fetch(saveBodyUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    // Asks the controller for a 204 instead of a redirect to the note page,
                    // which the browser would otherwise follow and download on every save.
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ body: outputData })
            }).then(() => {
                Livewire.dispatch('noteUpdated')
            })
        })
    }

    return new EditorJS({
        holder: container,
        data: initialData,
        tools: {
            header: {
                class: Header,
                inlineToolbar: true
            },
            checklist: {
                class: Checklist,
                inlineToolbar: true
            },
            list: {
                class: List,
                inlineToolbar: true,
                /**
                 * Workaround for disabling checklist option from @editorjs/list (as we already use checklist from @editorjs/checklist).
                 * Not disabling it results in two checklist instances in the toolbox.
                 * Additionally, the current note progress bar is only compatible with the @editorjs/checklist plugin.
                 * @see GitHub issue / workaround https://github.com/editor-js/list/issues/119
                 */
                toolbox: [
                    {
                        title: 'Ordered List',
                        data: {
                            style: 'ordered',
                        }
                    },
                    {
                        title: 'Unordered List',
                        data: {
                            style: 'unordered',
                        }
                    }
                ]
            },
            code: {
                class: Code,
                inlineToolbar: true
            },
            delimiter: {
                class: Delimiter
            },
            table: {
                class: Table,
                inlineToolbar: true
            }
        },
        onReady: () => {
            // The body does not exist until this fires, so the placeholder stays up until
            // here instead of leaving the reader staring at an empty note.
            placeholder?.remove()
        },
        onChange: () => {
            autoSave()
        }
    })
}

const initEditor = () => {
    const container = document.getElementById('editorjs')

    if (!container) {
        destroyEditor()
        return
    }

    // Turbolinks swaps in a fresh node on every visit. Same node means the editor we
    // already built is still live.
    if (container === attachedTo) {
        return
    }

    destroyEditor()
    attachedTo = container
    editor = buildEditor(container, document.getElementById('editor-placeholder'))
}

// Covers the visit during which this module is first fetched: turbolinks:load has usually
// already fired by the time the module finishes loading.
initEditor()

// Covers every visit after that.
document.addEventListener('turbolinks:load', initEditor)
