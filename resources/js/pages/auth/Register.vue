<script setup lang="ts">
import RegisteredUserController from '@/actions/App/Http/Controllers/Auth/RegisteredUserController';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { home, login } from '@/routes';
import { Form, Head, Link } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
</script>

<template>
    <Head title="Register - Partner Bisnismu" />

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

        <!-- Register Container -->
        <div class="relative z-10 flex min-h-screen flex-col items-center justify-center p-6">
            <div class="w-full max-w-md">
                <!-- Logo and Branding -->
                <div class="mb-8 text-center">
                    <Link :href="home()" class="group inline-flex flex-col items-center gap-4">
                        <!-- Logo Container -->
                        <div
                            class="flex h-16 w-16 items-center justify-center rounded-2xl border border-white/20 bg-white/15 p-2 shadow-lg backdrop-blur-sm transition-all duration-300 group-hover:bg-white/25"
                        >
                            <img src="/images/partner-bisnismu-logo.png" alt="Partner Bisnismu" class="h-full w-full object-contain" />
                        </div>

                        <!-- Company Name -->
                        <div class="text-center">
                            <h1 class="mb-1 text-2xl font-bold text-gray-700">Partner Bisnismu</h1>
                            <p class="text-sm text-gray-600">Sistem Pencatatan Database</p>
                        </div>
                    </Link>
                </div>

                <!-- Register Form Card -->
                <div class="rounded-2xl border border-white/15 bg-white/15 p-8 shadow-2xl backdrop-blur-sm">
                    <!-- Form Header -->
                    <div class="mb-6 text-center">
                        <h2 class="mb-2 text-xl font-semibold text-gray-700">Buat Akun Baru</h2>
                        <p class="text-sm text-gray-600">Lengkapi form dibawah untuk membuat akun</p>
                    </div>

                    <!-- Register Form -->
                    <Form
                        v-bind="RegisteredUserController.store.form()"
                        :reset-on-success="['password', 'password_confirmation']"
                        v-slot="{ errors, processing }"
                        class="space-y-5"
                    >
                        <!-- Name Field -->
                        <div class="space-y-2">
                            <Label for="name" class="font-medium text-gray-700">Nama Lengkap</Label>
                            <Input
                                id="name"
                                type="text"
                                name="name"
                                required
                                autofocus
                                :tabindex="1"
                                autocomplete="name"
                                placeholder="Masukkan nama lengkap"
                                class="h-12 rounded-lg border-white/20 bg-white/15 text-gray-700 backdrop-blur-sm placeholder:text-gray-500 focus:border-white/40 focus:ring-white/20"
                            />
                            <InputError :message="errors.name" class="text-red-600" />
                        </div>

                        <!-- Email Field -->
                        <div class="space-y-2">
                            <Label for="email" class="font-medium text-gray-700">Alamat Email</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                :tabindex="2"
                                autocomplete="email"
                                placeholder="email@example.com"
                                class="h-12 rounded-lg border-white/20 bg-white/15 text-gray-700 backdrop-blur-sm placeholder:text-gray-500 focus:border-white/40 focus:ring-white/20"
                            />
                            <InputError :message="errors.email" class="text-red-600" />
                        </div>

                        <!-- Password Field -->
                        <div class="space-y-2">
                            <Label for="password" class="font-medium text-gray-700">Password</Label>
                            <Input
                                id="password"
                                type="password"
                                name="password"
                                required
                                :tabindex="3"
                                autocomplete="new-password"
                                placeholder="Buat password"
                                class="h-12 rounded-lg border-white/20 bg-white/15 text-gray-700 backdrop-blur-sm placeholder:text-gray-500 focus:border-white/40 focus:ring-white/20"
                            />
                            <InputError :message="errors.password" class="text-red-600" />
                        </div>

                        <!-- Confirm Password Field -->
                        <div class="space-y-2">
                            <Label for="password_confirmation" class="font-medium text-gray-700">Konfirmasi Password</Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                required
                                :tabindex="4"
                                autocomplete="new-password"
                                placeholder="Ulangi password"
                                class="h-12 rounded-lg border-white/20 bg-white/15 text-gray-700 backdrop-blur-sm placeholder:text-gray-500 focus:border-white/40 focus:ring-white/20"
                            />
                            <InputError :message="errors.password_confirmation" class="text-red-600" />
                        </div>

                        <!-- Register Button -->
                        <Button
                            type="submit"
                            class="mt-6 h-12 w-full rounded-lg border border-white/20 bg-white/15 font-medium text-gray-700 shadow-lg backdrop-blur-sm transition-all duration-300 hover:bg-white/25 hover:shadow-xl disabled:opacity-50"
                            :tabindex="5"
                            :disabled="processing"
                        >
                            <LoaderCircle v-if="processing" class="mr-2 h-5 w-5 animate-spin" />
                            {{ processing ? 'Membuat Akun...' : 'Buat Akun' }}
                        </Button>
                    </Form>

                    <!-- Login Link -->
                    <div class="mt-6 text-center">
                        <p class="text-sm text-gray-600">
                            Sudah punya akun?
                            <TextLink :href="login()" class="ml-1 font-medium text-gray-700 transition-colors hover:text-orange-400" :tabindex="6">
                                Masuk disini
                            </TextLink>
                        </p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-8 text-center">
                    <p class="text-xs text-gray-500">&copy; 2024 Partner Bisnismu. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>
</template>
