<template>
    <div class="mail1s-app">
        <aside class="mail1s-sidebar mail1s-sidebar--desktop mail1s-sidebar--sticky">
            <SidebarPanel :show-advanced="showAdvanced" @logout="handleLogout" />
        </aside>

        <el-drawer
            v-model="drawerOpen"
            direction="ltr"
            :with-header="false"
            size="240px"
            class="app-sidebar-drawer"
        >
            <aside class="mail1s-sidebar" style="width: 100%; height: 100%; border: none">
                <SidebarPanel
                    :show-advanced="showAdvanced"
                    @logout="handleLogout"
                    @navigate="drawerOpen = false"
                />
            </aside>
        </el-drawer>

        <div class="mail1s-main">
            <nav class="mail1s-sidebar--mobile-strip">
                <router-link
                    v-for="item in flatMenuItems"
                    :key="item.path"
                    :to="item.path"
                    class="mail1s-nav-item"
                    :class="{ 'is-active': activeMenu === item.path }"
                >
                    <span class="mail1s-nav-icon">
                        <el-icon><component :is="item.icon" /></el-icon>
                    </span>
                    <span class="mail1s-nav-label">{{ t(item.labelKey) }}</span>
                </router-link>
            </nav>

            <header class="mail1s-topbar">
                <div class="mail1s-topbar-left">
                    <button type="button" class="mail1s-icon-btn mail1s-burger" @click="drawerOpen = true">
                        <el-icon :size="18"><Menu /></el-icon>
                    </button>
                    <div>
                        <div class="mail1s-topbar-title">{{ currentPageTitle }}</div>
                        <div class="mail1s-topbar-sub">GPM Admin · v{{ authStore.serverVersion }}</div>
                    </div>
                </div>
                <div class="mail1s-topbar-actions">
                    <button
                        type="button"
                        class="mail1s-icon-btn"
                        :title="theme === 'dark' ? t('app.themeLight') : t('app.themeDark')"
                        @click="toggleTheme"
                    >
                        <el-icon :size="18">
                            <Moon v-if="theme !== 'dark'" />
                            <Sunny v-else />
                        </el-icon>
                    </button>

                    <el-dropdown trigger="click" @command="onLocaleChange">
                        <button type="button" class="mail1s-icon-btn">
                            <el-icon :size="18"><GlobeIcon /></el-icon>
                        </button>
                        <template #dropdown>
                            <el-dropdown-menu>
                                <el-dropdown-item
                                    v-for="opt in localeOptions"
                                    :key="opt.value"
                                    :command="opt.value"
                                    :class="{ 'is-active-locale': locale === opt.value }"
                                >
                                    {{ opt.label }}
                                </el-dropdown-item>
                            </el-dropdown-menu>
                        </template>
                    </el-dropdown>

                    <el-button type="primary" plain size="small" @click="switchToOldUi">
                        <el-icon style="margin-right: 4px"><Monitor /></el-icon>
                        <span class="app-header-switch-ui-label">{{ t('app.switchToOldUi') }}</span>
                    </el-button>
                </div>
            </header>

            <main class="app-main mail1s-page-grid">
                <router-view v-slot="{ Component }">
                    <transition name="el-fade-in-linear" mode="out-in">
                        <component :is="Component" />
                    </transition>
                </router-view>
            </main>
        </div>
    </div>
</template>

<script setup>
import { computed, defineComponent, h, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { ElMessage } from 'element-plus';
import SidebarPanel from '../components/SidebarPanel.vue';
import { useAuthStore } from '../stores/auth';
import { useTheme } from '../composables/useTheme';

const GlobeIcon = defineComponent({
    render: () =>
        h(
            'svg',
            {
                viewBox: '0 0 24 24',
                fill: 'none',
                stroke: 'currentColor',
                'stroke-width': '2',
                width: '1em',
                height: '1em',
            },
            [
                h('circle', { cx: '12', cy: '12', r: '10' }),
                h('path', { d: 'M2 12h20' }),
                h('path', {
                    d: 'M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z',
                }),
            ]
        ),
});

const { t, locale } = useI18n();
const route = useRoute();
const authStore = useAuthStore();
const { theme, toggleTheme } = useTheme();
const config = window.__APP_CONFIG__ || {};

const savedLocale = localStorage.getItem('admin_locale');
if (savedLocale) locale.value = savedLocale;

const showAdvanced = ref(false);

function handleAdvancedShortcut(e) {
    if (!(e.ctrlKey || e.metaKey) || !e.shiftKey) return;
    if (e.key !== 'A' && e.key !== 'a') return;
    e.preventDefault();
    showAdvanced.value = !showAdvanced.value;
    ElMessage({
        message: showAdvanced.value ? t('menu.sqlConsoleRevealed') : t('menu.sqlConsoleHidden'),
        type: showAdvanced.value ? 'warning' : 'info',
        duration: 1500,
    });
}
onMounted(() => window.addEventListener('keydown', handleAdvancedShortcut));
onBeforeUnmount(() => window.removeEventListener('keydown', handleAdvancedShortcut));

const drawerOpen = ref(false);
watch(() => route.path, () => (drawerOpen.value = false));

const flatMenuItems = computed(() => {
    const items = [
        { path: '/admin/app/system', icon: 'Setting', labelKey: 'menu.systemSettings' },
        { path: '/admin/app/users', icon: 'User', labelKey: 'menu.users' },
        { path: '/admin/app/groups', icon: 'Collection', labelKey: 'menu.groups' },
        { path: '/admin/app/profiles', icon: 'UserFilled', labelKey: 'menu.profiles' },
        { path: '/admin/app/proxies', icon: 'Connection', labelKey: 'menu.proxies' },
        { path: '/admin/app/logs', icon: 'Document', labelKey: 'menu.logs' },
        { path: '/admin/app/system-logs', icon: 'Tickets', labelKey: 'menu.systemLogs' },
    ];
    if (showAdvanced.value) {
        items.push({ path: '/admin/app/sql', icon: 'Operation', labelKey: 'menu.sqlConsole' });
    }
    return items;
});

const activeMenu = computed(() => route.path);

const localeOptions = [
    { value: 'vi', label: 'Tiếng Việt' },
    { value: 'en', label: 'English' },
    { value: 'zh', label: '中文' },
];

const currentPageTitle = computed(() => {
    const key = route.meta?.titleKey;
    return key ? t(key) : '';
});

function onLocaleChange(value) {
    localStorage.setItem('admin_locale', value);
    window.location.reload();
}

function switchToOldUi() {
    window.location.href = config.legacyAdminUrl || '/admin';
}

function handleLogout() {
    authStore.logout();
}
</script>

<style scoped>
.mail1s-burger {
    display: none;
}
@media (max-width: 768px) {
    .mail1s-burger {
        display: inline-flex;
    }
}
</style>
