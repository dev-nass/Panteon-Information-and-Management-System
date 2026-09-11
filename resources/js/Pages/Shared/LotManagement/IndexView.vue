<script setup>
import { ref, computed, onBeforeUnmount, onMounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";

import Input from "@/Components/Form/Input.vue";
import Button from "@/Components/Form/Button.vue";
import Dashboard from "@/Layouts/Dashboard.vue";
import PhaseTable from "@/Components/LotManagement/PhaseTable.vue";
import ClusterTable from "@/Components/LotManagement/ClusterTable.vue";
import LotTable from "@/Components/LotManagement/LotTable.vue";

import { useSearch } from "@/composables/map/search/useSearch";

const page = usePage();
const user = computed(() => page.props.auth.user);
const userRole = computed(() =>
    page.props?.auth?.user?.role.toLowerCase()?.trim(),
);
const roleRoutes = {
    admin: {
        map: { route: "admin.map.index" },
        burial_record: { route: "admin.lot_management.show" },
    },
    clerk: {
        map: { route: "clerk.map.index" },
        burial_record: { route: "clerk.lot_management.show" },
    },
};

const props = defineProps({
    phases: Array,
});

const { fetchPhase, fetchCluster, fetchLot, clearSearch } = useSearch();

// =========================
// Search
// =========================
const search = ref("");

// Tabs
const activeTab = ref("phase"); // phase | cluster | lot

// Navigation state
const selectedPhase = ref(null);
const selectedCluster = ref(null);

const filteredPhases = computed(() =>
    props.phases.filter((p) =>
        p.name.toLowerCase().includes(search.value.toLowerCase()),
    ),
);

const currentPhaseName = computed(() => selectedPhase.value?.name || null);
const currentClusterName = computed(() => selectedCluster.value?.name || null);

const goToClusters = (phase) => {
    selectedPhase.value = phase;
    selectedCluster.value = null;
    activeTab.value = "cluster";
    search.value = "";
};

const goToLots = (cluster) => {
    selectedCluster.value = cluster;
    activeTab.value = "lot";
    search.value = "";
};

const goBack = () => {
    search.value = "";
    if (activeTab.value === "lot") {
        activeTab.value = "cluster";
        selectedCluster.value = null;
    } else if (activeTab.value === "cluster") {
        activeTab.value = "phase";
        selectedPhase.value = null;
    }
};

const goToCreate = () => {
    const params = { type: activeTab.value };

    if (activeTab.value === "cluster" && selectedPhase.value) {
        params.phase_id = selectedPhase.value.id;
    } else if (activeTab.value === "lot" && selectedCluster.value) {
        params.cluster_id = selectedCluster.value.id;
    }

    router.visit(route("admin.lot_management.create", params));
};

// Setup window functions for map navigation
window.fetchPhase = fetchPhase;
window.fetchCluster = fetchCluster;
window.fetchLot = fetchLot;

// View on Table handlers (called from Shared/Map/IndexView via window)
const handleViewPhaseOnTable = (phaseId) => {
    const phase = props.phases.find((p) => p.id == phaseId);
    if (phase) {
        goToClusters(phase);
    } else {
        activeTab.value = "phase";
    }
};

const handleViewClusterOnTable = async (clusterId) => {
    try {
        const res = await fetch(
            route("api.lot.management.cluster", { cluster_id: clusterId }),
            { credentials: "same-origin" },
        );
        const json = await res.json();
        const phaseName = json.data?.[0]?.cluster?.properties?.phase;
        if (phaseName) {
            const phase = props.phases.find((p) => p.name === phaseName);
            if (phase) {
                selectedPhase.value = phase;
                activeTab.value = "cluster";
                search.value = "";
                // Optionally highlight cluster via search after clusters load
                try {
                    const clusterRes = await fetch(
                        route("api.lot.management.clusters", phase.id),
                        { credentials: "same-origin" },
                    );
                    const clusters = await clusterRes.json();
                    const target = clusters.find((c) => c.id == clusterId);
                    if (target) {
                        search.value = target.name;
                    }
                } catch {}
                return;
            }
        }
    } catch {}
    activeTab.value = "cluster";
    search.value = "";
};

const handleViewLotOnTable = async (lotId) => {
    try {
        const res = await fetch(
            route("api.lot.management.lot", { lot_id: lotId }),
            { credentials: "same-origin" },
        );
        const json = await res.json();
        const phaseName = json.data?.[0]?.lot?.properties?.phase;
        const clusterName = json.data?.[0]?.lot?.properties?.cluster;
        const lotProps = json.data?.[0]?.lot?.properties;
        if (phaseName) {
            const phase = props.phases.find((p) => p.name === phaseName);
            if (phase) {
                selectedPhase.value = phase;
                try {
                    const clusterRes = await fetch(
                        route("api.lot.management.clusters", phase.id),
                        { credentials: "same-origin" },
                    );
                    const clusters = await clusterRes.json();
                    const cluster = clusters.find(
                        (c) => c.name === clusterName,
                    );
                    if (cluster) {
                        selectedCluster.value = cluster;
                        activeTab.value = "lot";
                        if (lotProps?.column && lotProps?.row) {
                            search.value = `${lotProps.column}${lotProps.row}`;
                        } else {
                            search.value = "";
                        }
                        return;
                    }
                } catch {}
                activeTab.value = "cluster";
                return;
            }
        }
    } catch {}
    activeTab.value = "lot";
    search.value = "";
};

window.handleViewPhaseOnTable = handleViewPhaseOnTable;
window.handleViewClusterOnTable = handleViewClusterOnTable;
window.handleViewLotOnTable = handleViewLotOnTable;

onMounted(() => {
    // Handle direct navigation via query params from Map -> Table
    const params = new URLSearchParams(window.location.search);
    const phaseId = params.get("phase_id");
    const clusterId = params.get("cluster_id");
    const lotId = params.get("lot_id");
    const legacyId = params.get("id");
    if (phaseId) {
        handleViewPhaseOnTable(phaseId);
    } else if (clusterId) {
        handleViewClusterOnTable(clusterId);
    } else if (lotId) {
        handleViewLotOnTable(lotId);
    } else if (legacyId) {
        // Try phase first
        const phase = props.phases.find((p) => p.id == legacyId);
        if (phase) {
            handleViewPhaseOnTable(legacyId);
        } else {
            // fallback to cluster/lot handling
            handleViewClusterOnTable(legacyId);
        }
    }
});

onBeforeUnmount(() => {
    clearSearch();
    // Cleanup global handlers
    if (window.handleViewPhaseOnTable === handleViewPhaseOnTable) {
        delete window.handleViewPhaseOnTable;
    }
    if (window.handleViewClusterOnTable === handleViewClusterOnTable) {
        delete window.handleViewClusterOnTable;
    }
    if (window.handleViewLotOnTable === handleViewLotOnTable) {
        delete window.handleViewLotOnTable;
    }
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
                class="px-6 py-4 grid gap-3 lg:flex lg:justify-between lg:items-center border-b border-gray-200 dark:border-neutral-700"
            >
                <!-- Search -->
                <Input
                    class="w-full lg:max-w-md"
                    placeholder="Search..."
                    v-model="search"
                />

                <!-- Tabs -->
                <div
                    class="flex flex-wrap gap-2 bg-gray-100 dark:bg-neutral-900 p-1 rounded-xl w-full lg:w-fit"
                >
                    <button
                        v-for="tab in ['phase', 'cluster', 'lot']"
                        :key="tab"
                        @click="activeTab = tab"
                        class="flex-1 lg:flex-none px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition"
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
                    <Button
                        v-if="activeTab !== 'phase'"
                        class="dark:text-white"
                        @click="goBack"
                    >
                        Back
                    </Button>

                    <Button
                        v-if="user.role === 'admin'"
                        @click="goToCreate"
                        class="bg-green-500/10 text-green-400 hover:bg-green-500/20"
                    >
                        Create
                    </Button>
                </div>
            </div>

            <!-- CONTEXT INDICATOR -->
            <div
                class="px-6 py-3 bg-gray-50 dark:bg-neutral-800 border-b border-gray-200 dark:border-neutral-700 flex flex-wrap items-center gap-2 text-sm"
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
            <div v-if="activeTab === 'phase'">
                <PhaseTable
                    :phases="filteredPhases"
                    :search="search"
                    :user-role="userRole"
                    :role-route="roleRoutes[userRole].map.route"
                    @select-phase="goToClusters"
                />
            </div>

            <div v-else-if="activeTab === 'cluster'">
                <ClusterTable
                    :phase-id="selectedPhase?.id"
                    :search="search"
                    :user-role="userRole"
                    :role-route="roleRoutes[userRole].map.route"
                    @select-cluster="goToLots"
                />
            </div>

            <div v-else>
                <LotTable
                    :cluster-id="selectedCluster?.id"
                    :search="search"
                    :user-role="userRole"
                    :map-role-route="roleRoutes[userRole].map.route"
                    :burial-record-role-route="
                        roleRoutes[userRole].burial_record.route
                    "
                />
            </div>
        </div>
    </div>
</template>
