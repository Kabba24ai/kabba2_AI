// Enforces "pick exactly one" behavior for grouped product options: checking
// an item that belongs to a group unchecks any other item sharing that same
// group id (radio-style), even though the inputs stay <input type="checkbox">
// because a single item can belong to more than one group.
export function initProductOptionGroups() {
    const optionGrid = document.getElementById('optionDiv');
    const modal = document.getElementById('modalOptionGroupAlert');
    const closeBtn = document.getElementById('optionGroupAlertCloseBtn');
    const choicesBox = document.getElementById('optionGroupAlertChoices');

    if (!optionGrid) return;

    function groupIdsOf(checkbox) {
        return (checkbox.dataset.groupIds || '')
            .split(',')
            .filter(Boolean);
    }

    function resolveGroupIds(groupIds) {
        groupIds.forEach(groupId => {
            if (modal && !modal.classList.contains('hidden') && modal.dataset.groupId === groupId) {
                modal.classList.add('hidden');
            }
        });
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
        resolveGroupIds(groupIds);
    });

    // Picking a choice directly inside the modal checks the matching page
    // checkbox (triggering the same mutual-exclusion above) and closes.
    if (choicesBox) {
        choicesBox.addEventListener('click', (e) => {
            const choice = e.target.closest('.option-group-choice');
            if (!choice) return;

            const checkbox = document.getElementById(`options_${choice.dataset.uniqueId}`);
            if (!checkbox) return;

            checkbox.checked = true;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

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
 * (with that group's message/image, plus its selectable choices so the
 * customer can pick right from the dialog) and returns the list of
 * unresolved group ids; returns an empty array when every group is satisfied.
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
        const choicesBox = document.getElementById('optionGroupAlertChoices');

        if (source && modal && modalImage && modalMessage && choicesBox) {
            modal.dataset.groupId = unresolvedGroupIds[0];
            modalImage.src = source.dataset.image || '';
            modalMessage.innerHTML = source.innerHTML;

            let choices = [];
            try {
                choices = JSON.parse(source.dataset.items || '[]');
            } catch (e) {
                choices = [];
            }

            const escapeHtml = (str) => String(str ?? '').replace(/[&<>"']/g, c => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
            }[c]));

            choicesBox.innerHTML = choices.map(choice => `
                <button type="button" class="option-group-choice w-full flex items-center justify-between gap-3 px-3 py-2 border border-gray-300 rounded-lg hover:border-yellow-400 hover:bg-yellow-50 transition-colors" data-unique-id="${escapeHtml(choice.unique_id)}">
                    <span class="text-gray-800">${escapeHtml(choice.label)}</span>
                    <span class="font-medium text-gray-700">+ ${escapeHtml(choice.price)}</span>
                </button>
            `).join('');

            modal.classList.remove('hidden');
        }
    }

    return unresolvedGroupIds;
}
