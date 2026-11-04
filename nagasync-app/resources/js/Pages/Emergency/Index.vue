<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { defineProps, onMounted, onUnmounted, ref } from 'vue';

defineProps({ types: Array, alerts: Array });

const form = useForm({ alert_type: 'other', latitude: null, longitude: null, accuracy_m: null, note: '' });
const HOLD_MS = 1500;
const progress = ref(0);
const busy = ref(false);
const geoMessage = ref('');
let holdTimer = null;
let holdStart = 0;
let poll;

function startHold() {
    if (busy.value || form.processing) return;
    holdStart = performance.now();
    holdTimer = setInterval(() => {
        progress.value = Math.min(100, ((performance.now() - holdStart) / HOLD_MS) * 100);
        if (progress.value >= 100) {
            cancelHold();
            send();
        }
    }, 30);
}

function cancelHold() {
    clearInterval(holdTimer);
    holdTimer = null;
    progress.value = 0;
}

function send() {
    busy.value = true;
    geoMessage.value = 'Getting your location...';

    const submit = () => form.post(route('emergency.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('note'),
        onFinish: () => (busy.value = false),
    });

    // An alert must never wait on GPS: if location fails, send anyway
    if (!navigator.geolocation) {
        geoMessage.value = 'Location unavailable. Alert sent without it.';
        return submit();
    }
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            form.latitude = pos.coords.latitude;
            form.longitude = pos.coords.longitude;
            form.accuracy_m = Math.round(pos.coords.accuracy);
            geoMessage.value = 'Location captured.';
            submit();
        },
        () => {
            geoMessage.value = 'Could not get your location. Alert sent without it.';
            submit();
        },
        { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 },
    );
}

onMounted(() => {
    poll = setInterval(() => {
        if (!document.hidden) router.reload({ only: ['alerts'], preserveScroll: true, preserveState: true });
    }, 10000);
});
onUnmounted(() => { clearInterval(poll); cancelHold(); });

const badge = {
    active: 'bg-red-100 text-red-800', dispatched: 'bg-blue-100 text-blue-800',
    resolved: 'bg-green-100 text-green-800', false_alarm: 'bg-gray-200 text-gray-700',
};
</script>

<template>
    <Head title="Emergency" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold text-gray-800">Emergency Alert</h2></template>

        <div class="mx-auto max-w-xl space-y-6 p-6">
            <div class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">
                In a life-threatening emergency, also call <strong>911</strong> or your local emergency hotline.
                This alert goes to your local responders and shares your location with them.
            </div>

            <div class="space-y-4 rounded-lg bg-white p-6 text-center shadow">
                <div class="flex flex-wrap justify-center gap-2">
                    <button v-for="t in types" :key="t" type="button" @click="form.alert_type = t"
                            :class="form.alert_type === t ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-700'"
                            class="rounded-full px-3 py-1 text-sm capitalize">{{ t }}</button>
                </div>

                <button type="button"
                        @pointerdown.prevent="startHold" @pointerup="cancelHold"
                        @pointerleave="cancelHold" @pointercancel="cancelHold"
                        :disabled="busy || form.processing"
                        class="relative mx-auto flex h-44 w-44 select-none items-center justify-center overflow-hidden rounded-full bg-red-600 text-lg font-bold text-white shadow-lg disabled:opacity-60">
                    <span class="absolute bottom-0 left-0 w-full bg-red-900/60" :style="{ height: progress + '%' }"></span>
                    <span class="relative">{{ busy ? 'SENDING...' : 'HOLD TO SEND ALERT' }}</span>
                </button>

                <input v-model="form.note" maxlength="255" placeholder="Optional note (e.g. landmark, what happened)"
                       class="w-full rounded border-gray-300 text-sm" />
                <p v-if="geoMessage" class="text-sm text-gray-600">{{ geoMessage }}</p>
                <p v-if="form.recentlySuccessful" class="text-sm font-semibold text-green-700">
                    Alert sent. Stay where you are if it is safe.
                </p>
                <p v-if="form.errors.alert_type || form.errors.note" class="text-sm text-red-600">
                    {{ form.errors.alert_type || form.errors.note }}
                </p>
            </div>

            <div v-for="a in alerts" :key="a.id" class="flex items-center justify-between rounded-lg bg-white p-4 shadow">
                <div>
                    <p class="font-semibold capitalize">{{ a.type }}</p>
                    <p class="text-xs text-gray-500">{{ a.at }} · {{ a.located ? 'location shared' : 'no location' }}</p>
                </div>
                <span :class="badge[a.status]" class="rounded px-2 py-0.5 text-xs font-medium capitalize">{{ a.status.replace('_', ' ') }}</span>
            </div>
        </div>
    </AuthenticatedLayout>
</template>