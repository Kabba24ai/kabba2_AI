// Enforces "pick exactly one" behavior for grouped product options: checking
// an item that belongs to a group unchecks any other item sharing that same
// group id (radio-style), even though the inputs stay <input type="checkbox">
// because a single item can belong to more than one group.
export function initProductOptionGroups() {
    const optionGrid = document.getElementById('optionDiv');
    const modal = document.getElementById('modalOptionGroupAlert');
    const closeBtn = document.getElementById('optionGroupAlertCloseBtn');

    if (!optionGrid) return;

    function groupIdsOf(checkbox) {
        return (checkbox.dataset.groupIds || '')
            .split(',')
            .filter(Boolean);
    }

    optionGrid.addEventListener('change', function(e) {
        const checkbox = e.target.closest('input[type="checkbox"].form-checkbox');
        if (!checkbox) return;

        const groupIds = groupIdsOf(checkbox);
        if (!groupIds.length || !checkbox.checked) return;

        groupIds.forEach(groupId => {
            optionGrid.querySelectorAll('input[type="checkbox"].form-checkbox[data-group-ids]').forEach(other => {
                if (other !== checkbox && groupIdsOf(other).includes(groupId)) {
                    other.checked = false;
                }
            });
        });

        // If the modal is currently showing this group's alert, close it now that it's resolved.
        if (modal && !modal.classList.contains('hidden') && groupIds.includes(modal.dataset.groupId)) {
            modal.classList.add('hidden');
        }
    });

    function closeModal() {
        if (modal) modal.classList.add('hidden');
    }

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
    }
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });
}

/**
 * Checks every option group with at least one rendered member for a required
 * selection. If any group has none checked, shows the shared alert modal
 * (with that group's message/image) and returns the list of unresolved group
 * ids; returns an empty array when every group is satisfied.
 */
export function validateRequiredOptionGroups() {
    const unresolvedGroupIds = [];

    document.querySelectorAll('.option-group-source[data-group-id]').forEach(source => {
        const groupId = source.dataset.groupId;
        const hasSelection = Array.from(
            document.querySelectorAll('#optionDiv input[type="checkbox"].form-checkbox[data-group-ids]')
        ).some(cb => cb.checked && (cb.dataset.groupIds || '').split(',').includes(groupId));

        if (!hasSelection) {
            unresolvedGroupIds.push(groupId);
        }
    });

    if (unresolvedGroupIds.length) {
        const source = document.querySelector(`.option-group-source[data-group-id="${unresolvedGroupIds[0]}"]`);
        const modal = document.getElementById('modalOptionGroupAlert');
        const modalImage = document.getElementById('optionGroupAlertImage');
        const modalMessage = document.getElementById('optionGroupAlertMessage');

        if (source && modal && modalImage && modalMessage) {
            modal.dataset.groupId = unresolvedGroupIds[0];
            modalImage.src = source.dataset.image || '';
            modalMessage.innerHTML = source.innerHTML;
            modal.classList.remove('hidden');
        }
    }

    return unresolvedGroupIds;
}
