<script setup>
import { ref, computed, onBeforeUnmount } from "vue";
import { router } from "@inertiajs/vue3";

import Input from "@/Components/Form/Input.vue";
import Button from "@/Components/Form/Button.vue";
import Dashboard from "@/Layouts/Dashboard.vue";
import PhaseTable from "@/Components/LotManagement/PhaseTable.vue";
import ClusterTable from "@/Components/LotManagement/ClusterTable.vue";
import LotTable from "@/Components/LotManagement/LotTable.vue";

import { useSearch } from "@/composables/map/search/useSearch";

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

    router.visit(route("clerk.lot_management.create", params));
};

// Setup window functions for map navigation
window.fetchPhase = fetchPhase;
window.fetchCluster = fetchCluster;
window.fetchLot = fetchLot;

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
                    <Button
                        v-if="activeTab !== 'phase'"
                        class="dark:text-white"
                        @click="goBack"
                    >
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
            <div v-if="activeTab === 'phase'">
                <PhaseTable
                    :phases="filteredPhases"
                    :search="search"
                    @select-phase="goToClusters"
                />
            </div>

            <div v-else-if="activeTab === 'cluster'">
                <ClusterTable
                    :phase-id="selectedPhase?.id"
                    :search="search"
                    @select-cluster="goToLots"
                />
            </div>

            <div v-else>
                <LotTable :cluster-id="selectedCluster?.id" :search="search" />
            </div>
        </div>
    </div>
</template>
