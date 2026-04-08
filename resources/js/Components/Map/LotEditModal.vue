<script setup>
import { onMounted, onBeforeUnmount, watch } from "vue";
import { useEditLotPlot } from "@/composables/lot_management/edit/useEditLotPlot";
import PlottingModalWrapper from "./PlottingModalWrapper.vue";
import "leaflet/dist/leaflet.css";
import "leaflet-draw/dist/leaflet.draw.css";

const props = defineProps({
    clusterId: { type: [Number, String], default: null },
    phases: { type: Array, default: () => [] },
    existingCoordinates: { type: [String, Object], default: null },
});

const emit = defineEmits(["coordinatesSet", "close"]);

const { coordinates, initializeMap, loadCluster, cleanupMap, getCoordinates } =
    useEditLotPlot();

onMounted(() => {
    initializeMap("lot-edit-map", props.existingCoordinates);

    if (props.clusterId) {
        loadCluster(props.clusterId, props.phases);
    }
});

onBeforeUnmount(() => {
    cleanupMap();
});

const saveCoordinates = () => {
    const coords = getCoordinates();
    if (!coords) {
        alert("Please place a marker on the map first");
        return;
    }
    emit("coordinatesSet", coords);
    emit("close");
};

watch(
    () => props.clusterId,
    (newClusterId) => {
        if (newClusterId) {
            loadCluster(newClusterId, props.phases);
        }
    }
);
</script>

<template>
    <PlottingModalWrapper
        title="Edit Lot Location"
        instruction="📍 Edit the lot location by dragging the marker or use the marker tool to place a new one."
        map-id="lot-edit-map"
        :coordinates="coordinates"
        :coordinate-label="
            coordinates
                ? `Coordinates set: ${coordinates.coordinates[1].toFixed(
                      6
                  )}, ${coordinates.coordinates[0].toFixed(6)}`
                : null
        "
        @save="saveCoordinates"
        @close="emit('close')"
    />
</template>
