<template>
    <div class="sidebar-panel">
        <div class="mail1s-sidebar-brand">
            <div class="mail1s-sidebar-brand-icon">G</div>
            <div>
                <div class="mail1s-sidebar-brand-title">{{ t('app.title') }}</div>
                <div class="mail1s-sidebar-brand-sub">v{{ authStore.serverVersion }}</div>
            </div>
        </div>

        <div class="mail1s-nav-scroll">
            <template v-for="group in groups" :key="group.key">
                <template v-if="itemsInGroup(group.key).length">
                    <p class="mail1s-nav-section">{{ t(group.titleKey) }}</p>
                    <router-link
                        v-for="item in itemsInGroup(group.key)"
                        :key="item.path"
                        :to="item.path"
                        class="mail1s-nav-item"
                        :class="{ 'is-active': route.path === item.path }"
                        @click="emit('navigate')"
                    >
                        <span class="mail1s-nav-icon">
                            <el-icon><component :is="item.icon" /></el-icon>
                        </span>
                        <span class="mail1s-nav-label">{{ t(item.labelKey) }}</span>
                        <span v-if="route.path === item.path" class="mail1s-nav-dot" />
                    </router-link>
                </template>
            </template>
        </div>

        <div class="mail1s-sidebar-footer">
            <div class="mail1s-sidebar-user">{{ authStore.displayName }}</div>
            <el-button size="small" style="width: 100%" @click="emit('logout')">
                <el-icon style="margin-right: 4px"><SwitchButton /></el-icon>
                {{ t('app.logout') }}
            </el-button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '../stores/auth';

const props = defineProps({
    showAdvanced: { type: Boolean, default: false },
});

const emit = defineEmits(['logout', 'navigate']);

const { t } = useI18n();
const route = useRoute();
const authStore = useAuthStore();

const groups = [
    { key: 'overview', titleKey: 'menu.groupOverview' },
    { key: 'management', titleKey: 'menu.groupManagement' },
    { key: 'system', titleKey: 'menu.groupSystem' },
];

const allItems = computed(() => {
    const items = [
        { path: '/admin/app/system', icon: 'Setting', labelKey: 'menu.systemSettings', group: 'overview' },
        { path: '/admin/app/users', icon: 'User', labelKey: 'menu.users', group: 'management' },
        { path: '/admin/app/groups', icon: 'Collection', labelKey: 'menu.groups', group: 'management' },
        { path: '/admin/app/profiles', icon: 'UserFilled', labelKey: 'menu.profiles', group: 'management' },
        { path: '/admin/app/proxies', icon: 'Connection', labelKey: 'menu.proxies', group: 'management' },
        { path: '/admin/app/logs', icon: 'Document', labelKey: 'menu.logs', group: 'system' },
        { path: '/admin/app/system-logs', icon: 'Tickets', labelKey: 'menu.systemLogs', group: 'system' },
    ];
    if (props.showAdvanced) {
        items.push({
            path: '/admin/app/sql',
            icon: 'Operation',
            labelKey: 'menu.sqlConsole',
            group: 'system',
        });
    }
    return items;
});

function itemsInGroup(key) {
    return allItems.value.filter((i) => i.group === key);
}
</script>

<style scoped>
.sidebar-panel {
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 0;
}
</style>
