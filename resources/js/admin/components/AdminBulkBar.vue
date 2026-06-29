<template>
    <div class="admin-bulk-bar">
        <span class="admin-bulk-bar__count">{{ countLabel }}</span>
        <div class="admin-bulk-bar__actions">
            <slot />
        </div>
    </div>
</template>

<script setup>
defineProps({
    countLabel: { type: String, required: true },
});
</script>
