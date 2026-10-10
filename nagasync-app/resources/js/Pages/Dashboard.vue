<script setup>
import Card from '@/Components/Card.vue';
import HeroCard from '@/Components/HeroCard.vue';
import Icon from '@/Components/Icon.vue';
import { useNavigation } from '@/Composables/useNavigation';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, defineProps } from 'vue';

defineProps({
    openPolls: { type: Array, default: () => [] },
    canVote: { type: Boolean, default: false },
});

const page = usePage();
const { groups } = useNavigation();

const firstName = computed(() => (page.props.auth.user.name || '').split(' ')[0]);
const greeting = computed(() => {
    const h = new Date().getHours();
    return h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening';
});

const services = computed(() => groups.value.find((g) => g.label === 'Services')?.items ?? []);
const workspace = computed(() => groups.value.filter((g) => g.label !== 'Services'));
</script>

<template>
    <Head title="Home" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Home</h2>
        </template>

        <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <HeroCard
                :title="`${greeting}, ${firstName}`"
                subtitle="Request documents, pay fees and reach responders from one place."
            >
                <Link
                    :href="route('documents.index')"
                    class="inline-flex items-center rounded-tile bg-white px-4 py-2.5 text-sm font-semibold text-brand-700 transition-transform active:scale-95"
                >
                    Request a document
                </Link>
                <span
                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium"
                    :class="canVote ? 'bg-white/15 text-white' : 'bg-accent-500/90 text-brand-900'"
                >
                    <Icon name="check" class="h-4 w-4" />
                    {{ canVote ? 'Verified resident' : 'Resident verification pending' }}
                </span>
            </HeroCard>

            <!-- Services: the one staggered entrance on this page. -->
            <section aria-label="Services">
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    <Link
                        v-for="(s, n) in services"
                        :key="s.route"
                        :href="route(s.route)"
                        class="flex flex-col gap-3 rounded-card bg-white p-4 shadow-card transition-transform active:scale-[0.98] motion-safe:animate-tile-in"
                        :style="{ animationDelay: n * 60 + 'ms' }"
                    >
                        <span
                            class="flex h-11 w-11 items-center justify-center rounded-tile"
                            :class="s.danger ? 'bg-red-50 text-red-600' : 'bg-brand-50 text-brand-600'"
                        >
                            <Icon :name="s.icon" class="h-6 w-6" />
                        </span>
                        <span>
                            <span class="block font-semibold text-gray-900">{{ s.label }}</span>
                            <span class="block text-xs text-gray-500">{{ s.text }}</span>
                        </span>
                    </Link>
                </div>
            </section>

            <Card v-if="openPolls.length">
                <h3 class="font-semibold text-gray-900">Polls open for your vote</h3>
                <ul class="mt-2 divide-y divide-gray-100 text-sm">
                    <li v-for="p in openPolls" :key="p.id" class="flex items-center justify-between gap-3 py-3">
                        <span class="min-w-0">
                            <span class="block truncate font-medium text-gray-800">{{ p.title }}</span>
                            <span class="block text-xs text-gray-500">Closes {{ p.closes_at }}</span>
                        </span>
                        <Link
                            :href="route('polls.index')"
                            class="shrink-0 rounded-full bg-brand-50 px-4 py-1.5 text-sm font-medium text-brand-700 transition-colors hover:bg-brand-100"
                        >
                            {{ canVote ? 'Vote' : 'View' }}
                        </Link>
                    </li>
                </ul>
                <p v-if="!canVote" class="mt-2 text-xs text-amber-700">
                    To vote, your barangay must first verify you as a resident.
                </p>
            </Card>

            <!-- Staff, responders, treasury and admins also see their tools here. -->
            <section v-for="g in workspace" :key="g.label" :aria-label="g.label">
                <h3 class="mb-2 text-sm font-semibold text-gray-700">{{ g.label }}</h3>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="i in g.items"
                        :key="i.route"
                        :href="route(i.route)"
                        class="flex items-center gap-3 rounded-card bg-white p-4 shadow-card transition-transform active:scale-[0.98]"
                    >
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-tile bg-brand-50 text-brand-600">
                            <Icon :name="i.icon" class="h-6 w-6" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold text-gray-900">{{ i.label }}</span>
                            <span class="block truncate text-xs text-gray-500">{{ i.text }}</span>
                        </span>
                        <Icon name="chevron" class="h-4 w-4 text-gray-300" />
                    </Link>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>