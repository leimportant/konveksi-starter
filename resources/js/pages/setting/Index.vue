<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { useSettingStore } from '@/stores/useSettingStore';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const settingStore = useSettingStore();

const breadcrumbs = [
  { title: 'Setting', href: '/setting' }
];

onMounted(() => {
    settingStore.fetchSettings();
});

const updateSettingValue = async (settingId: number, newValue: string) => {
    try {
        await settingStore.updateSetting(settingId, newValue);
        alert('Setting updated successfully!');
    } catch (error) {
        console.log(error);
        alert('Failed to update setting.');
    }
};
</script>

<template>
      <Head title="Setting Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-4">
          <div class="rounded-md border">
        <div v-if="settingStore.settings.length > 0">
            <Table>
                <TableHeader>
                    <TableRow class="bg-gray-100">
                        <TableHead>Name</TableHead>
                        <TableHead>Key</TableHead>
                        <TableHead>Current Value</TableHead>
                        <TableHead>Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="setting in settingStore.settings" :key="setting.id">
                        <TableCell>{{ setting.name }}</TableCell>
                        <TableCell>{{ setting.key }}</TableCell>
                        <TableCell>
                            <input type="text" v-model="setting.value" @keyup.enter="updateSettingValue(setting.id, setting.value)" />
                        </TableCell>
                        <TableCell>
                            <Button @click="updateSettingValue(setting.id, setting.value)">Update</Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
        
        <p v-else>No settings found.</p>
    </div>
    </div>
     </AppLayout>
</template>

