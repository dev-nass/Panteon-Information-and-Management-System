<script setup>
import { useMap } from "@/composables/clerk/useMap";
import { ref, onMounted } from "vue";

import Dashboard from "@/Layouts/Dashboard.vue";

const { initializeMap } = useMap();

const mapContainer = ref(null);

defineOptions({
    layout: Dashboard,
});

onMounted(() => {
    initializeMap(mapContainer.value);
});
</script>

<template>
    <section id="map-wrapper" class="border border-red-500">
        <div
            ref="mapContainer"
            id="map"
            class="h-full w-full z-0"
            style="height: 97vh"
        ></div>
    </section>
</template>
