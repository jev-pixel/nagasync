<script setup>
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Icon from '@/Components/Icon.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, defineProps, onMounted, onUnmounted, ref } from 'vue';

defineProps({ types: Array, alerts: Array });

const form = useForm({ alert_type: 'other', latitude: null, longitude: null, accuracy_m: null, note: '' });
const HOLD_MS = 1500;
const progress = ref(0);
const busy = ref(false);
const geoMessage = ref('');
let holdTimer = null;
let holdStart = 0;
let poll;

// Progress ring around the button
const RING = 2 * Math.PI * 104;
const ringOffset = computed(() => RING * (1 - progress.value / 100));

function startHold() {
    if (holdTimer || busy.value || form.processing) return; // also ignores key auto-repeat
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
</script>

<template>
    <Head title="Emergency" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold text-gray-800">Emergency Alert</h2></template>

        <div class="mx-auto max-w-xl space-y-5 px-4 py-6 sm:px-6">
            <div class="rounded-card border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                In a life-threatening emergency, also call <strong>911</strong> or your local emergency hotline.
                This alert goes to your local responders and shares your location with them.
            </div>

            <Card class="space-y-5 text-center">
                <div>
                    <p class="mb-2 text-sm text-gray-500">What kind of emergency?</p>
                    <div class="flex flex-wrap justify-center gap-2">
                        <button v-for="t in types" :key="t" type="button" @click="form.alert_type = t"
                                :aria-pressed="form.alert_type === t"
                                :class="form.alert_type === t ? 'bg-brand-900 text-white' : 'bg-brand-50 text-brand-800 hover:bg-brand-100'"
                                class="min-h-[40px] rounded-full px-4 text-sm font-medium capitalize transition-colors">{{ t }}</button>
                    </div>
                </div>

                <div class="relative mx-auto h-56 w-56">
                    <svg viewBox="0 0 224 224" class="absolute inset-0 -rotate-90" aria-hidden="true">
                        <circle cx="112" cy="112" r="104" fill="none" stroke-width="8" class="stroke-red-100" />
                        <circle cx="112" cy="112" r="104" fill="none" stroke-width="8" stroke-linecap="round"
                                class="stroke-red-600" :stroke-dasharray="RING" :stroke-dashoffset="ringOffset" />
                    </svg>
                    <button type="button"
                            @pointerdown.prevent="startHold" @pointerup="cancelHold"
                            @pointerleave="cancelHold" @pointercancel="cancelHold"
                            @keydown.space.prevent="startHold" @keydown.enter.prevent="startHold"
                            @keyup.space="cancelHold" @keyup.enter="cancelHold" @blur="cancelHold"
                            :disabled="busy || form.processing"
                            class="absolute inset-4 flex select-none touch-none flex-col items-center justify-center gap-1 rounded-full bg-red-600 text-white shadow-lg transition-transform focus:outline-none focus-visible:ring-4 focus-visible:ring-red-300 active:scale-[0.97] disabled:opacity-60">
                        <Icon name="alert" class="h-10 w-10" />
                        <span class="text-lg font-bold">{{ busy ? 'Sending...' : 'Hold to send' }}</span>
                        <span v-if="!busy" class="text-xs text-red-100">Press and hold for 1.5 seconds</span>
                    </button>
                </div>

                <input v-model="form.note" maxlength="255" placeholder="Optional note (e.g. landmark, what happened)"
                       class="w-full rounded-tile border-gray-300 text-sm" />
                <p v-if="geoMessage" class="text-sm text-gray-600" role="status">{{ geoMessage }}</p>
                <p v-if="form.recentlySuccessful" class="text-sm font-semibold text-green-700" role="status">
                    Alert sent. Stay where you are if it is safe.
                </p>
                <p v-if="form.errors.alert_type || form.errors.note" class="text-sm text-red-600">
                    {{ form.errors.alert_type || form.errors.note }}
                </p>
            </Card>

            <div>
                <h3 class="mb-2 text-sm font-semibold text-gray-700">Your alerts</h3>
                <Card :padded="false" class="overflow-hidden">
                    <ul class="divide-y divide-gray-100">
                        <li v-for="a in alerts" :key="a.id" class="flex items-center justify-between gap-3 p-4">
                            <div class="min-w-0">
                                <p class="font-semibold capitalize">{{ a.type }}</p>
                                <p class="text-xs text-gray-500">{{ a.at }} · {{ a.located ? 'location shared' : 'no location' }}</p>
                            </div>
                            <StatusBadge :status="a.status" />
                        </li>
                    </ul>
                    <EmptyState v-if="!alerts.length" title="No alerts sent" text="Alerts you send will be listed here with their status." icon="bell" />
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>