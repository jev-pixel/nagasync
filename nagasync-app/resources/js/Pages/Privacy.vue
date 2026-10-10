<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { defineProps, } from 'vue';

defineProps({ version: String });
</script>

<template>
    <Head title="Privacy Notice" />
    <div class="min-h-screen bg-gray-100 p-4">
        <article class="mx-auto max-w-2xl space-y-5 rounded-lg bg-white p-6 text-sm leading-relaxed text-gray-700 shadow">
            <h1 class="text-2xl font-bold text-gray-900">Privacy Notice</h1>
            <p class="rounded bg-amber-50 p-3 text-xs text-amber-900">
                DRAFT TEMPLATE (version {{ version }}). Items in [brackets] must be completed, and the whole notice
                reviewed by the LGU's Data Protection Officer, before any real use.
            </p>

            <section>
                <h2 class="font-semibold text-gray-900">Who we are</h2>
                <p>NagaSync is operated for [LGU name]. Questions go to our Data Protection Officer: [name, email, phone].</p>
            </section>
            <section>
                <h2 class="font-semibold text-gray-900">What we collect, and why</h2>
                <ul class="list-disc space-y-1 ps-5">
                    <li><strong>Account details</strong> (name, email, mobile number, barangay): to identify you, send SMS about your requests and payments, and let responders contact you.</li>
                    <li><strong>Document requests</strong>: to process barangay documents. A public verification page shows only your first name and initials.</li>
                    <li><strong>Payments</strong>: reference numbers and amounts, to record municipal fees. [Payment provider] processes the payment itself.</li>
                    <li><strong>Emergency alerts</strong>: your precise location when you press the alert button, to direct responders to you.</li>
                    <li><strong>Polls</strong>: that you voted (to prevent double voting), stored separately from what you chose.</li>
                </ul>
            </section>
            <section>
                <h2 class="font-semibold text-gray-900">Who can see it</h2>
                <p>Barangay staff see requests in their barangay. Treasury sees payments. Responders see emergency alerts. Administrators see aggregate statistics. [Add any other recipients or processors.]</p>
            </section>
            <section>
                <h2 class="font-semibold text-gray-900">How long we keep it</h2>
                <p>[Retention periods, to be set by the LGU. Example: emergency locations removed X days after an alert is closed.]</p>
            </section>
            <section>
                <h2 class="font-semibold text-gray-900">Your rights</h2>
                <p>You may ask to access, correct, or object to the processing of your personal data, request its blocking or removal where the law allows, and complain to the National Privacy Commission. Contact the Data Protection Officer above.</p>
            </section>

            <Link :href="route('login')" class="text-indigo-600 underline">Back</Link>
        </article>
    </div>
</template>