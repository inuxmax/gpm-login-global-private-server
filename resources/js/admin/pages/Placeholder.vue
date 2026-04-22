<template>
    <div class="page-card" style="text-align: center; padding: 60px 20px">
        <el-icon :size="48" color="#9ca3af"><Loading /></el-icon>
        <h3 style="margin-top: 16px; color: #374151">{{ title }}</h3>
        <p style="color: #6b7280">
            {{ phaseMsg }}
        </p>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';

const route = useRoute();
const { t } = useI18n();

const title = computed(() => (route.meta?.titleKey ? t(route.meta.titleKey) : ''));
const phaseMsg = computed(() => {
    const phase = route.meta?.phase;
    return phase ? `Coming in Phase ${phase}` : '';
});
</script>
