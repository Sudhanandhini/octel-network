<?php
/**
 * WordPress-style visual block editor for the "Custom Page Content" section
 * of the item edit form. Expects $record['content_blocks'] (array) to be set.
 * Renders its own toolbar + block list + hidden input named "content_blocks"
 * that the surrounding <form> submits as a JSON string.
 */
$initialBlocks = is_array($record['content_blocks'] ?? null) ? $record['content_blocks'] : [];
?>
<label>Custom page content
    <span class="hint">Optional. Add extra sections — headings, paragraphs, images, buttons, tables, or multi-column rows — shown below the main content on the detail page.</span>
</label>
<div class="blocks-toolbar">
    <button type="button" class="small-btn" onclick="blocksAddTop('heading')"><i class="ri-heading"></i> Heading</button>
    <button type="button" class="small-btn" onclick="blocksAddTop('paragraph')"><i class="ri-paragraph"></i> Paragraph</button>
    <button type="button" class="small-btn" onclick="blocksAddTop('image')"><i class="ri-image-line"></i> Image</button>
    <button type="button" class="small-btn" onclick="blocksAddTop('button')"><i class="ri-cursor-line"></i> Button</button>
    <button type="button" class="small-btn" onclick="blocksAddTop('table')"><i class="ri-table-line"></i> Table</button>
    <button type="button" class="small-btn" onclick="blocksAddTop('row2')"><i class="ri-layout-column-line"></i> Row (2 columns)</button>
    <button type="button" class="small-btn" onclick="blocksAddTop('row3')"><i class="ri-layout-column-line"></i> Row (3 columns)</button>
</div>
<div id="blocksEditor" class="blocks-editor"></div>
<textarea id="content_blocks_input" name="content_blocks" style="display:none"></textarea>

<script>
(function () {
    let blocks;
    try {
        blocks = <?= json_encode($initialBlocks, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        if (!Array.isArray(blocks)) blocks = [];
    } catch (e) {
        blocks = [];
    }

    function genId() {
        return 'b' + Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
    }

    // Every block needs a stable id so its file upload input (image blocks)
    // keeps a fixed name across reorders/re-renders. Backfill ids for blocks
    // saved before this existed.
    function ensureIds(list) {
        list.forEach(b => {
            if (!b.id) b.id = genId();
            if (b.type === 'row' && Array.isArray(b.columns)) {
                b.columns.forEach(ensureIds);
            }
        });
    }
    ensureIds(blocks);

    function newBlock(kind) {
        switch (kind) {
            case 'heading': return { id: genId(), type: 'heading', level: 'h2', text: 'New heading' };
            case 'paragraph': return { id: genId(), type: 'paragraph', text: '' };
            case 'image': return { id: genId(), type: 'image', src: '', alt: '', caption: '' };
            case 'button': return { id: genId(), type: 'button', text: 'Learn More', href: '#' };
            case 'table': return { id: genId(), type: 'table', header: true, text: 'Column A | Column B\nRow 1 | Row 1' };
            case 'row2': return { id: genId(), type: 'row', columns: [[], []] };
            case 'row3': return { id: genId(), type: 'row', columns: [[], [], []] };
        }
    }

    function blockLabel(type) {
        return { heading: 'Heading', paragraph: 'Paragraph', image: 'Image', button: 'Button', table: 'Table', row: 'Row' }[type] || type;
    }

    function resolveArray(addr) {
        if (addr.length === 1) return { arr: blocks, idx: addr[0] };
        const rowI = addr[0], colI = addr[1], blockI = addr[2];
        return { arr: blocks[rowI].columns[colI], idx: blockI };
    }

    window.blocksAdd = function (arr, kind) {
        arr.push(newBlock(kind));
        render();
    };

    // Inline onclick="" attributes run in global scope, so they can't see the
    // `blocks` variable closed over by this IIFE — route top-level toolbar
    // clicks through this global wrapper instead.
    window.blocksAddTop = function (kind) {
        blocksAdd(blocks, kind);
    };

    function moveBlock(addr, dir) {
        const { arr, idx } = resolveArray(addr);
        const newIdx = idx + dir;
        if (newIdx < 0 || newIdx >= arr.length) return;
        const tmp = arr[idx];
        arr[idx] = arr[newIdx];
        arr[newIdx] = tmp;
        render();
    }

    function removeBlock(addr) {
        const { arr, idx } = resolveArray(addr);
        arr.splice(idx, 1);
        render();
    }

    function el(tag, className, text) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function field(labelText, inputEl) {
        const wrap = el('div', 'block-field');
        wrap.appendChild(el('label', null, labelText));
        wrap.appendChild(inputEl);
        return wrap;
    }

    function textInput(value, onInput) {
        const inp = document.createElement('input');
        inp.type = 'text';
        inp.value = value || '';
        inp.oninput = () => onInput(inp.value);
        return inp;
    }

    function textareaInput(value, rows, onInput) {
        const ta = document.createElement('textarea');
        ta.rows = rows || 3;
        ta.value = value || '';
        ta.oninput = () => onInput(ta.value);
        return ta;
    }

    function iconBtn(icon, title, onClick) {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'icon-btn';
        b.title = title;
        b.innerHTML = '<i class="' + icon + '"></i>';
        b.onclick = onClick;
        return b;
    }

    function renderBlock(block, addr) {
        const card = el('div', 'block-card block-' + block.type);

        const head = el('div', 'block-card-head');
        head.appendChild(el('span', 'block-type-tag', blockLabel(block.type)));
        const controls = el('div', 'block-controls');
        controls.appendChild(iconBtn('ri-arrow-up-line', 'Move up', () => moveBlock(addr, -1)));
        controls.appendChild(iconBtn('ri-arrow-down-line', 'Move down', () => moveBlock(addr, 1)));
        controls.appendChild(iconBtn('ri-delete-bin-line', 'Remove', () => { if (confirm('Remove this block?')) removeBlock(addr); }));
        head.appendChild(controls);
        card.appendChild(head);

        const body = el('div', 'block-card-body');

        if (block.type === 'heading') {
            body.appendChild(field('Text', textInput(block.text, v => { block.text = v; sync(); })));
            const sel = document.createElement('select');
            ['h2', 'h3', 'h4'].forEach(o => {
                const opt = document.createElement('option');
                opt.value = o; opt.textContent = o.toUpperCase();
                if (o === block.level) opt.selected = true;
                sel.appendChild(opt);
            });
            sel.onchange = () => { block.level = sel.value; sync(); };
            body.appendChild(field('Size', sel));
        } else if (block.type === 'paragraph') {
            body.appendChild(field('Text', textareaInput(block.text, 3, v => { block.text = v; sync(); })));
        } else if (block.type === 'image') {
            if (block.src) {
                const preview = document.createElement('img');
                preview.src = '../' + block.src;
                preview.className = 'current-image';
                body.appendChild(preview);
            }
            body.appendChild(field('Image path (e.g. assets/img/uploads/...)', textInput(block.src, v => { block.src = v; sync(); })));
            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = 'image/*';
            fileInput.name = 'content_block_image_' + block.id;
            body.appendChild(field('...or upload a new image (JPG, PNG, WEBP or GIF, up to 5MB — replaces the path above)', fileInput));
            body.appendChild(field('Alt text', textInput(block.alt, v => { block.alt = v; sync(); })));
            body.appendChild(field('Caption (optional)', textInput(block.caption, v => { block.caption = v; sync(); })));
        } else if (block.type === 'button') {
            body.appendChild(field('Button text', textInput(block.text, v => { block.text = v; sync(); })));
            body.appendChild(field('Link URL', textInput(block.href, v => { block.href = v; sync(); })));
        } else if (block.type === 'table') {
            const row = el('label', 'checkbox-row');
            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.checked = !!block.header;
            cb.onchange = () => { block.header = cb.checked; sync(); };
            row.appendChild(cb);
            row.appendChild(document.createTextNode(' First row is a header'));
            body.appendChild(row);
            body.appendChild(field('Rows (one per line, cells separated by |)', textareaInput(block.text, 5, v => { block.text = v; sync(); })));
        } else if (block.type === 'row') {
            const cols = el('div', 'block-row-columns');
            cols.style.gridTemplateColumns = 'repeat(' + block.columns.length + ', 1fr)';
            block.columns.forEach((colBlocks, colI) => {
                const colEl = el('div', 'block-column');
                colEl.appendChild(el('div', 'block-column-head', 'Column ' + (colI + 1)));
                colBlocks.forEach((cb2, blockI) => {
                    colEl.appendChild(renderBlock(cb2, [addr[0], colI, blockI]));
                });
                const toolbar = el('div', 'block-mini-toolbar');
                ['heading', 'paragraph', 'image', 'button', 'table'].forEach(t => {
                    const b = el('button', 'small-btn', '+ ' + blockLabel(t));
                    b.type = 'button';
                    b.onclick = () => blocksAdd(colBlocks, t);
                    toolbar.appendChild(b);
                });
                colEl.appendChild(toolbar);
                cols.appendChild(colEl);
            });
            body.appendChild(cols);
        }

        card.appendChild(body);
        return card;
    }

    function sync() {
        document.getElementById('content_blocks_input').value = JSON.stringify(blocks);
    }

    function render() {
        const root = document.getElementById('blocksEditor');

        // A structural change (add/move/remove) rebuilds every block card from
        // scratch, which would silently drop any file the user already picked
        // in an image block's upload input — carry selected FileLists over by id.
        const savedFiles = {};
        root.querySelectorAll('input[type=file]').forEach(inp => {
            if (inp.files && inp.files.length) savedFiles[inp.name] = inp.files;
        });

        root.innerHTML = '';
        if (blocks.length === 0) {
            root.appendChild(el('p', 'blocks-empty', 'No custom blocks yet. Use the buttons above to add content.'));
        } else {
            blocks.forEach((b, i) => root.appendChild(renderBlock(b, [i])));
        }

        root.querySelectorAll('input[type=file]').forEach(inp => {
            if (savedFiles[inp.name]) inp.files = savedFiles[inp.name];
        });

        sync();
    }

    render();
})();
</script>
