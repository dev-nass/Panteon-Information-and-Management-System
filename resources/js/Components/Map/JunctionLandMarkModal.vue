<script setup>
import { ref, watch } from "vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    junctionId: Number,
    junctionNumber: String,
    junctionType: String,
});

const modalId = "junction-modal";

const getJunctionImage = (id) => {
    if (id === 1) {
        return "/images/entrance.jpg"; // Entrance image
    } else if (id === 3 || id === 89) {
        return "/images/roundabout.jpg"; // Same image for 3 and 89
    }
    return null;
};

const getJunctionTitle = (id) => {
    if (id === 1) {
        return "Main Entrance";
    } else if (id === 3 || id === 89) {
        return `Junction ${props.junctionNumber}`;
    }
    return "Junction";
};

const getJunctionDescription = (id) => {
    if (id === 1) {
        return "This is the main entrance to the cemetery. Start your journey here.";
    } else if (id === 3 || id === 89) {
        return "Follow the path to reach your destination from this junction point.";
    }
    return "";
};
</script>

<template>
    <Modal :id="modalId" size="lg">
        <template #main>
            <div class="w-full">
                <img
                    :src="getJunctionImage(junctionId)"
                    :alt="getJunctionTitle(junctionId)"
                    class="w-full h-64 object-cover rounded-lg mb-4"
                    @error="
                        $event.target.src =
                            'https://via.placeholder.com/600x400?text=Junction+Image'
                    "
                />

                <h3
                    class="text-2xl font-bold text-gray-800 dark:text-neutral-200 mb-2"
                >
                    {{ getJunctionTitle(junctionId) }}
                </h3>

                <p class="text-gray-600 dark:text-neutral-400 mb-4">
                    {{ getJunctionDescription(junctionId) }}
                </p>

                <div
                    class="bg-gray-100 dark:bg-neutral-800 rounded-lg p-4 text-left"
                >
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <span
                                class="font-semibold text-gray-700 dark:text-neutral-300"
                                >Junction ID:</span
                            >
                            <span
                                class="ml-2 text-gray-600 dark:text-neutral-400"
                                >{{ junctionId }}</span
                            >
                        </div>
                        <div>
                            <span
                                class="font-semibold text-gray-700 dark:text-neutral-300"
                                >Number:</span
                            >
                            <span
                                class="ml-2 text-gray-600 dark:text-neutral-400"
                                >{{ junctionNumber }}</span
                            >
                        </div>
                        <div class="col-span-2">
                            <span
                                class="font-semibold text-gray-700 dark:text-neutral-300"
                                >Type:</span
                            >
                            <span
                                class="ml-2 text-gray-600 dark:text-neutral-400"
                                >{{ junctionType }}</span
                            >
                        </div>
                    </div>
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
