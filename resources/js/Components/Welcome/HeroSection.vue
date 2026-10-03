<script setup>
import { Link } from "@inertiajs/vue3";
import Button from "../Form/Button.vue";

// Bound in script so Vue does not rewrite them into Vite asset imports.
// Serving these straight from public/ keeps the hero out of the hashed
// build manifest and lets it use the cache headers in public/.htaccess.
const heroImageSrc = "/" + "images/front-office-v5-1920.webp";
const heroImageSrcset = [768, 1280, 1920, 2560, 3072, 3840]
    .map((w) => `/images/front-office-v5-${w}.webp ${w}w`)
    .join(", ");

// The photo is 2.22:1 but the hero box is 0.60:1 on mobile (h-150 = 600px
// tall), so object-cover fits by height and renders ~1333px wide before
// cropping to the 358px slot. The browser has to be told that, or it
// sizes against the 390px viewport, picks the 768w candidate and renders
// a 346px-tall file into a 600px-tall box.
const heroImageSizes = "(min-width: 768px) 125vw, 342vw";
</script>

<template>
    <!-- Hero -->
    <div id="home" class="px-4 sm:px-6 lg:px-4">
        <div
            class="relative h-150 max-h-250 md:h-[90dvh] flex flex-col bg-neutral-900 rounded-2xl overflow-hidden"
        >
            <picture class="absolute inset-0 block h-full w-full">
                <img
                    :src="heroImageSrc"
                    :srcset="heroImageSrcset"
                    :sizes="heroImageSizes"
                    width="1920"
                    height="864"
                    alt=""
                    fetchpriority="high"
                    decoding="async"
                    class="h-full w-full object-cover object-center"
                />
            </picture>

            <!-- Content - Add relative z-10 here -->
            <div
                class="relative z-10 h-full w-full flex flex-col md:flex-col-reverse justify-between pt-10 lg:pt-20 mt-auto md:w-2/3 md:max-w-xl ps-5 pe-5 pb-5 md:ps-10 md:pb-10"
            >
                <h1
                    class="bona-nova-heading text-center md:text-left text-4xl md:text-6xl lg:text-8xl text-white"
                ></h1>
                <div class="text-center md:text-left">
                    <p class="text-white text-base md:text-lg leading-relaxed">
                        A historic ground dedicated to honoring lives,
                        preserving cultural heritage, and providing
                        compassionate support to families across generations.
                    </p>
                    <div
                        class="flex mt-5 gap-4 flex-wrap justify-center md:justify-start"
                    >
                        <Link
                            :href="route('visitor.map.index')"
                            class="inline-flex items-center justify-center px-5 py-3 font-semibold text-center text-white no-underline align-middle transition-all duration-300 ease-in-out bg-green-500 backdrop-blur-md border border-white/20 rounded-full cursor-pointer select-none hover:bg-green-600 hover:border-white/40 hover:shadow-xl focus:shadow-xs focus:no-underline shadow-lg"
                        >
                            View Map
                        </Link>
                        <a
                            href="#contact"
                            class="inline-flex items-center justify-center px-5 py-3 font-semibold text-center text-white no-underline align-middle transition-all duration-300 ease-in-out bg-white/10 backdrop-blur-md border border-white/20 rounded-full cursor-pointer select-none hover:bg-white/20 hover:border-white/40 focus:shadow-xs focus:no-underline shadow-lg"
                        >
                            Contact Us
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Hero -->
</template>
