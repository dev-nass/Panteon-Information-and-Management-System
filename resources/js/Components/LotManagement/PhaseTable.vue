<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { useToast } from "vue-toast-notification";
import TableHeader from "@/Components/Table/TableHeader.vue";
import TableData from "@/Components/Table/TableData.vue";
import PhaseEditModal from "@/Components/Map/PhaseEditModal.vue";

const props = defineProps({
    phases: Array,
    search: String,
});

const emit = defineEmits(["select-phase"]);

const toast = useToast();
const editingRow = ref(null);
const showPhaseModal = ref(false);
const editingItem = ref(null);

const startEditRow = (phase) => {
    editingRow.value = { ...phase };
};

const cancelEditRow = () => {
    editingRow.value = null;
};

const saveEditRow = () => {
    const phaseName = editingRow.value.name;
    router.put(
        route("clerk.lot_management.update.phase", editingRow.value.id),
        editingRow.value,
        {
            onSuccess: () => {
                cancelEditRow();
                toast.success(`Phase "${phaseName}" updated successfully!`, {
                    duration: 3000,
                });
            },
        }
    );
};

const openPhaseCoordinateModal = (phase) => {
    editingItem.value = phase;
    showPhaseModal.value = true;
};

const handlePhaseCoordinatesSet = (coords) => {
    router.put(
        route("clerk.lot_management.update.phase", editingItem.value.id),
        {
            name: editingItem.value.name,
            coordinates: JSON.stringify(coords),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showPhaseModal.value = false;
                editingItem.value = null;
            },
        }
    );
};

const deletePhase = (phaseId) => {
    if (
        confirm(
            "Are you sure you want to delete this phase? This will also delete all clusters and lots within it."
        )
    ) {
        router.delete(route("clerk.lot_management.delete.phase", phaseId));
    }
};

const redirectToClerkMap = (id) => {
    router.visit(route("clerk.map.index"), {
        data: { id },
        onSuccess: () => {
            setTimeout(() => {
                if (window.fetchPhase) window.fetchPhase(id);
            }, 500);
        },
    });
};
</script>

<template>
    <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
        <thead class="bg-gray-50 dark:bg-neutral-800">
            <tr>
                <TableHeader>ID</TableHeader>
                <TableHeader>Name</TableHeader>
                <TableHeader>Total Clusters</TableHeader>
                <TableHeader>Coordinate</TableHeader>
                <TableHeader>Actions</TableHeader>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
            <tr
                v-for="phase in phases"
                :key="phase.id"
                @click="emit('select-phase', phase)"
                class="transition cursor-pointer bg-white dark:bg-neutral-800 hover:bg-gray-50 dark:hover:bg-neutral-700"
            >
                <TableData>{{ phase.id }}</TableData>
                <TableData>
                    <input
                        v-if="editingRow?.id === phase.id"
                        v-model="editingRow.name"
                        @click.stop
                        class="w-full px-3 py-2 bg-white dark:bg-neutral-900 border border-gray-300 dark:border-neutral-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900 dark:text-gray-100"
                    />
                    <span v-else>{{ phase.name }}</span>
                </TableData>
                <TableData>{{ phase.total_clusters }}</TableData>
                <TableData>
                    <button
                        v-if="editingRow?.id === phase.id"
                        @click.stop="openPhaseCoordinateModal(phase)"
                        class="px-3 py-1 text-sm rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 border border-blue-500/30 transition-all duration-200"
                    >
                        {{ phase.isPhase_mapped ? "Edit" : "Add" }}
                    </button>
                    <button
                        v-else-if="phase.isPhase_mapped"
                        @click.stop="redirectToClerkMap(phase.id)"
                        class="px-3 py-1 text-sm rounded-lg bg-green-500/10 text-green-400 hover:bg-green-500/20 border border-green-500/30 transition-all duration-200"
                    >
                        View on Map
                    </button>
                    <span v-else class="text-gray-500 dark:text-gray-600"
                        >Not Mapped</span
                    >
                </TableData>
                <TableData>
                    <div v-if="editingRow?.id === phase.id" class="flex gap-2">
                        <button
                            @click.stop="saveEditRow"
                            class="px-3 py-1 text-sm rounded-lg bg-green-500/10 text-green-400 hover:bg-green-500/20 border border-green-500/30 transition-all duration-200"
                        >
                            Save
                        </button>
                        <button
                            @click.stop="cancelEditRow"
                            class="px-3 py-1 text-sm rounded-lg bg-gray-500/10 text-gray-400 hover:bg-gray-500/20 border border-gray-500/30 transition-all duration-200"
                        >
                            Cancel
                        </button>
                    </div>
                    <div v-else class="flex gap-2">
                        <button
                            @click.stop="startEditRow(phase)"
                            class="px-3 py-1 text-sm rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 border border-blue-500/30 transition-all duration-200"
                        >
                            Edit
                        </button>
                        <button
                            @click.stop="deletePhase(phase.id)"
                            class="px-3 py-1 text-sm rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/30 transition-all duration-200"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="20"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M10 11v6" />
                                <path d="M14 11v6" />
                                <path
                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"
                                />
                                <path d="M3 6h18" />
                                <path
                                    d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"
                                />
                            </svg>
                        </button>
                    </div>
                </TableData>
            </tr>
        </tbody>
    </table>

    <PhaseEditModal
        v-if="showPhaseModal"
        :existing-coordinates="editingItem?.coordinates"
        @coordinates-set="handlePhaseCoordinatesSet"
        @close="showPhaseModal = false"
    />
</template>
