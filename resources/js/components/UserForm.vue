<script setup lang="ts">
import { ref, watch } from 'vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { useToast } from "@/composables/useToast";
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select-input';

// Initialize toast
const toast = useToast();

interface Props {
  form: {
    name: string;
    email: string;
    password: string;
    role: string;
    active: boolean;
    reset?: () => void;
  };
  roles: { id: number; name: string }[];
  show: boolean;
}

const emit = defineEmits(['close', 'submit']);
const props = defineProps<Props>();

const localForm = ref({ ...props.form });

// Sync props.form -> localForm (reactivity-safe)
watch(
  () => props.form,
  (newVal) => {
    localForm.value = { ...newVal };
  },
  { deep: true, immediate: true }
);

const handleSubmit = async () => {
  if (!localForm.value.name || !localForm.value.email || !localForm.value.role) {
    toast.error('All fields are required');
    return;
  }
  emit('submit', localForm.value);
};

const closeModal = () => {
  emit('close');
};
</script>

<template>
  <form @submit.prevent="handleSubmit" class="space-y-4">
    <div>
      <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
      <Input v-model="localForm.name" id="name" placeholder="Full Name" required />
    </div>

    <div>
      <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
      <Input v-model="localForm.email" id="email" type="email" placeholder="Email Address" required />
    </div>

    <div>
      <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
      <Input
        v-model="localForm.password"
        id="password"
        type="password"
        placeholder="New Password"
        autocomplete="new-password"
        required
      />
    </div>

    <div>
      <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
      <Select v-model="localForm.role" required>
        <SelectTrigger id="role">
          <SelectValue placeholder="Select role" />
        </SelectTrigger>
        <SelectContent>
          <SelectGroup>
            <SelectLabel>Available Roles</SelectLabel>
            <SelectItem
              v-for="role in roles"
              :key="role.id"
              :value="role.name"
            >
              {{ role.name }}
            </SelectItem>
          </SelectGroup>
        </SelectContent>
      </Select>
    </div>

    <div class="flex justify-end gap-2 pt-2">
      <Button type="button" variant="outline" @click="closeModal">Cancel</Button>
      <Button type="submit">Save</Button>
    </div>
  </form>
</template>

<style scoped>
/* Responsive design */
@media (max-width: 640px) {
  form {
    width: 100%;
  }
}
</style>
