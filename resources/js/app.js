import "../css/app.css";
import "./bootstrap.js";
import "preline";

import { createApp, h } from "vue";
import { createInertiaApp, router } from "@inertiajs/vue3";

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob("./Pages/**/*.vue", { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
});

/**
 * The following event listener are used for listening to
 * navigation events and reinitialize Preline elements
 */

// This event fires on every page change, including Back/Forward browser buttons
router.on("navigate", (event) => {
    window.HSStaticMethods.autoInit();
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
