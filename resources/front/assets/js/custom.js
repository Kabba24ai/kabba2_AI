document.addEventListener("DOMContentLoaded", () => {
    // Panel configs
    const offcanvasConfigs = [
        {
            toggleBtn: ".toggleCart", // Cart toggle button(s)
            panel: "#cartOffCanvas",
            directionClass: "translate-x-full",
            openClasses: ["max-w-[403px]", "w-full", "opacity-100", "translate-x-0"],
            closeClasses: ["max-w-0", "w-0", "opacity-0", "translate-x-full"]
        },
        {
            toggleBtn: "#toggleHeader", // Mobile header/menu toggle
            panel: "#headerOffCanvas",
            directionClass: "translate-y-full",
            openClasses: ["max-h-[100vh]", "h-[100vh]", "opacity-100", "translate-y-0"],
            closeClasses: ["max-h-[0]", "opacity-0", "translate-y-full"]
        }
    ];

    // DRY: Open/close any panel
    function setPanelState(panelEl, open, openClasses, closeClasses, directionClass) {
        if (!panelEl) return;
        if (open) {
            openClasses.forEach(cls => panelEl.classList.add(cls));
            closeClasses.forEach(cls => panelEl.classList.remove(cls));
            panelEl.classList.remove(directionClass);
        } else {
            closeClasses.forEach(cls => panelEl.classList.add(cls));
            openClasses.forEach(cls => panelEl.classList.remove(cls));
            panelEl.classList.add(directionClass);
        }
    }

    // DRY: Close all panels except optionally one
    function closeAllOffcanvas(excludePanelSelector = null) {
        offcanvasConfigs.forEach(({panel, openClasses, closeClasses, directionClass}) => {
            const panelEl = document.querySelector(panel);
            if (!panelEl || (excludePanelSelector && panel === excludePanelSelector)) return;
            setPanelState(panelEl, false, openClasses, closeClasses, directionClass);
            document.querySelectorAll(`[data-panel="${panel}"].active`).forEach(btn => btn.classList.remove("active"));
        });
    }

    // Setup toggles
    offcanvasConfigs.forEach(({toggleBtn, panel, openClasses, closeClasses, directionClass}) => {
        const panelEl = document.querySelector(panel);
        if (!panelEl) return;

        // Attach toggle click handlers
        document.querySelectorAll(toggleBtn).forEach(toggleEl => {
            // Mark toggle with data-panel for closeAllOffcanvas active cleanup
            toggleEl.dataset.panel = panel;
            toggleEl.addEventListener("click", (e) => {
                e.stopPropagation();
                const isOpen = panelEl.classList.contains(openClasses[0]);
                if (isOpen) {
                    setPanelState(panelEl, false, openClasses, closeClasses, directionClass);
                    toggleEl.classList.remove("active");
                } else {
                    closeAllOffcanvas(panel);
                    setPanelState(panelEl, true, openClasses, closeClasses, directionClass);
                    toggleEl.classList.add("active");
                }
            });
        });

        // Prevent closing panel when clicking inside panel
        panelEl.addEventListener("click", (e) => e.stopPropagation());
    });

    // Close on document click
    document.addEventListener("click", () => closeAllOffcanvas());

    // ESC to close all
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") closeAllOffcanvas();
    });

    // Utility: update panel position on scroll for sticky cart/contact/header
    function updatePanelPosition(panelSelector, navbarSelector, topClassWhenScrolled, topClassWhenNotScrolled) {
        const panel = document.querySelector(panelSelector);
        const navbar = document.querySelector(navbarSelector);
        if (!panel || !navbar) return;
        if (window.scrollY >= 50) {
            panel.classList.add(topClassWhenScrolled);
            panel.classList.remove(topClassWhenNotScrolled);
            navbar.classList.add("shadow-[0_1px_3px_0_rgba(0,0,0,.05)]", "bg-opacity-90");
            navbar.classList.remove("mt-[35px]", "bg-opacity-100");
        } else {
            panel.classList.remove(topClassWhenScrolled);
            panel.classList.add(topClassWhenNotScrolled);
            navbar.classList.remove("shadow-[0_1px_3px_0_rgba(0,0,0,.05)]", "bg-opacity-90");
            navbar.classList.add("mt-[35px]", "bg-opacity-100");
        }
    }
    // Example usage for cart panel
    window.addEventListener("scroll", () => {
        updatePanelPosition(".offcanvas-cart-body", ".navbar", "top-[80px]", "top-[110px]");
        updatePanelPosition(".offcanvas-body", ".navbar", "top-[80px]", "top-[110px]");
    });

    // DRY: Dropdown menu toggling inside offcanvas
    document.querySelectorAll("#menuToggleBtn").forEach((btn) => {
        btn.addEventListener("click", () => {
            const dropdown = btn.nextElementSibling;
            if (dropdown) dropdown.classList.toggle("active");
        });
    });
});
