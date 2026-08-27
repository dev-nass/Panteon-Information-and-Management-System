<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { useToast } from "vue-toast-notification";

import Input from "@/Components/Form/Input.vue";
import Button from "@/Components/Form/Button.vue";
import Dashboard from "@/Layouts/Dashboard.vue";

const props = defineProps({
    phases: Array,
});

const toast = useToast();

const barangays = ref([]);
const barangayNames = computed(() =>
    barangays.value.map((b) => b.name).sort(),
);

const activeTab = ref("personal");
const tabs = [
    { key: "personal", label: "Personal Info" },
    { key: "death", label: "Death Info" },
    { key: "disposition", label: "Disposition" },
    { key: "family", label: "Family & Company" },
    { key: "applicant", label: "Applicant" },
    { key: "location", label: "Location" },
];

const burialTypes = {
    burial: { label: "Normal Burial", badge: "bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-500" },
    muslim: { label: "Muslim Burial", badge: "bg-emerald-100 text-emerald-800 dark:bg-emerald-800/30 dark:text-emerald-500" },
    cremation: { label: "Cremation", badge: "bg-amber-100 text-amber-800 dark:bg-amber-800/30 dark:text-amber-500" },
};

const burialTypeOptions = Object.entries(burialTypes).map(
    ([value, { label }]) => ({ value, label }),
);

const selectedType = ref(
    ["burial", "muslim", "cremation"].includes(route().params.type)
        ? route().params.type
        : "burial",
);

const form = useForm({
    // Personal Info
    first_name: "",
    middle_name: "",
    last_name: "",
    age: "",
    birth_date: "",
    civil_status: "",
    religion: "",
    nationality: "",
    occupation_name: "",
    address: "",
    otherAddress: "",
    lgbtq: "",
    precinct_num: "",

    // Death Info
    death_date: "",
    death_cause: "",
    death_place: "",

    // Disposition
    corpse_disposal: selectedType.value,
    cremation_place: "",
    cremation_date: "",
    burial_place: "",
    burial_date: "",
    burial_time: "",

    // Family & Company
    father_name: "",
    mother_maiden_name: "",
    company_address: "",
    company_supervisor: "",

    // Applicant
    applicant_first_name: "",
    applicant_middle_name: "",
    applicant_last_name: "",
    applicant_contact_number: "",
    applicant_relationship: "",

    // Location
    lot_id: "",
});

const isCremation = computed(
    () => form.corpse_disposal === "cremation",
);

const filteredPhases = computed(() => {
    if (form.corpse_disposal === "cremation") {
        return props.phases.filter((phase) =>
            phase.name.toLowerCase().includes("clbm"),
        );
    }

    return props.phases.filter(
        (phase) => !phase.name.toLowerCase().includes("clbm"),
    );
});

watch(
    () => form.corpse_disposal,
    () => {
        if (isCremation.value) {
            const columbariumPhase = filteredPhases.value[0];

            if (columbariumPhase) {
                handlePhaseChange(columbariumPhase.id);
                return;
            }
        }

        handlePhaseChange("");
    },
);

onMounted(async () => {
    try {
        const res = await fetch(route("api.barangays"));
        barangays.value = await res.json();
    } catch {
        barangays.value = [];
    }

    const modal = document.getElementById("burial-type-modal");

    if (modal) {
        if (typeof HSOverlay !== "undefined" && window.$hsOverlayCollection) {
            HSOverlay.close(modal);
        } else {
            modal.classList.remove("open", "opened");
            modal.classList.add("hidden");
        }
    }

    document.body.style.overflow = "";
});

const burialTypeLabel = computed(
    () => burialTypes[form.corpse_disposal]?.label ?? "Normal Burial",
);

const burialTypeBadge = computed(
    () =>
        burialTypes[form.corpse_disposal]?.badge ??
        burialTypes.burial.badge,
);

const selectedPhase = ref(null);
const availableClusters = ref([]);
const selectedPhaseForLot = ref("");
const selectedClusterId = ref("");
const availableLots = ref([]);

const handlePhaseChange = (phaseId) => {
    selectedPhaseForLot.value = phaseId;
    const phase = props.phases.find((p) => p.id == phaseId);
    selectedPhase.value = phase;
    availableClusters.value = phase?.clusters || [];
    selectedClusterId.value = "";
    form.lot_id = "";
    availableLots.value = [];
};

const handleClusterChange = (clusterId) => {
    selectedClusterId.value = clusterId;
    const cluster = availableClusters.value.find((c) => c.id == clusterId);
    availableLots.value =
        cluster?.lots?.filter((lot) => !lot.is_occupied) || [];
    form.lot_id = "";
};

const submitForm = () => {
    if (form.address === "Other") {
        form.address = form.otherAddress;
    }

    form.post(route("clerk.burial_records.store"), {
        onSuccess: () => {
            form.reset();
            toast.success("Burial record created successfully!");
        },
        onError: (errors) => {
            if (errors.first_name?.includes("already exists")) {
                toast.error(errors.first_name, { duration: 8000 });
            } else {
                toast.error("Please fix the validation errors before submitting.");
            }
        },
    });
};

const goBack = () => {
    router.visit(route("clerk.burial_records.index"));
};

defineOptions({
    layout: Dashboard,
});
</script>

<template>
    <div class="max-w-[55rem] px-4 py-10 mx-auto">
        <div
            class="bg-white dark:bg-neutral-800 rounded-xl shadow overflow-hidden"
        >
            <!-- HEADER -->
            <div
                class="px-6 py-4 flex justify-between items-center border-b border-gray-200 dark:border-neutral-700"
            >
                                <div class="flex flex-col gap-1.5">
                    <h2
                        class="text-xl font-semibold text-gray-800 dark:text-gray-200"
                    >
                        Create Burial Record
                    </h2>
                    <span
                        class="inline-flex items-center gap-x-1.5 py-1 px-3 rounded-full text-xs font-medium self-start"
                        :class="burialTypeBadge"
                    >
                        {{ burialTypeLabel }}
                    </span>
                </div>

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
                        v-for="tab in tabs"
                        :key="tab.key"
                        @click="activeTab = tab.key"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition"
                        :class="
                            activeTab === tab.key
                                ? 'bg-green-500/20 text-green-400'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-green-500/10'
                        "
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </div>

            <!-- FORMS -->
            <form @submit.prevent="submitForm" class="px-6 py-6">
                <!-- PERSONAL INFO -->
                <div
                    v-if="activeTab === 'personal'"
                    class="grid grid-cols-1 md:grid-cols-2 gap-4"
                >
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            First Name
                        </label>
                        <Input
                            v-model="form.first_name"
                            placeholder="Enter first name"
                            required
                        />
                        <span
                            v-if="form.errors.first_name"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.first_name }}
                        </span>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Middle Name
                        </label>
                        <Input
                            v-model="form.middle_name"
                            placeholder="Enter middle name"
                        />
                        <span
                            v-if="form.errors.middle_name"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.middle_name }}
                        </span>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Last Name
                        </label>
                        <Input
                            v-model="form.last_name"
                            placeholder="Enter last name"
                            required
                        />
                        <span
                            v-if="form.errors.last_name"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.last_name }}
                        </span>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Age
                        </label>
                        <Input
                            v-model="form.age"
                            type="number"
                            placeholder="Enter age"
                            required
                        />
                        <span
                            v-if="form.errors.age"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.age }}
                        </span>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Date of Birth
                        </label>
                        <Input
                            v-model="form.birth_date"
                            type="date"
                            :required="isCremation"
                        />
                        <span
                            v-if="form.errors.birth_date"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.birth_date }}
                        </span>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Civil Status
                        </label>
                        <Input
                            v-model="form.civil_status"
                            placeholder="Enter civil status"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Religion
                        </label>
                        <Input
                            v-model="form.religion"
                            placeholder="Enter religion"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Nationality
                        </label>
                        <Input
                            v-model="form.nationality"
                            placeholder="Enter nationality"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Occupation
                        </label>
                        <Input
                            v-model="form.occupation_name"
                            placeholder="Enter occupation"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Barangay
                        </label>
                        <select
                            v-model="form.address"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-green-500"
                            required
                        >
                            <option value="" disabled>Select barangay</option>
                            <option
                                v-for="name in barangayNames"
                                :key="name"
                                :value="name"
                            >
                                {{ name }}
                            </option>
                            <option value="Other">Other</option>
                        </select>
                        <span
                            v-if="form.errors.address"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.address }}
                        </span>
                    </div>

                    <div v-if="form.address === 'Other'" class="md:col-span-2">
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Address (Other)
                        </label>
                        <Input
                            v-model="form.otherAddress"
                            placeholder="Enter address"
                            required
                        />
                        <span
                            v-if="form.errors.otherAddress"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.otherAddress }}
                        </span>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Part of LGBTQ
                        </label>
                        <Input v-model="form.lgbtq" placeholder="Yes/No" />
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Precinct Number
                        </label>
                        <Input
                            v-model="form.precinct_num"
                            placeholder="Enter precinct number"
                            required
                        />
                        <span
                            v-if="form.errors.precinct_num"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.precinct_num }}
                        </span>
                    </div>
                </div>

                <!-- DEATH INFO -->
                <div
                    v-if="activeTab === 'death'"
                    class="grid grid-cols-1 md:grid-cols-2 gap-4"
                >
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Date of Death
                        </label>
                        <Input v-model="form.death_date" type="date" required />
                        <span
                            v-if="form.errors.death_date"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.death_date }}
                        </span>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Cause of Death
                        </label>
                        <Input
                            v-model="form.death_cause"
                            placeholder="Enter cause of death"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Place of Death
                        </label>
                        <Input
                            v-model="form.death_place"
                            placeholder="Enter place of death"
                        />
                    </div>
                </div>

                <!-- DISPOSITION -->
                <div
                    v-if="activeTab === 'disposition'"
                    class="grid grid-cols-1 md:grid-cols-2 gap-4"
                >
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Corpse Disposal
                        </label>
                        <select
                            v-model="form.corpse_disposal"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-green-500"
                            required
                        >
                            <option
                                v-for="option in burialTypeOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                        <span
                            v-if="form.errors.corpse_disposal"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.corpse_disposal }}
                        </span>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Cremation Place
                        </label>
                        <Input
                            v-model="form.cremation_place"
                            placeholder="Enter cremation place"
                            :required="isCremation"
                        />
                        <span
                            v-if="form.errors.cremation_place"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.cremation_place }}
                        </span>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Cremation Date
                        </label>
                        <Input
                            v-model="form.cremation_date"
                            type="date"
                            :required="isCremation"
                        />
                        <span
                            v-if="form.errors.cremation_date"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.cremation_date }}
                        </span>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Burial Place
                        </label>
                        <Input
                            v-model="form.burial_place"
                            placeholder="Enter burial place"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Date of Depository
                        </label>
                        <Input
                            v-model="form.burial_date"
                            type="date"
                            required
                        />
                        <span
                            v-if="form.errors.burial_date"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.burial_date }}
                        </span>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Time of Depository
                        </label>
                        <Input
                            v-model="form.burial_time"
                            type="time"
                            required
                        />
                        <span
                            v-if="form.errors.burial_time"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.burial_time }}
                        </span>
                    </div>
                </div>

                <!-- FAMILY & COMPANY -->
                <div
                    v-if="activeTab === 'family'"
                    class="grid grid-cols-1 md:grid-cols-2 gap-4"
                >
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Father's Name
                        </label>
                        <Input
                            v-model="form.father_name"
                            placeholder="Enter father's name"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Mother's Maiden Name
                        </label>
                        <Input
                            v-model="form.mother_maiden_name"
                            placeholder="Enter mother's maiden name"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Company Address
                        </label>
                        <Input
                            v-model="form.company_address"
                            placeholder="Enter company address"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Company Supervisor Name
                        </label>
                        <Input
                            v-model="form.company_supervisor"
                            placeholder="Enter supervisor name"
                        />
                    </div>
                </div>

                <!-- APPLICANT -->
                <div
                    v-if="activeTab === 'applicant'"
                    class="grid grid-cols-1 md:grid-cols-2 gap-4"
                >
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Applicant First Name
                        </label>
                        <Input
                            v-model="form.applicant_first_name"
                            placeholder="Enter applicant first name"
                            required
                        />
                        <span
                            v-if="form.errors.applicant_first_name"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.applicant_first_name }}
                        </span>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Applicant Middle Name
                        </label>
                        <Input
                            v-model="form.applicant_middle_name"
                            placeholder="Enter applicant middle name"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Applicant Last Name
                        </label>
                        <Input
                            v-model="form.applicant_last_name"
                            placeholder="Enter applicant last name"
                            required
                        />
                        <span
                            v-if="form.errors.applicant_last_name"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.applicant_last_name }}
                        </span>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Contact Number
                        </label>
                        <Input
                            v-model="form.applicant_contact_number"
                            placeholder="09XXXXXXXXX"
                            required
                        />
                        <span
                            v-if="form.errors.applicant_contact_number"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.applicant_contact_number }}
                        </span>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Relationship to Deceased
                        </label>
                        <Input
                            v-model="form.applicant_relationship"
                            placeholder="e.g. Son, Daughter, Spouse"
                        />
                    </div>
                </div>

                <!-- LOCATION -->
                <div
                    v-if="activeTab === 'location'"
                    class="grid grid-cols-1 md:grid-cols-2 gap-4"
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
                                v-for="phase in filteredPhases"
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
                            v-model="selectedClusterId"
                            @change="handleClusterChange(selectedClusterId)"
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
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Select Lot
                        </label>
                        <select
                            v-model="form.lot_id"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-green-500"
                            :disabled="!selectedClusterId"
                            required
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
                        <span
                            v-if="form.errors.lot_id"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.lot_id }}
                        </span>
                    </div>
                </div>

                <!-- SUBMIT BUTTON -->
                <div class="mt-6">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-green-500/10 text-green-400 hover:bg-green-500/20"
                    >
                        Create Burial Record
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
