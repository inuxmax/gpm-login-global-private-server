<template>
    <div class="page-card">
        <div class="page-card-title" style="justify-content: space-between; display: flex">
            <div style="display: flex; align-items: center; gap: 8px">
                <el-icon><Tickets /></el-icon>
                {{ t('systemLogs.title') }}
                <el-tag v-if="meta.size != null" size="small" type="info" disable-transitions>
                    {{ meta.path }} · {{ formatBytes(meta.size) }}
                </el-tag>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap">
                <el-input
                    v-model="filters.search"
                    :placeholder="t('common.searchPlaceholder')"
                    size="default"
                    style="width: 220px"
                    clearable
                    @input="onSearch"
                    @clear="fetchLogs"
                >
                    <template #prefix>
                        <el-icon><Search /></el-icon>
                    </template>
                </el-input>

                <el-select
                    v-model="filters.level"
                    :placeholder="t('systemLogs.levelAll')"
                    size="default"
                    style="width: 150px"
                    clearable
                    @change="fetchLogs"
                >
                    <el-option v-for="lv in levels" :key="lv" :label="lv.toUpperCase()" :value="lv" />
                </el-select>

                <el-select
                    v-model="filters.lines"
                    size="default"
                    style="width: 130px"
                    @change="fetchLogs"
                >
                    <el-option
                        v-for="n in [100, 500, 1000, 2000, 5000]"
                        :key="n"
                        :label="t('systemLogs.linesLabel', { count: n })"
                        :value="n"
                    />
                </el-select>

                <el-button @click="fetchLogs">
                    <el-icon><Refresh /></el-icon>
                </el-button>

                <el-button type="danger" plain :disabled="!entries.length" @click="clearFile">
                    <el-icon style="margin-right: 4px"><Delete /></el-icon>
                    {{ t('systemLogs.clear') }}
                </el-button>
            </div>
        </div>

        <el-alert
            v-if="meta.truncated_read"
            :title="t('systemLogs.truncatedNotice')"
            type="warning"
            :closable="false"
            style="margin-bottom: 12px"
        />

        <div v-loading="loading" class="syslog-console">
            <div v-if="!entries.length && !loading" class="syslog-empty">
                {{ t('common.noData') }}
            </div>

            <div v-for="(e, idx) in entries" :key="idx" class="syslog-entry">
                <div class="syslog-entry-head">
                    <el-tag
                        :type="levelTagType(e.level)"
                        size="small"
                        disable-transitions
                        style="text-transform: uppercase"
                    >
                        {{ e.level }}
                    </el-tag>
                    <span class="syslog-time">{{ e.time }}</span>
                    <span class="syslog-env">{{ e.env }}</span>
                </div>

                <div class="syslog-message">{{ e.message }}</div>

                <details v-if="e.raw && e.raw.length > e.message.length" class="syslog-raw">
                    <summary>{{ t('systemLogs.showRaw') }}</summary>
                    <pre>{{ e.raw }}</pre>
                </details>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { ElMessage, ElMessageBox } from 'element-plus';
import { http } from '../api/http';

const { t } = useI18n();

const levels = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];

const entries = ref([]);
const loading = ref(false);
let searchTimer = null;

const filters = reactive({
    search: '',
    level: '',
    lines: 500,
});

const meta = reactive({
    size: null,
    path: 'storage/logs/laravel.log',
    truncated_read: false,
    exists: true,
});

function levelTagType(level) {
    switch (level) {
        case 'emergency':
        case 'alert':
        case 'critical':
        case 'error':
            return 'danger';
        case 'warning':
            return 'warning';
        case 'notice':
        case 'info':
            return 'primary';
        case 'debug':
        default:
            return 'info';
    }
}

function formatBytes(bytes) {
    if (bytes == null) return '';
    if (bytes < 1024) return `${bytes} B`;
    const units = ['KB', 'MB', 'GB'];
    let value = bytes / 1024;
    let i = 0;
    while (value >= 1024 && i < units.length - 1) {
        value /= 1024;
        i++;
    }
    return `${value.toFixed(value < 10 ? 2 : 1)} ${units[i]}`;
}

async function fetchLogs() {
    loading.value = true;
    try {
        const { data } = await http.get('/system-logs', {
            params: {
                lines: filters.lines,
                level: filters.level || undefined,
                search: filters.search || undefined,
            },
        });
        if (data?.success) {
            const payload = data.data || {};
            entries.value = payload.entries || [];
            meta.size = payload.size ?? 0;
            meta.path = payload.path || meta.path;
            meta.truncated_read = !!payload.truncated_read;
            meta.exists = payload.exists !== false;
        }
    } catch (err) {
        ElMessage.error(err?.response?.data?.message || t('common.error'));
    } finally {
        loading.value = false;
    }
}

function onSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(fetchLogs, 300);
}

async function clearFile() {
    try {
        await ElMessageBox.confirm(t('systemLogs.clearConfirm'), t('common.confirm'), { type: 'warning' });
    } catch {
        return;
    }
    loading.value = true;
    try {
        const { data } = await http.post('/system-logs/clear');
        if (data?.success) {
            ElMessage.success(t('common.success'));
            fetchLogs();
        } else {
            ElMessage.error(data?.message || t('common.error'));
        }
    } catch (err) {
        ElMessage.error(err?.response?.data?.message || t('common.error'));
    } finally {
        loading.value = false;
    }
}

onMounted(fetchLogs);
</script>

<style scoped>
.syslog-console {
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 8px;
    max-height: calc(100vh - 280px);
    overflow: auto;
    background: #fafafa;
}

.syslog-empty {
    text-align: center;
    color: #9ca3af;
    padding: 32px;
}

.syslog-entry {
    border-bottom: 1px dashed #e5e7eb;
    padding: 8px 4px;
}
.syslog-entry:last-child {
    border-bottom: none;
}

.syslog-entry-head {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 4px;
}

.syslog-time {
    font-family: monospace;
    font-size: 12px;
    color: #4b5563;
}

.syslog-env {
    font-size: 12px;
    color: #6b7280;
    font-style: italic;
}

.syslog-message {
    font-family: monospace;
    font-size: 13px;
    color: #111827;
    white-space: pre-wrap;
    word-break: break-word;
}

.syslog-raw {
    margin-top: 6px;
}
.syslog-raw summary {
    cursor: pointer;
    font-size: 12px;
    color: #2563eb;
}
.syslog-raw pre {
    margin: 6px 0 0;
    padding: 8px;
    background: #1f2937;
    color: #e5e7eb;
    font-size: 12px;
    border-radius: 4px;
    overflow-x: auto;
    max-height: 320px;
    white-space: pre-wrap;
    word-break: break-word;
}
</style>
