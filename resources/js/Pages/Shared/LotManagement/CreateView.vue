<script setup>
import { ref, computed, onMounted, watch, nextTick } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { useToast } from "vue-toast-notification";

import Input from "@/Components/Form/Input.vue";
import Button from "@/Components/Form/Button.vue";
import Dashboard from "@/Layouts/Dashboard.vue";
import PhaseePlottingModal from "@/Components/Map/PhaseePlottingModal.vue";
import ClusterPlottingModal from "@/Components/Map/ClusterPlottingModal.vue";
import BulkLotPlottingModal from "@/Components/Map/BulkLotPlottingModal.vue";

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

const lotBulkForm = useForm({
    cluster_id: "",
    row: "",
    start_column: "",
    end_column: "",
});

const bulkErrors = ref({});
const capacityStatus = ref(null);

const selectedPhase = ref(null);
const availableClusters = ref([]);
const selectedPhaseForLot = ref("");

const handlePhaseChange = (phaseId) => {
    selectedPhaseForLot.value = phaseId;
    const phase = props.phases.find((p) => p.id == phaseId);
    selectedPhase.value = phase;
    availableClusters.value = phase?.clusters || [];
    lotBulkForm.cluster_id = "";
};

const submitPhase = () => {
    phaseForm.post(route("admin.lot_management.store.phase"), {
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
    clusterForm.post(route("admin.lot_management.store.cluster"), {
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

const selectedClusterForLot = computed(() =>
    availableClusters.value.find((c) => c.id == lotBulkForm.cluster_id),
);

const validateLotRange = () => {
    if (!lotBulkForm.cluster_id) {
        toast.error("Please select a cluster first");
        return;
    }

    if (
        !lotBulkForm.row ||
        !lotBulkForm.start_column ||
        !lotBulkForm.end_column
    ) {
        toast.error("Please fill in the row, start column, and end column");
        return;
    }

    if (parseInt(lotBulkForm.start_column) > parseInt(lotBulkForm.end_column)) {
        toast.error("Start column must be less than or equal to end column");
        return;
    }

    const cluster = selectedClusterForLot.value;
    if (!cluster) {
        toast.error("Please select a valid cluster");
        return;
    }

    const requested =
        parseInt(lotBulkForm.end_column) -
        parseInt(lotBulkForm.start_column) +
        1;
    const remaining = cluster.remaining_capacity;
    const valid = remaining === null || requested <= remaining;

    capacityStatus.value = {
        valid,
        requested,
        remaining,
        total_capacity: cluster.total_capacity,
    };

    if (valid) {
        toast.success(
            remaining === null
                ? `${requested} lot(s) fit in this cluster.`
                : `${requested} lot(s) fit — ${remaining} remaining.`,
        );
    } else {
        toast.error(
            `Not enough space: ${requested} lot(s) requested but only ${remaining} remaining.`,
        );
    }
};

const openBulkPlottingModal = () => {
    if (!lotBulkForm.cluster_id) {
        toast.error("Please select a cluster first");
        return;
    }

    if (
        !lotBulkForm.row ||
        !lotBulkForm.start_column ||
        !lotBulkForm.end_column
    ) {
        toast.error("Please fill in the row, start column, and end column");
        return;
    }

    if (parseInt(lotBulkForm.start_column) > parseInt(lotBulkForm.end_column)) {
        toast.error("Start column must be less than or equal to end column");
        return;
    }

    bulkErrors.value = {};
    showLotModal.value = true;
};

const handleBulkSave = (lots) => {
    router.post(
        route("admin.lot_management.store.bulk_lot"),
        {
            cluster_id: lotBulkForm.cluster_id,
            lots: lots.map((lot) => ({
                column: lot.column,
                row: lot.row,
                coordinates: JSON.stringify(lot.coordinates),
            })),
        },
        {
            onSuccess: () => {
                lotBulkForm.reset();
                showLotModal.value = false;
                toast.success("Lots created successfully!");
            },
            onError: (errors) => {
                bulkErrors.value = errors;
                toast.error(
                    "Please fix the validation errors before submitting.",
                );
            },
        },
    );
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
    router.visit(route("admin.lot_management.index"));
};

defineOptions({
    layout: Dashboard,
});

const initTooltips = () => {
    nextTick(() => {
        if (window.HSTooltip) {
            window.HSTooltip.autoInit();
        }
    });
};

onMounted(() => {
    initTooltips();
});

watch(activeTab, () => {
    initTooltips();
});

watch(
    () => [
        lotBulkForm.cluster_id,
        lotBulkForm.row,
        lotBulkForm.start_column,
        lotBulkForm.end_column,
    ],
    () => {
        capacityStatus.value = null;
    },
);
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

                <Button class="dark:text-white" @click="goBack"> Back </Button>
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
                        <select
                            v-model="clusterForm.type"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-green-500"
                            required
                        >
                            <option value="">Select a type</option>
                            <option value="underground">Underground</option>
                            <option value="apartment">Apartment</option>
                            <option value="columbarium">Columbarium</option>
                        </select>
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
                                    class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-xs uppercase font-medium text-white rounded-md shadow-sm dark:bg-neutral-700"
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
                <form
                    v-else
                    @submit.prevent="openBulkPlottingModal"
                    class="space-y-4"
                >
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
                            v-model="lotBulkForm.cluster_id"
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
                            v-if="lotBulkForm.errors.cluster_id"
                            class="text-red-500 text-sm"
                        >
                            {{ lotBulkForm.errors.cluster_id }}
                        </span>
                        <span
                            v-if="selectedClusterForLot"
                            class="block mt-1 text-xs text-gray-500 dark:text-gray-400"
                        >
                            {{
                                selectedClusterForLot.remaining_capacity ===
                                null
                                    ? "No capacity limit set"
                                    : `${selectedClusterForLot.remaining_capacity} of ${selectedClusterForLot.total_capacity} lot(s) remaining`
                            }}
                        </span>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Lot Row
                        </label>
                        <Input
                            v-model="lotBulkForm.row"
                            placeholder="A, B, C..."
                            required
                        />
                        <span
                            v-if="lotBulkForm.errors.row"
                            class="text-red-500 text-sm"
                        >
                            {{ lotBulkForm.errors.row }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-x-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                            >
                                Start Column
                            </label>
                            <Input
                                v-model="lotBulkForm.start_column"
                                type="number"
                                placeholder="1, 2, 3..."
                                required
                            />
                            <span
                                v-if="lotBulkForm.errors.start_column"
                                class="text-red-500 text-sm"
                            >
                                {{ lotBulkForm.errors.start_column }}
                            </span>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                            >
                                End Column
                            </label>
                            <Input
                                v-model="lotBulkForm.end_column"
                                type="number"
                                placeholder="1, 2, 3..."
                                required
                            />
                            <span
                                v-if="lotBulkForm.errors.end_column"
                                class="text-red-500 text-sm"
                            >
                                {{ lotBulkForm.errors.end_column }}
                            </span>
                        </div>
                    </div>

                    <div
                        v-if="Object.keys(bulkErrors).length"
                        class="text-red-500 text-sm space-y-1"
                    >
                        <p v-for="(error, key) in bulkErrors" :key="key">
                            {{ error }}
                        </p>
                    </div>

                    <Button
                        :highlighted="true"
                        type="button"
                        @click="validateLotRange"
                        class="w-full flex-1 px-4 py-2 rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-gray-900 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-neutral-600 transition"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="lucide lucide-check-check-icon lucide-check-check"
                        >
                            <path d="M18 6 7 17l-5-5" />
                            <path d="m22 10-7.5 7.5L13 16" />
                        </svg>
                        Validate Range
                    </Button>

                    <div
                        v-if="capacityStatus"
                        class="px-4 py-3 rounded-lg border text-sm"
                        :class="
                            capacityStatus.valid
                                ? 'border-green-500/50 bg-green-500/5 text-green-700 dark:text-green-400'
                                : 'border-red-500/50 bg-red-500/5 text-red-700 dark:text-red-400'
                        "
                    >
                        <template v-if="capacityStatus.valid">
                            ✓ {{ capacityStatus.requested }} lot(s) fit —
                            {{
                                capacityStatus.remaining === null
                                    ? "no capacity limit set"
                                    : `${capacityStatus.remaining} remaining`
                            }}
                        </template>
                        <template v-else>
                            ✗ Not enough space:
                            {{ capacityStatus.requested }} lot(s) requested but
                            only {{ capacityStatus.remaining }} remaining
                        </template>
                    </div>

                    <div>
                        <Button
                            type="submit"
                            :disabled="!capacityStatus?.valid"
                            class="w-full bg-green-500/10 text-green-400 hover:bg-green-500/20 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            📍 Plot Lots on Map
                        </Button>
                        <p
                            v-if="!capacityStatus?.valid"
                            class="mt-2 text-xs text-gray-500 dark:text-gray-400 text-center"
                        >
                            Validate the range first before plotting.
                        </p>
                    </div>
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

        <!-- Bulk Lot Plotting Modal -->
        <BulkLotPlottingModal
            v-if="showLotModal"
            :cluster-id="lotBulkForm.cluster_id"
            :phases="phases"
            :row="lotBulkForm.row"
            :start-column="lotBulkForm.start_column"
            :end-column="lotBulkForm.end_column"
            @bulk-save="handleBulkSave"
            @close="closeLotModal"
        />
    </div>
</template>
