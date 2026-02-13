<template>
    <!-- Contact Us -->
    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
        <div class="max-w-2xl lg:max-w-5xl mx-auto">
            <div class="text-left">
                <h1
                    class="text-lg font-bold text-green-600 dark:text-green-500 sm:text-3xl"
                >
                    Contact us
                </h1>
                <h2
                    class="text-3xl font-semibold text-gray-800 dark:text-neutral-200 sm:text-4xl"
                >
                    Get in Touch with Our Team
                </h2>
                <p class="mt-1 text-gray-600 dark:text-neutral-300">
                    We're here to answer your questions, discuss your project,
                    and help you find the best solutions for your software
                    needs. Reach out to us, and let's start building something
                    great together.
                </p>
            </div>

            <div class="mt-12 grid items-center lg:grid-cols-2 gap-6 lg:gap-16">
                <!-- Card -->
                <div
                    class="flex flex-col bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl p-4 sm:p-6 lg:p-8"
                >
                    <h2
                        class="mb-8 text-xl font-semibold text-gray-800 dark:text-neutral-200"
                    >
                        Fill in the form
                    </h2>

                    <form>
                        <div class="grid gap-7">
                            <!-- Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label
                                        for="hs-firstname-contacts-1"
                                        class="sr-only"
                                        >First Name</label
                                    >
                                    <input
                                        type="text"
                                        name="hs-firstname-contacts-1"
                                        id="hs-firstname-contacts-1"
                                        class="py-2.5 sm:py-3 px-4 block w-full border border-gray-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 rounded-lg sm:text-sm text-gray-800 dark:text-neutral-200 placeholder:text-gray-500 dark:placeholder:text-neutral-400 focus:outline-none focus:border-green-600 dark:focus:border-green-500 focus:ring-2 focus:ring-green-600 dark:focus:ring-green-500 disabled:opacity-50 disabled:pointer-events-none"
                                        placeholder="First Name"
                                    />
                                </div>

                                <div>
                                    <label
                                        for="hs-lastname-contacts-1"
                                        class="sr-only"
                                        >Last Name</label
                                    >
                                    <input
                                        type="text"
                                        name="hs-lastname-contacts-1"
                                        id="hs-lastname-contacts-1"
                                        class="py-2.5 sm:py-3 px-4 block w-full border border-gray-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 rounded-lg sm:text-sm text-gray-800 dark:text-neutral-200 placeholder:text-gray-500 dark:placeholder:text-neutral-400 focus:outline-none focus:border-green-600 dark:focus:border-green-500 focus:ring-2 focus:ring-green-600 dark:focus:ring-green-500 disabled:opacity-50 disabled:pointer-events-none"
                                        placeholder="Last Name"
                                    />
                                </div>
                            </div>
                            <!-- End Grid -->

                            <div>
                                <label for="hs-email-contacts-1" class="sr-only"
                                    >Email</label
                                >
                                <input
                                    type="email"
                                    name="hs-email-contacts-1"
                                    id="hs-email-contacts-1"
                                    autocomplete="email"
                                    class="py-2.5 sm:py-3 px-4 block w-full border border-gray-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 rounded-lg sm:text-sm text-gray-800 dark:text-neutral-200 placeholder:text-gray-500 dark:placeholder:text-neutral-400 focus:outline-none focus:border-green-600 dark:focus:border-green-500 focus:ring-2 focus:ring-green-600 dark:focus:ring-green-500 disabled:opacity-50 disabled:pointer-events-none"
                                    placeholder="Email"
                                />
                            </div>

                            <div>
                                <label for="hs-phone-number-1" class="sr-only"
                                    >Phone Number</label
                                >
                                <input
                                    type="text"
                                    name="hs-phone-number-1"
                                    id="hs-phone-number-1"
                                    class="py-2.5 sm:py-3 px-4 block w-full border border-gray-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 rounded-lg sm:text-sm text-gray-800 dark:text-neutral-200 placeholder:text-gray-500 dark:placeholder:text-neutral-400 focus:outline-none focus:border-green-600 dark:focus:border-green-500 focus:ring-2 focus:ring-green-600 dark:focus:ring-green-500 disabled:opacity-50 disabled:pointer-events-none"
                                    placeholder="Phone Number"
                                />
                            </div>

                            <div>
                                <label for="hs-about-contacts-1" class="sr-only"
                                    >Details</label
                                >
                                <textarea
                                    id="hs-about-contacts-1"
                                    name="hs-about-contacts-1"
                                    rows="4"
                                    class="py-2.5 sm:py-3 px-4 block w-full border border-gray-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 rounded-lg sm:text-sm text-gray-800 dark:text-neutral-200 placeholder:text-gray-500 dark:placeholder:text-neutral-400 focus:outline-none focus:border-green-600 dark:focus:border-green-500 focus:ring-2 focus:ring-green-600 dark:focus:ring-green-500 disabled:opacity-50 disabled:pointer-events-none"
                                    placeholder="Details"
                                ></textarea>
                            </div>
                        </div>
                        <!-- End Grid -->

                        <div class="mt-4 grid">
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center px-5 py-3 font-semibold text-center text-white no-underline align-middle transition-all duration-300 ease-in-out bg-green-500 backdrop-blur-md border border-white/20 rounded-full cursor-pointer select-none hover:bg-green-600 hover:border-white/40 hover:shadow-xl focus:shadow-xs focus:no-underline shadow-lg"
                            >
                                Button Text
                            </button>
                        </div>

                        <div class="mt-3 text-center">
                            <p
                                class="text-sm text-gray-500 dark:text-neutral-400"
                            >
                                We'll get back to you in 1-2 business days.
                            </p>
                        </div>
                    </form>
                </div>
                <!-- End Card -->

                <div class="">
                    <p class="mt-1 font-bold text-black dark:text-neutral-300">
                        Prefer a Direct Approach
                    </p>
                    <!-- Icon Block -->
                    <div class="flex gap-x-7 py-2">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="lucide lucide-phone-icon lucide-phone text-green-600 dark:text-green-500"
                        >
                            <path
                                d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"
                            />
                        </svg>
                        <div class="grow">
                            <h3 class="text-gray-800 dark:text-neutral-200">
                                0998-9677-278
                            </h3>
                        </div>
                    </div>
                    <!-- End Icon Block -->

                    <!-- Icon Block -->
                    <div class="flex gap-x-7 py-2">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="lucide lucide-mail-icon lucide-mail text-green-600 dark:text-green-500"
                        >
                            <path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7" />
                            <rect x="2" y="4" width="20" height="16" rx="2" />
                        </svg>
                        <div class="grow">
                            <h3 class="text-gray-800 dark:text-neutral-200">
                                plfdasmarinas@gmail.com
                            </h3>
                        </div>
                    </div>
                    <!-- End Icon Block -->

                    <!-- Icon Block -->
                    <div class="flex gap-x-7 py-2">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="lucide lucide-clock-icon lucide-clock text-green-600 dark:text-green-500"
                        >
                            <path d="M12 6v6l4 2" />
                            <circle cx="12" cy="12" r="10" />
                        </svg>
                        <div class="grow">
                            <h3 class="text-gray-800 dark:text-neutral-200">
                                Monday to Sunday, 6am - 6pm (GMT +8)
                            </h3>
                        </div>
                    </div>
                    <!-- End Icon Block -->

                    <div class="relative">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2006.8864824785987!2d120.97624765644527!3d14.305315628933007!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397d5c33a7a3f51%3A0x5f9a10924d8d15c0!2sPanteon%20de%20Dasmari%C3%B1as%20Cemetery!5e1!3m2!1sen!2sph!4v1770945390724!5m2!1sen!2sph"
                            class="w-full lg:h-[400px]"
                            height="300"
                            style="border: 0"
                            allowfullscreen="false"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                        ></iframe>
                        <div
                            class="absolute bottom-2 w-[95%] bg-white dark:text-black px-2 py-1 left-1/2 transform -translate-x-1/2"
                        >
                            <p class="mt-1 font-medium text-black">
                                Visit Our Office
                            </p>
                            <article class="flex space-x-2">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="25"
                                    height="25"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="lucide lucide-map-pin-house-icon lucide-map-pin-house text-green-600 dark:text-green-500"
                                >
                                    <path
                                        d="M15 22a1 1 0 0 1-1-1v-4a1 1 0 0 1 .445-.832l3-2a1 1 0 0 1 1.11 0l3 2A1 1 0 0 1 22 17v4a1 1 0 0 1-1 1z"
                                    />
                                    <path
                                        d="M18 10a8 8 0 0 0-16 0c0 4.993 5.539 10.193 7.399 11.799a1 1 0 0 0 .601.2"
                                    />
                                    <path d="M18 22v-3" />
                                    <circle cx="10" cy="10" r="3" />
                                </svg>
                                <p>
                                    Sitio Talisayan, Brgy. Sampaloc IV,
                                    Dasmariñas City, Cavite
                                </p>
                            </article>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Contact Us -->
</template>
