<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";

import Input from "@/Components/Form/Input.vue";
import Button from "@/Components/Form/Button.vue";
import Dashboard from "@/Layouts/Dashboard.vue";
import TableHeader from "@/Components/Table/TableHeader.vue";
import TableData from "@/Components/Table/TableData.vue";

// Props (you will pass these from backend)
const props = defineProps({
    phases: Array, // each phase has clusters
});

// Search
const search = ref("");

// Tabs
const activeTab = ref("phase"); // phase | cluster | lot

// Navigation state
const selectedPhase = ref(null);
const selectedCluster = ref(null);

// =========================
// FILTERED DATA
// =========================
const filteredPhases = computed(() =>
    props.phases.filter((p) =>
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

    return selectedCluster.value.lots.filter((l) =>
        l.number.toLowerCase().includes(search.value.toLowerCase())
    );
});

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

const goToLotDetails = (lot) => {
    router.visit(route("clerk.lots.show", lot.id));
};

// Optional: back navigation
const goBack = () => {
    if (activeTab.value === "lot") {
        activeTab.value = "cluster";
        selectedCluster.value = null;
    } else if (activeTab.value === "cluster") {
        activeTab.value = "phase";
        selectedPhase.value = null;
    }
};

defineOptions({
    layout: Dashboard,
});
</script>

<template>
    <div class="max-w-[85rem] px-4 py-10 mx-auto">
        <div
            class="bg-white dark:bg-neutral-800 border rounded-xl shadow overflow-hidden"
        >
            <!-- HEADER -->
            <div class="px-6 py-4 flex justify-between items-center border-b">
                <!-- Search -->
                <Input placeholder="Search..." v-model="search" />

                <!-- Tabs -->
                <div
                    class="flex gap-2 bg-gray-100 dark:bg-neutral-700 p-1 rounded-xl"
                >
                    <button
                        v-for="tab in ['phase', 'cluster', 'lot']"
                        :key="tab"
                        @click="activeTab = tab"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition"
                        :class="
                            activeTab === tab
                                ? 'bg-green-500 text-white'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-green-500/10'
                        "
                    >
                        {{ tab.charAt(0).toUpperCase() + tab.slice(1) }}
                    </button>
                </div>

                <!-- Back button -->
                <Button v-if="activeTab !== 'phase'" @click="goBack">
                    Back
                </Button>
            </div>

            <!-- TABLE -->
            <table class="min-w-full divide-y">
                <!-- HEADERS -->
                <thead class="bg-gray-50 dark:bg-neutral-800">
                    <tr v-if="activeTab === 'phase'">
                        <TableHeader>Name</TableHeader>
                        <TableHeader>Total Clusters</TableHeader>
                    </tr>

                    <tr v-else-if="activeTab === 'cluster'">
                        <TableHeader>Name</TableHeader>
                        <TableHeader>Total Lots</TableHeader>
                    </tr>

                    <tr v-else>
                        <TableHeader>Lot Number</TableHeader>
                        <TableHeader>Status</TableHeader>
                    </tr>
                </thead>

                <!-- BODY -->
                <tbody class="divide-y">
                    <!-- PHASE -->
                    <tr
                        v-if="activeTab === 'phase'"
                        v-for="phase in filteredPhases"
                        :key="phase.id"
                        class="cursor-pointer hover:bg-gray-50"
                        @click="goToClusters(phase)"
                    >
                        <TableData>{{ phase.name }}</TableData>
                        <TableData>{{ phase.clusters.length }}</TableData>
                    </tr>

                    <!-- CLUSTER -->
                    <tr
                        v-else-if="activeTab === 'cluster'"
                        v-for="cluster in filteredClusters"
                        :key="cluster.id"
                        class="cursor-pointer hover:bg-gray-50"
                        @click="goToLots(cluster)"
                    >
                        <TableData>{{ cluster.name }}</TableData>
                        <TableData>{{ cluster.lots.length }}</TableData>
                    </tr>

                    <!-- LOT -->
                    <tr
                        v-else
                        v-for="lot in filteredLots"
                        :key="lot.id"
                        class="cursor-pointer hover:bg-gray-50"
                        @click="goToLotDetails(lot)"
                    >
                        <TableData>{{ lot.number }}</TableData>
                        <TableData :isHighlighted="true">
                            {{ lot.status }}
                        </TableData>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
