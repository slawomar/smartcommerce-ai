<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

interface Product {
    id: number;
    name: string;
    sku: string;
    description: string | null;
    image_path: string | null;
    price: string;
    stock: number;
    created_at: string;
}

defineProps<{
    product: Product;
}>();

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
    <Head :title="product.name" />

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

        <section class="mx-auto max-w-7xl px-6 py-10">
            <Link
                :href="route('catalog.index')"
                class="inline-flex items-center text-sm font-medium text-gray-600 transition hover:text-gray-950"
            >
                ← Wróć do katalogu
            </Link>

            <div
                class="mt-6 grid overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm lg:grid-cols-2"
            >
                <div
                    class="flex min-h-80 items-center justify-center overflow-hidden bg-gradient-to-br from-gray-100 to-gray-50 lg:min-h-[520px]"
                >
                    <img
                        v-if="product.image_path"
                        :src="`/storage/${product.image_path}`"
                        :alt="product.name"
                        class="h-full max-h-[620px] w-full object-contain p-8"
                    />

                    <div
                        v-else
                        class="flex h-full min-h-80 w-full items-center justify-center p-10 lg:min-h-[520px]"
                    >
                        <div
                            class="flex h-32 w-32 items-center justify-center rounded-3xl bg-white text-5xl font-bold text-gray-400 shadow-sm"
                        >
                            {{ product.name.charAt(0).toUpperCase() }}
                        </div>
                    </div>
                </div>

                <div class="flex flex-col p-8 sm:p-10">
                    <div>
                        <p
                            class="text-sm font-medium uppercase tracking-wide text-gray-400"
                        >
                            {{ product.sku }}
                        </p>

                        <h1
                            class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl"
                        >
                            {{ product.name }}
                        </h1>
                    </div>

                    <div class="mt-8">
                        <p class="text-sm text-gray-500">
                            Cena
                        </p>

                        <p class="mt-1 text-3xl font-bold">
                            {{ formatPrice(product.price) }}
                        </p>
                    </div>

                    <div class="mt-6">
                        <span
                            class="inline-flex rounded-full px-3 py-1.5 text-sm font-medium"
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

                    <div class="mt-10 border-t border-gray-200 pt-8">
                        <h2 class="text-lg font-semibold">
                            Opis produktu
                        </h2>

                        <p
                            v-if="product.description"
                            class="mt-4 whitespace-pre-line leading-7 text-gray-600"
                        >
                            {{ product.description }}
                        </p>

                        <p
                            v-else
                            class="mt-4 text-gray-500"
                        >
                            Ten produkt nie ma jeszcze opisu.
                        </p>
                    </div>

                    <div
                        class="mt-auto border-t border-gray-200 pt-8"
                    >
                        <p class="text-sm text-gray-500">
                            Stan magazynowy
                        </p>

                        <p class="mt-1 font-medium">
                            {{ product.stock }} szt.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>
</template>