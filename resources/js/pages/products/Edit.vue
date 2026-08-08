<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { onBeforeUnmount, ref } from 'vue';

type Product = {
    id: number;
    name: string;
    sku: string;
    description: string | null;
    image_path: string | null;
    price: string;
    stock: number;
    is_active: boolean;
};

type ProductForm = {
    _method: 'put';
    name: string;
    sku: string;
    description: string;
    image: File | null;
    remove_image: boolean;
    price: string;
    stock: number;
    is_active: boolean;
};

const props = defineProps<{
    product: Product;
}>();

const form = useForm<ProductForm>({
    _method: 'put',
    name: props.product.name,
    sku: props.product.sku,
    description: props.product.description ?? '',
    image: null,
    remove_image: false,
    price: props.product.price,
    stock: props.product.stock,
    is_active: props.product.is_active,
});

const imagePreview = ref<string | null>(
    props.product.image_path
        ? `/storage/${props.product.image_path}`
        : null,
);

const revokePreview = (): void => {
    if (imagePreview.value?.startsWith('blob:')) {
        URL.revokeObjectURL(imagePreview.value);
    }
};

const handleImageChange = (event: Event): void => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    revokePreview();

    form.image = file;

    if (file) {
        form.remove_image = false;
    }

    imagePreview.value = file
        ? URL.createObjectURL(file)
        : props.product.image_path
          ? `/storage/${props.product.image_path}`
          : null;
};

onBeforeUnmount(() => {
    revokePreview();
});

const submit = (): void => {
    form.post(route('products.update', props.product.id), {
        preserveScroll: true,
        forceFormData: true,
    });
};
</script>

<template>
    <Head :title="`Edytuj: ${product.name}`" />

    <main class="min-h-screen bg-gray-50 px-6 py-10 text-gray-900">
        <div class="mx-auto max-w-3xl">
            <header class="mb-6">
                <Link
                    :href="route('products.index')"
                    class="text-sm font-medium text-gray-600 hover:text-gray-950"
                >
                    ← Wróć do katalogu
                </Link>

                <h1 class="mt-4 text-3xl font-semibold">
                    Edytuj produkt
                </h1>

                <p class="mt-1 text-sm text-gray-600">
                    Zmień dane produktu {{ product.name }}.
                </p>
            </header>

            <form
                class="space-y-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm"
                @submit.prevent="submit"
            >
                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label
                            for="name"
                            class="mb-2 block text-sm font-medium"
                        >
                            Nazwa produktu
                        </label>

                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            autocomplete="off"
                            class="w-full rounded-md border px-3 py-2 outline-none focus:ring-2"
                            :class="
                                form.errors.name
                                    ? 'border-red-500 focus:ring-red-200'
                                    : 'border-gray-300 focus:border-gray-500 focus:ring-gray-200'
                            "
                        />

                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="sku"
                            class="mb-2 block text-sm font-medium"
                        >
                            SKU
                        </label>

                        <input
                            id="sku"
                            v-model="form.sku"
                            type="text"
                            autocomplete="off"
                            placeholder="np. LAPTOP-001"
                            class="w-full rounded-md border px-3 py-2 font-mono outline-none focus:ring-2"
                            :class="
                                form.errors.sku
                                    ? 'border-red-500 focus:ring-red-200'
                                    : 'border-gray-300 focus:border-gray-500 focus:ring-gray-200'
                            "
                        />

                        <p
                            v-if="form.errors.sku"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.sku }}
                        </p>
                    </div>
                </div>

                <div>
                    <label
                        for="description"
                        class="mb-2 block text-sm font-medium"
                    >
                        Opis
                    </label>

                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="5"
                        class="w-full rounded-md border px-3 py-2 outline-none focus:ring-2"
                        :class="
                            form.errors.description
                                ? 'border-red-500 focus:ring-red-200'
                                : 'border-gray-300 focus:border-gray-500 focus:ring-gray-200'
                        "
                    />

                    <p
                        v-if="form.errors.description"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.description }}
                    </p>
                </div>

                <div>
                    <label
                        for="image"
                        class="mb-2 block text-sm font-medium"
                    >
                        Zdjęcie główne
                    </label>

                    <input
                        id="image"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm"
                        @change="handleImageChange"
                    />

                    <p class="mt-2 text-xs text-gray-500">
                        JPG, PNG lub WebP. Maksymalnie 5 MB.
                        Wybranie nowego zdjęcia zastąpi obecne.
                    </p>

                    <p
                        v-if="form.errors.image"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.image }}
                    </p>

                    <div
                        v-if="imagePreview && !form.remove_image"
                        class="mt-4"
                    >
                        <p class="mb-2 text-sm font-medium text-gray-700">
                            {{
                                form.image
                                    ? 'Podgląd nowego zdjęcia'
                                    : 'Obecne zdjęcie'
                            }}
                        </p>

                        <img
                            :src="imagePreview"
                            :alt="product.name"
                            class="h-48 w-48 rounded-lg border border-gray-200 object-cover"
                        />
                    </div>

                    <label
                        v-if="product.image_path && !form.image"
                        class="mt-4 flex items-center gap-3"
                    >
                        <input
                            v-model="form.remove_image"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300"
                        />

                        <span class="text-sm font-medium text-red-700">
                            Usuń obecne zdjęcie
                        </span>
                    </label>

                    <p
                        v-if="form.remove_image"
                        class="mt-3 text-sm text-red-600"
                    >
                        Zdjęcie zostanie usunięte po zapisaniu zmian.
                    </p>

                    <p
                        v-if="!imagePreview && !form.remove_image"
                        class="mt-3 text-sm text-gray-500"
                    >
                        Produkt nie ma jeszcze zdjęcia.
                    </p>

                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label
                            for="price"
                            class="mb-2 block text-sm font-medium"
                        >
                            Cena
                        </label>

                        <div class="relative">
                            <input
                                id="price"
                                v-model="form.price"
                                type="number"
                                min="0"
                                step="0.01"
                                inputmode="decimal"
                                class="w-full rounded-md border px-3 py-2 pr-12 outline-none focus:ring-2"
                                :class="
                                    form.errors.price
                                        ? 'border-red-500 focus:ring-red-200'
                                        : 'border-gray-300 focus:border-gray-500 focus:ring-gray-200'
                                "
                            />

                            <span
                                class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-gray-500"
                            >
                                zł
                            </span>
                        </div>

                        <p
                            v-if="form.errors.price"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.price }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="stock"
                            class="mb-2 block text-sm font-medium"
                        >
                            Stan magazynowy
                        </label>

                        <input
                            id="stock"
                            v-model.number="form.stock"
                            type="number"
                            min="0"
                            step="1"
                            class="w-full rounded-md border px-3 py-2 outline-none focus:ring-2"
                            :class="
                                form.errors.stock
                                    ? 'border-red-500 focus:ring-red-200'
                                    : 'border-gray-300 focus:border-gray-500 focus:ring-gray-200'
                            "
                        />

                        <p
                            v-if="form.errors.stock"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.stock }}
                        </p>
                    </div>
                </div>

                <div>
                    <label class="flex items-center gap-3">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300"
                        />

                        <span class="text-sm font-medium">
                            Produkt aktywny
                        </span>
                    </label>

                    <p
                        v-if="form.errors.is_active"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.is_active }}
                    </p>
                </div>

                <div
                    class="flex items-center justify-end gap-3 border-t border-gray-200 pt-6"
                >
                    <Link
                        :href="route('products.index')"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium hover:bg-gray-50"
                    >
                        Anuluj
                    </Link>

                    <button
                        type="submit"
                        class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        {{
                            form.processing
                                ? 'Zapisywanie...'
                                : 'Zapisz zmiany'
                        }}
                    </button>
                </div>
            </form>
        </div>
    </main>
</template>