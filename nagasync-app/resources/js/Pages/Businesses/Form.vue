<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { CATEGORY, TAG_LABEL } from '@/categories';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ business: Object, categories: Array, tags: Array, barangays: Array, defaultBarangay: Number });
const editing = !!props.business;
const b = props.business ?? {};

const form = useForm({
    name: b.name ?? '', category: b.category ?? 'cafe', tagline: b.tagline ?? '', description: b.description ?? '',
    barangay_id: b.barangay_id ?? props.defaultBarangay ?? '', address: b.address ?? '',
    latitude: b.latitude ?? '', longitude: b.longitude ?? '', phone: b.phone ?? '', email: b.email ?? '',
    website: b.website ?? '', facebook_url: b.facebook_url ?? '', hours: b.hours ?? '',
    price_range: b.price_range ?? '', tags: b.tags ?? [], permit_no: b.permit_no ?? '', cover: null,
});

const preview = ref(b.cover_url ?? null);
function onCover(e) {
    const file = e.target.files[0] ?? null;
    form.cover = file;
    if (file) preview.value = URL.createObjectURL(file);
}

function locate() {
    navigator.geolocation?.getCurrentPosition(
        (p) => { form.latitude = p.coords.latitude.toFixed(6); form.longitude = p.coords.longitude.toFixed(6); },
        () => alert('Could not get your location. You can leave this blank.'),
        { enableHighAccuracy: true, timeout: 8000 },
    );
}

// A file upload needs multipart; PUT is sent as POST with a method override
const submit = () => editing
    ? form.transform((d) => ({ ...d, _method: 'put' })).post(route('businesses.update', b.id), { forceFormData: true })
    : form.post(route('businesses.store'), { forceFormData: true });

const input = 'mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-ns-violet focus:ring-ns-violet';
const label = 'block text-sm font-semibold';
const err = 'mt-1 text-sm text-red-600';
</script>

<template>
    <Head :title="editing ? 'Edit business' : 'Add a business'" />
    <AuthenticatedLayout>
        <template #header>{{ editing ? 'Edit business' : 'Add a business' }}</template>

        <form @submit.prevent="submit" class="mx-auto max-w-3xl space-y-5 rounded-3xl bg-white p-5 shadow-card sm:p-8">
            <p v-if="editing && business.status === 'approved'" class="rounded-xl bg-amber-50 p-3 text-sm text-amber-900">
                Changing the name, category, description, address, location or photo sends this listing back for review. Hours, phone and features update straight away.
            </p>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2"><label for="name" :class="label">Business name</label>
                    <input id="name" v-model="form.name" required maxlength="120" :class="input" /><p :class="err">{{ form.errors.name }}</p></div>

                <div><label for="category" :class="label">Category</label>
                    <select id="category" v-model="form.category" :class="input"><option v-for="c in categories" :key="c" :value="c">{{ CATEGORY[c].label }}</option></select>
                    <p :class="err">{{ form.errors.category }}</p></div>

                <div><label for="price" :class="label">Price level (optional)</label>
                    <select id="price" v-model="form.price_range" :class="input">
                        <option value="">Not shown</option><option :value="1">₱ Budget</option><option :value="2">₱₱ Moderate</option><option :value="3">₱₱₱ Premium</option></select></div>

                <div class="sm:col-span-2"><label for="tagline" :class="label">Short tagline (optional)</label>
                    <input id="tagline" v-model="form.tagline" maxlength="140" :class="input" /><p :class="err">{{ form.errors.tagline }}</p></div>

                <div class="sm:col-span-2"><label for="description" :class="label">Description (optional)</label>
                    <textarea id="description" v-model="form.description" rows="4" maxlength="2000" :class="input"></textarea><p :class="err">{{ form.errors.description }}</p></div>

                <div><label for="barangay" :class="label">Barangay</label>
                    <select id="barangay" v-model="form.barangay_id" required :class="input">
                        <option value="" disabled>Select</option><option v-for="x in barangays" :key="x.id" :value="x.id">{{ x.name }}</option></select>
                    <p :class="err">{{ form.errors.barangay_id }}</p></div>

                <div><label for="address" :class="label">Street address</label>
                    <input id="address" v-model="form.address" required maxlength="255" :class="input" /><p :class="err">{{ form.errors.address }}</p></div>

                <div class="sm:col-span-2">
                    <div class="flex items-end gap-3">
                        <div class="flex-1"><label for="lat" :class="label">Latitude (optional)</label><input id="lat" v-model="form.latitude" inputmode="decimal" :class="input" /></div>
                        <div class="flex-1"><label for="lng" :class="label">Longitude (optional)</label><input id="lng" v-model="form.longitude" inputmode="decimal" :class="input" /></div>
                        <button type="button" class="h-11 shrink-0 rounded-full border border-ns-mist px-4 text-sm font-semibold hover:bg-ns-mist focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet" @click="locate">Use my location</button>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Shown publicly as a map link, so only use the business's own location.</p>
                    <p :class="err">{{ form.errors.latitude || form.errors.longitude }}</p>
                </div>

                <div><label for="phone" :class="label">Business phone (optional)</label><input id="phone" v-model="form.phone" type="tel" maxlength="20" :class="input" /><p :class="err">{{ form.errors.phone }}</p></div>
                <div><label for="email" :class="label">Business email (optional)</label><input id="email" v-model="form.email" type="email" maxlength="120" :class="input" /><p :class="err">{{ form.errors.email }}</p></div>
                <div><label for="website" :class="label">Website (optional)</label><input id="website" v-model="form.website" placeholder="https://" :class="input" /><p :class="err">{{ form.errors.website }}</p></div>
                <div><label for="fb" :class="label">Facebook page (optional)</label><input id="fb" v-model="form.facebook_url" placeholder="https://" :class="input" /><p :class="err">{{ form.errors.facebook_url }}</p></div>
                <div class="sm:col-span-2"><label for="hours" :class="label">Opening hours (optional)</label><input id="hours" v-model="form.hours" maxlength="255" placeholder="Mon–Sat 8am–9pm" :class="input" /></div>
            </div>

            <fieldset>
                <legend :class="label">Features (optional)</legend>
                <div class="mt-2 flex flex-wrap gap-2">
                    <label v-for="t in tags" :key="t" class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-full bg-ns-mist px-4 text-sm font-medium has-[:checked]:bg-ns-ink has-[:checked]:text-white">
                        <input v-model="form.tags" type="checkbox" :value="t" class="sr-only" /> {{ TAG_LABEL[t] }}
                    </label>
                </div>
            </fieldset>

            <div>
                <label for="cover" :class="label">Cover photo (optional)</label>
                <img v-if="preview" :src="preview" alt="Cover preview" class="mt-2 h-40 w-full max-w-xs rounded-2xl object-cover" />
                <input id="cover" type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 block text-sm" @change="onCover" />
                <p class="mt-1 text-xs text-slate-500">JPG, PNG or WebP, at least 400×300, up to 2 MB.</p>
                <p :class="err">{{ form.errors.cover }}</p>
            </div>

            <div>
                <label for="permit" :class="label">Business permit number (optional)</label>
                <input id="permit" v-model="form.permit_no" maxlength="60" :class="input" />
                <p class="mt-1 text-xs text-slate-500">Only barangay staff can see this. It helps them review your listing faster.</p>
            </div>

            <div class="flex items-center justify-end gap-3">
                <Link :href="route('businesses.index')" class="text-sm font-semibold underline">Cancel</Link>
                <button :disabled="form.processing" class="h-11 rounded-full bg-ns-orange px-6 text-sm font-semibold text-ns-ink shadow-card transition hover:shadow-lg active:scale-95 disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ns-violet">
                    {{ editing ? 'Save changes' : 'Submit for review' }}
                </button>
            </div>
        </form>
    </AuthenticatedLayout>
</template>