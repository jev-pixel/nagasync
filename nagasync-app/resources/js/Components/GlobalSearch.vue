<script setup>
import { useNavigation } from '@/navigation';
import { router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';

defineProps({ placeholder: { type: String, default: 'Jump to…' } });

const { items } = useNavigation();
const q = ref('');
const open = ref(false);
const active = ref(0);

const results = computed(() => {
    const term = q.value.trim().toLowerCase();
    return term ? items.value.filter((i) => i.label.toLowerCase().includes(term)).slice(0, 6) : [];
});

function go(item) {
    if (!item) return;
    q.value = '';
    open.value = false;
    router.visit(route(item.route));
}

function onKey(e) {
    if (e.key === 'ArrowDown') { e.preventDefault(); active.value = Math.min(active.value + 1, results.value.length - 1); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); active.value = Math.max(active.value - 1, 0); }
    else if (e.key === 'Enter') { go(results.value[active.value]); }
    else if (e.key === 'Escape') { q.value = ''; open.value = false; }
}
</script>

<template>
    <div class="relative">
        <label for="global-search" class="sr-only">Jump to a page</label>
        <Search class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
        <input id="global-search" data-testid="search" v-model="q" type="search" role="combobox" autocomplete="off"
               :aria-expanded="open && results.length > 0" aria-controls="search-results" :placeholder="placeholder"
               @input="open = true; active = 0" @focus="open = true" @blur="open = false" @keydown="onKey"
               class="h-11 w-full rounded-xl border border-ns-mist bg-white pl-11 pr-4 text-sm transition placeholder:text-slate-400 hover:border-ns-violet/40 focus:border-ns-violet focus:outline-none focus:ring-2 focus:ring-ns-violet/30" />
        <ul v-if="open && results.length" id="search-results" role="listbox"
            class="absolute z-50 mt-2 w-full overflow-hidden rounded-xl border border-ns-mist bg-white py-1 shadow-xl">
            <li v-for="(r, i) in results" :key="r.key" role="option" :aria-selected="i === active"
                @mousedown.prevent="go(r)" @mouseenter="active = i"
                :class="['flex cursor-pointer items-center gap-2 px-4 py-2.5 text-sm', i === active ? 'bg-ns-mist' : '']">
                <component :is="r.icon" class="h-4 w-4" aria-hidden="true" /> {{ r.label }}
            </li>
        </ul>
    </div>
</template>
