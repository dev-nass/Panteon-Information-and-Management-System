<script setup>
import { onMounted, ref, watch } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import { has, isEqual } from "lodash";

import Display from "@/Components/Display.vue";
import Dashboard from "@/Layouts/Dashboard.vue";

const props = defineProps({
    burial_record: { type: Object, required: true },
});

console.log(props.burial_record);

const activeTab = ref("personal");
const tabs = [
    { key: "personal", label: "Personal Info" },
    { key: "death", label: "Death Info" },
    { key: "disposition", label: "Disposition" },
    { key: "family", label: "Family & Company" },
];

const back = () => {
    window.history.back();
};

const editing = ref(false);
const hasChanges = ref(false);

// deep copy original data
const originalData = ref(JSON.parse(JSON.stringify(props.burial_record.data)));
const localData = ref(JSON.parse(JSON.stringify(originalData.value)));
console.log(localData.value);

watch(
    localData,
    (newVal) => {
        hasChanges.value = !isEqual(newVal, originalData.value);
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
    hasChanges.value = false;
    editing.value = false;

    HSOverlay.close("#hs-cookies");
};

const saveChanges = () => {
    alert("saving...");
};

defineOptions({
    layout: Dashboard,
});

// added to close the modal from Clerk/Map/IndexView
onMounted(() => {
    document
        .querySelectorAll("#hs-scroll-inside-body-modal")
        .forEach((el) => HSOverlay.close(el));
});
</script>

<template>
    <Teleport to="body">
        <div
            id="hs-cookies"
            class="hs-overlay hidden size-full fixed top-0 start-0 z-[2000] overflow-x-hidden overflow-y-auto bg-black/40 backdrop-blur-sm"
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
                <button
                    v-if="!editing"
                    @click="editing = !editing"
                    class="flex items-center justify-center gap-x-2 mt-4 px-4 py-2 bg-green-500/10 text-green-400 rounded-xl border border-transparent hover:bg-green-500/20 hover:border-green-500/40 hover:text-green-600 dark:hover:text-green-300 transition-all duration-200"
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
                    @update:modelValue="
                        (val) => (localData.deceased.first_name = val)
                    "
                />

                <Display
                    label="Middle Name"
                    :modelValue="localData.deceased?.middle_name"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.middle_name = val)
                    "
                />
                <Display
                    label="Last Name"
                    :modelValue="localData.deceased?.last_name"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.last_name = val)
                    "
                />
                <Display
                    label="Age"
                    :modelValue="localData.deceased?.age"
                    :editing="editing"
                    @update:modelValue="(val) => (localData.deceased.age = val)"
                />
                <Display
                    label="Date of Birth"
                    :modelValue="localData.deceased?.birth?.date"
                    :editing="editing"
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
                    :modelValue="localData.deceased?.burial?.date"
                    :editing="editing"
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
        </div>
    </div>
</template>
