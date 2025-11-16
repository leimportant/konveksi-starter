import axios from "axios";
import { defineStore } from "pinia";
import { computed, ref } from "vue";
import parsePhoneNumber from 'libphonenumber-js'


interface PayrollDetail {
  id: string;
  qty: number;
  price?: number | null;
  model_desc: string;
  variant: string;
  size_id: string;
  activity_role_id?: number | string;
  activity_role?: { id: number | string; name?: string };
  created_at?: string;
}

export interface EmployeePayroll {
  id: string;
  employee_id: number;
  activity_role_id: number;
  activity_role?: {
    id: number;
    name: string;
  };
  phone_number?: string; 
  employee_name?: string;
  status: string;
  period_start?: string;
  period_end?: string;
  employee?: {
    id: number;
    name: string;
  };
  total_qty: number;
  price_per_pcs: number;
  total_upah: number;
  uang_makan: number;
  lembur: number;
  potongan: number;
  total_gaji: number;
  net_gaji: number;
  saldo_kasbon: number;

  details: PayrollDetail[];
}

export interface Payslip {
  id: string;
  employee_name?: string;
  employee_role?: string;
  company_logo?: string;
  period_start?: string;
  period_end?: string;
  payroll_date?: string;
  total_upah?: number;
  uang_makan?: number;
  lembur?: number;
  potongan?: number;
  saldo_kasbon?: number;
  net_gaji?: number;
  employee?: {
    id: number;
    name: string;
    employee_role?: string;
  };
   activity_role?: {
    id: number;
    name: string;
  };
}

export const usePayrollStore = defineStore("payroll", () => {
  const employees = ref<EmployeePayroll[]>([]);
  const startDate = ref<string>("");
  const endDate = ref<string>("");
  const search = ref<string>("");
  const searchEmployee = ref<string>("");
  const selectedEmployees = ref<number[]>([]);
  const isLoading = ref(false)
  const currentSlip = ref<Payslip | null>(null);

  const totalSummary = computed(() => ({
    totalGaji: employees.value.reduce((s, e) => s + e.total_gaji, 0),
    totalPotongan: employees.value.reduce((s, e) => s + e.potongan, 0),
    totalMeal: employees.value.reduce((s, e) => s + e.uang_makan, 0),
  }));

    const loadData = async (searchQuery?: string) => {
    if (isLoading.value) return;
    isLoading.value = true;

    if (!startDate.value || !endDate.value) return;

   if (searchQuery !== undefined) {
    search.value = searchQuery || "";
  }

    try {
      const res = await axios.get("/api/payroll", {
        params: {
          start: startDate.value,
          end: endDate.value,
          search: search.value ?? "",
        },
      });

      employees.value = res.data.data;
      selectedEmployees.value = [];
    } catch (err) {
      console.error("Failed to load payroll:", err);
    }
    finally {
      isLoading.value = false;
    }
  };

  const loadPayrollData = async () => {
    if (isLoading.value) return;
    isLoading.value = true;

    if (!startDate.value || !endDate.value) return;

    try {
      const res = await axios.get("/api/payroll/preview", {
        params: {
          start: startDate.value,
          end: endDate.value,
          search: searchEmployee.value,
        },
      });

      employees.value = res.data.data;
      selectedEmployees.value = [];
    } catch (err) {
      console.error("Failed to load payroll:", err);
    }
    finally {
      isLoading.value = false;
    }
  };

const updatePotongan = (id: number, pot: number) => {
  const emp = employees.value.find((e) => e.employee_id === id);
  if (emp) {
    emp.potongan = Number(String(pot).replace(/[^0-9.-]/g, "")); // hilangkan koma/dot selain angka

    const totalUpah = Number(String(emp.total_upah).replace(/[^0-9.-]/g, ""));
    const uangMakan = Number(String(emp.uang_makan).replace(/[^0-9.-]/g, ""));
    const lembur = Number(String(emp.lembur ?? 0).replace(/[^0-9.-]/g, ""));
    const potongan = Number(String(emp.potongan).replace(/[^0-9.-]/g, ""));

    emp.total_gaji = totalUpah + uangMakan + lembur - potongan;
  }
};


  const toggleSelect = (id: number) => {
    const i = selectedEmployees.value.indexOf(id);
    if (i === -1) selectedEmployees.value.push(id);
    else selectedEmployees.value.splice(i, 1);
  };

  const formatPhoneNumber = (phone: string) => {
    try {
      const phoneNumber = parsePhoneNumber(phone, 'ID')
      if (phoneNumber) {
        return phoneNumber.format('INTERNATIONAL')
      }
    } catch (error) {
      console.error('Error parsing phone number:', error)
    }
    return phone
  }

  // ✅ Close 1 karyawan
const closeSingle = async (emp: EmployeePayroll) => {
    try {

      const detailsPayload = emp.details.map(d => ({
        size_id: d.size_id,
        activity_role_id: d.activity_role_id,
        variant: d.variant,
        qty: d.qty,
        price: d.price,
        model_desc: d.model_desc,
        created_at: d.created_at,
      }));

      await axios.post("/api/payroll/close", {
        period_start: startDate.value,
        period_end: endDate.value,
        employees: [
          {
            employee_id: emp.employee_id,
            activity_role_id: emp.activity_role_id,
            total_gaji: emp.total_gaji,
            uang_makan: emp.uang_makan,
            lembur: emp.lembur ?? 0,
            potongan: emp.potongan ?? 0,
            details: detailsPayload,
          },
        ],
      });

      emp.status = "closed";

      // ============= SEND WHATSAPP =============
      const slip = `
*Slip Gaji Periode*
${startDate.value} s/d ${endDate.value}

*Nama:* ${emp.employee_name}
*ID:* ${emp.employee_id}

*Upah Pokok:* Rp ${emp.total_upah.toLocaleString()}
*Uang Makan:* Rp ${emp.uang_makan.toLocaleString()}
*Lembur:* Rp ${(emp.lembur ?? 0).toLocaleString()}
*Potongan:* Rp ${(emp.potongan ?? 0).toLocaleString()}

*Total Diterima:* Rp ${emp.total_gaji.toLocaleString()}

Terima kasih ✅
`;

      const hp = formatPhoneNumber(emp.phone_number ?? "")
        .replace(/[^0-9]/g, "")
        .replace(/^0/, "62"); // 08xx → 628xx

      if (hp) {
        const url = `https://wa.me/${hp}?text=${encodeURIComponent(slip)}`;
        window.open(url, "_blank");
      }

      selectedEmployees.value = selectedEmployees.value.filter(
        id => id !== emp.employee_id
      );
    } catch (err) {
      console.error(err);
      alert("Gagal closing payroll");
    }

    // refresh data
    loadPayrollData();
  };

  // ✅ Bulk Close
const closePayroll = async () => {
  if (selectedEmployees.value.length === 0) return;

  try {
    const payload = {
      period_start: startDate.value,
      period_end: endDate.value,
      employees: employees.value
        .filter(e => selectedEmployees.value.includes(e.employee_id))
        .map(e => ({
          employee_id: e.employee_id,
          activity_role_id: e.activity_role_id,
          total_gaji: e.total_gaji,
          uang_makan: e.uang_makan,
          lembur: e.lembur ?? 0,
          potongan: e.potongan ?? 0,
          details: e.details.map(d => ({ // <-- harus pakai e.details, bukan emp.details
            size_id: d.size_id,
            variant: d.variant,
            activity_role_id: d.activity_role_id,
            qty: d.qty,
            price: d.price,
            model_desc: d.model_desc,
            created_at: d.created_at,
          })),
        })),
    };

    const { data } = await axios.post("/api/payroll/close", payload);

    // ✅ Mark locally closed
    employees.value.forEach(emp => {
      if (selectedEmployees.value.includes(emp.employee_id)) {
        emp.status = "closed";
      }
    });

    // ✅ Kirim WA slip gaji ke masing2
    payload.employees.forEach(empPayload => {
      const e = employees.value.find(x => x.employee_id === empPayload.employee_id);
      if (!e) return;

      const slip = `
*Slip Gaji Periode*
${startDate.value} s/d ${endDate.value}

*Nama:* ${e.employee_name}
*ID:* ${e.employee_id}

*Upah Pokok:* Rp ${e.total_upah.toLocaleString()}
*Uang Makan:* Rp ${e.uang_makan.toLocaleString()}
*Lembur:* Rp ${(e.lembur ?? 0).toLocaleString()}
*Potongan:* Rp ${(e.potongan ?? 0).toLocaleString()}

*Total Diterima:* Rp ${e.total_gaji.toLocaleString()}

Terima kasih ✅
      `;

      const hp = formatPhoneNumber(e.phone_number ?? "")
        .replace(/[^0-9]/g, "")
        .replace(/^0/, "62"); // 08xx → 628xx

      if (hp.length >= 10) {
        const url = `https://wa.me/${hp}?text=${encodeURIComponent(slip)}`;
        window.open(url, "_blank");
      }
    });

    // ✅ bersihkan selection
    selectedEmployees.value = [];

    // ✅ reload data
    await loadPayrollData();

    return data;

  } catch (e) {
    console.error(e);
    alert("Gagal closing payroll");
  }
};


 const fetchPayslip = async (id: string) => {
  try {
    const { data } = await axios.get(`/api/payroll/slip/${id}`);
    currentSlip.value = data.data;
    return data.data;
  } catch (error) {
    console.error(error);
  }
};


  return {
    employees,
    startDate,
    endDate,
    selectedEmployees,
    totalSummary,
    loadData,
    loadPayrollData,
    updatePotongan,
    toggleSelect,
    closeSingle,
    closePayroll,
    fetchPayslip,
    currentSlip,
  };
});