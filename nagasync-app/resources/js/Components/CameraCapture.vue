<script setup>
/**
 * Live camera capture for a requirement (e.g. the 1x1 photo). There is no file picker:
 * the resident takes the photo in the browser, cropped to a square and sent as a JPEG.
 *
 *   <CameraCapture :file="form.files[r.id]" @captured="(f) => ..." @cleared="() => ..." />
 *
 * `file` is the parent's current photo (so the preview survives a re-render).
 * With `allow-fallback`, a resident whose camera is blocked or missing may pick a photo instead.
 * Browsers only allow the camera on https:// or localhost.
 */
import { Camera, RefreshCw, Upload } from 'lucide-vue-next';
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    file: { type: [File, null], default: null },
    allowFallback: { type: Boolean, default: false },
    facing: { type: String, default: 'user' }, // 'user' = front camera, 'environment' = back camera
    busy: { type: Boolean, default: false },
});
const emit = defineEmits(['captured', 'cleared']);

const video = ref(null);
const stream = ref(null);
const live = ref(false);
const starting = ref(false);
const error = ref('');
const previewUrl = ref(null);

// Preview whatever file the parent holds
watch(() => props.file, (f) => {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = f ? URL.createObjectURL(f) : null;
}, { immediate: true });

function stop() {
    stream.value?.getTracks().forEach((t) => t.stop());
    stream.value = null;
    live.value = false;
}
onBeforeUnmount(() => {
    stop();
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
});

async function start() {
    error.value = '';
    if (!navigator.mediaDevices?.getUserMedia) {
        error.value = window.isSecureContext ? 'This browser cannot open the camera.' : 'The camera needs a secure (https) connection.';
        return;
    }
    starting.value = true;
    try {
        stream.value = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: props.facing, width: { ideal: 1280 }, height: { ideal: 1280 } },
            audio: false,
        });
        live.value = true;
        await nextTick();
        video.value.srcObject = stream.value;
        await video.value.play();
    } catch (e) {
        stop();
        error.value = e?.name === 'NotAllowedError'
            ? 'Camera permission was denied. Allow it in your browser settings, then try again.'
            : 'No camera was found on this device.';
    } finally {
        starting.value = false;
    }
}

function snap() {
    const v = video.value;
    if (!v?.videoWidth) return;
    const side = Math.min(v.videoWidth, v.videoHeight); // centre square: it is a 1x1 photo
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = Math.min(side, 800);
    canvas.getContext('2d').drawImage(v, (v.videoWidth - side) / 2, (v.videoHeight - side) / 2, side, side, 0, 0, canvas.width, canvas.height);
    canvas.toBlob((blob) => {
        if (!blob) { error.value = 'Could not take the photo. Please try again.'; return; }
        stop();
        emit('captured', new File([blob], `photo-${Date.now()}.jpg`, { type: 'image/jpeg' }));
    }, 'image/jpeg', 0.9);
}

function retake() {
    emit('cleared');
    start();
}

function pick(e) {
    const f = e.target.files[0];
    if (f) emit('captured', f);
    e.target.value = '';
}
</script>

<template>
    <div class="space-y-2">
        <!-- Live viewfinder -->
        <div v-if="live" class="space-y-2">
            <div class="relative mx-auto aspect-square w-full max-w-xs overflow-hidden rounded-2xl bg-black">
                <video ref="video" playsinline muted class="h-full w-full -scale-x-100 object-cover"></video>
            </div>
            <div class="flex justify-center gap-2">
                <button type="button" @click="snap"
                        class="inline-flex items-center gap-2 rounded-xl bg-[#F04A2B] px-4 py-2 text-sm font-bold text-white hover:bg-[#D93C1E]">
                    <Camera class="h-4 w-4" /> Take photo
                </button>
                <button type="button" @click="stop" class="rounded-xl px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100">Cancel</button>
            </div>
        </div>

        <!-- Captured photo -->
        <div v-else-if="previewUrl" class="flex items-center gap-3">
            <img :src="previewUrl" alt="Your photo" class="h-24 w-24 rounded-xl object-cover ring-1 ring-gray-200" />
            <button type="button" :disabled="busy" @click="retake"
                    class="inline-flex items-center gap-2 rounded-xl bg-[#F1E8F6] px-3 py-2 text-sm font-semibold text-[#3D2470] hover:bg-[#E8DAF1] disabled:opacity-50">
                <RefreshCw class="h-4 w-4" /> {{ busy ? 'Uploading…' : 'Retake' }}
            </button>
        </div>

        <!-- Not started -->
        <button v-else type="button" :disabled="starting || busy" @click="start"
                class="inline-flex items-center gap-2 rounded-xl bg-[#F1E8F6] px-3 py-2 text-sm font-semibold text-[#3D2470] hover:bg-[#E8DAF1] disabled:opacity-50">
            <Camera class="h-4 w-4" /> {{ starting ? 'Opening camera…' : busy ? 'Uploading…' : 'Open camera' }}
        </button>

        <p v-if="error" role="alert" class="text-sm text-red-600">{{ error }}</p>

        <label v-if="allowFallback && error" class="inline-flex cursor-pointer items-center gap-2 text-sm font-semibold text-[#3D2470] underline">
            <Upload class="h-4 w-4" /> Upload a photo instead
            <input type="file" accept="image/jpeg,image/png" class="sr-only" @change="pick" />
        </label>
    </div>
</template>
