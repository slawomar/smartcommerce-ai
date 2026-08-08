<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { computed } from 'vue';

interface Product {
    id: number;
    name: string;
    sku: string;
    price: string;
    stock: number;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedProducts {
    data: Product[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
    links: PaginationLink[];
}

const props = defineProps<{
    products: PaginatedProducts;
}>();

const pageLinks = computed(() => {
    return props.products.links.slice(1, -1);
});

const formatPrice = (price: string): string => {
    return new Intl.NumberFormat('pl-PL', {
        style: 'currency',
        currency: 'PLN',
    }).format(Number(price));
};

const stockLabel = (stock: number): string => {
    if (stock === 0) {
        return 'Brak na magazynie';
    }

    if (stock <= 5) {
        return `Ostatnie ${stock} szt.`;
    }

    return 'Dostępny';
};
</script>

<template>
    <Head title="Katalog produktów" />

    <main class="min-h-screen bg-gray-50 text-gray-900">
        <header class="border-b border-gray-200 bg-white">
            <div
                class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5"
            >
                <div>
                    <Link
                        :href="route('home')"
                        class="text-xl font-bold tracking-tight"
                    >
                        SmartCommerce AI
                    </Link>

                    <p class="mt-1 text-xs text-gray-500">
                        Inteligentny katalog produktów
                    </p>
                </div>

                <Link
                    :href="route('login')"
                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                >
                    Zaloguj się
                </Link>
            </div>
        </header>

        <section class="mx-auto max-w-7xl px-6 py-12">
            <div
                class="mb-10 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
            >
                <div>
                    <p
                        class="mb-2 text-sm font-semibold uppercase tracking-wider text-gray-500"
                    >
                        SmartCommerce AI
                    </p>

                    <h1
                        class="text-3xl font-bold tracking-tight sm:text-4xl"
                    >
                        Katalog produktów
                    </h1>

                    <p class="mt-3 max-w-2xl text-gray-600">
                        Przeglądaj aktywne produkty dostępne w katalogu
                        SmartCommerce AI.
                    </p>
                </div>

                <p
                    v-if="products.total > 0"
                    class="text-sm text-gray-500"
                >
                    {{ products.total }}
                    {{
                        products.total === 1
                            ? 'produkt'
                            : 'produktów'
                    }}
                </p>
            </div>

            <div
                v-if="products.data.length === 0"
                class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-20 text-center"
            >
                <h2 class="text-xl font-semibold">
                    Katalog jest obecnie pusty
                </h2>

                <p class="mt-2 text-sm text-gray-600">
                    Nie ma jeszcze aktywnych produktów do wyświetlenia.
                </p>
            </div>

            <template v-else>
                <div
                    class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                >
                    <article
                        v-for="product in products.data"
                        :key="product.id"
                        class="group flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div
                            class="flex aspect-[4/3] items-center justify-center bg-gradient-to-br from-gray-100 to-gray-50"
                        >
                            <div
                                class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-2xl font-bold text-gray-400 shadow-sm"
                            >
                                {{
                                    product.name
                                        .charAt(0)
                                        .toUpperCase()
                                }}
                            </div>
                        </div>

                        <div class="flex flex-1 flex-col p-5">
                            <div class="mb-4">
                                <p
                                    class="text-xs font-medium uppercase tracking-wide text-gray-400"
                                >
                                    {{ product.sku }}
                                </p>

                                <h2
                                    class="mt-1 text-lg font-semibold leading-snug"
                                >
                                    {{ product.name }}
                                </h2>
                            </div>

                            <div
                                class="mt-auto flex items-end justify-between gap-4"
                            >
                                <div>
                                    <p
                                        class="text-xs text-gray-500"
                                    >
                                        Cena
                                    </p>

                                    <p
                                        class="mt-1 text-xl font-bold"
                                    >
                                        {{ formatPrice(product.price) }}
                                    </p>
                                </div>

                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="
                                        product.stock === 0
                                            ? 'bg-red-50 text-red-700'
                                            : product.stock <= 5
                                              ? 'bg-amber-50 text-amber-700'
                                              : 'bg-green-50 text-green-700'
                                    "
                                >
                                    {{ stockLabel(product.stock) }}
                                </span>
                            </div>
                        </div>
                    </article>
                </div>

                <footer
                    class="mt-10 flex flex-col gap-4 border-t border-gray-200 pt-6 text-sm text-gray-600 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        Wyświetlono
                        {{ products.from }}–{{ products.to }}
                        z {{ products.total }} produktów
                    </div>

                    <nav
                        v-if="products.last_page > 1"
                        class="flex flex-wrap items-center gap-1"
                        aria-label="Paginacja katalogu"
                    >
                        <Link
                            v-if="products.prev_page_url"
                            :href="products.prev_page_url"
                            preserve-scroll
                            class="rounded-md border border-gray-300 bg-white px-3 py-2 font-medium text-gray-700 transition hover:bg-gray-50"
                        >
                            Poprzednia
                        </Link>

                        <span
                            v-else
                            class="cursor-not-allowed rounded-md border border-gray-200 bg-gray-100 px-3 py-2 text-gray-400"
                        >
                            Poprzednia
                        </span>

                        <template
                            v-for="link in pageLinks"
                            :key="link.label"
                        >
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                preserve-scroll
                                class="min-w-10 rounded-md border px-3 py-2 text-center font-medium transition"
                                :class="
                                    link.active
                                        ? 'border-gray-900 bg-gray-900 text-white'
                                        : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
                                "
                            >
                                {{ link.label }}
                            </Link>

                            <span
                                v-else
                                class="px-2 py-2 text-gray-400"
                            >
                                {{ link.label }}
                            </span>
                        </template>

                        <Link
                            v-if="products.next_page_url"
                            :href="products.next_page_url"
                            preserve-scroll
                            class="rounded-md border border-gray-300 bg-white px-3 py-2 font-medium text-gray-700 transition hover:bg-gray-50"
                        >
                            Następna
                        </Link>

                        <span
                            v-else
                            class="cursor-not-allowed rounded-md border border-gray-200 bg-gray-100 px-3 py-2 text-gray-400"
                        >
                            Następna
                        </span>
                    </nav>
                </footer>
            </template>
        </section>
    </main>
</template>