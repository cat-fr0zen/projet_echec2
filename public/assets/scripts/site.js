/**
 * site.js
 *
 * JavaScript "vanilla" du site.
 *
 * Responsabilites:
 * - consentement cookies (blocage du site tant que non accepte)
 * - theme clair/sombre (cookie `site_theme`)
 * - header sticky, menu burger
 * - modale d'authentification (tabs + focus trap)
 * - carrousel (pieces) et micro-interactions
 * - actions page Paramètres (reset consentement)
 */

/**
 * Lit un cookie par nom (decode URL).
 *
 * @param {string} name Nom du cookie.
 * @returns {string} Valeur, ou chaine vide.
 */
function getCookieValue(name) {
    const escapedName = name.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    const match = document.cookie.match(new RegExp(`(?:^|; )${escapedName}=([^;]*)`));

    return match ? decodeURIComponent(match[1]) : "";
}

/**
 * Ecrit un cookie simple (SameSite=Lax).
 *
 * @param {string} name Nom du cookie.
 * @param {string} value Valeur a stocker.
 * @param {number} days Duree de vie en jours (defaut 365).
 */
function setCookieValue(name, value, days = 365) {
    const expiresAt = new Date(Date.now() + days * 24 * 60 * 60 * 1000).toUTCString();
    const secureFlag = window.location.protocol === "https:" ? "; Secure" : "";
    document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expiresAt}; path=/; SameSite=Strict${secureFlag}`;
}

/**
 * Corrige les positions horizontales conservees par certains navigateurs mobiles.
 */
function resetHorizontalScroll() {
    const scrollingElement = document.scrollingElement || document.documentElement;
    const currentVerticalScroll = window.scrollY || scrollingElement.scrollTop || 0;

    scrollingElement.scrollLeft = 0;
    document.documentElement.scrollLeft = 0;
    document.body.scrollLeft = 0;

    if (window.scrollX !== 0) {
        window.scrollTo(0, currentVerticalScroll);
    }
}

function scheduleHorizontalScrollReset() {
    resetHorizontalScroll();
    window.requestAnimationFrame(resetHorizontalScroll);
    window.setTimeout(resetHorizontalScroll, 80);
    window.setTimeout(resetHorizontalScroll, 240);
}

/**
 * Place le focus sans declencher de scroll parasite sur mobile.
 *
 * @param {HTMLElement | null | undefined} element Element a focus.
 */
function focusElementWithoutScroll(element) {
    if (!(element instanceof HTMLElement)) {
        return;
    }

    try {
        element.focus({ preventScroll: true });
    } catch (error) {
        element.focus();
    }
}

/**
 * Retourne la liste des elements focusables dans un conteneur (accessibilite).
 *
 * @param {HTMLElement} root Conteneur.
 * @returns {HTMLElement[]} Elements focusables.
 */
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

/**
 * Empeche le focus de sortir d'une modale (Tab / Shift+Tab).
 *
 * @param {KeyboardEvent} event Evenement clavier.
 * @param {HTMLElement} root Conteneur de la modale.
 */
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
        focusElementWithoutScroll(lastElement);
    } else if (!event.shiftKey && activeElement === lastElement) {
        event.preventDefault();
        focusElementWithoutScroll(firstElement);
    }
}

/**
 * Gere l'ecran de consentement cookies.
 *
 * Flux:
 * - si cookie `site_consent=accepted`: on debloque le site
 * - sinon on affiche la modale, on lock le body, et on active le focus trap
 */
function initConsentGate() {
    const consentRoot = document.querySelector("[data-consent-root]");

    if (!consentRoot) {
        return;
    }

    const cookieName = consentRoot.getAttribute("data-consent-cookie") || "site_consent";
    const acceptButton = consentRoot.querySelector("[data-consent-accept]");
    const continueButton = consentRoot.querySelector("[data-consent-continue]");
    const savedLevel = getCookieValue(cookieName);
    const hasAccepted = savedLevel === "accepted" || savedLevel === "essential";
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

    if (hasAccepted) {
        unlockSite();
        return;
    }

    document.body.classList.add("consent-locked");
    consentRoot.removeAttribute("hidden");
    document.addEventListener("keydown", handleKeydown);

    if (continueButton instanceof HTMLElement) {
        focusElementWithoutScroll(continueButton);
    }

    if (acceptButton instanceof HTMLButtonElement) {
        acceptButton.addEventListener("click", () => {
            setCookieValue(cookieName, "accepted");
            setCookieValue("site_cookie_level", "essential-preferences");
            unlockSite();
        });
    }

    if (continueButton instanceof HTMLButtonElement) {
        continueButton.addEventListener("click", () => {
            setCookieValue(cookieName, "essential");
            setCookieValue("site_cookie_level", "essential-only");
            unlockSite();
        });
    }
}

/**
 * Gere le bouton de theme (clair/sombre) via cookie `site_theme`.
 */
function initThemeToggle() {
    const themeToggle = document.querySelector("[data-theme-toggle]");

    if (!(themeToggle instanceof HTMLButtonElement)) {
        return;
    }

    function applyTheme(theme) {
        document.body.setAttribute("data-theme", theme);
        themeToggle.setAttribute("aria-pressed", theme === "dark" ? "true" : "false");
        themeToggle.setAttribute("aria-label", theme === "dark" ? "Activer le thème clair" : "Activer le thème sombre");
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

/**
 * Reduit visuellement le header quand on scrolle (classe CSS `is-condensed`).
 */
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

/**
 * Gere l'affichage des messages flash (success/error) et leur disparition.
 */
function initFlashMessages() {
    const flashMessages = Array.from(document.querySelectorAll(".flash-message"));
    const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    flashMessages.forEach((message) => {
        if (!(message instanceof HTMLElement)) {
            return;
        }

        const dismissButton = message.querySelector("[data-flash-dismiss]");
        const flashStack = message.closest(".flash-stack");
        const dismissDelay = message.classList.contains("flash-message--error") ? 8000 : 6500;
        let autoDismissId = null;

        const dismiss = () => {
            if (message.classList.contains("is-dismissing")) {
                return;
            }

            window.clearTimeout(autoDismissId);
            message.classList.add("is-dismissing");

            window.setTimeout(() => {
                message.remove();

                if (flashStack instanceof HTMLElement && !flashStack.querySelector(".flash-message")) {
                    flashStack.remove();
                }
            }, reducedMotion ? 0 : 220);
        };

        const scheduleAutoDismiss = () => {
            window.clearTimeout(autoDismissId);
            autoDismissId = window.setTimeout(dismiss, dismissDelay);
        };

        const pauseAutoDismiss = () => {
            window.clearTimeout(autoDismissId);
        };

        if (dismissButton instanceof HTMLButtonElement) {
            dismissButton.addEventListener("click", dismiss);
        }

        message.addEventListener("mouseenter", pauseAutoDismiss);
        message.addEventListener("mouseleave", scheduleAutoDismiss);
        message.addEventListener("focusin", pauseAutoDismiss);
        message.addEventListener("focusout", (event) => {
            const nextFocusedElement = event.relatedTarget;

            if (nextFocusedElement instanceof Node && message.contains(nextFocusedElement)) {
                return;
            }

            scheduleAutoDismiss();
        });

        scheduleAutoDismiss();
    });
}

/**
 * Gere le menu burger (mobile).
 */
function initBurgerMenu() {
    const burgerToggle = document.querySelector("[data-burger-toggle]");
    const burgerPanel = document.querySelector("[data-burger-panel]");
    const burgerBackdrop = document.querySelector("[data-burger-backdrop]");
    const burgerCloseButtons = Array.from(document.querySelectorAll("[data-burger-close]"));
    const siteHeader = document.querySelector("[data-site-header]");
    let previousFocusedElement = null;
    let layoutSyncFrame = null;

    if (!(burgerToggle instanceof HTMLButtonElement) || !(burgerPanel instanceof HTMLElement)) {
        return;
    }

    burgerToggle.setAttribute("aria-haspopup", "dialog");

    function syncPanelLayout() {
        if (!(siteHeader instanceof HTMLElement)) {
            return;
        }

        const headerRect = siteHeader.getBoundingClientRect();
        const toggleRect = burgerToggle.getBoundingClientRect();
        const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
        const panelTop = Math.max(toggleRect.bottom + 4, headerRect.top + 4, 8);
        const panelRight = Math.max(viewportWidth - toggleRect.right, 8);
        const panelMaxHeight = Math.max(viewportHeight - panelTop - 12, 240);
        const backdropTop = Math.max(headerRect.bottom + 4, 0);

        document.documentElement.style.setProperty("--burger-panel-top", `${Math.round(panelTop)}px`);
        document.documentElement.style.setProperty("--burger-panel-right", `${Math.round(panelRight)}px`);
        document.documentElement.style.setProperty("--burger-panel-max-height", `${Math.round(panelMaxHeight)}px`);
        document.documentElement.style.setProperty("--burger-backdrop-top", `${Math.round(backdropTop)}px`);
    }

    function requestPanelLayoutSync() {
        if (layoutSyncFrame !== null) {
            return;
        }

        layoutSyncFrame = window.requestAnimationFrame(() => {
            layoutSyncFrame = null;

            if (!burgerPanel.hidden) {
                syncPanelLayout();
            }
        });
    }

    function setOpenState(isOpen, shouldRestoreFocus = true) {
        burgerToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
        burgerToggle.setAttribute("aria-label", isOpen ? "Fermer le menu" : "Ouvrir le menu");
        burgerPanel.hidden = !isOpen;
        burgerPanel.setAttribute("aria-hidden", isOpen ? "false" : "true");
        if (burgerBackdrop instanceof HTMLElement) {
            burgerBackdrop.hidden = !isOpen;
            burgerBackdrop.setAttribute("aria-hidden", isOpen ? "false" : "true");
        }
        document.body.classList.toggle("burger-open", isOpen);
        siteHeader?.classList.toggle("is-menu-open", isOpen);

        if (isOpen) {
            previousFocusedElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
            syncPanelLayout();
            burgerPanel.scrollTop = 0;
            const [firstFocusableElement] = getFocusableElements(burgerPanel);
            focusElementWithoutScroll(firstFocusableElement || burgerPanel);
        } else if (shouldRestoreFocus && previousFocusedElement instanceof HTMLElement) {
            focusElementWithoutScroll(previousFocusedElement);
            previousFocusedElement = null;
        } else {
            previousFocusedElement = null;
        }

        scheduleHorizontalScrollReset();
    }

    burgerToggle.addEventListener("click", () => {
        const isOpen = burgerToggle.getAttribute("aria-expanded") === "true";
        setOpenState(!isOpen);
    });

    burgerCloseButtons.forEach((button) => {
        button.addEventListener("click", () => {
            setOpenState(false);
        });
    });

    burgerPanel.querySelectorAll("a").forEach((link) => {
        link.addEventListener("click", () => {
            setOpenState(false, false);
        });
    });

    document.addEventListener("keydown", (event) => {
        if (!burgerPanel.hidden) {
            trapFocus(event, burgerPanel);
        }

        if (event.key === "Escape" && !burgerPanel.hidden) {
            event.preventDefault();
            setOpenState(false);
        }
    });

    window.addEventListener("scroll", requestPanelLayoutSync, { passive: true });
    window.addEventListener("resize", requestPanelLayoutSync);
    window.addEventListener("orientationchange", requestPanelLayoutSync);
}

/**
 * Gere la modale d'authentification (ouverture, onglets, focus trap).
 */
function initAuthModal() {
    const modalRoot = document.querySelector("[data-auth-modal]");

    if (!(modalRoot instanceof HTMLElement)) {
        return;
    }

    const openButtons = Array.from(document.querySelectorAll("[data-auth-open]"));
    const closeButtons = Array.from(modalRoot.querySelectorAll("[data-auth-close]"));
    const tabButtons = Array.from(modalRoot.querySelectorAll("[data-auth-tab-trigger]"));
    const panels = Array.from(modalRoot.querySelectorAll("[data-auth-panel]"));
    const modalPanel = modalRoot.querySelector(".auth-modal-panel");
    const initialOpenState = modalRoot.getAttribute("data-auth-open-state") === "true";
    const modalId = modalRoot.id || "auth-modal";
    let previousFocusedElement = null;
    let currentTab = modalRoot.getAttribute("data-auth-tab") || "connexion";

    modalRoot.id = modalId;

    function renderTab(tabName) {
        currentTab = tabName === "inscription" ? "inscription" : "connexion";

        tabButtons.forEach((button) => {
            const isActive = button.getAttribute("data-auth-tab-trigger") === currentTab;
            button.setAttribute("aria-selected", isActive ? "true" : "false");
            button.setAttribute("tabindex", isActive ? "0" : "-1");
            button.classList.toggle("is-active", isActive);
        });

        panels.forEach((panel) => {
            const isActive = panel.getAttribute("data-auth-panel") === currentTab;
            panel.hidden = !isActive;
            panel.setAttribute("aria-hidden", isActive ? "false" : "true");
        });
    }

    function openModal(tabName) {
        renderTab(tabName || currentTab);
        previousFocusedElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        modalRoot.hidden = false;
        modalRoot.setAttribute("aria-hidden", "false");
        document.body.classList.add("modal-open");
        if (modalPanel instanceof HTMLElement) {
            modalPanel.scrollTop = 0;
        }
        const errorSummary = modalRoot.querySelector(".auth-errors");

        if (errorSummary instanceof HTMLElement) {
            focusElementWithoutScroll(errorSummary);
            return;
        }

        const [firstFocusableElement] = getFocusableElements(modalRoot);

        focusElementWithoutScroll(firstFocusableElement);
    }

    function closeModal() {
        modalRoot.hidden = true;
        modalRoot.setAttribute("aria-hidden", "true");
        document.body.classList.remove("modal-open");

        if (previousFocusedElement instanceof HTMLElement) {
            focusElementWithoutScroll(previousFocusedElement);
            previousFocusedElement = null;
        }
    }

    openButtons.forEach((button) => {
        button.setAttribute("aria-haspopup", "dialog");
        button.setAttribute("aria-controls", modalId);
        button.addEventListener("click", () => {
            openModal(button.getAttribute("data-auth-tab") || "connexion");
        });
    });

    closeButtons.forEach((button) => {
        button.addEventListener("click", closeModal);
    });

    tabButtons.forEach((button) => {
        button.addEventListener("click", () => {
            renderTab(button.getAttribute("data-auth-tab-trigger") || "connexion");
        });

        button.addEventListener("keydown", (event) => {
            const currentIndex = tabButtons.indexOf(button);

            if (currentIndex < 0) {
                return;
            }

            let nextIndex = currentIndex;

            if (event.key === "ArrowRight" || event.key === "ArrowDown") {
                nextIndex = (currentIndex + 1) % tabButtons.length;
            } else if (event.key === "ArrowLeft" || event.key === "ArrowUp") {
                nextIndex = (currentIndex - 1 + tabButtons.length) % tabButtons.length;
            } else if (event.key === "Home") {
                nextIndex = 0;
            } else if (event.key === "End") {
                nextIndex = tabButtons.length - 1;
            } else {
                return;
            }

            event.preventDefault();
            const nextButton = tabButtons[nextIndex];

            if (!(nextButton instanceof HTMLButtonElement)) {
                return;
            }

            renderTab(nextButton.getAttribute("data-auth-tab-trigger") || "connexion");
            nextButton.focus();
        });
    });

    document.addEventListener("keydown", (event) => {
        if (!modalRoot.hidden) {
            trapFocus(event, modalRoot);
        }

        if (event.key === "Escape" && !modalRoot.hidden) {
            event.preventDefault();
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

/**
 * Gere le carrousel des pieces (navigation, autoplay, acces clavier).
 */
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

/**
 * Gere le puzzle hebdomadaire "dammier":
 * - rendu du damier a partir d'une FEN
 * - sequence de coups a choix multiples
 * - chrono local + envoi du score au classement si le membre est connecte
 */
function initDammierPuzzle() {
    const dammierRoot = document.querySelector("[data-dammier-root]");

    if (!(dammierRoot instanceof HTMLElement)) {
        return;
    }

    const payloadNode = dammierRoot.querySelector("[data-dammier-payload]");
    const boardNode = dammierRoot.querySelector("[data-dammier-board]");
    const promptNode = dammierRoot.querySelector("[data-dammier-prompt]");
    const optionsNode = dammierRoot.querySelector("[data-dammier-options]");
    const feedbackNode = dammierRoot.querySelector("[data-dammier-feedback]");
    const timerNode = dammierRoot.querySelector("[data-dammier-timer]");
    const resetButton = dammierRoot.querySelector("[data-dammier-reset]");
    const rankingListNode = dammierRoot.querySelector("[data-dammier-ranking-list]");
    const rankingEmptyNode = dammierRoot.querySelector("[data-dammier-ranking-empty]");
    const submitUrl = dammierRoot.getAttribute("data-dammier-submit-url") || window.location.pathname;
    const csrfToken = dammierRoot.getAttribute("data-dammier-csrf") || "";
    const isAuthenticated = dammierRoot.getAttribute("data-dammier-is-authenticated") === "true";

    if (!(payloadNode instanceof HTMLScriptElement) || !(boardNode instanceof HTMLElement) || !(optionsNode instanceof HTMLElement)) {
        return;
    }

    let payload = null;

    try {
        payload = JSON.parse(payloadNode.textContent || "{}");
    } catch (error) {
        return;
    }

    const puzzle = payload?.dammier_puzzle;

    if (!puzzle || !Array.isArray(puzzle.dammier_steps)) {
        return;
    }

    const unicodePieces = {
        p: "♟",
        r: "♜",
        n: "♞",
        b: "♝",
        q: "♛",
        k: "♚",
        P: "♙",
        R: "♖",
        N: "♘",
        B: "♗",
        Q: "♕",
        K: "♔",
    };
    const files = ["a", "b", "c", "d", "e", "f", "g", "h"];
    let stepIndex = 0;
    let movesCount = 0;
    let startedAt = Date.now();
    let timerId = null;
    let isSolved = false;

    function formatElapsed(seconds) {
        const safeSeconds = Math.max(0, seconds);
        const minutes = String(Math.floor(safeSeconds / 60)).padStart(2, "0");
        const remainder = String(safeSeconds % 60).padStart(2, "0");

        return `${minutes}:${remainder}`;
    }

    function parseFenBoard(fen) {
        const [boardPart] = String(fen || "").split(" ");
        const ranks = boardPart.split("/");
        const squares = [];

        ranks.forEach((rank, rankIndex) => {
            Array.from(rank).forEach((character) => {
                const emptyCount = Number(character);

                if (!Number.isNaN(emptyCount) && emptyCount > 0) {
                    for (let index = 0; index < emptyCount; index += 1) {
                        squares.push({
                            piece: "",
                            coordinate: `${files[squares.length % 8]}${8 - rankIndex}`,
                        });
                    }

                    return;
                }

                squares.push({
                    piece: unicodePieces[character] || "",
                    coordinate: `${files[squares.length % 8]}${8 - rankIndex}`,
                });
            });
        });

        return squares;
    }

    function renderBoard() {
        const squares = parseFenBoard(puzzle.dammier_fen);

        boardNode.innerHTML = "";

        squares.forEach((square, index) => {
            const squareNode = document.createElement("div");
            const row = Math.floor(index / 8);
            const column = index % 8;
            const isLight = (row + column) % 2 === 0;

            squareNode.className = "dammier_square";
            squareNode.setAttribute("data-dammier-color", isLight ? "light" : "dark");
            squareNode.setAttribute("data-dammier-coordinate", square.coordinate);

            if (square.piece !== "") {
                const pieceNode = document.createElement("span");
                pieceNode.className = "dammier_piece";
                pieceNode.textContent = square.piece;
                squareNode.appendChild(pieceNode);
            }

            boardNode.appendChild(squareNode);
        });
    }

    function setFeedback(message, state) {
        if (!(feedbackNode instanceof HTMLElement)) {
            return;
        }

        feedbackNode.textContent = message;
        feedbackNode.classList.remove("is-success", "is-error");

        if (state === "success" || state === "error") {
            feedbackNode.classList.add(`is-${state}`);
        }
    }

    function renderRanking(scores) {
        if (!(rankingListNode instanceof HTMLElement)) {
            return;
        }

        rankingListNode.innerHTML = "";

        scores.forEach((score) => {
            const item = document.createElement("li");
            const name = document.createElement("span");
            const moves = document.createElement("span");
            const elapsed = document.createElement("span");

            item.className = "dammier_ranking_item";
            name.textContent = String(score.dammier_display_name || "Membre");
            moves.textContent = `${Number(score.dammier_moves_count || 0)} coups`;
            elapsed.textContent = formatElapsed(Number(score.dammier_elapsed_seconds || 0));

            item.append(name, moves, elapsed);
            rankingListNode.appendChild(item);
        });

        if (rankingEmptyNode instanceof HTMLElement) {
            rankingEmptyNode.hidden = scores.length > 0;
        }
    }

    function stopTimer() {
        if (timerId !== null) {
            window.clearInterval(timerId);
            timerId = null;
        }
    }

    function startTimer() {
        stopTimer();

        const tick = () => {
            if (timerNode instanceof HTMLElement) {
                timerNode.textContent = formatElapsed(Math.floor((Date.now() - startedAt) / 1000));
            }
        };

        tick();
        timerId = window.setInterval(tick, 1000);
    }

    function renderStep() {
        const currentStep = puzzle.dammier_steps[stepIndex];

        if (!(promptNode instanceof HTMLElement)) {
            return;
        }

        optionsNode.innerHTML = "";

        if (!currentStep) {
            promptNode.textContent = "Puzzle terminé.";
            return;
        }

        promptNode.textContent = currentStep.dammier_prompt || "Choisissez le meilleur coup.";

        currentStep.dammier_options.forEach((option) => {
            const button = document.createElement("button");
            button.type = "button";
            button.className = "button dammier_option";
            button.textContent = option.dammier_label || option.dammier_move;
            button.setAttribute("data-dammier-move", option.dammier_move || "");
            button.addEventListener("click", () => {
                handleMove(option, button);
            });
            optionsNode.appendChild(button);
        });
    }

    function submitScore() {
        if (!isAuthenticated) {
            setFeedback("Puzzle résolu. Connecte-toi pour enregistrer ton score dans le classement.", "success");
            return;
        }

        const formData = new FormData();
        formData.append("action", "soumettre_resultat_dammier");
        formData.append("_token", csrfToken);
        formData.append("jeton_csrf", csrfToken);
        formData.append("dammier_puzzle_id", String(puzzle.dammier_id || ""));
        formData.append("dammier_week_key", String(puzzle.dammier_week_key || ""));
        formData.append("dammier_moves_count", String(movesCount));
        formData.append("dammier_elapsed_seconds", String(Math.max(1, Math.floor((Date.now() - startedAt) / 1000))));

        window.fetch(submitUrl, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "X-CSRF-TOKEN": csrfToken,
                "X-Requested-With": "XMLHttpRequest",
            },
            body: formData,
        })
            .then(async (response) => {
                let result = null;

                try {
                    result = await response.json();
                } catch (error) {
                    result = null;
                }

                if (response.status === 419) {
                    return {
                        success: false,
                        message: "Ta session a expiré. Recharge la page puis reconnecte-toi si besoin.",
                    };
                }

                if (result && typeof result === "object") {
                    return result;
                }

                return {
                    success: false,
                    message: response.ok
                        ? "Le score n'a pas pu être confirmé."
                        : "Le score n'a pas pu être envoyé.",
                };
            })
            .then((result) => {
                if (!result?.success) {
                    setFeedback(result?.message || "Score non enregistré.", "error");
                    return;
                }

                setFeedback("Puzzle résolu. Ton score est enregistré dans le classement.", "success");
                renderRanking(Array.isArray(result.dammier_classement) ? result.dammier_classement : []);
            })
            .catch(() => {
                setFeedback("Le puzzle est résolu, mais l'enregistrement du score a échoué.", "error");
            });
    }

    function handleMove(option, button) {
        if (isSolved) {
            return;
        }

        movesCount += 1;
        const expectedMove = String(puzzle.dammier_solution[stepIndex] || "");
        const isCorrect = String(option.dammier_move || "") === expectedMove;

        if (isCorrect) {
            button.classList.add("is-correct");
            stepIndex += 1;

            if (stepIndex >= puzzle.dammier_solution.length) {
                isSolved = true;
                stopTimer();
                optionsNode.innerHTML = "";
                promptNode.textContent = "Bravo, le casse-tête est terminé.";
                submitScore();
                return;
            }

            setFeedback("Bien joué. Passe à l'étape suivante.", "success");
            window.setTimeout(() => {
                renderStep();
            }, 250);
            return;
        }

        button.classList.add("is-wrong");
        setFeedback("Ce n'est pas le bon coup. Le score ajoute une tentative.", "error");
    }

    function resetPuzzle() {
        stepIndex = 0;
        movesCount = 0;
        startedAt = Date.now();
        isSolved = false;
        renderBoard();
        renderStep();
        setFeedback("Le score compte le nombre total de tentatives jusqu’à la résolution.", "");
        startTimer();
    }

    renderBoard();
    renderRanking(Array.isArray(payload?.dammier_classement) ? payload.dammier_classement : []);
    renderStep();
    startTimer();

    if (resetButton instanceof HTMLButtonElement) {
        resetButton.addEventListener("click", resetPuzzle);
    }
}

/**
 * Gere les actions de la page Paramètres (ex: reset du consentement).
 */
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

/**
 * Gere l'editeur d'articles par blocs.
 */
function initArticleEditor() {
    const editorRoot = document.querySelector("[data-article-editor]");
    const openButton = document.querySelector("[data-article-editor-open]");

    if (!(editorRoot instanceof HTMLElement)) {
        return;
    }

    const form = editorRoot.querySelector("[data-article-editor-form]");
    const blockList = editorRoot.querySelector("[data-article-block-list]");
    const payloadInput = editorRoot.querySelector("[data-article-blocks-payload]");
    const titleInput = editorRoot.querySelector("[data-article-title]");
    const authorInput = editorRoot.querySelector("[data-article-author]");
    const previewTitle = editorRoot.querySelector("[data-article-preview-title]");
    const previewAuthor = editorRoot.querySelector("[data-article-preview-author]");
    const previewBody = editorRoot.querySelector("[data-article-preview-body]");
    const statusNode = editorRoot.querySelector("[data-article-editor-status]");
    const addButtons = Array.from(editorRoot.querySelectorAll("[data-add-article-block]"));
    let blockCounter = 0;
    let previewUrls = [];

    if (!(form instanceof HTMLFormElement) || !(blockList instanceof HTMLElement) || !(payloadInput instanceof HTMLInputElement)) {
        return;
    }

    function setStatus(message) {
        if (statusNode instanceof HTMLElement) {
            statusNode.textContent = message;
        }
    }

    function createBlockId() {
        blockCounter += 1;
        return `${Date.now().toString(36)}_${blockCounter.toString(36)}`;
    }

    function buildBlockActions(block) {
        const actions = document.createElement("div");
        actions.className = "article-editor-block-actions";

        const moveUpButton = document.createElement("button");
        moveUpButton.type = "button";
        moveUpButton.className = "article-editor-icon-button";
        moveUpButton.textContent = "↑";
        moveUpButton.setAttribute("aria-label", "Monter ce bloc");

        const moveDownButton = document.createElement("button");
        moveDownButton.type = "button";
        moveDownButton.className = "article-editor-icon-button";
        moveDownButton.textContent = "↓";
        moveDownButton.setAttribute("aria-label", "Descendre ce bloc");

        const removeButton = document.createElement("button");
        removeButton.type = "button";
        removeButton.className = "article-editor-icon-button article-editor-icon-button--danger";
        removeButton.textContent = "×";
        removeButton.setAttribute("aria-label", "Supprimer ce bloc");

        moveUpButton.addEventListener("click", () => {
            const previous = block.previousElementSibling;
            if (previous instanceof HTMLElement) {
                blockList.insertBefore(block, previous);
                updateEditorState("Bloc deplacé vers le haut.");
            }
        });

        moveDownButton.addEventListener("click", () => {
            const next = block.nextElementSibling;
            if (next instanceof HTMLElement) {
                blockList.insertBefore(next, block);
                updateEditorState("Bloc deplacé vers le bas.");
            }
        });

        removeButton.addEventListener("click", () => {
            block.remove();
            updateEditorState("Bloc supprimé.");
        });

        actions.append(moveUpButton, moveDownButton, removeButton);
        return actions;
    }

    function createTextBlock(type) {
        const block = document.createElement("section");
        const blockId = createBlockId();
        block.className = "article-editor-block";
        block.dataset.articleBlockType = type;

        const header = document.createElement("header");
        header.className = "article-editor-block-header";

        const title = document.createElement("h3");
        title.textContent = type === "sous_titre" ? "Sous-titre" : "Paragraphe";

        const field = type === "sous_titre" ? document.createElement("input") : document.createElement("textarea");
        field.className = "article-editor-block-field";
        field.name = `article_block_${blockId}`;
        field.required = true;
        field.maxLength = type === "sous_titre" ? 140 : 3000;

        if (field instanceof HTMLTextAreaElement) {
            field.rows = 5;
        }

        field.setAttribute("aria-label", title.textContent);
        field.addEventListener("input", () => updateEditorState());

        header.append(title, buildBlockActions(block));
        block.append(header, field);
        return block;
    }

    function createMediaBlock(type) {
        const block = document.createElement("section");
        const blockId = createBlockId();
        const isVideo = type === "video";
        block.className = "article-editor-block article-editor-block--media";
        block.dataset.articleBlockType = type;

        const header = document.createElement("header");
        header.className = "article-editor-block-header";

        const title = document.createElement("h3");
        title.textContent = isVideo ? "Vidéo" : "Image / GIF";

        const grid = document.createElement("div");
        grid.className = "article-editor-media-grid";

        const fileLabel = document.createElement("label");
        const fileLabelText = document.createElement("span");
        fileLabel.className = "form-group";
        fileLabelText.textContent = "Fichier";
        const fileInput = document.createElement("input");
        fileInput.type = "file";
        fileInput.name = `article_media_${blockId}`;
        fileInput.accept = isVideo ? "video/mp4,video/webm,video/quicktime" : "image/jpeg,image/png,image/webp,image/gif";
        fileInput.required = true;

        const altLabel = document.createElement("label");
        const altLabelText = document.createElement("span");
        altLabel.className = "form-group";
        altLabelText.textContent = isVideo ? "Description accessible" : "Texte alternatif";
        const altInput = document.createElement("input");
        altInput.type = "text";
        altInput.maxLength = 180;
        altInput.required = true;

        const captionLabel = document.createElement("label");
        const captionLabelText = document.createElement("span");
        captionLabel.className = "form-group";
        captionLabelText.textContent = "Légende";
        const captionInput = document.createElement("input");
        captionInput.type = "text";
        captionInput.maxLength = 220;

        fileInput.addEventListener("change", () => updateEditorState("Média ajouté à l'aperçu."));
        altInput.addEventListener("input", () => updateEditorState());
        captionInput.addEventListener("input", () => updateEditorState());

        fileLabel.append(fileLabelText, fileInput);
        altLabel.append(altLabelText, altInput);
        captionLabel.append(captionLabelText, captionInput);
        grid.append(fileLabel, altLabel, captionLabel);
        header.append(title, buildBlockActions(block));
        block.append(header, grid);
        return block;
    }

    function readBlocks() {
        return Array.from(blockList.querySelectorAll("[data-article-block-type]")).map((block) => {
            const type = block.getAttribute("data-article-block-type") || "paragraphe";

            if (type === "paragraphe" || type === "sous_titre") {
                const field = block.querySelector(".article-editor-block-field");
                return {
                    type,
                    texte: field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement ? field.value.trim() : "",
                };
            }

            const fileInput = block.querySelector('input[type="file"]');
            const textInputs = Array.from(block.querySelectorAll('input[type="text"]'));

            return {
                type,
                nom_champ_fichier: fileInput instanceof HTMLInputElement ? fileInput.name : "",
                texte_alternatif: textInputs[0] instanceof HTMLInputElement ? textInputs[0].value.trim() : "",
                legende: textInputs[1] instanceof HTMLInputElement ? textInputs[1].value.trim() : "",
            };
        });
    }

    function clearPreviewUrls() {
        previewUrls.forEach((url) => URL.revokeObjectURL(url));
        previewUrls = [];
    }

    function renderPreview(blocks) {
        if (previewTitle instanceof HTMLElement && titleInput instanceof HTMLInputElement) {
            previewTitle.textContent = titleInput.value.trim() || "Nouvel article";
        }

        if (previewAuthor instanceof HTMLElement && authorInput instanceof HTMLInputElement) {
            previewAuthor.textContent = authorInput.value.trim() || "Auteur";
        }

        if (!(previewBody instanceof HTMLElement)) {
            return;
        }

        clearPreviewUrls();
        previewBody.replaceChildren();

        blocks.forEach((blockData, index) => {
            const type = blockData.type || "paragraphe";

            if (type === "sous_titre" && blockData.texte) {
                const subtitle = document.createElement("h3");
                subtitle.className = "published-article-subtitle";
                subtitle.textContent = blockData.texte;
                previewBody.append(subtitle);
                return;
            }

            if (type === "paragraphe" && blockData.texte) {
                const paragraph = document.createElement("p");
                paragraph.textContent = blockData.texte;
                previewBody.append(paragraph);
                return;
            }

            if (type === "image" || type === "video") {
                const block = blockList.querySelectorAll("[data-article-block-type]")[index];
                const fileInput = block instanceof HTMLElement ? block.querySelector('input[type="file"]') : null;
                const file = fileInput instanceof HTMLInputElement && fileInput.files ? fileInput.files[0] : null;

                if (!file) {
                    return;
                }

                const figure = document.createElement("figure");
                figure.className = "published-article-media";
                const previewUrl = URL.createObjectURL(file);
                previewUrls.push(previewUrl);

                if (type === "video") {
                    const video = document.createElement("video");
                    video.controls = true;
                    video.preload = "metadata";
                    video.src = previewUrl;
                    video.setAttribute("aria-label", blockData.texte_alternatif || "Video de l'article");
                    figure.append(video);
                } else {
                    const image = document.createElement("img");
                    image.src = previewUrl;
                    image.alt = blockData.texte_alternatif || "";
                    figure.append(image);
                }

                if (blockData.legende) {
                    const caption = document.createElement("figcaption");
                    caption.textContent = blockData.legende;
                    figure.append(caption);
                }

                previewBody.append(figure);
            }
        });
    }

    function updateEditorState(message = "") {
        const blocks = readBlocks();
        payloadInput.value = JSON.stringify(blocks);
        renderPreview(blocks);

        if (message) {
            setStatus(message);
        }
    }

    function addBlock(type, shouldFocus = true) {
        const block = type === "image" || type === "video" ? createMediaBlock(type) : createTextBlock(type);
        blockList.append(block);
        updateEditorState(`${type === "sous_titre" ? "Sous-titre" : type === "paragraphe" ? "Paragraphe" : "Média"} ajouté.`);

        if (shouldFocus) {
            const focusTarget = block.querySelector("input, textarea, button");
            if (focusTarget instanceof HTMLElement) {
                focusTarget.focus();
            }
        }
    }

    addButtons.forEach((button) => {
        button.addEventListener("click", () => {
            addBlock(button.getAttribute("data-add-article-block") || "paragraphe");
        });
    });

    [titleInput, authorInput].forEach((field) => {
        if (field instanceof HTMLInputElement) {
            field.addEventListener("input", () => updateEditorState());
        }
    });

    if (openButton instanceof HTMLButtonElement) {
        openButton.addEventListener("click", () => {
            editorRoot.hidden = false;
            openButton.setAttribute("aria-expanded", "true");
            addBlock("paragraphe", false);

            if (titleInput instanceof HTMLInputElement) {
                titleInput.focus();
            }
        }, { once: true });
    }

    form.addEventListener("submit", () => {
        updateEditorState();
    });
}

function initDeleteConfirmations() {
    const modalRoot = document.querySelector("[data-confirm-modal]");
    const submitButton = modalRoot?.querySelector("[data-confirm-modal-submit]");
    const cancelButtons = Array.from(modalRoot?.querySelectorAll("[data-confirm-modal-cancel]") || []);
    const descriptionNode = modalRoot?.querySelector("#confirm-modal-description");
    let pendingForm = null;
    let previousFocusedElement = null;

    if (!(modalRoot instanceof HTMLElement) || !(submitButton instanceof HTMLButtonElement)) {
        return;
    }

    function buildMessage(form) {
        const customMessage = form.getAttribute("data-confirm-message");

        if (customMessage) {
            return customMessage;
        }

        const titleNode = form.closest(".info-card, .course-document-row, .course-rubrique, .panel")?.querySelector("h3, h4, .course-document-title");
        const title = titleNode instanceof HTMLElement ? titleNode.textContent.trim() : "";

        if (title) {
            return `Tu es sur le point de supprimer « ${title} ». Cette action est definitive et l'element ne pourra pas etre restaure depuis l'interface.`;
        }

        return "Tu es sur le point de supprimer cet element. Cette action est definitive et ne pourra pas etre annulee depuis l'interface.";
    }

    function closeModal(restoreFocus = true) {
        modalRoot.hidden = true;
        modalRoot.setAttribute("aria-hidden", "true");
        document.body.classList.remove("modal-open");

        if (restoreFocus && previousFocusedElement instanceof HTMLElement) {
            focusElementWithoutScroll(previousFocusedElement);
        }

        previousFocusedElement = null;
        pendingForm = null;
    }

    function openModal(form) {
        pendingForm = form;
        previousFocusedElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;

        if (descriptionNode instanceof HTMLElement) {
            descriptionNode.textContent = buildMessage(form);
        }

        modalRoot.hidden = false;
        modalRoot.setAttribute("aria-hidden", "false");
        document.body.classList.add("modal-open");
        focusElementWithoutScroll(submitButton);
    }

    document.querySelectorAll("[data-confirm-delete]").forEach((form) => {
        form.addEventListener("submit", (event) => {
            if (form.dataset.confirmAccepted === "true") {
                delete form.dataset.confirmAccepted;
                return;
            }

            event.preventDefault();
            openModal(form);
        });
    });

    submitButton.addEventListener("click", () => {
        if (!(pendingForm instanceof HTMLFormElement)) {
            closeModal();
            return;
        }

        pendingForm.dataset.confirmAccepted = "true";
        const formToSubmit = pendingForm;
        closeModal(false);
        formToSubmit.requestSubmit();
    });

    cancelButtons.forEach((button) => {
        button.addEventListener("click", () => {
            closeModal();
        });
    });

    document.addEventListener("keydown", (event) => {
        if (modalRoot.hidden) {
            return;
        }

        trapFocus(event, modalRoot);

        if (event.key === "Escape") {
            event.preventDefault();
            closeModal();
        }
    });
}

function initAdminTabs() {
    const tabRoot = document.querySelector("[data-admin-tabs]");
    const tabButtons = Array.from(document.querySelectorAll("[data-admin-tab-trigger]"));
    const tabPanels = Array.from(document.querySelectorAll("[data-admin-tab-panel]"));

    if (!(tabRoot instanceof HTMLElement) || tabButtons.length === 0 || tabPanels.length === 0) {
        return;
    }

    const knownTabs = new Set(
        tabButtons
            .map((button) => button.getAttribute("data-admin-tab-trigger") || "")
            .filter(Boolean)
    );

    function resolveTabFromHash() {
        const hash = window.location.hash.replace(/^#/, "");

        if (!hash) {
            return "";
        }

        if (knownTabs.has(hash)) {
            return hash;
        }

        const targetElement = document.getElementById(hash);

        if (!(targetElement instanceof HTMLElement)) {
            return "";
        }

        return targetElement.getAttribute("data-admin-tab-panel") || "";
    }

    function activateTab(tabName, updateHash = false) {
        const nextTab = knownTabs.has(tabName) ? tabName : tabButtons[0].getAttribute("data-admin-tab-trigger") || "";

        tabButtons.forEach((button) => {
            const isActive = (button.getAttribute("data-admin-tab-trigger") || "") === nextTab;
            button.classList.toggle("is-active", isActive);
            button.setAttribute("aria-pressed", isActive ? "true" : "false");
            button.setAttribute("tabindex", isActive ? "0" : "-1");
        });

        tabPanels.forEach((panel) => {
            const shouldShow = (panel.getAttribute("data-admin-tab-panel") || "") === nextTab;
            panel.hidden = !shouldShow;
            panel.setAttribute("aria-hidden", shouldShow ? "false" : "true");
        });

        if (updateHash && nextTab) {
            window.history.replaceState(null, "", `#${nextTab}`);
        }
    }

    tabButtons.forEach((button) => {
        button.addEventListener("click", () => {
            activateTab(button.getAttribute("data-admin-tab-trigger") || "", true);
        });

        button.addEventListener("keydown", (event) => {
            const currentIndex = tabButtons.indexOf(button);

            if (currentIndex < 0) {
                return;
            }

            let nextIndex = currentIndex;

            if (event.key === "ArrowRight" || event.key === "ArrowDown") {
                nextIndex = (currentIndex + 1) % tabButtons.length;
            } else if (event.key === "ArrowLeft" || event.key === "ArrowUp") {
                nextIndex = (currentIndex - 1 + tabButtons.length) % tabButtons.length;
            } else if (event.key === "Home") {
                nextIndex = 0;
            } else if (event.key === "End") {
                nextIndex = tabButtons.length - 1;
            } else {
                return;
            }

            event.preventDefault();
            const nextButton = tabButtons[nextIndex];

            if (!(nextButton instanceof HTMLButtonElement)) {
                return;
            }

            focusElementWithoutScroll(nextButton);
            activateTab(nextButton.getAttribute("data-admin-tab-trigger") || "", true);
        });
    });

    window.addEventListener("hashchange", () => {
        activateTab(resolveTabFromHash(), false);
    });

    activateTab(resolveTabFromHash(), false);
}

function initCourseSearch() {
    const searchRoots = Array.from(document.querySelectorAll("[data-course-search]"));

    searchRoots.forEach((searchRoot) => {
        if (!(searchRoot instanceof HTMLElement)) {
            return;
        }

        const rubriqueRoot = searchRoot.closest(".course-rubrique");
        const input = searchRoot.querySelector("[data-course-search-input]");
        const resetButton = searchRoot.querySelector("[data-course-search-reset]");
        const statusNode = searchRoot.querySelector("[data-course-search-status]");
        const emptyState = rubriqueRoot?.querySelector("[data-course-search-empty]");
        const groups = Array.from(rubriqueRoot?.querySelectorAll("[data-course-search-group]") || []);
        const items = Array.from(rubriqueRoot?.querySelectorAll("[data-course-search-item]") || []);

        if (!(rubriqueRoot instanceof HTMLElement) || !(input instanceof HTMLInputElement) || items.length === 0) {
            return;
        }

        const defaultStatus = `${items.length} document${items.length > 1 ? "s" : ""} disponible${items.length > 1 ? "s" : ""}.`;
        const emptyMessage = searchRoot.getAttribute("data-course-search-empty-message") || "Aucun document ne correspond a cette recherche.";

        function normalizeText(value) {
            return String(value || "")
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .toLowerCase()
                .trim();
        }

        function setStatus(message) {
            if (statusNode instanceof HTMLElement) {
                statusNode.textContent = message;
            }
        }

        function updateSearch() {
            const query = normalizeText(input.value);
            let visibleCount = 0;

            groups.forEach((group) => {
                if (!(group instanceof HTMLElement)) {
                    return;
                }

                const groupText = normalizeText(group.getAttribute("data-course-search-text"));
                const subgroups = Array.from(group.querySelectorAll("[data-course-search-subgroup]"));
                const groupMatches = query !== "" && groupText.includes(query);

                subgroups.forEach((subgroup) => {
                    if (!(subgroup instanceof HTMLElement)) {
                        return;
                    }

                    const subgroupText = normalizeText(subgroup.getAttribute("data-course-search-text"));
                    const subgroupMatches = query !== "" && subgroupText.includes(query);
                    const subgroupItems = Array.from(subgroup.querySelectorAll("[data-course-search-item]"));

                    subgroupItems.forEach((item) => {
                        if (!(item instanceof HTMLElement)) {
                            return;
                        }

                        const itemText = normalizeText(item.getAttribute("data-course-search-text"));
                        const matches = query === "" || groupMatches || subgroupMatches || itemText.includes(query);

                        item.hidden = !matches;

                        if (matches) {
                            visibleCount += 1;
                        }
                    });
                });

                subgroups.forEach((subgroup) => {
                    if (!(subgroup instanceof HTMLElement)) {
                        return;
                    }

                    const subgroupVisibleItems = Array.from(subgroup.querySelectorAll("[data-course-search-item]")).filter((item) => item instanceof HTMLElement && !item.hidden);
                    const subgroupText = normalizeText(subgroup.getAttribute("data-course-search-text"));
                    const subgroupMatches = query !== "" && subgroupText.includes(query);
                    subgroup.hidden = subgroupVisibleItems.length === 0 && !subgroupMatches;
                });

                const hasVisibleSubgroup = subgroups.some((subgroup) => subgroup instanceof HTMLElement && !subgroup.hidden);
                const visibleItems = Array.from(group.querySelectorAll("[data-course-search-item]")).filter((item) => item instanceof HTMLElement && !item.hidden);
                group.hidden = visibleItems.length === 0 && !hasVisibleSubgroup && !groupMatches;
            });

            if (resetButton instanceof HTMLButtonElement) {
                resetButton.hidden = query === "";
            }

            if (emptyState instanceof HTMLElement) {
                emptyState.hidden = visibleCount !== 0;
            }

            if (query === "") {
                setStatus(defaultStatus);
                return;
            }

            if (visibleCount === 0) {
                setStatus(emptyMessage);
                return;
            }

            setStatus(`${visibleCount} resultat${visibleCount > 1 ? "s" : ""} pour "${input.value.trim()}".`);
        }

        input.addEventListener("input", updateSearch);

        if (resetButton instanceof HTMLButtonElement) {
            resetButton.addEventListener("click", () => {
                input.value = "";
                updateSearch();
                focusElementWithoutScroll(input);
            });
        }

        setStatus(defaultStatus);
    });
}

function initArticleSearch() {
    const searchRoot = document.querySelector("[data-article-search]");

    if (!(searchRoot instanceof HTMLElement)) {
        return;
    }

    const input = searchRoot.querySelector("[data-article-search-input]");
    const resetButton = searchRoot.querySelector("[data-article-search-reset]");
    const statusNode = searchRoot.querySelector("[data-article-search-status]");
    const listRoot = document.querySelector("[data-article-search-list]");
    const emptyState = document.querySelector("[data-article-search-empty]");
    const items = Array.from(document.querySelectorAll("[data-article-search-item]"));

    if (!(input instanceof HTMLInputElement) || !(listRoot instanceof HTMLElement) || items.length === 0) {
        return;
    }

    const normalize = (value) => value.toLocaleLowerCase("fr-FR").trim();

    const update = () => {
        const query = normalize(input.value);
        let visibleCount = 0;

        items.forEach((item) => {
            if (!(item instanceof HTMLElement)) {
                return;
            }

            const haystack = normalize(item.getAttribute("data-article-search-text") || "");
            const shouldShow = query === "" || haystack.includes(query);
            item.hidden = !shouldShow;

            if (shouldShow) {
                visibleCount += 1;
            }
        });

        if (emptyState instanceof HTMLElement) {
            emptyState.hidden = visibleCount !== 0;
        }

        if (statusNode instanceof HTMLElement) {
            statusNode.textContent = query === ""
                ? `${visibleCount} article${visibleCount > 1 ? "s" : ""} visible${visibleCount > 1 ? "s" : ""}.`
                : `${visibleCount} résultat${visibleCount > 1 ? "s" : ""} pour "${input.value.trim()}".`;
        }
    };

    input.addEventListener("input", update);

    if (resetButton instanceof HTMLButtonElement) {
        resetButton.addEventListener("click", () => {
            input.value = "";
            update();
            input.focus();
        });
    }

    update();
}


function initScrollJumpButton() {
    const button = document.querySelector("[data-scroll-jump]");
    const icon = button?.querySelector("[data-scroll-jump-icon]");
    const label = button?.querySelector("[data-scroll-jump-label]");
    const mobileQuery = window.matchMedia("(max-width: 640px)");

    if (!(button instanceof HTMLButtonElement)) {
        return;
    }

    function updateButtonState() {
        const scrollingElement = document.scrollingElement || document.documentElement;
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
        const maxScroll = Math.max(scrollingElement.scrollHeight - viewportHeight, 0);
        const currentScroll = window.scrollY || scrollingElement.scrollTop || 0;
        const canScroll = maxScroll > viewportHeight * 0.4;

        if (!mobileQuery.matches || !canScroll) {
            button.hidden = true;
            button.classList.remove("is-visible");
            return;
        }

        const shouldGoTop = currentScroll > maxScroll * 0.55;
        const nextLabel = shouldGoTop ? "Haut" : "Bas";
        const nextIcon = shouldGoTop ? "↑" : "↓";
        const nextAriaLabel = shouldGoTop ? "Aller en haut de la page" : "Aller en bas de la page";

        button.hidden = false;
        button.classList.add("is-visible");
        button.setAttribute("aria-label", nextAriaLabel);
        button.setAttribute("title", nextAriaLabel);
        button.dataset.scrollDirection = shouldGoTop ? "top" : "bottom";

        if (icon instanceof HTMLElement) {
            icon.textContent = nextIcon;
        }

        if (label instanceof HTMLElement) {
            label.textContent = nextLabel;
        }
    }

    button.addEventListener("click", () => {
        const direction = button.dataset.scrollDirection === "top" ? "top" : "bottom";
        const scrollingElement = document.scrollingElement || document.documentElement;
        const targetTop = direction === "top" ? 0 : scrollingElement.scrollHeight;

        window.scrollTo({
            top: targetTop,
            behavior: "smooth",
        });
    });

    window.addEventListener("scroll", updateButtonState, { passive: true });
    window.addEventListener("resize", updateButtonState);

    if (typeof mobileQuery.addEventListener === "function") {
        mobileQuery.addEventListener("change", updateButtonState);
    } else if (typeof mobileQuery.addListener === "function") {
        mobileQuery.addListener(updateButtonState);
    }

    updateButtonState();
}

function initAccessibilityTools() {
    const openButtons = Array.from(document.querySelectorAll("[data-accessibility-open]"));
    const panelRoot = document.querySelector("[data-accessibility-panel]");
    const closeButton = panelRoot?.querySelector("[data-accessibility-close]");
    const presetButton = panelRoot?.querySelector("[data-accessibility-preset]");
    const resetButton = panelRoot?.querySelector("[data-accessibility-reset]");
    const readButton = panelRoot?.querySelector("[data-accessibility-read]");
    const stopReadButton = panelRoot?.querySelector("[data-accessibility-stop-read]");
    const fontDecreaseButton = panelRoot?.querySelector("[data-accessibility-font-decrease]");
    const fontIncreaseButton = panelRoot?.querySelector("[data-accessibility-font-increase]");
    const fontValue = panelRoot?.querySelector("[data-accessibility-font-value]");
    const statusNode = panelRoot?.querySelector("[data-accessibility-status]");
    const readableFontToggle = panelRoot?.querySelector("[data-accessibility-readable-font]");
    const spacingToggle = panelRoot?.querySelector("[data-accessibility-spacing]");
    const contrastToggle = panelRoot?.querySelector("[data-accessibility-contrast]");
    const visibleActionsToggle = panelRoot?.querySelector("[data-accessibility-visible-actions]");
    const reducedMotionToggle = panelRoot?.querySelector("[data-accessibility-reduced-motion]");
    const storageKey = "site_accessibility_preferences";
    let previousFocusedElement = null;

    if (openButtons.length === 0 || !(panelRoot instanceof HTMLElement)) {
        return;
    }

    const defaultPreferences = {
        fontScale: 1,
        readableFont: false,
        spacing: false,
        contrast: false,
        visibleActions: false,
        reducedMotion: false,
    };

    function setStatus(message) {
        if (statusNode instanceof HTMLElement) {
            statusNode.textContent = message;
        }
    }

    function loadPreferences() {
        try {
            const serialized = window.localStorage.getItem(storageKey);

            if (!serialized) {
                return { ...defaultPreferences };
            }

            return { ...defaultPreferences, ...JSON.parse(serialized) };
        } catch (error) {
            return { ...defaultPreferences };
        }
    }

    let preferences = loadPreferences();

    function savePreferences() {
        window.localStorage.setItem(storageKey, JSON.stringify(preferences));
    }

    function syncControls() {
        if (fontValue instanceof HTMLElement) {
            fontValue.textContent = `${Math.round(preferences.fontScale * 100)} %`;
        }

        if (readableFontToggle instanceof HTMLInputElement) {
            readableFontToggle.checked = preferences.readableFont;
        }

        if (spacingToggle instanceof HTMLInputElement) {
            spacingToggle.checked = preferences.spacing;
        }

        if (contrastToggle instanceof HTMLInputElement) {
            contrastToggle.checked = preferences.contrast;
        }

        if (visibleActionsToggle instanceof HTMLInputElement) {
            visibleActionsToggle.checked = preferences.visibleActions;
        }

        if (reducedMotionToggle instanceof HTMLInputElement) {
            reducedMotionToggle.checked = preferences.reducedMotion;
        }
    }

    function applyPreferences(announceMessage = "") {
        document.documentElement.style.setProperty("--reading-font-scale", String(preferences.fontScale));
        document.body.setAttribute("data-readable-font", preferences.readableFont ? "true" : "false");
        document.body.setAttribute("data-readable-spacing", preferences.spacing ? "true" : "false");
        document.body.setAttribute("data-high-contrast", preferences.contrast ? "true" : "false");
        document.body.setAttribute("data-visible-actions", preferences.visibleActions ? "true" : "false");
        document.body.setAttribute("data-reduced-motion", preferences.reducedMotion ? "true" : "false");
        syncControls();

        if (announceMessage) {
            setStatus(announceMessage);
        }
    }

    function openPanel() {
        previousFocusedElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        panelRoot.hidden = false;
        panelRoot.setAttribute("aria-hidden", "false");
        openButtons.forEach((button) => {
            if (button instanceof HTMLButtonElement) {
                button.setAttribute("aria-expanded", "true");
            }
        });
        document.body.classList.add("modal-open");
        const focusTarget = panelRoot.querySelector(".accessibility-panel__close");
        focusElementWithoutScroll(focusTarget instanceof HTMLElement ? focusTarget : panelRoot);
    }

    function closePanel() {
        panelRoot.hidden = true;
        panelRoot.setAttribute("aria-hidden", "true");
        openButtons.forEach((button) => {
            if (button instanceof HTMLButtonElement) {
                button.setAttribute("aria-expanded", "false");
            }
        });
        document.body.classList.remove("modal-open");

        if (previousFocusedElement instanceof HTMLElement) {
            focusElementWithoutScroll(previousFocusedElement);
        }
    }

    function clampFontScale(value) {
        return Math.min(1.4, Math.max(0.9, Math.round(value * 100) / 100));
    }

    function updatePreference(key, value, statusMessage) {
        preferences = { ...preferences, [key]: value };
        savePreferences();
        applyPreferences(statusMessage);
    }

    openButtons.forEach((button) => {
        if (!(button instanceof HTMLButtonElement)) {
            return;
        }

        button.addEventListener("click", openPanel);
    });

    if (closeButton instanceof HTMLButtonElement) {
        closeButton.addEventListener("click", closePanel);
    }

    if (presetButton instanceof HTMLButtonElement) {
        presetButton.addEventListener("click", () => {
            preferences = {
                ...preferences,
                fontScale: 1.18,
                readableFont: true,
                spacing: true,
                contrast: true,
                visibleActions: true,
                reducedMotion: true,
            };
            savePreferences();
            applyPreferences("Le mode lecture confortable est active.");
        });
    }

    if (resetButton instanceof HTMLButtonElement) {
        resetButton.addEventListener("click", () => {
            preferences = { ...defaultPreferences };
            savePreferences();
            if ("speechSynthesis" in window) {
                window.speechSynthesis.cancel();
            }
            applyPreferences("Les reglages d'accessibilite ont ete reinitialises.");
        });
    }

    if (fontDecreaseButton instanceof HTMLButtonElement) {
        fontDecreaseButton.addEventListener("click", () => {
            updatePreference("fontScale", clampFontScale(preferences.fontScale - 0.05), "La taille du texte a ete reduite.");
        });
    }

    if (fontIncreaseButton instanceof HTMLButtonElement) {
        fontIncreaseButton.addEventListener("click", () => {
            updatePreference("fontScale", clampFontScale(preferences.fontScale + 0.05), "La taille du texte a ete augmentee.");
        });
    }

    [
        [readableFontToggle, "readableFont", "Police plus lisible mise a jour."],
        [spacingToggle, "spacing", "Espacement du texte mis a jour."],
        [contrastToggle, "contrast", "Contraste renforce mis a jour."],
        [visibleActionsToggle, "visibleActions", "Visibilite des actions mise a jour."],
        [reducedMotionToggle, "reducedMotion", "Reduction des animations mise a jour."],
    ].forEach(([input, key, message]) => {
        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        input.addEventListener("change", () => {
            updatePreference(key, input.checked, message);
        });
    });

    if (readButton instanceof HTMLButtonElement) {
        readButton.addEventListener("click", () => {
            if (!("speechSynthesis" in window) || typeof window.SpeechSynthesisUtterance !== "function") {
                setStatus("La synthese vocale n'est pas disponible sur cet appareil.");
                return;
            }

            const pageTitle = document.querySelector("main h1");
            const mainContent = document.querySelector("main");
            const text = [
                pageTitle instanceof HTMLElement ? pageTitle.textContent : "",
                mainContent instanceof HTMLElement ? mainContent.innerText : "",
            ]
                .filter(Boolean)
                .join(". ")
                .replace(/\s+/g, " ")
                .trim();

            if (text === "") {
                setStatus("Aucun contenu lisible n'a ete trouve sur cette page.");
                return;
            }

            window.speechSynthesis.cancel();
            const utterance = new window.SpeechSynthesisUtterance(text);
            utterance.lang = "fr-FR";
            utterance.rate = 0.95;
            utterance.onstart = () => setStatus("Lecture vocale demarree.");
            utterance.onend = () => setStatus("Lecture vocale terminee.");
            utterance.onerror = () => setStatus("La lecture vocale a rencontre un probleme.");
            window.speechSynthesis.speak(utterance);
        });
    }

    if (stopReadButton instanceof HTMLButtonElement) {
        stopReadButton.addEventListener("click", () => {
            if ("speechSynthesis" in window) {
                window.speechSynthesis.cancel();
                setStatus("Lecture vocale arretee.");
            }
        });
    }

    panelRoot.addEventListener("click", (event) => {
        if (event.target === panelRoot) {
            closePanel();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (panelRoot.hidden) {
            return;
        }

        trapFocus(event, panelRoot);

        if (event.key === "Escape") {
            event.preventDefault();
            closePanel();
        }
    });

    applyPreferences();
    setStatus("Choisis les reglages les plus confortables pour ta lecture.");
}

function initShopSidebarDrawer() {
    const toggleButton = document.querySelector("[data-shop-sidebar-toggle]");
    const toggleIcon = toggleButton?.querySelector("[data-shop-sidebar-toggle-icon]");
    const sidebar = document.querySelector("[data-shop-sidebar]");
    const mobileQuery = window.matchMedia("(max-width: 640px)");

    if (!(toggleButton instanceof HTMLButtonElement) || !(sidebar instanceof HTMLElement)) {
        return;
    }

    function setOpenState(isOpen) {
        if (!mobileQuery.matches) {
            sidebar.classList.remove("is-open");
            toggleButton.setAttribute("aria-expanded", "false");
            toggleButton.setAttribute("aria-label", "Ouvrir les filtres de la boutique");
            toggleButton.setAttribute("title", "Ouvrir les filtres");

            if (toggleIcon instanceof HTMLElement) {
                toggleIcon.innerHTML = "&gt;";
            }

            return;
        }

        sidebar.classList.toggle("is-open", isOpen);
        toggleButton.setAttribute("aria-expanded", isOpen ? "true" : "false");
        toggleButton.setAttribute("aria-label", isOpen ? "Fermer les filtres de la boutique" : "Ouvrir les filtres de la boutique");
        toggleButton.setAttribute("title", isOpen ? "Fermer les filtres" : "Ouvrir les filtres");

        if (toggleIcon instanceof HTMLElement) {
            toggleIcon.innerHTML = isOpen ? "&lt;" : "&gt;";
        }
    }

    toggleButton.addEventListener("click", () => {
        const isOpen = toggleButton.getAttribute("aria-expanded") === "true";
        setOpenState(!isOpen);
    });

    if (typeof mobileQuery.addEventListener === "function") {
        mobileQuery.addEventListener("change", () => setOpenState(false));
    } else if (typeof mobileQuery.addListener === "function") {
        mobileQuery.addListener(() => setOpenState(false));
    }

    setOpenState(false);
}

/**
 * Gere les pop-ups legaux du footer (documents obligatoires et registre cookies).
 */
function initLegalModals() {
    const openButtons = Array.from(document.querySelectorAll("[data-legal-open]"));
    const modalRoots = Array.from(document.querySelectorAll("[data-legal-modal]"));
    let previousFocusedElement = null;
    let currentModal = null;

    if (openButtons.length === 0 || modalRoots.length === 0) {
        return;
    }

    modalRoots.forEach((modal) => {
        const modalId = modal.getAttribute("data-legal-modal") || "";

        if (modalId !== "" && !modal.id) {
            modal.id = `legal-modal-${modalId}`;
        }
    });

    function closeModal(modal = currentModal) {
        if (!(modal instanceof HTMLElement)) {
            return;
        }

        modal.hidden = true;
        modal.setAttribute("aria-hidden", "true");

        if (currentModal === modal) {
            currentModal = null;
            document.body.classList.remove("modal-open");
        }

        openButtons.forEach((button) => {
            const controlsId = button.getAttribute("aria-controls");
            const buttonTargetsClosedModal = controlsId !== "" && controlsId === modal.id;
            button.setAttribute("aria-expanded", buttonTargetsClosedModal ? "false" : button.getAttribute("aria-expanded") || "false");
        });

        if (previousFocusedElement instanceof HTMLElement) {
            focusElementWithoutScroll(previousFocusedElement);
            previousFocusedElement = null;
        }
    }

    function openModal(modalId, triggerButton) {
        const modal = modalRoots.find((candidate) => candidate.getAttribute("data-legal-modal") === modalId);

        if (!(modal instanceof HTMLElement)) {
            return;
        }

        if (currentModal instanceof HTMLElement && currentModal !== modal) {
            currentModal.hidden = true;
            currentModal.setAttribute("aria-hidden", "true");
        }

        previousFocusedElement = triggerButton instanceof HTMLElement ? triggerButton : document.activeElement instanceof HTMLElement ? document.activeElement : null;
        currentModal = modal;
        modal.hidden = false;
        modal.setAttribute("aria-hidden", "false");
        document.body.classList.add("modal-open");

        openButtons.forEach((button) => {
            const targetsCurrentModal = button.getAttribute("data-legal-open") === modalId;
            button.setAttribute("aria-expanded", targetsCurrentModal ? "true" : "false");
        });

        const [firstFocusableElement] = getFocusableElements(modal);

        focusElementWithoutScroll(firstFocusableElement);
    }

    openButtons.forEach((button) => {
        const modalId = button.getAttribute("data-legal-open") || "";
        const targetModal = modalRoots.find((modal) => modal.getAttribute("data-legal-modal") === modalId);

        button.setAttribute("aria-haspopup", "dialog");
        button.setAttribute("aria-expanded", "false");

        if (targetModal instanceof HTMLElement && targetModal.id !== "") {
            button.setAttribute("aria-controls", targetModal.id);
        }

        button.addEventListener("click", () => {
            openModal(modalId, button);
        });
    });

    modalRoots.forEach((modal) => {
        modal.querySelectorAll("[data-legal-close]").forEach((button) => {
            button.addEventListener("click", () => {
                closeModal(modal instanceof HTMLElement ? modal : null);
            });
        });

        modal.addEventListener("click", (event) => {
            if (event.target === modal) {
                closeModal(modal instanceof HTMLElement ? modal : null);
            }
        });
    });

    document.addEventListener("keydown", (event) => {
        if (!(currentModal instanceof HTMLElement) || currentModal.hidden) {
            return;
        }

        trapFocus(event, currentModal);

        if (event.key === "Escape") {
            event.preventDefault();
            closeModal(currentModal);
        }
    });
}

document.addEventListener("DOMContentLoaded", () => {
    scheduleHorizontalScrollReset();
    initConsentGate();
    initThemeToggle();
    initStickyHeader();
    initFlashMessages();
    initBurgerMenu();
    initAuthModal();
    initPieceCarousel();
    initDammierPuzzle();
    initArticleEditor();
    initAdminTabs();
    initCourseSearch();
    initArticleSearch();
    initScrollJumpButton();
    initAccessibilityTools();
    initShopSidebarDrawer();
    initDeleteConfirmations();
    initLegalModals();
    initSettingsActions();
});

window.addEventListener("pageshow", scheduleHorizontalScrollReset);
window.addEventListener("resize", scheduleHorizontalScrollReset);
window.addEventListener("orientationchange", scheduleHorizontalScrollReset);
