<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

interface ProductStats {
    total: number;
    active: number;
    lowStock: number;
    lowStockThreshold: number;
}

interface RecentProduct {
    id: number;
    name: string;
    sku: string;
    price: string;
    stock: number;
    is_active: boolean;
    created_at: string;
}

defineProps<{
    productStats: ProductStats;
    recentProducts: RecentProduct[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
];

const formatPrice = (price: string): string => {
    return Number(price).toLocaleString('pl-PL', {
        style: 'currency',
        currency: 'PLN',
    });
};

const formatDate = (date: string): string => {
    return new Intl.DateTimeFormat('pl-PL').format(new Date(date));
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Dashboard
                    </h1>

                    <p class="mt-1 text-sm text-muted-foreground">
                        Podsumowanie Twojego katalogu produktów.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="route('products.index')"
                        class="inline-flex items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground"
                    >
                        Zobacz produkty
                    </Link>

                    <Link
                        :href="route('products.create')"
                        class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90"
                    >
                        Dodaj produkt
                    </Link>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div
                    class="rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border"
                >
                    <p class="text-sm font-medium text-muted-foreground">
                        Wszystkie produkty
                    </p>

                    <p class="mt-3 text-3xl font-bold">
                        {{ productStats.total }}
                    </p>

                    <p class="mt-2 text-sm text-muted-foreground">
                        Łączna liczba produktów w katalogu
                    </p>
                </div>

                <div
                    class="rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border"
                >
                    <p class="text-sm font-medium text-muted-foreground">
                        Aktywne produkty
                    </p>

                    <p class="mt-3 text-3xl font-bold">
                        {{ productStats.active }}
                    </p>

                    <p class="mt-2 text-sm text-muted-foreground">
                        Produkty widoczne i dostępne w katalogu
                    </p>
                </div>

                <div
                    class="rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border"
                >
                    <p class="text-sm font-medium text-muted-foreground">
                        Niski stan magazynowy
                    </p>

                    <p class="mt-3 text-3xl font-bold">
                        {{ productStats.lowStock }}
                    </p>

                    <p class="mt-2 text-sm text-muted-foreground">
                        Aktywne produkty ze stanem do
                        {{ productStats.lowStockThreshold }} sztuk
                    </p>
                </div>
            </div>

            <section
                class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm dark:border-sidebar-border"
            >
                <div
                    class="flex items-center justify-between border-b border-sidebar-border/70 px-6 py-4 dark:border-sidebar-border"
                >
                    <div>
                        <h2 class="text-lg font-semibold">
                            Ostatnio dodane produkty
                        </h2>

                        <p class="mt-1 text-sm text-muted-foreground">
                            Pięć najnowszych pozycji w Twoim katalogu.
                        </p>
                    </div>

                    <Link
                        :href="route('products.index')"
                        class="text-sm font-medium text-primary hover:underline"
                    >
                        Pełna lista
                    </Link>
                </div>

                <div
                    v-if="recentProducts.length === 0"
                    class="px-6 py-12 text-center"
                >
                    <p class="font-medium">
                        Nie masz jeszcze żadnych produktów.
                    </p>

                    <p class="mt-1 text-sm text-muted-foreground">
                        Dodaj pierwszy produkt, aby rozpocząć budowę katalogu.
                    </p>

                    <Link
                        :href="route('products.create')"
                        class="mt-4 inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90"
                    >
                        Dodaj pierwszy produkt
                    </Link>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-muted/50 text-muted-foreground">
                            <tr>
                                <th class="px-6 py-3 font-medium">
                                    Produkt
                                </th>
                                <th class="px-6 py-3 font-medium">
                                    SKU
                                </th>
                                <th class="px-6 py-3 font-medium">
                                    Cena
                                </th>
                                <th class="px-6 py-3 font-medium">
                                    Stan
                                </th>
                                <th class="px-6 py-3 font-medium">
                                    Status
                                </th>
                                <th class="px-6 py-3 font-medium">
                                    Dodano
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="product in recentProducts"
                                :key="product.id"
                                class="border-t border-sidebar-border/70 dark:border-sidebar-border"
                            >
                                <td class="px-6 py-4 font-medium">
                                    {{ product.name }}
                                </td>

                                <td
                                    class="px-6 py-4 font-mono text-xs text-muted-foreground"
                                >
                                    {{ product.sku }}
                                </td>

                                <td class="px-6 py-4">
                                    {{ formatPrice(product.price) }}
                                </td>

                                <td class="px-6 py-4">
                                    <span
                                        :class="[
                                            'inline-flex rounded-full px-2.5 py-1 text-xs font-medium',
                                            product.stock <=
                                            productStats.lowStockThreshold
                                                ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200'
                                                : 'bg-muted text-muted-foreground',
                                        ]"
                                    >
                                        {{ product.stock }} szt.
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <span
                                        :class="[
                                            'inline-flex rounded-full px-2.5 py-1 text-xs font-medium',
                                            product.is_active
                                                ? 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-200'
                                                : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                        ]"
                                    >
                                        {{
                                            product.is_active
                                                ? 'Aktywny'
                                                : 'Nieaktywny'
                                        }}
                                    </span>
                                </td>

                                <td
                                    class="px-6 py-4 text-muted-foreground"
                                >
                                    {{ formatDate(product.created_at) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AppLayout>
</template>