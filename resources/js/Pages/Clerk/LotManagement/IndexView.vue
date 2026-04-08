<script setup>
import { ref, computed, watch, onBeforeUnmount } from "vue";
import { router } from "@inertiajs/vue3";

import Input from "@/Components/Form/Input.vue";
import Button from "@/Components/Form/Button.vue";
import Dashboard from "@/Layouts/Dashboard.vue";
import TableHeader from "@/Components/Table/TableHeader.vue";
import TableData from "@/Components/Table/TableData.vue";
import PhaseEditModal from "@/Components/Map/PhaseEditModal.vue";
import ClusterEditModal from "@/Components/Map/ClusterEditModal.vue";
import LotEditModal from "@/Components/Map/LotEditModal.vue";

import { useSearch } from "@/composables/map/search/useSearch";

// Props (you will pass these from backend)
const props = defineProps({
    phases: Array,
    clusters: Array,
    lots: Array,
});

// console.log(props.phases, props.clusters, props.lots);

const { fetchPhase, fetchCluster, fetchLot, clearSearch } = useSearch();

// =========================
// EDITING STATE
// =========================
const editingRow = ref(null);
const editingType = ref(null); // 'phase' | 'cluster' | 'lot'

// Modal states
const showPhaseModal = ref(false);
const showClusterModal = ref(false);
const showLotModal = ref(false);
const editingItem = ref(null);

// Local copies for editing
const localPhases = ref([...props.phases]);
const localClusters = ref([...props.clusters]);
const localLots = ref([...props.lots]);

// Watch for prop changes and update local copies
watch(
    () => props.phases,
    (newPhases) => {
        localPhases.value = [...newPhases];
    },
    { deep: true }
);

watch(
    () => props.clusters,
    (newClusters) => {
        localClusters.value = [...newClusters];
    },
    { deep: true }
);

watch(
    () => props.lots,
    (newLots) => {
        localLots.value = [...newLots];
    },
    { deep: true }
);

const startEditRow = (row, type) => {
    editingRow.value = { ...row };
    editingType.value = type;
};

const cancelEditRow = () => {
    editingRow.value = null;
    editingType.value = null;
};

const saveEditRow = () => {
    if (!editingRow.value || !editingType.value) return;

    const routes = {
        phase: "clerk.lot_management.update.phase",
        cluster: "clerk.lot_management.update.cluster",
        lot: "clerk.lot_management.update.lot",
    };

    router.put(
        route(routes[editingType.value], editingRow.value.id),
        editingRow.value,
        {
            onSuccess: () => {
                cancelEditRow();
            },
        }
    );
};

// =========================
// COORDINATE EDITING
// =========================
const openPhaseCoordinateModal = (phase) => {
    editingItem.value = phase;
    showPhaseModal.value = true;
};

const openClusterCoordinateModal = (cluster) => {
    editingItem.value = cluster;
    showClusterModal.value = true;
};

const openLotCoordinateModal = (lot) => {
    editingItem.value = lot;
    showLotModal.value = true;
};

const handlePhaseCoordinatesSet = (coords) => {
    if (editingItem.value) {
        router.put(
            route("clerk.lot_management.update.phase", editingItem.value.id),
            {
                name: editingItem.value.name,
                coordinates: JSON.stringify(coords),
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    closePhaseModal();
                },
            }
        );
    }
};

const handleClusterCoordinatesSet = (coords) => {
    if (editingItem.value) {
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
                    closeClusterModal();
                },
            }
        );
    }
};

const handleLotCoordinatesSet = (coords) => {
    if (editingItem.value) {
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
                    closeLotModal();
                },
            }
        );
    }
};

const closePhaseModal = () => {
    showPhaseModal.value = false;
    editingItem.value = null;
};

const closeClusterModal = () => {
    showClusterModal.value = false;
    editingItem.value = null;
};

const closeLotModal = () => {
    showLotModal.value = false;
    editingItem.value = null;
};

// =========================
// Search
// =========================
const search = ref("");

// Tabs
const activeTab = ref("phase"); // phase | cluster | lot

// Navigation state
const selectedPhase = ref(null);
const selectedCluster = ref(null);

// =========================
// VIEW ON TABLE HANDLERS
// =========================
const handleViewPhaseOnTable = (phaseId) => {
    const phase = localPhases.value.find((p) => p.id === phaseId);
    if (phase) {
        selectedPhase.value = phase;
        selectedCluster.value = null;
        activeTab.value = "phase";
    }
};

const handleViewClusterOnTable = (clusterId) => {
    const cluster = localClusters.value.find((c) => c.id === clusterId);
    if (cluster) {
        const phase = localPhases.value.find((p) => p.id === cluster.phase_id);
        if (phase) {
            selectedPhase.value = phase;
            selectedCluster.value = cluster;
            activeTab.value = "cluster";
        }
    }
};

const handleViewLotOnTable = (lotId) => {
    const lot = localLots.value.find((l) => l.id === lotId);
    if (lot) {
        const cluster = localClusters.value.find(
            (c) => c.id === lot.cluster_id
        );
        if (cluster) {
            const phase = localPhases.value.find(
                (p) => p.id === cluster.phase_id
            );
            if (phase) {
                selectedPhase.value = phase;
                selectedCluster.value = cluster;
                activeTab.value = "lot";
                search.value = `${lot.column}${lot.row}`;
            }
        }
    }
};

window.handleViewPhaseOnTable = handleViewPhaseOnTable;
window.handleViewClusterOnTable = handleViewClusterOnTable;
window.handleViewLotOnTable = handleViewLotOnTable;

// =========================
// FILTERED DATA WHEN ON SEARCH
// =========================
const filteredPhases = computed(() =>
    localPhases.value.filter((p) =>
        p.name.toLowerCase().includes(search.value.toLowerCase())
    )
);

const filteredClusters = computed(() => {
    if (!selectedPhase.value) return [];

    return localClusters.value.filter(
        (c) =>
            c.phase_id === selectedPhase.value.id &&
            c.name.toLowerCase().includes(search.value.toLowerCase())
    );
});

const filteredLots = computed(() => {
    if (!selectedCluster.value) return [];

    return localLots.value.filter(
        (l) =>
            l.cluster_id === selectedCluster.value.id &&
            (l.column.toLowerCase().includes(search.value.toLowerCase()) ||
                l.row.toLowerCase().includes(search.value.toLowerCase()) ||
                `${l.column}${l.row}`.toLowerCase().includes(search.value.toLowerCase()))
    );
});

// =========================
// CONTEXT LABELS
// =========================
const currentPhaseName = computed(() => selectedPhase.value?.name || null);
const currentClusterName = computed(() => selectedCluster.value?.name || null);

// =========================
// NAVIGATION
// =========================
const goToClusters = (phase) => {
    selectedPhase.value = phase;
    selectedCluster.value = null;
    activeTab.value = "cluster";
};

const goToLots = (cluster) => {
    selectedCluster.value = cluster;
    activeTab.value = "lot";
};

const goBack = () => {
    if (activeTab.value === "lot") {
        activeTab.value = "cluster";
        selectedCluster.value = null;
    } else if (activeTab.value === "cluster") {
        activeTab.value = "phase";
        selectedPhase.value = null;
    }
};

/**
* Description: Get the burial ID of the record being showng and pass
               it on the fetchClusterByBurialId()
    @param type of navigation ex. 'phase', 'cluster', 'lot'
*/
const redirectToClerkMap = (id, type) => {
    router.visit(route("clerk.map.index"), {
        data: { id },
        onSuccess: () => {
            setTimeout(() => {
                if (type === "phase") fetchPhase(id);
                else if (type === "cluster") fetchCluster(id);
                else if (type === "lot") fetchLot(id);
                else console.warn("Provide a valid lot type to redirect");
            }, 500);
        },
    });
};

const redirectToClerkBurialRecordShow = (lotId) => {
    router.visit(route("clerk.lot_management.show", { lot: lotId }));
};

const goToCreate = () => {
    router.visit(route("clerk.lot_management.create"));
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

const deleteCluster = (clusterId) => {
    if (
        confirm(
            "Are you sure you want to delete this cluster? This will also delete all lots within it."
        )
    ) {
        router.delete(route("clerk.lot_management.delete.cluster", clusterId));
    }
};

const deleteLot = (lotId) => {
    if (confirm("Are you sure you want to delete this lot?")) {
        router.delete(route("clerk.lot_management.delete.lot", lotId));
    }
};

onBeforeUnmount(() => {
    clearSearch();
});

defineOptions({
    layout: Dashboard,
});
</script>

<template>
    <div class="max-w-340 px-4 py-10 mx-auto">
        <div
            class="bg-white dark:bg-neutral-800 rounded-xl shadow overflow-hidden"
        >
            <!-- HEADER -->
            <div
                class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-b border-gray-200 dark:border-neutral-700"
            >
                <!-- Search -->
                <Input placeholder="Search..." v-model="search" />

                <!-- Tabs -->
                <div
                    class="flex gap-2 bg-gray-100 dark:bg-neutral-900 p-1 rounded-xl"
                >
                    <button
                        v-for="tab in ['phase', 'cluster', 'lot']"
                        :key="tab"
                        @click="activeTab = tab"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition"
                        :class="
                            activeTab === tab
                                ? 'bg-green-500/20 text-green-400'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-green-500/10'
                        "
                    >
                        {{ tab.charAt(0).toUpperCase() + tab.slice(1) }}
                    </button>
                </div>

                <!-- ACTION BUTTONS -->
                <div class="flex gap-2">
                    <Button v-if="activeTab !== 'phase'" @click="goBack">
                        Back
                    </Button>

                    <Button
                        @click="goToCreate"
                        class="bg-green-500/10 text-green-400 hover:bg-green-500/20"
                    >
                        Create
                    </Button>
                </div>
            </div>

            <!-- CONTEXT INDICATOR -->
            <div
                class="px-6 py-3 bg-gray-50 dark:bg-neutral-800 border-b border-gray-200 dark:border-neutral-700 flex items-center gap-2 text-sm"
            >
                <span
                    class="px-3 py-1 rounded-lg"
                    :class="
                        activeTab === 'phase'
                            ? 'bg-green-500/20 text-green-400'
                            : 'text-gray-500 dark:text-gray-400'
                    "
                >
                    Phase
                </span>

                <span
                    v-if="currentPhaseName"
                    class="px-3 py-1 rounded-lg bg-green-500/10 text-green-400"
                >
                    {{ currentPhaseName }}
                </span>

                <span v-if="currentPhaseName" class="text-gray-400">→</span>

                <span v-if="currentPhaseName" class="text-gray-400"
                    >Cluster</span
                >

                <span
                    v-if="currentClusterName"
                    class="px-3 py-1 rounded-lg bg-green-500/10 text-green-400"
                >
                    {{ currentClusterName }}
                </span>

                <span v-if="currentClusterName" class="text-gray-400">→</span>

                <span v-if="currentClusterName" class="text-gray-400">Lot</span>
            </div>

            <!-- TABLE -->
            <table
                class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700"
            >
                <!-- HEADERS -->
                <thead class="bg-gray-50 dark:bg-neutral-800">
                    <tr v-if="activeTab === 'phase'">
                        <TableHeader>ID</TableHeader>
                        <TableHeader>Name</TableHeader>
                        <TableHeader>Total Clusters</TableHeader>
                        <TableHeader>Coordinate</TableHeader>
                        <TableHeader>Actions</TableHeader>
                    </tr>

                    <tr v-else-if="activeTab === 'cluster'">
                        <TableHeader>ID</TableHeader>
                        <TableHeader>Name</TableHeader>
                        <TableHeader>Occupants</TableHeader>
                        <TableHeader>Total Lots</TableHeader>
                        <TableHeader>Type</TableHeader>
                        <TableHeader>Coordinate</TableHeader>
                        <TableHeader>Actions</TableHeader>
                    </tr>

                    <tr v-else>
                        <TableHeader>ID</TableHeader>
                        <TableHeader>Column</TableHeader>
                        <TableHeader>Row</TableHeader>
                        <TableHeader>Status</TableHeader>
                        <TableHeader>Burial Record</TableHeader>
                        <TableHeader>Coordinate</TableHeader>
                        <TableHeader>Actions</TableHeader>
                    </tr>
                </thead>

                <!-- BODY -->
                <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                    <!-- PHASE -->
                    <tr
                        v-if="activeTab === 'phase'"
                        v-for="phase in filteredPhases"
                        :key="phase.id"
                        @click="goToClusters(phase)"
                        class="transition cursor-pointer"
                        :class="[
                            'bg-white dark:bg-neutral-800 hover:bg-gray-50 dark:hover:bg-neutral-700',
                            selectedPhase?.id === phase.id
                                ? 'bg-green-500/10 dark:bg-green-500/20'
                                : '',
                        ]"
                    >
                        <TableData> {{ phase.id }} </TableData>
                        <TableData>
                            <input
                                v-if="editingRow?.id === phase.id"
                                v-model="editingRow.name"
                                @click.stop
                                class="w-full px-3 py-2 bg-white dark:bg-neutral-900 border border-gray-300 dark:border-neutral-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900 dark:text-gray-100"
                            />
                            <span v-else>{{ phase.name }}</span>
                        </TableData>

                        <TableData> {{ phase.total_clusters }} </TableData>
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
                                @click.stop="
                                    redirectToClerkMap(phase.id, 'phase')
                                "
                                class="px-3 py-1 text-sm rounded-lg bg-green-500/10 text-green-400 hover:bg-green-500/20 border border-green-500/30 transition-all duration-200"
                            >
                                View on Map
                            </button>
                            <span
                                v-else
                                class="text-gray-500 dark:text-gray-600"
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
                            <div v-else class="flex gap-2">
                                <button
                                    @click.stop="startEditRow(phase, 'phase')"
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
                                        class="lucide lucide-trash2-icon lucide-trash-2"
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

                    <!-- CLUSTER -->
                    <tr
                        v-else-if="activeTab === 'cluster'"
                        v-for="cluster in filteredClusters"
                        :key="cluster.id"
                        @click="goToLots(cluster)"
                        class="transition cursor-pointer"
                        :class="[
                            'bg-white dark:bg-neutral-800 hover:bg-gray-50 dark:hover:bg-neutral-700',
                            selectedCluster?.id === cluster.id
                                ? 'bg-green-500/10 dark:bg-green-500/20'
                                : '',
                        ]"
                    >
                        <TableData> {{ cluster.id }} </TableData>
                        <TableData>
                            <input
                                v-if="editingRow?.id === cluster.id"
                                v-model="editingRow.name"
                                @click.stop
                                class="w-full px-3 py-2 bg-white dark:bg-neutral-900 border border-gray-300 dark:border-neutral-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900 dark:text-gray-100"
                            />
                            <span v-else>{{ cluster.name }}</span>
                        </TableData>
                        <TableData> {{ cluster.occupants }} </TableData>
                        <TableData>
                            {{ cluster.total_lots }}
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
                                @click.stop="
                                    openClusterCoordinateModal(cluster)
                                "
                                class="px-3 py-1 text-sm rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 border border-blue-500/30 transition-all duration-200"
                            >
                                {{ cluster.isCluster_mapped ? "Edit" : "Add" }}
                            </button>
                            <button
                                v-else-if="cluster.isCluster_mapped"
                                @click.stop="
                                    redirectToClerkMap(cluster.id, 'cluster')
                                "
                                class="px-3 py-1 text-sm rounded-lg bg-green-500/10 text-green-400 hover:bg-green-500/20 border border-green-500/30 transition-all duration-200"
                            >
                                View on Map
                            </button>
                            <span
                                v-else
                                class="text-gray-500 dark:text-gray-600"
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
                                    @click.stop="
                                        startEditRow(cluster, 'cluster')
                                    "
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
                                        class="lucide lucide-trash2-icon lucide-trash-2"
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

                    <!-- LOT -->
                    <tr
                        v-else
                        v-for="lot in filteredLots"
                        :key="lot.id"
                        class="transition bg-white dark:bg-neutral-800 hover:bg-gray-50 dark:hover:bg-neutral-700"
                    >
                        <TableData> {{ lot.id }} </TableData>
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
                            :highlightColor="
                                lot.status === 'available' ? 'green' : 'red'
                            "
                        >
                            {{ lot.status }}
                        </TableData>

                        <TableData>
                            <button
                                v-if="lot.status == 'occupied'"
                                @click.stop="
                                    redirectToClerkBurialRecordShow(lot.id)
                                "
                                class="px-3 py-1 text-sm rounded-lg bg-green-500/10 text-green-400 hover:bg-green-500/20 border border-green-500/30 transition-all duration-200"
                            >
                                View Details
                            </button>

                            <span v-else class="text-neutral-600">
                                ———————
                            </span>
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
                                @click.stop="redirectToClerkMap(lot.id, 'lot')"
                                class="px-3 py-1 text-sm rounded-lg bg-green-500/10 text-green-400 hover:bg-green-500/20 border border-green-500/30 transition-all duration-200"
                            >
                                View on Map
                            </button>
                            <span
                                v-else
                                class="text-gray-500 dark:text-gray-600"
                                >Not Mapped</span
                            >
                        </TableData>

                        <TableData>
                            <div
                                v-if="editingRow?.id === lot.id"
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
                                    @click.stop="startEditRow(lot, 'lot')"
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
                                        class="lucide lucide-trash2-icon lucide-trash-2"
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

        <!-- Phase Edit Modal -->
        <PhaseEditModal
            v-if="showPhaseModal"
            :existing-coordinates="editingItem?.coordinates"
            @coordinates-set="handlePhaseCoordinatesSet"
            @close="closePhaseModal"
        />

        <!-- Cluster Edit Modal -->
        <ClusterEditModal
            v-if="showClusterModal"
            :phase-id="selectedPhase?.id"
            :phases="localPhases"
            :existing-coordinates="editingItem?.coordinates"
            @coordinates-set="handleClusterCoordinatesSet"
            @close="closeClusterModal"
        />

        <!-- Lot Edit Modal -->
        <LotEditModal
            v-if="showLotModal"
            :cluster-id="selectedCluster?.id"
            :phases="localPhases"
            :existing-coordinates="editingItem?.coordinates"
            @coordinates-set="handleLotCoordinatesSet"
            @close="closeLotModal"
        />
    </div>
</template>
