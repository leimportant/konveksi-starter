<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { useActivityGroupStore } from '@/stores/useActivityGroupStore';
import { useMenuStore } from '@/stores/useMenuStore';
import { useRoleStore, type Role } from '@/stores/useRoleStore';
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import { computed, onMounted, ref } from 'vue';
import { nextTick } from 'vue'


const toast = useToast();

const showCreateModal = ref(false);
const showAssignMenuModal = ref(false);
const currentRole = ref<Role | null>(null);
const allMenus = ref<any[]>([]);
const selectedMenus = ref<number[]>([]);

const form = useForm({ name: '', create_prod: 'Y' });

const breadcrumbs = [{ title: 'Role', href: '/roles' }];

const roleStore = useRoleStore();
const { items: roles } = storeToRefs(roleStore);
const activityGroupStore = useActivityGroupStore();

const menuStore = useMenuStore();
// --- Activity Group Logic ---
const showAssignActivityGroupModal = ref(false);

const allActivityGroups = ref<any[]>([]);
const selectedActivityGroups = ref<string[]>([]);

onMounted(() => {
    roleStore.fetchRoles();
});

const handleAssignMenu = async () => {
    if (!currentRole.value) return toast.error('No role selected');
    try {
        await roleStore.assignMenusToRole(currentRole.value.id, selectedMenus.value);
        toast.success('Menus assigned successfully');
        showAssignMenuModal.value = false;
        roleStore.loaded = false;
        await roleStore.fetchRoles(true);
    } catch (error: any) {
        toast.error(error?.response?.data?.message ?? 'Failed to assign menus');
    }
};

const openAssignActivityGroupModal = async (role: Role) => {
    currentRole.value = role;

    // 1️⃣ Ambil semua master activity group
    await activityGroupStore.fetchActivity();

    // 2️⃣ Simpan hasilnya ke lokal (tanpa ubah store global)
    allActivityGroups.value = [...activityGroupStore.items];

    // 3️⃣ Tandai activity group yang sudah dimiliki role ini
    selectedActivityGroups.value = role.activity_groups?.map((a: any) => String(a.activity_group_id || a.id)) || [];

    // 4️⃣ Buka modal
    showAssignActivityGroupModal.value = true;
};

const handleAssignActivityGroup = async () => {
    if (!currentRole.value) return toast.error('No role selected');

    try {
        await activityGroupStore.assignToRole(currentRole.value.id, selectedActivityGroups.value);

        toast.success('Activity Groups assigned successfully');
        showAssignActivityGroupModal.value = false;
        roleStore.loaded = false;
        await nextTick()
        await roleStore.fetchRoles(true);
    } catch (error: any) {
        toast.error(error?.response?.data?.message ?? 'Failed to assign activity groups');
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
    if (!form.name) return toast.error('Name is required');
    if (!form.create_prod) form.create_prod = 'N';
    try {
        await roleStore.createRole(form.name, form.create_prod);
        toast.success('Role created successfully');
        form.reset();
        roleStore.loaded = false;
        await roleStore.fetchRoles(true);
        showCreateModal.value = false;
    } catch (error: any) {
        const nameError = error?.response?.data?.errors?.name?.[0];
        toast.error(nameError || 'Failed to create role');
    }
};

const handleDelete = async (id: number) => {
    if (!confirm('Are you sure you want to delete this role?')) return;
    try {
        await roleStore.deleteRole(id);
        toast.success('Role deleted successfully');
        roleStore.loaded = false;
        await roleStore.fetchRoles();
    } catch (error: any) {
        toast.error(error?.response?.data?.message ?? 'Failed to delete role');
    }
};

// --- Menu Logic ---
const isChecked = (id: number) => selectedMenus.value.includes(id);

const truncate = (text: string, max = 8) => {
    if (!text) return '';
    return text.length > max ? text.slice(0, max) + '...' : text;
};

const toggleParent = (menu: any) => {
    const menuId = menu.id;
    const childIds = menu.children?.map((c: { id: number }) => c.id) || [];

    if (isChecked(menuId)) {
        selectedMenus.value = selectedMenus.value.filter((id) => id !== menuId && !childIds.includes(id));
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
        selectedMenus.value = selectedMenus.value.filter((id) => id !== childId);
        const parent = allMenus.value.find((m) => m.id === parentId);
        const anySelected = parent?.children?.some((c: { id: number }) => selectedMenus.value.includes(c.id));
        if (!anySelected) {
            selectedMenus.value = selectedMenus.value.filter((id) => id !== parentId);
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
        const allIds = allMenus.value
            .flatMap((menu) => {
                const childrenIds = menu.children?.map((c: any) => c.id) || [];
                return [menu.id, ...childrenIds];
            })
            .filter((id): id is number => typeof id === 'number');

        selectedMenus.value = [...new Set(allIds)];
    } else {
        selectedMenus.value = [];
    }
};

const isAllChecked = computed(() => {
    const allIds = allMenus.value
        .flatMap((menu) => {
            const childrenIds = menu.children?.map((c: any) => c.id) || [];
            return [menu.id, ...childrenIds];
        })
        .filter((id): id is number => typeof id === 'number');

    return allIds.length > 0 && allIds.every((id) => selectedMenus.value.includes(id));
});
</script>

<template>
    <Head title="Role Management" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-4">
            <div class="mb-2 flex items-center justify-between gap-2">
                <Button
                    @click="showCreateModal = true"
                    class="h-10 rounded-md bg-indigo-600 py-2 text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500"
                >
                    <Plus class="h-4 w-4" />
                    Tambah Role
                </Button>
            </div>

            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-gray-100">
                            <TableHead>Name</TableHead>
                            <TableHead>Apparel (Menu)</TableHead>
                            <TableHead>Buat Produksi</TableHead>
                            <TableHead class="w-24">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="role in roles" :key="role.id">
                            <TableCell>{{ role.name }}</TableCell>
                            <TableCell>
                                <div
                                    class="flex cursor-pointer flex-wrap gap-1 rounded-md p-2 transition hover:bg-gray-50"
                                    @click="openAssignActivityGroupModal(role)"
                                >
                                    <template v-if="role.activity_groups && role.activity_groups.length > 0">
                                        <span
                                            v-for="a in role.activity_groups"
                                            :key="a.activity_group_id"
                                            class="rounded-full border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-xs font-medium text-gray-700"
                                            :title="a.activity_group_id"
                                        >
                                            {{ truncate(a.activity_group_id) }}
                                        </span>
                                    </template>

                                    <span v-else class="text-sm italic text-gray-400">No activity groups</span>
                                </div>
                            </TableCell>
                            <TableCell>
                                <span v-if="role.create_prod == 'Y'" class="text-green-600 font-semibold">Yes</span>
                                <span v-else class="text-red-600 font-semibold">No</span>
                            </TableCell>

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

            <!-- Assign Activity Group Modal -->
            <div v-if="showAssignActivityGroupModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                <div class="w-96 rounded-lg bg-white p-6">
                    <h2 class="mb-4 text-lg font-semibold">Assign Activity Group untuk {{ currentRole?.name }}</h2>

                    <div class="mb-4 max-h-60 overflow-y-auto">
                        <div v-for="group in allActivityGroups" :key="group.id" class="mb-2 flex items-center">
                            <!-- ✅ Otomatis checked berdasarkan selectedActivityGroups -->
                            <input type="checkbox" :id="group.id" :value="group.id" v-model="selectedActivityGroups" class="mr-2" />
                            <label :for="group.id">{{ group.name }}</label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2">
                        <Button variant="outline" @click="showAssignActivityGroupModal = false"> Cancel </Button>
                        <Button @click="handleAssignActivityGroup">Save</Button>
                    </div>
                </div>
            </div>

            <!-- Assign Menu Modal -->
            <div v-if="showAssignMenuModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                <div class="w-96 rounded-lg bg-white p-6">
                    <h2 class="mb-4 text-lg font-semibold">Assign Menu to {{ currentRole?.name }}</h2>

                    <!-- Check All -->
                    <div class="mb-2 flex items-center border-b pb-2">
                        <input type="checkbox" id="check-all" :checked="isAllChecked" @change="toggleAll" class="mr-2" />
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
                                <div v-for="child in menu.children" :key="child.id" class="mb-1 flex items-center">
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
                        <Button type="button" variant="outline" @click="showAssignMenuModal = false" class="h-10">Cancel</Button>
                        <Button type="submit" @click="handleAssignMenu">Assign</Button>
                    </div>
                </div>
            </div>

            <!-- Create Modal -->
            <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                <div class="w-96 rounded-lg bg-white p-6">
                    <h2 class="mb-4 text-lg font-semibold">Tambah Role Baru</h2>
                    <form @submit.prevent="handleCreate">
                        <div class="mb-4">
                            <Input v-model="form.name" placeholder="Role Name" required />
                        </div>
                        <div class="mb-4">  
                            <label class="block mb-1 font-medium">Buat Produksi</label>
                            <select v-model="form.create_prod" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="Y">Yes</option>
                                <option value="N">No</option>
                            </select>
                        </div>
                        <div class="flex justify-end gap-2">
                            <Button type="button" variant="outline" @click="showCreateModal = false" class="h-10">Cancel</Button>
                            <Button
                                type="submit"
                                class="h-10 rounded-md bg-indigo-600 py-2 text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500"
                                >Create</Button
                            >
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
