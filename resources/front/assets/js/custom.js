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
    // window.updateClPosition = function updateClPosition() {
    //     const breadcrumb = document.querySelector("#breadcrumbs");
    //     if (!breadcrumb) return;
    //     if (window.scrollY >= 10) {
    //         breadcrumb.classList.add("pt-[142px]");
    //         breadcrumb.classList.remove("pt-[100px]");
    //     } else {
    //         breadcrumb.classList.remove("pt-[142px]");
    //         breadcrumb.classList.add("pt-[100px]");
    //     }
    // };

    // window.addEventListener("scroll", updateClPosition);
    // updateClPosition();

    // Optional: dropdown inside header
    document.querySelectorAll("#menuToggleBtn").forEach((btn) => {
        btn.addEventListener("click", () => {
            const dropdown = btn.nextElementSibling;
            if (dropdown) dropdown.classList.toggle("active");
        });
    });

    document.querySelectorAll(".toggleCart").forEach((btn) => {
        btn.addEventListener("click", () => {
            const cartItems =
                JSON.parse(localStorage.getItem("rental_cart")) || [];

            // If you need the tax rate, fetch only that:
            fetch("/cart", {
                method: "GET",
                credentials: "same-origin",
            })
                .then((res) => res.json())
                .then((data) => {
                    const fixTaxRate = data.cart?.tax_rate || 0;

                    const container = document.querySelector(
                        "#cartoffcanvas .overflow-y-scroll",
                    );
                    container.innerHTML = "";

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

                    container.insertAdjacentHTML(
                        "beforeend",
                        `
                        <div class="flex items-center justify-between mb-8">
                            <h4 class="text-base font-bold">Your Cart</h4>
                            <i class="fas fa-arrow-down text-light-gray text-3 opacity-[0.5]"></i>
                        </div>
                    `,
                    );

                    cartItems.forEach((item, index) => {
                        const qty = parseInt(item.qty);
                        totalQty += qty;
                        const base = parseFloat(item.base_price);
                        const addons = (item.addons || []).filter(
                            (a) => a.name?.trim() && parseFloat(a.price) > 0,
                        );
                        const delivery_pickup = (
                            item.delivery_pickup || []
                        ).filter((a) => a.name?.trim());

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
                                    <li class="lh-13">
                                        <span class="text-xs mb-0 before:content-['-'] before:pr-1">
                                            ${a.name}${qtyNote}
                                        </span>
                                    </li>`;
                            })
                            .join("");

                        const delivery_pickupList = delivery_pickup
                            .map((a) => {
                                return `
                                    <li class="lh-13">
                                        <span class="text-xs mb-0 before:content-['-'] before:pr-1">
                                            ${a.name}
                                        </span>
                                    </li>`;
                            })
                            .join("");

                        // const productURL = `/products/${item.product_slug}/${item.product_type}/details`;

                        const urlParams = new URLSearchParams();

                        urlParams.set("qty", item.qty);
                        urlParams.set(
                            "sechdule_start_date",
                            item.sechdule_start_date,
                        );
                        urlParams.set("delivery_fee", item.delivery_fee || 0);
                        urlParams.set("store_id", item.store_id || "");
                        urlParams.set(
                            "service_method",
                            item.service_method || "",
                        );
                        urlParams.set(
                            "service_option",
                            item.service_option || "",
                        );

                        if (item.addons?.length) {
                            urlParams.set(
                                "addons",
                                JSON.stringify(item.addons),
                            );
                        }

                        if (item.delivery_pickup?.length) {
                            urlParams.set(
                                "delivery_pickup",
                                JSON.stringify(item.delivery_pickup),
                            );
                        }

                        const productURL = `/products/${item.product_slug}/${item.product_type}/details?${urlParams.toString()}`;

                        const productHTML = `
                            <div class="flex gap-4 justify-between mb-8" data-index="${index}">
                                <div class="w-20"><img src="${item.image}" alt="image" class="w-full"></div>
                                <div class="w-80">
                                    <h4 class="text-sm font-bold">${item.name}</h4>
                                    <p class="text-xs my-1">$${item.base_price} (x${qty})</p>

                                    <div class="flex justify-between text-sm">
                                        Price: <span class="font-bold">$${(base * qty).toFixed(2)}</span>
                                    </div>
                                    <div class="flex justify-between text-sm mb-1">
                                        Options: <span class="font-bold">+$${(addonTotal + deliveryFee).toFixed(2)}</span>
                                    </div>

                                    <!--<div class="flex justify-between text-sm">
                                        Delivery Fee: <span class="font-bold">$${deliveryFee.toFixed(2)}</span>
                                    </div>-->

                                    <ul class="flex flex-col gap-y-2 mb-2">${addonList} ${delivery_pickupList}</ul>

                                    <p class="text-sm">Schedule Date: ${item.sechdule_start_date}</p>

                                    <div>
                                        <a href="javascript:void(0)" class="removeItem text-sm after:content-['|'] after:pl-2 text-red-500">
                                            <i class="fa-solid fa-xmark"></i> Remove
                                        </a>
                                        <a href="${productURL}" class="text-sm text-cyan-400" target="_blank">Update</a>
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
                            <button class="w-full border-0 bg-yellow-400 text-center font-bold justify-center flex px-6 py-3 mt-4 leading-4 rounded-lg text-sm hover:bg-yellow-300  transition-all duration-500 ease-in-out">
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
                            // Remove from localStorage directly
                            const cart =
                                JSON.parse(
                                    localStorage.getItem("rental_cart"),
                                ) || [];
                            cart.splice(index, 1);
                            localStorage.setItem(
                                "rental_cart",
                                JSON.stringify(cart),
                            );

                            // Re-render cart panel
                            document.querySelector(".toggleCart")?.click();
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
                    console.error("Failed to fetch cart:", err);
                });
        });
    });

    const cartItems = JSON.parse(localStorage.getItem("rental_cart")) || [];
    const totalQty = cartItems.reduce(
        (sum, item) => sum + parseInt(item.qty || 0),
        0,
    );
    document.querySelectorAll(".toggleCart span").forEach((el) => {
        el.textContent = totalQty;
    });

    // update section
    window.onload = function () {
        if (!window.location.search) return;

        const urlParams = new URLSearchParams(window.location.search);

        //  Uncheck all delivery method radios ---
        const optionRadios = document.querySelectorAll('input[name="option"]');
        optionRadios.forEach((radio) => (radio.checked = false));

        //  Select correct delivery method from URL ---
        const serviceMethod = urlParams.get("service_method"); // 'delivery' or 'in-store'
        if (serviceMethod) {
            const selectedOption = document.querySelector(
                `input[name="option"][value="${serviceMethod}"]`,
            );
            if (selectedOption) {
                selectedOption.checked = true;
                selectedOption.dispatchEvent(new Event("change"));
            }
        }

        //  Uncheck all delivery-option radios first ---
        const deliveryOptionRadios = document.querySelectorAll(
            'input[name="delivery-option"]',
        );
        deliveryOptionRadios.forEach((radio) => (radio.checked = false));

        // Match delivery_fee to dis1/dis2 radio ---
        const deliveryFee = parseFloat(urlParams.get("delivery_fee"));
        let matchedRadio = null;

        if (!isNaN(deliveryFee)) {
            const stdOption = document.querySelector(
                '#deliveryOption_std_delivery_fee option[data-id="Delivery"]',
            );
            const extOption = document.querySelector(
                '#deliveryOption_ext_delivery_fee option[data-id="Delivery"]',
            );

            const stdFee = stdOption
                ? parseFloat(stdOption.textContent.replace(/[^0-9.]/g, ""))
                : null;
            const extFee = extOption
                ? parseFloat(extOption.textContent.replace(/[^0-9.]/g, ""))
                : null;

            if (deliveryFee === stdFee) {
                matchedRadio = document.querySelector(
                    `input[name="delivery-option"][value="dis1"]`,
                );
            } else if (deliveryFee === extFee) {
                matchedRadio = document.querySelector(
                    `input[name="delivery-option"][value="dis2"]`,
                );
            }
        }

        // Default to dis1 if nothing matched
        if (!matchedRadio) {
            matchedRadio = document.querySelector(
                `input[name="delivery-option"][value="dis1"]`,
            );
        }

        if (matchedRadio) {
            matchedRadio.checked = true;
            matchedRadio.dispatchEvent(new Event("change"));
        }

        //  Select deliveryOption dropdown (Delivery / Pickup) ---
        const serviceOption = urlParams.get("service_option");
        [
            "deliveryOption_std_delivery_fee",
            "deliveryOption_ext_delivery_fee",
        ].forEach((selectId) => {
            const select = document.getElementById(selectId);
            if (select && serviceOption) {
                [...select.options].forEach((opt) => {
                    if (opt.dataset.id === serviceOption) {
                        select.value = opt.value;
                    }
                });
            }
        });

        //  Add-ons ---
        const addonsParam = urlParams.get("addons");
        if (addonsParam) {
            try {
                // Uncheck all checkboxes first
                document
                    .querySelectorAll('input[type="checkbox"]')
                    .forEach((cb) => (cb.checked = false));

                const addons = JSON.parse(decodeURIComponent(addonsParam));
                addons.forEach((addon) => {
                    const id = addon?.unique_id;
                    const name = addon?.name;

                    if (id) {
                        const checkbox = document.querySelector(
                            `input[data-id="${id}"]`,
                        );
                        if (checkbox) checkbox.checked = true;
                    } else if (name) {
                        document
                            .querySelectorAll('input[type="checkbox"]')
                            .forEach((input) => {
                                const labelText =
                                    input.closest("label")?.innerText || "";
                                if (labelText.includes(name)) {
                                    input.checked = true;
                                }
                            });
                    }
                });
            } catch (e) {
                console.error("Invalid addons param:", e);
            }
        }

        //  Set quantity ---
        const qty = urlParams.get("qty");
        if (qty) {
            const qtyInput = document.getElementById("qty");
            if (qtyInput) qtyInput.value = qty;
        }

        //  Set schedule start date ---
        const date = urlParams.get("sechdule_start_date");
        if (date) {
            const dateInput = document.getElementById("dateInput");
            const dateDisplay = document.getElementById("selectedDateText");
            if (dateInput) dateInput.value = date;
            if (dateDisplay) dateDisplay.innerText = date;
        }

        // Store location dropdown ---
        const storeId = urlParams.get("store_id");
        const storeDropdown = document.getElementById("storeLocation");
        const addressDiv = document.getElementById("address");

        if (storeId && storeDropdown) {
            storeDropdown.value = storeId;

            //  Unhide the address div
            addressDiv?.classList.remove("hidden");
        }
    };
});
