/**
 * Turns saved Editor.js blocks into plain text that reads well wherever it is pasted:
 * lists keep their markers and nesting, checklists become Markdown tasks and tables
 * are separated by tabs, so they land in columns in a spreadsheet.
 */

const parser = new DOMParser();

const htmlToText = (html) => {
    const withBreaks = String(html ?? '').replace(/<br\s*\/?>/gi, '\n');

    return parser.parseFromString(withBreaks, 'text/html').body.textContent.replace(/\u00a0/g, ' ').trim();
};

const listLines = (items, ordered, depth, start = 1) => items.flatMap((item, index) => {
    const marker = ordered ? `${start + index}.` : '-';
    const line = `${'    '.repeat(depth)}${marker} ${htmlToText(item.content)}`;

    return [line, ...listLines(item.items ?? [], ordered, depth + 1)];
});

const blockText = ({ type, data }) => {
    switch (type) {
        case 'paragraph':
        case 'header':
            return htmlToText(data.text);
        case 'list':
            return listLines(data.items ?? [], data.style === 'ordered', 0, data.meta?.start ?? 1).join('\n');
        case 'checklist':
            return (data.items ?? []).map((item) => `- [${item.checked ? 'x' : ' '}] ${htmlToText(item.text)}`).join('\n');
        case 'table':
            return (data.content ?? []).map((row) => row.map(htmlToText).join('\t')).join('\n');
        case 'code':
            return data.code ?? '';
        case 'delimiter':
            return '---';
        default:
            return htmlToText(data?.text ?? '');
    }
};

export const blocksToText = (blocks) => blocks
    .map(blockText)
    .filter((text) => text !== '')
    .join('\n\n');

/**
 * Safari only lets a page write to the clipboard during the tap itself. A ClipboardItem
 * may take a promise, so the text can still be put together after the tap.
 */
export const copyText = (textPromise) => {
    if (window.ClipboardItem && navigator.clipboard?.write) {
        const blob = Promise.resolve(textPromise).then((text) => new Blob([text], { type: 'text/plain' }));

        return navigator.clipboard.write([new ClipboardItem({ 'text/plain': blob })]);
    }

    return Promise.resolve(textPromise).then((text) => navigator.clipboard.writeText(text));
};
