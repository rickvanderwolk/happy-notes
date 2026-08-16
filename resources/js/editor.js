import EditorJS from '@editorjs/editorjs'
import Header from '@editorjs/header'
import Checklist from '@editorjs/checklist'
import List from '@editorjs/list'
import Code from '@editorjs/code'
import Delimiter from '@editorjs/delimiter'
import Table from '@editorjs/table'

const editorContainer = document.getElementById('editorjs')

// This module is loaded deferred, so a navigation can complete before it runs. In that
// case we are already on another page and there is nothing to attach the editor to.
if (editorContainer) {
    const saveBodyUrl = editorContainer.dataset.saveBodyUrl
    const initialData = JSON.parse(editorContainer.dataset.initialData)

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
            }).then(response => {
                Livewire.dispatch('noteUpdated');
            })
        })
    }

    const editor = new EditorJS({
        holder: 'editorjs',
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
        onChange: () => {
            autoSave()
        }
    })
}
