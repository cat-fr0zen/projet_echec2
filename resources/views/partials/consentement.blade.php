<?php
/**
 * Partiel: consentement cookies.
 */
$donneesConsentement = $donneesSite['consentement'] ?? $donneesSite['consent'];
?>

<div
    class="consent-gate"
    data-consent-root
    data-consent-cookie="<?= e($donneesConsentement['nom_cookie'] ?? $donneesConsentement['cookie_name'] ?? 'site_consent') ?>"
    role="dialog"
    aria-modal="true"
    aria-labelledby="consent-title"
    aria-describedby="consent-description"
>
    <div class="consent-panel">
        <div class="consent-panel__body">
            <p class="eyebrow">Acc&egrave;s au site</p>
            <h2 id="consent-title"><?= e($donneesConsentement['titre'] ?? $donneesConsentement['title'] ?? '') ?></h2>
            <p id="consent-description" class="consent-text"><?= e($donneesConsentement['introduction'] ?? $donneesConsentement['intro'] ?? '') ?></p>

            <div class="consent-highlight-list" aria-label="Cookies essentiels">
                <p class="consent-highlight-item">S&eacute;curit&eacute; du site</p>
                <p class="consent-highlight-item">Session membre</p>
                <p class="consent-highlight-item">Th&egrave;me facultatif</p>
            </div>

            <p class="consent-text consent-text--compact">
                Les cookies essentiels restent n&eacute;cessaires au bon fonctionnement du site.
                Le cookie de th&egrave;me reste optionnel.
            </p>
        </div>

        <div class="consent-panel__footer">
            <div class="button-row consent-actions">
                <button
                    type="button"
                    class="button button-secondary"
                    data-consent-continue
                    onclick="return window.__siteConsentChoice && window.__siteConsentChoice('essential', event);"
                    ontouchend="return window.__siteConsentChoice && window.__siteConsentChoice('essential', event);"
                >
                    Continuer sans accepter
                </button>
                <button
                    type="button"
                    class="button button-primary"
                    data-consent-accept
                    onclick="return window.__siteConsentChoice && window.__siteConsentChoice('accepted', event);"
                    ontouchend="return window.__siteConsentChoice && window.__siteConsentChoice('accepted', event);"
                >
                    Autoriser aussi le th&egrave;me
                </button>
            </div>
            <p class="consent-text consent-note">Vous pouvez modifier ce choix plus tard depuis le site.</p>
        </div>
    </div>
</div>

<script>
    (function () {
        var consentRoot = document.querySelector("[data-consent-root]");
        var consentHandled = false;

        if (!consentRoot) {
            return;
        }

        var cookieName = consentRoot.getAttribute("data-consent-cookie") || "site_consent";

        function writeCookie(name, value, days) {
            var lifetime = typeof days === "number" ? days : 365;
            var expiresAt = new Date(Date.now() + lifetime * 24 * 60 * 60 * 1000).toUTCString();
            var secureFlag = window.location.protocol === "https:" ? "; Secure" : "";
            document.cookie = name + "=" + encodeURIComponent(value) + "; expires=" + expiresAt + "; path=/; SameSite=Strict" + secureFlag;
        }

        function unlockConsent() {
            consentRoot.setAttribute("hidden", "hidden");
            consentRoot.style.setProperty("display", "none", "important");
            consentRoot.style.setProperty("visibility", "hidden", "important");
            consentRoot.style.setProperty("opacity", "0", "important");
            consentRoot.style.setProperty("pointer-events", "none", "important");
            document.body.classList.remove("consent-locked");
            document.body.style.overflow = "";
            document.documentElement.style.overflow = "";

            window.setTimeout(function () {
                if (consentRoot && consentRoot.parentNode) {
                    consentRoot.parentNode.removeChild(consentRoot);
                }
            }, 80);
        }

        function applyConsent(level, event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }

            if (consentHandled || consentRoot.hidden) {
                return false;
            }

            consentHandled = true;

            if (level === "accepted") {
                writeCookie(cookieName, "accepted");
                writeCookie("site_cookie_level", "essential-preferences");
                unlockConsent();
                return false;
            }

            writeCookie(cookieName, "essential");
            writeCookie("site_cookie_level", "essential-only");
            unlockConsent();
            return false;
        }

        window.__siteConsentChoice = applyConsent;
    })();
</script>
