import "../css/app.css";
import "./bootstrap.js";
import "preline";

import { createApp, h } from "vue";
import { createInertiaApp, router } from "@inertiajs/vue3";

import AOS from "aos";
import "aos/dist/aos.css"; // AOS (scroll animation library)

import L from "leaflet";
import "leaflet/dist/leaflet.css";

import { ZiggyVue } from "ziggy-js";

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob("./Pages/**/*.vue", { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);

        // Initialize AOS
        AOS.init({
            duration: 1000, // global animation duration in ms
            once: true, // whether animation should happen only once - while scrolling down
            offset: 150, // trigger only when element is close to viewport
            anchorPlacement: "top-bottom", // element top hits bottom of viewport
        });
    },
});

// ✅ Re-init plugins after Inertia navigation
router.on("finish", () => {
    if (window.HSStaticMethods) {
        window.HSStaticMethods.autoInit();
    }

    AOS.refresh();
});

// delete L.Icon.Default.prototype._getIconUrl;
//
// L.Icon.Default.mergeOptions({
//     iconUrl: icon,
//     shadowUrl: iconShadow,
// });

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
