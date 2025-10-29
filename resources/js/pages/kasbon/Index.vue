<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { useKasbonStore } from '@/stores/useKasbonStore';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { CheckCircle2, Edit, Search, Trash2, XCircle } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import { computed, onMounted, reactive, ref, Ref } from 'vue';
import Vue3Select from 'vue3-select';
import 'vue3-select/dist/vue3-select.css';

const toast = useToast();
const kasbonStore = useKasbonStore();
const { kasbonList, pagination, loading } = storeToRefs(kasbonStore);

const showCreateModal = ref(false);
const showEditModal = ref(false);
const currentKasbon = ref<any | null>(null);

const page = usePage();
const user = page.props.auth.user;

const filterStatus: Ref<string | { value: string; label: string }> = ref("");

const form = useForm({
  id: '',
  employee_id: 0,
  employee_name: "",
  amount: 0,
  description: '',
  status: 'Pending',
});

const breadcrumbs = [{ title: 'Kasbon', href: '/kasbon' }];

const filters = reactive({
  search: '',
});

const handleSearch = () => {

   const statusVal =
            typeof filterStatus.value === 'string'
                ? filterStatus.value
                : filterStatus.value?.value ?? '';

  kasbonStore.fetchKasbon(1, 50, {
    search: filters.search,
    status: statusVal
  });
};

// Pagination setup
const totalPages = computed(() => Math.ceil(pagination.value.total / pagination.value.per_page) || 1);
const goToPage = async (page: number) => {
  if (page < 1 || page > totalPages.value) return;
  await kasbonStore.fetchKasbon(page);
};
const nextPage = () => goToPage(pagination.value.current_page + 1);
const prevPage = () => goToPage(pagination.value.current_page - 1);

// Load initial data
onMounted(() => {
  kasbonStore.fetchKasbon();
});

// 🧾 Create new kasbon
const handleCreate = async () => {
  if (!form.employee_id || !form.amount) return toast.error('Karyawan dan nominal wajib diisi');

  try {
    await kasbonStore.storeKasbon(form);
    toast.success('Kasbon berhasil ditambahkan');
    form.reset();
    showCreateModal.value = false;
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? 'Gagal membuat kasbon');
  }
};

// ✏️ Edit kasbon
const handleEdit = (kasbon: any) => {
  currentKasbon.value = kasbon;
  form.id = kasbon.id;
  form.employee_id = kasbon.employee_id;
  form.amount = kasbon.amount;
  form.description = kasbon.description;
  form.status = kasbon.status;
  form.employee_name = kasbon.employee?.name;
  showEditModal.value = true;
};

// 💾 Update kasbon
const handleUpdate = async () => {
  if (!currentKasbon.value) return;
  try {
    await kasbonStore.updateKasbon(currentKasbon.value.id, form);
    toast.success('Kasbon berhasil diperbarui');
    showEditModal.value = false;
    currentKasbon.value = null;
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? 'Gagal memperbarui kasbon');
  }
};

// ❌ Delete
const handleDelete = async (id?: string) => {
  if (!id) return;
  if (!confirm('Yakin ingin menghapus kasbon ini?')) return;
  try {
    await kasbonStore.deleteKasbon(id);
    toast.success('Kasbon berhasil dihapus');
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? 'Gagal menghapus kasbon');
  }
};

// ✅ Approve kasbon
const handleApprove = async (id?: string) => {
  if (!id) return;
  if (!confirm('Setujui kasbon ini?')) return;
  try {
    await kasbonStore.approveKasbon(id);
    toast.success('Kasbon disetujui dan masuk ke mutasi');
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? 'Gagal menyetujui kasbon');
  }
};

// 🚫 Reject kasbon
const handleReject = async (id?: string) => {
  if (!id) return;
  const remark = prompt('Masukkan alasan penolakan:');
  if (!remark) return;
  try {
    await kasbonStore.rejectKasbon(id, remark);
    toast.success('Kasbon ditolak');
  } catch (error: any) {
    toast.error(error?.response?.data?.message ?? 'Gagal menolak kasbon');
  }
};
</script>

<template>

  <Head title="Kasbon Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-4">
      <!-- Header -->
      <div class="mb-4 flex flex-col sm:flex-row items-center justify-between gap-3">
        <!-- Filter kiri -->
        <div class="flex items-center gap-3 w-full sm:w-auto">

          <!-- Dropdown Status -->
          <Vue3Select v-model="filterStatus" :options="[
            { label: 'Semua Status', value: '' },
            { label: 'Pending', value: 'Pending' },
            { label: 'Approved', value: 'Approved' },
            { label: 'Rejected', value: 'Rejected' }
          ]" placeholder="Pilih Status" class="w-40" />

          <!-- Input Pencarian -->
          <input v-model="filters.search" type="text" placeholder="Cari data..."
            @keyup.enter="handleSearch"
            class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-full sm:w-56" />

          <!-- Tombol Cari -->
          <Button @click="handleSearch" class="bg-indigo-600 text-white hover:bg-indigo-700 flex items-center gap-1">
            <Search class="h-4 w-4" /> Cari
          </Button>

        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto rounded-md border">
        <Table>
          <TableHeader>
            <TableRow class="bg-gray-100">
              <TableHead class="px-3 py-2">Karyawan</TableHead>
              <TableHead class="px-3 py-2">Details</TableHead>
            </TableRow>
          </TableHeader>

          <TableBody>
            <template v-for="kas in kasbonList" :key="kas.id">
              <!-- Baris atas: Employee + Status -->
              <TableRow class="bg-gray-50">
                <TableCell colspan="2" class="px-3 py-2">
                  <div class="flex items-center justify-between">
                    <span class="font-semibold text-gray-800">
                      {{ kas.employee ? kas.employee.name : '-' }}
                    </span>
                    <span :class="[
                      'inline-block rounded-full border px-2.5 py-0.5 text-xs font-semibold',
                      {
                        'border-yellow-400 bg-yellow-50 text-yellow-700': kas.status === 'Pending',
                        'border-green-400 bg-green-50 text-green-700': kas.status === 'Approved',
                        'border-red-400 bg-red-50 text-red-700': kas.status === 'Rejected',
                      },
                    ]">
                      {{ kas.status }}
                    </span>
                  </div>
                </TableCell>
              </TableRow>

              <!-- Detail rows -->
              <TableRow>
                <TableCell class="w-1/4 px-3 py-1 font-medium">Tanggal</TableCell>
                <TableCell class="px-3 py-1">
                  {{ kas.created_at ? new Date(kas.created_at).toLocaleDateString() : '-' }}
                </TableCell>
              </TableRow>

              <TableRow>
                <TableCell class="w-1/4 px-3 py-1 font-medium">Jumlah Kasbon</TableCell>
                <TableCell class="px-3 py-1">
                  {{ Number(kas.amount).toLocaleString() }}
                </TableCell>
              </TableRow>

              <TableRow>
                <TableCell class="px-3 py-1 font-medium">Keterangan</TableCell>
                <TableCell class="px-3 py-1">
                  {{ kas.description || '-' }}
                </TableCell>
              </TableRow>

              <TableRow>
                <TableCell class="px-3 py-1 font-medium">Actions</TableCell>
                <TableCell class="flex gap-2 px-3 py-1">
                  <!-- Kondisi untuk OWNER -->
                  <template v-if="user.employee_status && user.employee_status.toUpperCase() === 'OWNER'">
                    <template v-if="kas.status === 'Pending'">
                      <Button size="icon" variant="ghost" @click="handleApprove(kas.id)">
                        <CheckCircle2 class="h-4 w-4 text-green-600" />
                      </Button>
                      <Button size="icon" variant="ghost" @click="handleReject(kas.id)">
                        <XCircle class="h-4 w-4 text-red-600" />
                      </Button>
                    </template>
                  </template>

                  <!-- Kondisi untuk NON-OWNER -->
                  <template v-else>
                    <template v-if="kas.status !== 'Approved'">
                      <Button size="icon" variant="ghost" @click="handleEdit(kas)">
                        <Edit class="h-4 w-4" />
                      </Button>
                      <Button size="icon" variant="ghost" @click="handleDelete(kas.id)">
                        <Trash2 class="h-4 w-4" />
                      </Button>
                    </template>
                  </template>
                </TableCell>
              </TableRow>

              <!-- Spacer antar employee -->
              <TableRow>
                <TableCell colspan="2" class="h-3"></TableCell>
              </TableRow>
            </template>
          </TableBody>
        </Table>
      </div>

      <!-- Pagination -->
      <div class="mt-4 flex justify-end space-x-2">
        <button @click="prevPage" :disabled="pagination.current_page === 1 || loading"
          class="rounded border px-3 py-1 hover:bg-gray-100 disabled:opacity-50">
          Previous
        </button>
        <span>Page {{ pagination.current_page }} / {{ totalPages }}</span>
        <button @click="nextPage" :disabled="pagination.current_page >= totalPages || loading"
          class="rounded border px-3 py-1 hover:bg-gray-100 disabled:opacity-50">
          Next
        </button>
      </div>

      <!-- Create Modal -->
      <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="w-96 rounded-lg bg-white p-6">
          <h2 class="mb-4 text-lg font-semibold">Tambah Kasbon</h2>
          <form @submit.prevent="handleCreate">
            <div class="mb-4">
              <Input v-model="form.employee_id" placeholder="Employee ID" required />
            </div>
            <div class="mb-4">
              <Input type="number" v-model="form.amount" placeholder="Jumlah Kasbon" required />
            </div>
            <div class="mb-4">
              <Input v-model="form.description" placeholder="Deskripsi" required />
            </div>
            <div class="flex justify-end gap-2">
              <Button variant="outline" type="button" @click="showCreateModal = false">Cancel</Button>
              <Button type="submit" class="bg-indigo-600 text-white hover:bg-indigo-700">Create</Button>
            </div>
          </form>
        </div>
      </div>

      <!-- Edit Modal -->
      <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="w-96 rounded-lg bg-white p-6">
          <h2 class="mb-4 text-lg font-semibold">Edit Kasbon</h2>
          <form @submit.prevent="handleUpdate">
            <div class="mb-4">
              <Input type="hidden" v-model="form.employee_id" readonly placeholder="Employee ID" required />
              <Input v-model="form.employee_name" readonly placeholder="Employee ID" required />
            </div>
            <div class="mb-4">
              <Input type="number" v-model="form.amount" placeholder="Jumlah Kasbon" required />
            </div>
            <div class="mb-4">
              <Input v-model="form.description" placeholder="Deskripsi" required />
            </div>
            <div class="flex justify-end gap-2">
              <Button variant="outline" type="button" @click="showEditModal = false">Cancel</Button>
              <Button type="submit" class="bg-indigo-600 text-white hover:bg-indigo-700">Update</Button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
