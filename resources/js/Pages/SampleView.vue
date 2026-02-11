<script setup>
import Navbar from "@/Components/Navbar.vue";
</script>
<template>
    <Navbar />
    <!-- Hero -->
    <div class="px-4 sm:px-6 lg:px-4">
        <div
            class="relative h-150 max-h-250 md:h-[90dvh] flex flex-col bg-[url('/images/hero-background.png')] bg-cover bg-center bg-no-repeat rounded-2xl overflow-hidden"
        >
            <!-- Overlay -->
            <div class="absolute inset-0 bg-black/40 rounded-2xl"></div>

            <!-- Content - Add relative z-10 here -->
            <div
                class="relative z-10 h-full w-full flex flex-col md:flex-col-reverse justify-between pt-10 lg:pt-20 mt-auto md:w-2/3 md:max-w-xl ps-5 pe-5 pb-5 md:ps-10 md:pb-10"
            >
                <h1
                    class="bona-nova-heading text-center md:text-left text-4xl md:text-6xl lg:text-8xl text-white"
                >
                    Panteon De Dasmariñas
                </h1>
                <div class="text-center md:text-left">
                    <p class="text-white text-base md:text-lg leading-relaxed">
                        A historic ground dedicated to honoring lives,
                        preserving cultural heritage, and providing
                        compassionate support to families across generations.
                    </p>
                    <div
                        class="flex mt-5 gap-4 flex-wrap justify-center md:justify-start"
                    >
                        <a
                            href="#_"
                            class="inline-flex items-center justify-center px-5 py-3 font-semibold text-center text-white no-underline align-middle transition-all duration-300 ease-in-out bg-green-500 backdrop-blur-md border border-white/20 rounded-full cursor-pointer select-none hover:bg-green-600 hover:border-white/40 hover:shadow-xl focus:shadow-xs focus:no-underline shadow-lg"
                        >
                            Button Text
                        </a>
                        <a
                            href="#_"
                            class="inline-flex items-center justify-center px-5 py-3 font-semibold text-center text-white no-underline align-middle transition-all duration-300 ease-in-out bg-white/10 backdrop-blur-md border border-white/20 rounded-full cursor-pointer select-none hover:bg-white/20 hover:border-white/40 focus:shadow-xs focus:no-underline shadow-lg"
                        >
                            Button Text
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Hero -->

    <div
        class="container mx-auto flex flex-col items-center px-4 py-5 lg:px-4 lg:py-15"
    >
        <article
            class="py-10 text-xl leading-relaxed lg:text-2xl text-center md:w-[50ch] lg:w-[60ch]"
        >
            <span class="font-bold">Panteon De Dasmariñas Cemetery</span> is a
            long-established memorial ground located in the heart of Dasmariñas,
            Cavite. Serving generations of families, it features developed
            burial areas with planned plots and additional spaces reserved for
            future expansion. The cemetery remains an important place of
            remembrance, honoring the heritage and stories of the community.
        </article>
        <!-- Features -->
        <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
            <!-- Grid -->
            <div
                class="grid grid-cols-2 justify-items-center gap-6 items-around sm:gap-12 lg:grid-cols-3 lg:gap-8"
            >
                <!-- Stats -->
                <div>
                    <h4
                        class="text-lg font-semibold text-gray-800 sm:text-xl dark:text-neutral-200"
                    >
                        Deceased Records
                    </h4>
                    <p
                        class="mt-2 text-4xl font-bold text-green-600 sm:mt-3 sm:text-6xl dark:text-green-500"
                    >
                        20K+
                    </p>
                    <p class="mt-1 text-gray-500 dark:text-neutral-400">
                        in fulfilling orders
                    </p>
                </div>
                <!-- End Stats -->

                <!-- Stats -->
                <div>
                    <h4
                        class="text-lg font-semibold text-gray-800 sm:text-xl dark:text-neutral-200"
                    >
                        Open Hours
                    </h4>
                    <p
                        class="mt-2 text-4xl font-bold text-green-600 sm:mt-3 sm:text-6xl dark:text-green-500"
                    >
                        6-18
                    </p>
                    <p class="mt-1 text-gray-500 dark:text-neutral-400">
                        GMT +8
                    </p>
                </div>
                <!-- End Stats -->

                <!-- Stats -->
                <div>
                    <h4
                        class="text-lg font-semibold text-gray-800 sm:text-xl dark:text-neutral-200"
                    >
                        Happy customer
                    </h4>
                    <p
                        class="mt-2 text-4xl font-bold text-green-600 sm:mt-3 sm:text-6xl dark:text-green-500"
                    >
                        85%
                    </p>
                    <p class="mt-1 text-gray-500 dark:text-neutral-400">
                        this year alone
                    </p>
                </div>
                <!-- End Stats -->
            </div>
            <!-- End Grid -->
        </div>
        <!-- End Features -->
    </div>
</template>
