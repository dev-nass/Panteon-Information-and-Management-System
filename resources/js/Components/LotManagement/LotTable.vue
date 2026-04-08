<script setup>
import { ref, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";
import TableHeader from "@/Components/Table/TableHeader.vue";
import TableData from "@/Components/Table/TableData.vue";
import LotEditModal from "@/Components/Map/LotEditModal.vue";

const props = defineProps({
    clusterId: Number,
    search: String,
});

const lots = ref([]);
const loading = ref(false);
const editingRow = ref(null);
const showLotModal = ref(false);
const editingItem = ref(null);

const fetchLots = async () => {
    if (!props.clusterId) {
        lots.value = [];
        return;
    }
    
    loading.value = true;
    try {
        const response = await fetch(
            route("clerk.lot_management.lots", props.clusterId)
        );
        const data = await response.json();
        lots.value = data;
    } catch (error) {
        console.error("Error fetching lots:", error);
        lots.value = [];
    } finally {
        loading.value = false;
    }
};

watch(() => props.clusterId, fetchLots, { immediate: true });

const filteredLots = computed(() =>
    lots.value.filter(
        (l) =>
            l.column.toLowerCase().includes(props.search.toLowerCase()) ||
            l.row.toLowerCase().includes(props.search.toLowerCase()) ||
            `${l.column}${l.row}`
                .toLowerCase()
                .includes(props.search.toLowerCase())
    )
);

const startEditRow = (lot) => {
    editingRow.value = { ...lot };
};

const cancelEditRow = () => {
    editingRow.value = null;
};

const saveEditRow = () => {
    router.put(
        route("clerk.lot_management.update.lot", editingRow.value.id),
        editingRow.value,
        {
            onSuccess: () => {
                cancelEditRow();
                fetchLots();
            },
        }
    );
};

const openLotCoordinateModal = (lot) => {
    editingItem.value = lot;
    showLotModal.value = true;
};

const handleLotCoordinatesSet = (coords) => {
    router.put(
        route("clerk.lot_management.update.lot", editingItem.value.id),
        {
            column: editingItem.value.column,
            row: editingItem.value.row,
            coordinates: JSON.stringify(coords),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showLotModal.value = false;
                editingItem.value = null;
                fetchLots();
            },
        }
    );
};

const deleteLot = (lotId) => {
    if (confirm("Are you sure you want to delete this lot?")) {
        router.delete(route("clerk.lot_management.delete.lot", lotId), {
            onSuccess: () => fetchLots(),
        });
    }
};

const redirectToClerkMap = (id) => {
    router.visit(route("clerk.map.index"), {
        data: { id },
        onSuccess: () => {
            setTimeout(() => {
                if (window.fetchLot) window.fetchLot(id);
            }, 500);
        },
    });
};

const redirectToClerkBurialRecordShow = (lotId) => {
    router.visit(route("clerk.lot_management.show", { lot: lotId }));
};
</script>

<template>
    <div v-if="loading" class="p-8">
        <div class="animate-pulse space-y-4">
            <div v-for="i in 5" :key="i" class="flex gap-4">
                <div class="h-12 bg-gray-200 dark:bg-neutral-700 rounded w-16"></div>
                <div class="h-12 bg-gray-200 dark:bg-neutral-700 rounded w-24"></div>
                <div class="h-12 bg-gray-200 dark:bg-neutral-700 rounded w-24"></div>
                <div class="h-12 bg-gray-200 dark:bg-neutral-700 rounded w-32"></div>
                <div class="h-12 bg-gray-200 dark:bg-neutral-700 rounded flex-1"></div>
                <div class="h-12 bg-gray-200 dark:bg-neutral-700 rounded w-32"></div>
                <div class="h-12 bg-gray-200 dark:bg-neutral-700 rounded w-32"></div>
            </div>
        </div>
    </div>
    <table
        v-else
        class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700"
    >
        <thead class="bg-gray-50 dark:bg-neutral-800">
            <tr>
                <TableHeader>ID</TableHeader>
                <TableHeader>Column</TableHeader>
                <TableHeader>Row</TableHeader>
                <TableHeader>Status</TableHeader>
                <TableHeader>Burial Record</TableHeader>
                <TableHeader>Coordinate</TableHeader>
                <TableHeader>Actions</TableHeader>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
            <tr
                v-for="lot in filteredLots"
                :key="lot.id"
                class="transition bg-white dark:bg-neutral-800 hover:bg-gray-50 dark:hover:bg-neutral-700"
            >
                <TableData>{{ lot.id }}</TableData>
                <TableData>
                    <input
                        v-if="editingRow?.id === lot.id"
                        v-model="editingRow.column"
                        @click.stop
                        class="w-full px-3 py-2 bg-white dark:bg-neutral-900 border border-gray-300 dark:border-neutral-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900 dark:text-gray-100"
                    />
                    <span v-else>{{ lot.column }}</span>
                </TableData>
                <TableData>
                    <input
                        v-if="editingRow?.id === lot.id"
                        v-model="editingRow.row"
                        @click.stop
                        class="w-full px-3 py-2 bg-white dark:bg-neutral-900 border border-gray-300 dark:border-neutral-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900 dark:text-gray-100"
                    />
                    <span v-else>{{ lot.row }}</span>
                </TableData>
                <TableData
                    :isHighlighted="true"
                    :highlightColor="lot.status === 'available' ? 'green' : 'red'"
                >
                    {{ lot.status }}
                </TableData>
                <TableData>
                    <button
                        v-if="lot.status == 'occupied'"
                        @click.stop="redirectToClerkBurialRecordShow(lot.id)"
                        class="px-3 py-1 text-sm rounded-lg bg-green-500/10 text-green-400 hover:bg-green-500/20 border border-green-500/30 transition-all duration-200"
                    >
                        View Details
                    </button>
                    <span v-else class="text-neutral-600">———————</span>
                </TableData>
                <TableData>
                    <button
                        v-if="editingRow?.id === lot.id"
                        @click.stop="openLotCoordinateModal(lot)"
                        class="px-3 py-1 text-sm rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 border border-blue-500/30 transition-all duration-200"
                    >
                        {{ lot.isLot_mapped ? "Edit" : "Add" }}
                    </button>
                    <button
                        v-else-if="lot.isLot_mapped"
                        @click.stop="redirectToClerkMap(lot.id)"
                        class="px-3 py-1 text-sm rounded-lg bg-green-500/10 text-green-400 hover:bg-green-500/20 border border-green-500/30 transition-all duration-200"
                    >
                        View on Map
                    </button>
                    <span v-else class="text-gray-500 dark:text-gray-600"
                        >Not Mapped</span
                    >
                </TableData>
                <TableData>
                    <div v-if="editingRow?.id === lot.id" class="flex gap-2">
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
                            @click.stop="startEditRow(lot)"
                            class="px-3 py-1 text-sm rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 border border-blue-500/30 transition-all duration-200"
                        >
                            Edit
                        </button>
                        <button
                            @click.stop="deleteLot(lot.id)"
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
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
                                <path d="M3 6h18" />
                                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                            </svg>
                        </button>
                    </div>
                </TableData>
            </tr>
        </tbody>
    </table>

    <LotEditModal
        v-if="showLotModal"
        :cluster-id="clusterId"
        :existing-coordinates="editingItem?.coordinates"
        @coordinates-set="handleLotCoordinatesSet"
        @close="showLotModal = false"
    />
</template>
