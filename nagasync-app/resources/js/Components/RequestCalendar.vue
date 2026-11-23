<script setup>
// Compact week strip that expands to a month grid. Days that have requests get colored dots;
// choosing a day emits its key (YYYY-MM-DD, local time) so the page can filter "My requests".
// Place at: resources/js/Components/RequestCalendar.vue   (needs resources/js/utils/dateKey.js)
//
// Keyboard: arrows move by day/week, Home/End jump to the start/end of the week, PageUp/PageDown
// change month, Enter/Space selects. Only one day is in the tab order (roving tabindex).
import { fromKey, dayKey as keyOf } from '@/utils/dateKey';
import { CalendarDays, ChevronDown, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: null }, // selected day key or null
    // { 'YYYY-MM-DD': ['pending', 'approved', ...] }  request statuses submitted that day
    marks: { type: Object, default: () => ({}) },
    variant: { type: String, default: 'glass' }, // 'glass' (on the navy header) | 'light' (on a white card)
});
const emit = defineEmits(['update:modelValue']);

const WEEKDAYS = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
const longFmt = new Intl.DateTimeFormat('en-PH', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
const monthFmt = new Intl.DateTimeFormat('en-PH', { month: 'long', year: 'numeric' });
const shortMonth = new Intl.DateTimeFormat('en-PH', { month: 'short' });

// "Today" is refreshed so a tab left open past midnight does not highlight yesterday
const todayKey = ref(keyOf(new Date()));
const refreshToday = () => { todayKey.value = keyOf(new Date()); };
let timer;
onMounted(() => {
    timer = setInterval(refreshToday, 60_000);
    document.addEventListener('visibilitychange', refreshToday);
});
onBeforeUnmount(() => {
    clearInterval(timer);
    document.removeEventListener('visibilitychange', refreshToday);
});

const cursor = ref(fromKey(todayKey.value));
const expanded = ref(false);
const glass = computed(() => props.variant === 'glass');
const root = ref(null);
const focusKey = ref(props.modelValue ?? todayKey.value);

// Statuses collapse into four groups, listed in priority order (at most one dot per group)
const GROUPS = [
    { key: 'action', label: 'Needs your action', dot: 'bg-orange-500', statuses: ['needs_resubmission'] },
    { key: 'review', label: 'In review', dot: 'bg-amber-400', statuses: ['pending', 'processing'] },
    { key: 'done', label: 'Ready / claimed', dot: 'bg-emerald-500', statuses: ['approved', 'released'] },
    { key: 'closed', label: 'Not approved', dot: 'bg-red-500', statuses: ['rejected', 'revoked'] },
];
const groupsFor = (statuses = []) => GROUPS.filter((g) => statuses.some((s) => g.statuses.includes(s)));

const days = computed(() => {
    const c = cursor.value;
    let start;
    let length;

    if (expanded.value) {
        const first = new Date(c.getFullYear(), c.getMonth(), 1);
        const last = new Date(c.getFullYear(), c.getMonth() + 1, 0);
        start = new Date(first.getFullYear(), first.getMonth(), 1 - first.getDay());
        length = Math.ceil((first.getDay() + last.getDate()) / 7) * 7;
    } else {
        start = new Date(c.getFullYear(), c.getMonth(), c.getDate() - c.getDay());
        length = 7;
    }

    return Array.from({ length }, (_, i) => {
        const date = new Date(start.getFullYear(), start.getMonth(), start.getDate() + i);
        const key = keyOf(date);
        const statuses = props.marks[key] ?? [];
        const count = statuses.length;
        return {
            key,
            date,
            num: date.getDate(),
            inMonth: date.getMonth() === c.getMonth(),
            count,
            groups: groupsFor(statuses),
            label: `${longFmt.format(date)}, ${count ? `${count} ${count === 1 ? 'request' : 'requests'}` : 'no requests'}`,
        };
    });
});

// A week can straddle two months, so say so instead of naming only one
const title = computed(() => {
    if (expanded.value) return monthFmt.format(cursor.value);
    const first = days.value[0].date;
    const last = days.value[days.value.length - 1].date;
    if (first.getMonth() === last.getMonth()) return monthFmt.format(first);
    return first.getFullYear() === last.getFullYear()
        ? `${shortMonth.format(first)} – ${shortMonth.format(last)} ${last.getFullYear()}`
        : `${shortMonth.format(first)} ${first.getFullYear()} – ${shortMonth.format(last)} ${last.getFullYear()}`;
});

// Legend: only the statuses that actually appear on screen, so the dots never rely on color alone unexplained
const legend = computed(() => GROUPS.filter((g) => days.value.some((d) => d.groups.some((x) => x.key === g.key))));

const showToday = computed(() => !days.value.some((d) => d.key === todayKey.value));

// Exactly one day is tabbable: the focused one if visible, else the first day of the shown month/week
const tabKey = computed(() => (days.value.some((d) => d.key === focusKey.value)
    ? focusKey.value
    : (days.value.find((d) => d.inMonth) ?? days.value[0]).key));

function shift(dir) {
    const c = cursor.value;
    cursor.value = expanded.value
        ? new Date(c.getFullYear(), c.getMonth() + dir, 1)
        : new Date(c.getFullYear(), c.getMonth(), c.getDate() + dir * 7);
}

const goToday = () => {
    cursor.value = fromKey(todayKey.value);
    focusKey.value = todayKey.value;
};

function pick(key) {
    focusKey.value = key;
    emit('update:modelValue', props.modelValue === key ? null : key);
}

function moveFocus(date) {
    focusKey.value = keyOf(date);
    cursor.value = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    nextTick(() => root.value?.querySelector(`[data-day="${focusKey.value}"]`)?.focus());
}

// Same day-of-month in another month, clamped (Jan 31 + 1 month = Feb 28/29, not Mar 3)
function inMonths(date, n) {
    const lastDay = new Date(date.getFullYear(), date.getMonth() + n + 1, 0).getDate();
    return new Date(date.getFullYear(), date.getMonth() + n, Math.min(date.getDate(), lastDay));
}

function onKeydown(e, d) {
    const { date } = d;
    const step = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 }[e.key];
    let next = null;

    if (step) next = new Date(date.getFullYear(), date.getMonth(), date.getDate() + step);
    else if (e.key === 'Home') next = new Date(date.getFullYear(), date.getMonth(), date.getDate() - date.getDay());
    else if (e.key === 'End') next = new Date(date.getFullYear(), date.getMonth(), date.getDate() + (6 - date.getDay()));
    else if (e.key === 'PageUp') next = inMonths(date, -1);
    else if (e.key === 'PageDown') next = inMonths(date, 1);

    if (next) {
        e.preventDefault();
        moveFocus(next);
    }
}

// Read out the result of choosing a day (the page shows its own banner for sighted users)
const announce = computed(() => {
    if (!props.modelValue) return '';
    const n = props.marks[props.modelValue]?.length ?? 0;
    return `${longFmt.format(fromKey(props.modelValue))}: showing ${n} ${n === 1 ? 'request' : 'requests'}`;
});

const ghost = computed(() => (glass.value ? 'bg-white/10 hover:bg-white/20' : 'bg-[#F1E8F6] hover:bg-[#E8DAF1]'));
const dayClass = (d) => {
    const selected = props.modelValue === d.key;
    return [
        selected
            ? (glass.value ? 'bg-white text-[#3D2470]' : 'bg-[#3D2470] text-white')
            : (glass.value ? 'hover:bg-white/15' : 'hover:bg-[#F1E8F6]'),
        !selected && d.key === todayKey.value ? (glass.value ? 'ring-1 ring-white/70' : 'ring-1 ring-[#3D2470]/50') : '',
        expanded.value && !d.inMonth && !selected ? 'opacity-40' : '',
    ];
};
const ctl = 'flex h-9 w-9 items-center justify-center rounded-full transition active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#F04A2B]';
</script>

<template>
    <div ref="root" role="group" aria-label="Request history calendar"
         :class="glass ? 'text-white' : 'rounded-2xl bg-white p-3 text-[#3D2470] ring-1 ring-black/5'">
        <div class="flex items-center justify-between gap-2">
            <div class="flex min-w-0 items-center gap-1.5 text-sm font-bold">
                <CalendarDays class="h-4 w-4 shrink-0" aria-hidden="true" />
                <span class="truncate">{{ title }}</span>
            </div>
            <div class="flex shrink-0 items-center gap-1">
                <button v-if="showToday" type="button" @click="goToday"
                        class="h-9 rounded-full px-3 text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#F04A2B]" :class="ghost">Today</button>
                <button type="button" :aria-label="expanded ? 'Previous month' : 'Previous week'" @click="shift(-1)" :class="[ctl, ghost]">
                    <ChevronLeft class="h-4 w-4" aria-hidden="true" />
                </button>
                <button type="button" :aria-label="expanded ? 'Next month' : 'Next week'" @click="shift(1)" :class="[ctl, ghost]">
                    <ChevronRight class="h-4 w-4" aria-hidden="true" />
                </button>
                <button type="button" :aria-label="expanded ? 'Show week' : 'Show month'" :aria-expanded="expanded"
                        @click="expanded = !expanded" :class="[ctl, ghost]">
                    <ChevronDown class="h-4 w-4 transition-transform motion-reduce:transition-none" :class="expanded ? 'rotate-180' : ''" aria-hidden="true" />
                </button>
            </div>
        </div>

        <div class="mt-1.5 grid grid-cols-7 text-center text-[10px] font-semibold uppercase"
             :class="glass ? 'text-white/70' : 'text-[#3D2470]/60'" aria-hidden="true">
            <span v-for="(w, i) in WEEKDAYS" :key="i">{{ w }}</span>
        </div>

        <!-- 44px tall touch targets; width shrinks on very narrow phones so seven columns always fit -->
        <div class="mt-0.5 grid grid-cols-7 gap-y-0.5">
            <button v-for="d in days" :key="d.key" type="button" :data-day="d.key"
                    :aria-pressed="modelValue === d.key" :aria-label="d.label"
                    :tabindex="d.key === tabKey ? 0 : -1"
                    @click="pick(d.key)" @keydown="onKeydown($event, d)"
                    class="relative mx-auto flex h-11 w-full max-w-[2.75rem] items-center justify-center rounded-2xl text-sm font-semibold transition active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#F04A2B] motion-reduce:transition-none"
                    :class="dayClass(d)">
                {{ d.num }}
                <span v-if="d.groups.length" class="absolute bottom-1 flex gap-0.5" aria-hidden="true">
                    <span v-for="g in d.groups" :key="g.key" class="h-1.5 w-1.5 rounded-full ring-1"
                          :class="[g.dot, modelValue === d.key ? (glass ? 'ring-white' : 'ring-[#3D2470]') : (glass ? 'ring-[#2a1a52]/60' : 'ring-white')]"></span>
                </span>
            </button>
        </div>

        <ul v-if="legend.length" class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-[11px]" :class="glass ? 'text-white/80' : 'text-[#3D2470]/70'">
            <li v-for="g in legend" :key="g.key" class="flex items-center gap-1">
                <span class="h-1.5 w-1.5 rounded-full" :class="g.dot" aria-hidden="true"></span>{{ g.label }}
            </li>
        </ul>

        <p class="sr-only" role="status" aria-live="polite">{{ announce }}</p>
    </div>
</template>