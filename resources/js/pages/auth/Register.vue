<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

function redirectToGoogle() {
    window.location.href = route('auth.google');
}
</script>

<template>
    <Head title="Register" />

    <div
        class="flex min-h-screen flex-col scroll-smooth bg-gray-50 font-sans text-gray-900 dark:bg-gray-900 dark:text-white"
        style="background-image: url('/images/img1.png'); background-size: cover; background-position: center"
    >
        <!-- Navigation Bar -->
        <header class="sticky top-0 z-50 w-full bg-white/80 shadow-lg backdrop-blur-md dark:bg-gray-900/80">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                <h1 class="text-xl font-extrabold tracking-tight text-gray-800 dark:text-white"><span class="text-pink-500">ICo-nic</span> Fashion</h1>
                
            </div>
        </header>

       
        <section class="relative flex h-[600px] w-full items-center justify-center overflow-hidden md:h-[700px]">
            <div class="absolute inset-0 flex w-full flex-col items-center justify-center bg-black/50 p-6 text-center text-white md:p-12">
                <p class="mb-8 max-w-xl text-base opacity-90 drop-shadow-md md:text-xl">
                    Buat akun Anda untuk memulai pengalaman berbelanja yang luar biasa.
                </p>
                <div class="w-full max-w-md rounded-lg bg-white p-8 shadow-lg dark:bg-gray-800">
                    <form @submit.prevent="submit" class="flex flex-col gap-6">
                        <div class="grid gap-4">
                            <div class="grid gap-2">
                                <label for="name" class="block text-left text-sm font-medium text-gray-700">Name</label>
                                <Input
                                    id="name"
                                    type="text"
                                    required
                                    autofocus
                                    :tabindex="1"
                                    autocomplete="name"
                                    v-model="form.name"
                                    class="text-black"
                                    placeholder="Nama Lengkap"
                                />
                                <InputError :message="form.errors.name" />
                                <Label for="email" class="block  text-left text-sm font-medium text-gray-700">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    :tabindex="2"
                                    autocomplete="email"
                                    v-model="form.email"
                                    class="text-black"
                                    placeholder="email@example.com"
                                />
                                <InputError :message="form.errors.email" />

                                <Label for="password" class="block text-left text-sm font-medium text-gray-700">Password</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    required
                                    :tabindex="3"
                                    autocomplete="new-password"
                                    v-model="form.password"
                                    class="text-black"
                                    placeholder="Password"
                                />
                                <InputError :message="form.errors.password" />

                                <Label for="password_confirmation" class="block text-left text-sm font-medium text-gray-700">Confirm password</Label>
                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    required
                                    :tabindex="4"
                                    autocomplete="new-password"
                                    class="text-black"
                                    v-model="form.password_confirmation"
                                    placeholder="Confirm password"
                                />
                                <InputError :message="form.errors.password_confirmation" />
                            </div>

                            <Button
                                type="submit"
                                class="mt-2 w-full bg-blue-500 text-white hover:bg-blue-600"
                                tabindex="5"
                                :disabled="form.processing"
                            >
                                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                                Buat account
                            </Button>
                        </div>

                        <button
                            @click="redirectToGoogle"
                            class="flex w-full items-center justify-center gap-2 rounded-md py-3 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 533.5 544.3" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    fill="#4285F4"
                                    d="M533.5 278.4c0-17.4-1.6-34-4.6-50.1H272v95h147.4c-6.3 33.9-25 62.6-53.6 81.6v67h86.4c50.6-46.6 81.3-115.3 81.3-193.5z"
                                />
                                <path
                                    fill="#34A853"
                                    d="M272 544.3c72.6 0 133.4-24 177.8-65.1l-86.4-67c-24 16.1-54.6 25.6-91.4 25.6-70.3 0-130-47.4-151.3-111.2h-89.6v69.9C103.7 478 181.2 544.3 272 544.3z"
                                />
                                <path
                                    fill="#FBBC04"
                                    d="M120.7 326.6c-10.1-29.4-10.1-61.2 0-90.6v-69.9h-89.6c-19.5 38.8-30.7 82.4-30.7 129.5s11.2 90.7 30.7 129.5l89.6-69.9z"
                                />
                                <path
                                    fill="#EA4335"
                                    d="M272 214.8c39.5 0 75 13.6 102.9 36.1l77.4-77.4C405.4 128.3 344.6 104 272 104c-90.8 0-168.3 66.3-195.3 156.1l89.6 69.9c21.3-63.8 81-111.2 151.3-111.2z"
                                />
                            </svg>
                            <span class="text-center text-sm text-muted-foreground">Daftar dengan Google</span>
                        </button>
                        <div class="text-center text-sm text-muted-foreground">
                            Sudah punya akun?
                            <TextLink :href="route('login')" class="underline underline-offset-4" :tabindex="6">Log in </TextLink>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
</template>
