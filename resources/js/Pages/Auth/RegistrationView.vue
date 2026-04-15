<script setup>
import { useForm, Link } from "@inertiajs/vue3";
import Input from "@/Components/Form/Input.vue";
import Button from "@/Components/Form/Button.vue";

const form = useForm({
    first_name: "",
    middle_name: "",
    last_name: "",
    contact_number: "",
    email: "",
    password: "",
    password_confirmation: "",
});

const handleSubmit = () => {
    form.post(route("register.store"));
};
</script>

<template>
    <div class="min-h-screen flex">
        <!-- Left Side - Image with Gradient Overlay -->
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden">
            <!-- Background Image -->
            <div
                class="absolute inset-0 bg-cover bg-center bg-[url('/images/entrance.jpg')]"
            ></div>

            <!-- Gradient Overlay -->
            <div
                class="absolute inset-0 bg-gradient-to-br from-green-800/95 via-green-700/90 to-green-500/85 dark:from-green-900/95 dark:via-green-800/90 dark:to-green-600/85"
            ></div>

            <!-- Content -->
            <div
                class="w-full relative z-10 flex flex-col justify-center items-center text-white p-12"
            >
                <h1
                    class="bona-nova-heading text-8xl font-thin text-yellow-400 text-center leading-none"
                >
                    Panteon De<br />Dasmariñas
                </h1>
            </div>
        </div>

        <!-- Right Side - Registration Form -->
        <div
            class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-white dark:bg-neutral-900"
        >
            <div class="w-full max-w-md space-y-8">
                <!-- Header -->
                <div class="text-center">
                    <h2
                        class="text-3xl font-bold text-gray-900 dark:text-white mb-2"
                    >
                        Create Account
                    </h2>
                    <p class="text-gray-600 dark:text-neutral-400">
                        Clerk Registration Page
                    </p>
                </div>

                <!-- Form -->
                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <!-- First Name -->
                    <div>
                        <label
                            for="first_name"
                            class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2"
                        >
                            First Name
                        </label>
                        <Input
                            id="first_name"
                            v-model="form.first_name"
                            type="text"
                            placeholder="Enter your first name"
                            required
                        />
                        <p v-if="form.errors.first_name" class="mt-1 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.first_name }}
                        </p>
                    </div>

                    <!-- Middle Name -->
                    <div>
                        <label
                            for="middle_name"
                            class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2"
                        >
                            Middle Name
                        </label>
                        <Input
                            id="middle_name"
                            v-model="form.middle_name"
                            type="text"
                            placeholder="Enter your middle name (optional)"
                        />
                        <p v-if="form.errors.middle_name" class="mt-1 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.middle_name }}
                        </p>
                    </div>

                    <!-- Last Name -->
                    <div>
                        <label
                            for="last_name"
                            class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2"
                        >
                            Last Name
                        </label>
                        <Input
                            id="last_name"
                            v-model="form.last_name"
                            type="text"
                            placeholder="Enter your last name"
                            required
                        />
                        <p v-if="form.errors.last_name" class="mt-1 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.last_name }}
                        </p>
                    </div>

                    <!-- Contact Number -->
                    <div>
                        <label
                            for="contact_number"
                            class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2"
                        >
                            Contact Number
                        </label>
                        <Input
                            id="contact_number"
                            v-model="form.contact_number"
                            type="text"
                            placeholder="Enter your contact number"
                            required
                        />
                        <p v-if="form.errors.contact_number" class="mt-1 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.contact_number }}
                        </p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label
                            for="email"
                            class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2"
                        >
                            Email Address
                        </label>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            placeholder="Enter your email"
                            required
                        />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label
                            for="password"
                            class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2"
                        >
                            Password
                        </label>
                        <Input
                            id="password"
                            v-model="form.password"
                            type="password"
                            placeholder="Create a password"
                            required
                        />
                        <p v-if="form.errors.password" class="mt-1 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label
                            for="password_confirmation"
                            class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2"
                        >
                            Confirm Password
                        </label>
                        <Input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            placeholder="Confirm your password"
                            required
                        />
                        <p v-if="form.errors.password_confirmation" class="mt-1 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.password_confirmation }}
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <Button type="submit" :highlighted="true" class="w-full justify-center" :disabled="form.processing">
                            {{ form.processing ? 'Registering...' : 'Register' }}
                        </Button>
                    </div>
                </form>

                <!-- Footer -->
                <div
                    class="text-center text-sm text-gray-600 dark:text-neutral-400"
                >
                    Already have an account?
                    <Link
                        :href="route('login')"
                        class="text-green-600 dark:text-green-500 hover:underline font-medium"
                    >
                        Sign in
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.bona-nova-heading {
    font-family: "Bona Nova", serif;
}
</style>
