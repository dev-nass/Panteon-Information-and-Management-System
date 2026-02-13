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
                <div class="col-span-12 lg:col-span-12">
                    <!-- Card -->
                    <a
                        class="group relative block rounded-xl overflow-hidden focus:outline-none h-64 md:h-[30rem] lg:h-full"
                        href="#"
                        full
                    >
                        <div
                            class="h-full sm:aspect-w-12 sm:aspect-h-7 sm:aspect-none rounded-xl overflow-hidden"
                        >
                            <img
                                class="group-hover:scale-105 group-focus:scale-105 transition-transform duration-500 ease-in-out rounded-xl w-full h-full object-cover"
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
                    </a>
                    <!-- End Card -->
                </div>
                <!-- End Col -->

                <div class="grid grid-cols-12 gap-6 col-span-12">
                    <!-- Cols 2 / 5 -->
                    <div class="col-span-12 md:col-span-6 h-full">
                        <!-- Card -->
                        <a
                            class="group relative block rounded-xl overflow-hidden focus:outline-none h-full"
                            href="#"
                        >
                            <div
                                class="h-full sm:aspect-w-12 sm:aspect-h-7 sm:aspect-none rounded-xl overflow-hidden"
                            >
                                <div
                                    class="bg-green-500 text-base md:text-xl py-6 px-4 group-hover:scale-105 group-focus:scale-105 transition-transform duration-500 ease-in-out rounded-xl w-full h-full"
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
                    <div class="col-span-12 md:col-span-6 h-full">
                        <!-- Card -->
                        <a
                            class="group relative block rounded-xl overflow-hidden focus:outline-none h-full"
                            href="#"
                        >
                            <div
                                class="h-full sm:aspect-w-12 sm:aspect-h-7 sm:aspect-none rounded-xl overflow-hidden"
                            >
                                <div
                                    class="bg-yellow-400 text-base md:text-xl py-6 px-4 group-hover:scale-105 group-focus:scale-105 transition-transform duration-500 ease-in-out rounded-xl w-full h-full"
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
                <div class="sm:col-span-12 md:col-span-6">
                    <!-- Card -->
                    <a
                        class="group relative block rounded-xl overflow-hidden focus:outline-none h-64 md:h-[30rem] lg:h-full"
                        href="#"
                    >
                        <div
                            class="h-full sm:aspect-w-12 sm:aspect-h-7 sm:aspect-none rounded-xl overflow-hidden"
                        >
                            <img
                                class="group-hover:scale-105 group-focus:scale-105 transition-transform duration-500 ease-in-out rounded-xl w-full h-full object-cover"
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
                    </a>
                    <!-- End Card -->
                </div>
                <!-- End Col -->

                <!-- Cols 5 / 5 -->
                <div class="sm:col-span-12 md:col-span-6">
                    <!-- Card -->
                    <a
                        class="group relative block rounded-xl overflow-hidden focus:outline-none h-64 md:h-[30rem] lg:h-full"
                        href="#"
                    >
                        <div
                            class="h-full sm:aspect-w-12 sm:aspect-h-7 sm:aspect-none rounded-xl overflow-hidden"
                        >
                            <img
                                class="group-hover:scale-105 group-focus:scale-105 transition-transform duration-500 ease-in-out rounded-xl w-full h-full object-cover"
                                src="/public/images/apartment.png"
                                alt="Masonry Cards Image"
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
                    </a>
                    <!-- End Card -->
                </div>
                <!-- End Col -->
            </div>
        </div>
        <!-- End Grid -->
    </div>
    <!-- End Masonry Cards -->
</template>
