<script setup>
import SidebarLink from "@/Components/Dashboard/SidebarLink.vue";
import Sidebar from "@/Components/Dashboard/Sidebar.vue";
import Header from "@/Components/Dashboard/Header.vue";
</script>

<template>
    <div
        id="hs-pro-sidebar"
        class="hs-overlay [--auto-close:md] [--opened:md] [--body-scroll:true] hs-overlay-open:translate-x-0 -translate-x-full transition-all duration-300 transform w-65 hs-overlay-minified:w-13 overflow-hidden md:hs-overlay-minified:overflow-visible hidden fixed inset-y-0 z-60 start-0 bg-white dark:bg-neutral-800 border-e border-gray-200 dark:border-neutral-700 md:block md:translate-x-0 md:end-auto md:bottom-0"
        data-hs-overlay-options='{
        "backdropClasses": "bg-gray-900/50 dark:bg-neutral-900/80 fixed inset-0 z-58 md:hidden"
    }'
        role="dialog"
        tabindex="-1"
        aria-label="Sidebar"
    >
        <Sidebar />
    </div>

    <!-- ========== MAIN CONTENT ========== -->
    <main
        class="md:ps-65 md:hs-overlay-minified:ps-13 transition-all duration-300 min-h-screen bg-white dark:bg-neutral-900"
    >
        <Header />
        <slot />
    </main>
    <!-- ========== END MAIN CONTENT ========== -->
</template>
