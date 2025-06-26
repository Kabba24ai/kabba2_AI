// ofcanvas
document.addEventListener("DOMContentLoaded", () => {
    const allOffcanvasConfigs = [
        {
            toggleBtn: ".toggleCart",
            panel: "#cartoffcanvas",
            directionClass: "translate-x-full",
        },
        {
            toggleBtn: "#toggleSidebar",
            panel: "#contactoffcanvas",
            directionClass: "translate-x-full",
        },
        {
            toggleBtn: "#toggleHeader",
            panel: "#headeroffcanvas",
            directionClass: "translate-y-full",
            hideClasses: ["max-h-[0]", "opacity-0"],
            showClasses: [
                "max-h-[100vh]",
                "h-[100vh]",
                "opacity-100",
                "transition-transform",
                "duration-300",
                "ease-in-out",
            ],
        },
    ];

    window.closeAllOffcanvas = function closeAllOffcanvas(excludeId = "") {
        allOffcanvasConfigs.forEach(
            ({
                toggleBtn,
                panel,
                directionClass,
                hideClasses = [],
                showClasses = [],
            }) => {
                const panelEl = document.querySelector(panel);
                const toggleEls = document.querySelectorAll(toggleBtn);
                if (!panelEl || panel === excludeId) return;
                panelEl.classList.add(directionClass);
                hideClasses.forEach((cls) => panelEl.classList.add(cls));
                showClasses.forEach((cls) => panelEl.classList.remove(cls));
                toggleEls.forEach((toggleEl) =>
                    toggleEl.classList.remove("active"),
                );
            },
        );
    };

    allOffcanvasConfigs.forEach(
        ({
            toggleBtn,
            panel,
            directionClass,
            hideClasses = [],
            showClasses = [],
        }) => {
            const toggleEls = document.querySelectorAll(toggleBtn);
            const panelEl = document.querySelector(panel);
            if (!toggleEls.length || !panelEl) return;

            toggleEls.forEach((toggleEl) => {
                toggleEl.addEventListener("click", (e) => {
                    e.stopPropagation();
                    const isOpen = !panelEl.classList.contains(directionClass);
                    if (isOpen) {
                        panelEl.classList.add(directionClass);
                        hideClasses.forEach((cls) =>
                            panelEl.classList.add(cls),
                        );
                        showClasses.forEach((cls) =>
                            panelEl.classList.remove(cls),
                        );
                        toggleEl.classList.remove("active");
                        if (toggleBtn === ".toggleCart") {
                            applyCartCloseClasses(panelEl);
                        }
                        if (toggleBtn === "#toggleSidebar") {
                            applyContactCloseClasses(panelEl);
                        }
                    } else {
                        closeAllOffcanvas(panel);
                        panelEl.classList.remove(directionClass);
                        hideClasses.forEach((cls) =>
                            panelEl.classList.remove(cls),
                        );
                        showClasses.forEach((cls) =>
                            panelEl.classList.add(cls),
                        );
                        toggleEl.classList.add("active");
                        if (toggleBtn === ".toggleCart") {
                            applyCartOpenClasses(panelEl);
                        }
                        if (toggleBtn === "#toggleSidebar") {
                            applyContactOpenClasses(panelEl);
                        }
                    }
                });
            });

            panelEl.addEventListener("click", (e) => e.stopPropagation());
        },
    );

    document.addEventListener("click", () => {
        closeAllOffcanvas();
    });

    // cart header
    window.updateCartPosition = function updateCartPosition() {
        const header = document.querySelector(".offcanvas-cart-body");
        const navbar = document.querySelector(".navbar");
        if (!header || !navbar) return;
        if (window.scrollY >= 50) {
            header.classList.add("top-[80px]");
            header.classList.add("md:top-[68px]");
            header.classList.remove("top-[110px]");
            header.classList.remove("md:top-[100px]");
            navbar.classList.add("shadow-[0_1px_3px_0_rgba(0,0,0,.05)]");
            navbar.classList.add("bg-opacity-90");
            navbar.classList.remove("mt-[35px]");
            navbar.classList.remove("bg-opacity-100");
        } else {
            header.classList.remove("top-[80px]");
            header.classList.remove("md:top-[68px]");
            header.classList.add("top-[110px]");
            header.classList.add("md:top-[100px]");
            navbar.classList.remove("shadow-[0_1px_3px_0_rgba(0,0,0,.05)]");
            navbar.classList.remove("bg-opacity-90");
            navbar.classList.add("mt-[35px]");
            navbar.classList.add("bg-opacity-100");
        }
    };

    window.applyCartOpenClasses = function applyCartOpenClasses(panelEl) {
        panelEl.classList.add("opacity-100");
        panelEl.classList.add("max-w-[98%]");
        panelEl.classList.add("w-full");
        panelEl.classList.remove("opacity-0");
        panelEl.classList.remove("max-w-0");
        panelEl.classList.remove("w-0");
    };

    window.applyCartCloseClasses = function applyCartCloseClasses(panelEl) {
        panelEl.classList.add("w-0");
        panelEl.classList.add("opacity-0");
        panelEl.classList.add("max-w-0");
        panelEl.classList.remove("max-w-[98%]");
        panelEl.classList.remove("w-full");
        panelEl.classList.remove("opacity-100");
    };

    window.addEventListener("scroll", updateCartPosition);
    updateCartPosition();

    // cart contact info
    window.updateContactPosition = function updateContactPosition() {
        const header = document.querySelector(".offcanvas-body");
        const navbar = document.querySelector(".navbar");
        if (!header || !navbar) return;
        if (window.scrollY >= 50) {
            header.classList.add("top-[80px]");
            header.classList.add("md:top-[68px]");
            header.classList.remove("top-[110px]");
            navbar.classList.add("shadow-[0_1px_3px_0_rgba(0,0,0,.05)]");
        } else {
            header.classList.remove("top-[80px]");
            header.classList.remove("md:top-[68px]");
            header.classList.add("top-[110px]");
            navbar.classList.remove("shadow-[0_1px_3px_0_rgba(0,0,0,.05)]");
        }
    };

    window.applyContactOpenClasses = function applyContactOpenClasses(panelEl) {
        panelEl.classList.add("opacity-100");
        panelEl.classList.add("max-w-[98%]");
        panelEl.classList.add("w-full");
        panelEl.classList.remove("opacity-0");
        panelEl.classList.remove("max-w-0");
        panelEl.classList.remove("w-0");
    };

    window.applyContactCloseClasses = function applyContactCloseClasses(
        panelEl,
    ) {
        panelEl.classList.add("w-0");
        panelEl.classList.add("opacity-0");
        panelEl.classList.add("max-w-0");
        panelEl.classList.remove("max-w-[98%]");
        panelEl.classList.remove("w-full");
        panelEl.classList.remove("opacity-100");
    };

    window.addEventListener("scroll", updateContactPosition);
    updateContactPosition();

    // add / remove class
    window.updateClPosition = function updateClPosition() {
        const breadcrumb = document.querySelector("#breadcrumbs");
        if (!breadcrumb) return;
        if (window.scrollY >= 10) {
            breadcrumb.classList.add("pt-[142px]");
            breadcrumb.classList.remove("pt-[100px]");
        } else {
            breadcrumb.classList.remove("pt-[142px]");
            breadcrumb.classList.add("pt-[100px]");
        }
    };

    window.addEventListener("scroll", updateClPosition);
    updateClPosition();

    // Optional: dropdown inside header
    document.querySelectorAll("#menuToggleBtn").forEach((btn) => {
        btn.addEventListener("click", () => {
            const dropdown = btn.nextElementSibling;
            if (dropdown) dropdown.classList.toggle("active");
        });
    });

    document.querySelectorAll(".toggleCart").forEach((btn) => {
        btn.addEventListener("click", () => {
            fetch("/cart/", {
                method: "GET",
                credentials: "same-origin",
            })
                .then((res) => res.json())
                .then((data) => {
                    const cartItems = data.cart?.rental_cart || [];
                    const fixTaxRate = data.cart?.tax_rate || 0;


                    const container = document.querySelector(
                        "#cartoffcanvas .overflow-y-scroll",
                    );
                    container.innerHTML = ""; // Clear cart panel

                    if (cartItems.length === 0) {
                        container.innerHTML = `<p class="text-center py-10 text-gray-600">Your cart is empty.</p>`;
                        document
                            .querySelectorAll(".toggleCart span")
                            .forEach((el) => {
                                el.textContent = 0;
                            });
                        return;
                    }

                    let subtotal = 0;
                    let totalQty = 0;
                
                    cartItems.forEach((item, index) => {
                        const qty = parseInt(item.qty);
                        totalQty += qty;
                        const base = parseFloat(item.base_price);
                        const addons = (item.addons || []).filter(
                            (a) => a.name?.trim() && parseFloat(a.price) > 0,
                        );
                        const delivery_pickup = (item.delivery_pickup || []).filter(
                            (a) => a.name?.trim(),
                        );

                        let addonTotal = 0;
                        addons.forEach((addon) => {
                            const price = parseFloat(addon.price) || 0;
                            const charged = (addon.charged || "").toLowerCase();
                            if (!addon.name?.trim() || price <= 0) return;
                            addonTotal +=
                                charged === "unlimited" ? price * qty : price;
                        });

                        const deliveryFee = parseFloat(item.delivery_fee || 0);
                        const itemTotal = base * qty + addonTotal + deliveryFee;
                        subtotal += itemTotal;

                        const addonList = addons
                            .map((a) => {
                                const charged = (a.charged || "").toLowerCase();
                                //const qtyNote =
                                //    charged === "unlimited" ? ` (x${qty})` : "";
                                 const qtyNote =
                                    charged === "unlimited" ? ` ` : "";
                                return `
                                    <li class="leading-[13px]">
                                        <span class="text-[13px] mb-0 before:content-['-'] before:pr-1">
                                            ${a.name}${qtyNote}
                                        </span>
                                    </li>`;
                            })
                            .join("");
                        
                        const delivery_pickupList = delivery_pickup
                            .map((a) => {
                                return `
                                    <li class="leading-[13px]">
                                        <span class="text-[13px] mb-0 before:content-['-'] before:pr-1">
                                            ${a.name}
                                        </span>
                                    </li>`;
                            })
                            .join("");

                        const productURL = `/products/${item.product_slug}/${item.rental_type}/details`;

                        const productHTML = `
                            <div class="flex gap-8 mb-5 pb-5" data-index="${index}">
                                <div><img src="${item.image}" alt="image" class="w-16"></div>
                                <div class="flex-1">
                                    <h4 class="text-[14px] font-bold">${item.name}</h4>
                                    <p class="text-[13px]">$${item.base_price} (x${qty})</p>

                                    <div class="flex justify-between text-sm">
                                        Price: <span class="font-bold">$${(base * qty).toFixed(2)}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        Options: <span class="font-bold">+$${(addonTotal+deliveryFee).toFixed(2)}</span>
                                    </div>

                                    <!--<div class="flex justify-between text-sm">
                                        Delivery Fee: <span class="font-bold">$${deliveryFee.toFixed(2)}</span>
                                    </div>-->
                                    
                                    <ul class="flex flex-col gap-y-2 mb-2">${addonList} ${delivery_pickupList}</ul>

                                    <p class="text-[13px]">Schedule Date: ${item.schedule_date}</p>

                                    <div>
                                        <a href="javascript:void(0)" class="removeItem text-[13px] after:content-['|'] after:pl-2 text-red-500">
                                            <i class="fa-solid fa-xmark"></i> Remove
                                        </a>
                                        <a href="${productURL}" class="text-[13px] text-[#0dcaf0]" target="_blank">Update</a>
                                    </div>
                                </div>
                            </div>`;

                        container.insertAdjacentHTML("beforeend", productHTML);
                    });

                  
                    const tax = subtotal * fixTaxRate;
                    const grandTotal = subtotal + tax;

                    const totalsHTML = `
                        <div class="border-t mt-5 pt-5">
                            <div class="flex justify-between">
                                <label class="font-bold">Sub Total</label>
                                <span>$${subtotal.toFixed(2)}</span>
                            </div>
                            <div class="flex justify-between">
                                <label class="font-bold">Tax</label>
                                <span>$${tax.toFixed(2)}</span>
                            </div>
                            <div class="flex justify-between">
                                <label class="font-bold">Total</label>
                                <span>$${grandTotal.toFixed(2)}</span>
                            </div>
                            <button class="w-full border-0 bg-yellow-400 text-center font-bold justify-center flex px-6 py-3 mt-4 leading-4 rounded-lg text-[14px] hover:bg-yellow-300  transition-all duration-500 ease-in-out">
                                <a href="/checkout">CHECKOUT</a>
                            </button>
                        </div>`;
                    container.insertAdjacentHTML("beforeend", totalsHTML);

                    // Update quantity badge
                    document
                        .querySelectorAll(".toggleCart span")
                        .forEach((el) => {
                            el.textContent = totalQty;
                        });

                    // Remove buttons
                    container.querySelectorAll(".removeItem").forEach((btn) => {
                        btn.addEventListener("click", function () {
                            const index =
                                this.closest("[data-index]").dataset.index;
                            fetch("/cart/remove", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json",
                                    "X-CSRF-TOKEN": document
                                        .querySelector(
                                            'meta[name="csrf-token"]',
                                        )
                                        .getAttribute("content"),
                                },
                                body: JSON.stringify({ index }),
                            })
                                .then((res) => res.json())
                                .then(() => {
                                    document
                                        .querySelector(".toggleCart")
                                        .click();
                                })
                                .catch((err) => {
                                    console.error("❌ Remove failed:", err);
                                });
                        });
                    });

                    // Show the panel
                    const cartPanel = document.getElementById("cartoffcanvas");
                    cartPanel.classList.remove(
                        "max-w-0",
                        "w-0",
                        "opacity-0",
                        "translate-x-full",
                    );
                    cartPanel.classList.add(
                        "max-w-[403px]",
                        "w-full",
                        "opacity-100",
                        "translate-x-0",
                    );
                })
                .catch((err) => {
                    console.error("❌ Failed to fetch cart:", err);
                });
        });
    });

    // Automatically fetch cart total quantity on page load
    fetch("/cart/", {
        method: "GET",
        credentials: "same-origin",
    })
        .then((res) => res.json())
        .then((data) => {
            const cartItems = data.cart?.rental_cart || [];
            const totalQty = cartItems.reduce(
                (sum, item) => sum + parseInt(item.qty || 0),
                0,
            );
            document.querySelectorAll(".toggleCart span").forEach((el) => {
                el.textContent = totalQty;
            });
        })
        .catch((err) => {
            console.error("Failed to fetch cart on load:", err);
        });
});
