<script setup lang="ts">
import RegisteredUserController from '@/actions/App/Http/Controllers/Auth/RegisteredUserController';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { login, home } from '@/routes';
import { Form, Head, Link } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
</script>

<template>
    <Head title="Register - Partner Bisnismu" />
    
    <!-- Background with Soft Blue-Orange Gradient -->
    <div class="min-h-screen bg-gradient-to-br from-blue-300 via-blue-200 to-orange-300 relative overflow-hidden">
        <!-- Decorative Background Elements -->
        <div class="absolute top-0 left-0 w-full h-full">
            <!-- Floating circles for decoration with more transparency -->
            <div class="absolute top-20 left-10 w-32 h-32 bg-white/5 rounded-full blur-xl animate-pulse"></div>
            <div class="absolute top-40 right-20 w-24 h-24 bg-white/8 rounded-full blur-lg animate-pulse delay-1000"></div>
            <div class="absolute bottom-32 left-1/4 w-40 h-40 bg-white/3 rounded-full blur-2xl animate-pulse delay-2000"></div>
            <div class="absolute bottom-20 right-1/3 w-28 h-28 bg-white/5 rounded-full blur-xl animate-pulse delay-500"></div>
            <div class="absolute top-1/2 left-0 w-20 h-20 bg-white/8 rounded-full blur-lg animate-pulse delay-1500"></div>
            <div class="absolute top-1/3 right-0 w-16 h-16 bg-white/10 rounded-full blur-md animate-pulse delay-3000"></div>
        </div>

        <!-- Glass Morphism Overlay -->
        <div class="absolute inset-0 bg-black/5 backdrop-blur-[1px]"></div>
        
        <!-- Register Container -->
        <div class="relative z-10 flex min-h-screen flex-col items-center justify-center p-6">
            <div class="w-full max-w-md">
                <!-- Logo and Branding -->
                <div class="text-center mb-8">
                    <Link :href="home()" class="inline-flex flex-col items-center gap-4 group">
                        <!-- Logo Container -->
                        <div class="w-16 h-16 bg-white/15 backdrop-blur-sm rounded-2xl flex items-center justify-center border border-white/20 p-2 group-hover:bg-white/25 transition-all duration-300 shadow-lg">
                            <img src="/images/partner-bisnismu-logo.png" alt="Partner Bisnismu" class="w-full h-full object-contain" />
                        </div>
                        
                        <!-- Company Name -->
                        <div class="text-center">
                            <h1 class="text-2xl font-bold text-gray-700 mb-1">Partner Bisnismu</h1>
                            <p class="text-gray-600 text-sm">Sistem Pencatatan Database</p>
                        </div>
                    </Link>
                </div>

                <!-- Register Form Card -->
                <div class="bg-white/15 backdrop-blur-sm rounded-2xl p-8 border border-white/15 shadow-2xl">
                    <!-- Form Header -->
                    <div class="text-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-700 mb-2">Buat Akun Baru</h2>
                        <p class="text-gray-600 text-sm">Lengkapi form dibawah untuk membuat akun</p>
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
                            <Label for="name" class="text-gray-700 font-medium">Nama Lengkap</Label>
                            <Input
                                id="name"
                                type="text"
                                name="name"
                                required
                                autofocus
                                :tabindex="1"
                                autocomplete="name"
                                placeholder="Masukkan nama lengkap"
                                class="bg-white/15 backdrop-blur-sm border-white/20 text-gray-700 placeholder:text-gray-500 focus:border-white/40 focus:ring-white/20 rounded-lg h-12"
                            />
                            <InputError :message="errors.name" class="text-red-600" />
                        </div>

                        <!-- Email Field -->
                        <div class="space-y-2">
                            <Label for="email" class="text-gray-700 font-medium">Alamat Email</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                :tabindex="2"
                                autocomplete="email"
                                placeholder="email@example.com"
                                class="bg-white/15 backdrop-blur-sm border-white/20 text-gray-700 placeholder:text-gray-500 focus:border-white/40 focus:ring-white/20 rounded-lg h-12"
                            />
                            <InputError :message="errors.email" class="text-red-600" />
                        </div>

                        <!-- Password Field -->
                        <div class="space-y-2">
                            <Label for="password" class="text-gray-700 font-medium">Password</Label>
                            <Input
                                id="password"
                                type="password"
                                name="password"
                                required
                                :tabindex="3"
                                autocomplete="new-password"
                                placeholder="Buat password"
                                class="bg-white/15 backdrop-blur-sm border-white/20 text-gray-700 placeholder:text-gray-500 focus:border-white/40 focus:ring-white/20 rounded-lg h-12"
                            />
                            <InputError :message="errors.password" class="text-red-600" />
                        </div>

                        <!-- Confirm Password Field -->
                        <div class="space-y-2">
                            <Label for="password_confirmation" class="text-gray-700 font-medium">Konfirmasi Password</Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                required
                                :tabindex="4"
                                autocomplete="new-password"
                                placeholder="Ulangi password"
                                class="bg-white/15 backdrop-blur-sm border-white/20 text-gray-700 placeholder:text-gray-500 focus:border-white/40 focus:ring-white/20 rounded-lg h-12"
                            />
                            <InputError :message="errors.password_confirmation" class="text-red-600" />
                        </div>

                        <!-- Register Button -->
                        <Button 
                            type="submit" 
                            class="w-full h-12 bg-white/15 backdrop-blur-sm hover:bg-white/25 text-gray-700 font-medium border border-white/20 rounded-lg transition-all duration-300 shadow-lg hover:shadow-xl disabled:opacity-50 mt-6" 
                            :tabindex="5" 
                            :disabled="processing"
                        >
                            <LoaderCircle v-if="processing" class="h-5 w-5 animate-spin mr-2" />
                            {{ processing ? 'Membuat Akun...' : 'Buat Akun' }}
                        </Button>
                    </Form>

                    <!-- Login Link -->
                    <div class="mt-6 text-center">
                        <p class="text-gray-600 text-sm">
                            Sudah punya akun?
                            <TextLink :href="login()" class="text-gray-700 hover:text-orange-400 font-medium ml-1 transition-colors" :tabindex="6">
                                Masuk disini
                            </TextLink>
                        </p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="text-center mt-8">
                    <p class="text-gray-500 text-xs">
                        &copy; 2024 Partner Bisnismu. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
