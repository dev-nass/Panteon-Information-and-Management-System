<script setup>
import { ref, nextTick, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import { useToast } from "vue-toast-notification";
import TableHeader from "@/Components/Table/TableHeader.vue";
import TableData from "@/Components/Table/TableData.vue";
import PhaseEditModal from "@/Components/Map/PhaseEditModal.vue";

const props = defineProps({
    phases: Array,
    search: String,
    userRole: String,
    roleRoute: String, // contain route for "view on map"
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
        route("admin.lot_management.update.phase", editingRow.value.id),
        editingRow.value,
        {
            onSuccess: () => {
                cancelEditRow();
                toast.success(`Phase "${phaseName}" updated successfully!`, {
                    duration: 3000,
                });
            },
        },
    );
};

const openPhaseCoordinateModal = (phase) => {
    editingItem.value = phase;
    showPhaseModal.value = true;
};

const handlePhaseCoordinatesSet = (coords) => {
    router.put(
        route("admin.lot_management.update.phase", editingItem.value.id),
        {
            name: editingItem.value.name,
            coordinates: JSON.stringify(coords),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showPhaseModal.value = false;
                cancelEditRow();
                toast.success(
                    `Phase "${editingItem.value.name}" updated coordinates successfully!`,
                    {
                        duration: 3000,
                    },
                );
            },
        },
    );
};

const phaseToDelete = ref(null);

const openDeletePhaseModal = (phase) => {
    phaseToDelete.value = phase;
    nextTick(() => {
        const el = document.getElementById("delete-phase-modal");
        try {
            if (el && window.HSOverlay) {
                window.HSOverlay.open(el);
            } else if (typeof HSOverlay !== "undefined") {
                HSOverlay.open("#delete-phase-modal");
            }
        } catch {
            if (typeof HSOverlay !== "undefined") HSOverlay.open("#delete-phase-modal");
        }
    });
};

const confirmDeletePhase = () => {
    if (!phaseToDelete.value) return;
    const phaseName = phaseToDelete.value.name;
    router.delete(route("admin.lot_management.delete.phase", phaseToDelete.value.id), {
        onSuccess: () => {
            HSOverlay.close("#delete-phase-modal");
            phaseToDelete.value = null;
            toast.success(`Phase "${phaseName}" deleted successfully!`, {
                duration: 3000,
            });
        },
        onError: () => {
            toast.error("Failed to delete phase.");
        },
    });
};

const cancelDeletePhase = () => {
    HSOverlay.close("#delete-phase-modal");
    phaseToDelete.value = null;
};

const redirectToMap = (id) => {
    router.visit(route(props.roleRoute), {
        data: { id },
        onSuccess: () => {
            setTimeout(() => {
                if (window.fetchPhase) window.fetchPhase(id);
            }, 500);
        },
    });
};

onMounted(() => {
    // Ensure HSOverlay registers Teleport'd delete modal (when PhaseTable mounts at page load it should, but also for consistency)
    nextTick(() => {
        try {
            if (window.HSOverlay && window.HSOverlay.autoInit) window.HSOverlay.autoInit();
            else if (typeof HSOverlay !== "undefined" && HSOverlay.autoInit) HSOverlay.autoInit();
        } catch {}
    });
});
</script>

<template>
    <div class="overflow-x-auto">
        <table
            class="min-w-[880px] lg:min-w-full divide-y divide-gray-200 dark:divide-neutral-700"
        >
            <thead class="bg-gray-50 dark:bg-neutral-800">
                <tr>
                    <TableHeader>Name</TableHeader>
                    <TableHeader>Total Clusters</TableHeader>
                    <TableHeader>Coordinate</TableHeader>
                    <TableHeader v-if="userRole === 'admin'"
                        >Actions</TableHeader
                    >
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                <tr
                    v-for="phase in phases"
                    :key="phase.id"
                    @click="emit('select-phase', phase)"
                    class="transition cursor-pointer bg-white dark:bg-neutral-800 hover:bg-gray-50 dark:hover:bg-neutral-700"
                >
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
                            @click.stop="redirectToMap(phase.id)"
                            class="px-3 py-1 text-sm rounded-lg bg-green-500/10 text-green-400 hover:bg-green-500/20 border border-green-500/30 transition-all duration-200"
                        >
                            View on Map
                        </button>
                        <span v-else class="text-gray-500 dark:text-gray-600"
                            >Not Mapped</span
                        >
                    </TableData>
                    <TableData>
                        <div
                            v-if="editingRow?.id === phase.id"
                            class="flex gap-2"
                        >
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
                        <div
                            v-else-if="userRole === 'admin'"
                            class="flex gap-2"
                        >
                            <button
                                @click.stop="startEditRow(phase)"
                                class="px-3 py-1 text-sm rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 border border-blue-500/30 transition-all duration-200"
                            >
                                Edit
                            </button>
                            <button
                                @click.stop="openDeletePhaseModal(phase)"
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
    </div>

    <PhaseEditModal
        v-if="showPhaseModal"
        :existing-coordinates="editingItem?.coordinates"
        @coordinates-set="handlePhaseCoordinatesSet"
        @close="showPhaseModal = false"
    />

    <Teleport to="body">
        <div
            id="delete-phase-modal"
            class="hs-overlay hidden size-full fixed top-0 start-0 z-2000 overflow-x-hidden overflow-y-auto bg-black/40 backdrop-blur-sm"
            role="dialog"
            tabindex="-1"
            aria-labelledby="delete-phase-modal-label"
        >
            <div
                class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto"
            >
                <div
                    class="relative w-full max-h-full flex flex-col bg-white/70 dark:bg-neutral-900/70 backdrop-blur-xl border border-white/20 dark:border-white/10 rounded-2xl shadow-lg shadow-gray-200/50 dark:shadow-black/50"
                >
                    <div class="absolute top-3 end-3">
                        <button
                            type="button"
                            class="size-8 inline-flex justify-center items-center rounded-full bg-white/40 dark:bg-neutral-800/40 backdrop-blur-md border border-white/20 dark:border-white/10 text-gray-700 dark:text-neutral-200 hover:bg-white/60 dark:hover:bg-neutral-700/60 transition"
                            @click="cancelDeletePhase"
                        >
                            <svg
                                class="size-4"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M18 6 6 18" />
                                <path d="m6 6 12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="p-10 flex flex-col items-center gap-y-4 text-center">
                        <div
                            class="flex items-center justify-center size-14 rounded-full bg-red-500/10 text-red-600 dark:text-red-400"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="60"
                                height="60"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M3 6h18" />
                                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                            </svg>
                        </div>

                        <h3
                            id="delete-phase-modal-label"
                            class="-mt-2 text-2xl font-bold text-red-600 dark:text-red-400"
                        >
                            Delete Phase
                        </h3>

                        <p class="text-gray-600 dark:text-neutral-300 max-w-sm">
                            Are you sure you want to delete
                            <span class="font-semibold text-gray-900 dark:text-white">
                                PHASE {{ phaseToDelete?.name }}
                            </span>
                            ? This will also delete all clusters and lots within
                            it. This action cannot be undone.
                        </p>
                    </div>

                    <div class="flex border-t border-white/20 dark:border-white/10">
                        <button
                            type="button"
                            class="w-full py-3 text-sm font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-500/10 transition"
                            @click="cancelDeletePhase"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            class="w-full py-3 text-sm font-semibold text-red-500 hover:bg-red-500/10 transition"
                            @click="confirmDeletePhase"
                        >
                            Delete Phase
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
