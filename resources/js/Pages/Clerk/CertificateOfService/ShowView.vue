<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

import Input from "@/Components/Form/Input.vue";
import Button from "@/Components/Form/Button.vue";
import Dashboard from "@/Layouts/Dashboard.vue";

const props = defineProps({
    burial_record_id: { type: Number, required: true },
    prefilled: { type: Object, required: true },
    csrf_token: { type: String, required: true },
});

const form = ref({
    deceased_name: props.prefilled.deceased_name ?? "",
    deceased_address: props.prefilled.deceased_address ?? "",
    date_of_death: props.prefilled.date_of_death ?? "",
    date_of_depository: props.prefilled.date_of_depository ?? "",
    applicant_name: props.prefilled.applicant_name ?? "",
    applicant_address: "",
    relationship: props.prefilled.relationship ?? "",
});

const errors = ref({});

const goBack = () => {
    router.visit(route("clerk.burial_records.show", props.burial_record_id));
};

const generate = () => {
    errors.value = {};

    if (!form.value.deceased_name) {
        errors.value.deceased_name = "Deceased name is required.";
    }
    if (!form.value.applicant_name) {
        errors.value.applicant_name = "Applicant name is required.";
    }
    if (!form.value.applicant_address) {
        errors.value.applicant_address = "Applicant address is required.";
    }

    if (Object.keys(errors.value).length) return;

    // Build and submit a native form for file download
    const f = document.createElement("form");
    f.method = "POST";
    f.action = route("clerk.certificate_of_service.generate", props.burial_record_id);

    const fields = { ...form.value, _token: props.csrf_token };
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
                <Button @click="goBack">Back</Button>
            </div>

            <!-- FORM -->
            <div class="px-6 py-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Deceased Name
                    </label>
                    <Input
                        v-model="form.deceased_name"
                        placeholder="Deceased full name"
                    />
                    <span v-if="errors.deceased_name" class="text-red-500 text-sm">
                        {{ errors.deceased_name }}
                    </span>
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Deceased Address
                    </label>
                    <Input
                        v-model="form.deceased_address"
                        placeholder="Deceased address"
                    />
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Date of Death
                    </label>
                    <Input v-model="form.date_of_death" type="date" />
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Date of Depository
                    </label>
                    <Input v-model="form.date_of_depository" type="date" />
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Applicant Name
                    </label>
                    <Input
                        v-model="form.applicant_name"
                        placeholder="Applicant full name"
                    />
                    <span v-if="errors.applicant_name" class="text-red-500 text-sm">
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
                        placeholder="Enter applicant address"
                    />
                    <span v-if="errors.applicant_address" class="text-red-500 text-sm">
                        {{ errors.applicant_address }}
                    </span>
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Relationship to Deceased
                    </label>
                    <Input
                        v-model="form.relationship"
                        placeholder="e.g. Son, Daughter, Spouse"
                    />
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
