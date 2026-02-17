<template>
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
                    data-aos="fade-up-right"
                >
                    Panteon De Dasmariñas
                </h1>
                <div class="text-center md:text-left">
                    <p
                        class="text-white text-base md:text-lg leading-relaxed"
                        data-aos="fade-down-right"
                    >
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
</template>
