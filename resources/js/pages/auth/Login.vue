<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const { status, canResetPassword } = defineProps<{
  status?: string;
  canResetPassword: boolean;
}>();

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  });
};

const redirectToGoogle = () => {
  window.location.href = '/auth/google';
};
</script>

<template>
  <Head title="Log in" />

  <div
    class="scroll-smooth min-h-screen flex flex-col bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white font-sans"
    style="background-image: url('/images/img1.png'); background-size: cover; background-position: center;"
  >
    <!-- NAVBAR -->
    <header
      class="sticky top-0 z-50 w-full bg-white/80 dark:bg-gray-900/80 backdrop-blur-md shadow-lg"
    >
      <div
        class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between"
      >
        <h1
          class="text-xl font-extrabold tracking-tight text-gray-800 dark:text-white"
        >
          <span class="text-pink-500">Aninka</span> Fashion
        </h1>
      </div>
    </header>

    <!-- LOGIN SECTION -->
    <section
      class="relative w-full min-h-[600px] flex items-center justify-center"
    >
      <div
        class="absolute inset-0 bg-black/50 flex flex-col justify-center items-center text-center p-6 md:p-12 text-white w-full"
      >
        <div
          class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow-xl w-full max-w-md text-left"
        >
          <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-4">
              <!-- EMAIL -->
              <div class="grid gap-2">
                <Label for="email" class="text-sm font-medium text-gray-700 dark:text-gray-300"
                  >Email</Label
                >
                <Input
                  id="email"
                  type="email"
                  autocomplete="email"
                  v-model="form.email"
                  required
                  autofocus
                  class="text-black"
                  placeholder="email@example.com"
                />
                <InputError :message="form.errors.email" />
              </div>

              <!-- PASSWORD -->
              <div class="grid gap-2">
                <Label for="password" class="text-sm font-medium text-gray-700 dark:text-gray-300"
                  >Password</Label
                >
                <Input
                  id="password"
                  type="password"
                  autocomplete="current-password"
                  v-model="form.password"
                  required
                  class="text-black"
                  placeholder="Password"
                />
                <InputError :message="form.errors.password" />
              </div>

              <!-- STATUS MESSAGE -->
              <div v-if="status" class="text-green-500 text-sm">{{ status }}</div>

              <!-- FORGOT PASSWORD -->
              <div v-if="canResetPassword" class="text-right">
                <TextLink href="/forgot-password" class="text-blue-500 text-xs"
                  >Lupa password?</TextLink
                >
              </div>

              <!-- REMEMBER ME -->
              <div class="flex items-center space-x-2">
                <input
                  id="remember"
                  type="checkbox"
                  v-model="form.remember"
                  class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                />
                <Label for="remember" class="cursor-pointer text-sm text-gray-700 dark:text-gray-300"
                  >Remember me</Label
                >
              </div>

              <!-- SUBMIT BUTTON -->
              <Button
                type="submit"
                class="mt-2 w-full bg-blue-500 text-white hover:bg-blue-600"
                :disabled="form.processing"
              >
                <LoaderCircle
                  v-if="form.processing"
                  class="h-4 w-4 animate-spin"
                />
                Log in
              </Button>
            </div>

            <!-- GOOGLE LOGIN -->
            <button
              @click="redirectToGoogle"
              type="button"
              class="flex w-full items-center justify-center gap-2 rounded-md py-3 mt-2 bg-white dark:bg-gray-700 shadow hover:shadow-md transition border border-gray-200 dark:border-gray-600"
            >
              <svg
                class="h-5 w-5"
                viewBox="0 0 533.5 544.3"
                xmlns="http://www.w3.org/2000/svg"
              >
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
              <span class="text-center text-sm">Login with Google</span>
            </button>

            <!-- REGISTER LINK -->
            <div class="text-center text-sm text-gray-700 dark:text-gray-300 mt-2">
              Belum punya akun?
              <TextLink href="/register" class="underline underline-offset-4"
                >Daftar sekarang</TextLink
              >
            </div>
          </form>
        </div>
      </div>
    </section>
  </div>
</template>
