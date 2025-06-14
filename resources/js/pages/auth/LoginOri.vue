<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
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
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
  <AuthBase
    title="Log in to your account"
    description="Enter your email and password below to log in"
  >
    <Head title="Log in" />

    <div
      v-if="status"
      class="mb-6 text-center text-sm font-semibold text-green-600"
    >
      {{ status }}
    </div>

    <form
      @submit.prevent="submit"
      class="max-w-md w-full mx-auto bg-white p-8 rounded-lg shadow-lg space-y-6"
    >
      <div class="space-y-4">
        <div>
          <Label for="email" class="block text-sm font-medium text-gray-700"
            >Email address</Label
          >
          <Input
            id="email"
            type="email"
            required
            autofocus
            :tabindex="1"
            autocomplete="email"
            v-model="form.email"
            placeholder="email@example.com"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          />
          <InputError :message="form.errors.email" />
        </div>

        <div>
          <div
            class="flex items-center justify-between text-sm font-medium text-gray-700"
          >
            <Label for="password">Password</Label>
            <TextLink
              v-if="canResetPassword"
              :href="route('password.request')"
              class="text-indigo-600 hover:text-indigo-800"
              :tabindex="5"
            >
              Forgot password?
            </TextLink>
          </div>

          <Input
            id="password"
            type="password"
            required
            :tabindex="2"
            autocomplete="current-password"
            v-model="form.password"
            placeholder="Password"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          />
          <InputError :message="form.errors.password" />
        </div>

        <div class="flex items-center space-x-2">
          <Checkbox
            id="remember"
            v-model:checked="form.remember"
            :tabindex="4"
            class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
          />
          <Label for="remember" class="text-sm text-gray-700 cursor-pointer"
            >Remember me</Label
          >
        </div>

        <Button
          type="submit"
          class="w-full py-3 text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 flex justify-center items-center gap-2"
          :tabindex="4"
          :disabled="form.processing"
        >
          <LoaderCircle
            v-if="form.processing"
            class="h-5 w-5 animate-spin text-white"
          />
          Log in
        </Button>
      </div>

      <p class="text-center text-sm text-gray-500">
        Don't have an account?
        <TextLink
          :href="route('register')"
          class="text-indigo-600 hover:text-indigo-800"
          :tabindex="5"
          >Sign up</TextLink
        >
      </p>
    </form>
  </AuthBase>
</template>

