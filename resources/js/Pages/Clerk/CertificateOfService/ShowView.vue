<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";

import Input from "@/Components/Form/Input.vue";
import Button from "@/Components/Form/Button.vue";
import Dashboard from "@/Layouts/Dashboard.vue";

const props = defineProps({
    burial_record_id: { type: Number, required: true },
    prefilled: { type: Object, required: true },
    csrf_token: { type: String, required: true },
    templates: { type: Array, default: () => [] },
    is_columbarium: { type: Boolean, default: false },
});

const form = ref({
    deceased_name: props.prefilled.deceased_name ?? "",
    deceased_address: props.prefilled.deceased_address ?? "",
    date_of_death: props.prefilled.date_of_death ?? "",
    place_of_death: props.prefilled.place_of_death ?? "",
    date_of_depository: props.prefilled.date_of_depository ?? "",
    burial_place: props.prefilled.burial_place ?? "",
    cremation_place: props.prefilled.cremation_place ?? "",
    applicant_name: props.prefilled.applicant_name ?? "",
    applicant_address: "",
    relationship: props.prefilled.relationship ?? "",
});

const templateId = ref(null);
const errors = ref({});

const requiredFields = computed(() => {
    const fields = [
        "deceased_name",
        "deceased_address",
        "date_of_death",
        "place_of_death",
        "date_of_depository",
        "applicant_name",
        "applicant_address",
        "relationship",
    ];
    if (props.is_columbarium) {
        fields.push("cremation_place");
    } else {
        fields.push("burial_place");
    }
    return fields;
});

const fieldLabels = {
    deceased_name: "Deceased name",
    deceased_address: "Deceased address",
    date_of_death: "Date of death",
    place_of_death: "Place of death",
    date_of_depository: "Date of depository",
    burial_place: "Burial place",
    cremation_place: "Cremation place",
    applicant_name: "Applicant name",
    applicant_address: "Applicant address",
    relationship: "Relationship to deceased",
};

const goBack = () => {
    router.visit(route("clerk.burial_records.show", props.burial_record_id));
};

const generate = async () => {
    errors.value = {};

    for (const field of requiredFields.value) {
        if (!form.value[field]) {
            errors.value[field] = `${fieldLabels[field]} is required.`;
        }
    }

    if (Object.keys(errors.value).length) return;

    const f = document.createElement("form");
    f.method = "POST";
    f.action = route(
        "clerk.certificate_of_service.generate",
        props.burial_record_id,
    );

    const fields = {
        ...form.value,
        _token: props.csrf_token,
        template_id: templateId.value ?? "",
    };

    for (const [key, value] of Object.entries(fields)) {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = key;
        input.value = value ?? "";
        f.appendChild(input);
    }

    document.body.appendChild(f);
    f.submit();
    document.body.removeChild(f);
};

defineOptions({ layout: Dashboard });
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
                <h2
                    class="text-xl font-semibold text-gray-800 dark:text-gray-200"
                >
                    Certificate of Service
                </h2>
                <div class="flex items-center gap-3">
                    <span
                        :class="
                            is_columbarium
                                ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400'
                                : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                        "
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    >
                        {{
                            is_columbarium
                                ? "Cremation / Columbarium"
                                : "Normal Burial"
                        }}
                    </span>
                    <Button @click="goBack">Back</Button>
                </div>
            </div>

            <!-- FORM -->
            <div class="px-6 py-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Certificate Template
                    </label>
                    <select
                        v-model="templateId"
                        class="w-full px-3 py-2.5 text-sm rounded-lg border border-gray-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 text-gray-800 dark:text-gray-200 focus:border-green-500 focus:ring-2 focus:ring-green-500 outline-none"
                    >
                        <option :value="null">Plain (text-only PDF)</option>
                        <option
                            v-for="template in templates"
                            :key="template.id"
                            :value="template.id"
                        >
                            {{ template.name }}
                        </option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Choose an uploaded template to use as the background, or
                        keep "Plain" for no background.
                    </p>
                    <span
                        v-if="errors.template_id"
                        class="block mt-1 text-red-500 text-sm"
                    >
                        {{ errors.template_id }}
                    </span>
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Deceased Name
                        <span class="text-red-400">*</span>
                    </label>
                    <Input
                        v-model="form.deceased_name"
                        placeholder="Deceased full name"
                    />
                    <span
                        v-if="errors.deceased_name"
                        class="text-red-500 text-sm"
                    >
                        {{ errors.deceased_name }}
                    </span>
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Deceased Address
                        <span class="text-red-400">*</span>
                    </label>
                    <Input
                        v-model="form.deceased_address"
                        placeholder="Brgy. Sampaloc III, City of Dasmariñas, Cavite"
                    />
                    <span
                        v-if="errors.deceased_address"
                        class="text-red-500 text-sm"
                    >
                        {{ errors.deceased_address }}
                    </span>
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Date of Death
                        <span class="text-red-400">*</span>
                    </label>
                    <Input v-model="form.date_of_death" type="date" />
                    <span
                        v-if="errors.date_of_death"
                        class="text-red-500 text-sm"
                    >
                        {{ errors.date_of_death }}
                    </span>
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Place of Death
                        <span class="text-red-400">*</span>
                    </label>
                    <Input
                        v-model="form.place_of_death"
                        placeholder="Pagamutan ng Dasmariñas Brgy. Burol II, City of Dasmariñas, Cavite"
                    />
                    <span
                        v-if="errors.place_of_death"
                        class="text-red-500 text-sm"
                    >
                        {{ errors.place_of_death }}
                    </span>
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Date of Depository
                        <span class="text-red-400">*</span>
                    </label>
                    <Input v-model="form.date_of_depository" type="date" />
                    <span
                        v-if="errors.date_of_depository"
                        class="text-red-500 text-sm"
                    >
                        {{ errors.date_of_depository }}
                    </span>
                </div>

                <div v-if="!is_columbarium">
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Burial Place
                        <span class="text-red-400">*</span>
                    </label>
                    <Input
                        v-model="form.burial_place"
                        placeholder="e.g. Panteon De Dasmariñas Extension"
                    />
                    <span
                        v-if="errors.burial_place"
                        class="text-red-500 text-sm"
                    >
                        {{ errors.burial_place }}
                    </span>
                </div>

                <div v-else>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Cremation Place
                        <span class="text-red-400">*</span>
                    </label>
                    <Input
                        v-model="form.cremation_place"
                        placeholder="e.g. Panteon De Dasmariñas"
                    />
                    <span
                        v-if="errors.cremation_place"
                        class="text-red-500 text-sm"
                    >
                        {{ errors.cremation_place }}
                    </span>
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Applicant Name
                        <span class="text-red-400">*</span>
                    </label>
                    <Input
                        v-model="form.applicant_name"
                        placeholder="Applicant full name"
                    />
                    <span
                        v-if="errors.applicant_name"
                        class="text-red-500 text-sm"
                    >
                        {{ errors.applicant_name }}
                    </span>
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Applicant Address
                        <span class="text-red-400">*</span>
                    </label>
                    <Input
                        v-model="form.applicant_address"
                        placeholder="Brgy. Sampaloc III, City of Dasmariñas, Cavite"
                    />
                    <span
                        v-if="errors.applicant_address"
                        class="text-red-500 text-sm"
                    >
                        {{ errors.applicant_address }}
                    </span>
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Relationship to Deceased
                        <span class="text-red-400">*</span>
                    </label>
                    <Input
                        v-model="form.relationship"
                        placeholder="e.g. Son, Daughter, Spouse"
                    />
                    <span
                        v-if="errors.relationship"
                        class="text-red-500 text-sm"
                    >
                        {{ errors.relationship }}
                    </span>
                </div>

                <div class="md:col-span-2 mt-2">
                    <Button
                        type="button"
                        @click="generate"
                        class="bg-green-500/10 text-green-400 hover:bg-green-500/20"
                    >
                        Generate PDF
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
