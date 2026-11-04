<script setup>
import { Landmark } from 'lucide-vue-next';
</script>

<template>
    <!-- Official-site strip shown at the top of every page: the "centralized governance" signature -->
    <div class="bg-ns-ink text-[11px] text-white/80">
        <div class="mx-auto flex max-w-7xl items-center gap-2 px-4 py-1.5 sm:px-6">
            <Landmark class="h-3.5 w-3.5 shrink-0 text-ns-orange" aria-hidden="true" />
            <span class="font-semibold tracking-wide">Republic of the Philippines · City of Naga</span>
            <span class="ml-auto hidden sm:inline">Centralized Governance Portal</span>
        </div>
        <div class="flex h-0.5" aria-hidden="true">
            <span class="flex-1 bg-ns-orange"></span><span class="flex-1 bg-ns-teal"></span><span class="flex-1 bg-ns-violet"></span>
        </div>
    </div>
</template>
