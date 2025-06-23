<template>
  <div class="cart-container bg-white shadow rounded-lg p-4">
    <h2 class="text-lg font-semibold mb-4">Your Cart</h2>

    <!-- Cart Items -->
    <div v-if="cartItems.length > 0" class="space-y-4">
      <div
        v-for="item in cartItems"
        :key="item.id"
        class="flex items-center justify-between border-b pb-2"
      >
        <div class="flex items-center gap-4">
          <div class="text-sm font-medium">
            Product ID: {{ item.product_id }}
          </div>
          <div class="text-sm text-gray-500">
            Quantity: {{ item.quantity }}
          </div>
        </div>
        <button
          class="text-red-500 hover:text-red-700"
          @click="removeFromCart(item.id)"
        >
          Remove
        </button>
      </div>
    </div>

    <!-- Empty Cart Message -->
    <div v-else class="text-center text-gray-500">
      <p>Your cart is empty.</p>
    </div>

    <!-- Clear Cart Button -->
    <div class="mt-4">
      <button
        class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600"
        @click="clearCart"
        :disabled="loading"
      >
        Clear Cart
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useCartStore } from '@/stores/useCartStore'
import { onMounted } from 'vue'

const cartStore = useCartStore()

const cartItems = cartStore.cartItems
const loading = cartStore.loading

const removeFromCart = async (id: number) => {
  try {
    await cartStore.removeFromCart(id)
  } catch (error) {
    console.error(error)
  }
}

const clearCart = async () => {
  try {
    await cartStore.clearCart()
  } catch (error) {
    console.error(error)
  }
}

onMounted(async () => {
  await cartStore.fetchCartItems()
})
</script>

<style scoped>
.cart-container {
  max-width: 400px;
  margin: auto;
}
</style>