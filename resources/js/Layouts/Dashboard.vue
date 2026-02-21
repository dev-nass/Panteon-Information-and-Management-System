<script setup>
import { ref } from "vue";

import Sidebar from "@/Components/Dashboard/Sidebar.vue";

const isActive = ref(true);
</script>

<template>
    <div class="flex">
        <Sidebar :isActive="isActive" @toggle="isActive = !isActive" />
        <!-- Main Content -->
        <main
            id="main-content"
            class="flex-1 transition-all duration-300 lg:ml-16 py-2 pr-2"
            :class="{ 'lg:ml-67': isActive }"
        >
            <slot />
        </main>
    </div>
</template>
