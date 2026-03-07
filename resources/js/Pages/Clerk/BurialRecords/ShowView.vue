<script setup>
import { ref } from "vue";
import { usePage, router } from "@inertiajs/vue3";

import Display from "@/Components/Display.vue";
import Dashboard from "@/Layouts/Dashboard.vue";

const props = defineProps({
    burial_record: { type: Object, required: true },
});

console.log(props.burial_record);

const editing = ref(false);
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

defineOptions({
    layout: Dashboard,
});
</script>

<template>
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
                        {{ burial_record.data.deceased.full_name }}
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
                        @click="editing = !editing"
                        class="flex items-center justify-center gap-x-2 mt-4 px-4 py-2 bg-green-500/10 text-green-400 rounded-xl border border-transparent hover:bg-green-500/20 hover:border-green-500/40 hover:text-green-600 dark:hover:text-green-300 transition-all duration-200"
                    >
                        Save Changes
                    </button>
                    <button
                        v-if="editing"
                        @click="editing = !editing"
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
                    label="Age"
                    :value="burial_record.data.deceased?.age"
                    :editing="editing"
                />
                <Display
                    label="Date of Birth"
                    :value="burial_record.data.deceased?.birth?.date"
                    :editing="editing"
                />
                <Display
                    label="Civil Status"
                    :value="burial_record.data.deceased?.civil_status"
                    :editing="editing"
                />
                <Display
                    label="Religion"
                    :value="burial_record.data.deceased?.religion"
                    :editing="editing"
                />
                <Display
                    label="Nationality"
                    :value="burial_record.data.deceased?.nationality"
                    :editing="editing"
                />
                <Display
                    label="Occupation"
                    :value="burial_record.data.deceased?.occupation?.name"
                    :editing="editing"
                />
                <Display
                    label="Address"
                    :value="burial_record.data.deceased?.address"
                    :editing="editing"
                />
                <Display
                    label="Part of LGBTQ"
                    :value="burial_record.data.deceased?.lgbtq"
                    :editing="editing"
                />
                <Display
                    label="Precinct Number"
                    :value="burial_record.data.deceased?.precinct_num"
                    :editing="editing"
                />
            </div>

            <!-- DEATH -->
            <div
                v-if="activeTab === 'death'"
                class="grid grid-cols-1 md:grid-cols-2 gap-6"
            >
                <Display
                    label="Date of Death"
                    :value="burial_record.data.deceased?.death?.date"
                    :editing="editing"
                />
                <Display
                    label="Cause of Death"
                    :value="burial_record.data.deceased?.death?.cause"
                    :editing="editing"
                />
                <Display
                    label="Place of Death"
                    :value="burial_record.data.deceased?.death?.place"
                    :editing="editing"
                />
            </div>

            <!-- DISPOSITION -->
            <div
                v-if="activeTab === 'disposition'"
                class="grid grid-cols-1 md:grid-cols-2 gap-6"
            >
                <Display
                    label="Corpse Disposal"
                    :value="burial_record.data.deceased?.corpse_disposal"
                    :editing="editing"
                />
                <Display
                    label="Cremation Place"
                    :value="burial_record.data.deceased?.cremation?.place"
                    :editing="editing"
                />
                <Display
                    label="Cremation Date"
                    :value="burial_record.data.deceased?.cremation?.date"
                    :editing="editing"
                />
                <Display
                    label="Burial Place"
                    :value="burial_record.data.deceased?.burial_place"
                    :editing="editing"
                />
                <Display
                    label="Date of Depository"
                    :value="burial_record.data.deceased?.burial?.date"
                    :editing="editing"
                />
            </div>

            <!-- FAMILY -->
            <div
                v-if="activeTab === 'family'"
                class="grid grid-cols-1 md:grid-cols-2 gap-6"
            >
                <Display
                    label="Father's Name"
                    :value="burial_record.data.deceased?.family?.father"
                    :editing="editing"
                />
                <Display
                    label="Mother's Maiden Name"
                    :value="burial_record.data.deceased?.family?.mother_maiden"
                    :editing="editing"
                />
                <Display
                    label="Company Address"
                    :value="burial_record.data.deceased?.occupation?.address"
                    :editing="editing"
                />
                <Display
                    label="Company Supervisor Name"
                    :value="burial_record.data.deceased?.occupation?.supervisor"
                    :editing="editing"
                />
            </div>
        </div>
    </div>
</template>
