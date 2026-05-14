/**
 * sortable-table.js — Lightweight client-side sortable tables
 * Attach to any <table class="data-table" data-sortable>
 *
 * Rules:
 *  - Column #0 (the # row number column) is NEVER sortable
 *  - Columns whose header text is "#" or contains only an action (no plain text value
 *    that differs per row, like "Assign Agency") skip the sorter automatically
 *    via the  data-no-sort  attribute on <th>
 */

(function () {
    'use strict';

    function cellText(cell) {
        // Try to get a numeric value first (strip non-numeric chars except dot)
        const raw = cell.textContent.trim();
        // Convert Bengali digits to ASCII for numeric compare
        const ascii = raw.replace(/[০-৯]/g, d => '০১২৩৪৫৬৭৮৯'.indexOf(d));
        const num = parseFloat(ascii.replace(/[^0-9.]/g, ''));
        return isNaN(num) ? raw.toLowerCase() : num;
    }

    function sortTable(table, colIdx, asc) {
        const tbody = table.tBodies[0];
        if (!tbody) return;

        const rows = Array.from(tbody.querySelectorAll('tr'));
        rows.sort((a, b) => {
            const cellA = a.cells[colIdx];
            const cellB = b.cells[colIdx];
            if (!cellA || !cellB) return 0;
            const va = cellText(cellA);
            const vb = cellText(cellB);
            if (typeof va === 'number' && typeof vb === 'number') return asc ? va - vb : vb - va;
            return asc ? String(va).localeCompare(String(vb)) : String(vb).localeCompare(String(va));
        });

        rows.forEach(row => tbody.appendChild(row));

        // Highlight sorted column cells
        const allCells = tbody.querySelectorAll('td');
        allCells.forEach(td => td.classList.remove('sorted-col'));
        rows.forEach(row => {
            if (row.cells[colIdx]) row.cells[colIdx].classList.add('sorted-col');
        });
    }

    function initTable(table) {
        const ths = table.querySelectorAll('thead th');
        ths.forEach((th, idx) => {
            // Skip: first column (#), columns with data-no-sort, or action columns
            if (idx === 0) return;
            if (th.hasAttribute('data-no-sort')) return;
            if (th.textContent.trim() === '#') return;

            th.setAttribute('data-sort-col', idx);
            let asc = true;

            th.addEventListener('click', () => {
                // Remove sort classes from siblings
                ths.forEach(t => { t.classList.remove('sort-asc', 'sort-desc'); });
                th.classList.add(asc ? 'sort-asc' : 'sort-desc');
                sortTable(table, idx, asc);
                asc = !asc;
            });
        });
    }

    // Init on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('table.data-table[data-sortable]').forEach(initTable);
    });
})();
