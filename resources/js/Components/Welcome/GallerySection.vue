<script setup lang="ts">
import { reactive } from "vue";
import Modal from "../Modal.vue";
import emblaCarouselVue from "embla-carousel-vue";

// Carousel 1 - Lot Type (underground + apartment)
const [emblaRef1, emblaApi1] = emblaCarouselVue();
const scrollPrev1 = () => emblaApi1.value?.scrollPrev();
const scrollNext1 = () => emblaApi1.value?.scrollNext();

// Carousel 2 - Facilities
const [emblaRef2, emblaApi2] = emblaCarouselVue();
const scrollPrev2 = () => emblaApi2.value?.scrollPrev();
const scrollNext2 = () => emblaApi2.value?.scrollNext();

// The facility photos and the full-size org chart are only attached once
// their modal has been opened, so they are not requested on first paint.
// Preline still drives show/hide through data-hs-overlay.
//
// The carousel markup itself must stay mounted: embla-carousel-vue
// initialises in onMounted and bails out if its element is absent, so
// wrapping these in v-if would break the prev/next arrows. Only the
// src/srcset attributes are withheld instead.
const openedModalIds = reactive(new Set<string>());

const showModal = (id: string) => openedModalIds.add(id);
const isModalOpened = (id: string) => openedModalIds.has(id);

const lotTypePreviewImage = "/" + "images/columbarium-thumb.webp";

const lotTypeModalImages = [
    "/" + "images/columbarium-thumb.webp",
    "/" + "images/underground.webp",
    "/" + "images/apartment.webp",
];

// The hero card uses the clean 1672x941 chart at its native 16:9 ratio so the
// whole board stays readable - legibility comes from the CSS scrim below, not
// from a pre-dimmed or zoomed copy. The modal reuses the same file, so the
// browser serves it from cache once the hero has loaded.
const orgChartImage = "/" + "images/org-chart.webp";

const facilitiesPreviewImage = "/" + "images/facilities-665.webp";

const facilitiesImages = [
    "/" + "images/facilities/IMG_20260327_163528_636-1920.webp",
    "/" + "images/facilities/IMG_20260327_165238_402-1920.webp",
    "/" + "images/facilities/IMG_20260327_165414_515-1920.webp",
    "/" + "images/facilities/IMG_20260327_165559_020-1920.webp",
    "/" + "images/facilities/IMG_20260327_165613_342-1920.webp",
    "/" + "images/facilities/IMG_20260327_170105_723-1920.webp",
];

// Derive the "-1280" sibling from the "-1920" path so narrow viewports do
// not pull a 1920px photo into a ~390px wide slot.
const srcsetFor = (src: string) =>
    `${src.replace("-1920.webp", "-1280.webp")} 1280w, ${src} 1920w`;

// Headcounts below were counted off the org chart board itself, not
// estimated - keep them in sync when the board is reprinted.
const leadership = [
    { name: "Jennifer Austria Barzaga", role: "City Mayor of Dasmariñas" },
    { name: "Liezl Marizje Camangcanan", role: "Office-in-Charge" },
];

// The board prints two separate "Utility" columns - one under La Funeraria
// De Dasmariñas (8) and one under Panteon De Dasmariñas (5) - merged into a
// single 13 here so the strip does not show the same label twice.
const divisions = [
    { name: "Driver", count: 16 },
    { name: "Helper", count: 16 },
    { name: "Cemetery Caretaker", count: 15 },
    { name: "Crematory Operator", count: 10 },
    { name: "Utility", count: 13 },
    { name: "Watchman", count: 2 },
];

// Office clerks (4) + messenger (1) + Panteon office clerks (2).
const officeStaffCount = 7;

const fieldStaffCount = divisions.reduce(
    (total, division) => total + division.count,
    0,
);

const totalStaffCount = fieldStaffCount + officeStaffCount + leadership.length;
</script>

<template>
    <!-- Gallery -->

    <div
        id="gallery"
        class="bg-gray-100 dark:bg-neutral-800 max-w-8xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto"
    >
        <!-- Section header -->
        <div class="max-w-3xl mx-auto text-center">
            <p
                class="text-xl font-semibold uppercase tracking-wider text-green-600 dark:text-green-500"
            >
                Our People
            </p>
            <h2
                class="mt-2 text-3xl font-semibold text-gray-800 sm:text-4xl dark:text-neutral-100"
            >
                The Team Behind Panteon De Dasmariñas
            </h2>
            <p
                class="mt-3 text-base text-gray-600 sm:text-lg dark:text-neutral-300"
            >
                From the City Mayor's office down to the caretakers and
                crematory operators on the grounds, here is who looks after
                every visit and every resting place.
            </p>
        </div>
        <!-- End Section header -->

        <!-- Org Chart Hero -->
        <article
            class="group mt-10 overflow-hidden rounded-2xl bg-white dark:bg-neutral-900 ring-1 ring-black/5 dark:ring-white/10 shadow-sm transition-shadow duration-300 ease-out hover:shadow-xl"
        >
            <div
                class="relative w-full overflow-hidden aspect-[4/3] sm:aspect-[16/9]"
            >
                <img
                    :src="orgChartImage"
                    :srcset="'/images/org-chart-886.webp 886w, /images/org-chart.webp 1672w'"
                    sizes="(min-width: 1024px) 886px, 100vw"
                    width="1672"
                    height="941"
                    alt="Organizational chart of La Funeraria De Dasmariñas and Panteon De Dasmariñas"
                    loading="lazy"
                    decoding="async"
                    class="absolute inset-0 size-full object-cover object-center transition-transform duration-700 ease-out group-hover:scale-[1.03]"
                />
                <!-- Scrim keeps the headline readable over the board -->
                <div
                    class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/60 to-black/20"
                    aria-hidden="true"
                />

                <div class="absolute inset-x-0 bottom-0 p-5 sm:p-8 lg:p-10">
                    <div class="max-w-2xl">
                        <span
                            class="inline-flex items-center rounded-full bg-black/45 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-yellow-200 ring-1 ring-inset ring-yellow-200/40 backdrop-blur-sm"
                        >
                            Organizational Chart
                        </span>
                        <h3
                            class="mt-3 text-2xl font-semibold text-white sm:text-3xl lg:text-4xl"
                        >
                            {{ totalStaffCount }} people, one team
                        </h3>
                        <p
                            class="mt-2 hidden max-w-xl text-sm text-white/85 sm:block sm:text-base"
                        >
                            Led by {{ leadership[0].name }} ({{
                                leadership[0].role
                            }}) with {{ leadership[1].name }} as
                            {{ leadership[1].role }}, supported by
                            {{ officeStaffCount }} office staff and
                            {{ fieldStaffCount }} field personnel.
                        </p>
                    </div>

                    <span
                        class="mt-5 inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-gray-900 shadow-lg transition-all duration-300 ease-out group-hover:bg-green-500 group-hover:text-white"
                    >
                        View full org chart
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="16"
                            height="16"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="size-4 transition-transform duration-300 ease-out group-hover:translate-x-1"
                            aria-hidden="true"
                        >
                            <path d="M5 12h14" />
                            <path d="m12 5 7 7-7 7" />
                        </svg>
                    </span>
                </div>

                <!-- Stretched trigger: the whole board stays clickable while
                     the heading and copy above remain real document flow. -->
                <button
                    type="button"
                    class="absolute inset-0 z-10 size-full rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-green-500"
                    aria-label="View the full organizational chart of La Funeraria De Dasmariñas and Panteon De Dasmariñas"
                    aria-haspopup="dialog"
                    :aria-expanded="isModalOpened('hs-org-chart')"
                    aria-controls="hs-org-chart"
                    data-hs-overlay="#hs-org-chart"
                    @click="showModal('hs-org-chart')"
                ></button>
            </div>
            <!-- End Org Chart Hero -->

            <!-- Division strip -->
            <div
                class="flex flex-col gap-4 border-t border-black/5 px-5 py-5 sm:px-8 dark:border-white/10 lg:flex-row lg:items-center lg:justify-between"
            >
                <div>
                    <p
                        class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-neutral-400"
                    >
                        Field Divisions
                    </p>
                    <p class="mt-1 text-sm text-gray-600 dark:text-neutral-300">
                        {{ fieldStaffCount }} personnel stationed across the
                        grounds
                    </p>
                </div>
                <ul class="flex flex-wrap gap-2">
                    <li
                        v-for="division in divisions"
                        :key="division.name"
                        class="inline-flex items-center gap-2 rounded-full bg-green-50 px-3 py-1.5 text-sm ring-1 ring-inset ring-green-600/20 dark:bg-green-500/10 dark:ring-green-400/20"
                    >
                        <span class="text-gray-700 dark:text-neutral-200">{{
                            division.name
                        }}</span>
                        <span
                            class="font-semibold tabular-nums text-green-700 dark:text-green-400"
                        >
                            {{ division.count }}
                        </span>
                    </li>
                </ul>
            </div>
            <!-- End Division strip -->

            <Teleport to="body">
                <Modal id="hs-org-chart" size="screen" :no-padding="true">
                    <template v-slot:main>
                        <div
                            v-if="isModalOpened('hs-org-chart')"
                            class="relative w-full h-full min-h-[85vh] flex items-center justify-center bg-black p-6"
                        >
                            <img
                                :src="orgChartImage"
                                class="w-auto h-auto max-w-[90vw] max-h-[85vh] object-contain bg-white rounded-lg shadow-2xl"
                                alt="Org Chart"
                            />
                        </div>
                    </template>
                </Modal>
            </Teleport>
        </article>
        <!-- End Org Chart -->

        <!-- Supporting Cards -->
        <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <!-- Lot Types -->
            <article
                class="group relative flex h-full flex-col overflow-hidden rounded-2xl bg-white text-left ring-1 ring-black/5 shadow-sm transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-xl dark:bg-neutral-900 dark:ring-white/10"
            >
                <div class="relative w-full overflow-hidden aspect-[4/3]">
                    <img
                        :src="lotTypePreviewImage"
                        width="628"
                        height="551"
                        alt="Columbarium structure at Panteon De Dasmariñas"
                        loading="lazy"
                        decoding="async"
                        class="absolute inset-0 size-full object-cover transition-transform duration-500 ease-out group-hover:scale-105"
                    />
                    <div
                        class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-transparent"
                        aria-hidden="true"
                    />
                    <span
                        class="absolute bottom-3 start-4 rounded-full bg-white/15 px-3 py-1 text-xs font-medium text-white backdrop-blur-md ring-1 ring-inset ring-white/25"
                    >
                        {{ lotTypeModalImages.length }} lot types
                    </span>
                </div>

                <div class="flex grow flex-col p-5 sm:p-6">
                    <h3
                        class="text-xl font-semibold text-gray-800 dark:text-neutral-100"
                    >
                        Different Lot Types
                    </h3>
                    <p
                        class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-neutral-300"
                    >
                        Underground plots and apartment-style resting spaces,
                        each maintained with care.
                    </p>
                    <span
                        class="mt-auto inline-flex items-center gap-1.5 pt-4 text-sm font-semibold text-green-600 dark:text-green-400"
                    >
                        View photos
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="16"
                            height="16"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="size-4 transition-transform duration-300 ease-out group-hover:translate-x-1"
                            aria-hidden="true"
                        >
                            <path d="M5 12h14" />
                            <path d="m12 5 7 7-7 7" />
                        </svg>
                    </span>
                </div>

                <button
                    type="button"
                    class="absolute inset-0 z-10 size-full rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-green-500"
                    aria-label="View photos of the different lot types"
                    aria-haspopup="dialog"
                    :aria-expanded="isModalOpened('hs-lot-type')"
                    aria-controls="hs-lot-type"
                    data-hs-overlay="#hs-lot-type"
                    @click="showModal('hs-lot-type')"
                ></button>
            </article>
            <!-- End Lot Types -->

            <Teleport to="body">
                <Modal id="hs-lot-type" size="screen" :no-padding="true">
                    <template v-slot:main>
                        <div
                            class="relative flex items-center justify-center bg-black"
                        >
                            <!-- Carousel -->
                            <div class="overflow-hidden" ref="emblaRef1">
                                <div class="flex">
                                    <div
                                        v-for="(img, idx) in lotTypeModalImages"
                                        :key="idx"
                                        class="flex-[0_0_100%]"
                                    >
                                        <img
                                            :src="
                                                isModalOpened('hs-lot-type')
                                                    ? img
                                                    : undefined
                                            "
                                            :alt="
                                                isModalOpened('hs-lot-type')
                                                    ? `Lot Type ${idx + 1}`
                                                    : ''
                                            "
                                            width="628"
                                            height="490"
                                            class="w-full h-[90vh] object-cover"
                                        />
                                    </div>
                                </div>
                            </div>
                            <!-- Prev -->
                            <button
                                type="button"
                                aria-label="Previous lot type"
                                @click="scrollPrev1"
                                class="absolute top-1/2 left-3 -translate-y-1/2 z-10 inline-flex items-center justify-center bg-black/60 text-white size-10 rounded-full ring-1 ring-white/20 transition hover:bg-black/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="20"
                                    height="20"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path d="m15 18-6-6 6-6" />
                                </svg>
                            </button>
                            <!-- Next -->
                            <button
                                type="button"
                                aria-label="Next lot type"
                                @click="scrollNext1"
                                class="absolute top-1/2 right-3 -translate-y-1/2 z-10 inline-flex items-center justify-center bg-black/60 text-white size-10 rounded-full ring-1 ring-white/20 transition hover:bg-black/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="20"
                                    height="20"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path d="m9 18 6-6-6-6" />
                                </svg>
                            </button>
                        </div>
                    </template>
                </Modal>
            </Teleport>

            <!-- Facilities -->
            <article
                class="group relative flex h-full flex-col overflow-hidden rounded-2xl bg-white text-left ring-1 ring-black/5 shadow-sm transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-xl dark:bg-neutral-900 dark:ring-white/10"
            >
                <div class="relative w-full overflow-hidden aspect-[4/3]">
                    <img
                        :src="facilitiesPreviewImage"
                        :srcset="'/images/facilities-665.webp 665w, /images/facilities.webp 1120w'"
                        sizes="(min-width: 640px) 665px, 100vw"
                        width="1120"
                        height="840"
                        alt="Cemetery office and facilities"
                        loading="lazy"
                        decoding="async"
                        class="absolute inset-0 size-full object-cover transition-transform duration-500 ease-out group-hover:scale-105"
                    />
                    <div
                        class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-transparent"
                        aria-hidden="true"
                    />
                    <span
                        class="absolute bottom-3 start-4 rounded-full bg-white/15 px-3 py-1 text-xs font-medium text-white backdrop-blur-md ring-1 ring-inset ring-white/25"
                    >
                        {{ facilitiesImages.length }} photos
                    </span>
                </div>

                <div class="flex grow flex-col p-5 sm:p-6">
                    <h3
                        class="text-xl font-semibold text-gray-800 dark:text-neutral-100"
                    >
                        Facilities &amp; Equipments
                    </h3>
                    <p
                        class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-neutral-300"
                    >
                        The cemetery office and surrounding structures, built
                        for comfort, accessibility, and quiet.
                    </p>
                    <span
                        class="mt-auto inline-flex items-center gap-1.5 pt-4 text-sm font-semibold text-green-600 dark:text-green-400"
                    >
                        Browse facilities
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="16"
                            height="16"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="size-4 transition-transform duration-300 ease-out group-hover:translate-x-1"
                            aria-hidden="true"
                        >
                            <path d="M5 12h14" />
                            <path d="m12 5 7 7-7 7" />
                        </svg>
                    </span>
                </div>

                <button
                    type="button"
                    class="absolute inset-0 z-10 size-full rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-green-500"
                    aria-label="Browse photos of the cemetery facilities and equipment"
                    aria-haspopup="dialog"
                    :aria-expanded="isModalOpened('hs-facilities')"
                    aria-controls="hs-facilities"
                    data-hs-overlay="#hs-facilities"
                    @click="showModal('hs-facilities')"
                ></button>
            </article>
            <!-- End Facilities -->

            <Teleport to="body">
                <Modal id="hs-facilities" size="screen" :no-padding="true">
                    <template v-slot:main>
                        <div class="relative">
                            <!-- Carousel -->
                            <div class="overflow-hidden" ref="emblaRef2">
                                <div class="flex">
                                    <div
                                        v-for="(img, idx) in facilitiesImages"
                                        :key="idx"
                                        class="flex-[0_0_100%]"
                                    >
                                        <img
                                            :src="
                                                isModalOpened('hs-facilities')
                                                    ? img
                                                    : undefined
                                            "
                                            :srcset="
                                                isModalOpened('hs-facilities')
                                                    ? srcsetFor(img)
                                                    : undefined
                                            "
                                            sizes="(min-width: 896px) 896px, 100vw"
                                            :alt="
                                                isModalOpened('hs-facilities')
                                                    ? `Facilities ${idx + 1}`
                                                    : ''
                                            "
                                            class="w-full h-[90vh] object-cover"
                                            decoding="async"
                                        />
                                    </div>
                                </div>
                            </div>
                            <!-- Prev -->
                            <button
                                type="button"
                                aria-label="Previous facility photo"
                                @click="scrollPrev2"
                                class="absolute top-1/2 left-3 -translate-y-1/2 z-10 inline-flex items-center justify-center bg-black/60 text-white size-10 rounded-full ring-1 ring-white/20 transition hover:bg-black/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="20"
                                    height="20"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path d="m15 18-6-6 6-6" />
                                </svg>
                            </button>
                            <!-- Next -->
                            <button
                                type="button"
                                aria-label="Next facility photo"
                                @click="scrollNext2"
                                class="absolute top-1/2 right-3 -translate-y-1/2 z-10 inline-flex items-center justify-center bg-black/60 text-white size-10 rounded-full ring-1 ring-white/20 transition hover:bg-black/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="20"
                                    height="20"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path d="m9 18 6-6-6-6" />
                                </svg>
                            </button>
                        </div>
                    </template>
                </Modal>
            </Teleport>

            <!-- About the Grounds -->
            <article
                class="flex h-full flex-col rounded-2xl bg-gradient-to-br from-green-800 to-green-600 p-6 text-white shadow-sm ring-1 ring-black/5 sm:p-7 dark:ring-white/10"
            >
                <div class="flex items-center gap-3">
                    <span
                        class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-inset ring-white/25"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <path d="M12 22V12" />
                            <path
                                d="M12 12c0-3.314 2.239-6 5-6 0 3.314-2.239 6-5 6Z"
                            />
                            <path
                                d="M12 12c0-2.761-1.791-5-4-5 0 2.761 1.791 5 4 5Z"
                            />
                            <path d="M4 22h16" />
                        </svg>
                    </span>
                    <h3 class="text-xl font-semibold">About the Grounds</h3>
                </div>

                <p
                    class="mt-5 text-sm leading-relaxed text-white/90 sm:text-base"
                >
                    The cemetery office and surrounding facilities are designed
                    to provide both comfort and functionality. Modern structures
                    blend with serene landscapes, creating spaces that are
                    peaceful, accessible, and welcoming for all visitors.
                </p>
                <p
                    class="mt-4 text-sm leading-relaxed text-white/90 sm:text-base"
                >
                    Various burial options are available to meet the needs of
                    families. These include underground plots, columbarium
                    niches, and apartment-style resting spaces. Each type is
                    maintained with care.
                </p>

                <dl
                    class="mt-auto grid grid-cols-2 gap-4 border-t border-white/20 pt-5"
                >
                    <div>
                        <dt
                            class="text-xs uppercase tracking-wider text-white/75"
                        >
                            Lot Types
                        </dt>
                        <dd class="mt-1 text-2xl font-bold">3</dd>
                    </div>
                    <div>
                        <dt
                            class="text-xs uppercase tracking-wider text-white/75"
                        >
                            Open Daily
                        </dt>
                        <dd class="mt-1 text-2xl font-bold">6AM – 6PM</dd>
                    </div>
                </dl>
            </article>
            <!-- End About the Grounds -->
        </div>
        <!-- End Supporting Cards -->
    </div>
    <!-- End Gallery -->
</template>

<style scoped>
/* Gallery media modals: black background */
:deep(#hs-org-chart > div > div),
:deep(#hs-lot-type > div > div),
:deep(#hs-facilities > div > div) {
    background-color: rgb(0 0 0) !important;
    border-color: rgb(255 255 255 / 0.1) !important;
}
</style>
