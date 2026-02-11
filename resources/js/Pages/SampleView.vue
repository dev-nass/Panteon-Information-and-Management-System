<script setup>
import Navbar from "@/Components/Navbar.vue";
import AboutUsSection from "@/Components/Welcome/AboutUsSection.vue";
import GallerySection from "@/Components/Welcome/GallerySection.vue";
import HeroSection from "@/Components/Welcome/HeroSection.vue";
</script>
<template>
    <Navbar />
    <HeroSection />
    <AboutUsSection />
    <GallerySection />

    <!-- Hero -->
    <div class="px-4 py-[4rem] sm:px-6 lg:px-4">
        <div
            class="relative h-150 max-h-250 md:h-[100dvh] flex flex-col bg-[url('/images/use-map.png')] bg-cover bg-center bg-no-repeat rounded-2xl overflow-hidden"
        >
            <!-- Overlay -->
            <div class="absolute inset-0 bg-black/40 rounded-2xl"></div>

            <!-- Content - Add relative z-10 here -->
            <div
                class="relative z-10 h-full w-full flex flex-col md:flex-row justify-between pt-10 lg:pt-20 mt-auto ps-5 pe-5 pb-5 md:ps-10 md:pb-10"
            >
                <article class="flex flex-col justify-between">
                    <div>
                        <p
                            class="text-base md:text-xl text-yellow-500 font-bold"
                        >
                            Search our Records
                        </p>
                        <h1
                            class="font-semibold text-left text-4xl md:text-6xl text-white"
                        >
                            Deceased Search and Plot Map
                        </h1>
                    </div>
                    <div class="hidden lg:flex lg:space-x-5 text-white">
                        <div
                            class="backdrop-blur-md bg-white/10 border border-white/20 shadow-lg rounded-xl p-5 flex items-center space-x-3"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="70"
                                height="70"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="lucide lucide-pin-icon lucide-pin"
                            >
                                <path d="M12 17v5" />
                                <path
                                    d="M9 10.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24V16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V7a1 1 0 0 1 1-1 2 2 0 0 0 0-4H8a2 2 0 0 0 0 4 1 1 0 0 1 1 1z"
                                />
                            </svg>
                            <div>
                                <h2 class="text-4xl font-bold">Pin</h2>
                                <p class="w-[18ch]">
                                    And get the extract path to grave
                                </p>
                            </div>
                        </div>

                        <div
                            class="backdrop-blur-md bg-white/10 border border-white/20 shadow-lg rounded-xl p-5 flex items-center space-x-3"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="70"
                                height="70"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="lucide lucide-user-round-search-icon lucide-user-round-search"
                            >
                                <circle cx="10" cy="8" r="5" />
                                <path d="M2 21a8 8 0 0 1 10.434-7.62" />
                                <circle cx="18" cy="18" r="3" />
                                <path d="m22 22-1.9-1.9" />
                            </svg>
                            <div>
                                <h2 class="text-4xl font-bold">Search</h2>
                                <p class="w-[18ch]">
                                    And find the deceased at ease
                                </p>
                            </div>
                        </div>
                    </div>
                </article>
                <div class="text-right flex flex-col justify-end">
                    <div class="flex mt-5 gap-4 flex-wrap justify-end mb-2">
                        <a
                            href="#_"
                            class="inline-flex items-center justify-center px-5 py-3 font-semibold text-center text-yellow-500 no-underline align-middle transition-all duration-300 ease-in-out bg-neutral-900 backdrop-blur-md border border-yellow rounded-full cursor-pointer select-none hover:bg-yellow-500 hover:text-black hover:border-white/40 focus:shadow-xs focus:no-underline shadow-lg"
                        >
                            Button Text
                        </a>
                    </div>
                    <p
                        class="text-white text-base lg:w-[50ch] md:text-lg leading-relaxed"
                    >
                        A historic ground dedicated to honoring lives,
                        preserving cultural heritage, and providing
                        compassionate support to families across generations.
                    </p>
                </div>
            </div>
        </div>
    </div>
    <!-- End Hero -->
</template>
