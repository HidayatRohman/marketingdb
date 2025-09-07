<script setup lang="ts">
import AuthenticatedSessionController from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { register, home } from '@/routes';
import { request } from '@/routes/password';
import { Form, Head, Link } from '@inertiajs/vue3';
import { LoaderCircle, Building2 } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Log in - Partner Bisnismu" />
    
    <!-- Background with Gradient Wallpaper -->
    <div class="min-h-screen bg-gradient-to-br from-blue-600 via-purple-600 to-orange-500 relative overflow-hidden">
        <!-- Decorative Background Elements -->
        <div class="absolute top-0 left-0 w-full h-full">
            <!-- Floating circles for decoration -->
            <div class="absolute top-20 left-10 w-32 h-32 bg-white/10 rounded-full blur-xl animate-pulse"></div>
            <div class="absolute top-40 right-20 w-24 h-24 bg-white/15 rounded-full blur-lg animate-pulse delay-1000"></div>
            <div class="absolute bottom-32 left-1/4 w-40 h-40 bg-white/5 rounded-full blur-2xl animate-pulse delay-2000"></div>
            <div class="absolute bottom-20 right-1/3 w-28 h-28 bg-white/10 rounded-full blur-xl animate-pulse delay-500"></div>
            <div class="absolute top-1/2 left-0 w-20 h-20 bg-white/20 rounded-full blur-lg animate-pulse delay-1500"></div>
            <div class="absolute top-1/3 right-0 w-16 h-16 bg-white/25 rounded-full blur-md animate-pulse delay-3000"></div>
        </div>

        <!-- Glass Morphism Overlay -->
        <div class="absolute inset-0 bg-black/10 backdrop-blur-[1px]"></div>
        
        <!-- Login Container -->
        <div class="relative z-10 flex min-h-screen flex-col items-center justify-center p-6">
            <div class="w-full max-w-md">
                <!-- Logo and Branding -->
                <div class="text-center mb-8">
                    <Link :href="home()" class="inline-flex flex-col items-center gap-4 group">
                        <!-- Logo Container -->
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/30 p-2 group-hover:bg-white/30 transition-all duration-300 shadow-lg">
                            <img src="/images/partner-bisnismu-logo.png" alt="Partner Bisnismu" class="w-full h-full object-contain" />
                        </div>
                        
                        <!-- Company Name -->
                        <div class="text-center">
                            <h1 class="text-2xl font-bold text-white mb-1">Partner Bisnismu</h1>
                            <p class="text-white/80 text-sm">Sistem Pencatatan Database</p>
                        </div>
                    </Link>
                </div>

                <!-- Login Form Card -->
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-8 border border-white/20 shadow-2xl">
                    <!-- Form Header -->
                    <div class="text-center mb-6">
                        <h2 class="text-xl font-semibold text-white mb-2">Masuk ke Akun Anda</h2>
                        <p class="text-white/70 text-sm">Masukkan email dan password untuk melanjutkan</p>
                    </div>

                    <!-- Status Message -->
                    <div v-if="status" class="mb-6 p-3 bg-green-500/20 backdrop-blur-md border border-green-400/30 rounded-lg">
                        <p class="text-center text-sm font-medium text-green-100">{{ status }}</p>
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
                            <Label for="email" class="text-white/90 font-medium">Alamat Email</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autofocus
                                :tabindex="1"
                                autocomplete="email"
                                placeholder="email@example.com"
                                class="bg-white/10 backdrop-blur-md border-white/30 text-white placeholder:text-white/50 focus:border-white/50 focus:ring-white/25 rounded-lg h-12"
                            />
                            <InputError :message="errors.email" class="text-red-200" />
                        </div>

                        <!-- Password Field -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <Label for="password" class="text-white/90 font-medium">Password</Label>
                                <TextLink 
                                    v-if="canResetPassword" 
                                    :href="request()" 
                                    class="text-sm text-white/70 hover:text-white transition-colors" 
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
                                class="bg-white/10 backdrop-blur-md border-white/30 text-white placeholder:text-white/50 focus:border-white/50 focus:ring-white/25 rounded-lg h-12"
                            />
                            <InputError :message="errors.password" class="text-red-200" />
                        </div>

                        <!-- Remember Me -->
                        <div class="flex items-center justify-between">
                            <Label for="remember" class="flex items-center space-x-3 text-white/90 cursor-pointer">
                                <Checkbox 
                                    id="remember" 
                                    name="remember" 
                                    :tabindex="3" 
                                    class="border-white/30 data-[state=checked]:bg-white/20 data-[state=checked]:border-white/50"
                                />
                                <span class="text-sm">Ingat saya</span>
                            </Label>
                        </div>

                        <!-- Login Button -->
                        <Button 
                            type="submit" 
                            class="w-full h-12 bg-white/20 backdrop-blur-md hover:bg-white/30 text-white font-medium border border-white/30 rounded-lg transition-all duration-300 shadow-lg hover:shadow-xl disabled:opacity-50" 
                            :tabindex="4" 
                            :disabled="processing"
                        >
                            <LoaderCircle v-if="processing" class="h-5 w-5 animate-spin mr-2" />
                            {{ processing ? 'Memproses...' : 'Masuk' }}
                        </Button>
                    </Form>

                    <!-- Register Link -->
                    <div class="mt-6 text-center">
                        <p class="text-white/70 text-sm">
                            Belum punya akun?
                            <TextLink :href="register()" class="text-white hover:text-orange-200 font-medium ml-1 transition-colors" :tabindex="5">
                                Daftar sekarang
                            </TextLink>
                        </p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="text-center mt-8">
                    <p class="text-white/60 text-xs">
                        &copy; 2024 Partner Bisnismu. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
