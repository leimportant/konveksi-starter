<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted,  computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Trash2, Plus } from 'lucide-vue-next';
import { useToast } from "@/composables/useToast";
import { useRoleStore, type Role } from '@/stores/useRoleStore';
import { useMenuStore } from '@/stores/useMenuStore';
import { storeToRefs } from 'pinia';

const toast = useToast();

const showCreateModal = ref(false);
const showAssignMenuModal = ref(false);
const currentRole = ref<Role | null>(null);
const allMenus = ref<any[]>([]);
const selectedMenus = ref<number[]>([]);

const form = useForm({ name: '' });

const breadcrumbs = [{ title: 'Role', href: '/roles' }];

const roleStore = useRoleStore();
const { items: roles } = storeToRefs(roleStore);

const menuStore = useMenuStore();

onMounted(() => {
  roleStore.fetchRoles();
});

const handleAssignMenu = async () => {
  if (!currentRole.value) return toast.error("No role selected");
  try {
    await roleStore.assignMenusToRole(currentRole.value.id, selectedMenus.value);
    toast.success("Menus assigned successfully");
    showAssignMenuModal.value = false;
    roleStore.loaded = false;
    await roleStore.fetchRoles();
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? "Failed to assign menus");
  }
};

const openAssignMenuModal = async (role: Role) => {
  currentRole.value = role;
  allMenus.value = await menuStore.fetchAllMenus(role.id); // Must return children too
  selectedMenus.value = [];
  allMenus.value.forEach((menu: any) => {
    if (menu.checked) {
      selectedMenus.value.push(menu.id);
    }
    menu.children?.forEach((child: any) => {
      if (child.checked) {
        selectedMenus.value.push(child.id);
      }
    });
  });
  showAssignMenuModal.value = true;
};

const handleCreate = async () => {
  if (!form.name) return toast.error("Name is required");
  try {
    await roleStore.createRole(form.name);
    toast.success("Role created successfully");
    form.reset();
    roleStore.loaded = false;
    await roleStore.fetchRoles();
    showCreateModal.value = false;
  } catch (error: any) {
    const nameError = error?.response?.data?.errors?.name?.[0];
    toast.error(nameError || "Failed to create role");
  }
};

const handleDelete = async (id: number) => {
  if (!confirm('Are you sure you want to delete this role?')) return;
  try {
    await roleStore.deleteRole(id);
    toast.success("Role deleted successfully");
    roleStore.loaded = false;
    await roleStore.fetchRoles();
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? "Failed to delete role");
  }
};

// --- Menu Logic ---
const isChecked = (id: number) => selectedMenus.value.includes(id);

const toggleParent = (menu: any) => {
  const menuId = menu.id;
  const childIds = menu.children?.map((c: { id: number }) => c.id) || [];

  if (isChecked(menuId)) {
    selectedMenus.value = selectedMenus.value.filter(id => id !== menuId && !childIds.includes(id));
  } else {
    selectedMenus.value.push(menuId);
    for (const childId of childIds) {
      if (!selectedMenus.value.includes(childId)) {
        selectedMenus.value.push(childId);
      }
    }
  }
};

const toggleChild = (parentId: number, childId: number) => {
  if (isChecked(childId)) {
    selectedMenus.value = selectedMenus.value.filter(id => id !== childId);
    const parent = allMenus.value.find(m => m.id === parentId);
    const anySelected = parent?.children?.some((c: { id: number }) => selectedMenus.value.includes(c.id));
    if (!anySelected) {
      selectedMenus.value = selectedMenus.value.filter(id => id !== parentId);
    }
  } else {
    selectedMenus.value.push(childId);
    if (!selectedMenus.value.includes(parentId)) {
      selectedMenus.value.push(parentId);
    }
  }
};

const toggleAll = (event: Event) => {
  const checked = (event.target as HTMLInputElement).checked;

  if (checked) {
    const allIds = allMenus.value.flatMap(menu => {
      const childrenIds = menu.children?.map((c: any) => c.id) || [];
      return [menu.id, ...childrenIds];
    }).filter((id): id is number => typeof id === 'number');

    selectedMenus.value = [...new Set(allIds)];
  } else {
    selectedMenus.value = [];
  }
};

const isAllChecked = computed(() => {
  const allIds = allMenus.value.flatMap(menu => {
    const childrenIds = menu.children?.map((c: any) => c.id) || [];
    return [menu.id, ...childrenIds];
  }).filter((id): id is number => typeof id === 'number');

  return allIds.length > 0 && allIds.every(id => selectedMenus.value.includes(id));
});


</script>

<template>
  <Head title="Role Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-4">
      <div class="flex justify-between items-center gap-2 mb-2">
        <Button @click="showCreateModal = true"  class="bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">
          <Plus class="h-4 w-4" />
          Tambah Role
        </Button>
      </div>

      <div class="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow class="bg-gray-100">
              <TableHead>Name</TableHead>
              <TableHead class="w-24">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="role in roles" :key="role.id">
              <TableCell>{{ role.name }}</TableCell>
              <TableCell class="flex gap-2">
                <Button variant="ghost" size="icon" @click="openAssignMenuModal(role)">
                  <Plus class="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="icon" @click="handleDelete(role.id)">
                  <Trash2 class="h-4 w-4" />
                </Button>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <!-- Assign Menu Modal -->
      <div v-if="showAssignMenuModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg w-96">
          <h2 class="text-lg font-semibold mb-4">Assign Menu to {{ currentRole?.name }}</h2>

          <!-- Check All -->
          <div class="flex items-center mb-2 border-b pb-2">
            <input
              type="checkbox"
              id="check-all"
              :checked="isAllChecked"
              @change="toggleAll"
              class="mr-2"
            />
            <label for="check-all" class="font-medium">Check / Uncheck All</label>
          </div>

          <div class="mb-4 max-h-60 overflow-y-auto">
            <div v-for="menu in allMenus" :key="menu.id" class="mb-2">
              <div class="flex items-center">

                <input
                  type="checkbox"
                  :id="`menu-${menu.id}`"
                  :checked="isChecked(menu.id)"
                  @change="toggleParent(menu)"
                  class="mr-2"
                />
                <label :for="`menu-${menu.id}`"> {{ menu.title }}</label>
              </div>
              <div v-if="menu.children && menu.children.length" class="ml-6">
                <div v-for="child in menu.children" :key="child.id" class="flex items-center mb-1">
              <input
                    type="checkbox"
                    :id="`menu-${child.id}`"
                    :checked="isChecked(child.id)"
                    @change="toggleChild(menu.id, child.id)"
                    class="mr-2"
                  />
                  <label :for="`menu-${child.id}`"> {{ child.title }}</label>
                </div>
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-2">
            <Button type="button" variant="outline" @click="showAssignMenuModal = false">Cancel</Button>
            <Button type="submit" @click="handleAssignMenu">Assign</Button>
          </div>
        </div>
      </div>

      <!-- Create Modal -->
      <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg w-96">
          <h2 class="text-lg font-semibold mb-4">Tambah Role Baru</h2>
          <form @submit.prevent="handleCreate">
            <div class="mb-4">
              <Input v-model="form.name" placeholder="Role Name" required />
            </div>
            <div class="flex justify-end gap-2">
              <Button type="button" variant="outline" @click="showCreateModal = false">Cancel</Button>
              <Button type="submit" class="bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">Create</Button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
