// Fix: Blocked aria-hidden on focused element (e.g. #junction-modal) — HSOverlay focuses the overlay (tabindex=-1)
// and some manual close paths set aria-hidden="true" while descendant still has focus. Blur before hiding.
// See https://w3c.github.io/aria/#aria-hidden
export const patchHsOverlayAriaHidden = () => {
    try {
        const HS = window.HSOverlay || (typeof HSOverlay !== "undefined" ? HSOverlay : null);
        if (!HS || HS._ariaFixPatched) return;
        const proto = HS.prototype;
        const origClose = proto.close;
        proto.close = function (...args) {
            try {
                const el = this.el;
                if (el && (el === document.activeElement || el.contains(document.activeElement))) {
                    document.activeElement?.blur?.();
                    if (el === document.activeElement || el.contains(document.activeElement)) {
                        el.blur?.();
                    }
                }
            } catch {}
            return origClose.apply(this, args);
        };
        HS._ariaFixPatched = true;
    } catch {}
};

export const setupAriaHiddenObserver = () => {
    try {
        const observer = new MutationObserver((mutations) => {
            for (const m of mutations) {
                if (m.attributeName !== "aria-hidden") continue;
                const el = m.target;
                if (!(el instanceof HTMLElement)) continue;
                if (el.getAttribute("aria-hidden") !== "true") continue;
                if (el === document.activeElement || el.contains(document.activeElement)) {
                    document.activeElement?.blur?.();
                    el.blur?.();
                }
            }
        });
        observer.observe(document.body, { attributes: true, subtree: true, attributeFilter: ["aria-hidden"] });
    } catch {}
};
