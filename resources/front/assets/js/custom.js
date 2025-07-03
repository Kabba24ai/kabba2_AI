window.updateCartCount = function() {
    if (window.CartStorage && typeof window.CartStorage.getTotalQuantity === 'function') {
        const count = window.CartStorage.getTotalQuantity();
        const cartCountEl = document.getElementById('cart-count');
        if (cartCountEl) {
            cartCountEl.textContent = count;
        }
    }
};

window.loadCartSidebarPreview = (function() {
    let loading = false; // Prevents double fetches

    return function() {
        const cartDataDiv = document.getElementById('cartData');
        if (!cartDataDiv) return;

        // Prevent double loading if already loading
        if (loading) return;

        // Try to get kabba_cart from localStorage
        let cart = window.CartStorage.getCart();

        // Update cart count every time sidebar preview loads
        if (typeof window.updateCartCount === "function") {
            window.updateCartCount();
        }

        if (!cart?.length) {
            cartDataDiv.innerHTML = '<p class="text-center py-10 text-gray-600">Your cart is empty.</p>';
            return;
        }

        const url = cartDataDiv.dataset.fetchCartUrl;
        if (!url) {
            cartDataDiv.innerHTML = '<p class="text-center py-10 text-gray-600">Cart URL not set.</p>';
            return;
        }

        // Show a loading spinner or message
        cartDataDiv.innerHTML = `
            <div class="flex items-center justify-center py-10">
                <svg class="animate-spin h-6 w-6 text-gray-600 mr-3" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M12 2a10 10 0 0 1 10 10h-4a6 6 0 0 0-6-6V2z"></path>
                </svg>
                <span class="text-gray-700">Loading cart...</span>
            </div>
        `;
        loading = true;

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({ kabba_cart: cart })
        })
        .then(response => response.text())
        .then(html => {
            cartDataDiv.innerHTML = html;
        })
        .catch(error => {
            console.error('Fetch error:', error);
            cartDataDiv.innerHTML = '<p class="text-center py-10 text-gray-600">Could not load cart.</p>';
        })
        .finally(() => {
            loading = false;
        });
    };
})();



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

                    // If this is the cart panel, load the sidebar preview
                    // if (panel === "#cartOffCanvas" && typeof window.loadCartSidebarPreview === "function") {
                    //     window.loadCartSidebarPreview();
                    // }
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

    // Update cart count ONCE when the page loads
    window.loadCartSidebarPreview();
});
