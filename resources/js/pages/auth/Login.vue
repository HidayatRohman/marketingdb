<script setup lang="ts">
import AuthenticatedSessionController from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { home, register } from '@/routes';
import { request } from '@/routes/password';
import { Form, Head, Link } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { useSiteSettings } from '@/composables/useSiteSettings';

const { siteTitle, siteLogo } = useSiteSettings();

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head :title="`Log in - ${siteTitle}`" />

    <!-- Background with Soft Blue-Orange Gradient -->
    <div class="relative min-h-screen overflow-hidden bg-gradient-to-br from-blue-300 via-blue-200 to-orange-300">
        <!-- Decorative Background Elements -->
        <div class="absolute top-0 left-0 h-full w-full">
            <!-- Floating circles for decoration with more transparency -->
            <div class="absolute top-20 left-10 h-32 w-32 animate-pulse rounded-full bg-white/5 blur-xl"></div>
            <div class="absolute top-40 right-20 h-24 w-24 animate-pulse rounded-full bg-white/8 blur-lg delay-1000"></div>
            <div class="absolute bottom-32 left-1/4 h-40 w-40 animate-pulse rounded-full bg-white/3 blur-2xl delay-2000"></div>
            <div class="absolute right-1/3 bottom-20 h-28 w-28 animate-pulse rounded-full bg-white/5 blur-xl delay-500"></div>
            <div class="absolute top-1/2 left-0 h-20 w-20 animate-pulse rounded-full bg-white/8 blur-lg delay-1500"></div>
            <div class="absolute top-1/3 right-0 h-16 w-16 animate-pulse rounded-full bg-white/10 blur-md delay-3000"></div>
        </div>

        <!-- Glass Morphism Overlay -->
        <div class="absolute inset-0 bg-black/5 backdrop-blur-[1px]"></div>

        <!-- Login Container -->
        <div class="relative z-10 flex min-h-screen flex-col items-center justify-center p-6">
            <div class="w-full max-w-md">
                <!-- Logo and Branding -->
                <div class="mb-8 text-center">
                    <Link :href="home()" class="group inline-flex flex-col items-center gap-4">
                        <!-- Logo Container -->
                        <div
                            class="flex h-16 w-16 items-center justify-center rounded-2xl border border-white/20 bg-white/15 p-2 shadow-lg backdrop-blur-sm transition-all duration-300 group-hover:bg-white/25"
                        >
                            <img v-if="siteLogo" :src="siteLogo" :alt="siteTitle" class="h-full w-full object-contain" />
                            <img v-else src="/images/partner-bisnismu-logo.png" alt="Partner Bisnismu" class="h-full w-full object-contain" />
                        </div>

                        <!-- Company Name -->
                        <div class="text-center">
                            <h1 class="mb-1 text-2xl font-bold text-gray-700">{{ siteTitle }}</h1>
                            <p class="text-sm text-gray-600">Sistem Pencatatan Database</p>
                        </div>
                    </Link>
                </div>

                <!-- Login Form Card -->
                <div class="rounded-2xl border border-white/15 bg-white/15 p-8 shadow-2xl backdrop-blur-sm">
                    <!-- Form Header -->
                    <div class="mb-6 text-center">
                        <h2 class="mb-2 text-xl font-semibold text-gray-700">Masuk ke Akun Anda</h2>
                        <p class="text-sm text-gray-600">Masukkan email dan password untuk melanjutkan</p>
                    </div>

                    <!-- Status Message -->
                    <div v-if="status" class="mb-6 rounded-lg border border-green-300/30 bg-green-400/20 p-3 backdrop-blur-sm">
                        <p class="text-center text-sm font-medium text-green-700">{{ status }}</p>
                    </div>

                    <!-- Login Form -->
                    <Form
                        v-bind="AuthenticatedSessionController.store.form()"
                        :reset-on-success="['password']"
                        v-slot="{ errors, processing }"
                        class="space-y-6"
                    >
                        <!-- Email Field -->
                        <div class="space-y-2">
                            <Label for="email" class="font-medium text-gray-700">Alamat Email</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autofocus
                                :tabindex="1"
                                autocomplete="email"
                                placeholder="email@example.com"
                                class="h-12 rounded-lg border-white/20 bg-white/15 text-gray-700 backdrop-blur-sm placeholder:text-gray-500 focus:border-white/40 focus:ring-white/20"
                            />
                            <InputError :message="errors.email" class="text-red-600" />
                        </div>

                        <!-- Password Field -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <Label for="password" class="font-medium text-gray-700">Password</Label>
                                <TextLink
                                    v-if="canResetPassword"
                                    :href="request()"
                                    class="text-sm text-gray-600 transition-colors hover:text-gray-800"
                                    :tabindex="5"
                                >
                                    Lupa password?
                                </TextLink>
                            </div>
                            <Input
                                id="password"
                                type="password"
                                name="password"
                                required
                                :tabindex="2"
                                autocomplete="current-password"
                                placeholder="Password"
                                class="h-12 rounded-lg border-white/20 bg-white/15 text-gray-700 backdrop-blur-sm placeholder:text-gray-500 focus:border-white/40 focus:ring-white/20"
                            />
                            <InputError :message="errors.password" class="text-red-600" />
                        </div>

                        <!-- Remember Me -->
                        <div class="flex items-center justify-between">
                            <Label for="remember" class="flex cursor-pointer items-center space-x-3 text-gray-700">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    :tabindex="3"
                                    class="border-white/20 data-[state=checked]:border-white/40 data-[state=checked]:bg-white/15"
                                />
                                <span class="text-sm">Ingat saya</span>
                            </Label>
                        </div>

                        <!-- Login Button -->
                        <Button
                            type="submit"
                            class="h-12 w-full rounded-lg border border-white/20 bg-white/15 font-medium text-gray-700 shadow-lg backdrop-blur-sm transition-all duration-300 hover:bg-white/25 hover:shadow-xl disabled:opacity-50"
                            :tabindex="4"
                            :disabled="processing"
                        >
                            <LoaderCircle v-if="processing" class="mr-2 h-5 w-5 animate-spin" />
                            {{ processing ? 'Memproses...' : 'Masuk' }}
                        </Button>
                    </Form>

                    <!-- Register Link -->
                    <div class="mt-6 text-center">
                        <p class="text-sm text-gray-600">
                            Belum punya akun?
                            <TextLink :href="register()" class="ml-1 font-medium text-gray-700 transition-colors hover:text-orange-400" :tabindex="5">
                                Daftar sekarang
                            </TextLink>
                        </p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-8 text-center">
                    <p class="text-xs text-gray-500">&copy; 2024 {{ siteTitle }}. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>
</template>
