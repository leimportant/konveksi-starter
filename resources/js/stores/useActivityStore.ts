import { defineStore } from 'pinia';
import { ref, reactive } from 'vue';
// import { useToast } from '@/composables/useToast';

interface Role {
  id: number;
  name: string;
}
interface ActivityItem {
  role_id: number;
  price: number;
}

export const useActivityStore = defineStore('activity', () => {
//   const toast = useToast();

  const roles = ref<Role[]>([]); // Array of roles
  const rolesMap = reactive<Record<number, string>>({}); // Map for quick lookup
  const items = ref<ActivityItem[]>([]); // Array to hold activity items

  // Fetch roles from API
  const fetchRoles = async () => {
    try {
      const response = await fetch('/api/activity-roles');
      const json = await response.json();
      const data: Role[] = json.data;

      roles.value = data;
      // Build roles map for easy access
      data.forEach(r => {
        rolesMap[r.id] = r.name;
      });

      // Initialize items for each role, preserving existing prices
      items.value = data.map(r => {
        const existing = items.value.find(v => v.role_id === r.id);
        return { role_id: r.id, price: existing?.price ?? 0 };
      });
    } catch (error) {
        console.error('Failed to fetch menus', error);
    }
  };

  const updatePrice = (index: number, value: string) => {
    const price = parseFloat(value);
    items.value[index].price = isNaN(price) ? 0 : price;
  };

  return {
    roles,
    rolesMap,
    items,
    fetchRoles,
    updatePrice
  };
});
