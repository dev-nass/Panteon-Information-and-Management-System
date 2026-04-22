<script setup>
import { ref, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { useToast } from "vue-toast-notification";
import TableHeader from "@/Components/Table/TableHeader.vue";
import TableData from "@/Components/Table/TableData.vue";
import ClusterEditModal from "@/Components/Map/ClusterEditModal.vue";

const props = defineProps({
    phaseId: Number,
    search: String,
});

const emit = defineEmits(["select-cluster"]);

const toast = useToast();
const clusters = ref([]);
const loading = ref(false);
const editingRow = ref(null);
const showClusterModal = ref(false);
const editingItem = ref(null);
const currentPage = ref(1);
const perPage = ref(10);

const paginatedClusters = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    const end = start + perPage.value;
    return filteredClusters.value.slice(start, end);
});

const totalPages = computed(() => {
    return Math.ceil(filteredClusters.value.length / perPage.value);
});

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

const fetchClusters = async () => {
    if (!props.phaseId) {
        clusters.value = [];
        return;
    }

    loading.value = true;
    try {
        const response = await fetch(
            route("api.lot.management.clusters", props.phaseId),
        );
        const data = await response.json();
        clusters.value = data;
    } catch (error) {
        console.error("Error fetching clusters:", error);
        clusters.value = [];
    } finally {
        loading.value = false;
    }
};

watch(() => props.phaseId, fetchClusters, { immediate: true });

// Reset to page 1 when search changes
watch(
    () => props.search,
    () => {
        currentPage.value = 1;
    },
);

const filteredClusters = computed(() =>
    clusters.value.filter((c) =>
        c.name.toLowerCase().includes(props.search.toLowerCase()),
    ),
);

const startEditRow = (cluster) => {
    editingRow.value = { ...cluster };
};

const cancelEditRow = () => {
    editingRow.value = null;
};

const saveEditRow = () => {
    const clusterName = editingRow.value.name;
    router.put(
        route("clerk.lot_management.update.cluster", editingRow.value.id),
        editingRow.value,
        {
            onSuccess: () => {
                cancelEditRow();
                fetchClusters();
                toast.success(
                    `Cluster "${clusterName}" updated successfully!`,
                    {
                        duration: 3000,
                    },
                );
            },
        },
    );
};

const openClusterCoordinateModal = (cluster) => {
    editingItem.value = cluster;
    showClusterModal.value = true;
};

const handleClusterCoordinatesSet = (coords) => {
    router.put(
        route("clerk.lot_management.update.cluster", editingItem.value.id),
        {
            name: editingItem.value.name,
            type: editingItem.value.type,
            coordinates: JSON.stringify(coords),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showClusterModal.value = false;
                editingItem.value = null;
                fetchClusters();
            },
        },
    );
};

const deleteCluster = (clusterId) => {
    if (
        confirm(
            "Are you sure you want to delete this cluster? This will also delete all lots within it.",
        )
    ) {
        router.delete(route("clerk.lot_management.delete.cluster", clusterId), {
            onSuccess: () => fetchClusters(),
        });
    }
};

const redirectToClerkMap = (id) => {
    router.visit(route("clerk.map.index"), {
        data: { id },
        onSuccess: () => {
            setTimeout(() => {
                if (window.fetchCluster) window.fetchCluster(id);
            }, 500);
        },
    });
};
</script>

<template>
    <div v-if="loading" class="p-8">
        <div class="animate-pulse space-y-4">
            <div v-for="i in 5" :key="i" class="flex gap-4">
                <div
                    class="h-12 bg-gray-200 dark:bg-neutral-700 rounded w-16"
                ></div>
                <div
                    class="h-12 bg-gray-200 dark:bg-neutral-700 rounded flex-1"
                ></div>
                <div
                    class="h-12 bg-gray-200 dark:bg-neutral-700 rounded w-24"
                ></div>
                <div
                    class="h-12 bg-gray-200 dark:bg-neutral-700 rounded w-24"
                ></div>
                <div
                    class="h-12 bg-gray-200 dark:bg-neutral-700 rounded w-32"
                ></div>
                <div
                    class="h-12 bg-gray-200 dark:bg-neutral-700 rounded w-32"
                ></div>
                <div
                    class="h-12 bg-gray-200 dark:bg-neutral-700 rounded w-32"
                ></div>
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
                <TableHeader>Name</TableHeader>
                <TableHeader>Occupants</TableHeader>
                <TableHeader>Total Lots</TableHeader>
                <TableHeader>Type</TableHeader>
                <TableHeader>Coordinate</TableHeader>
                <TableHeader>Actions</TableHeader>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
            <tr
                v-for="cluster in paginatedClusters"
                :key="cluster.id"
                @click="emit('select-cluster', cluster)"
                class="transition cursor-pointer bg-white dark:bg-neutral-800 hover:bg-gray-50 dark:hover:bg-neutral-700"
            >
                <TableData>{{ cluster.id }}</TableData>
                <TableData>
                    <input
                        v-if="editingRow?.id === cluster.id"
                        v-model="editingRow.name"
                        @click.stop
                        class="w-full px-3 py-2 bg-white dark:bg-neutral-900 border border-gray-300 dark:border-neutral-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900 dark:text-gray-100"
                    />
                    <span v-else>{{ cluster.name }}</span>
                </TableData>
                <TableData>{{ cluster.occupants }}</TableData>
                <TableData>
                    <span>{{ cluster.total_lots }} / </span>
                    <input
                        v-if="editingRow?.id === cluster.id"
                        v-model.number="editingRow.total_capacity"
                        type="number"
                        min="1"
                        @click.stop
                        class="w-full px-3 py-2 bg-white dark:bg-neutral-900 border border-gray-300 dark:border-neutral-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900 dark:text-gray-100"
                    />
                    <span v-else>{{ cluster.total_capacity }}</span>
                </TableData>
                <TableData>
                    <input
                        v-if="editingRow?.id === cluster.id"
                        v-model="editingRow.type"
                        @click.stop
                        class="w-full px-3 py-2 bg-white dark:bg-neutral-900 border border-gray-300 dark:border-neutral-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900 dark:text-gray-100"
                    />
                    <span v-else>{{ cluster.type }}</span>
                </TableData>
                <TableData>
                    <button
                        v-if="editingRow?.id === cluster.id"
                        @click.stop="openClusterCoordinateModal(cluster)"
                        class="px-3 py-1 text-sm rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 border border-blue-500/30 transition-all duration-200"
                    >
                        {{ cluster.isCluster_mapped ? "Edit" : "Add" }}
                    </button>
                    <button
                        v-else-if="cluster.isCluster_mapped"
                        @click.stop="redirectToClerkMap(cluster.id)"
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
                        v-if="editingRow?.id === cluster.id"
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
                    <div v-else class="flex gap-2">
                        <button
                            @click.stop="startEditRow(cluster)"
                            class="px-3 py-1 text-sm rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 border border-blue-500/30 transition-all duration-200"
                        >
                            Edit
                        </button>
                        <button
                            @click.stop="deleteCluster(cluster.id)"
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

    <!-- Pagination -->
    <div
        v-if="!loading && filteredClusters.length > 0"
        class="px-6 py-4 border-t border-gray-200 dark:border-neutral-700 flex items-center justify-between"
    >
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Showing {{ (currentPage - 1) * perPage + 1 }} to
            {{ Math.min(currentPage * perPage, filteredClusters.length) }} of
            {{ filteredClusters.length }} clusters
        </div>
        <div class="flex gap-2">
            <button
                @click="goToPage(currentPage - 1)"
                :disabled="currentPage === 1"
                class="px-3 py-1 text-sm rounded-lg border transition-all duration-200"
                :class="
                    currentPage === 1
                        ? 'bg-gray-100 dark:bg-neutral-800 text-gray-400 dark:text-gray-600 cursor-not-allowed'
                        : 'bg-white dark:bg-neutral-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-neutral-700 hover:bg-gray-50 dark:hover:bg-neutral-700'
                "
            >
                Previous
            </button>
            <button
                v-for="page in totalPages"
                :key="page"
                @click="goToPage(page)"
                class="px-3 py-1 text-sm rounded-lg border transition-all duration-200"
                :class="
                    currentPage === page
                        ? 'bg-green-500 text-white border-green-500'
                        : 'bg-white dark:bg-neutral-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-neutral-700 hover:bg-gray-50 dark:hover:bg-neutral-700'
                "
            >
                {{ page }}
            </button>
            <button
                @click="goToPage(currentPage + 1)"
                :disabled="currentPage === totalPages"
                class="px-3 py-1 text-sm rounded-lg border transition-all duration-200"
                :class="
                    currentPage === totalPages
                        ? 'bg-gray-100 dark:bg-neutral-800 text-gray-400 dark:text-gray-600 cursor-not-allowed'
                        : 'bg-white dark:bg-neutral-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-neutral-700 hover:bg-gray-50 dark:hover:bg-neutral-700'
                "
            >
                Next
            </button>
        </div>
    </div>

    <ClusterEditModal
        v-if="showClusterModal"
        :phase-id="phaseId"
        :existing-coordinates="editingItem?.coordinates"
        @coordinates-set="handleClusterCoordinatesSet"
        @close="showClusterModal = false"
    />
</template>
