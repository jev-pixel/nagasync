<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({ barangays: { type: Array, default: () => [] } });

const form = useForm({
    name: '', email: '', phone: '', barangay_id: '',
    privacy_consent: false, password: '', password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="name" value="Full name" />
                <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus autocomplete="name" />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Email" />
                <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required autocomplete="username" />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="phone" value="Mobile number" />
                <TextInput id="phone" type="tel" class="mt-1 block w-full" v-model="form.phone" required placeholder="0917 123 4567" autocomplete="tel" />
                <InputError class="mt-2" :message="form.errors.phone" />
            </div>

            <div class="mt-4">
                <InputLabel for="barangay_id" value="Barangay" />
                <select id="barangay_id" v-model="form.barangay_id" required
                        class="mt-1 block w-full rounded-xl border-ns-mist shadow-sm focus:border-ns-violet focus:ring-ns-violet">
                    <option value="" disabled>Select your barangay</option>
                    <option v-for="b in barangays" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.barangay_id" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />
                <TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" required autocomplete="new-password" />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel for="password_confirmation" value="Confirm password" />
                <TextInput id="password_confirmation" type="password" class="mt-1 block w-full" v-model="form.password_confirmation" required autocomplete="new-password" />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="mt-4">
                <label class="flex items-start gap-2 text-sm text-slate-600">
                    <input type="checkbox" v-model="form.privacy_consent"
                           class="mt-0.5 rounded border-slate-300 text-ns-violet shadow-sm focus:ring-ns-violet" />
                    <span>
                        I have read and agree to the
                        <a :href="route('privacy')" target="_blank" class="text-ns-violet underline">Privacy Notice</a>.
                    </span>
                </label>
                <InputError class="mt-2" :message="form.errors.privacy_consent" />
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-end gap-3">
                <Link :href="route('login')"
                      class="rounded-md text-sm text-slate-600 underline hover:text-ns-ink focus:outline-none focus:ring-2 focus:ring-ns-violet focus:ring-offset-2">
                    Already registered?
                </Link>

                <PrimaryButton class="sm:ms-1" :class="{ 'opacity-50': form.processing }" :disabled="form.processing">
                    Register
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>