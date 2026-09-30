<script setup>
import { Link } from "@inertiajs/vue3";
</script>

<template>
    <!-- Use Map -->
    <div id="map" class="px-4 pt-10 sm:px-6 sm:pt-12 lg:px-4 lg:pt-14">
        <div
            class="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:grid-rows-[auto_1fr]"
        >
            <!-- Hero Card — tall image, spans 2 rows on sm+ -->
            <article
                class="group relative flex flex-col justify-between overflow-hidden rounded-2xl bg-neutral-900 p-7 sm:col-span-2 sm:row-span-2 sm:row-start-1 min-h-[480px] sm:min-h-[640px]"
            >
                <!-- Map background image -->
                <div
                    class="absolute inset-0 bg-[url('/images/use-map.webp')] bg-cover bg-center bg-no-repeat transition-transform duration-700 ease-out group-hover:scale-[1.03]"
                    aria-hidden="true"
                />
                <!-- Scrim -->
                <div
                    class="absolute inset-0 bg-gradient-to-br from-black/85 via-black/35 to-black/75"
                    aria-hidden="true"
                />

                <!-- Top label -->
                <div class="relative z-10">
                    <span
                        class="inline-flex items-center rounded-full bg-yellow-400/15 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-yellow-300 ring-1 ring-inset ring-yellow-300/25"
                    >
                        Interactive Map
                    </span>
                </div>

                <!-- Bottom content -->
                <div class="relative z-10">
                    <h2
                        class="text-3xl font-semibold text-white sm:text-4xl lg:text-5xl"
                    >
                        Deceased Search &amp; Plot Map
                    </h2>
                    <p
                        class="mt-3 max-w-md text-base leading-relaxed text-white/70"
                    >
                        Locate any burial plot and search over 21,000 records —
                        all from one interactive map.
                    </p>
                    <Link
                        :href="route('visitor.map.index')"
                        class="mt-6 inline-flex items-center gap-2 rounded-full bg-yellow-400 px-6 py-3 text-sm font-semibold text-gray-900 shadow-lg transition-all duration-300 ease-out hover:bg-yellow-300 hover:shadow-yellow-400/25 hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-yellow-400 focus-visible:ring-offset-2 focus-visible:ring-offset-black"
                    >
                        View Map
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
                            aria-hidden="true"
                        >
                            <path d="M5 12h14" />
                            <path d="m12 5 7 7-7 7" />
                        </svg>
                    </Link>
                </div>
            </article>

            <!-- Row 1 right: two compact cards side by side -->
            <div class="flex gap-4 sm:row-start-1 min-h-[200px]">
                <!-- Pin a Location -->
                <article
                    class="flex flex-1 flex-col justify-between rounded-2xl bg-neutral-900 p-6 ring-1 ring-white/10"
                >
                    <span
                        class="inline-flex size-10 items-center justify-center rounded-xl bg-yellow-400/10 ring-1 ring-inset ring-yellow-300/20"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="text-yellow-300"
                            aria-hidden="true"
                        >
                            <path d="M12 17v5" />
                            <path
                                d="M9 10.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24V16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V7a1 1 0 0 1 1-1 2 2 0 0 0 0-4H8a2 2 0 0 0 0 4 1 1 0 0 1 1 1z"
                            />
                        </svg>
                    </span>
                    <div class="mt-6">
                        <h3 class="text-base font-semibold text-white">
                            Pin a Location
                        </h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-neutral-400">
                            Get the exact path to any grave directly on the map.
                        </p>
                    </div>
                </article>

                <!-- Find on Map -->
                <article
                    class="flex flex-1 flex-col justify-between rounded-2xl bg-neutral-900 p-6 ring-1 ring-white/10"
                >
                    <span
                        class="inline-flex size-10 items-center justify-center rounded-xl bg-yellow-400/10 ring-1 ring-inset ring-yellow-300/20"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="text-yellow-300"
                            aria-hidden="true"
                        >
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.3-4.3" />
                        </svg>
                    </span>
                    <div class="mt-6">
                        <h3 class="text-base font-semibold text-white">
                            Find the Deceased
                        </h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-neutral-400">
                            Search by name and jump straight to their plot on the map.
                        </p>
                    </div>
                </article>
            </div>

            <!-- Row 2 right: Search Records — yellow gradient with decorative elements -->
            <article
                class="relative flex flex-col justify-between overflow-hidden rounded-2xl bg-gradient-to-br from-yellow-600/95 via-yellow-500/90 to-yellow-400/85 dark:from-yellow-700/95 dark:via-yellow-600/90 dark:to-yellow-500/85 p-6 sm:row-start-2"
            >
                <!-- Decorative dot grid -->
                <svg
                    class="absolute inset-0 h-full w-full opacity-10"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden="true"
                >
                    <defs>
                        <pattern id="dots" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
                            <circle cx="2" cy="2" r="1.5" fill="white" />
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#dots)" />
                </svg>

                <!-- Decorative large faded icon -->
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="180"
                    height="180"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="0.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="absolute -bottom-8 -right-8 text-white/15"
                    aria-hidden="true"
                >
                    <circle cx="10" cy="8" r="5" />
                    <path d="M2 21a8 8 0 0 1 10.434-7.62" />
                    <circle cx="18" cy="18" r="3" />
                    <path d="m22 22-1.9-1.9" />
                </svg>

                <!-- Top icon + label -->
                <div class="relative z-10 flex items-center gap-3">
                    <span
                        class="inline-flex size-11 items-center justify-center rounded-xl bg-black/15 ring-1 ring-inset ring-black/10"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="22"
                            height="22"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="text-white"
                            aria-hidden="true"
                        >
                            <circle cx="10" cy="8" r="5" />
                            <path d="M2 21a8 8 0 0 1 10.434-7.62" />
                            <circle cx="18" cy="18" r="3" />
                            <path d="m22 22-1.9-1.9" />
                        </svg>
                    </span>
                    <span class="text-xs font-semibold uppercase tracking-wider text-white/70">
                        Burial Records
                    </span>
                </div>

                <!-- Stat + description -->
                <div class="relative z-10 mt-4">
                    <p class="text-5xl font-bold text-white sm:text-6xl">
                        21,000+
                    </p>
                    <h3 class="mt-2 text-xl font-semibold text-white">
                        Search Records
                    </h3>
                    <p class="mt-2 text-sm leading-relaxed text-white/80">
                        Find the deceased quickly by name or plot number across
                        decades of preserved burial data.
                    </p>
                </div>

                <!-- Divider + burial types -->
                <div class="relative z-10 mt-6 border-t border-white/20 pt-4 flex items-center gap-3">
                    <span class="text-sm font-medium text-white/80">Normal Burial</span>
                    <span class="w-px h-4 bg-white/30"></span>
                    <span class="text-sm font-medium text-white/80">Cremation</span>
                </div>
            </article>
        </div>
    </div>
    <!-- End Use Map -->
</template>
