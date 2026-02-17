<script setup>
import Navbar from "@/Components/Navbar.vue";
</script>
<template>
    <div class="relative min-h-screen">
        <!-- Navbar - Now truly sticky -->
        <Navbar />

        <!-- Hero Section -->
        <div class="relative h-screen -mt-20">
            <div
                class="absolute inset-0 bg-cover bg-center"
                style="
                    background-image: url(&quot;/images/hero-background.png&quot;);
                "
            >
                <div class="absolute inset-0 bg-black/40"></div>
            </div>

            <!-- Hero Content -->
            <div
                class="relative z-10 flex items-center justify-center h-full border border-red-500"
            >
                <div
                    class="border text-center text-white px-6 mt-20 flex flex-col justify-between gap-y-50 md:flex-col-reverse"
                >
                    <h1
                        class="text-5xl md:text-8xl mb-6 font-extralight lg:text-[7rem] bona-nova-heading"
                    >
                        Panteon De Dasmariñas
                    </h1>
                    <article class="md:flex md:flex-col-reverse md:w-md">
                        <div class="flex gap-4 justify-center md:justify-start">
                            <button
                                class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold transition-colors"
                            >
                                View Map
                            </button>
                            <button
                                class="border-2 border-white hover:bg-white hover:text-gray-900 text-white px-8 py-3 rounded-lg font-semibold transition-colors"
                            >
                                Contact Us
                            </button>
                        </div>
                        <p
                            class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto md:text-left md:mx-0 leading-relaxed lg:w-[40ch] lg:text-xl"
                        >
                            A historic ground dedicated to honoring lives,
                            preserving cultural heritage, and offering
                            compassionate care and guidance to families during
                            life’s most meaningful moments.
                        </p>
                    </article>
                </div>
            </div>
        </div>

        <!-- New Section - Now visible! -->
        <div class="relative z-20 bg-white dark:bg-neutral-900 p-20">
            <div class="max-w-7xl mx-auto">
                <h2 class="text-3xl font-bold mb-4">Next Section</h2>
                <p>hello thereqyweiuqwyeiquwyeiuqwyeiuqyweiu qywe</p>
                <p class="mt-4">
                    Scroll up to see the sticky navbar in action!
                </p>
            </div>
        </div>

        <!-- Add more sections to test scrolling -->
        <div class="relative z-20 bg-gray-100 dark:bg-neutral-800 p-20">
            <div class="max-w-7xl mx-auto">
                <h2 class="text-3xl font-bold mb-4">Another Section</h2>
                <p>Keep scrolling to see the navbar stick to the top!</p>
            </div>
        </div>
        <div class="relative z-20 bg-white dark:bg-neutral-900 p-20">
            <div class="max-w-7xl mx-auto">
                <h2 class="text-3xl font-bold mb-4">Next Section</h2>
                <p>hello thereqyweiuqwyeiquwyeiuqwyeiuqyweiu qywe</p>
                <p class="mt-4">
                    Scroll up to see the sticky navbar in action!
                </p>
            </div>
        </div>

        <!-- Add more sections to test scrolling -->
        <div class="relative z-20 bg-gray-100 dark:bg-neutral-800 p-20">
            <div class="max-w-7xl mx-auto">
                <h2 class="text-3xl font-bold mb-4">Another Section</h2>
                <p>Keep scrolling to see the navbar stick to the top!</p>
            </div>
        </div>
    </div>
</template>
