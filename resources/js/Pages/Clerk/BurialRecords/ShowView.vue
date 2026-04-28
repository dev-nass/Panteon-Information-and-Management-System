<script setup>
import { onMounted, onBeforeUnmount, ref, watch, computed } from "vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import { has, isEqual } from "lodash";
import { useToast } from "vue-toast-notification";

import Display from "@/Components/Display.vue";
import Dashboard from "@/Layouts/Dashboard.vue";

import { useMap } from "@/composables/useMap";
import { useSearch } from "@/composables/map/search/useSearch";

const props = defineProps({
    burial_record: { type: Object, required: true },
    phases: { type: Array, required: true },
    current_selection: { type: Object, default: null },
});

const page = usePage();
const errors = computed(() => page.props.errors || {});
const $toast = useToast();

const { initializeMap, cleanupMap, toggleMapFeatures, togglePhaseVisibility } =
    useMap();
const { fetchClusterByBurialId } = useSearch();

// console.log("Clerk Burial Show", props.burial_record);

const activeTab = ref("personal");
const tabs = [
    { key: "personal", label: "Personal Info" },
    { key: "death", label: "Death Info" },
    { key: "disposition", label: "Disposition" },
    { key: "family", label: "Family & Company" },
    { key: "applicant", label: "Applicant" },
    { key: "location", label: "Location" },
    { key: "imported", label: "Imported By" },
];

const back = () => {
    router.visit(route("clerk.burial_records.index"));
};

const editing = ref(false);
const hasChanges = ref(false);

// deep copy original data
const originalData = ref(JSON.parse(JSON.stringify(props.burial_record.data)));
const localData = ref(JSON.parse(JSON.stringify(originalData.value)));
console.log(localData.value);

const selectedPhaseId = ref(props.current_selection?.phase_id || null);
const selectedClusterId = ref(props.current_selection?.cluster_id || null);
const selectedLotId = ref(props.current_selection?.lot_id || null);
const originalLotId = ref(props.current_selection?.lot_id || null);

watch(
    [localData, selectedLotId],
    ([newData, newLotId]) => {
        const dataChanged = !isEqual(newData, originalData.value);
        const lotChanged = newLotId !== originalLotId.value;
        hasChanges.value = dataChanged || lotChanged;
    },
    { deep: true },
);

const discardChanges = () => {
    if (hasChanges.value) {
        HSOverlay.open("#hs-cookies");
        return;
    }

    editing.value = false;
};

const confirmDiscard = () => {
    localData.value = JSON.parse(JSON.stringify(originalData.value));
    selectedPhaseId.value = props.current_selection?.phase_id || null;
    selectedClusterId.value = props.current_selection?.cluster_id || null;
    selectedLotId.value = props.current_selection?.lot_id || null;
    originalLotId.value = props.current_selection?.lot_id || null;
    hasChanges.value = false;
    editing.value = false;

    HSOverlay.close("#hs-cookies");
};

/**
* Description: Get the burial ID of the record being showng and pass
               it on the fetchClusterByBurialId()
*/
const redirectToClerkMap = () => {
    const burialId = props.burial_record.data.burial.id;
    router.visit(route("clerk.map.index"), {
        data: { burialId },
        onSuccess: () => {
            setTimeout(() => {
                fetchClusterByBurialId(burialId);
            }, 500);
        },
    });
};

const deleteBurialRecord = () => {
    if (
        confirm(
            "Are you sure you want to delete this burial record? This action cannot be undone.",
        )
    ) {
        router.delete(
            route(
                "clerk.burial_records.destroy",
                props.burial_record.data.burial.id,
            ),
            {
                onSuccess: () => {
                    $toast.success("Burial record deleted successfully!");
                },
                onError: () => {
                    $toast.error("Failed to delete burial record.");
                },
            },
        );
    }
};

const saveChanges = () => {
    router.post(
        route(
            "clerk.burial_records.update",
            props.burial_record.data.burial.id,
        ),
        {
            deceased: localData.value.deceased,
            lot_id: selectedLotId.value,
        },
        {
            onSuccess: () => {
                originalData.value = JSON.parse(
                    JSON.stringify(localData.value),
                );
                originalLotId.value = selectedLotId.value;
                hasChanges.value = false;
                editing.value = false;
                $toast.success("Burial record updated successfully!");
            },
            onError: () => {
                $toast.error(
                    "Failed to update burial record. Please check the form for errors.",
                );
            },
            preserveScroll: true,
            preserveState: false, // updates the record on show after updating
        },
    );
};

// Initialize location selections based on current burial record
// const initializeLocationSelections = () => {
//     const currentLotId = props.burial_record.data.lot?.lot?.id;
//     const currentClusterId = props.burial_record.data.lot?.cluster?.id;
//
//     if (currentLotId && currentClusterId) {
//         // Find the phase that contains this cluster
//         for (const phase of props.phases) {
//             const cluster = phase.clusters.find(
//                 (c) => c.id == currentClusterId,
//             );
//             if (cluster) {
//                 selectedPhaseId.value = phase.id;
//                 selectedClusterId.value = currentClusterId;
//                 selectedLotId.value = currentLotId;
//                 break;
//             }
//         }
//     }
// };

const availableClusters = computed(() => {
    if (!selectedPhaseId.value) return [];
    const phase = props.phases.find((p) => p.id == selectedPhaseId.value);
    return phase?.clusters || [];
});

const availableLots = computed(() => {
    if (!selectedClusterId.value) return [];
    const cluster = availableClusters.value.find(
        (c) => c.id == selectedClusterId.value,
    );
    return (
        cluster?.lots.filter(
            (lot) => !lot.is_occupied || lot.id == selectedLotId.value,
        ) || []
    );
});

const selectedLotColumn = computed(() => {
    if (!selectedLotId.value) return null;
    const lot = availableLots.value.find((l) => l.id == selectedLotId.value);
    return lot?.column || null;
});

const selectedLotRow = computed(() => {
    if (!selectedLotId.value) return null;
    const lot = availableLots.value.find((l) => l.id == selectedLotId.value);
    return lot?.row || null;
});

const selectedClusterType = computed(() => {
    if (!selectedClusterId.value) return null;
    const cluster = availableClusters.value.find(
        (c) => c.id == selectedClusterId.value,
    );
    return cluster?.cluster_type || null;
});

defineOptions({
    layout: Dashboard,
});

// added to close the modal from Clerk/Map/IndexView
onMounted(() => {
    // initializeMap(mapContainer.value);
    // initializeLocationSelections();

    document
        .querySelectorAll("#hs-scroll-inside-body-modal")
        .forEach((el) => HSOverlay.close(el));
});

onBeforeUnmount(() => {
    cleanupMap();
    document
        .querySelectorAll("#hs-scroll-inside-body-modal")
        .forEach((el) => HSOverlay.close(el));
});
</script>

<template>
    <Teleport to="body">
        <div
            id="hs-cookies"
            class="hs-overlay hidden size-full fixed top-0 start-0 z-2000 overflow-x-hidden overflow-y-auto bg-black/40 backdrop-blur-sm"
            role="dialog"
            tabindex="-1"
            aria-labelledby="hs-cookies-label"
        >
            <div
                class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto"
            >
                <div
                    class="relative w-full max-h-full flex flex-col bg-white/70 dark:bg-neutral-900/70 backdrop-blur-xl border border-white/20 dark:border-white/10 rounded-2xl shadow-lg shadow-gray-200/50 dark:shadow-black/50"
                >
                    <!-- Close button -->
                    <div class="absolute top-3 end-3">
                        <button
                            type="button"
                            class="size-8 inline-flex justify-center items-center rounded-full bg-white/40 dark:bg-neutral-800/40 backdrop-blur-md border border-white/20 dark:border-white/10 text-gray-700 dark:text-neutral-200 hover:bg-white/60 dark:hover:bg-neutral-700/60 transition"
                            data-hs-overlay="#hs-cookies"
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

                    <!-- Content -->
                    <div
                        class="p-10 flex flex-col items-center gap-y-4 text-center"
                    >
                        <div
                            class="flex items-center justify-center size-14 rounded-full bg-green-500/10 text-green-600 dark:text-green-400"
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
                                class="lucide lucide-circle-question-mark-icon lucide-circle-question-mark"
                            >
                                <circle cx="12" cy="12" r="10" />
                                <path
                                    d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"
                                />
                                <path d="M12 17h.01" />
                            </svg>
                        </div>

                        <h3
                            id="hs-cookies-label"
                            class="-mt-2 text-2xl font-bold text-green-600 dark:text-green-400"
                        >
                            Unsaved Changes
                        </h3>

                        <p class="text-gray-600 dark:text-neutral-300 max-w-sm">
                            Are you sure you want to discard your changes? This
                            action cannot be undone.
                        </p>
                    </div>

                    <!-- Buttons -->
                    <div
                        class="flex border-t border-white/20 dark:border-white/10"
                    >
                        <button
                            type="button"
                            class="w-full py-3 text-sm font-semibold text-green-600 dark:text-green-400 hover:bg-green-500/10 transition"
                            data-hs-overlay="#hs-cookies"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            class="w-full py-3 text-sm font-semibold text-red-500 hover:bg-red-500/10 transition"
                            @click="confirmDiscard"
                        >
                            Discard Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

    <div class="max-w-6xl mx-auto p-6">
        <!-- Header -->

        <button
            @click="back"
            class="flex items-center gap-1 mb-6 text-sm text-green-600 dark:text-green-400 hover:underline"
        >
            ← Back
        </button>
        <div class="mb-6 flex items-center justify-between">
            <div class="flex gap-x-3">
                <div
                    class="flex items-center justify-center size-13 rounded-full bg-green-500/10 text-green-600 dark:text-green-400"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="22"
                        height="22"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="lucide lucide-user-round-icon lucide-user-round"
                    >
                        <circle cx="12" cy="8" r="5" />
                        <path d="M20 21a8 8 0 0 0-16 0" />
                    </svg>
                </div>
                <article>
                    <h1
                        class="text-2xl font-bold text-green-600 dark:text-green-400"
                    >
                        {{ localData.deceased.full_name }}
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Deceased Record Details
                    </p>
                </article>
            </div>

            <div>
                <article
                    v-if="!editing"
                    class="flex space-x-3 items-center justify-center"
                >
                    <button
                        @click="redirectToClerkMap"
                        :disabled="
                            !burial_record.data.lot?.lot?.geometry?.coordinates
                                ?.length
                        "
                        :class="{
                            'opacity-50 cursor-not-allowed disabled:hover:dark:bg-neutral-800':
                                !burial_record.data.lot?.lot?.geometry
                                    ?.coordinates?.length,
                        }"
                        class="flex items-center justify-center gap-x-2 mt-4 px-4 py-2 rounded-xl border border-transparent dark:bg-neutral-800 hover:dark:bg-neutral-600 transition-all duration-200"
                    >
                        View on Map
                    </button>
                    <button
                        @click="editing = !editing"
                        class="flex items-center justify-center gap-x-2 px-4 mt-4 py-2 bg-green-500/10 text-green-400 rounded-xl border border-transparent hover:bg-green-500/20 hover:border-green-500/40 hover:text-green-600 dark:hover:text-green-300 transition-all duration-200"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="20"
                            height="20"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="lucide lucide-pencil-icon lucide-pencil"
                        >
                            <path
                                d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"
                            />
                            <path d="m15 5 4 4" />
                        </svg>
                        Edit
                    </button>

                    <button
                        @click="deleteBurialRecord"
                        class="flex items-center justify-center gap-x-2 mt-4 px-4 py-2 rounded-xl border border-transparent bg-red-500/10 text-red-500 hover:bg-red-500/20 hover:border-red-500/40 transition-all duration-200"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="20"
                            height="20"
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
                        Delete
                    </button>
                </article>
                <div class="flex gap-x-3">
                    <button
                        v-if="editing"
                        :class="{
                            'opacity-50 cursor-not-allowed': !hasChanges,
                        }"
                        @click="saveChanges"
                        class="flex items-center justify-center gap-x-2 mt-4 px-4 py-2 bg-green-500/10 text-green-400 rounded-xl border border-transparent hover:bg-green-500/20 hover:border-green-500/40 hover:text-green-600 dark:hover:text-green-300 transition-all duration-200"
                    >
                        Save Changes
                    </button>
                    <button
                        v-if="editing"
                        @click="discardChanges"
                        class="flex items-center justify-center gap-x-2 mt-4 px-4 py-2 rounded-xl border border-transparent dark:bg-neutral-800 hover:dark:bg-neutral-600 transition-all duration-200"
                    >
                        Discard
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex border-b border-gray-200 dark:border-neutral-700 mb-6">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                @click="activeTab = tab.key"
                class="px-4 py-2 text-sm font-medium transition"
                :class="
                    activeTab === tab.key
                        ? 'border-b-2 border-green-500 text-green-600 dark:text-green-400'
                        : 'text-gray-500 dark:text-gray-400 hover:text-green-500'
                "
            >
                {{ tab.label }}
            </button>
        </div>

        <!-- Card Container -->
        <div
            class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-700 rounded-xl shadow-md p-6 transition"
        >
            <!-- PERSONAL -->
            <div
                v-if="activeTab === 'personal'"
                class="grid grid-cols-1 md:grid-cols-2 gap-6"
            >
                <Display
                    label="First Name"
                    :modelValue="localData.deceased?.first_name"
                    :editing="editing"
                    :error="errors['deceased.first_name']"
                    @update:modelValue="
                        (val) => (localData.deceased.first_name = val)
                    "
                />

                <Display
                    label="Middle Name"
                    :modelValue="localData.deceased?.middle_name"
                    :editing="editing"
                    :error="errors['deceased.middle_name']"
                    @update:modelValue="
                        (val) => (localData.deceased.middle_name = val)
                    "
                />
                <Display
                    label="Last Name"
                    :modelValue="localData.deceased?.last_name"
                    :editing="editing"
                    :error="errors['deceased.last_name']"
                    @update:modelValue="
                        (val) => (localData.deceased.last_name = val)
                    "
                />
                <Display
                    label="Age"
                    :modelValue="localData.deceased?.age"
                    :editing="editing"
                    :error="errors['deceased.age']"
                    @update:modelValue="(val) => (localData.deceased.age = val)"
                />
                <Display
                    label="Date of Birth"
                    :modelValue="localData.deceased?.birth?.date"
                    :editing="editing"
                    :error="errors['deceased.birth.date']"
                    @update:modelValue="
                        (val) => (localData.deceased.birth.date = val)
                    "
                />
                <Display
                    label="Civil Status"
                    :modelValue="localData.deceased?.civil_status"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.civil_status = val)
                    "
                />
                <Display
                    label="Religion"
                    :modelValue="localData.deceased?.religion"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.religion = val)
                    "
                />
                <Display
                    label="Nationality"
                    :modelValue="localData.deceased?.nationality"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.nationality = val)
                    "
                />
                <Display
                    label="Occupation"
                    :modelValue="localData.deceased?.occupation?.name"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.occupation.name = val)
                    "
                />
                <Display
                    label="Address"
                    :modelValue="localData.deceased?.address"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.address = val)
                    "
                />
                <Display
                    label="Part of LGBTQ"
                    :modelValue="localData.deceased?.lgbtq"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.lgbtq = val)
                    "
                />
                <Display
                    label="Precinct Number"
                    :modelValue="localData.deceased?.precinct_num"
                    :editing="editing"
                    :error="errors['deceased.precinct_num']"
                    @update:modelValue="
                        (val) => (localData.deceased.precinct_num = val)
                    "
                />
            </div>

            <!-- DEATH -->
            <div
                v-if="activeTab === 'death'"
                class="grid grid-cols-1 md:grid-cols-2 gap-6"
            >
                <Display
                    label="Date of Death"
                    :modelValue="localData.deceased?.death?.date"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.death.date = val)
                    "
                />
                <Display
                    label="Cause of Death"
                    :modelValue="localData.deceased?.death?.cause"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.death.cause = val)
                    "
                />
                <Display
                    label="Place of Death"
                    :modelValue="localData.deceased?.death?.place"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.death.place = val)
                    "
                />
            </div>

            <!-- DISPOSITION -->
            <div
                v-if="activeTab === 'disposition'"
                class="grid grid-cols-1 md:grid-cols-2 gap-6"
            >
                <Display
                    label="Corpse Disposal"
                    :modelValue="localData.deceased?.corpse_disposal"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.corpse_disposal = val)
                    "
                />
                <Display
                    label="Cremation Place"
                    :modelValue="localData.deceased?.cremation?.place"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.cremation.place = val)
                    "
                />
                <Display
                    label="Cremation Date"
                    :modelValue="localData.deceased?.cremation?.date"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.cremation.date = val)
                    "
                />
                <Display
                    label="Burial Place"
                    :modelValue="localData.deceased?.burial_place"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.burial_place = val)
                    "
                />

                <Display
                    label="Date of Depository"
                    placeholder="YYYY-MM-DD"
                    :modelValue="localData.deceased?.burial?.date"
                    :editing="editing"
                    :error="errors['deceased.burial.date']"
                    @update:modelValue="
                        (val) => (localData.deceased.burial.date = val)
                    "
                />
            </div>

            <!-- FAMILY -->
            <div
                v-if="activeTab === 'family'"
                class="grid grid-cols-1 md:grid-cols-2 gap-6"
            >
                <Display
                    label="Father's Name"
                    :modelValue="localData.deceased?.family?.father"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.family.father = val)
                    "
                />
                <Display
                    label="Mother's Maiden Name"
                    :modelValue="localData.deceased?.family?.mother_maiden"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.family.mother_maiden = val)
                    "
                />
                <Display
                    label="Company Address"
                    :modelValue="localData.deceased?.occupation?.address"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.occupation.address = val)
                    "
                />
                <Display
                    label="Company Supervisor Name"
                    :modelValue="localData.deceased?.occupation?.supervisor"
                    :editing="editing"
                    @update:modelValue="
                        (val) =>
                            (localData.deceased.occupation.supervisor = val)
                    "
                />
            </div>

            <!-- APPLICANT -->
            <div
                v-if="activeTab === 'applicant'"
                class="grid grid-cols-1 md:grid-cols-2 gap-6"
            >
                <Display
                    label="Applicant First Name"
                    :modelValue="localData.deceased?.applicant?.first_name"
                    :editing="editing"
                    :error="errors['deceased.applicant.first_name']"
                    @update:modelValue="
                        (val) => (localData.deceased.applicant.first_name = val)
                    "
                />
                <Display
                    label="Applicant Middle Name"
                    :modelValue="localData.deceased?.applicant?.middle_name"
                    :editing="editing"
                    :error="errors['deceased.applicant.middle_name']"
                    @update:modelValue="
                        (val) =>
                            (localData.deceased.applicant.middle_name = val)
                    "
                />
                <Display
                    label="Applicant Last Name"
                    :modelValue="localData.deceased?.applicant?.last_name"
                    :editing="editing"
                    :error="errors['deceased.applicant.last_name']"
                    @update:modelValue="
                        (val) => (localData.deceased.applicant.last_name = val)
                    "
                />
                <Display
                    label="Contact Number"
                    :modelValue="localData.deceased?.applicant?.contact_number"
                    :editing="editing"
                    :error="errors['deceased.applicant.contact_number']"
                    @update:modelValue="
                        (val) =>
                            (localData.deceased.applicant.contact_number = val)
                    "
                />
            </div>

            <!-- LOCATION -->
            <div
                v-if="activeTab === 'location'"
                class="grid grid-cols-1 md:grid-cols-2 gap-6"
            >
                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Phase
                    </label>
                    <select
                        v-if="editing"
                        v-model="selectedPhaseId"
                        :class="{
                            'border-red-500 focus:ring-red-500':
                                errors['lot_id'],
                        }"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-green-500"
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
                    <p v-else class="text-gray-900 dark:text-gray-100">
                        {{
                            localData.cluster?.cluster?.properties?.phase ||
                            "N/A"
                        }}
                    </p>
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Cluster
                    </label>
                    <select
                        v-if="editing"
                        v-model="selectedClusterId"
                        :disabled="!selectedPhaseId"
                        :class="{
                            'border-red-500 focus:ring-red-500':
                                errors['lot_id'],
                        }"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50"
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
                    <p v-else class="text-gray-900 dark:text-gray-100">
                        {{
                            localData.cluster?.cluster?.properties?.name ||
                            "N/A"
                        }}
                    </p>
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Lot
                    </label>
                    <select
                        v-if="editing"
                        v-model="selectedLotId"
                        :disabled="!selectedClusterId"
                        :class="{
                            'border-red-500 focus:ring-red-500':
                                errors['lot_id'],
                        }"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50"
                    >
                        <option value="">Select a lot</option>
                        <option
                            v-for="lot in availableLots"
                            :key="lot.id"
                            :value="lot.id"
                        >
                            {{ lot.column }} - {{ lot.row }}
                        </option>
                    </select>
                    <p v-else class="text-gray-900 dark:text-gray-100">
                        {{ localData.lot?.lot?.properties?.column }} -
                        {{ localData.lot?.lot?.properties?.row || "N/A" }}
                    </p>
                    <p
                        v-if="editing && errors['lot_id']"
                        class="mt-1 text-sm text-red-500"
                    >
                        {{ errors["lot_id"] }}
                    </p>
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Cluster Type
                    </label>
                    <p class="text-gray-900 dark:text-gray-100">
                        {{
                            editing
                                ? selectedClusterType ||
                                  "Select a cluster first"
                                : localData.cluster?.cluster?.properties
                                      ?.type || "N/A"
                        }}
                    </p>
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Column
                    </label>
                    <p class="text-gray-900 dark:text-gray-100">
                        {{
                            editing
                                ? selectedLotColumn || "Select a lot first"
                                : localData.lot?.lot?.properties?.column ||
                                  "N/A"
                        }}
                    </p>
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Row
                    </label>
                    <p class="text-gray-900 dark:text-gray-100">
                        {{
                            editing
                                ? selectedLotRow || "Select a lot first"
                                : localData.lot?.lot?.properties?.row || "N/A"
                        }}
                    </p>
                </div>
            </div>

            <!-- IMPORTED BY -->
            <div
                v-if="activeTab === 'imported'"
                class="grid grid-cols-1 md:grid-cols-2 gap-6"
            >
                <Display
                    label="Imported/Created By"
                    :modelValue="
                        burial_record.data.imported_by
                            ? burial_record.data.imported_by.full_name
                            : 'N/A'
                    "
                    :editing="false"
                />

                <Display
                    label="Role"
                    :modelValue="
                        burial_record.data.imported_by
                            ? burial_record.data.imported_by.role
                            : 'N/A'
                    "
                    :editing="false"
                />
            </div>
        </div>
    </div>
</template>
