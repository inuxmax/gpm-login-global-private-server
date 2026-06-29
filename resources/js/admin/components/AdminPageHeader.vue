<template>
    <header class="admin-page-header" :class="`admin-page-header--${tone}`">
        <div class="admin-page-header__main">
            <span class="admin-page-header__icon">
                <el-icon><component :is="icon" /></el-icon>
            </span>
            <div class="admin-page-header__text">
                <h2 class="admin-page-header__title">{{ title }}</h2>
                <p v-if="subtitle" class="admin-page-header__subtitle">{{ subtitle }}</p>
            </div>
        </div>
        <div v-if="$slots.actions" class="admin-page-header__actions">
            <slot name="actions" />
        </div>
    </header>
</template>

<script setup>
defineProps({
    icon: { type: String, required: true },
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    tone: {
        type: String,
        default: 'brand',
        validator: (v) => ['brand', 'sky', 'violet', 'emerald', 'amber', 'rose'].includes(v),
    },
});
</script>
