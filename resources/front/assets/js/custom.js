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
});

// qty pluse/minus
window.changeQty = function changeQty(delta) {
    const input = document.getElementById("qty");
    let current = parseInt(input.value) || 1;
    let newValue = current + delta;
    if (newValue < parseInt(input.min)) newValue = parseInt(input.min);
    if (newValue > parseInt(input.max)) newValue = parseInt(input.max);
    input.value = newValue;
};

// ui-datrpicker
const openBtn = document.getElementById("openDatePicker");
const selectedText = document.getElementById("selectedDateText");
const datePicker = document.getElementById("datePicker");
const monthYear = document.getElementById("monthYear");
const daysGrid = document.getElementById("daysGrid");
const prevMonthBtn = document.getElementById("prevMonth");
const nextMonthBtn = document.getElementById("nextMonth");
const dateInput = document.getElementById("dateInput");

// Only initialize if all required elements exist
if (
    openBtn &&
    selectedText &&
    datePicker &&
    monthYear &&
    daysGrid &&
    prevMonthBtn &&
    nextMonthBtn &&
    dateInput
) {
    let currentDate = new Date();

    openBtn.addEventListener("click", () => {
        datePicker.classList.toggle("hidden");
        if (!datePicker.classList.contains("hidden")) {
            renderCalendar(currentDate);
        }
    });

    document.addEventListener("click", (e) => {
        if (
            !datePicker.contains(e.target) &&
            e.target !== openBtn &&
            !openBtn.contains(e.target)
        ) {
            datePicker.classList.add("hidden");
        }
    });

    prevMonthBtn.addEventListener("click", () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar(currentDate);
    });

    nextMonthBtn.addEventListener("click", () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar(currentDate);
    });
    window.renderCalendar = function renderCalendar(date) {
        const year = date.getFullYear();
        const month = date.getMonth();

        monthYear.textContent = date.toLocaleString("default", {
            month: "long",
            year: "numeric",
        });
        daysGrid.innerHTML = "";

        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const startDay = firstDay.getDay();

        for (let i = 0; i < startDay; i++) {
            daysGrid.innerHTML += "<div></div>";
        }

        for (let day = 1; day <= lastDay.getDate(); day++) {
            const dayEl = document.createElement("button");
            dayEl.textContent = day;
            dayEl.className =
                "py-1 rounded hover:bg-yellow-500 hover:text-white focus:outline-none";

            const today = new Date();
            if (
                day === today.getDate() &&
                month === today.getMonth() &&
                year === today.getFullYear()
            ) {
                dayEl.classList.add("bg-yellow-100");
            }

            dayEl.addEventListener("click", () => {
                const formatted = `${year}-${String(month + 1).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
                selectedText.textContent = formatted;
                dateInput.value = formatted; // update hidden input
                datePicker.classList.add("hidden");
            });

            daysGrid.appendChild(dayEl);
        }
    };
} else {
    console.log("Date picker elements not found on this page");
}

// modal product checkbox
window.confirmUncheck = function confirmUncheck(checkbox, modalId) {
    // If it's being unchecked
    if (!checkbox.checked) {
        // Prevent uncheck immediately
        checkbox.checked = true;
        // Show modal
        document.getElementById(modalId).classList.remove("hidden");
    }
};

window.cancelUncheck = function cancelUncheck(checkboxId, modalId) {
    // Keep it checked
    document.getElementById(checkboxId).checked = true;
    document.getElementById(modalId).classList.add("hidden");
};

window.confirmUncheckModal = function confirmUncheckModal(checkboxId, modalId) {
    // Uncheck the checkbox
    document.getElementById(checkboxId).checked = false;
    document.getElementById(modalId).classList.add("hidden");
};

// Close modal if click outside
document.querySelectorAll('[id^="modalBackdrop"]').forEach((modal) => {
    modal.addEventListener("click", function (e) {
        if (e.target === this) cancelUncheck("fuelCheckbox", this.id);
    });
});

// product details radio hide/show
window.toggleDeliveryOption = function toggleDeliveryOption(radio) {
    const inStoreDiv = document.getElementById("inStoreDiv");
    const deliveryDiv = document.getElementById("deliveryDiv");
    const customdis = document.getElementById("customdis");
    const distance = document.getElementById("distance");
    const address = document.getElementById("address");
    const rentalbtn = document.getElementById("rentalbtn");

    if (radio.name === "option") {
        if (radio.value === "in-store") {
            // Show for in-store
            inStoreDiv.classList.remove("hidden");
            address.classList.remove("hidden");
            distance.classList.remove("hidden");
            rentalbtn.classList.remove("hidden");

            // Hide delivery-related
            deliveryDiv.classList.add("hidden");
            customdis.classList.add("hidden");
        } else if (radio.value === "delivery") {
            // Show delivery section
            deliveryDiv.classList.remove("hidden");
            inStoreDiv.classList.add("hidden");

            // Hide address for delivery initially
            address.classList.add("hidden");
            customdis.classList.add("hidden");

            // Check which delivery-option is selected
            const deliveryOption = document.querySelector(
                'input[name="delivery-option"]:checked',
            );
            if (deliveryOption) {
                toggleDeliveryOption(deliveryOption); // Trigger sub-option logic
            }
        }
    }

    if (radio.name === "delivery-option") {
        if (radio.value === "dis1" || radio.value === "dis2") {
            distance.classList.remove("hidden");
            rentalbtn.classList.remove("hidden");
            customdis.classList.add("hidden");
            address.classList.add("hidden"); // ✅ HIDE address in delivery
        } else if (radio.value === "discustom") {
            customdis.classList.remove("hidden");
            distance.classList.add("hidden");
            rentalbtn.classList.add("hidden");
            address.classList.add("hidden"); // ✅ HIDE address in custom
        }
    }
};

window.onload = () => {
    const mainSelected = document.querySelector('input[name="option"]:checked');
    if (mainSelected) {
        toggleDeliveryOption(mainSelected);
    }
};


// product detail img
document.addEventListener("DOMContentLoaded", function () {
    var product = new Swiper(".thumbSwiper", {
        loop: true,
        spaceBetween: 10,
        slidesPerView: 4,
        freeMode: true,
        watchSlidesProgress: true,
    });

    var swiper2 = new Swiper(".mainSwiper", {
        loop: true,
        spaceBetween: 10,
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },
        thumbs: {
            swiper: product,
        },
    });
});

// readmore
window.readMore = function readMore() {
    const dots = document.getElementById("dots");
    const moreText = document.getElementById("more");
    const btn = document.getElementById("read_more");

    if (dots.style.display === "none") {
        dots.style.display = "inline";
        moreText.classList.add("hidden");
        btn.textContent = "Learn More";
        btn.classList.remove("font-bold", "text-blue-800");
        btn.classList.add("text-blue-600");
    } else {
        dots.style.display = "none";
        moreText.classList.remove("hidden");
        btn.textContent = "Show Less";
        btn.classList.remove("text-blue-600");
        btn.classList.add("font-bold", "text-blue-800");
    }
};

// payment method
window.toggleCodOption = function toggleCodOption(radio) {
    const creditSection = document.getElementById("creditSection");
    const codSection = document.getElementById("codSection");
    const accountSection = document.getElementById("accountSection");

    if (radio.value === "credit") {
        creditSection.classList.remove("hidden");
        codSection.classList.add("hidden");
        accountSection.classList.add("hidden");
    } else if (radio.value === "cod") {
        codSection.classList.remove("hidden");
        creditSection.classList.add("hidden");
        accountSection.classList.add("hidden");
    } else if (radio.value === "account") {
        accountSection.classList.remove("hidden");
        creditSection.classList.add("hidden");
        codSection.classList.add("hidden");
    }
};

// phone number formate
window.formatPhone = function formatPhone(input) {
    let value = input.value.replace(/\D/g, ""); // Remove non-digits

    if (value.length > 10) value = value.slice(0, 10); // Limit to 10 digits

    let formatted = value;

    if (value.length >= 1) {
        formatted = `(${value.slice(0, 3)}`;
    }
    if (value.length >= 4) {
        formatted = `(${value.slice(0, 3)})-${value.slice(3, 6)}`;
    }
    if (value.length >= 7) {
        formatted = `(${value.slice(0, 3)})-${value.slice(3, 6)}-${value.slice(6, 10)}`;
    }

    input.value = formatted;
};

window.validatePhone = function validatePhone(input) {
    const pattern = /^\(\d{3}\)-\d{3}-\d{4}$/;
    if (!pattern.test(input.value)) {
        input.focus();
    }
};

// deliver information
window.toggleDiv = function toggleDiv() {
    const checkbox = document.getElementById("toggleCheckbox");
    const div = document.getElementById("deliveryDiv");
    div.style.display = checkbox.checked ? "none" : "block";
};
