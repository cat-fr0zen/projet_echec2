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
    document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expiresAt}; path=/; SameSite=Lax`;
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
        lastElement.focus();
    } else if (!event.shiftKey && activeElement === lastElement) {
        event.preventDefault();
        firstElement.focus();
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

    flashMessages.forEach((message) => {
        if (!(message instanceof HTMLElement)) {
            return;
        }

        const dismissButton = message.querySelector("[data-flash-dismiss]");
        const dismiss = () => {
            message.style.opacity = "0";
            message.style.transform = "translateY(-8px)";
            window.setTimeout(() => {
                message.remove();
            }, 220);
        };

        if (dismissButton instanceof HTMLButtonElement) {
            dismissButton.addEventListener("click", dismiss);
        }
    });
}

/**
 * Gere le menu burger (mobile).
 */
function initBurgerMenu() {
    const burgerToggle = document.querySelector("[data-burger-toggle]");
    const burgerPanel = document.querySelector("[data-burger-panel]");
    const burgerCloseButton = document.querySelector("[data-burger-close]");
    const siteHeader = document.querySelector("[data-site-header]");
    const panelGap = 10;
    const panelViewportPadding = 12;
    let previousFocusedElement = null;

    if (!(burgerToggle instanceof HTMLButtonElement) || !(burgerPanel instanceof HTMLElement)) {
        return;
    }

    function clampPanelPosition(value, minimum, maximum) {
        return Math.min(Math.max(value, minimum), maximum);
    }

    function syncBurgerPanelPosition() {
        if (burgerPanel.hidden) {
            return;
        }

        const viewportWidth = document.documentElement.clientWidth || window.innerWidth;
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
        const triggerRect = burgerToggle.getBoundingClientRect();
        const panelWidth = Math.min(
            burgerPanel.offsetWidth || 352,
            viewportWidth - panelViewportPadding * 2
        );
        const maximumLeft = Math.max(
            panelViewportPadding,
            viewportWidth - panelWidth - panelViewportPadding
        );
        const panelLeft = clampPanelPosition(
            triggerRect.right - panelWidth,
            panelViewportPadding,
            maximumLeft
        );
        const panelTop = Math.max(panelViewportPadding, triggerRect.bottom + panelGap);
        const panelMaxHeight = Math.max(220, viewportHeight - panelTop - panelViewportPadding);

        burgerPanel.style.setProperty("--burger-panel-left", `${Math.round(panelLeft)}px`);
        burgerPanel.style.setProperty("--burger-panel-top", `${Math.round(panelTop)}px`);
        burgerPanel.style.setProperty("--burger-panel-max-height", `${Math.round(panelMaxHeight)}px`);
    }

    function setOpenState(isOpen, shouldRestoreFocus = true) {
        burgerToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
        burgerToggle.setAttribute("aria-label", isOpen ? "Fermer le menu" : "Ouvrir le menu");
        burgerPanel.hidden = !isOpen;
        burgerPanel.setAttribute("aria-hidden", isOpen ? "false" : "true");
        document.body.classList.toggle("burger-open", isOpen);
        siteHeader?.classList.toggle("is-menu-open", isOpen);

        if (isOpen) {
            previousFocusedElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
            syncBurgerPanelPosition();
            const [firstFocusableElement] = getFocusableElements(burgerPanel);

            if (firstFocusableElement instanceof HTMLElement) {
                firstFocusableElement.focus();
            }
        } else if (shouldRestoreFocus && previousFocusedElement instanceof HTMLElement) {
            previousFocusedElement.focus();
            previousFocusedElement = null;
        } else {
            previousFocusedElement = null;
        }

        resetHorizontalScroll();
    }

    burgerToggle.addEventListener("click", () => {
        const isOpen = burgerToggle.getAttribute("aria-expanded") === "true";
        setOpenState(!isOpen);
    });

    window.addEventListener("resize", syncBurgerPanelPosition);
    window.addEventListener("orientationchange", syncBurgerPanelPosition);
    window.addEventListener("scroll", syncBurgerPanelPosition, { passive: true });

    if (window.visualViewport) {
        window.visualViewport.addEventListener("resize", syncBurgerPanelPosition);
        window.visualViewport.addEventListener("scroll", syncBurgerPanelPosition);
    }

    if (burgerCloseButton instanceof HTMLButtonElement) {
        burgerCloseButton.addEventListener("click", () => {
            setOpenState(false);
        });
    }

    burgerPanel.querySelectorAll("a").forEach((link) => {
        link.addEventListener("click", () => {
            setOpenState(false, false);
        });
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
    const initialOpenState = modalRoot.getAttribute("data-auth-open-state") === "true";
    let previousFocusedElement = null;
    let currentTab = modalRoot.getAttribute("data-auth-tab") || "connexion";

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
        const errorSummary = modalRoot.querySelector(".auth-errors");

        if (errorSummary instanceof HTMLElement) {
            errorSummary.focus();
            return;
        }

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
        formData.append("jeton_csrf", csrfToken);
        formData.append("dammier_puzzle_id", String(puzzle.dammier_id || ""));
        formData.append("dammier_week_key", String(puzzle.dammier_week_key || ""));
        formData.append("dammier_moves_count", String(movesCount));
        formData.append("dammier_elapsed_seconds", String(Math.max(1, Math.floor((Date.now() - startedAt) / 1000))));

        window.fetch(submitUrl, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
            body: formData,
        })
            .then((response) => response.json())
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
    document.querySelectorAll("[data-confirm-delete]").forEach((form) => {
        form.addEventListener("submit", (event) => {
            if (!window.confirm("Supprimer definitivement cet article ?")) {
                event.preventDefault();
            }
        });
    });
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

        if (previousFocusedElement instanceof HTMLElement) {
            previousFocusedElement.focus();
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

        const [firstFocusableElement] = getFocusableElements(modal);

        if (firstFocusableElement instanceof HTMLElement) {
            firstFocusableElement.focus();
        }
    }

    openButtons.forEach((button) => {
        button.addEventListener("click", () => {
            openModal(button.getAttribute("data-legal-open") || "", button);
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
    initDeleteConfirmations();
    initLegalModals();
    initSettingsActions();
});

window.addEventListener("pageshow", scheduleHorizontalScrollReset);
window.addEventListener("resize", scheduleHorizontalScrollReset);
window.addEventListener("orientationchange", scheduleHorizontalScrollReset);
