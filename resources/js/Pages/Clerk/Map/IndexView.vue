<script setup>
import { useMap } from "@/composables/useMap";
import { ref, onMounted, onBeforeMount, onBeforeUnmount } from "vue";

import Dashboard from "@/Layouts/Dashboard.vue";
import DeceasedRecordTable from "@/Pages/Clerk/DeceasedRecords/IndexView.vue";
import Input from "@/Components/Form/Input.vue";
import { Link } from "@inertiajs/vue3";

const { initializeMap, cleanupMap } = useMap();

const mapContainer = ref(null);
const toggleMap = ref(true);

// NOTE: toggle map and table view (out for now since we ended up separating the MAP and TABLE)
// const toggleMapEvent = () => {
//     toggleMap.value = !toggleMap.value;
//     if (toggleMap.value) {
//         cleanupMap();
//         setTimeout(() => initializeMap(mapContainer.value), 0);
//     } else {
//         // Switched to table view - need to reinitialize Preline
//         setTimeout(() => {
//             if (window.HSStaticMethods) {
//                 window.HSStaticMethods.autoInit();
//             }
//         }, 0);
//     }
// };

window.openLotModal = function (feature, layerId) {
    const modalBody = document.querySelector(
        "#hs-scroll-inside-body-modal .p-4",
    );

    modalBody.innerHTML = `
        <strong>Lot: ${feature.properties.lot_id}</strong><br>
        Section: ${feature.properties.section}<br>
        Type: ${feature.properties.lot_type}<br>
        Status: ${feature.properties.status}<br>
        Fullname: ${feature.properties.deceased_record?.full_name ?? "N/A"}
    `;

    HSOverlay.open("#hs-scroll-inside-body-modal");
};

defineOptions({
    layout: Dashboard,
});

onMounted(() => {
    initializeMap(mapContainer.value);
});

onBeforeUnmount(() => {
    cleanupMap();
});
</script>

<template>
    <Teleport to="body">
        <div
            id="hs-scroll-inside-body-modal"
            class="hs-overlay hidden size-full fixed top-0 start-0 z-[2000] overflow-x-hidden overflow-y-auto pointer-events-none"
            role="dialog"
            tabindex="-1"
            aria-labelledby="hs-scroll-inside-body-modal-label"
        >
            <div
                class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 h-[calc(100%-56px)] sm:mx-auto"
            >
                <div
                    class="max-h-full overflow-hidden flex flex-col bg-overlay border border-overlay-line shadow-2xs rounded-xl pointer-events-auto"
                >
                    <div
                        class="flex justify-between items-center py-3 px-4 border-b border-overlay-header"
                    >
                        <h3
                            id="hs-scroll-inside-body-modal-label"
                            class="font-semibold text-foreground"
                        >
                            Modal title
                        </h3>
                        <button
                            type="button"
                            class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full bg-surface border border-surface-line text-surface-foreground hover:bg-surface-hover focus:outline-hidden focus:bg-surface-focus disabled:opacity-50 disabled:pointer-events-none"
                            aria-label="Close"
                            data-hs-overlay="#hs-scroll-inside-body-modal"
                        >
                            <span class="sr-only">Close</span>
                            <svg
                                class="shrink-0 size-4"
                                xmlns="http://www.w3.org/2000/svg"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M18 6 6 18"></path>
                                <path d="m6 6 12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="p-4 overflow-y-auto">
                        <div class="space-y-4">
                            <div>
                                <h3
                                    class="text-lg font-semibold text-foreground"
                                >
                                    Be bold
                                </h3>
                                <p class="mt-1 text-foreground">
                                    Motivate teams to do their best work. Offer
                                    best practices to get users going in the
                                    right direction. Be bold and offer just
                                    enough help to get the work started, and
                                    then get out of the way. Give accurate
                                    information so users can make educated
                                    decisions. Know your user's struggles and
                                    desired outcomes and give just enough
                                    information to let them get where they need
                                    to go.
                                </p>
                            </div>

                            <div>
                                <h3
                                    class="text-lg font-semibold text-foreground"
                                >
                                    Be optimistic
                                </h3>
                                <p class="mt-1 text-foreground">
                                    Focusing on the details gives people
                                    confidence in our products. Weave a
                                    consistent story across our fabric and be
                                    diligent about vocabulary across all
                                    messaging by being brand conscious across
                                    products to create a seamless flow across
                                    all the things. Let people know that they
                                    can jump in and start working expecting to
                                    find a dependable experience across all the
                                    things. Keep teams in the loop about what is
                                    happening by informing them of relevant
                                    features, products and opportunities for
                                    success. Be on the journey with them and
                                    highlight the key points that will help them
                                    the most - right now. Be in the moment by
                                    focusing attention on the important bits
                                    first.
                                </p>
                            </div>

                            <div>
                                <h3
                                    class="text-lg font-semibold text-foreground"
                                >
                                    Be practical, with a wink
                                </h3>
                                <p class="mt-1 text-foreground">
                                    Keep our own story short and give teams just
                                    enough to get moving. Get to the point and
                                    be direct. Be concise - we tell the story of
                                    how we can help, but we do it directly and
                                    with purpose. Be on the lookout for
                                    opportunities and be quick to offer a
                                    helping hand. At the same time realize that
                                    nobody likes a nosy neighbor. Give the user
                                    just enough to know that something awesome
                                    is around the corner and then get out of the
                                    way. Write clear, accurate, and concise text
                                    that makes interfaces more usable and
                                    consistent - and builds trust. We strive to
                                    write text that is understandable by anyone,
                                    anywhere, regardless of their culture or
                                    language so that everyone feels they are
                                    part of the team.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div
                        class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-overlay-footer"
                    >
                        <button
                            type="button"
                            class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg bg-layer border border-layer-line text-layer-foreground shadow-2xs hover:bg-layer-hover focus:outline-hidden focus:bg-layer-focus disabled:opacity-50 disabled:pointer-events-none"
                            data-hs-overlay="#hs-scroll-inside-body-modal"
                        >
                            Close
                        </button>
                        <button
                            type="button"
                            class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg bg-primary border border-primary-line text-primary-foreground hover:bg-primary-hover focus:outline-hidden focus:bg-primary-focus disabled:opacity-50 disabled:pointer-events-none"
                        >
                            Save changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

    <section id="map-wrapper" class="relative w-full" style="height: 98vh">
        <div v-if="toggleMap" key="map" class="h-full w-full">
            <!-- Map container -->
            <div
                ref="mapContainer"
                id="map"
                class="h-full w-full focus:outline-none"
            ></div>
        </div>

        <div class="absolute top-2 inset-x-0 flex justify-between z-888 px-4">
            <Input placeholder="Full name" type="search" />

            <div class="flex gap-x-2">
                <!--- ISSUE: Change this into offcanvas or modal button --->
                <!--- NOTE: Filter button  --->
                <div
                    class="flex items-center justify-center py-2 px-3 bg-white dark:bg-neutral-900 border border-gray-300 dark:border-neutral-700 rounded-lg shadow-md transition"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="lucide lucide-funnel-plus-icon lucide-funnel-plus text-green-500 dark:text-green-600"
                    >
                        <path
                            d="M13.354 3H3a1 1 0 0 0-.742 1.67l7.225 7.989A2 2 0 0 1 10 14v6a1 1 0 0 0 .553.895l2 1A1 1 0 0 0 14 21v-7a2 2 0 0 1 .517-1.341l1.218-1.348"
                        />
                        <path d="M16 6h6" />
                        <path d="M19 3v6" />
                    </svg>
                </div>

                <!--- NOTE: Toggle table view button --->

                <Link
                    :href="route('clerk.deceased_records.index')"
                    class="flex items-center justify-center py-2 px-3 bg-white dark:bg-neutral-900 border border-gray-300 dark:border-neutral-700 rounded-lg shadow-md transition"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="lucide lucide-arrow-right-left-icon lucide-arrow-right-left text-green-500 dark:text-green-600"
                    >
                        <path d="m16 3 4 4-4 4" />
                        <path d="M20 7H4" />
                        <path d="m8 21-4-4 4-4" />
                        <path d="M4 17h16" />
                    </svg>
                </Link>
            </div>
        </div>

        <div class="absolute bottom-5 inset-x-0 flex justify-end z-999 px-4">
            <div class="flex gap-x-2">
                <!--- ISSUE: Change this a button that on and off polygon, and change the element to be button --->
                <!--- NOTE: Toggle polygon button --->
                <div
                    class="flex items-center justify-center py-2 px-3 bg-white dark:bg-neutral-900 border border-gray-300 dark:border-neutral-700 rounded-lg shadow-md transition"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="lucide lucide-eye-icon lucide-eye text-green-500 dark:text-green-600"
                    >
                        <path
                            d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"
                        />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                </div>
            </div>
        </div>
    </section>
</template>
