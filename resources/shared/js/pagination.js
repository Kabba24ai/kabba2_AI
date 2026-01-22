//  Paginator Utility
// -------------------------------
window.Paginator = (function () {
    const instances = [];

    function init({ wrapper, fetchCallback }) {
        if (!wrapper || typeof fetchCallback !== "function") {
            console.error("Paginator.init requires { wrapper, fetchCallback }");
            return;
        }
        instances.push({ wrapper, fetchCallback });
    }

    // Helper: get per_page value for this wrapper only
    function getPerPage(wrapper) {
        const select = wrapper.querySelector('[data-per-page]');
        return select ? select.value : null;
    }

    // Pagination click
    function handlePaginationClick(e) {
        const link = e.target.closest('nav[aria-label="Pagination Navigation"] a');
        if (!link) return;

        // find which wrapper this link belongs to
        const instance = instances.find(i => i.wrapper.contains(link));
        if (!instance) return;

        e.preventDefault();

        const url  = new URL(link.href, window.location.origin);
        const page = parseInt(url.searchParams.get("page") || "1", 10);
        const perPage = getPerPage(instance.wrapper);

        // page + per_page go to the correct fetch function
        instance.fetchCallback(page, perPage);
    }

    // Per-page change (capture to block inline this.form.submit())
    function handlePerPageChange(e) {
        const select = e.target.closest('[data-per-page]');
        if (!select) return;

        // find which wrapper this select belongs to
        const instance = instances.find(i => i.wrapper.contains(select));
        if (!instance) return;

        e.preventDefault();
        e.stopPropagation();

        const perPage = select.value;

        // reset to page 1 for that specific table
        instance.fetchCallback(1, perPage);
    }

    // Attach listeners ONCE (works for all tables)
    document.addEventListener("click", handlePaginationClick);
    document.addEventListener("change", handlePerPageChange, true);

    return { init };
})();
