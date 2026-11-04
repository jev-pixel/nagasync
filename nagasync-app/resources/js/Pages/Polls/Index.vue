<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { CalendarClock, Lock, MapPin, ShieldCheck, Vote } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

const props = defineProps({ eligible: Boolean, polls: Array });

const isAdmin = computed(() => usePage().props.auth.user.role === 'admin');

// ---- Voting state (per poll) ---------------------------------------------------------------
const choice = reactive({});
const error = reactive({});
const busy = reactive({});
const imageFailed = reactive({});

function vote(poll) {
    if (!choice[poll.id]) return;
    busy[poll.id] = true;
    error[poll.id] = null;
    router.post(route('polls.vote', poll.id), { option_id: choice[poll.id] }, {
        preserveScroll: true,
        onError: (e) => (error[poll.id] = e.option_id ?? 'Your vote was not recorded. Try again.'),
        onFinish: () => (busy[poll.id] = false),
    });
}

// ---- Results helpers -----------------------------------------------------------------------
const total = (p) => p.options.reduce((s, o) => s + (o.votes ?? 0), 0);
const pct = (p, o) => (total(p) ? Math.round(((o.votes ?? 0) / total(p)) * 100) : 0);
const topVotes = (p) => Math.max(0, ...p.options.map((o) => o.votes ?? 0));
const isLeader = (p, o) => topVotes(p) > 0 && (o.votes ?? 0) === topVotes(p);

// ---- Filter tabs ---------------------------------------------------------------------------
const count = (state) => props.polls.filter((p) => p.state === state).length;
const tabs = computed(() => [
    { key: 'open', label: 'Open', n: count('open') },
    { key: 'upcoming', label: 'Upcoming', n: count('upcoming') },
    { key: 'closed', label: 'Closed', n: count('closed') },
    { key: 'all', label: 'All', n: props.polls.length },
]);
const active = ref(count('open') ? 'open' : 'all');
const visible = computed(() => (active.value === 'all' ? props.polls : props.polls.filter((p) => p.state === active.value)));

const emptyText = {
    open: 'No polls are open right now. Check Upcoming, or come back soon.',
    upcoming: 'No polls are scheduled.',
    closed: 'No polls have closed yet.',
    all: 'No polls are available for you right now.',
};

const stateStyle = {
    open: 'bg-emerald-50 text-emerald-800 ring-emerald-200',
    upcoming: 'bg-sky-50 text-sky-800 ring-sky-200',
    closed: 'bg-slate-100 text-slate-700 ring-slate-200',
};
const stateLabel = { open: 'Open for voting', upcoming: 'Opens soon', closed: 'Closed' };
</script>

<template>
    <Head title="Community polls" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold text-gray-800">Community polls</h2></template>

        <div class="mx-auto max-w-3xl space-y-6 px-4 py-6 sm:px-6">
            <!-- Masthead -->
            <section class="relative overflow-hidden rounded-2xl bg-indigo-950 px-6 py-7 text-white">
                <div class="absolute inset-y-0 left-0 w-1.5 bg-amber-400" aria-hidden="true"></div>
                <div class="flex flex-wrap items-start justify-between gap-4 pl-2">
                    <div class="max-w-md">
                        <h1 class="font-serif text-2xl font-semibold leading-snug sm:text-3xl">Have your say in how Naga decides.</h1>
                        <p class="mt-2 text-sm leading-relaxed text-indigo-200">
                            One verified resident, one vote. Results appear when a poll closes.
                        </p>
                    </div>
                    <Link v-if="isAdmin" :href="route('admin.polls.index')"
                          class="inline-flex items-center gap-2 rounded-lg bg-amber-400 px-4 py-2 text-sm font-semibold text-indigo-950 hover:bg-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
                        <Vote class="h-4 w-4" /> Manage polls
                    </Link>
                </div>
                <p class="mt-5 flex items-center gap-2 pl-2 text-xs text-indigo-300">
                    <ShieldCheck class="h-4 w-4 shrink-0 text-amber-300" />
                    Ballots are anonymous. Your choice is stored separately from your name.
                </p>
            </section>

            <div v-if="!eligible && !isAdmin" class="flex gap-3 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
                <Lock class="mt-0.5 h-4 w-4 shrink-0" />
                <p>To vote, your barangay must first verify you as a resident. You can still read polls and see results.</p>
            </div>

            <!-- Filter tabs -->
            <nav class="flex gap-1 overflow-x-auto rounded-xl bg-slate-100 p-1" aria-label="Filter polls">
                <button v-for="t in tabs" :key="t.key" type="button" @click="active = t.key"
                        :aria-pressed="active === t.key"
                        class="flex-1 whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                        :class="active === t.key ? 'bg-white text-indigo-950 shadow-sm' : 'text-slate-600 hover:text-indigo-950'">
                    {{ t.label }} <span class="ml-1 text-xs text-slate-400">{{ t.n }}</span>
                </button>
            </nav>

            <!-- Polls -->
            <article v-for="p in visible" :key="p.id" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <img v-if="p.image_url && !imageFailed[p.id]" :src="p.image_url" :alt="'Image for the poll: ' + p.title"
                     loading="lazy" class="h-44 w-full object-cover sm:h-56" @error="imageFailed[p.id] = true" />

                <div class="p-5 sm:p-6">
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 font-medium ring-1" :class="stateStyle[p.state]">
                            {{ stateLabel[p.state] }}
                        </span>
                        <span class="inline-flex items-center gap-1 text-slate-500">
                            <MapPin class="h-3.5 w-3.5" /> {{ p.scope }}
                        </span>
                    </div>

                    <h3 class="mt-3 font-serif text-xl font-semibold leading-snug text-indigo-950">{{ p.title }}</h3>
                    <p v-if="p.description" class="mt-2 max-w-prose text-sm leading-relaxed text-slate-600">{{ p.description }}</p>

                    <p class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                        <span class="inline-flex items-center gap-1"><CalendarClock class="h-3.5 w-3.5" /> Opens {{ p.opens_at }}</span>
                        <span>Closes {{ p.closes_at }}</span>
                    </p>

                    <!-- Closed: results -->
                    <div v-if="p.state === 'closed'" class="mt-5 space-y-3 border-t border-slate-100 pt-5">
                        <div v-for="o in p.options" :key="o.id">
                            <div class="flex items-baseline justify-between gap-3 text-sm">
                                <span class="flex items-center gap-2" :class="isLeader(p, o) ? 'font-semibold text-indigo-950' : 'text-slate-700'">
                                    {{ o.label }}
                                    <span v-if="isLeader(p, o)" class="rounded bg-amber-100 px-1.5 py-0.5 text-xs font-medium text-amber-900">Most votes</span>
                                </span>
                                <span class="tabular-nums text-slate-500">{{ o.votes }} · {{ pct(p, o) }}%</span>
                            </div>
                            <div class="mt-1.5 h-2.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full" :class="isLeader(p, o) ? 'bg-indigo-700' : 'bg-indigo-300'" :style="{ width: pct(p, o) + '%' }"></div>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500">{{ total(p) }} {{ total(p) === 1 ? 'vote' : 'votes' }} cast</p>
                    </div>

                    <!-- Open: ballot -->
                    <div v-else-if="p.state === 'open'" class="mt-5 border-t border-slate-100 pt-5">
                        <p v-if="p.has_voted" class="flex items-center gap-2 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                            <ShieldCheck class="h-4 w-4" /> Your vote is recorded. Results appear when the poll closes.
                        </p>

                        <fieldset v-else-if="eligible" class="space-y-2">
                            <legend class="mb-2 text-sm font-medium text-slate-700">Choose one answer</legend>
                            <label v-for="o in p.options" :key="o.id"
                                   class="flex cursor-pointer items-center gap-3 rounded-xl border px-4 py-3 text-sm transition focus-within:ring-2 focus-within:ring-amber-400"
                                   :class="choice[p.id] === o.id ? 'border-indigo-600 bg-indigo-50 text-indigo-950' : 'border-slate-200 text-slate-700 hover:border-indigo-300'">
                                <input type="radio" class="sr-only" :name="'poll-' + p.id" :value="o.id" v-model="choice[p.id]" />
                                <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full border-2"
                                      :class="choice[p.id] === o.id ? 'border-indigo-600' : 'border-slate-300'" aria-hidden="true">
                                    <span v-if="choice[p.id] === o.id" class="h-2.5 w-2.5 rounded-full bg-indigo-600"></span>
                                </span>
                                {{ o.label }}
                            </label>

                            <p v-if="error[p.id]" class="text-sm text-red-600" role="alert">{{ error[p.id] }}</p>

                            <div class="flex flex-wrap items-center gap-3 pt-2">
                                <button type="button" @click="vote(p)" :disabled="!choice[p.id] || busy[p.id]"
                                        class="rounded-lg bg-indigo-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                    {{ busy[p.id] ? 'Recording…' : 'Cast vote' }}
                                </button>
                                <span class="text-xs text-slate-500">You cannot change your vote once cast.</span>
                            </div>
                        </fieldset>

                        <p v-else-if="isAdmin" class="text-sm text-slate-500">You are viewing this as an administrator. Voting is for verified residents.</p>
                        <p v-else class="flex items-center gap-2 text-sm text-slate-500"><Lock class="h-4 w-4" /> Verification is needed before you can vote.</p>
                    </div>

                    <!-- Upcoming -->
                    <p v-else class="mt-5 border-t border-slate-100 pt-5 text-sm text-slate-500">
                        Voting opens {{ p.opens_at }}.
                    </p>
                </div>
            </article>

            <div v-if="!visible.length" class="rounded-2xl border border-dashed border-slate-300 px-6 py-12 text-center text-sm text-slate-500">
                {{ emptyText[active] }}
            </div>
        </div>
    </AuthenticatedLayout>
</template>