import "../css/app.css";
import "./bootstrap.js";
import "preline";

// Fix: Blocked aria-hidden on focused element (e.g. #junction-modal) — HSOverlay focuses the overlay (tabindex=-1)
// and some manual close paths set aria-hidden="true" while descendant still has focus. Blur before hiding.
// See https://w3c.github.io/aria/#aria-hidden
const patchHsOverlayAriaHidden = () => {
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

const setupAriaHiddenObserver = () => {
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

if (typeof window !== "undefined") {
    patchHsOverlayAriaHidden();
    setupAriaHiddenObserver();
    window.addEventListener("load", patchHsOverlayAriaHidden);
    document.addEventListener("DOMContentLoaded", patchHsOverlayAriaHidden);
}

import { createApp, h } from "vue";
import { createInertiaApp, router } from "@inertiajs/vue3";

import L from "leaflet";
import "leaflet/dist/leaflet.css";
import "./utils/leafletPatch.js";

import { ZiggyVue } from "ziggy-js"; // laravel routes

import NProgress from "nprogress"; // progress indicator

import ToastPlugin from "vue-toast-notification";
import "vue-toast-notification/dist/theme-bootstrap.css";

createInertiaApp({
    progress: {
        // The delay after which the progress bar will appear, in milliseconds...
        delay: 250,
        // The color of the progress bar...
        color: "#00FF00",
        // Whether to include the default NProgress styles...
        includeCSS: true,
        // Whether the NProgress spinner will be shown...
        showSpinner: false,
    },
    resolve: (name) => {
        const pages = import.meta.glob("./Pages/**/*.vue", { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(ToastPlugin)
            .mount(el);
    },
});

router.on("start", () => NProgress.start());

// ✅ Re-init plugins after Inertia navigation
router.on("finish", () => {
    if (window.HSStaticMethods) {
        window.HSStaticMethods.autoInit();
    }
    patchHsOverlayAriaHidden();

    NProgress.done();
});

// --------- AI SUGGESTION for leaflet, BUT ITS CAUSING ERROR
// delete L.Icon.Default.prototype._getIconUrl;
//
// L.Icon.Default.mergeOptions({
//     iconUrl: icon,
//     shadowUrl: iconShadow,
// });
// ---------

/**
 * The following event listener are used for listening to
 * navigation events and reinitialize Preline elements
 */

// This event fires on every page change, including Back/Forward browser buttons
// router.on("navigate", (event) => {
//     window.HSStaticMethods.autoInit();
// });

document.addEventListener("inertia:navigate", (event) => {
    if (typeof window.HSStaticMethods !== "undefined") {
        window.HSStaticMethods.autoInit();
    }
    patchHsOverlayAriaHidden();
});

// // 1. Handle Inertia navigations (Page swaps)
// router.on("finish", () => {
//     window.HSStaticMethods.autoInit();
// });
//
// // Handle ALL types of navigation
// router.on("success", () => {
//     window.HSStaticMethods.autoInit();
// });
//
// // // IMPORTANT: Handle browser back/forward buttons
// router.on("navigate", () => {
//     window.HSStaticMethods.autoInit();
// });
//
// // Handle browser popstate (back/forward)
// window.addEventListener("popstate", () => {
//     window.HSStaticMethods.autoInit();
// });
//
// document.addEventListener("domcontentloaded", () => {
//     window.hsstaticmethods.autoinit();
// });
