const TABLE_SELECTOR = 'table[data-sequential-rows]';

function renumberDataTable(table) {
    const $ = window.jQuery;
    const api = $(table).DataTable();
    const start = api.page.info().start;

    api.column(0, { page: 'current', order: 'applied', search: 'applied' })
        .nodes()
        .each((cell, index) => {
            cell.textContent = String(start + index + 1);
        });
}

export function initSequentialRowNumbers() {
    const $ = window.jQuery;

    if (! $?.fn?.dataTable) return;

    $(document).on('draw.dt', TABLE_SELECTOR, function () {
        renumberDataTable(this);
    });

    document.querySelectorAll(TABLE_SELECTOR).forEach((table) => {
        if ($.fn.dataTable.isDataTable(table)) {
            renumberDataTable(table);
        }
    });
}
