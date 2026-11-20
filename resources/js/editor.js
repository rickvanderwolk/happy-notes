import EditorJS from '@editorjs/editorjs'
import Header from '@editorjs/header'
import Checklist from '@editorjs/checklist'
import List from '@editorjs/list'
import Code from '@editorjs/code'
import Delimiter from '@editorjs/delimiter'
import Table from '@editorjs/table'
import Undo from 'editorjs-undo'
import { blocksToText, copyText } from './note-text'

/**
 * This module is evaluated only once per browser session, because that is how ES modules
 * work: page navigation re-inserts the script tag on every visit, but the browser will not
 * run it again. So the editor is built from a livewire:navigated listener rather than at module
 * scope, otherwise opening a second note leaves an empty body until a hard refresh.
 */

let editor = null
let undo = null
let attachedTo = null
// The last saved body, so a copy can be answered right away; a copy event cannot wait for
// the editor to save.
let latestData = null

const copyIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 0 1 2-2h9"/></svg>'

// "Copy" in the menu of every block, so a single list can be copied on a phone as well
class CopyTune {
    static get isTune() {
        return true
    }

    constructor({ block }) {
        this.block = block
    }

    render() {
        return {
            icon: copyIcon,
            title: 'Copy',
            closeOnActivate: true,
            onActivate: () => {
                copyText(this.block.save().then((saved) => blocksToText([{ type: saved.tool, data: saved.data }])))
            },
        }
    }
}

// Same check EditorJS uses; iPads report themselves as a Mac with a touch screen.
const isIOS = /iP(ad|hone|od)/.test(navigator.platform)
    || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)

const itemContent = (item) => item.querySelector(':scope > .cdx-list__item-content')

const currentListItem = () => {
    const selection = window.getSelection()

    if (!selection?.isCollapsed || !selection.anchorNode) {
        return null
    }

    const node = selection.anchorNode
    const element = node.nodeType === Node.ELEMENT_NODE ? node : node.parentElement

    return element?.closest('.cdx-list__item-content')?.closest('.cdx-list__item') ?? null
}

const textBeforeCaret = (item) => {
    const range = window.getSelection().getRangeAt(0)
    const before = document.createRange()
    before.selectNodeContents(itemContent(item))
    before.setEnd(range.startContainer, range.startOffset)

    return before.toString()
}

const removeLeadingWhitespace = (element, count) => {
    const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT)

    while (count > 0 && walker.nextNode()) {
        const node = walker.currentNode

        while (count > 0 && /^[ \u00a0]/.test(node.data)) {
            node.data = node.data.slice(1)
            count--
        }
    }
}

const pressTab = (content, shiftKey) => {
    content.dispatchEvent(new KeyboardEvent('keydown', { key: 'Tab', shiftKey, bubbles: true, cancelable: true }))
}

const placeCaretAtStart = (element) => {
    const range = document.createRange()
    range.setStart(element, 0)
    range.collapse(true)

    const selection = window.getSelection()
    selection.removeAllRanges()
    selection.addRange(range)
}

const destroyEditor = () => {
    try {
        undo?.onDestroy()
        editor?.destroy?.()
    } catch {
        // The old DOM is already gone after a page swap; nothing to clean up.
    }
    editor = null
    undo = null
    attachedTo = null
    latestData = null
}

const buildEditor = (container, placeholder) => {
    const saveBodyUrl = container.dataset.saveBodyUrl
    const initialData = JSON.parse(container.dataset.initialData)
    latestData = initialData

    const autoSave = () => {
        editor.save().then((outputData) => {
            latestData = outputData
            fetch(saveBodyUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    // Asks the controller for JSON instead of a redirect to the note page,
                    // which the browser would otherwise follow and download on every save.
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ body: outputData })
            })
                .then((response) => response.ok ? response.json() : null)
                .then((data) => {
                    if (!data) return
                    window.dispatchEvent(new CustomEvent('note-progress-updated', {
                        detail: { progress: data.progress }
                    }))
                })
        })
    }

    /**
     * After "? " (or ". ", "! ") the iOS keyboard turns Shift on to capitalize the next sentence,
     * and the Enter that follows arrives with shiftKey set. The list plugin reads that as
     * Shift+Enter and puts a line break in the item instead of starting a new one. EditorJS
     * ignores this fake Shift on iOS itself, the list plugin does not, so drop it before the
     * plugin sees the event.
     * @see https://github.com/codex-team/editor.js/pull/2696
     */
    if (isIOS) {
        container.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && event.shiftKey) {
                Object.defineProperty(event, 'shiftKey', { value: false })
            }
        }, true)
    }

    /**
     * The list plugin only nests items with Tab and Shift+Tab, which the iOS keyboard does
     * not have. Two spaces at the start of an item indent it, and Backspace at the start of
     * a nested item outdents it before it would merge with the item above. Both hand a Tab
     * keydown to the plugin, so its own nesting code does the work.
     */
    container.addEventListener('input', () => {
        const item = currentListItem()

        if (!item || !item.previousElementSibling || !/^[ \u00a0]{2}$/.test(textBeforeCaret(item))) {
            return
        }

        const content = itemContent(item)
        removeLeadingWhitespace(content, 2)
        pressTab(content, false)
        placeCaretAtStart(content)
    })

    container.addEventListener('keydown', (event) => {
        if (event.key !== 'Backspace') {
            return
        }

        const item = currentListItem()

        if (!item || !item.parentElement.closest('.cdx-list__item') || textBeforeCaret(item) !== '') {
            return
        }

        event.preventDefault()
        event.stopPropagation()
        const content = itemContent(item)
        pressTab(content, true)
        placeCaretAtStart(content)
    }, true)

    const instance = new EditorJS({
        holder: container,
        data: initialData,
        tunes: ['copy'],
        tools: {
            copy: CopyTune,
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

            // Leaving the note before the editor is ready destroys it first; there is nothing
            // left to attach undo to then.
            if (instance !== editor) {
                return
            }

            // EditorJS has no undo of its own; Ctrl+Z is the browser's, which cannot reverse
            // block changes such as a checklist item turning into a paragraph on backspace.
            undo = new Undo({ editor: instance })

            // Without this the first undo step would be an empty note. A new note has no body yet.
            if (initialData) {
                undo.initialize(initialData)
            }
        },
        onChange: () => {
            autoSave()
        }
    })

    return instance
}

const initEditor = () => {
    const container = document.getElementById('editorjs')

    if (!container) {
        destroyEditor()
        return
    }

    // Navigation swaps in a fresh node on every visit. Same node means the editor we
    // already built is still live.
    if (container === attachedTo) {
        return
    }

    destroyEditor()
    attachedTo = container
    editor = buildEditor(container, document.getElementById('editor-placeholder'))
}

// The copy button in the pill puts the whole note on the clipboard
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-copy-note]')

    if (!button || !editor) {
        return
    }

    copyText(editor.save().then((data) => blocksToText(data.blocks))).then(() => {
        button.classList.add('is-copied')
        button.setAttribute('aria-label', 'Copied')

        setTimeout(() => {
            button.classList.remove('is-copied')
            button.setAttribute('aria-label', 'Copy note')
        }, 1500)
    })
})

/**
 * Blocks selected with the mouse are copied by EditorJS as the bare text of each block,
 * which glues list items together. This runs after it and replaces that text.
 */
window.addEventListener('copy', (event) => {
    const selected = [...document.querySelectorAll('#editorjs .ce-block--selected')]

    if (!latestData || selected.length === 0) {
        return
    }

    const blocks = selected.map((element) => latestData.blocks.find((block) => block.id === element.dataset.id))

    if (blocks.includes(undefined)) {
        return
    }

    event.clipboardData.setData('text/plain', blocksToText(blocks))
})

// Covers the visit during which this module is first fetched: livewire:navigated has usually
// already fired by the time the module finishes loading.
initEditor()

// Covers every visit after that.
document.addEventListener('livewire:navigated', initEditor)
