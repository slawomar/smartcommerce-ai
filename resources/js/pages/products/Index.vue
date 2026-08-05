<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ref, watch } from 'vue';

interface Product {
    id: number;
    name: string;
    sku: string;
    price: string;
    stock: number;
    is_active: boolean;
    created_at: string;
}

interface PaginatedProducts {
    data: Product[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface ProductFilters {
    search: string;
}

const props = defineProps<{
    products: PaginatedProducts;
    filters: ProductFilters;
}>();

const search = ref(props.filters.search);

watch(
    () => props.filters.search,
    (value) => {
        search.value = value;
    },
);

const submitSearch = (): void => {
    const normalizedSearch = search.value.trim();

    router.get(
        route('products.index'),
        normalizedSearch !== ''
            ? { search: normalizedSearch }
            : {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const clearSearch = (): void => {
    search.value = '';

    router.get(
        route('products.index'),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const formatPrice = (price: string): string => {
    return new Intl.NumberFormat('pl-PL', {
        style: 'currency',
        currency: 'PLN',
    }).format(Number(price));
};

const formatDate = (date: string): string => {
    return new Intl.DateTimeFormat('pl-PL', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).format(new Date(date));
};

const deletingProductId = ref<number | null>(null);

const deleteProduct = (product: Product): void => {
    const confirmed = window.confirm(
        `Czy na pewno chcesz usunąć produkt „${product.name}”?`,
    );

    if (!confirmed) {
        return;
    }

    router.delete(route('products.destroy', product.id), {
        preserveScroll: true,

        onStart: () => {
            deletingProductId.value = product.id;
        },

        onFinish: () => {
            deletingProductId.value = null;
        },
    });
};
</script>

<template>
    <Head title="Produkty" />

    <main class="min-h-screen bg-gray-50 px-6 py-10 text-gray-900">
        <div class="mx-auto max-w-6xl">
            <header
                class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-3xl font-semibold">
                        Katalog produktów
                    </h1>

                    <p class="mt-1 text-sm text-gray-600">
                        <template v-if="filters.search">
                            Wyniki wyszukiwania dla „{{ filters.search }}”:
                            {{ products.total }}
                        </template>

                        <template v-else>
                            Produkty przypisane do zalogowanego użytkownika:
                            {{ products.total }}
                        </template>
                    </p>
                </div>

                <div class="flex items-center gap-4">
                    <Link
                        :href="route('dashboard')"
                        class="text-sm font-medium text-gray-700 hover:text-gray-950"
                    >
                        Wróć do panelu
                    </Link>

                    <Link
                        :href="route('products.create')"
                        class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700"
                    >
                        Dodaj produkt
                    </Link>
                </div>
            </header>

            <form
                class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
                @submit.prevent="submitSearch"
            >
                <label
                    for="product-search"
                    class="mb-2 block text-sm font-medium text-gray-700"
                >
                    Wyszukaj produkt
                </label>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <input
                        id="product-search"
                        v-model="search"
                        type="search"
                        name="search"
                        placeholder="Nazwa, SKU lub opis produktu"
                        autocomplete="off"
                        class="min-w-0 flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200"
                    />

                    <button
                        type="submit"
                        class="rounded-md bg-gray-900 px-5 py-2 text-sm font-medium text-white transition hover:bg-gray-700"
                    >
                        Szukaj
                    </button>

                    <button
                        v-if="filters.search"
                        type="button"
                        class="rounded-md border border-gray-300 bg-white px-5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                        @click="clearSearch"
                    >
                        Wyczyść
                    </button>
                </div>
            </form>

            <div
                v-if="products.data.length === 0"
                class="rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center"
            >
                <template v-if="filters.search">
                    <h2 class="text-lg font-medium">
                        Brak wyników
                    </h2>

                    <p class="mt-2 text-sm text-gray-600">
                        Nie znaleziono produktów pasujących do frazy
                        „{{ filters.search }}”.
                    </p>

                    <button
                        type="button"
                        class="mt-4 text-sm font-medium text-gray-700 underline hover:text-gray-950"
                        @click="clearSearch"
                    >
                        Wyczyść wyszukiwanie
                    </button>
                </template>

                <template v-else>
                    <h2 class="text-lg font-medium">
                        Brak produktów
                    </h2>

                    <p class="mt-2 text-sm text-gray-600">
                        W katalogu nie ma jeszcze żadnych produktów.
                    </p>
                </template>
            </div>

            <div
                v-else
                class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm"
            >
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600"
                                >
                                    Nazwa
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600"
                                >
                                    SKU
                                </th>

                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600"
                                >
                                    Cena
                                </th>

                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600"
                                >
                                    Stan
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600"
                                >
                                    Status
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600"
                                >
                                    Utworzono
                                </th>

                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600"
                                >
                                    Akcje
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="product in products.data"
                                :key="product.id"
                                class="hover:bg-gray-50"
                            >
                                <td class="px-4 py-4 font-medium">
                                    {{ product.name }}
                                </td>

                                <td class="px-4 py-4 font-mono text-sm text-gray-600">
                                    {{ product.sku }}
                                </td>

                                <td class="px-4 py-4 text-right">
                                    {{ formatPrice(product.price) }}
                                </td>

                                <td class="px-4 py-4 text-right">
                                    {{ product.stock }}
                                </td>

                                <td class="px-4 py-4">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="
                                            product.is_active
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-gray-100 text-gray-700'
                                        "
                                    >
                                        {{
                                            product.is_active
                                                ? 'Aktywny'
                                                : 'Nieaktywny'
                                        }}
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-sm text-gray-600">
                                    {{ formatDate(product.created_at) }}
                                </td>

                                <td class="px-4 py-4 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <Link
                                            :href="route('products.edit', product.id)"
                                            class="text-sm font-medium text-gray-700 hover:text-gray-950"
                                        >
                                            Edytuj
                                        </Link>

                                        <button
                                            type="button"
                                            class="text-sm font-medium text-red-600 hover:text-red-800 disabled:cursor-not-allowed disabled:opacity-50"
                                            :disabled="deletingProductId === product.id"
                                            @click="deleteProduct(product)"
                                        >
                                            {{
                                                deletingProductId === product.id
                                                    ? 'Usuwanie...'
                                                    : 'Usuń'
                                            }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <footer
                    class="border-t border-gray-200 px-4 py-3 text-sm text-gray-600"
                >
                    Wyświetlono
                    {{ products.from }}–{{ products.to }}
                    z {{ products.total }} produktów
                </footer>
            </div>
        </div>
    </main>
</template>