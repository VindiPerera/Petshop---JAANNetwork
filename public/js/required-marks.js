/* Adds a red * to the label of every required field. Runs once on load. */
(function () {
    function mark() {
        document.querySelectorAll('input[required], select[required], textarea[required]').forEach(function (el) {
            var label = null;
            if (el.id) label = document.querySelector('label[for="' + el.id + '"]');
            if (!label) { var p = el.previousElementSibling; while (p && !(p.tagName === 'LABEL')) p = p.previousElementSibling; label = p; }
            if (!label && el.parentElement) label = el.parentElement.querySelector(':scope > label');
            if (!label && el.parentElement && el.parentElement.parentElement) label = el.parentElement.parentElement.querySelector(':scope > label');
            if (label) label.classList.add('req');
            var td = el.closest('td');
            if (td) { var th = td.closest('table').querySelectorAll('thead th')[td.cellIndex]; if (th) th.classList.add('req'); }
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mark); else mark();
})();
