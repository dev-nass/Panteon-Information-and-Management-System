<script setup>
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    junctionId: Number,
    junctionNumber: String,
    junctionType: String,
});

const modalId = "junction-modal";

const getJunctionImage = (id) => {
    if (id === 1) {
        return "/images/entrance.jpg";
    } else if (id === 3 || id === 89) {
        return "/images/roundabout.jpg";
    } else if (id === 217) {
        return "/images/columbarium.jpg";
    }
    return null;
};

const getJunctionTitle = (id) => {
    if (id === 1) {
        return "Main Entrance";
    } else if (id === 3 || id === 89) {
        return "Rotonda / Roundabout";
    } else if (id === 217) {
        return "The Columbarium";
    }
    return "Junction";
};

const getJunctionSubheader = (id) => {
    if (id === 3 || id === 89) {
        return "You are close to your destination — keep following the path ahead.";
    } else if (id === 1) {
        return "You are at the main entrance. Follow the highlighted path to your destination.";
    } else if (id === 217) {
        return "You are near the columbarium.";
    }
    return "Follow the path to reach your destination.";
};
</script>

<template>
    <Modal :id="modalId" size="lg">
        <template #main>
            <div class="w-full">
                <!-- Only image + title/subheader — simplified -->
                <img
                    v-if="getJunctionImage(junctionId)"
                    :src="getJunctionImage(junctionId)"
                    :alt="getJunctionTitle(junctionId)"
                    class="w-full h-64 object-cover rounded-xl"
                    @error="
                        $event.target.src =
                            'https://via.placeholder.com/600x400?text=Junction+Image'
                    "
                />

                <div class="pt-5 text-center">
                    <h3
                        class="text-xl font-bold text-gray-800 dark:text-neutral-200"
                    >
                        {{ getJunctionTitle(junctionId) }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-neutral-400 mt-2 max-w-md mx-auto">
                        {{ getJunctionSubheader(junctionId) }}
                    </p>
                </div>
            </div>
        </template>

        <template #footer>
            <button
                type="button"
                class="w-full py-3 text-sm font-semibold text-green-600 dark:text-green-400 hover:bg-green-500/10 transition"
                :data-hs-overlay="`#${modalId}`"
            >
                Close
            </button>
        </template>
    </Modal>
</template>
