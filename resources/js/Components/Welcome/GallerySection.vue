<script setup lang="ts">
import Modal from "../Modal.vue";
import emblaCarouselVue from "embla-carousel-vue";

// Carousel 1
const [emblaRef1, emblaApi1] = emblaCarouselVue();
const scrollPrev1 = () => emblaApi1.value?.scrollPrev();
const scrollNext1 = () => emblaApi1.value?.scrollNext();

// Carousel 2
const [emblaRef2, emblaApi2] = emblaCarouselVue();
const scrollPrev2 = () => emblaApi2.value?.scrollPrev();
const scrollNext2 = () => emblaApi2.value?.scrollNext();

// Carousel 3
const [emblaRef3, emblaApi3] = emblaCarouselVue();
const scrollPrev3 = () => emblaApi3.value?.scrollPrev();
const scrollNext3 = () => emblaApi3.value?.scrollNext();
</script>

<template>
    <!-- Masonry Cards -->

    <div
        class="bg-gray-100 dark:bg-neutral-800 max-w-8xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto"
    >
        <!-- <h1 -->
        <!--     class="mt-2 text-xl text-center mb-6 md:text-2xl lg:text-6xl font-bold text-green-600 sm:mt-3 sm:text-6xl dark:text-green-500" -->
        <!-- > -->
        <!--     Gallery -->
        <!-- </h1> -->

        <!-- Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-6">
            <div
                class="grid grid-cols-12 col-span-12 sm:grid-cols-12 lg:col-span-6 gap-6"
            >
                <!-- Cols 1 / 5 -->
                <div class="col-span-12 lg:col-span-12" data-aos="fade-right">
                    <!-- Card -->
                    <button
                        type="button"
                        class="group relative block w-full text-left border-3 border-transparent rounded-xl overflow-hidden focus:outline-none h-64 md:h-[30rem] lg:h-full transition duration-300 hover:border-green-500"
                        aria-haspopup="dialog"
                        aria-expanded="false"
                        aria-controls="hs-columbarium"
                        data-hs-overlay="#hs-columbarium"
                    >
                        <div
                            class="h-full sm:aspect-w-12 sm:aspect-h-7 sm:aspect-none rounded-xl overflow-hidden"
                        >
                            <img
                                class="w-full h-full object-cover rounded-xl transition-transform duration-500 ease-in-out group-hover:scale-105 group-active:scale-95"
                                src="/public/images/columbarium.png"
                                alt="Masonry Cards Image"
                            />
                        </div>

                        <div class="absolute top-0 start-0 end-0 p-2 sm:p-4">
                            <div
                                class="text-3xl text-left font-semibold bg-transparent text-white rounded-lg p-4 md:text-5xl lg:text-6xl"
                            >
                                Columbarium <br />
                                Type
                            </div>
                        </div>
                    </button>
                    <!-- End Card -->

                    <Teleport to="body">
                        <Modal id="hs-columbarium" size="xl" :no-padding="true">
                            <template v-slot:main>
                                <div class="relative">
                                    <!-- Carousel -->
                                    <div
                                        class="overflow-hidden"
                                        ref="emblaRef1"
                                    >
                                        <div class="flex">
                                            <div class="flex-[0_0_100%]">
                                                <img
                                                    src="/public/images/columbarium.png"
                                                    class="w-full h-[90vh] object-cover"
                                                />
                                            </div>
                                            <div class="flex-[0_0_100%]">
                                                <img
                                                    src="/public/images/columbarium.png"
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
                    <div
                        class="col-span-12 md:col-span-6 h-full"
                        data-aos="fade-up"
                    >
                        <!-- Card -->
                        <a
                            class="group relative block rounded-xl overflow-hidden focus:outline-none h-full"
                            href="#"
                        >
                            <div
                                class="h-full sm:aspect-w-12 sm:aspect-h-7 sm:aspect-none rounded-xl overflow-hidden"
                            >
                                <div
                                    class="text-white bg-gradient-to-br from-green-800/95 via-green-700/90 to-green-500/85 dark:from-green-900/95 dark:via-green-800/90 dark:to-green-600/85 text-base md:text-xl py-6 px-4 rounded-xl w-full h-full"
                                >
                                    Lorem ipsum dolor sit amet, consectetur
                                    adipiscing elit. Morbi pretium rutrum erat
                                    ut molestie. Cras lectus odio, tempor a
                                    rutrum non, posuere eu enim. Aliquam ac
                                    nulla ultrices, scelerisque urna.
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Cols 3 / 5 -->
                    <div
                        class="col-span-12 md:col-span-6 h-full"
                        data-aos="fade-up"
                    >
                        <!-- Card -->
                        <a
                            class="group relative block rounded-xl overflow-hidden focus:outline-none h-full"
                            href="#"
                        >
                            <div
                                class="h-full sm:aspect-w-12 sm:aspect-h-7 sm:aspect-none rounded-xl overflow-hidden"
                            >
                                <div
                                    class="text-white bg-gradient-to-br from-yellow-600/95 via-yellow-500/90 to-yellow-400/85 dark:from-yellow-700/95 dark:via-yellow-600/90 dark:to-yellow-500/85 text-base md:text-xl py-6 px-4 rounded-xl w-full h-full"
                                >
                                    Nam elementum orci ut pulvinar lobortis.
                                    Proin consequat imperdiet nisl, id facilisis
                                    ligula faucibus sit amet. Aliquam finibus
                                    porttitor condimentum. Aenean gravida ut mi
                                    ut ultricies.
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <div
                class="grid grid-cols-1 sm:grid-cols-12 gap-6 col-span-12 lg:col-span-6 md:grid-cols-6"
            >
                <!-- Cols 4 / 5 -->
                <div class="sm:col-span-12 md:col-span-6" data-aos="fade-left">
                    <!-- Card -->
                    <button
                        type="button"
                        class="group relative block w-full text-center border-3 border-transparent rounded-xl overflow-hidden focus:outline-none h-64 md:h-[30rem] lg:h-full transition duration-300 hover:border-green-500"
                        aria-haspopup="dialog"
                        aria-expanded="false"
                        aria-controls="hs-underground"
                        data-hs-overlay="#hs-underground"
                    >
                        <div
                            class="h-full sm:aspect-w-12 sm:aspect-h-7 sm:aspect-none rounded-xl overflow-hidden"
                        >
                            <img
                                class="w-full h-full object-cover rounded-xl transition-transform duration-500 ease-in-out group-hover:scale-105 group-active:scale-95"
                                src="/public/images/underground.png"
                                alt="Masonry Cards Image"
                            />
                        </div>

                        <div
                            class="absolute top-1/2 start-0 end-0 -translate-y-1/2 p-2 sm:p-4"
                        >
                            <div
                                class="text-3xl text-center font-semibold bg-transparent text-white rounded-lg p-4 md:text-5xl lg:text-6xl"
                            >
                                Underground <br />
                                Type
                            </div>
                        </div>
                    </button>
                    <!-- End Card -->

                    <Teleport to="body">
                        <Modal id="hs-underground" size="xl" :no-padding="true">
                            <template v-slot:main>
                                <div class="relative">
                                    <!-- Carousel -->
                                    <div
                                        class="overflow-hidden"
                                        ref="emblaRef2"
                                    >
                                        <div class="flex">
                                            <div class="flex-[0_0_100%]">
                                                <img
                                                    src="/public/images/underground.png"
                                                    class="w-full h-[90vh] object-cover"
                                                />
                                            </div>
                                            <div class="flex-[0_0_100%]">
                                                <img
                                                    src="/public/images/underground.png"
                                                    class="w-full h-[90vh] object-cover"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Prev -->
                                    <button
                                        @click="scrollPrev2"
                                        class="absolute top-1/2 left-3 -translate-y-1/2 z-10 bg-black/60 text-white size-10 rounded-full"
                                    >
                                        ←
                                    </button>
                                    <!-- Next -->
                                    <button
                                        @click="scrollNext2"
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

                <!-- Cols 5 / 5 -->
                <div class="sm:col-span-12 md:col-span-6" data-aos="fade-up">
                    <!-- Card -->
                    <button
                        class="border-3 border-transparent group relative block w-full rounded-xl overflow-hidden focus:outline-none h-64 md:h-[30rem] lg:h-full transition duration-300 hover:border-green-500"
                        aria-haspopup="dialog"
                        aria-expanded="false"
                        aria-controls="hs-apartment"
                        data-hs-overlay="#hs-apartment"
                    >
                        <div
                            class="h-full sm:aspect-w-12 sm:aspect-h-7 sm:aspect-none rounded-xl overflow-hidden"
                        >
                            <img
                                class="group-hover:scale-105 group-focus:scale-105 transition-transform duration-500 ease-in-out rounded-xl w-full h-full object-cover"
                                src="/public/images/apartment.png"
                                alt="Apartment Card Image"
                            />
                        </div>
                        <div class="absolute top-0 start-0 end-0 p-2 sm:p-4">
                            <div
                                class="text-3xl text-right font-semibold bg-transparent text-white rounded-lg p-4 md:text-5xl lg:text-6xl"
                            >
                                Apartment <br />
                                Type
                            </div>
                        </div>
                    </button>
                    <!-- End Card -->

                    <Teleport to="body">
                        <Modal id="hs-apartment" size="xl" :no-padding="true">
                            <template v-slot:main>
                                <div class="relative">
                                    <!-- Carousel -->
                                    <div
                                        class="overflow-hidden"
                                        ref="emblaRef3"
                                    >
                                        <div class="flex">
                                            <div class="flex-[0_0_100%]">
                                                <img
                                                    src="/public/images/apartment.png"
                                                    class="w-full h-[90vh] object-cover"
                                                />
                                            </div>
                                            <div class="flex-[0_0_100%]">
                                                <img
                                                    src="/public/images/apartment.png"
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
