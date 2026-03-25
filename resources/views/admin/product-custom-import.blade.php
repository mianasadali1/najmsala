<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Products</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6">

<div class="max-w-7xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Import Products from Excel</h1>

    <!-- Step 1: Upload & Preview -->
    <div id="step-upload" class="bg-white rounded-xl shadow p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Step 1: Select File &amp; Preview</h2>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-600 mb-1">Excel File (.xlsx / .xls)</label>
            <input type="file" id="fileInput" accept=".xlsx,.xls"
                class="block w-full text-sm text-gray-700 border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            <p class="mt-1 text-xs text-gray-400">Image URLs from the file are stored as links — no downloading.</p>
        </div>

        <button id="btnPreview"
            class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2 rounded-lg transition disabled:opacity-50"
            onclick="doPreview()">
            Preview
        </button>
        <span id="previewSpinner" class="hidden ml-3 text-blue-600 text-sm">Loading preview…</span>
        <div id="previewError" class="hidden mt-3 text-red-600 text-sm font-medium"></div>
    </div>

    <!-- Step 2: Preview Table -->
    <div id="step-preview" class="hidden bg-white rounded-xl shadow p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-700">
                Step 2: Preview
                <span id="previewCount" class="ml-2 text-sm font-normal text-gray-500"></span>
            </h2>
            <button id="btnImport"
                class="bg-green-600 hover:bg-green-700 text-white font-medium px-6 py-2 rounded-lg transition"
                onclick="doImport()">
                Import Now
            </button>
        </div>
        <span id="importSpinner" class="hidden text-green-700 text-sm">Importing… this may take a while.</span>

        <div id="importResult" class="hidden mb-4 p-3 rounded-lg text-sm font-medium"></div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-xs border-collapse">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 uppercase text-left">
                        <th class="px-3 py-2 border">#</th>
                        <th class="px-3 py-2 border">Status</th>
                        <th class="px-3 py-2 border">Thumbnail</th>
                        <th class="px-3 py-2 border">Name</th>
                        <th class="px-3 py-2 border">Category</th>
                        <th class="px-3 py-2 border">Brand</th>
                        <th class="px-3 py-2 border">Price</th>
                        <th class="px-3 py-2 border">Disc. Price</th>
                        <th class="px-3 py-2 border">Stock</th>
                        <th class="px-3 py-2 border">Size</th>
                        <th class="px-3 py-2 border">Color</th>
                        <th class="px-3 py-2 border">Tag</th>
                        <th class="px-3 py-2 border">Availability</th>
                        <th class="px-3 py-2 border">Images</th>
                        <th class="px-3 py-2 border">Notes</th>
                    </tr>
                </thead>
                <tbody id="previewTableBody" class="text-gray-700 divide-y"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const API_URL = "{{ $appUrl }}/api";
    const API_KEY = "{{ $apiKey }}";

    function apiHeaders() {
        return {
            'Accept': 'application/json',
            'x-api-key': API_KEY,
        };
    }

    let lastFile = null;

    async function doPreview() {
        const fileInput = document.getElementById('fileInput');
        if (!fileInput.files.length) {
            showPreviewError('Please select an Excel file.');
            return;
        }
        lastFile = fileInput.files[0];
        clearPreviewError();

        document.getElementById('btnPreview').disabled = true;
        document.getElementById('previewSpinner').classList.remove('hidden');
        document.getElementById('step-preview').classList.add('hidden');

        try {
            const fd = new FormData();
            fd.append('file', lastFile);

            const res = await fetch(`${API_URL}/product-import/preview`, {
                method: 'POST',
                headers: apiHeaders(),
                body: fd,
            });

            const json = await res.json();

            if (!res.ok) {
                const msg = json.message || json.errors ? Object.values(json.errors || {}).flat().join(', ') : 'Request failed.';
                showPreviewError(msg);
                return;
            }

            renderPreviewTable(json.data);
            document.getElementById('step-preview').classList.remove('hidden');
        } catch (e) {
            showPreviewError('Network error: ' + e.message);
        } finally {
            document.getElementById('btnPreview').disabled = false;
            document.getElementById('previewSpinner').classList.add('hidden');
        }
    }

    async function doImport() {
        if (!lastFile) { alert('Please preview first.'); return; }

        document.getElementById('btnImport').disabled = true;
        document.getElementById('importSpinner').classList.remove('hidden');
        document.getElementById('importResult').classList.add('hidden');

        try {
            const fd = new FormData();
            fd.append('file', lastFile);

            const res = await fetch(`${API_URL}/product-import/import`, {
                method: 'POST',
                headers: apiHeaders(),
                body: fd,
            });

            const json = await res.json();

            if (!res.ok) {
                showImportResult('error', json.message || 'Import failed.');
                return;
            }

            renderPreviewTable(json.data);
            showImportResult('success',
                `Import complete — ${json.imported} imported, ${json.skipped} skipped.`
            );
        } catch (e) {
            showImportResult('error', 'Network error: ' + e.message);
        } finally {
            document.getElementById('btnImport').disabled = false;
            document.getElementById('importSpinner').classList.add('hidden');
        }
    }

    function renderPreviewTable(rows) {
        const tbody = document.getElementById('previewTableBody');
        tbody.innerHTML = '';

        const valid   = rows.filter(r => r.status === 'valid' || r.status === 'imported').length;
        const errored = rows.filter(r => r.status === 'error').length;
        document.getElementById('previewCount').textContent =
            `${rows.length} rows — ${valid} valid, ${errored} with errors`;

        rows.forEach((row, i) => {
            const tr = document.createElement('tr');
            tr.className = row.status === 'error' ? 'bg-red-50' :
                           row.status === 'imported' ? 'bg-green-50' : '';

            const statusBadge = statusBadgeHtml(row.status);
            const thumbUrl    = row.thumbnail || row.image1 || '';
            const thumbHtml   = thumbUrl
                ? `<img src="${escHtml(thumbUrl)}" class="w-12 h-12 object-cover rounded" onerror="this.style.display='none'">`
                : '<span class="text-gray-400">—</span>';

            const images = [row.image1, row.image2].filter(Boolean).map(u =>
                `<a href="${escHtml(u)}" target="_blank" class="text-blue-500 underline block truncate max-w-[120px]">${escHtml(u)}</a>`
            ).join('');

            const errors   = (row.errors   || []).join('; ');
            const warnings = (row.warnings || []).join('; ');
            const message  = row.message   || '';
            const notes    = [errors, warnings, message].filter(Boolean).join(' | ');

            tr.innerHTML = `
                <td class="px-3 py-2 border text-gray-500">${row._row || i + 2}</td>
                <td class="px-3 py-2 border">${statusBadge}</td>
                <td class="px-3 py-2 border">${thumbHtml}</td>
                <td class="px-3 py-2 border font-medium">${escHtml(row.name || '')}</td>
                <td class="px-3 py-2 border">${escHtml(row.category || '')}</td>
                <td class="px-3 py-2 border">${escHtml(row.brand || '')}</td>
                <td class="px-3 py-2 border">${escHtml(row.price || '')}</td>
                <td class="px-3 py-2 border">${escHtml(row.discounted_price || '')}</td>
                <td class="px-3 py-2 border">${escHtml(row.stock || '')}</td>
                <td class="px-3 py-2 border">${escHtml(row.size || '')}</td>
                <td class="px-3 py-2 border">${escHtml(row.color || '')}</td>
                <td class="px-3 py-2 border">${escHtml(row.tag || '')}</td>
                <td class="px-3 py-2 border">${escHtml(row.availability || '')}</td>
                <td class="px-3 py-2 border">${images || '<span class="text-gray-400">—</span>'}</td>
                <td class="px-3 py-2 border text-red-600">${escHtml(notes)}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    function statusBadgeHtml(status) {
        const map = {
            valid:    'bg-blue-100 text-blue-700',
            imported: 'bg-green-100 text-green-700',
            error:    'bg-red-100 text-red-700',
        };
        const cls = map[status] || 'bg-gray-100 text-gray-600';
        return `<span class="px-2 py-0.5 rounded text-xs font-semibold ${cls}">${status}</span>`;
    }

    function showPreviewError(msg) {
        const el = document.getElementById('previewError');
        el.textContent = msg;
        el.classList.remove('hidden');
    }

    function clearPreviewError() {
        document.getElementById('previewError').classList.add('hidden');
    }

    function showImportResult(type, msg) {
        const el = document.getElementById('importResult');
        el.textContent = msg;
        el.className = type === 'success'
            ? 'mb-4 p-3 rounded-lg text-sm font-medium bg-green-100 text-green-800'
            : 'mb-4 p-3 rounded-lg text-sm font-medium bg-red-100 text-red-700';
        el.classList.remove('hidden');
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
</script>
</body>
</html>
