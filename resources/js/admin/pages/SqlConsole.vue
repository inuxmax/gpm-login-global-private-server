<template>
    <div class="page-card">
        <div class="page-card-title" style="justify-content: space-between; display: flex">
            <div style="display: flex; align-items: center; gap: 8px">
                <el-icon><Operation /></el-icon>
                {{ t('sqlConsole.title') }}
            </div>
            <div style="display: flex; gap: 8px; align-items: center">
                <el-tag type="warning" size="small" disable-transitions>
                    {{ t('sqlConsole.dangerHint') }}
                </el-tag>
            </div>
        </div>

        <el-input
            v-model="sql"
            type="textarea"
            :rows="8"
            :placeholder="t('sqlConsole.sqlPlaceholder')"
            input-style="font-family: monospace; font-size: 13px"
            @keydown.ctrl.enter="onRun"
            @keydown.meta.enter="onRun"
        />

        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px; gap: 12px; flex-wrap: wrap">
            <div style="display: flex; gap: 8px">
                <el-button type="primary" :loading="running" @click="onRun">
                    <el-icon style="margin-right: 4px"><CaretRight /></el-icon>
                    {{ t('sqlConsole.run') }}
                </el-button>
                <el-button @click="sql = ''" :disabled="!sql">
                    <el-icon style="margin-right: 4px"><Delete /></el-icon>
                    {{ t('sqlConsole.clearSql') }}
                </el-button>
                <span style="font-size: 12px; color: #6b7280; align-self: center">
                    {{ t('sqlConsole.shortcutHint') }}
                </span>
            </div>

            <div v-if="result" style="display: flex; gap: 8px; align-items: center">
                <el-tag size="small" disable-transitions :type="result.success ? 'success' : 'danger'">
                    {{ result.success ? t('sqlConsole.statusSuccess') : t('sqlConsole.statusFailed') }}
                </el-tag>
                <el-tag size="small" disable-transitions type="info">
                    {{ result.duration_ms }} ms
                </el-tag>
                <el-tag v-if="result.type === 'select' && result.success" size="small" disable-transitions type="info">
                    {{ t('sqlConsole.rowsReturned', { count: result.row_count }) }}
                </el-tag>
                <el-tag v-if="result.type === 'modify' && result.success" size="small" disable-transitions>
                    {{ t('sqlConsole.affectedRows', { count: result.affected_rows }) }}
                </el-tag>
            </div>
        </div>

        <el-alert
            v-if="result && !result.success"
            type="error"
            :closable="false"
            style="margin-top: 12px"
            :title="result.message"
        />

        <el-alert
            v-if="result?.success && result.type === 'select' && result.truncated"
            type="warning"
            :closable="false"
            style="margin-top: 12px"
            :title="t('sqlConsole.truncatedNotice', { max: 5000 })"
        />

        <div v-if="result?.success && result.type === 'select'" style="margin-top: 16px">
            <div v-if="result.rows.length === 0" style="text-align: center; color: #9ca3af; padding: 24px">
                {{ t('common.noData') }}
            </div>
            <el-table
                v-else
                :data="result.rows"
                stripe
                border
                size="small"
                style="width: 100%"
                max-height="540"
            >
                <el-table-column type="index" width="60" align="center" />
                <el-table-column
                    v-for="col in result.columns"
                    :key="col"
                    :prop="col"
                    :label="col"
                    show-overflow-tooltip
                    min-width="140"
                >
                    <template #default="{ row }">
                        <span v-if="row[col] === null" style="color: #9ca3af; font-style: italic">NULL</span>
                        <span v-else-if="typeof row[col] === 'object'" style="font-family: monospace; font-size: 12px">
                            {{ JSON.stringify(row[col]) }}
                        </span>
                        <span v-else style="font-family: monospace; font-size: 12px">{{ row[col] }}</span>
                    </template>
                </el-table-column>
            </el-table>
        </div>

        <div
            v-else-if="result?.success && result.type !== 'select'"
            style="margin-top: 16px; padding: 16px; background: #f0f9ff; border-radius: 6px; color: #0369a1"
        >
            <el-icon style="vertical-align: middle; margin-right: 6px"><CircleCheckFilled /></el-icon>
            {{ result.type === 'modify'
                ? t('sqlConsole.modifyOk', { count: result.affected_rows })
                : t('sqlConsole.ddlOk') }}
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { ElMessage, ElMessageBox } from 'element-plus';
import { http } from '../api/http';

const { t } = useI18n();

const sql = ref('');
const running = ref(false);
const result = ref(null);

const DESTRUCTIVE_PATTERN = /^\s*(?:--[^\n]*\n|\/\*.*?\*\/|\s)*\s*(drop|truncate|alter|delete|update)\b/is;

async function onRun() {
    const text = sql.value.trim();
    if (!text) {
        ElMessage.warning(t('sqlConsole.sqlEmpty'));
        return;
    }

    if (DESTRUCTIVE_PATTERN.test(text)) {
        try {
            await ElMessageBox.confirm(t('sqlConsole.destructiveConfirm'), t('common.confirm'), {
                type: 'warning',
            });
        } catch {
            return;
        }
    }

    running.value = true;
    try {
        const { data } = await http.post('/sql/execute', { sql: text });
        result.value = data;
        if (!data?.success) {
            ElMessage.error(data?.message || t('common.error'));
        }
    } catch (err) {
        const payload = err?.response?.data;
        result.value = payload || {
            success: false,
            message: err?.message || t('common.error'),
            duration_ms: 0,
        };
        ElMessage.error(payload?.message || t('common.error'));
    } finally {
        running.value = false;
    }
}
</script>
