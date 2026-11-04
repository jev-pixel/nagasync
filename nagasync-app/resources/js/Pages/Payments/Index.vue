<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle2, Clock, CreditCard, Receipt, RefreshCw, ShieldCheck, Wallet, XCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({ types: Array, fee: Number, payments: Array });

const page = usePage();
// Shown after returning from checkout (needs `flash` shared in HandleInertiaRequests)
const flash = computed(() => page.props.flash ?? {});

const form = useForm({ payment_type: '' });
const php = (c) => '₱' + (c / 100).toLocaleString('en-PH', { minimumFractionDigits: 2 });
const selected = computed(() => props.types.find((t) => t.key === form.payment_type));
const total = computed(() => (selected.value ? selected.value.amount + props.fee : 0));

const submit = () => form.post(route('payments.store'));

// Which history row is busy, so only that button shows "Opening…"
const busy = ref(null);
const track = (p) => ({
    preserveScroll: true,
    onStart: () => (busy.value = p.id),
    onFinish: () => (busy.value = null),
});

// Finish a payment that was started but not completed
const resume = (p) => router.post(route('payments.resume', p.reference_no), {}, track(p));

// Start a fresh payment of the same type (amount is always recalculated on the server)
const payAgain = (p) => router.post(route('payments.store'), { payment_type: p.payment_type }, track(p));

// Status tone + a plain-language explanation of what it means for the resident
const statusInfo = {
    pending: { tone: 'bg-amber-100 text-amber-800', icon: Clock, text: 'Payment was started but not completed yet.' },
    paid: { tone: 'bg-emerald-100 text-emerald-800', icon: CheckCircle2, text: 'Payment received. Keep the reference number as your receipt.' },
    failed: { tone: 'bg-red-100 text-red-800', icon: XCircle, text: 'The payment did not go through. You can try again.' },
    expired: { tone: 'bg-gray-200 text-gray-700', icon: Clock, text: 'This payment session ended. You can start the payment again.' },
    refunded: { tone: 'bg-sky-100 text-sky-800', icon: Receipt, text: 'This payment was refunded.' },
};
</script>

<template>
    <Head title="Payments" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold text-gray-800">Municipal Payments</h2></template>

        <div class="mx-auto max-w-4xl space-y-8 p-4 sm:p-6">
            <!-- Result messages -->
            <div v-if="flash.success" role="status" class="flex items-start gap-3 rounded-2xl bg-emerald-50 p-4 text-emerald-800 ring-1 ring-emerald-200">
                <CheckCircle2 class="mt-0.5 h-5 w-5 shrink-0" />
                <p class="font-semibold">{{ flash.success }}</p>
            </div>
            <div v-if="flash.error" role="alert" class="flex items-start gap-3 rounded-2xl bg-red-50 p-4 text-red-800 ring-1 ring-red-200">
                <AlertCircle class="mt-0.5 h-5 w-5 shrink-0" />
                <p class="font-semibold">{{ flash.error }}</p>
            </div>

            <!-- How it works -->
            <section class="rounded-3xl bg-[#F1E8F6] p-5 sm:p-6">
                <h3 class="text-lg font-extrabold text-[#3D2470]">How to pay a fee</h3>
                <ol class="mt-4 grid gap-4 sm:grid-cols-3">
                    <li v-for="(s, i) in [
                        { t: 'Choose', d: 'Pick what you are paying for.' },
                        { t: 'Review', d: 'Check the amount and total.' },
                        { t: 'Pay', d: 'Continue to the payment page. Your receipt appears below.' },
                    ]" :key="s.t" class="flex items-start gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#3D2470] text-sm font-bold text-white">{{ i + 1 }}</span>
                        <div>
                            <p class="font-bold text-[#3D2470]">{{ s.t }}</p>
                            <p class="text-sm text-[#1B1340]/70">{{ s.d }}</p>
                        </div>
                    </li>
                </ol>
            </section>

            <!-- Pay form -->
            <form @submit.prevent="submit" class="space-y-6 rounded-3xl bg-white p-5 shadow-sm ring-1 ring-black/5 sm:p-6">
                <div>
                    <h3 class="text-lg font-extrabold text-[#3D2470]">What are you paying for?</h3>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2" role="radiogroup" aria-label="Payment type">
                        <button v-for="t in types" :key="t.key" type="button" role="radio" :aria-checked="form.payment_type === t.key"
                                @click="form.payment_type = t.key"
                                class="flex items-center justify-between gap-3 rounded-2xl p-4 text-left ring-1 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#F04A2B]"
                                :class="form.payment_type === t.key ? 'bg-[#F1E8F6] ring-2 ring-[#3D2470]' : 'bg-white ring-gray-200 hover:ring-[#3D2470]/40'">
                            <span class="flex items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#F1E8F6] text-[#3D2470]">
                                    <Wallet class="h-5 w-5" />
                                </span>
                                <span class="font-bold text-[#1B1340]">{{ t.label }}</span>
                            </span>
                            <span class="shrink-0 font-bold text-[#3D2470]">{{ php(t.amount) }}</span>
                        </button>
                    </div>
                    <p v-if="form.errors.payment_type" class="mt-2 text-sm text-red-600">{{ form.errors.payment_type }}</p>
                </div>

                <!-- Summary -->
                <div v-if="selected" class="rounded-2xl bg-gray-50 p-4 ring-1 ring-gray-200">
                    <p class="font-bold text-[#3D2470]">Your payment summary</p>
                    <dl class="mt-3 space-y-1.5 text-sm text-[#1B1340]">
                        <div class="flex justify-between"><dt>{{ selected.label }}</dt><dd>{{ php(selected.amount) }}</dd></div>
                        <div class="flex justify-between"><dt>Convenience fee <span class="text-gray-500">(online payment)</span></dt><dd>{{ php(fee) }}</dd></div>
                        <div class="flex justify-between border-t pt-2 text-base font-extrabold"><dt>Total to pay</dt><dd>{{ php(total) }}</dd></div>
                    </dl>
                </div>
                <p v-else class="rounded-2xl bg-gray-50 p-4 text-sm text-gray-500">Choose a payment above to see the total.</p>

                <div class="flex flex-wrap items-center gap-4 border-t pt-5">
                    <button :disabled="form.processing || !form.payment_type"
                            class="inline-flex items-center gap-2 rounded-2xl bg-[#F04A2B] px-6 py-3 font-bold text-white shadow transition hover:bg-[#D93C1E] disabled:cursor-not-allowed disabled:opacity-40">
                        <CreditCard class="h-4 w-4" />
                        {{ form.processing ? 'Please wait…' : selected ? `Pay ${php(total)}` : 'Proceed to payment' }}
                    </button>
                    <p class="flex items-center gap-1.5 text-sm text-gray-500">
                        <ShieldCheck class="h-4 w-4" /> You will be taken to the payment page to finish.
                    </p>
                </div>
            </form>

            <!-- History -->
            <section>
                <h3 class="mb-3 text-lg font-extrabold text-[#3D2470]">My payments</h3>

                <div v-if="!payments.length" class="rounded-3xl border-2 border-dashed border-[#3D2470]/20 p-10 text-center">
                    <Receipt class="mx-auto h-8 w-8 text-[#3D2470]/50" />
                    <p class="mt-3 font-semibold text-[#3D2470]">No payments yet</p>
                    <p class="text-sm text-gray-500">Your receipts will show up here after you pay.</p>
                </div>

                <div v-else class="space-y-4">
                    <article v-for="p in payments" :key="p.id" class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-lg font-bold text-[#1B1340]">{{ p.label }}</p>
                                <p class="mt-1 text-xs text-gray-500">
                                    Reference no. <span class="select-all rounded bg-gray-100 px-1.5 py-0.5 font-mono text-gray-700">{{ p.reference_no }}</span>
                                </p>
                                <p v-if="p.paid_at" class="mt-1 text-xs text-gray-500">Paid {{ p.paid_at }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-lg font-extrabold text-[#1B1340]">{{ php(p.total) }}</p>
                                <span :class="statusInfo[p.status]?.tone" class="mt-1 inline-block rounded-full px-3 py-1 text-xs font-bold capitalize">{{ p.status }}</span>
                            </div>
                        </div>

                        <p class="mt-3 flex items-start gap-2 text-sm text-[#1B1340]/80">
                            <component :is="statusInfo[p.status].icon" v-if="statusInfo[p.status]" class="mt-0.5 h-4 w-4 shrink-0" />
                            {{ statusInfo[p.status]?.text }}
                        </p>

                        <!-- Finish an unfinished payment -->
                        <button v-if="p.status === 'pending'" type="button" @click="resume(p)" :disabled="busy === p.id"
                                class="mt-4 inline-flex items-center gap-2 rounded-2xl bg-[#F04A2B] px-5 py-2.5 text-sm font-bold text-white shadow transition hover:bg-[#D93C1E] disabled:cursor-not-allowed disabled:opacity-50">
                            <CreditCard class="h-4 w-4" />
                            {{ busy === p.id ? 'Opening…' : `Continue payment (${php(p.total)})` }}
                        </button>

                        <!-- Start over after a failed or expired payment -->
                        <button v-else-if="['failed', 'expired'].includes(p.status)" type="button" @click="payAgain(p)" :disabled="busy === p.id"
                                class="mt-4 inline-flex items-center gap-2 rounded-2xl bg-white px-5 py-2.5 text-sm font-bold text-[#3D2470] ring-1 ring-[#3D2470]/30 transition hover:bg-[#F1E8F6] disabled:cursor-not-allowed disabled:opacity-50">
                            <RefreshCw class="h-4 w-4" />
                            {{ busy === p.id ? 'Opening…' : 'Pay again' }}
                        </button>
                    </article>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>