<template>
    <el-container class="app-layout">
        <el-aside class="app-sidebar">
            <div class="app-brand">
                <div class="app-brand-icon">G</div>
                <div style="flex: 1">
                    <div>{{ t('app.title') }}</div>
                    <div class="app-version-chip" style="margin-top: 2px; display: inline-block">
                        v{{ authStore.serverVersion }}
                    </div>
                </div>
            </div>

            <el-menu
                :default-active="activeMenu"
                router
                class="app-menu"
                background-color="transparent"
                text-color="rgba(255,255,255,0.85)"
                active-text-color="#ffffff"
            >
                <el-menu-item index="/admin/app/system">
                    <el-icon><Setting /></el-icon>
                    <span>{{ t('menu.systemSettings') }}</span>
                </el-menu-item>
                <el-menu-item index="/admin/app/users">
                    <el-icon><User /></el-icon>
                    <span>{{ t('menu.users') }}</span>
                </el-menu-item>
                <el-menu-item index="/admin/app/groups">
                    <el-icon><Collection /></el-icon>
                    <span>{{ t('menu.groups') }}</span>
                </el-menu-item>
                <el-menu-item index="/admin/app/profiles">
                    <el-icon><UserFilled /></el-icon>
                    <span>{{ t('menu.profiles') }}</span>
                </el-menu-item>
                <el-menu-item index="/admin/app/proxies">
                    <el-icon><Connection /></el-icon>
                    <span>{{ t('menu.proxies') }}</span>
                </el-menu-item>
                <el-menu-item index="/admin/app/logs">
                    <el-icon><Document /></el-icon>
                    <span>{{ t('menu.logs') }}</span>
                </el-menu-item>
                <el-menu-item index="/admin/app/system-logs">
                    <el-icon><Tickets /></el-icon>
                    <span>{{ t('menu.systemLogs') }}</span>
                </el-menu-item>
                <el-menu-item v-if="showAdvanced" index="/admin/app/sql">
                    <el-icon><Operation /></el-icon>
                    <span>{{ t('menu.sqlConsole') }}</span>
                </el-menu-item>
            </el-menu>

            <div style="flex: 1"></div>

            <div style="padding: 12px 16px; border-top: 1px solid rgba(255, 255, 255, 0.08)">
                <div style="font-size: 12px; opacity: 0.75; margin-bottom: 8px">
                    {{ authStore.displayName }}
                </div>
                <el-button size="small" style="width: 100%" @click="handleLogout">
                    <el-icon style="margin-right: 4px"><SwitchButton /></el-icon>
                    {{ t('app.logout') }}
                </el-button>
            </div>
        </el-aside>

        <el-container>
            <el-header class="app-header">
                <div class="app-header-title">
                    {{ currentPageTitle }}
                </div>
                <div style="display: flex; align-items: center; gap: 10px">
                    <el-select
                        v-model="locale"
                        size="small"
                        style="width: 130px"
                        @change="onLocaleChange"
                    >
                        <template #prefix>
                            <el-icon><Promotion /></el-icon>
                        </template>
                        <el-option label="Tiếng Việt" value="vi" />
                        <el-option label="English" value="en" />
                        <el-option label="中文" value="zh" />
                    </el-select>

                    <el-button type="primary" plain size="small" @click="switchToOldUi">
                        <el-icon style="margin-right: 4px"><Monitor /></el-icon>
                        {{ t('app.switchToOldUi') }}
                    </el-button>
                </div>
            </el-header>

            <el-main class="app-main">
                <router-view v-slot="{ Component }">
                    <transition name="el-fade-in-linear" mode="out-in">
                        <component :is="Component" />
                    </transition>
                </router-view>
            </el-main>
        </el-container>
    </el-container>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { ElMessage } from 'element-plus';
import { useAuthStore } from '../stores/auth';

const { t, locale } = useI18n();
const route = useRoute();
const authStore = useAuthStore();

const config = window.__APP_CONFIG__ || {};

const savedLocale = localStorage.getItem('admin_locale');
if (savedLocale) locale.value = savedLocale;

// SQL Console is dangerous — hidden by default, revealed only when the
// admin explicitly presses Ctrl/Cmd+Shift+A. Reset on every page load.
const showAdvanced = ref(false);

function handleAdvancedShortcut(e) {
    if (!(e.ctrlKey || e.metaKey) || !e.shiftKey) return;
    if (e.key !== 'A' && e.key !== 'a') return;
    e.preventDefault();
    showAdvanced.value = !showAdvanced.value;
    ElMessage({
        message: showAdvanced.value
            ? t('menu.sqlConsoleRevealed')
            : t('menu.sqlConsoleHidden'),
        type: showAdvanced.value ? 'warning' : 'info',
        duration: 1500,
    });
}

onMounted(() => window.addEventListener('keydown', handleAdvancedShortcut));
onBeforeUnmount(() => window.removeEventListener('keydown', handleAdvancedShortcut));

const activeMenu = computed(() => route.path);

const currentPageTitle = computed(() => {
    const key = route.meta?.titleKey;
    return key ? t(key) : '';
});

function onLocaleChange(value) {
    localStorage.setItem('admin_locale', value);
    // Reload so Element Plus locale picks up the change.
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
.app-menu {
    margin-top: 8px;
}
</style>
