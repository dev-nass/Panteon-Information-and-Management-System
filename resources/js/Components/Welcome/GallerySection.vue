<script setup lang="ts">
import Modal from "../Modal.vue";
import emblaCarouselVue from "embla-carousel-vue";

// Carousel 1 - Lot Type (underground + apartment)
const [emblaRef1, emblaApi1] = emblaCarouselVue();
const scrollPrev1 = () => emblaApi1.value?.scrollPrev();
const scrollNext1 = () => emblaApi1.value?.scrollNext();

// Carousel 2 - Org Chart
const [emblaRef2, emblaApi2] = emblaCarouselVue();
const scrollPrev2 = () => emblaApi2.value?.scrollPrev();
const scrollNext2 = () => emblaApi2.value?.scrollNext();

// Carousel 3 - Facilities
const [emblaRef3, emblaApi3] = emblaCarouselVue();
const scrollPrev3 = () => emblaApi3.value?.scrollPrev();
const scrollNext3 = () => emblaApi3.value?.scrollNext();

const lotTypeModalImages = [
    "/" + "images/underground.png",
    "/" + "images/apartment.png",
];

const orgChartPreviewImage = "/" + "images/org-chart-darker.png";
const orgChartModalImage = "/" + "images/org-chart.png";

const facilitiesPreviewImage = "/" + "images/facilities.png";

const facilitiesImages = [
    "/" + "images/facilities/IMG_20260327_163528_636.jpg",
    "/" + "images/facilities/IMG_20260327_165238_402.jpg",
    "/" + "images/facilities/IMG_20260327_165414_515.jpg",
    "/" + "images/facilities/IMG_20260327_165559_020.jpg",
    "/" + "images/facilities/IMG_20260327_165613_342.jpg",
    "/" + "images/facilities/IMG_20260327_170105_723.jpg",
];
</script>

<template>
    <!-- Masonry Cards -->

    <div
        id="gallery"
        class="bg-gray-100 dark:bg-neutral-800 max-w-8xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto"
    >
        <!-- Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-6">
            <div
                class="grid grid-cols-12 col-span-12 sm:grid-cols-12 lg:col-span-6 gap-6"
            >
                <!-- Cols 1 / 5 - Lot Type (preview retained as columbarium, modal now underground+apartment) -->
                <div class="col-span-12 lg:col-span-12">
                    <!-- Card -->
                    <button
                        type="button"
                        class="group relative block w-full text-left border-3 border-transparent rounded-xl overflow-hidden focus:outline-none h-64 md:h-[30rem] lg:h-full transition duration-300 hover:border-green-500"
                        aria-haspopup="dialog"
                        aria-expanded="false"
                        aria-controls="hs-lot-type"
                        data-hs-overlay="#hs-lot-type"
                    >
                        <div
                            class="h-full sm:aspect-w-12 sm:aspect-h-7 sm:aspect-none rounded-xl overflow-hidden"
                        >
                            <img
                                class="w-full h-full object-cover rounded-xl transition-transform duration-500 ease-in-out group-hover:scale-105 group-active:scale-95"
                                :src="'/' + 'images/columbarium.png'"
                                alt="Masonry Cards Image"
                            />
                        </div>

                        <div class="absolute top-0 start-0 end-0 p-2 sm:p-4">
                            <div
                                class="text-3xl text-left font-semibold bg-transparent text-white rounded-lg p-4 md:text-5xl lg:text-6xl"
                            >
                                Different <br />
                                Lot Types
                            </div>
                        </div>
                    </button>
                    <!-- End Card -->

                    <Teleport to="body">
                        <Modal id="hs-lot-type" size="xl" :no-padding="true">
                            <template v-slot:main>
                                <div class="relative">
                                    <!-- Carousel -->
                                    <div
                                        class="overflow-hidden"
                                        ref="emblaRef1"
                                    >
                                        <div class="flex">
                                            <div
                                                v-for="(
                                                    img, idx
                                                ) in lotTypeModalImages"
                                                :key="idx"
                                                class="flex-[0_0_100%]"
                                            >
                                                <img
                                                    :src="img"
                                                    :alt="`Lot Type ${idx + 1}`"
                                                    class="w-full h-[90vh] object-cover"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Prev -->
                                    <button
                                        @click="scrollPrev1"
                                        class="absolute top-1/2 left-3 -translate-y-1/2 z-10 bg-black/60 text-white size-10 rounded-full"
                                    >
                                        ←
                                    </button>
                                    <!-- Next -->
                                    <button
                                        @click="scrollNext1"
                                        class="absolute top-1/2 right-3 -translate-y-1/2 z-10 bg-black/60 text-white size-10 rounded-full"
                                    >
                                        →
                                    </button>
                                </div>
                            </template>
                        </Modal>
                    </Teleport>
                </div>
                <!-- End Col -->

                <div class="grid grid-cols-12 gap-6 col-span-12">
                    <!-- Cols 2 / 5 -->
                    <div class="col-span-12 md:col-span-6 h-full">
                        <!-- Card -->
                        <article
                            class="group relative block rounded-xl overflow-hidden focus:outline-none h-full"
                        >
                            <div
                                class="h-full sm:aspect-w-12 sm:aspect-h-7 sm:aspect-none rounded-xl overflow-hidden"
                            >
                                <div
                                    class="text-white bg-gradient-to-br from-green-800/95 via-green-700/90 to-green-500/85 dark:from-green-900/95 dark:via-green-800/90 dark:to-green-600/85 text-base md:text-xl py-6 px-4 rounded-xl w-full h-full"
                                >
                                    The cemetery office and surrounding
                                    facilities are designed to provide both
                                    comfort and functionality. Modern structures
                                    blend with serene landscapes, creating
                                    spaces that are peaceful, accessible, and
                                    welcoming for all visitors.
                                </div>
                            </div>
                        </article>
                    </div>

                    <!-- Cols 3 / 5 -->
                    <div class="col-span-12 md:col-span-6 h-full">
                        <!-- Card -->
                        <article
                            class="group relative block rounded-xl overflow-hidden focus:outline-none h-full"
                        >
                            <div
                                class="h-full sm:aspect-w-12 sm:aspect-h-7 sm:aspect-none rounded-xl overflow-hidden"
                            >
                                <div
                                    class="text-white bg-gradient-to-br from-yellow-600/95 via-yellow-500/90 to-yellow-400/85 dark:from-yellow-700/95 dark:via-yellow-600/90 dark:to-yellow-500/85 text-base md:text-xl py-6 px-4 rounded-xl w-full h-full"
                                >
                                    Various burial options are available to meet
                                    the needs of families. These include
                                    underground plots, columbarium niches, and
                                    apartment-style resting spaces. Each type is
                                    maintained with care.
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </div>

            <div
                class="grid grid-cols-1 sm:grid-cols-12 gap-6 col-span-12 lg:col-span-6 md:grid-cols-6"
            >
                <!-- Cols 4 / 5 - Org Chart -->
                <div class="sm:col-span-12 md:col-span-6">
                    <!-- Card -->
                    <button
                        type="button"
                        class="group relative block w-full text-center border-3 border-transparent rounded-xl overflow-hidden focus:outline-none h-64 md:h-[30rem] lg:h-full transition duration-300 hover:border-green-500"
                        aria-haspopup="dialog"
                        aria-expanded="false"
                        aria-controls="hs-org-chart"
                        data-hs-overlay="#hs-org-chart"
                    >
                        <div
                            class="h-full sm:aspect-w-12 sm:aspect-h-7 sm:aspect-none rounded-xl overflow-hidden"
                        >
                            <img
                                class="w-full h-full object-cover object-center scale-[1.35] rounded-xl transition-transform duration-500 ease-in-out group-hover:scale-[1.42] group-active:scale-95"
                                :src="orgChartPreviewImage"
                                alt="Masonry Cards Image"
                            />
                        </div>

                        <div
                            class="absolute top-1/2 start-0 end-0 -translate-y-1/2 p-2 sm:p-4"
                        >
                            <div
                                class="text-3xl text-center font-semibold bg-transparent text-white rounded-lg p-4 md:text-5xl lg:text-6xl"
                            >
                                Org <br />
                                Chart
                            </div>
                        </div>
                    </button>
                    <!-- End Card -->

                    <Teleport to="body">
                        <Modal id="hs-org-chart" size="screen" :no-padding="true">
                            <template v-slot:main>
                                <div
                                    class="relative w-full h-full min-h-[85vh] flex items-center justify-center bg-black p-6"
                                >
                                    <img
                                        :src="orgChartModalImage"
                                        class="w-auto h-auto max-w-[90vw] max-h-[85vh] object-contain bg-white rounded-lg shadow-2xl"
                                        alt="Org Chart"
                                    />
                                </div>
                            </template>
                        </Modal>
                    </Teleport>
                </div>
                <!-- End Col -->

                <!-- Cols 5 / 5 - Facilities -->
                <div class="sm:col-span-12 md:col-span-6">
                    <!-- Card -->
                    <button
                        class="border-3 border-transparent group relative block w-full rounded-xl overflow-hidden focus:outline-none h-64 md:h-[30rem] lg:h-full transition duration-300 hover:border-green-500"
                        aria-haspopup="dialog"
                        aria-expanded="false"
                        aria-controls="hs-facilities"
                        data-hs-overlay="#hs-facilities"
                    >
                        <div
                            class="h-full sm:aspect-w-12 sm:aspect-h-7 sm:aspect-none rounded-xl overflow-hidden"
                        >
                            <img
                                class="group-hover:scale-105 group-focus:scale-105 transition-transform duration-500 ease-in-out rounded-xl w-full h-full object-cover"
                                :src="facilitiesPreviewImage"
                                alt="Facilities Preview Image"
                            />
                        </div>
                        <div class="absolute top-0 start-0 end-0 p-2 sm:p-4">
                            <div
                                class="text-3xl text-right font-semibold bg-transparent text-white rounded-lg p-4 md:text-5xl lg:text-6xl"
                            >
                                Facilities <br />
                                & Equipments
                            </div>
                        </div>
                    </button>
                    <!-- End Card -->

                    <Teleport to="body">
                        <Modal id="hs-facilities" size="xl" :no-padding="true">
                            <template v-slot:main>
                                <div class="relative">
                                    <!-- Carousel -->
                                    <div
                                        class="overflow-hidden"
                                        ref="emblaRef3"
                                    >
                                        <div class="flex">
                                            <div
                                                v-for="(
                                                    img, idx
                                                ) in facilitiesImages"
                                                :key="idx"
                                                class="flex-[0_0_100%]"
                                            >
                                                <img
                                                    :src="img"
                                                    :alt="`Facilities ${idx + 1}`"
                                                    class="w-full h-[90vh] object-cover"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Prev -->
                                    <button
                                        @click="scrollPrev3"
                                        class="absolute top-1/2 left-3 -translate-y-1/2 z-10 bg-black/60 text-white size-10 rounded-full"
                                    >
                                        ←
                                    </button>
                                    <!-- Next -->
                                    <button
                                        @click="scrollNext3"
                                        class="absolute top-1/2 right-3 -translate-y-1/2 z-10 bg-black/60 text-white size-10 rounded-full"
                                    >
                                        →
                                    </button>
                                </div>
                            </template>
                        </Modal>
                    </Teleport>
                </div>
                <!-- End Col -->
            </div>
        </div>
        <!-- End Grid -->
    </div>
    <!-- End Masonry Cards -->
</template>

<style scoped>
/* Org Chart modal: override default white translucent modal bg to black */
:deep(#hs-org-chart > div > div) {
    background-color: rgb(0 0 0) !important;
    border-color: rgb(255 255 255 / 0.1) !important;
}
</style>
