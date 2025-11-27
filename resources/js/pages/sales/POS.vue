<template>
    <Head title="Point of Sale" />
    <AppLayout>
        <div class="min-h-screen space-y-4 bg-gray-50 p-2 md:p-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-5 md:gap-2">
                <!-- Products Section -->
                <section class="flex flex-col md:col-span-2 lg:col-span-2">
                    <div class="mb-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-sm font-semibold text-gray-800">Produk Tersedia</h2>
                        <input
                            type="text"
                            v-model="searchText"
                            @input="onSearchInput"
                            placeholder="Cari produk..."
                            class="focus:ring-primary-500 w-full max-w-xs rounded-md border border-gray-300 px-2 py-1 text-xs focus:outline-none focus:ring-2 md:text-sm"
                        />
                        <Button
                            @click="showListProductUnlistedModal = true"
                            class="flex items-center justify-center rounded-md bg-purple-600 p-2 text-white hover:bg-purple-700"
                            title="Tambah Produk"
                        >
                            <Plus class="h-4 w-4" /> Tambah Produk
                        </Button>
                    </div>
                    <div class="flex flex-col gap-2 md:gap-3">
                        <div
                            v-for="product in products"
                            :key="product.id"
                            @click="viewProductDetail(product)"
                            class="flex cursor-pointer flex-row items-center gap-2 rounded-lg border border-gray-200 bg-white p-2 shadow-sm transition hover:shadow-md md:gap-3 md:p-3"
                        >
                            <!-- Column 1: Image -->
                            <div class="flex-shrink-0">
                                <img
                                    v-if="product.image_path"
                                    :src="getImageUrl(product.image_path)"
                                    alt="Gambar produk"
                                    class="h-16 w-16 rounded object-cover"
                                />
                                <div v-else class="flex h-16 w-16 select-none items-center justify-center rounded bg-gray-200 text-xs text-gray-400">
                                    Tidak Ada Gambar
                                </div>
                            </div>
                            <!-- Column 2: Info -->
                            <div class="flex min-w-0 flex-1 flex-col justify-between">
                                <div class="flex items-center gap-2">
                                    <p class="flex-1 truncate text-xs font-semibold text-gray-900 md:text-sm">{{ product.product_name }}</p>
                                    <template v-if="product.discount && product.price">
                                        <span
                                            v-if="product.discount > 0"
                                            class="ml-1 rounded bg-orange-500 px-2 py-0.5 text-[14px] font-bold text-white md:text-xs"
                                        >
                                            {{ Math.round((product.discount / product.price) * 100) }}%
                                        </span>
                                    </template>
                                </div>
                                <p class="text-[10px] text-gray-400 md:text-xs">Stok: {{ product.qty_available }} / {{ product.uom_id }}</p>

                                <!-- Pilih Varian -->

                                <!-- Pilih Ukuran -->
                                <div v-if="product.variant" class="mt-2">
                                    <p class="text-xs text-gray-500">Tersedia Ukuran:</p>
                                    <div class="mt-1 flex flex-wrap gap-2">
                                        <div
                                            class="rounded-full border px-3 py-1 text-xs font-medium"
                                            v-for="size in getSizesForVariant(product.sizes, product.variant)"
                                            :key="size.size_id"
                                        >
                                            {{ size.size_id }}
                                        </div>

                                        <!-- <button
          v-for="size in getSizesForVariant(product.sizes, product.variant)"
          :key="size.size_id"
          @click="selectSize(product, size)"
          :class="[
            'rounded-full border px-3 py-1 text-xs font-medium',
            product.sizes.some(s => s.size_id === size.size_id && s.qty_stock > 0)
              ? 'border-green-600 bg-green-600 text-white'
              : 'border-gray-300 text-gray-700 hover:bg-gray-100'
          ]"
        >
          {{ size.size_id }}
        </button> -->
                                    </div>
                                </div>

                                <div>
                                    <template v-if="product.discount && product.discount > 0">
                                        <p class="text-[10px] text-gray-400 line-through md:text-xs">{{ formatRupiah(product.price) }}</p>
                                        <p class="text-xs font-semibold text-green-600 md:text-sm">
                                            {{ formatRupiah(product.price_sell ?? product.price - product.discount) }}
                                        </p>
                                        <p class="text-[10px] text-green-500 md:text-xs">(Diskon {{ formatRupiah(product.discount) }})</p>
                                    </template>
                                    <p v-else class="text-xs font-semibold text-gray-700 md:text-sm">{{ formatRupiah(product.price) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Cart Sidebar -->

                <section class="flex flex-col bg-gray-100 md:col-span-2 lg:col-span-3">
                    <div class="flex flex-wrap gap-1 p-2 md:gap-1">
                        <Button
                            variant="outline"
                            size="sm"
                            @click="openDiscountDialog"
                            :disabled="selectedForDiscount.length === 0"
                            class="flex items-center gap-1 bg-indigo-100"
                        >
                            <PercentIcon class="h-4 w-4" /> Disc ({{ selectedForDiscount.length }})
                        </Button>
                        <Button variant="outline" size="sm" @click="showQrScanner = true" class="flex items-center gap-1">
                            <ScanQrCode class="h-4 w-4" /> QR
                        </Button>
                        <Button variant="outline" size="sm" @click="() => applyCustomer()" class="flex items-center gap-1">
                            <UserPlusIcon class="h-4 w-4" /> Cust
                        </Button>

                        <Button variant="outline" size="sm" @click="applyReturn" class="flex items-center gap-1">
                            <ReplaceAll class="h-4 w-4" /> Retur
                        </Button>

                        <Button variant="outline" size="sm" @click="rePrintDialog()" class="flex items-center gap-1">
                            <PrinterCheckIcon class="h-4 w-4" /> RePrint
                        </Button>
                        <Button variant="outline" size="sm" @click="clearCart" class="flex items-center gap-1">
                            <Trash2 class="h-4 w-4" /> Clear
                        </Button>
                    </div>

                    <div v-if="selectedProducts.length === 0" class="select-none py-8 text-center text-xs text-gray-400">
                        Tidak ada item di keranjang
                    </div>

                    <div class="overflow-auto p-2">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="text-xs">#</TableHead>
                                    <TableHead class="text-xs">Nama</TableHead>
                                    <TableHead class="text-center text-xs">Qty</TableHead>
                                    <TableHead class="text-center text-xs">Total</TableHead>
                                    <TableHead class="text-center text-xs">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="item in selectedProducts" :key="item.id">
                                    <TableCell>
                                        <div class="md-grid-cols-2 relative flex grid-cols-1 items-center justify-center">
                                            <input
                                                type="checkbox"
                                                :checked="selectedForDiscount.includes(item.id + item.size_id)"
                                                @change="toggleProductSelection(item.id + item.size_id)"
                                                class="text-primary-600 focus:ring-primary-500 absolute left-0 top-0 rounded border-gray-300"
                                                :title="'Pilih untuk diskon'"
                                            />
                                            <!-- <img
              v-if="item.image_path"
              :src="getImageUrl(item.image_path)"
              alt="product image"
              class="h-12 w-12 object-cover rounded"
            /> -->
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-xs">
                                        <div class="max-w-[140px] truncate font-semibold text-gray-900">{{ item.product_name }}</div>
                                        <div class="max-w-[140px] truncate font-semibold text-gray-900">Ukuran : {{ item.size_id }}</div>
                                        <span v-if="item.discount && item.discount > 0" class="text-[10px] text-gray-400 line-through">
                                            {{ formatRupiah(item.price) }}
                                        </span>
                                        <span class="block font-semibold text-gray-900">
                                            {{ formatRupiah(item.price_sell || item.price - (item.discount || 0)) }}
                                        </span>
                                        <!-- variant -->
                                        <span class="text-[11px] text-gray-600">Variant: {{ item.variant || '' }} </span>

                                        <div v-if="item.discount && item.discount > 0" class="text-[11px] text-green-600">
                                            Diskon: -{{ formatRupiah(item.discount) }}
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <input
                                            type="number"
                                            min="1"
                                            :max="item.qty_available"
                                            v-model.number="item.quantity"
                                            class="w-12 rounded border px-1 py-0.5 text-center text-xs"
                                        />
                                        <!-- @change="updateQuantity(item)" -->
                                    </TableCell>
                                    <TableCell class="text-right text-xs font-semibold text-gray-900">
                                        {{ formatRupiah(item.quantity * (item.price_sell || item.price)) }}
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <Button variant="ghost" size="icon" @click="removeFromCart(item.id)" class="hover:bg-gray-100">
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <div
                        v-if="selectedCustomerName"
                        class="mt-4 flex flex-col items-start gap-2 rounded-xl bg-gray-100 p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <p class="text-sm font-medium text-gray-500">Pelanggan</p>
                            <p class="text-xs text-gray-800">
                                {{ selectedCustomerName }} <span class="text-xs text-gray-600">(#{{ selectedCustomerId }})</span>
                            </p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500">Transaksi Online</p>
                            <p class="text-xs text-gray-800">
                                {{ transactionNumber }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-2 gap-4 rounded-lg p-1 p-4 shadow-sm">
                        <label class="mb-1 block text-xs font-medium text-gray-900">Metode Pembayaran</label>
                        <select
                            v-model="selectedPaymentMethod"
                            class="focus:ring-primary-500 w-full rounded-lg border border-gray-300 p-1 text-xs focus:outline-none focus:ring-2"
                        >
                            <option value="">Pilih metode pembayaran</option>
                            <option v-for="method in paymentMethods" :key="method.id" :value="method.id">{{ method.name }}</option>
                        </select>
                    </div>

                    <div class="mt-4 gap-4 border-t p-4 pt-2">
                        <div class="mb-2 flex justify-between text-xs font-semibold text-gray-700">
                            <span>Total</span>
                            <span class="font-bold">{{ formattedTotalAmount }}</span>
                        </div>
                        <Button
                            class="w-full rounded-md bg-indigo-600 py-2 text-xs font-semibold text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500"
                            @click="openPaymentDialog"
                            :disabled="isLoading.placingOrder"
                        >
                            <span v-if="isLoading.placingOrder">Memproses...</span>
                            <span v-else>Bayar</span>
                        </Button>
                    </div>
                </section>
            </div>

            <!-- Modals... (Your modal markup remains unchanged, just ensure responsive widths, max widths, and padding) -->
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 p-4">
                <div class="w-full max-w-xs rounded-xl bg-white p-6 shadow-lg">
                    <h3 class="mb-4 text-lg font-semibold">Set Discount</h3>
                    <label class="mb-2 block text-sm font-medium">Discount Price</label>
                    <input
                        type="number"
                        v-model.number="discountInput"
                        class="focus:ring-primary-500 mb-4 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2"
                    />
                    <div class="flex justify-end gap-3">
                        <button @click="saveDiscount" class="rounded bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700">Save</button>
                        <button @click="showModal = false" class="rounded bg-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-400">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>

            <!-- Payment Dialog -->
            <div v-if="showPaymentDialog" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 p-4">
                <div class="w-full max-w-sm rounded-lg bg-white p-6 shadow-lg">
                    <h3 class="mb-4 text-lg font-semibold">Masukkan Jumlah Bayar</h3>
                    <input
                        type="number"
                        min="0"
                        v-model.number="paidAmount"
                        @keypress.enter="confirmPayment"
                        class="mb-4 w-full rounded border px-3 py-2 text-lg"
                        placeholder="Masukkan jumlah bayar"
                    />
                    <div class="mb-4 flex justify-between">
                        <div>Total:</div>
                        <div class="font-semibold">{{ formattedTotalAmount }}</div>
                    </div>
                    <div class="mb-4 flex justify-between">
                        <div>Kembalian:</div>
                        <div class="font-semibold text-green-600">{{ formatRupiah(changeAmount) }}</div>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <Button variant="outline" @click="showPaymentDialog = false">Batal</Button>
                        <Button
                            @click="confirmPayment"
                            class="rounded-md bg-indigo-600 py-2 text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500"
                            >Konfirmasi</Button
                        >
                    </div>
                </div>
            </div>

            <!-- QR Code Scanner Modal -->
            <Modal :show="showQrScanner" @close="showQrScanner = false" title="Scan QR Code">
                <div class="h-64 w-full">
                    <qrcode-stream @detect="onDetect" />
                </div>
            </Modal>

            <!-- Product Detail Modal -->
            <Modal :show="showDetailModal" @close="showDetailModal = false" title="Detail Produk">
                <div v-if="selectedProduct" class="space-y-4">
                    <div class="flex items-center gap-4">
                        <img
                            v-if="selectedProduct.image_path"
                            :src="getImageUrl(selectedProduct.image_path)"
                            alt="Gambar produk"
                            class="h-24 w-24 rounded object-cover"
                        />
                        <div v-else class="flex h-24 w-24 select-none items-center justify-center rounded bg-gray-200 text-xs text-gray-400">
                            Tidak Ada Gambar
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold">{{ selectedProduct.product_name }}</h3>
                            <span class="mb-1 text-sm font-semibold text-gray-700">Pilih Ukuran </span>
                            <div class="flex flex-wrap gap-2">
                                <br />
                                <button
                                    v-for="size in sizesForSelectedVariant"
                                    :key="size.size_id"
                                    @click="selectedSize = size.size_id"
                                    :class="[
                                        'rounded-full border px-3 py-1 text-sm font-semibold transition',
                                        selectedSize === size.size_id
                                            ? 'border-green-600 bg-green-600 text-white'
                                            : 'border-gray-300 bg-gray-200 text-gray-800 hover:bg-gray-300',
                                    ]"
                                >
                                    <div @click="selectSize(selectedProduct, size)">{{ size.size_id }}</div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Variant -->
                    <div v-if="selectedProduct?.variant !== 'all'" class="mb-4">
                        <h4 class="mb-1 text-sm font-semibold text-gray-700">Variant</h4>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="variant in uniqueVariants"
                                :key="variant"
                                @click="
                                    () => {
                                        selectedVariant = variant;
                                        selectedSize = null;
                                    }
                                "
                                :class="[
                                    'rounded-full border px-3 py-1 text-sm font-semibold transition',
                                    selectedVariant === variant
                                        ? 'border-indigo-600 bg-indigo-600 text-white'
                                        : 'border-gray-300 bg-gray-200 text-gray-800 hover:bg-gray-300',
                                ]"
                            >
                                {{ variant }}
                            </button>
                        </div>
                    </div>
                    <!-- Size -->
                    <div class="mb-4">
                        <div v-if="getSelectedItemDetail" class="mb-4 text-sm text-gray-700">
                            <p>
                                Stok: <strong>{{ getSelectedItemDetail.qty_available }}</strong>
                            </p>
                            <span v-if="(getSelectedItemDetail.discount ?? 0) > 0" class="mr-2 text-gray-400 line-through">
                                Harga {{ formatRupiah(getSelectedItemDetail.price) }}
                            </span>
                            <br />
                            <span class="font-bold">
                                Harga Diskon {{ formatRupiah(getSelectedItemDetail.price_sell || getSelectedItemDetail.price) }} </span
                            ><br />
                            <span v-if="(getSelectedItemDetail.discount ?? 0) > 0" class="text-xs text-green-600">
                                (Diskon: {{ formatRupiah(getSelectedItemDetail.discount ?? 0) }})
                            </span>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2">
                        <Button variant="outline" @click="showDetailModal = false">Batal</Button>
                        <Button
                            @click="addToCart(selectedProduct)"
                            :disabled="!selectedSize || (!selectedVariant && selectedProduct?.variant !== 'all')"
                            >Tambah ke Keranjang</Button
                        >
                    </div>
                </div>
            </Modal>

            <Modal :show="showReprintDialog" @close="closeReprintDialog" title="RePrint Struk">
                <!-- Pencarian -->
                <div class="mb-4 flex flex-col items-stretch gap-2 sm:flex-row sm:items-center">
                    <Input v-model="searchTransaction" class="w-full text-sm sm:w-64" placeholder="Cari transaksi..." aria-label="Search" />
                    <Button @click="searchprintTransaction()" class="w-full px-4 py-2 text-sm sm:w-auto"> Cari </Button>
                </div>

                <!-- Tabel Transaksi -->
                <div class="max-h-[60vh] overflow-auto rounded-lg border border-gray-200 shadow-sm">
                    <Table class="min-w-full text-sm">
                        <TableHeader>
                            <TableRow class="bg-gray-100 text-xs uppercase text-gray-600">
                                <TableHead class="whitespace-nowrap px-2 py-2">Aksi</TableHead>
                                <TableHead class="whitespace-nowrap px-2 py-2">Tanggal</TableHead>
                                <TableHead class="whitespace-nowrap px-2 py-2">Customer</TableHead>
                                <TableHead class="whitespace-nowrap px-2 py-2">Total</TableHead>
                                <TableHead class="whitespace-nowrap px-2 py-2">Pembayaran</TableHead>
                                <TableHead class="whitespace-nowrap px-2 py-2">Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="transaction in transactions" :key="transaction.id" class="cursor-pointer transition hover:bg-gray-50">
                                <TableCell class="px-2 py-1">
                                    <Button variant="outline" size="sm" @click="selectTransactionForPrint(transaction)"> Cetak </Button>
                                </TableCell>
                                <TableCell class="px-2 py-1">{{ formatDate(transaction.created_at) }}</TableCell>
                                <TableCell class="px-2 py-1">{{ transaction.customer }}</TableCell>
                                <TableCell class="px-2 py-1">{{ formatRupiah(Number(transaction.total_amount)) }} </TableCell>
                                <TableCell class="px-2 py-1">{{ transaction.payment_method }}</TableCell>
                                <TableCell class="px-2 py-1">{{ transaction.status }}</TableCell>
                            </TableRow>

                            <TableRow v-if="transactions.length === 0">
                                <TableCell colspan="6" class="py-3 text-center text-xs text-gray-400"> Tidak ada transaksi ditemukan. </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </Modal>

            <Modal :show="showReturnDialog" @close="closeReturnDialog" title="Retur Barang">
                <div class="space-y-2">
                    <label class="block text-xs font-medium">Produk</label>
                    <Vue3Select
                        v-model="returnProductId"
                        :options="productReturns"
                        label="product_title"
                        value="id"
                        :onSearch="searchProducts"
                        :reduce="
                            (product: Product) => ({
                                ...product,
                                name: product.product_name,
                                product_name: product.product_name,
                                size_id: product.size_id,
                                uom_id: product.uom_id,
                                qty_available: 0,
                            })
                        "
                        placeholder="Pilih Produk"
                    />

                    <!-- <select v-model="returnProductId" class="w-full rounded border p-1 text-xs">
                        <option value="">Pilih Produk</option>
                        <option v-for="product in products" :key="product.id" :value="product.id">
                            {{ product.product_name }} (Stok: {{ product.qty_available }})
                        </option>
                    </select> -->
                    <label class="block text-xs font-medium">Qty</label>
                    <input type="number" v-model.number="returnQty" min="1" class="w-full rounded border p-1 text-xs" />
                    <label class="block text-xs font-medium">Harga Retur</label>
                    <input type="number" v-model.number="returnPrice" min="0" class="w-full rounded border p-1 text-xs" />
                    <div class="mt-2 flex justify-end gap-2">
                        <Button variant="outline" @click="closeReturnDialog">Batal</Button>
                        <Button class="bg-red-600 text-white" @click="handleReturnAddToCart">Simpan</Button>
                    </div>
                    <div v-if="returnError" class="mt-1 text-xs text-red-600">{{ returnError }}</div>
                    <div v-if="returnSuccess" class="mt-1 text-xs text-red-600">Retur berhasil ditambahkan ke keranjang!</div>
                </div>
            </Modal>

            <!-- Print Preview Modal -->
            <div v-if="showPrintPreview" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-30 p-4">
                <div class="print-area w-[450px] max-w-full rounded bg-white p-2 font-mono text-[10px] leading-tight shadow" ref="printArea">
                    <div class="text-center">
                        <p class="text-[12px] font-bold">{{ locationName }}</p>
                        <p>{{ locationAddress }}</p>
                    </div>

                    <hr class="my-1 border-t border-dashed" />

                    <div class="mb-1">
                        <p>Tanggal&nbsp;: {{ lastOrderDate }}</p>
                        <p>Kasir&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ cashierName }}</p>
                        <p>Nomor&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ transactionNumber }}</p>
                        <p>Customer&nbsp;&nbsp;: {{ selectedCustomerName || '-' }}</p>
                    </div>

                    <hr class="my-1 border-t border-dashed" />

                    <!-- Items -->
                    <div v-for="(item, index) in lastOrderItems" :key="item.product_id" class="mb-1">
                        <p class="font-bold">{{ index + 1 }}. {{ item.product_name }} &nbsp; - {{ item.size_id }} {{ item.variant }}</p>

                        <div class="flex justify-between p-1">
                            <span>{{ item.quantity }} x {{ formatRupiah(item.price) }}</span>
                            <span>&nbsp;&nbsp;{{ formatRupiah(item.price * item.quantity) }}</span>
                        </div>
                        <div v-if="Number(item.discount) > 0">
                            <div class="flex justify-between">
                                <span class="text-green-600">- {{ formatRupiah(item.discount) }}</span>
                                <span>{{ formatRupiah(item.price * item.quantity - item.discount) }}</span>
                            </div>
                        </div>
                    </div>

                    <hr class="my-1 border-t border-dashed" />

                    <!-- Totals -->
                    <div class="flex justify-between">
                        <span>Sub Total&nbsp;&nbsp;&nbsp;&nbsp;:</span>
                        <span>{{ formatRupiah(lastOrderSubTotal) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Jumlah Bayar&nbsp;:</span>
                        <span>{{ formatRupiah(paidAmount || 0) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Kembalian&nbsp;&nbsp;&nbsp;&nbsp;:</span>
                        <span>{{ formatRupiah(changeAmount) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Total Disc&nbsp;&nbsp;&nbsp;:</span>
                        <span>{{ formatRupiah(totalDiscount) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Metode&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</span>
                        <span>{{ lastOrderPaymentMethodName }}</span>
                    </div>

                    <hr class="my-1 border-t border-dashed" />
                    <p class="text-center">SELAMAT BERBELANJA</p>
                    <hr class="my-1 border-t border-dashed" />

                    <!-- Buttons (hidden when printing) -->
                    <div class="no-print mt-2 flex justify-end gap-2">
                        <Button variant="outline" @click="closePrintPreview">TUTUP</Button>
                        <Button
                            @click="autoPrint"
                            class="rounded-md bg-indigo-600 py-2 text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500"
                            >CETAK</Button
                        >
                    </div>
                </div>
            </div>
        </div>
        <CustomerDialog :show="showCustomerDialog" @update:show="showCustomerDialog = $event" @customer-selected="handleCustomerSelected" />

        <!-- Add Product Custom Modal -->
        <Modal :show="showAddProductUnlistedModal" @close="showAddProductUnlistedModal = false" title="Tambah Produk Custom">
          <AddProductUnlistedModal @cancel="showAddProductUnlistedModal = false" />
        </Modal>

        <!-- List Product Unlisted Modal -->
        <Modal :show="showListProductUnlistedModal" @close="showListProductUnlistedModal = false" title="Daftar Produk Tanpa Master">
            <ListProductUnlisted @add-unlisted-to-cart="handleAddToPosCart" />
        </Modal>
    </AppLayout>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import type { User } from '@/types';
import { type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { PercentIcon, PrinterCheckIcon, ReplaceAll, ScanQrCode, Trash2, UserPlusIcon, Plus } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';
import Vue3Select from 'vue3-select';
import 'vue3-select/dist/vue3-select.css';
// import jsQR from 'jsqr';
import CustomerDialog from '@/components/CustomerDialog.vue';
import Modal from '@/components/Modal.vue';
import AddProductUnlistedModal from '@/components/AddProductUnlisted.vue';
import ListProductUnlisted from '@/components/ListProductUnlisted.vue';
import { useCurrencyInput } from '@/composables/useCurrencyInput';
import { QrcodeStream } from 'vue-qrcode-reader';

interface ProductSize {
    size_id: string;
    variant: string;
    qty_stock: number;
    qty_in_cart: number;
    qty_available?: number;
    price: number;
    price_sell?: number;
    discount?: number;
    price_retail: number;
    discount_retail: number;
    price_sell_retail: number;
    price_grosir: number;
    discount_grosir: number;
    price_sell_grosir: number;
}

interface Product {
    id: number;
    product_id?: number;
    product_name: string;
    uom_id: string;
    size_id: string;
    qty_stock: number;
    qty_available?: number;
    image_path: string;
    price: number;
    discount?: number;
    price_sell?: number;

    quantity: number;
    sizes: ProductSize[];
    variant: string | null;
    isReturn?: boolean;
    is_unlisted?: boolean; // Add is_unlisted property
}

interface ProductRetur {
    id: number;
    product_id: number;
    product_name: string;
    size_id: string;
    uom_id: string;
    quantity: number;
    price: number;
}

interface Transaction {
    id: string;
    created_at: string;
    customer: string;
    total_amount: number;
    payment_method: string;
    status: string;
    order_items: OrderItem[];
    paid_amount: number;
}

interface PaymentMethod {
    id: string;
    name: string;
}

interface OrderItem {
    product_id: number;
    product_name: string;
    quantity: number;
    price: number;
    discount: number;
    price_sell: number;
    variant: string | null;
    uom_id: string;
    size_id: string | null; // Optional size ID
    qty_stock: number;
    image_path: string;
}

interface OrderPayload {
    items: OrderItem[];
    payment_method_id: string;
    total_amount: number;
    paid_amount: number;
    customer_id?: number | null;
    transaction_number?: string | null;
}

// const showScanner = ref(false);
// const videoElement = ref<HTMLVideoElement>();
// const stream = ref<MediaStream | null>(null);
const showModal = ref(false);

const showQrScanner = ref(false);
const showAddProductUnlistedModal = ref(false);
const showListProductUnlistedModal = ref(false);

const toast = useToast();
const products = ref<Product[]>([]);
const productReturns = ref<Product[]>([]);
const selectedProducts = ref<Product[]>([]);
const paymentMethods = ref<PaymentMethod[]>([]);
const selectedPaymentMethod = ref<string | null>(null);
const isLoading = ref({ placingOrder: false });

const paymentInput = useCurrencyInput(0);

const searchText = ref('');
const currentPage = ref(1);
const lastPage = ref(1);
const discountInput = ref<number>(0);
const selectedForDiscount = ref<string[]>([]); // Stores selected product IDs

const showDetailModal = ref(false);
const selectedProduct = ref<Product | null>(null);
// const selectedVariant = ref<ProductVariant | null>(null);

function viewProductDetail(product: Product) {
    selectedProduct.value = product;
    selectedVariant.value = null; // Reset selected variant
    selectedSize.value = null; // Reset selected size

    if (selectedProduct.value.variant === 'all' && selectedProduct.value.sizes && selectedProduct.value.sizes.length > 0) {
        selectedSize.value = selectedProduct.value.sizes[0].size_id; // Automatically select the first size
    }
    showDetailModal.value = true;
}
const showPrintPreview = ref(false);
const showCustomerDialog = ref(false);
const selectedCustomerId = ref<number | null>(null);
const selectedCustomerName = ref<string | null>(null);
const lastOrderItems = ref<OrderItem[]>([]);
const lastOrderTotal = ref(0);
const lastOrderPaymentMethodName = ref('');
const lastOrderDate = ref('');
const transactionNumber = ref('');

const orderList = ref<HTMLElement | null>(null);
const printArea = ref<HTMLElement | null>(null);

const showReturnDialog = ref(false);
const showReprintDialog = ref(false);
const searchTransaction = ref('');
const transactions = ref<Transaction[]>([]);
const selectedTransaction = ref<Transaction | null>(null);
const returnProductId = ref<ProductRetur | null>(null);
const returnQty = ref<number>(1);
const returnPrice = ref<number>(0);
const returnError = ref('');
const returnSuccess = ref(false);
const selectedVariant = ref<string | null>(null);
const selectedSize = ref<string | null>(null);

async function searchprintTransaction() {
    try {
        const response = await axios.get(`/api/orders/search`, {
            params: { search: searchTransaction.value },
        });
        transactions.value = response.data.data;
    } catch (error) {
        console.error('Error searching transactions:', error);
        alert('Failed to search transactions.');
    }
}

async function selectTransactionForPrint(transaction: Transaction) {
    if (confirm('Yakin mau tampilkan struk untuk dicetak ulang?')) {
        selectedTransaction.value = transaction;

        // Set preview data
        lastOrderDate.value = formatDate(transaction.created_at);
        transactionNumber.value = transaction.id;
        selectedCustomerName.value = transaction.customer;
        lastOrderItems.value = transaction.order_items.map((item) => ({
            product_id: item.product_id,
            product_name: item.product_name,
            quantity: Number(item.quantity),
            price: Number(item.price),
            discount: Number(item.discount),
            price_sell: Number(item.price_sell),
            variant: item.variant || '',
            uom_id: item.uom_id,
            size_id: item.size_id,
            qty_stock: item.qty_stock,
            image_path: item.image_path,
        }));
        lastOrderTotal.value = transaction.total_amount;
        lastOrderPaymentMethodName.value = transaction.payment_method;
        paidAmount.value = transaction.paid_amount;

        // Tampilkan print preview modal terlebih dahulu
        showPrintPreview.value = true;

        // Jangan langsung autoPrint. Biarkan user klik "Cetak" dari preview
        closeReprintDialog();
    }
}

const paidAmount = ref<number | null>(null);
const changeAmount = computed(() => {
    if (paidAmount.value === null) return 0;
    return paidAmount.value - totalAmount.value > 0 ? paidAmount.value - totalAmount.value : 0;
});

const uniqueVariants = computed(() => {
    if (!selectedProduct.value?.sizes) return [];
    const variants = selectedProduct.value.sizes.map((item) => item.variant);
    return [...new Set(variants)];
});

const searchProducts = async (search: string) => {
    if (search.length < 2) {
        products.value = [];
        return;
    }
    try {
        const res = await axios.get('/api/product-with-size', { params: { search } });
        productReturns.value = res.data.data;
    } catch (error) {
        console.error('Search error:', error);
        toast.error('Failed to search products');
    }
};

function getSizesForVariant(sizes: ProductSize[], variant: string): ProductSize[] {
    if (!sizes) return [];
    return sizes.filter((size) => size.variant === variant);
}

function selectSize(product: Product, size: ProductSize) {
    if (product) {
        product.size_id = size.size_id;
        selectedSize.value = size.size_id;
    }
    if (selectedProduct.value) {
        selectedProduct.value.price = size.price;
        selectedProduct.value.price_sell = size.price_sell;
    }
}

// const applyDynamicPricing = (size: ProductSize, qty: number) => {
//   return {
//     price: qty > 1 ? size.price_grosir : size.price_retail,
//     price_sell: qty > 1 ? size.price_sell_grosir : size.price_sell_retail,
//     discount: qty > 1 ? size.discount_grosir : size.discount_retail,
//   };
// };

function applyReturn() {
    showReturnDialog.value = true;
}

function rePrintDialog() {
    showReprintDialog.value = true;
    transactions.value = []; // Clear previous search results
    searchTransaction.value = '';
}

const sizesForSelectedVariant = computed(() => {
    if (!selectedProduct.value) return [];
    if (selectedProduct.value.variant === 'all') {
        return selectedProduct.value.sizes.map((item) => item);
    }
    if (!selectedVariant.value) return [];
    return selectedProduct.value.sizes.filter((item) => item.variant === selectedVariant.value);
});

function closeReturnDialog() {
    showReturnDialog.value = false;
    returnProductId.value = null;
    returnQty.value = 1;
    returnPrice.value = 0;
    returnError.value = '';
    returnSuccess.value = false;
}

function closeReprintDialog() {
    showReprintDialog.value = false;
    searchTransaction.value = '';
    transactions.value = [];
    selectedTransaction.value = null;
}

async function handleReturnAddToCart() {
    returnError.value = '';
    returnSuccess.value = false;

    if (!returnProductId.value) {
        returnError.value = 'Produk harus dipilih';
        return;
    }
    const product = returnProductId.value;

    if (!product || !product.id) {
        returnError.value = 'Produk harus dipilih';
        return;
    }

    if (!returnQty.value || returnQty.value < 1) {
        returnError.value = 'Qty minimal 1';
        return;
    }
    if (!returnPrice.value || returnPrice.value < 0) {
        returnError.value = 'Harga retur harus diisi';
        return;
    }

    selectedProducts.value.push({
        id: product.id,
        product_id: product.id,
        product_name: product.product_name ?? '', // fallback to 'name' if 'product_name' missing
        size_id: product.size_id ?? '',
        uom_id: product.uom_id ?? '',
        // Properti tambahan yang diminta
        qty_stock: 0,
        qty_available: 0,
        image_path: '',
        sizes: [],
        variant: null,

        quantity: -Math.abs(returnQty.value), // negative qty for return
        price: returnPrice.value,
        price_sell: returnPrice.value,
        isReturn: true,
    });
    returnSuccess.value = true;
    setTimeout(() => {
        closeReturnDialog();
    }, 1000);
}

const locationName = ref('TOKO ANINKA');
const locationAddress = ref('Blok A lantai SLG Los F No 91-92');

const page = usePage<SharedData>();
const userLogin = page.props.auth.user as User;
const locationId = (userLogin as any)?.location_id;

async function fetchLocationInfo() {
    if (!locationId) return;
    try {
        const res = await axios.get(`/api/locations/get`);
        locationName.value = res.data.name || 'TOKO ANINKA';
        locationAddress.value = res.data.address || 'Blok A lantai SLG Los F No 91-92';
    } catch {
        locationName.value = 'TOKO ANINKA';
        locationAddress.value = 'Blok A lantai SLG Los F No 91-92';
    }
}

fetchLocationInfo();

// Hitung total diskon dari item
const totalDiscount = computed(() => lastOrderItems.value.reduce((sum, item) => sum + (item.discount ?? 0) * item.quantity, 0));

// Hitung subtotal dari item
const lastOrderSubTotal = computed(() => lastOrderItems.value.reduce((sum, item) => sum + item.price * item.quantity, 0));

const showPaymentDialog = ref(false);

// Buat computed property dari nama user
const cashierName = computed(() => userLogin.name ?? 'Kasir');

const getImageUrl = (path: string) => {
    if (!path) return '';
    if (path.startsWith('storage/')) return '/' + path;
    if (path.startsWith('/storage/')) return path;
    return '/storage/' + path;
};

const formatRupiah = (value: number): string => {
    if (typeof value !== 'number' || isNaN(value)) return '0,00';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
};

const toggleProductSelection = (productKey: string) => {
    const index = selectedForDiscount.value.indexOf(productKey);
    if (index === -1) {
        selectedForDiscount.value.push(productKey);
    } else {
        selectedForDiscount.value.splice(index, 1);
    }
};
const getSelectedItemDetail = computed(() => {
    if (!selectedSize.value || !selectedProduct.value) return null;
    if (selectedProduct.value.variant === 'all') {
        return selectedProduct.value.sizes.find((item) => item.size_id === selectedSize.value);
    }
    if (!selectedVariant.value) return null;
    return selectedProduct.value.sizes.find((item) => item.variant === selectedVariant.value && item.size_id === selectedSize.value);
});

async function fetchProducts() {
    try {
        const response = await axios.get(`/api/stock?page=${currentPage.value}&search=${searchText.value}`);
        products.value = response.data.data;
        lastPage.value = response.data.last_page;
    } catch (error) {
        console.error('Error fetching products:', error);
        toast.error('Failed to fetch products');
    }
}

async function fetchPaymentMethods() {
    try {
        const response = await axios.get('/api/payment-methods');
        paymentMethods.value = response.data.data;
    } catch {
        toast.error('Failed to fetch payment methods');
    }
}

function onSearchInput() {
    currentPage.value = 1;
    fetchProducts();
}

const addToCart = (product: Product) => {
    // Skip stock validation for unlisted products
    if (!product.is_unlisted) {
        if (product.qty_stock <= 0) {
            toast.error('Stok tidak cukup');
            return;
        }
    }

    if (product.price <= 0 && (!product.price_sell || product.price_sell <= 0)) {
        toast.error('Harga produk belum ditentukan');
        return;
    }

    let currentSelectedSize: ProductSize | undefined;

    // Handle size selection
    if (product.sizes && product.sizes.length > 0) {
        if (!selectedSize.value) {
            toast.error('Silahkan pilih ukuran terlebih dahulu');
            return;
        }

        currentSelectedSize = product.sizes.find(
            (s) => s.size_id === selectedSize.value && (selectedVariant.value === null || s.variant === selectedVariant.value),
        );

        // Skip stock validation for unlisted products here too
        if (!product.is_unlisted && (!currentSelectedSize || currentSelectedSize.qty_stock <= 0)) {
            toast.error('Stok ukuran ini tidak tersedia');
            return;
        }
    }

    const quantityToAdd = 1;

    // Always assume retail first
    const useGrosir = quantityToAdd > 1;

    // Ensure size-specific pricing exists
    const sizePrice = currentSelectedSize?.[useGrosir ? 'price_grosir' : 'price_retail'];
    const sizeSell = currentSelectedSize?.[useGrosir ? 'price_sell_grosir' : 'price_sell_retail'];
    const sizeDiscount = currentSelectedSize?.[useGrosir ? 'discount_grosir' : 'discount_retail'];

    if (product.sizes.length && !currentSelectedSize) {
        toast.error('Ukuran tidak valid.');
        return;
    }

    const itemToAdd: Product = {
        id: product.product_id || product.id,
        product_id: product.product_id || product.id,
        product_name: product.product_name,
        uom_id: product.uom_id,
        size_id: currentSelectedSize?.size_id ?? '',
        qty_stock: currentSelectedSize?.qty_stock ?? product.qty_stock,
        qty_available: (currentSelectedSize?.qty_stock ?? product.qty_stock) - quantityToAdd,
        image_path: product.image_path ?? '',
        price: sizePrice ?? product.price,
        price_sell: sizeSell ?? product.price_sell ?? product.price,
        discount: sizeDiscount ?? product.discount ?? 0,
        quantity: quantityToAdd,
        variant: currentSelectedSize?.variant ?? product.variant ?? null,
        sizes: currentSelectedSize
            ? [
                  {
                      ...currentSelectedSize,
                      qty_in_cart: quantityToAdd,
                      qty_available: currentSelectedSize.qty_stock - quantityToAdd,
                  },
              ]
            : [],
        is_unlisted: (product as any).is_unlisted || false, // Add is_unlisted flag
    };

    const existing = selectedProducts.value.find(
        (item) => item.id === itemToAdd.id && item.size_id === itemToAdd.size_id && item.variant === itemToAdd.variant,
    );

    if (existing) {
        existing.quantity += quantityToAdd;

        const nowGrosir = existing.quantity > 1;
        if (currentSelectedSize) {
            existing.price = currentSelectedSize[nowGrosir ? 'price_grosir' : 'price_retail'] ?? existing.price;
            existing.price_sell = currentSelectedSize[nowGrosir ? 'price_sell_grosir' : 'price_sell_retail'] ?? existing.price_sell;
            existing.discount = currentSelectedSize[nowGrosir ? 'discount_grosir' : 'discount_retail'] ?? existing.discount;
        }
    } else {
        selectedProducts.value.push(itemToAdd);
    }

    showDetailModal.value = false;
    nextTick(() => {
        if (orderList.value) orderList.value.scrollTop = orderList.value.scrollHeight;
    });
};

function removeFromCart(productId: number) {
    selectedProducts.value = selectedProducts.value.filter((p) => p.id !== productId);
}

// function selectProduct(product: Product) {
//   const exists = selectedProducts.value.some(p => p.id === product.id);
//   if (!exists) {
//     selectedProducts.value.push({ ...product });
//   }
// }

// function updateQuantity(item: Product) {
//     if (item.quantity < 1) {
//         item.quantity = 1;
//         toast.error('Minimal quantity 1');
//         return; // Stop here to avoid checking against stock
//     }

//     const productStock = products.value.find((p) => p.id === item.id)?.qty_stock ?? 0;

//     if (productStock === 0) {
//         item.quantity = 1;
//         toast.error('Stock kosong');
//         return;
//     }

//     if (item.quantity > productStock) {
//         item.quantity = productStock;
//         toast.error('Stock limit reached');
//     }
// }

function openDiscountDialog() {
    if (selectedProducts.value.length === 0) return;

    showModal.value = true;
}

function saveDiscount() {
    selectedProducts.value = selectedProducts.value.map((product) => {
        // Only update discount if product is selected
        if (selectedForDiscount.value.includes(product.id + product.size_id)) {
            return {
                ...product,
                discount: discountInput.value,
                price_sell: product.price - discountInput.value,
            };
        }
        return product;
    });

    // Clear selections after applying discount
    selectedForDiscount.value = [];
    showModal.value = false;
    toast.success('Discount applied successfully');
}

function handleAddToPosCart(unlistedProduct: any) {
    // Transform the unlistedProduct to match the 'Product' interface expected by addToCart
    const transformedProduct: Product = {
        id: unlistedProduct.id,
        product_id: unlistedProduct.id,
        product_name: unlistedProduct.name,
        uom_id: unlistedProduct.uom_id || 'PCS', // Default UOM for unlisted
        size_id: unlistedProduct.size_id,
        qty_stock: unlistedProduct.qty || 1, // Use qty from unlisted product as initial stock
        qty_available: unlistedProduct.qty || 1,
        image_path: unlistedProduct.image_path || '',
        price: unlistedProduct.price_store, // Use price_store as default price
        price_sell: unlistedProduct.price_store,
        discount: 0,
        quantity: 1, // Always add 1 to cart initially
        variant: unlistedProduct.variant,
        is_unlisted: true,
        sizes: [
            {
                size_id: unlistedProduct.size_id,
                variant: unlistedProduct.variant,
                qty_stock: unlistedProduct.qty || 1,
                qty_in_cart: 0,
                qty_available: unlistedProduct.qty || 1,
                price: unlistedProduct.price_store,
                price_sell: unlistedProduct.price_store,
                discount: 0,
                price_retail: unlistedProduct.price_store,
                discount_retail: 0,
                price_sell_retail: unlistedProduct.price_store,
                price_grosir: unlistedProduct.price_grosir,
                discount_grosir: 0,
                price_sell_grosir: unlistedProduct.price_grosir,
            },
        ],
    };

    // Temporarily set selectedSize for validation within addToCart
    selectedSize.value = transformedProduct.size_id;

    addToCart(transformedProduct);
    showListProductUnlistedModal.value = false;

    // Reset selectedSize after adding to cart
    selectedSize.value = null;
}

function openPaymentDialog() {
    if (selectedProducts.value.length === 0) {
        toast.error('Please add items to the cart');
        return;
    }
    if (!selectedPaymentMethod.value) {
        toast.error('Please select a payment method');
        return;
    }
    paidAmount.value = null;
    showPaymentDialog.value = true;
}

async function confirmPayment() {
    if (paymentMethods.value.length === 0) {
        toast.error('No payment methods available');
        return;
    }
    const selectedMethod = paymentMethods.value.find((pm) => pm.id === selectedPaymentMethod.value);
    if (!selectedMethod || selectedMethod.name !== 'CREDIT') {
        if (paidAmount.value === null || paidAmount.value < totalAmount.value) {
            toast.error('Paid amount must be at least total amount');
            return;
        }
    }

    showPaymentDialog.value = false;
    await placeOrder();
}

function clearCart() {
    selectedProducts.value = [];
    selectedPaymentMethod.value = null;
    selectedCustomerName.value = '';
    transactionNumber.value = '';
    selectedForDiscount.value = []; // Clear discount selections
}

async function applyCustomer(customerId: number | null = null) {
    if (typeof customerId === 'number') {
        selectedCustomerId.value = customerId;
    }

    if (typeof selectedCustomerId.value === 'number') {
        try {
            const response = await axios.get(`/api/customers/get/${selectedCustomerId.value}`);
            selectedCustomerName.value = response.data.name;
            toast.success(`Pelanggan ${selectedCustomerName.value} berhasil ditambahkan.`);
        } catch (error) {
            console.error('Error fetching customer details:', error);
            toast.error('Gagal mengambil detail pelanggan.');
            selectedCustomerId.value = null;
            selectedCustomerName.value = null;
        }
    } else {
        showCustomerDialog.value = true;
    }
}

const handleCustomerSelected = (customer: { id: number; name: string }) => {
    selectedCustomerId.value = customer.id;
    selectedCustomerName.value = customer.name;
    showCustomerDialog.value = false;
};

const totalAmount = computed(() => selectedProducts.value.reduce((sum, item) => sum + item.price * item.quantity, 0));
const formattedTotalAmount = computed(() => `${formatRupiah(totalAmount.value)}`);

async function placeOrder() {
    if (selectedProducts.value.length === 0) {
        toast.error('Please add items to the cart');
        return;
    }

    if (!selectedPaymentMethod.value) {
        toast.error('Please select a payment method');
        return;
    }

    isLoading.value.placingOrder = true;

    try {
        // Prepare items
        const items: OrderItem[] = selectedProducts.value.map((p) => {
            const discount = p.discount || 0;
            return {
                product_id: p.product_id ?? p.id,
                product_name: p.product_name,
                quantity: p.quantity,
                price: p.price,
                uom_id: p.uom_id,
                size_id: p.size_id || null, // Ensure size_id is passed, can be null
                qty_stock: 0, // This will be calculated on backend
                image_path: '', // Not needed for order payload
                discount,
                price_sell: p.price_sell ?? p.price - discount,
                variant: p.variant || 'all',
            };
        });

        const orderPayload: OrderPayload = {
            items,
            payment_method_id: selectedPaymentMethod.value,
            total_amount: totalAmount.value,
            paid_amount: paidAmount.value!,
            customer_id: selectedCustomerId.value, // Add customer_id here
            transaction_number: transactionNumber.value || '', // Add flag for online transaction
        };

        const response = await axios.post('/api/pos/orders', orderPayload);

        // For print preview or record
        lastOrderItems.value = items.map((item) => ({
            ...item,
            uom_id: selectedProducts.value.find((p) => (p.product_id ?? p.id) === item.product_id)?.uom_id || '',
        }));

        lastOrderTotal.value = response.data.total_amount;
        lastOrderPaymentMethodName.value = paymentMethods.value.find((pm) => pm.id === selectedPaymentMethod.value)?.name || '';
        lastOrderDate.value = formatDate(response.data.created_at);
        transactionNumber.value = response.data.transaction_number || '';
        selectedCustomerId.value = null;
        showPrintPreview.value = true;
    } catch (error) {
        console.error('Error placing order:', error);
        toast.error('Failed to place order');
    } finally {
        isLoading.value.placingOrder = false;
    }
}

function closePrintPreview() {
    showPrintPreview.value = false;
    clearCart(); // reset cart
    selectedPaymentMethod.value = null; // reset payment method
}

const onDetect = async (detectedCodes: { rawValue: string }[]) => {
    if (detectedCodes.length === 0) return;

    const qrCodeData = detectedCodes[0].rawValue;
    console.log('QR Code detected:', qrCodeData);
    showQrScanner.value = false;

    try {
        const orderIds = qrCodeData.split(',').map((id) => id.trim());

        const response = await axios.get('/api/orders/scan', {
            params: { ids: orderIds.join(',') },
        });

        const orders = response.data;

        const combinedOrderItems: OrderItem[] = [];

        if (orders.length > 0) {
            // Assuming the first order contains the customer information
            selectedCustomerId.value = orders[0].customer_id;
            selectedCustomerName.value = orders[0].customer?.name || 'Guest'; // Adjust based on actual customer object structure
            transactionNumber.value = orderIds.join(',');
        }

        orders.forEach((order: any) => {
            if (order?.order_items?.length) {
                combinedOrderItems.push(...order.order_items);
            }
        });

        if (combinedOrderItems.length === 0) {
            toast.error('No order items found for the scanned QR code(s).');
            return;
        }

        const products: Product[] = combinedOrderItems.map((item) => ({
            id: item.product_id,
            product_id: item.product_id,
            product_name: item.product_name,
            uom_id: item.uom_id,
            size_id: item.size_id || '',
            qty_stock: item.qty_stock,
            image_path: item.image_path,
            price: item.price,
            price_sell: item.price_sell,
            discount: item.discount,
            quantity: item.quantity ?? 1,
            variant: item.variant || 'all',
            sizes: item.size_id
                ? [
                      {
                          size_id: item.size_id,
                          variant: item.variant || 'all',
                          qty_stock: item.qty_stock,
                          qty_in_cart: item.quantity ?? 1,
                          qty_available: item.qty_stock,
                          price: item.price,
                          price_sell: item.price_sell,
                          discount: item.discount,
                          price_retail: item.price,
                          price_sell_retail: item.price_sell,
                          discount_retail: item.discount,
                          price_grosir: item.price,
                          price_sell_grosir: item.price_sell,
                          discount_grosir: item.discount,
                      },
                  ]
                : [],
        }));

        console.log('Produk dalam pesanan:', products);

        // ini sesuai list tidak di merge
        // products.forEach((product) => {
        //     selectedProducts.value.push({ ...product });
        // });

        products.forEach((product) => {
            const existingIndex = selectedProducts.value.findIndex(
                (p) => (p.product_id ?? p.id) === (product.product_id ?? product.id) && p.size_id === product.size_id,
            );

            if (existingIndex > -1) {
                selectedProducts.value[existingIndex].quantity += product.quantity;
            } else {
                selectedProducts.value.push({ ...product });
            }
        });

        toast.success('Order items added to cart!');

        // If a customer was identified from the QR scan, apply them
        if (selectedCustomerId.value) {
            applyCustomer(selectedCustomerId.value);
        }
    } catch (error) {
        console.error('Error scanning QR code:', error);
        toast.error('Failed to scan QR code or retrieve order details.');
    }
};

const watchedItems = new WeakMap<Product, boolean>();

watch(
    selectedProducts,
    (newItems) => {
        newItems.forEach((item) => {
            if (watchedItems.has(item)) return; // Sudah dipasang watch

            watchedItems.set(item, true); // Tandai sudah dipasang
            watch(
                () => item.quantity,
                (newQty) => {
                    if (!item.sizes || item.sizes.length === 0) return;
                    const size = item.sizes[0];
                    const isGrosir = newQty > 1;

                    item.price = isGrosir ? size.price_grosir : size.price_retail;
                    item.price_sell = isGrosir ? size.price_sell_grosir : size.price_sell_retail;
                    item.discount = isGrosir ? size.discount_grosir : size.discount_retail;

                    size.qty_in_cart = newQty;
                    size.qty_available = size.qty_stock - newQty;
                },
            );
        });
    },
    { deep: true },
);

watch(paymentInput.internalValue, (newValue) => {
    paidAmount.value = newValue;
});

function doPrintKasir80mm() {
    // QZ Tray integration for direct thermal printing
    // @ts-expect-ignore
    const qz = (window as any).qz;
    // Ambil nama kasir dari user login
    const kasirName = cashierName.value;
    if (qz) {
        const totalQty = lastOrderItems.value.reduce((sum, item) => sum + item.quantity, 0);
        const sisa = (lastOrderTotal.value || totalAmount.value) - (paidAmount.value || 0);

        const lines: string[] = [];
        lines.push(`     ${locationName.value}`);
        lines.push(` ${locationAddress.value}`);
        lines.push('----------------------------------------');
        lines.push(`Kasir   : ${kasirName}`);
        lines.push(`Tanggal : ${lastOrderDate.value}`);
        lines.push(`Nomor   : ${transactionNumber.value}`);
        lines.push('------------------------------------');
        lines.push(`Customer: ${selectedCustomerName.value || '-'}`);
        lines.push('--------------------------------------');
        lastOrderItems.value.forEach((item) => {
            // tambahkan variant
            lines.push(`${item.product_name}${item.size_id ? ' - ' + item.size_id : ''} ${item.variant ? ' ' + item.variant : ''}`);
            lines.push(
                `${item.quantity} x ${formatRupiah(item.price)}${' '.repeat(20 - (item.quantity + '').length - formatRupiah(item.price).length)}${formatRupiah(item.quantity * item.price)}`,
            );
        });
        lines.push('--------------------------------');
        lines.push(`Total QTY        ${totalQty}`);
        lines.push(`Total Bayar      ${formatRupiah(lastOrderTotal.value || totalAmount.value)}`);
        lines.push(`Jenis Bayar      ${lastOrderPaymentMethodName.value || '-'}`);
        lines.push(`Bayar Cash       ${formatRupiah(paidAmount.value || 0)}`);
        lines.push(`Sisa             ${formatRupiah(sisa > 0 ? sisa : 0)}`);
        lines.push('--------------------------------------');
        lines.push('      ---TERIMA KASIH---');
        lines.push('     aninkafashion.com');
        lines.push('\n\n\n');

        qz.websocket
            .connect()
            .then(() => {
                return qz.printers.find();
            })
            .then((printer: string) => {
                const config = qz.configs.create(printer);
                return qz.print(config, [{ type: 'raw', format: 'plain', data: lines.join('\n') }]);
            })
            .catch((err: any) => {
                alert('Print error: ' + err);
            })
            .finally(() => {
                qz.websocket.disconnect();
            });
        return;
    }

    // Fallback: browser print
    // ...existing code for browser print...
    const totalQty = lastOrderItems.value.reduce((sum, item) => sum + item.quantity, 0);
    const sisa = (lastOrderTotal.value || totalAmount.value) - (paidAmount.value || 0);

    const printContent = `
    <div style="font-family: 'Courier New', Courier, monospace; font-size: 12px; width: 80mm; padding: 0; margin: 0;">
      <div style="text-align:center; font-weight:bold;">${locationName.value}</div>
      <div style="text-align:center;">${locationAddress.value}</div>
      <div style="text-align:center;">----------------------------------------</div>
      <div style="display:flex; justify-content:space-between;">
        <span>Kasir   : ${kasirName}</span>
      </div>
      <div style="display:flex; justify-content:space-between;">
        <span>Tanggal : ${lastOrderDate.value}</span>
      </div>
      <div style="display:flex; justify-content:space-between;">
        <span>Nomor   : ${transactionNumber.value}</span>
      </div>
      <div style="text-align:center;">----------------------------------------</div>
      <div style="display:flex; justify-content:space-between;">
        <span>Customer</span>
        <span>${selectedCustomerName.value || '-'}</span>
      </div>
      <div style="text-align:center;">----------------------------------------</div>
      ${lastOrderItems.value
          .map(
              (item) => `
          <div>${item.product_name} - ${item.size_id} ${item.variant} </div>
          <div style="display:flex; justify-content:space-between;">
            <span>${item.quantity} x ${formatRupiah(item.price)}</span>
            <span>${formatRupiah(item.quantity * item.price)}</span>
          </div>
        `,
          )
          .join('')}
      <div style="text-align:center;">----------------------------------------</div>
      <div style="display:flex; justify-content:space-between;">
        <span>Total QTY</span>
        <span>${totalQty}</span>
      </div>
      <div style="display:flex; justify-content:space-between;">
        <span>Total Bayar</span>
        <span>${formatRupiah(lastOrderTotal.value || totalAmount.value)}</span>
      </div>
      <div style="display:flex; justify-content:space-between;">
        <span>Jenis Bayar</span>
        <span>${lastOrderPaymentMethodName.value || '-'}</span>
      </div>
      <div style="display:flex; justify-content:space-between;">
        <span>Bayar Cash</span>
        <span>${formatRupiah(paidAmount.value || 0)}</span>
      </div>
      <div style="display:flex; justify-content:space-between;">
        <span>Sisa</span>
        <span>${formatRupiah(sisa > 0 ? sisa : 0)}</span>
      </div>
      <div style="text-align:center;">----------------------------------------</div>
      <div style="text-align:center; margin-top:8px;">---TERIMA KASIH---</div>
      <div style="text-align:center;">aninkafashion.com</div>
    </div>
  `;

    const printWindow = window.open('', '', 'height=600,width=400');
    if (!printWindow) {
        alert('Please allow pop-ups for printing.');
        return;
    }
    printWindow.document.write(`
    <html>
      <head>
        <title>Print Struk</title>
        <style>
          @media print {
            body { margin: 0; }
            div { font-family: 'Courier New', Courier, monospace; font-size: 12px; }
          }
        </style>
      </head>
      <body>
        ${printContent}
        <script>
          window.onload = function() {
            setTimeout(function() {
              window.print();
              window.onafterprint = function() { window.close(); };
            }, 100);
          };
        <\/script>
      </body>
    </html>
  `);
    printWindow.document.close();
}

function printToRawBT() {
    const maxLineWidth = 46; // Ganti sesuai kapasitas karakter printer (44–48 umumnya)

    const separator = '-'.repeat(maxLineWidth);

    const lines = [
        locationName.value.padStart(Math.floor((maxLineWidth + locationName.value.length) / 2)),
        locationAddress.value.padStart(Math.floor((maxLineWidth + locationAddress.value.length) / 2)),
        separator,
        `Kasir   : ${cashierName.value}`,
        `Tanggal : ${lastOrderDate.value}`,
        `Nomor   : ${transactionNumber.value}`,
        separator,
        `Customer: ${selectedCustomerName.value || '-'}`,
        separator,
        ...lastOrderItems.value.flatMap((item) => {
            const name = item.product_name + ' - ' + item.size_id + (item.variant ? ' ' + item.variant : '');
            const qtyPrice = `${item.quantity} x ${formatRupiah(item.price)}`;
            const total = formatRupiah(item.quantity * item.price);

            // Panjang qtyPrice dan total dirapikan
            const left = qtyPrice.padEnd(23); // Setengah baris
            const right = total.padStart(maxLineWidth - 23); // Sisanya ke kanan

            return [name.length > maxLineWidth ? name.slice(0, maxLineWidth) : name, `${left}${right}`];
        }),
        separator,
        `Total QTY   : ${lastOrderItems.value.reduce((sum, item) => sum + item.quantity, 0)}`,
        `Total Bayar : ${formatRupiah(lastOrderTotal.value || totalAmount.value)}`,
        `Jenis Bayar : ${lastOrderPaymentMethodName.value || '-'}`,
        `Bayar Cash  : ${formatRupiah(paidAmount.value || 0)}`,
        `Sisa        : ${formatRupiah((lastOrderTotal.value || totalAmount.value) - (paidAmount.value || 0))}`,
        separator,
        '---TERIMA KASIH---'.padStart(Math.floor((maxLineWidth + 18) / 2)),
        'aninkafashion.com'.padStart(Math.floor((maxLineWidth + 19) / 2)),
        '\n\n\n',
    ];

    const rawText = lines.join('\n');

    const encoded = encodeURIComponent(rawText).replace(/'/g, '%27').replace(/"/g, '%22');

    const rawbtUrl = `intent://${encoded}#Intent;scheme=rawbt;package=ru.a402d.rawbtprinter;end;`;

    window.location.href = rawbtUrl;
}

// function printToRawBT2() {
//     const lines = [
//         `     ${locationName.value}`,
//         ` ${locationAddress.value}`,
//         '----------------------------------------',
//         `Kasir   : ${cashierName.value}`,
//         `Tanggal : ${lastOrderDate.value}`,
//         `Nomor   : ${transactionNumber.value}`,
//         '----------------------------------------',
//         `Customer: ${selectedCustomerName.value || '-'}`,
//         '----------------------------------------',
//         ...lastOrderItems.value.flatMap(item => [
//             `${item.product_name}`,
//             `${item.quantity} x ${formatRupiah(item.price)}${' '.repeat(20 - (item.quantity + '').length - formatRupiah(item.price).length)}${formatRupiah(item.quantity * item.price)}`
//         ]),
//         '----------------------------------------',
//         `Total QTY        ${lastOrderItems.value.reduce((sum, item) => sum + item.quantity, 0)}`,
//         `Total Bayar      ${formatRupiah(lastOrderTotal.value || totalAmount.value)}`,
//         `Jenis Bayar      ${lastOrderPaymentMethodName.value || '-'}`,
//         `Bayar Cash       ${formatRupiah(paidAmount.value || 0)}`,
//         `Sisa             ${formatRupiah(((lastOrderTotal.value || totalAmount.value) - (paidAmount.value || 0)))}`,
//         '----------------------------------------',
//         '      ---TERIMA KASIH---',
//         '     aninkafashion.com',
//         '\n\n\n'
//     ];

//     const rawText = lines.join('\n');

//     // Encode entire string
//     const encoded = encodeURIComponent(rawText)
//         .replace(/'/g, '%27')
//         .replace(/"/g, '%22');

//     // Build RawBT intent URI
//     const rawbtUrl = `intent://${encoded}#Intent;scheme=rawbt;package=ru.a402d.rawbtprinter;end;`;

//     // Redirect
//     window.location.href = rawbtUrl;
// }

function autoPrint() {
    if (isMobileDevice()) {
        printToRawBT();
    } else {
        doPrintKasir80mm(); // Menggunakan QZ Tray
    }
}

function isMobileDevice() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

const formatDate = (date: string | null | undefined) => {
    if (!date) return '-';

    const dt = new Date(date);

    const formatter = new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
        timeZone: 'Asia/Jakarta',
    });

    const parts = formatter.formatToParts(dt);

    const get = (type: string) => parts.find((p) => p.type === type)?.value || '';

    return `${get('day')}/${get('month')}/${get('year')} ${get('hour')}:${get('minute')} WIB`;
};

fetchProducts();
fetchPaymentMethods();
</script>

<style scoped>
.print-area {
    width: 100%;
    max-width: 600px;
}

@media print {
    .no-print,
    .no-print * {
        display: none !important;
    }
}

.print-area {
    width: 58mm;
    /* Set to 58mm for thermal printer */
    font-family: 'Courier New', Courier, monospace;
    /* Monospaced font for better alignment */
    font-size: 10px;
    /* Adjust font size as needed */
    padding: 5mm;
    /* Padding around the content */
    box-shadow: none;
    /* Remove box shadow for print */
}
</style>
