<script setup>
import Navbar from "@/Components/Navbar.vue";
</script>
<template>
    <Navbar />
    <!-- Hero -->
    <div class="px-4 sm:px-6 lg:px-4">
        <div
            class="relative h-150 md:h-[90dvh] flex flex-col bg-[url('/images/hero-background.png')] bg-cover bg-center bg-no-repeat rounded-2xl overflow-hidden"
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
                        <button
                            class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold transition"
                        >
                            View Map
                        </button>
                        <button
                            class="border-2 border-white hover:bg-white hover:text-gray-900 text-white px-8 py-3 rounded-lg font-semibold transition"
                        >
                            Contact Us
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Hero -->
</template>
