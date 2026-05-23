<template>
    <div class="min-h-screen bg-gray-100 flex">
        <!-- Sidebar Navigation (Clerk Scope Only) -->
        <aside
            class="w-64 bg-slate-900 text-white flex flex-col justify-between hidden md:flex"
        >
            <div class="p-5">
                <div
                    class="flex items-center space-x-2 font-bold text-xl tracking-wider mb-8 text-emerald-400"
                >
                    <span>🪦 SystemName</span>
                </div>
                <nav class="space-y-2">
                    <a
                        href="#"
                        class="flex items-center space-x-3 bg-slate-800 text-white px-4 py-2.5 rounded-lg font-medium"
                    >
                        <span>📊 Dashboard</span>
                    </a>
                    <a
                        href="#"
                        class="flex items-center space-x-3 text-slate-400 hover:bg-slate-800 hover:text-white px-4 py-2.5 rounded-lg transition"
                    >
                        <span>🗺️ Interactive Map</span>
                    </a>
                    <a
                        href="#"
                        class="flex items-center space-x-3 text-slate-400 hover:bg-slate-800 hover:text-white px-4 py-2.5 rounded-lg transition"
                    >
                        <span>🔲 Lot Management</span>
                    </a>
                    <a
                        href="#"
                        class="flex items-center space-x-3 text-slate-400 hover:bg-slate-800 hover:text-white px-4 py-2.5 rounded-lg transition"
                    >
                        <span>📅 Burial Schedules</span>
                    </a>
                    <a
                        href="#"
                        class="flex items-center space-x-3 text-slate-400 hover:bg-slate-800 hover:text-white px-4 py-2.5 rounded-lg transition"
                    >
                        <span>📜 Burial Records</span>
                    </a>
                </nav>
            </div>
            <div
                class="p-5 border-t border-slate-800 flex items-center space-x-3"
            >
                <div
                    class="w-9 h-9 bg-emerald-600 rounded-full flex items-center justify-center font-semibold"
                >
                    B
                </div>
                <div>
                    <p class="text-sm font-medium leading-none">
                        Beans (Clerk)
                    </p>
                    <span class="text-xs text-slate-400">Front Desk Ops</span>
                </div>
            </div>
        </aside>

        <!-- Main Content Work Area -->
        <main class="flex-1 p-8 overflow-y-auto">
            <!-- Top Row Header -->
            <header
                class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 pb-5 border-b border-gray-200"
            >
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        Operational Dashboard
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Welcome back! Manage daily cemetery check-ins, bookings,
                        and lookups here.
                    </p>
                </div>
                <div
                    class="mt-4 md:mt-0 text-sm text-right font-medium text-gray-600 bg-white px-4 py-2 rounded-lg shadow-sm border"
                >
                    📅 Today is:
                    <span class="text-emerald-600 font-semibold"
                        >May 23, 2026</span
                    >
                </div>
            </header>

            <!-- Section: Strategic Quick Action Tools -->
            <section class="mb-8">
                <h2
                    class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3"
                >
                    Quick Actions
                </h2>
                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
                >
                    <button
                        class="flex items-center p-4 bg-white border border-emerald-100 hover:border-emerald-300 rounded-xl shadow-sm hover:shadow transition group text-left"
                    >
                        <div
                            class="p-3 bg-emerald-50 text-emerald-600 rounded-lg group-hover:bg-emerald-600 group-hover:text-white transition mr-4 text-xl"
                        >
                            📝
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">
                                New Burial Record
                            </p>
                            <p class="text-xs text-gray-500">
                                Register a newly deceased profile
                            </p>
                        </div>
                    </button>
                    <button
                        class="flex items-center p-4 bg-white border border-indigo-100 hover:border-indigo-300 rounded-xl shadow-sm hover:shadow transition group text-left"
                    >
                        <div
                            class="p-3 bg-indigo-50 text-indigo-600 rounded-lg group-hover:bg-indigo-600 group-hover:text-white transition mr-4 text-xl"
                        >
                            📅
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">
                                Schedule Interment
                            </p>
                            <p class="text-xs text-gray-500">
                                Book a timestamp for an upcoming service
                            </p>
                        </div>
                    </button>
                    <button
                        class="flex items-center p-4 bg-white border border-amber-100 hover:border-amber-300 rounded-xl shadow-sm hover:shadow transition group text-left"
                    >
                        <div
                            class="p-3 bg-amber-50 text-amber-600 rounded-lg group-hover:bg-amber-600 group-hover:text-white transition mr-4 text-xl"
                        >
                            🔍
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">
                                Find Lot/Grave
                            </p>
                            <p class="text-xs text-gray-500">
                                Instantly locate a lot layout for families
                            </p>
                        </div>
                    </button>
                </div>
            </section>

            <!-- Main Columns Grid Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Side Primary Block: Today's Interactive Field Schedule -->
                <div class="lg:col-span-2 space-y-6">
                    <div
                        class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden"
                    >
                        <div
                            class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center"
                        >
                            <h3
                                class="font-bold text-gray-800 flex items-center"
                            >
                                <span class="mr-2">🔔</span> Today's Field
                                Schedule
                            </h3>
                            <span
                                class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-xs font-semibold rounded-full"
                                >3 Scheduled Today</span
                            >
                        </div>
                        <div class="divide-y divide-gray-100">
                            <!-- Item 1 -->
                            <div
                                class="p-6 flex items-start justify-between hover:bg-slate-50 transition"
                            >
                                <div class="flex items-start space-x-4">
                                    <div
                                        class="text-center bg-slate-100 text-slate-700 font-bold p-2.5 rounded-lg w-20 shadow-inner border border-slate-200"
                                    >
                                        <p
                                            class="text-xs tracking-wide uppercase font-semibold text-slate-500"
                                        >
                                            Time
                                        </p>
                                        <p class="text-sm">09:00 AM</p>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-900">
                                            Mariano S. Ramos
                                        </h4>
                                        <p class="text-sm text-gray-600">
                                            Lot:
                                            <span
                                                class="font-mono bg-gray-100 px-1 py-0.5 rounded text-xs"
                                                >SEC-B-ROW4-P12</span
                                            >
                                        </p>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            Contact: Juan Ramos (Son) •
                                            0917-XXXXXXX
                                        </p>
                                    </div>
                                </div>
                                <span
                                    class="px-2.5 py-1 bg-amber-100 text-amber-800 text-xs font-semibold rounded-full"
                                    >In Progress</span
                                >
                            </div>
                            <!-- Item 2 -->
                            <div
                                class="p-6 flex items-start justify-between hover:bg-slate-50 transition"
                            >
                                <div class="flex items-start space-x-4">
                                    <div
                                        class="text-center bg-slate-100 text-slate-700 font-bold p-2.5 rounded-lg w-20 shadow-inner border border-slate-200"
                                    >
                                        <p
                                            class="text-xs tracking-wide uppercase font-semibold text-slate-500"
                                        >
                                            Time
                                        </p>
                                        <p class="text-sm">01:30 PM</p>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-900">
                                            Leonora C. Santos
                                        </h4>
                                        <p class="text-sm text-gray-600">
                                            Lot:
                                            <span
                                                class="font-mono bg-gray-100 px-1 py-0.5 rounded text-xs"
                                                >SEC-A-ROW1-P05</span
                                            >
                                        </p>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            Contact: Maria Santos (Daughter)
                                        </p>
                                    </div>
                                </div>
                                <span
                                    class="px-2.5 py-1 bg-slate-100 text-slate-600 text-xs font-semibold rounded-full"
                                    >Confirmed</span
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side: Fast Quick View Local Context Widgets -->
                <div class="space-y-6">
                    <!-- Live Activity Tracking Log (Proves accountability to your panelists) -->
                    <div
                        class="bg-white rounded-xl border border-gray-200 shadow-sm p-6"
                    >
                        <h3
                            class="font-bold text-gray-800 mb-4 flex items-center"
                        >
                            <span class="mr-2">📝</span> Your Recent Encoding
                            Work
                        </h3>
                        <div class="space-y-4">
                            <div class="flex items-start text-sm">
                                <div
                                    class="w-2 h-2 rounded-full bg-emerald-500 mt-1.5 mr-3 flex-shrink-0"
                                ></div>
                                <div>
                                    <p class="text-gray-800 font-medium">
                                        Registered profile: Ricardo Diaz
                                    </p>
                                    <span class="text-xs text-gray-400"
                                        >14 minutes ago</span
                                    >
                                </div>
                            </div>
                            <div class="flex items-start text-sm">
                                <div
                                    class="w-2 h-2 rounded-full bg-emerald-500 mt-1.5 mr-3 flex-shrink-0"
                                ></div>
                                <div>
                                    <p class="text-gray-800 font-medium">
                                        Updated schedule for Leonora C. Santos
                                    </p>
                                    <span class="text-xs text-gray-400"
                                        >1 hour ago</span
                                    >
                                </div>
                            </div>
                            <div class="flex items-start text-sm">
                                <div
                                    class="w-2 h-2 rounded-full bg-indigo-500 mt-1.5 mr-3 flex-shrink-0"
                                ></div>
                                <div>
                                    <p class="text-gray-800 font-medium">
                                        Changed status of SEC-B-ROW4-P12 to
                                        "Occupied"
                                    </p>
                                    <span class="text-xs text-gray-400"
                                        >2 hours ago</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Micro-capacity Reference Counter -->
                    <div
                        class="bg-white rounded-xl border border-gray-200 shadow-sm p-6"
                    >
                        <h3
                            class="font-bold text-gray-800 mb-2 flex items-center"
                        >
                            <span class="mr-2">🗺️</span> Fast Section Lookup
                        </h3>
                        <p class="text-xs text-gray-500 mb-4">
                            Quickly check available space for immediate
                            inquiries.
                        </p>
                        <div class="space-y-3">
                            <div>
                                <div
                                    class="flex justify-between text-xs font-semibold text-gray-700 mb-1"
                                >
                                    <span>Section A (Premium Regular)</span>
                                    <span class="text-emerald-600"
                                        >45 Available Lots</span
                                    >
                                </div>
                                <div
                                    class="w-full bg-gray-100 rounded-full h-2"
                                >
                                    <div
                                        class="bg-emerald-500 h-2 rounded-full"
                                        style="width: 85%"
                                    ></div>
                                </div>
                            </div>
                            <div>
                                <div
                                    class="flex justify-between text-xs font-semibold text-gray-700 mb-1"
                                >
                                    <span>Section B (Common Lawn)</span>
                                    <span class="text-amber-600"
                                        >12 Available Lots</span
                                    >
                                </div>
                                <div
                                    class="w-full bg-gray-100 rounded-full h-2"
                                >
                                    <div
                                        class="bg-amber-500 h-2 rounded-full"
                                        style="width: 96%"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
