function getCookieValue(name) {
    const escapedName = name.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    const match = document.cookie.match(new RegExp(`(?:^|; )${escapedName}=([^;]*)`));

    return match ? decodeURIComponent(match[1]) : "";
}

function setCookieValue(name, value, days = 365) {
    const expiresAt = new Date(Date.now() + days * 24 * 60 * 60 * 1000).toUTCString();
    document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expiresAt}; path=/; SameSite=Lax`;
}

function getFocusableElements(root) {
    if (!(root instanceof HTMLElement)) {
        return [];
    }

    return Array.from(
        root.querySelectorAll(
            'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
        )
    ).filter((element) => {
        if (!(element instanceof HTMLElement)) {
            return false;
        }

        return !element.closest("[hidden]");
    });
}

function trapFocus(event, root) {
    if (event.key !== "Tab") {
        return;
    }

    const focusableElements = getFocusableElements(root);

    if (focusableElements.length === 0) {
        event.preventDefault();
        return;
    }

    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];
    const activeElement = document.activeElement;

    if (event.shiftKey && activeElement === firstElement) {
        event.preventDefault();
        lastElement.focus();
    } else if (!event.shiftKey && activeElement === lastElement) {
        event.preventDefault();
        firstElement.focus();
    }
}

function initConsentGate() {
    const consentRoot = document.querySelector("[data-consent-root]");

    if (!consentRoot) {
        return;
    }

    const cookieName = consentRoot.getAttribute("data-consent-cookie") || "site_consent";
    const acceptButton = consentRoot.querySelector("[data-consent-accept]");
    const checkboxes = Array.from(consentRoot.querySelectorAll("[data-consent-checkbox]"));
    const firstCheckbox = checkboxes[0];
    const hasAccepted = getCookieValue(cookieName) === "accepted";
    const handleKeydown = (event) => {
        if (!consentRoot.hidden) {
            trapFocus(event, consentRoot);
        }
    };

    function unlockSite() {
        consentRoot.setAttribute("hidden", "hidden");
        document.body.classList.remove("consent-locked");
        document.removeEventListener("keydown", handleKeydown);
    }

    function updateButtonState() {
        const allChecked = checkboxes.every((checkbox) => checkbox.checked);

        if (acceptButton instanceof HTMLButtonElement) {
            acceptButton.disabled = !allChecked;
        }
    }

    if (hasAccepted) {
        unlockSite();
        return;
    }

    document.body.classList.add("consent-locked");
    consentRoot.removeAttribute("hidden");
    document.addEventListener("keydown", handleKeydown);

    if (firstCheckbox instanceof HTMLElement) {
        firstCheckbox.focus();
    }

    checkboxes.forEach((checkbox) => {
        checkbox.addEventListener("change", updateButtonState);
    });

    updateButtonState();

    if (acceptButton instanceof HTMLButtonElement) {
        acceptButton.addEventListener("click", () => {
            setCookieValue(cookieName, "accepted");
            setCookieValue("site_cookie_level", "essential-preferences");
            unlockSite();
        });
    }
}

function initThemeToggle() {
    const themeToggle = document.querySelector("[data-theme-toggle]");

    if (!(themeToggle instanceof HTMLButtonElement)) {
        return;
    }

    function applyTheme(theme) {
        document.body.setAttribute("data-theme", theme);
        themeToggle.setAttribute("aria-pressed", theme === "dark" ? "true" : "false");
        themeToggle.setAttribute("aria-label", theme === "dark" ? "Activer le theme clair" : "Activer le theme sombre");
    }

    const savedTheme = getCookieValue("site_theme");

    if (savedTheme === "dark" || savedTheme === "light") {
        applyTheme(savedTheme);
    }

    themeToggle.addEventListener("click", () => {
        const currentTheme = document.body.getAttribute("data-theme") === "dark" ? "dark" : "light";
        const nextTheme = currentTheme === "dark" ? "light" : "dark";
        applyTheme(nextTheme);
        setCookieValue("site_theme", nextTheme);
    });
}

function initStickyHeader() {
    const siteHeader = document.querySelector("[data-site-header]");

    if (!(siteHeader instanceof HTMLElement)) {
        return;
    }

    let ticking = false;

    function syncHeaderState() {
        siteHeader.classList.toggle("is-condensed", window.scrollY > 48);
        ticking = false;
    }

    function requestSync() {
        if (ticking) {
            return;
        }

        ticking = true;
        window.requestAnimationFrame(syncHeaderState);
    }

    syncHeaderState();
    window.addEventListener("scroll", requestSync, { passive: true });
    window.addEventListener("resize", requestSync);
}

function initBurgerMenu() {
    const burgerToggle = document.querySelector("[data-burger-toggle]");
    const burgerPanel = document.querySelector("[data-burger-panel]");
    const siteHeader = document.querySelector("[data-site-header]");
    let previousFocusedElement = null;

    if (!(burgerToggle instanceof HTMLButtonElement) || !(burgerPanel instanceof HTMLElement)) {
        return;
    }

    function setOpenState(isOpen) {
        burgerToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
        burgerPanel.hidden = !isOpen;
        document.body.classList.toggle("burger-open", isOpen);
        siteHeader?.classList.toggle("is-menu-open", isOpen);

        if (isOpen) {
            previousFocusedElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
            const [firstFocusableElement] = getFocusableElements(burgerPanel);

            if (firstFocusableElement instanceof HTMLElement) {
                firstFocusableElement.focus();
            }
        } else if (previousFocusedElement instanceof HTMLElement) {
            previousFocusedElement.focus();
            previousFocusedElement = null;
        }
    }

    burgerToggle.addEventListener("click", () => {
        const isOpen = burgerToggle.getAttribute("aria-expanded") === "true";
        setOpenState(!isOpen);
    });

    document.addEventListener("click", (event) => {
        const target = event.target;

        if (!(target instanceof Node)) {
            return;
        }

        if (!burgerPanel.contains(target) && !burgerToggle.contains(target)) {
            setOpenState(false);
        }
    });

    document.addEventListener("keydown", (event) => {
        if (!burgerPanel.hidden) {
            trapFocus(event, burgerPanel);
        }

        if (event.key === "Escape") {
            setOpenState(false);
        }
    });
}

function initAuthModal() {
    const modalRoot = document.querySelector("[data-auth-modal]");

    if (!(modalRoot instanceof HTMLElement)) {
        return;
    }

    const openButtons = Array.from(document.querySelectorAll("[data-auth-open]"));
    const closeButtons = Array.from(modalRoot.querySelectorAll("[data-auth-close]"));
    const tabButtons = Array.from(modalRoot.querySelectorAll("[data-auth-tab-trigger]"));
    const panels = Array.from(modalRoot.querySelectorAll("[data-auth-panel]"));
    const initialOpenState = modalRoot.getAttribute("data-auth-open-state") === "true";
    let previousFocusedElement = null;
    let currentTab = modalRoot.getAttribute("data-auth-tab") || "login";

    function renderTab(tabName) {
        currentTab = tabName === "register" ? "register" : "login";

        tabButtons.forEach((button) => {
            const isActive = button.getAttribute("data-auth-tab-trigger") === currentTab;
            button.setAttribute("aria-selected", isActive ? "true" : "false");
            button.classList.toggle("is-active", isActive);
        });

        panels.forEach((panel) => {
            const isActive = panel.getAttribute("data-auth-panel") === currentTab;
            panel.hidden = !isActive;
        });
    }

    function openModal(tabName) {
        renderTab(tabName || currentTab);
        previousFocusedElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        modalRoot.hidden = false;
        modalRoot.setAttribute("aria-hidden", "false");
        document.body.classList.add("modal-open");

        const [firstFocusableElement] = getFocusableElements(modalRoot);

        if (firstFocusableElement instanceof HTMLElement) {
            firstFocusableElement.focus();
        }
    }

    function closeModal() {
        modalRoot.hidden = true;
        modalRoot.setAttribute("aria-hidden", "true");
        document.body.classList.remove("modal-open");

        if (previousFocusedElement instanceof HTMLElement) {
            previousFocusedElement.focus();
            previousFocusedElement = null;
        }
    }

    openButtons.forEach((button) => {
        button.addEventListener("click", () => {
            openModal(button.getAttribute("data-auth-tab") || "login");
        });
    });

    closeButtons.forEach((button) => {
        button.addEventListener("click", closeModal);
    });

    tabButtons.forEach((button) => {
        button.addEventListener("click", () => {
            renderTab(button.getAttribute("data-auth-tab-trigger") || "login");
        });
    });

    modalRoot.addEventListener("click", (event) => {
        if (event.target === modalRoot) {
            closeModal();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (!modalRoot.hidden) {
            trapFocus(event, modalRoot);
        }

        if (event.key === "Escape" && !modalRoot.hidden) {
            closeModal();
        }
    });

    renderTab(currentTab);

    if (initialOpenState) {
        openModal(currentTab);
    } else {
        closeModal();
    }
}

function initPieceCarousel() {
    const carouselRoot = document.querySelector("[data-piece-carousel]");

    if (!carouselRoot) {
        return;
    }

    const slides = Array.from(carouselRoot.querySelectorAll("[data-piece-slide]"));
    const indicators = Array.from(carouselRoot.querySelectorAll("[data-piece-indicator]"));
    const previousButton = carouselRoot.querySelector("[data-piece-prev]");
    const nextButton = carouselRoot.querySelector("[data-piece-next]");
    const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    const tiltCards = Array.from(carouselRoot.querySelectorAll("[data-piece-tilt]"));
    const autoPlayDuration = Number(carouselRoot.getAttribute("data-autoplay-ms")) || 6800;
    let activeIndex = slides.findIndex((slide) => slide.classList.contains("is-active"));
    let intervalId = null;

    if (slides.length === 0) {
        return;
    }

    if (activeIndex < 0) {
        activeIndex = 0;
    }

    function renderSlide(nextIndex) {
        activeIndex = (nextIndex + slides.length) % slides.length;

        slides.forEach((slide, index) => {
            const isActive = index === activeIndex;
            slide.classList.toggle("is-active", isActive);
            slide.setAttribute("aria-hidden", isActive ? "false" : "true");
        });

        indicators.forEach((indicator, index) => {
            indicator.classList.toggle("is-active", index === activeIndex);
            indicator.setAttribute("aria-pressed", index === activeIndex ? "true" : "false");
        });
    }

    function setInteractionState(isInteracting) {
        carouselRoot.classList.toggle("is-interacting", isInteracting);
    }

    function stopAutoPlay() {
        if (intervalId !== null) {
            window.clearInterval(intervalId);
            intervalId = null;
        }
    }

    function startAutoPlay() {
        if (reducedMotion) {
            return;
        }

        stopAutoPlay();
        intervalId = window.setInterval(() => {
            renderSlide(activeIndex + 1);
        }, autoPlayDuration);
    }

    if (previousButton instanceof HTMLButtonElement) {
        previousButton.addEventListener("click", () => {
            renderSlide(activeIndex - 1);
            startAutoPlay();
        });
    }

    if (nextButton instanceof HTMLButtonElement) {
        nextButton.addEventListener("click", () => {
            renderSlide(activeIndex + 1);
            startAutoPlay();
        });
    }

    indicators.forEach((indicator, index) => {
        indicator.addEventListener("click", () => {
            renderSlide(index);
            startAutoPlay();
        });
    });

    tiltCards.forEach((card) => {
        card.addEventListener("pointermove", (event) => {
            if (reducedMotion) {
                return;
            }

            const bounds = card.getBoundingClientRect();
            const pointerX = (event.clientX - bounds.left) / bounds.width;
            const pointerY = (event.clientY - bounds.top) / bounds.height;
            const rotateY = (pointerX - 0.5) * 22;
            const rotateX = (0.5 - pointerY) * 18;

            card.style.setProperty("--tilt-x", `${rotateX}deg`);
            card.style.setProperty("--tilt-y", `${rotateY}deg`);
            setInteractionState(true);
            stopAutoPlay();
        });

        card.addEventListener("pointerleave", () => {
            card.style.setProperty("--tilt-x", "0deg");
            card.style.setProperty("--tilt-y", "0deg");
            setInteractionState(false);
            startAutoPlay();
        });
    });

    carouselRoot.addEventListener("mouseenter", stopAutoPlay);
    carouselRoot.addEventListener("mouseleave", startAutoPlay);
    carouselRoot.addEventListener("focusin", stopAutoPlay);
    carouselRoot.addEventListener("focusout", startAutoPlay);
    carouselRoot.addEventListener("keydown", (event) => {
        if (event.key === "ArrowLeft") {
            event.preventDefault();
            renderSlide(activeIndex - 1);
            startAutoPlay();
        }

        if (event.key === "ArrowRight") {
            event.preventDefault();
            renderSlide(activeIndex + 1);
            startAutoPlay();
        }
    });

    renderSlide(activeIndex);
    startAutoPlay();
}

function initSettingsActions() {
    const resetButtons = Array.from(document.querySelectorAll("[data-reset-consent]"));

    resetButtons.forEach((button) => {
        button.addEventListener("click", () => {
            setCookieValue("site_consent", "", -1);
            setCookieValue("site_cookie_level", "", -1);
            window.location.reload();
        });
    });
}

document.addEventListener("DOMContentLoaded", () => {
    initConsentGate();
    initThemeToggle();
    initStickyHeader();
    initBurgerMenu();
    initAuthModal();
    initPieceCarousel();
    initSettingsActions();
});
