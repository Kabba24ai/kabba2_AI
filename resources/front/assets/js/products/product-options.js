export function initProductOptions() {
    const optionGrid = document.getElementById('optionDiv');
    const modal = document.getElementById("modalBackdropClean");
    const modalMessage = document.getElementById("modalMessage");
    const modalRejectBtn = document.getElementById("modalRejectBtn");
    const modalAcceptBtn = document.getElementById("modalAcceptBtn");
    let currentCheckbox = null;

    if (!optionGrid) return;

    optionGrid.addEventListener("click", function(e) {
        const checkbox = e.target.closest('input[type="checkbox"].form-checkbox');
        if (!checkbox) return;
        if (!checkbox.checked) {
            const message = (checkbox.dataset.message || "").trim();
            if (!message) return;
            e.preventDefault();
            checkbox.checked = true;
            modalMessage.innerHTML = message;
            modalRejectBtn.textContent = checkbox.dataset.discard || "No Thanks, I'll Take The Risk";
            modalAcceptBtn.textContent = checkbox.dataset.keep || "Keep Option";
            currentCheckbox = checkbox;
            modal.classList.remove("hidden");
        }
    });

    function closeModal() {
        modal.classList.add("hidden");
        currentCheckbox = null;
    }
    modalAcceptBtn.addEventListener("click", closeModal);
    modalRejectBtn.addEventListener("click", function() {
        if (currentCheckbox) currentCheckbox.checked = false;
        closeModal();
    });
    modal.addEventListener("click", function(e) {
        if (e.target === modal) closeModal();
    });
    document.addEventListener("keydown", function(e) {
        if (e.key === "Escape") closeModal();
    });
}
