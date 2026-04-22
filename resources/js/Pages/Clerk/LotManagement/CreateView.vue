<script setup>
import { ref, onMounted } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { useToast } from "vue-toast-notification";

import Input from "@/Components/Form/Input.vue";
import Button from "@/Components/Form/Button.vue";
import Dashboard from "@/Layouts/Dashboard.vue";
import PhaseePlottingModal from "@/Components/Map/PhaseePlottingModal.vue";
import ClusterPlottingModal from "@/Components/Map/ClusterPlottingModal.vue";
import LotPlottingModal from "@/Components/Map/LotPlottingModal.vue";

const props = defineProps({
    phases: Array,
});

const toast = useToast();

const activeTab = ref("phase");
const showPhaseModal = ref(false);
const showClusterModal = ref(false);
const showLotModal = ref(false);

const phaseForm = useForm({
    name: "",
    coordinates: null,
});

const clusterForm = useForm({
    phase_id: "",
    name: "",
    type: "",
    total_capacity: 0,
    coordinates: null,
});

const lotForm = useForm({
    cluster_id: "",
    column: "",
    row: "",
    status: "available",
    coordinates: null,
});

const selectedPhase = ref(null);
const availableClusters = ref([]);
const selectedPhaseForLot = ref("");

const handlePhaseChange = (phaseId) => {
    selectedPhaseForLot.value = phaseId;
    const phase = props.phases.find((p) => p.id == phaseId);
    selectedPhase.value = phase;
    availableClusters.value = phase?.clusters || [];
    lotForm.cluster_id = "";
};

const submitPhase = () => {
    phaseForm.post(route("clerk.lot_management.store.phase"), {
        onSuccess: () => {
            phaseForm.reset();
            toast.success("Phase created successfully!");
        },
        onError: () => {
            toast.error("Please fix the validation errors before submitting.");
        },
    });
};

const openPhaseModal = () => {
    showPhaseModal.value = true;
};

const handlePhaseCoordinatesSet = (coords) => {
    phaseForm.coordinates = JSON.stringify(coords);
    toast.success("Coordinates set successfully!");
};

const submitCluster = () => {
    clusterForm.post(route("clerk.lot_management.store.cluster"), {
        onSuccess: () => {
            clusterForm.reset();
            toast.success("Cluster created successfully!");
        },
        onError: () => {
            toast.error("Please fix the validation errors before submitting.");
        },
    });
};

const openClusterModal = () => {
    if (!clusterForm.phase_id) {
        toast.error("Please select a phase first");
        return;
    }
    showClusterModal.value = true;
};

const handleClusterCoordinatesSet = (coords) => {
    clusterForm.coordinates = JSON.stringify(coords);
    toast.success("Coordinates set successfully!");
};

const submitLot = () => {
    lotForm.post(route("clerk.lot_management.store.lot"), {
        onSuccess: () => {
            lotForm.reset();
            toast.success("Lot created successfully!");
        },
        onError: () => {
            toast.error("Please fix the validation errors before submitting.");
        },
    });
};

const openPlottingModal = () => {
    if (!lotForm.cluster_id) {
        toast.error("Please select a cluster first");
        return;
    }
    showLotModal.value = true;
};

const handleCoordinatesSet = (coords) => {
    lotForm.coordinates = JSON.stringify(coords);
    toast.success("Coordinates set successfully!");
};

const closePhaseModal = () => {
    showPhaseModal.value = false;
};

const closeClusterModal = () => {
    showClusterModal.value = false;
};

const closeLotModal = () => {
    showLotModal.value = false;
};

const goBack = () => {
    router.visit(route("clerk.lot_management.index"));
};

defineOptions({
    layout: Dashboard,
});

onMounted(() => {
    if (window.HSTooltip) {
        window.HSTooltip.autoInit();
    }
});
</script>

<template>
    <div class="max-w-220 px-4 py-10 mx-auto">
        <div
            class="bg-white dark:bg-neutral-800 rounded-xl shadow overflow-hidden"
        >
            <!-- HEADER -->
            <div
                class="px-6 py-4 flex justify-between items-center border-b border-gray-200 dark:border-neutral-700"
            >
                <h2
                    class="text-xl font-semibold text-gray-800 dark:text-gray-200"
                >
                    Create New
                </h2>

                <Button @click="goBack"> Back </Button>
            </div>

            <!-- TABS -->
            <div
                class="px-6 py-4 border-b border-gray-200 dark:border-neutral-700"
            >
                <div
                    class="flex items-center gap-2 bg-gray-100 dark:bg-neutral-900 p-1 rounded-xl"
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
            </div>

            <!-- FORMS -->
            <div class="px-6 py-6">
                <!-- PHASE FORM -->
                <form
                    v-if="activeTab === 'phase'"
                    @submit.prevent="submitPhase"
                    class="space-y-4"
                >
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Phase Name
                        </label>
                        <Input
                            v-model="phaseForm.name"
                            placeholder="Enter phase name"
                            required
                        />
                        <span
                            v-if="phaseForm.errors.name"
                            class="text-red-500 text-sm"
                        >
                            {{ phaseForm.errors.name }}
                        </span>
                    </div>

                    <!-- Coordinates Section -->
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Coordinates
                        </label>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                @click="openPhaseModal"
                                class="flex-1 px-4 py-2 rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-gray-900 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-neutral-600 transition"
                            >
                                📍
                                {{
                                    phaseForm.coordinates
                                        ? "Update Location"
                                        : "Plot on Map"
                                }}
                            </button>
                        </div>
                        <div
                            v-if="phaseForm.coordinates"
                            class="mt-2 text-sm text-green-600 dark:text-green-400"
                        >
                            ✓ Coordinates set
                        </div>
                        <span
                            v-if="phaseForm.errors.coordinates"
                            class="text-red-500 text-sm"
                        >
                            {{ phaseForm.errors.coordinates }}
                        </span>
                    </div>

                    <Button
                        type="submit"
                        :disabled="phaseForm.processing"
                        class="bg-green-500/10 text-green-400 hover:bg-green-500/20"
                    >
                        Create Phase
                    </Button>
                </form>

                <!-- CLUSTER FORM -->
                <form
                    v-else-if="activeTab === 'cluster'"
                    @submit.prevent="submitCluster"
                    class="space-y-4"
                >
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Select Phase
                        </label>
                        <select
                            v-model="clusterForm.phase_id"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-green-500"
                            required
                        >
                            <option value="">Select a phase</option>
                            <option
                                v-for="phase in phases"
                                :key="phase.id"
                                :value="phase.id"
                            >
                                {{ phase.name }}
                            </option>
                        </select>
                        <span
                            v-if="clusterForm.errors.phase_id"
                            class="text-red-500 text-sm"
                        >
                            {{ clusterForm.errors.phase_id }}
                        </span>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Cluster Name
                        </label>
                        <Input
                            v-model="clusterForm.name"
                            placeholder="Enter cluster name"
                            required
                        />
                        <span
                            v-if="clusterForm.errors.name"
                            class="text-red-500 text-sm"
                        >
                            {{ clusterForm.errors.name }}
                        </span>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Type
                        </label>
                        <Input
                            v-model="clusterForm.type"
                            placeholder="(e.g., aparmtent, underground)"
                            required
                        />
                        <span
                            v-if="clusterForm.errors.type"
                            class="text-red-500 text-sm"
                        >
                            {{ clusterForm.errors.type }}
                        </span>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Total Capacity
                            <span class="hs-tooltip inline-block ml-1">
                                <span
                                    class="hs-tooltip-toggle text-red-500 cursor-help font-bold"
                                    >*</span
                                >
                                <span
                                    class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-xs font-medium text-white rounded-md shadow-sm dark:bg-neutral-700"
                                    role="tooltip"
                                >
                                    Allowed lots for this cluster
                                </span>
                            </span>
                        </label>
                        <Input
                            v-model="clusterForm.total_capacity"
                            type="number"
                            placeholder="Enter number of occupants"
                            required
                        />
                        <span
                            v-if="clusterForm.errors.total_capacity"
                            class="text-red-500 text-sm"
                        >
                            {{ clusterForm.errors.total_capacity }}
                        </span>
                    </div>

                    <!-- Coordinates Section -->
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Coordinates
                        </label>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                @click="openClusterModal"
                                class="flex-1 px-4 py-2 rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-gray-900 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-neutral-600 transition"
                            >
                                📍
                                {{
                                    clusterForm.coordinates
                                        ? "Update Location"
                                        : "Plot on Map"
                                }}
                            </button>
                        </div>
                        <div
                            v-if="clusterForm.coordinates"
                            class="mt-2 text-sm text-green-600 dark:text-green-400"
                        >
                            ✓ Coordinates set
                        </div>
                        <span
                            v-if="clusterForm.errors.coordinates"
                            class="text-red-500 text-sm"
                        >
                            {{ clusterForm.errors.coordinates }}
                        </span>
                    </div>

                    <Button
                        type="submit"
                        :disabled="clusterForm.processing"
                        class="bg-green-500/10 text-green-400 hover:bg-green-500/20"
                    >
                        Create Cluster
                    </Button>
                </form>

                <!-- LOT FORM -->
                <form v-else @submit.prevent="submitLot" class="space-y-4">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Select Phase
                        </label>
                        <select
                            v-model="selectedPhaseForLot"
                            @change="handlePhaseChange(selectedPhaseForLot)"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-green-500"
                            required
                        >
                            <option value="">Select a phase</option>
                            <option
                                v-for="phase in phases"
                                :key="phase.id"
                                :value="phase.id"
                            >
                                {{ phase.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Select Cluster
                        </label>
                        <select
                            v-model="lotForm.cluster_id"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-green-500"
                            :disabled="!selectedPhase"
                            required
                        >
                            <option value="">Select a cluster</option>
                            <option
                                v-for="cluster in availableClusters"
                                :key="cluster.id"
                                :value="cluster.id"
                            >
                                {{ cluster.name }}
                            </option>
                        </select>
                        <span
                            v-if="lotForm.errors.cluster_id"
                            class="text-red-500 text-sm"
                        >
                            {{ lotForm.errors.cluster_id }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-x-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                            >
                                Lot Column
                            </label>
                            <Input
                                v-model="lotForm.column"
                                placeholder="1, 2, 3..."
                                required
                            />
                            <span
                                v-if="lotForm.errors.column"
                                class="text-red-500 text-sm"
                            >
                                {{ lotForm.errors.column }}
                            </span>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                            >
                                Lot Row
                            </label>
                            <Input
                                v-model="lotForm.row"
                                placeholder="A, B, C..."
                                required
                            />
                            <span
                                v-if="lotForm.errors.row"
                                class="text-red-500 text-sm"
                            >
                                {{ lotForm.errors.row }}
                            </span>
                        </div>
                    </div>

                    <!-- Coordinates Section -->
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Coordinates
                        </label>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                @click="openPlottingModal"
                                class="flex-1 px-4 py-2 rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-gray-900 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-neutral-600 transition"
                            >
                                📍
                                {{
                                    lotForm.coordinates
                                        ? "Update Location"
                                        : "Plot on Map"
                                }}
                            </button>
                        </div>
                        <div
                            v-if="lotForm.coordinates"
                            class="mt-2 text-sm text-green-600 dark:text-green-400"
                        >
                            ✓ Coordinates set
                        </div>
                        <span
                            v-if="lotForm.errors.coordinates"
                            class="text-red-500 text-sm"
                        >
                            {{ lotForm.errors.coordinates }}
                        </span>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Status
                        </label>
                        <select
                            v-model="lotForm.status"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-green-500"
                            required
                        >
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                        </select>
                        <span
                            v-if="lotForm.errors.status"
                            class="text-red-500 text-sm"
                        >
                            {{ lotForm.errors.status }}
                        </span>
                    </div>

                    <Button
                        type="submit"
                        :disabled="lotForm.processing"
                        class="bg-green-500/10 text-green-400 hover:bg-green-500/20"
                    >
                        Create Lot
                    </Button>
                </form>
            </div>
        </div>

        <!-- Phase Plotting Modal -->
        <PhaseePlottingModal
            v-if="showPhaseModal"
            @coordinates-set="handlePhaseCoordinatesSet"
            @close="closePhaseModal"
        />

        <!-- Cluster Plotting Modal -->
        <ClusterPlottingModal
            v-if="showClusterModal"
            :phase-id="clusterForm.phase_id"
            :phases="phases"
            @coordinates-set="handleClusterCoordinatesSet"
            @close="closeClusterModal"
        />

        <!-- Lot Plotting Modal -->
        <LotPlottingModal
            v-if="showLotModal"
            :cluster-id="lotForm.cluster_id"
            :phases="phases"
            @coordinates-set="handleCoordinatesSet"
            @close="closeLotModal"
        />
    </div>
</template>
