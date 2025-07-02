import axios from "axios";
import { ref } from "vue";

const loading = ref(false);

async function uploadPaymentProof(orderId: number, file: File) {
  loading.value = true;
  try {
    const formData = new FormData();
    formData.append("payment_proof", file);
    await axios.post(`/api/order/${orderId}/upload-payment-proof`, formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    });
  } finally {
    loading.value = false;
  }
}

export function useOrderCustomer() {
  return {
    loading,
    uploadPaymentProof,
  };
}