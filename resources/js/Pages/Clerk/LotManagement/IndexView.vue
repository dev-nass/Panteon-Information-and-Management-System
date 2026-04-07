<script setup>
import { ref, computed, watch, onBeforeUnmount } from "vue";
import { router } from "@inertiajs/vue3";

import Input from "@/Components/Form/Input.vue";
import Button from "@/Components/Form/Button.vue";
import Dashboard from "@/Layouts/Dashboard.vue";
import TableHeader from "@/Components/Table/TableHeader.vue";
import TableData from "@/Components/Table/TableData.vue";

import { useSearch } from "@/composables/map/search/useSearch";

// Props (you will pass these from backend)
const props = defineProps({
    phases: Array, // each phase has clusters
});

console.log(props.phases);

const { fetchPhase, fetchCluster, fetchLot, clearSearch } = useSearch();

// =========================
// EDITING STATE
// =========================
const editing = ref(false);
const hasChanges = ref(false);

// deep copy original data
const originalData = ref(JSON.parse(JSON.stringify(props.phases)));
const localPhases = ref(JSON.parse(JSON.stringify(props.phases)));

watch(
    localPhases,
    (val) => {
        hasChanges.value =
            JSON.stringify(val) !== JSON.stringify(originalData.value);
    },
    { deep: true }
);

const discardChanges = () => {
    localPhases.value = JSON.parse(JSON.stringify(originalData.value));
    hasChanges.value = false;
    editing.value = false;
};

const saveChanges = () => {
    router.post(
        route("clerk.lot_management.update"),
        {
            phases: localPhases.value,
        },
        {
            onSuccess: () => {
                originalData.value = JSON.parse(
                    JSON.stringify(localPhases.value)
                );
                hasChanges.value = false;
                editing.value = false;
            },
        }
    );
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
        activeTab.value = "cluster";
    }
};

const handleViewClusterOnTable = (clusterId) => {
    for (const phase of localPhases.value) {
        const cluster = phase.clusters.find((c) => c.id === clusterId);
        if (cluster) {
            selectedPhase.value = phase;
            selectedCluster.value = cluster;
            activeTab.value = "lot";
            break;
        }
    }
};

const handleViewLotOnTable = (lotId) => {
    for (const phase of localPhases.value) {
        for (const cluster of phase.clusters) {
            const lot = cluster.lots.find((l) => l.id === lotId);
            if (lot) {
                selectedPhase.value = phase;
                selectedCluster.value = cluster;
                activeTab.value = "lot";
                search.value = lot.number;
                return;
            }
        }
    }
};

window.handleViewPhaseOnTable = handleViewPhaseOnTable;
window.handleViewClusterOnTable = handleViewClusterOnTable;
window.handleViewLotOnTable = handleViewLotOnTable;

// =========================
// FILTERED DATA
// =========================
const filteredPhases = computed(() =>
    localPhases.value.filter((p) =>
        p.name.toLowerCase().includes(search.value.toLowerCase())
    )
);

const filteredClusters = computed(() => {
    if (!selectedPhase.value) return [];

    return selectedPhase.value.clusters.filter((c) =>
        c.name.toLowerCase().includes(search.value.toLowerCase())
    );
});

const filteredLots = computed(() => {
    if (!selectedCluster.value) return [];
    console.log(selectedCluster.value);

    return selectedCluster.value.lots.filter(
        (l) =>
            l.column.toLowerCase().includes(search.value.toLowerCase()) ||
            l.row.toLowerCase().includes(search.value.toLowerCase())
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
    if (editing.value) return;
    selectedPhase.value = phase;
    selectedCluster.value = null;
    activeTab.value = "cluster";
};

const goToLots = (cluster) => {
    if (editing.value) return;
    selectedCluster.value = cluster;
    activeTab.value = "lot";
};

// const goToLotDetails = (lot) => {
//     if (editing.value) return;
//     router.visit(route("clerk.lots.show", lot.id));
// };

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
        router.delete(route("clerk.lot_management.delete.phase", phaseId), {
            onSuccess: () => {
                localPhases.value = localPhases.value.filter(
                    (p) => p.id !== phaseId
                );
                originalData.value = JSON.parse(
                    JSON.stringify(localPhases.value)
                );
            },
        });
    }
};

const deleteCluster = (clusterId) => {
    if (
        confirm(
            "Are you sure you want to delete this cluster? This will also delete all lots within it."
        )
    ) {
        router.delete(route("clerk.lot_management.delete.cluster", clusterId), {
            onSuccess: () => {
                for (const phase of localPhases.value) {
                    phase.clusters = phase.clusters.filter(
                        (c) => c.id !== clusterId
                    );
                }
                originalData.value = JSON.parse(
                    JSON.stringify(localPhases.value)
                );
            },
        });
    }
};

const deleteLot = (lotId) => {
    if (confirm("Are you sure you want to delete this lot?")) {
        router.delete(route("clerk.lot_management.delete.lot", lotId), {
            onSuccess: () => {
                for (const phase of localPhases.value) {
                    for (const cluster of phase.clusters) {
                        cluster.lots = cluster.lots.filter(
                            (l) => l.id !== lotId
                        );
                    }
                }
                originalData.value = JSON.parse(
                    JSON.stringify(localPhases.value)
                );
            },
        });
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

                    <template v-if="!editing">
                        <Button
                            @click="goToCreate"
                            class="bg-white dark:bg-neutral-700 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-neutral-600"
                        >
                            Create
                        </Button>
                        <Button
                            @click="editing = true"
                            class="bg-green-500/10 text-green-400 hover:bg-green-500/20"
                        >
                            Edit
                        </Button>
                    </template>

                    <template v-else>
                        <Button
                            :disabled="!hasChanges"
                            @click="saveChanges"
                            class="bg-green-500/10 text-green-400"
                        >
                            Save
                        </Button>

                        <Button @click="discardChanges"> Discard </Button>
                    </template>
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
                        <TableHeader>Location</TableHeader>
                        <TableHeader v-if="editing">Actions</TableHeader>
                    </tr>

                    <tr v-else-if="activeTab === 'cluster'">
                        <TableHeader>ID</TableHeader>
                        <TableHeader>Name</TableHeader>
                        <TableHeader>Occupants</TableHeader>
                        <TableHeader>Total Lots</TableHeader>
                        <TableHeader>Type</TableHeader>
                        <TableHeader>Location</TableHeader>
                        <TableHeader v-if="editing">Actions</TableHeader>
                    </tr>

                    <tr v-else>
                        <TableHeader>ID</TableHeader>
                        <TableHeader>Column</TableHeader>
                        <TableHeader>Row</TableHeader>
                        <TableHeader>Status</TableHeader>
                        <TableHeader>Burial Record</TableHeader>
                        <TableHeader>Location</TableHeader>
                        <TableHeader v-if="editing">Actions</TableHeader>
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
                                v-if="editing"
                                v-model="phase.name"
                                @click.stop
                                class="w-full px-3 py-2 bg-white dark:bg-neutral-900 border border-gray-300 dark:border-neutral-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900 dark:text-gray-100"
                            />
                            <span v-else>{{ phase.name }}</span>
                        </TableData>

                        <TableData> {{ phase.clusters.length }} </TableData>
                        <TableData>
                            <button
                                v-if="phase.isPhase_mapped"
                                @click.stop="
                                    redirectToClerkMap(phase.id, 'phase')
                                "
                                class="px-3 py-1 text-sm rounded-lg bg-green-500/10 text-green-400 hover:bg-green-500/20 border border-green-500/30 transition-all duration-200"
                            >
                                View on Map
                            </button>
                            <span
                                v-else
                                class="px-3 py-1 text-sm rounded-lg bg-red-500/10 text-red-400 border border-red-500/30"
                            >
                                Not Mapped
                            </span>
                        </TableData>

                        <TableData v-if="editing">
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
                                v-if="editing"
                                v-model="cluster.name"
                                @click.stop
                                class="w-full px-3 py-2 bg-white dark:bg-neutral-900 border border-gray-300 dark:border-neutral-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900 dark:text-gray-100"
                            />
                            <span v-else>{{ cluster.name }}</span>
                        </TableData>
                        <TableData> {{ cluster.occupants }} </TableData>
                        <TableData>
                            {{ cluster.lots.length }}
                        </TableData>

                        <TableData>
                            <input
                                v-if="editing"
                                v-model="cluster.type"
                                @click.stop
                                class="w-full px-3 py-2 bg-white dark:bg-neutral-900 border border-gray-300 dark:border-neutral-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900 dark:text-gray-100"
                            />
                            <span v-else>{{ cluster.type }}</span>
                        </TableData>
                        <TableData>
                            <button
                                v-if="cluster.isCluster_mapped"
                                @click.stop="
                                    redirectToClerkMap(cluster.id, 'cluster')
                                "
                                class="px-3 py-1 text-sm rounded-lg bg-green-500/10 text-green-400 hover:bg-green-500/20 border border-green-500/30 transition-all duration-200"
                            >
                                View on Map
                            </button>
                            <span
                                v-else
                                class="px-3 py-1 text-sm rounded-lg bg-red-500/10 text-red-400 border border-red-500/30"
                            >
                                Not Mapped
                            </span>
                        </TableData>

                        <TableData v-if="editing">
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
                                v-if="editing"
                                v-model="lot.column"
                                @click.stop
                                class="w-full px-3 py-2 bg-white dark:bg-neutral-900 border border-gray-300 dark:border-neutral-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900 dark:text-gray-100"
                            />
                            <span v-else>{{ lot.column }}</span>
                        </TableData>

                        <TableData>
                            <input
                                v-if="editing"
                                v-model="lot.row"
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
                                @click.stop="
                                    redirectToClerkBurialRecordShow(lot.id)
                                "
                                class="px-3 py-1 text-sm rounded-lg bg-green-500/10 text-green-400 hover:bg-green-500/20 border border-green-500/30 transition-all duration-200"
                            >
                                View Details
                            </button>
                        </TableData>

                        <TableData>
                            <button
                                v-if="lot.isLot_mapped"
                                @click.stop="redirectToClerkMap(lot.id, 'lot')"
                                class="px-3 py-1 text-sm rounded-lg bg-green-500/10 text-green-400 hover:bg-green-500/20 border border-green-500/30 transition-all duration-200"
                            >
                                View on Map
                            </button>
                            <span
                                v-else
                                class="px-3 py-1 text-sm rounded-lg bg-red-500/10 text-red-400 border border-red-500/30"
                            >
                                Not Mapped
                            </span>
                        </TableData>

                        <TableData v-if="editing">
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
                        </TableData>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
