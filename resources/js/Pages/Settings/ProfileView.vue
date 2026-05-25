<script setup>
import { router, usePage } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import { isEqual } from "lodash";
import { useToast } from "vue-toast-notification";

import Input from "@/Components/Form/Input.vue";

const page = usePage();
const $toast = useToast();

// RBAC variables
const user = computed(() => page.props.auth.user);
const userRole = computed(() =>
    page.props.auth?.user?.role?.toLowerCase()?.trim(),
);

// Error handling from server
const errors = computed(() => page.props.errors || {});

// State management
const hasChanges = ref(false);

// Deep copy original user data
const originalData = ref({
    first_name: user.value?.first_name || "",
    middle_name: user.value?.middle_name || "",
    last_name: user.value?.last_name || "",
    contact_number: user.value?.contact_number || "",
});

const localData = ref({
    first_name: user.value?.first_name || "",
    middle_name: user.value?.middle_name || "",
    last_name: user.value?.last_name || "",
    contact_number: user.value?.contact_number || "",
});

// Watch for changes
watch(
    localData,
    (newData) => {
        hasChanges.value = !isEqual(newData, originalData.value);
    },
    { deep: true },
);

const back = () => {
    if (userRole.value === "clerk") router.visit(route("clerk.dashboard"));
    else if (userRole.value === "admin") router.visit(route("admin.dashboard"));
};

const discardChanges = () => {
    if (hasChanges.value) {
        if (confirm("Are you sure you want to discard your changes?")) {
            localData.value = JSON.parse(JSON.stringify(originalData.value));
            hasChanges.value = false;
        }
    }
};

const saveChanges = () => {
    router.post(
        route("profile.update"),
        {
            first_name: localData.value.first_name,
            middle_name: localData.value.middle_name,
            last_name: localData.value.last_name,
            contact_number: localData.value.contact_number,
        },
        {
            onSuccess: () => {
                originalData.value = JSON.parse(
                    JSON.stringify(localData.value),
                );
                hasChanges.value = false;
                $toast.success("Profile updated successfully!");
            },
            onError: () => {
                $toast.error(
                    "Failed to update profile. Please check the form for errors.",
                );
            },
            preserveScroll: true,
        },
    );
};
</script>

<template>
    <main
        class="w-full transition-all duration-300 min-h-screen bg-zinc-50 text-gray-600 dark:bg-neutral-900 dark:text-neutral-400"
    >
        <!-- Page content -->
        <div class="max-w-7xl mx-auto px-6 py-10">
            <button
                @click="back"
                class="flex items-center gap-1 mb-6 text-sm text-green-600 dark:text-green-400 hover:underline"
            >
                ← Back
            </button>
            <div class="grid grid-cols-[180px_1fr] gap-10">
                <!-- LEFT SIDEBAR -->
                <aside>
                    <nav class="flex flex-col gap-0.5">
                        <div
                            class="bg-gradient-to-r from-green-800/95 via-green-700/90 to-green-500/85 dark:from-green-900/95 dark:via-green-800/90 dark:to-green-600/85 rounded-lg"
                        >
                            <a
                                href="#"
                                class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-gray-800 dark:text-white text-sm font-medium transition"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="15"
                                    height="15"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <circle cx="12" cy="8" r="5" />
                                    <path d="M20 21a8 8 0 1 0-16 0" />
                                </svg>
                                Profile
                            </a>
                        </div>

                        <a
                            href="#"
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-gray-500 dark:text-neutral-400 hover:bg-gray-100 dark:hover:bg-neutral-800 hover:text-gray-800 dark:hover:text-white text-sm transition"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="15"
                                height="15"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <rect
                                    width="18"
                                    height="11"
                                    x="3"
                                    y="11"
                                    rx="2"
                                    ry="2"
                                />
                                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                            </svg>
                            Security
                        </a>
                        <a
                            href="#"
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-gray-500 dark:text-neutral-400 hover:bg-gray-100 dark:hover:bg-neutral-800 hover:text-gray-800 dark:hover:text-white text-sm transition"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="15"
                                height="15"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <circle cx="12" cy="12" r="3" />
                                <path
                                    d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"
                                />
                            </svg>
                            Preferences
                        </a>
                    </nav>
                </aside>

                <!-- RIGHT CONTENT -->
                <section>
                    <!-- Heading -->
                    <div
                        class="mb-6 pb-5 border-b border-gray-200 dark:border-neutral-700"
                    >
                        <h1
                            class="text-lg font-semibold text-gray-800 dark:text-white"
                        >
                            Profile
                        </h1>
                        <p
                            class="text-sm text-gray-500 dark:text-neutral-400 mt-0.5"
                        >
                            Manage your personal information.
                        </p>
                    </div>

                    <!-- Avatar section -->
                    <div class="flex items-center gap-4 mb-8">
                        <!-- SVG Avatar -->
                        <div
                            class="w-16 h-16 rounded-full overflow-hidden bg-gray-200 dark:bg-neutral-700 border border-gray-200 dark:border-neutral-600 flex-shrink-0"
                        >
                            <svg
                                viewBox="0 0 80 80"
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-full h-full"
                            >
                                <defs>
                                    <linearGradient
                                        id="avatarGrad"
                                        x1="0"
                                        y1="0"
                                        x2="1"
                                        y2="1"
                                    >
                                        <stop
                                            offset="0%"
                                            stop-color="#d1d5db"
                                        />
                                        <stop
                                            offset="100%"
                                            stop-color="#9ca3af"
                                        />
                                    </linearGradient>
                                </defs>
                                <rect
                                    width="80"
                                    height="80"
                                    fill="url(#avatarGrad)"
                                />
                                <ellipse
                                    cx="40"
                                    cy="72"
                                    rx="24"
                                    ry="14"
                                    fill="#6b7280"
                                    opacity="0.5"
                                />
                                <circle cx="40" cy="32" r="17" fill="#f3f4f6" />
                                <ellipse
                                    cx="40"
                                    cy="19"
                                    rx="17"
                                    ry="10"
                                    fill="#374151"
                                />
                                <circle
                                    cx="34"
                                    cy="32"
                                    r="2.2"
                                    fill="#374151"
                                />
                                <circle
                                    cx="46"
                                    cy="32"
                                    r="2.2"
                                    fill="#374151"
                                />
                                <path
                                    d="M35 39 Q40 44 45 39"
                                    stroke="#374151"
                                    stroke-width="1.5"
                                    fill="none"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </div>

                        <div>
                            <p
                                class="text-sm font-medium text-gray-800 dark:text-white"
                            >
                                {{ localData.first_name }}
                                {{ localData.middle_name }}
                                {{ localData.last_name }}
                            </p>
                            <p
                                class="text-xs text-gray-400 dark:text-neutral-500 mt-0.5"
                            >
                                {{ user?.created_at }}
                            </p>
                        </div>
                    </div>

                    <!-- Form fields -->
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <!-- First Name -->
                            <div>
                                <label
                                    class="block text-xs font-medium text-gray-500 dark:text-neutral-400 mb-1.5"
                                >
                                    First Name
                                </label>
                                <Input
                                    v-model="localData.first_name"
                                    placeholder="Enter first name"
                                    :error="errors['first_name']"
                                />
                            </div>
                            <!-- Middle Name -->
                            <div>
                                <label
                                    class="block text-xs font-medium text-gray-500 dark:text-neutral-400 mb-1.5"
                                >
                                    Middle Name
                                </label>
                                <Input
                                    v-model="localData.middle_name"
                                    placeholder="Enter middle name"
                                    :error="errors['middle_name']"
                                />
                            </div>
                        </div>

                        <!-- Last Name -->
                        <div>
                            <label
                                class="block text-xs font-medium text-gray-500 dark:text-neutral-400 mb-1.5"
                            >
                                Last Name
                            </label>
                            <Input
                                v-model="localData.last_name"
                                placeholder="Enter last name"
                                :error="errors['last_name']"
                            />
                        </div>

                        <!-- Contact Number -->
                        <div>
                            <label
                                class="block text-xs font-medium text-gray-500 dark:text-neutral-400 mb-1.5"
                            >
                                Contact Number
                            </label>
                            <Input
                                v-model="localData.contact_number"
                                placeholder="09XXXXXXXXX"
                                :error="errors['contact_number']"
                            />
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            @click="discardChanges"
                            :disabled="!hasChanges"
                            :class="{
                                'opacity-50 cursor-not-allowed': !hasChanges,
                            }"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-neutral-800 hover:bg-gray-200 dark:hover:bg-neutral-700 rounded-lg transition disabled:hover:bg-gray-100"
                        >
                            Discard
                        </button>
                        <button
                            @click="saveChanges"
                            :disabled="!hasChanges"
                            :class="{
                                'opacity-50 cursor-not-allowed': !hasChanges,
                            }"
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 dark:bg-green-600 dark:hover:bg-green-500 rounded-lg transition disabled:hover:bg-green-600"
                        >
                            Save Changes
                        </button>
                    </div>
                </section>
            </div>
        </div>
    </main>
</template>
