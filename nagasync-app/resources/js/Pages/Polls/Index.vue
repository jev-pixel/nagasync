<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { defineProps, reactive } from 'vue';

defineProps({ eligible: Boolean, polls: Array });

const choice = reactive({});
const error = reactive({});
const busy = reactive({});

function vote(poll) {
    if (!choice[poll.id]) return;
    busy[poll.id] = true;
    router.post(route('polls.vote', poll.id), { option_id: choice[poll.id] }, {
        preserveScroll: true,
        onError: (e) => (error[poll.id] = e.option_id ?? 'Could not record your vote.'),
        onSuccess: () => (error[poll.id] = null),
        onFinish: () => (busy[poll.id] = false),
    });
}

const total = (p) => p.options.reduce((s, o) => s + (o.votes ?? 0), 0);
const pct = (p, o) => (total(p) ? Math.round(((o.votes ?? 0) / total(p)) * 100) : 0);
const stateStyle = { open: 'bg-green-100 text-green-800', closed: 'bg-gray-200 text-gray-700', upcoming: 'bg-blue-100 text-blue-800' };
</script>

<template>
    <Head title="Polls" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold text-gray-800">Community Polls</h2></template>

        <div class="mx-auto max-w-3xl space-y-4 p-6">
            <div v-if="!eligible" class="rounded border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">
                To vote, your barangay must first verify you as a resident. You can still read polls and see results.
            </div>

            <div v-for="p in polls" :key="p.id" class="rounded-lg bg-white p-5 shadow">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold">{{ p.title }}</h3>
                        <p class="text-xs text-gray-500">{{ p.scope }} · {{ p.opens_at }} to {{ p.closes_at }}</p>
                    </div>
                    <span :class="stateStyle[p.state]" class="rounded px-2 py-0.5 text-xs font-medium capitalize">{{ p.state }}</span>
                </div>
                <p v-if="p.description" class="mt-2 text-sm text-gray-700">{{ p.description }}</p>

                <!-- Results (only after the poll closes) -->
                <div v-if="p.state === 'closed'" class="mt-4 space-y-2">
                    <div v-for="o in p.options" :key="o.id">
                        <div class="flex justify-between text-sm"><span>{{ o.label }}</span><span>{{ o.votes }} ({{ pct(p, o) }}%)</span></div>
                        <div class="h-2 rounded bg-gray-100"><div class="h-2 rounded bg-indigo-500" :style="{ width: pct(p, o) + '%' }"></div></div>
                    </div>
                    <p class="text-xs text-gray-500">{{ total(p) }} votes in total</p>
                </div>

                <!-- Voting -->
                <div v-else-if="p.state === 'open'" class="mt-4">
                    <p v-if="p.has_voted" class="text-sm font-medium text-green-700">
                        Your vote is recorded. Results are shown when the poll closes.
                    </p>
                    <div v-else-if="eligible" class="space-y-2">
                        <label v-for="o in p.options" :key="o.id" class="flex items-center gap-2 text-sm">
                            <input type="radio" :name="'poll-' + p.id" :value="o.id" v-model="choice[p.id]" /> {{ o.label }}
                        </label>
                        <p v-if="error[p.id]" class="text-sm text-red-600">{{ error[p.id] }}</p>
                        <button @click="vote(p)" :disabled="!choice[p.id] || busy[p.id]"
                                class="rounded bg-indigo-600 px-4 py-2 text-sm text-white disabled:opacity-50">Submit vote</button>
                        <p class="text-xs text-gray-500">Your vote cannot be changed once submitted.</p>
                    </div>
                </div>
            </div>
            <p v-if="!polls.length" class="text-center text-gray-500">No polls are available for you right now.</p>
        </div>
    </AuthenticatedLayout>
</template>