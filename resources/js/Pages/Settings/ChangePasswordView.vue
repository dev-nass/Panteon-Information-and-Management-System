<script setup>
import { router, usePage, Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { useToast } from "vue-toast-notification";

const page = usePage();
const $toast = useToast();

// RBAC variables
const user = computed(() => page.props.auth.user);
const userRole = computed(() =>
    page.props.auth?.user?.role?.toLowerCase()?.trim(),
);

// Error handling from server
const errors = computed(() => page.props.errors || {});

// Form state
const form = ref({
    current_password: "",
    password: "",
    password_confirmation: "",
});

const back = () => {
    if (userRole.value === "clerk") router.visit(route("clerk.dashboard"));
    else if (userRole.value === "admin") router.visit(route("admin.dashboard"));
};

const submit = () => {
    router.post(
        route("settings.password.update"),
        {
            current_password: form.value.current_password,
            password: form.value.password,
            password_confirmation: form.value.password_confirmation,
        },
        {
            onSuccess: () => {
                form.value = {
                    current_password: "",
                    password: "",
                    password_confirmation: "",
                };
                $toast.success("Password changed successfully!");
            },
            onError: () => {
                $toast.error(
                    "Failed to change password. Please check the form for errors.",
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
        <div class="max-w-7xl mx-auto px-6 py-10">
            <button
                @click="back"
                class="flex items-center gap-1 mb-6 text-sm text-green-600 dark:text-green-400 hover:underline"
            >
                ← Back
            </button>
            <div
                class="grid grid-cols-1 lg:grid-cols-[180px_1fr] gap-6 lg:gap-10"
            >
                <!-- LEFT SIDEBAR -->
                <aside>
                    <nav class="flex flex-wrap lg:flex-col gap-0.5">
                        <Link
                            :href="route('settings.profile.index')"
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
                                <circle cx="12" cy="8" r="5" />
                                <path d="M20 21a8 8 0 1 0-16 0" />
                            </svg>
                            Profile
                        </Link>

                        <div
                            class="bg-gradient-to-r from-green-800/95 via-green-700/90 to-green-500/85 dark:from-green-900/95 dark:via-green-800/90 dark:to-green-600/85 rounded-lg"
                        >
                            <Link
                                :href="route('settings.password.index')"
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
                            </Link>
                        </div>

                        <div
                            class="w-full mt-1 pt-1 border-t border-gray-200 dark:border-neutral-700 lg:mt-2 lg:pt-2"
                        ></div>
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
                            Security
                        </h1>
                        <p
                            class="text-sm text-gray-500 dark:text-neutral-400 mt-0.5"
                        >
                            Manage your password and security settings.
                        </p>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submit" class="space-y-4 max-w-md">
                        <!-- Current Password -->
                        <div>
                            <label
                                class="text-sm text-gray-500 dark:text-gray-400"
                            >
                                Current Password
                            </label>
                            <input
                                v-model="form.current_password"
                                type="password"
                                placeholder="Enter current password"
                                :class="{
                                    'border-red-500 focus:ring-red-500':
                                        errors['current_password'],
                                }"
                                class="mt-1 w-full rounded-lg border border-gray-300 dark:border-neutral-700 bg-gray-100 dark:bg-neutral-800 text-gray-800 dark:text-white px-3 py-2 text-sm transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            />
                            <p
                                v-if="errors['current_password']"
                                class="mt-1 text-sm text-red-500"
                            >
                                {{ errors["current_password"] }}
                            </p>
                        </div>

                        <!-- New Password -->
                        <div>
                            <label
                                class="text-sm text-gray-500 dark:text-gray-400"
                            >
                                New Password
                            </label>
                            <input
                                v-model="form.password"
                                type="password"
                                placeholder="Enter new password"
                                :class="{
                                    'border-red-500 focus:ring-red-500':
                                        errors['password'],
                                }"
                                class="mt-1 w-full rounded-lg border border-gray-300 dark:border-neutral-700 bg-gray-100 dark:bg-neutral-800 text-gray-800 dark:text-white px-3 py-2 text-sm transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            />
                            <p
                                v-if="errors['password']"
                                class="mt-1 text-sm text-red-500"
                            >
                                {{ errors["password"] }}
                            </p>
                        </div>

                        <!-- Confirm New Password -->
                        <div>
                            <label
                                class="text-sm text-gray-500 dark:text-gray-400"
                            >
                                Confirm New Password
                            </label>
                            <input
                                v-model="form.password_confirmation"
                                type="password"
                                placeholder="Confirm new password"
                                class="mt-1 w-full rounded-lg border border-gray-300 dark:border-neutral-700 bg-gray-100 dark:bg-neutral-800 text-gray-800 dark:text-white px-3 py-2 text-sm transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            />
                        </div>

                        <!-- Submit -->
                        <div class="flex justify-end pt-2">
                            <button
                                type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 dark:bg-green-600 dark:hover:bg-green-500 rounded-lg transition"
                            >
                                Change Password
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </main>
</template>
