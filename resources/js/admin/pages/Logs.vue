<template>
    <div class="page-card">
        <div class="page-card-title" style="justify-content: space-between; display: flex">
            <div style="display: flex; align-items: center; gap: 8px">
                <el-icon><Document /></el-icon>
                {{ t('logs.title') }}
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
                    v-model="filters.type"
                    :placeholder="t('logs.typeAll')"
                    size="default"
                    style="width: 140px"
                    clearable
                    @change="fetchLogs"
                >
                    <el-option :label="t('logs.typeInfo')" value="info" />
                    <el-option :label="t('logs.typeWarn')" value="warn" />
                    <el-option :label="t('logs.typeError')" value="error" />
                </el-select>

                <el-select
                    v-model="filters.target_type"
                    :placeholder="t('logs.targetTypeAll')"
                    size="default"
                    style="width: 160px"
                    clearable
                    @change="fetchLogs"
                >
                    <el-option
                        v-for="opt in targetTypeOptions"
                        :key="opt.value"
                        :label="opt.label"
                        :value="opt.value"
                    />
                </el-select>

                <el-date-picker
                    v-model="filters.range"
                    type="datetimerange"
                    :start-placeholder="t('logs.from')"
                    :end-placeholder="t('logs.to')"
                    value-format="YYYY-MM-DD HH:mm:ss"
                    size="default"
                    style="width: 360px"
                    clearable
                    @change="fetchLogs"
                />

                <el-button @click="fetchLogs">
                    <el-icon><Refresh /></el-icon>
                </el-button>

                <el-button type="danger" plain :disabled="!logs.length" @click="deleteAll">
                    <el-icon style="margin-right: 4px"><Delete /></el-icon>
                    {{ t('logs.deleteAll') }}
                </el-button>
            </div>
        </div>

        <el-table
            v-loading="loading"
            :data="logs"
            stripe
            border
            style="width: 100%"
            :empty-text="t('common.noData')"
        >
            <el-table-column prop="time" :label="t('logs.time')" width="180">
                <template #default="{ row }">
                    {{ formatTime(row.time) }}
                </template>
            </el-table-column>
            <el-table-column :label="t('logs.type')" width="110">
                <template #default="{ row }">
                    <el-tag
                        :type="typeTagType(row.type)"
                        size="small"
                        disable-transitions
                    >
                        {{ row.type }}
                    </el-tag>
                </template>
            </el-table-column>
            <el-table-column :label="t('logs.targetType')" width="120">
                <template #default="{ row }">
                    <el-tag v-if="row.target_type" size="small" disable-transitions type="info">
                        {{ row.target_type }}
                    </el-tag>
                    <span v-else style="color: #9ca3af">-</span>
                </template>
            </el-table-column>
            <el-table-column prop="target_id" :label="t('logs.targetId')" min-width="280">
                <template #default="{ row }">
                    <span style="font-family: monospace; font-size: 12px">{{ row.target_id || '-' }}</span>
                </template>
            </el-table-column>
            <el-table-column :label="t('logs.user')" min-width="180">
                <template #default="{ row }">
                    <span v-if="row.user">{{ row.user.display_name || row.user.email }}</span>
                    <span v-else style="color: #9ca3af">-</span>
                </template>
            </el-table-column>
            <el-table-column prop="message" :label="t('logs.message')" min-width="240" />
            <el-table-column :label="t('common.actions')" width="120" fixed="right">
                <template #default="{ row }">
                    <el-button
                        size="small"
                        type="danger"
                        plain
                        :loading="row._deleting"
                        @click="deleteOne(row)"
                    >
                        <el-icon style="margin-right: 3px"><Delete /></el-icon>
                        {{ t('common.delete') }}
                    </el-button>
                </template>
            </el-table-column>
        </el-table>

        <div style="margin-top: 12px; display: flex; justify-content: flex-end">
            <el-pagination
                v-model:current-page="pagination.page"
                v-model:page-size="pagination.perPage"
                :page-sizes="[20, 50, 100, 200]"
                :total="pagination.total"
                layout="total, sizes, prev, pager, next, jumper"
                background
                @current-change="fetchLogs"
                @size-change="onPageSizeChange"
            />
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { ElMessage, ElMessageBox } from 'element-plus';
import { http } from '../api/http';

const { t } = useI18n();

const targetTypeOptions = computed(() => [
    { value: 'profile', label: t('logs.targetProfile') },
    { value: 'group', label: t('logs.targetGroup') },
    { value: 'proxy', label: t('logs.targetProxy') },
    { value: 'user', label: t('logs.targetUser') },
]);

const logs = ref([]);
const loading = ref(false);
let searchTimer = null;

const filters = reactive({
    search: '',
    type: '',
    target_type: '',
    range: null,
});

const pagination = reactive({
    page: 1,
    perPage: 20,
    total: 0,
});

function buildParams() {
    const params = {
        search: filters.search || undefined,
        type: filters.type || undefined,
        target_type: filters.target_type || undefined,
        page: pagination.page,
        per_page: pagination.perPage,
    };
    if (Array.isArray(filters.range) && filters.range.length === 2) {
        params.from = filters.range[0];
        params.to = filters.range[1];
    }
    return params;
}

function typeTagType(type) {
    switch (type) {
        case 'error': return 'danger';
        case 'warn': return 'warning';
        case 'info': return 'info';
        default: return 'info';
    }
}

function formatTime(value) {
    if (!value) return '';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
}

async function fetchLogs() {
    loading.value = true;
    try {
        const { data } = await http.get('/logs', { params: buildParams() });
        if (data?.success) {
            const payload = data.data || {};
            logs.value = (payload.data || []).map((row) => ({ ...row, _deleting: false }));
            pagination.total = payload.total || 0;
            pagination.page = payload.current_page || 1;
            pagination.perPage = payload.per_page || pagination.perPage;
        }
    } catch (err) {
        ElMessage.error(err?.response?.data?.message || t('common.error'));
    } finally {
        loading.value = false;
    }
}

function onSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        pagination.page = 1;
        fetchLogs();
    }, 300);
}

function onPageSizeChange(size) {
    pagination.perPage = size;
    pagination.page = 1;
    fetchLogs();
}

async function deleteOne(row) {
    try {
        await ElMessageBox.confirm(t('logs.deleteOneConfirm'), t('common.confirm'), { type: 'warning' });
    } catch {
        return;
    }
    row._deleting = true;
    try {
        const { data } = await http.post(`/logs/${row.id}/delete`);
        if (data?.success) {
            ElMessage.success(t('common.success'));
            fetchLogs();
        } else {
            ElMessage.error(data?.message || t('common.error'));
        }
    } catch (err) {
        ElMessage.error(err?.response?.data?.message || t('common.error'));
    } finally {
        row._deleting = false;
    }
}

async function deleteAll() {
    try {
        await ElMessageBox.confirm(t('logs.deleteAllConfirm'), t('common.confirm'), { type: 'warning' });
    } catch {
        return;
    }
    loading.value = true;
    try {
        const params = buildParams();
        const { data } = await http.post('/logs/delete-all', {
            search: params.search,
            type: params.type,
            target_type: params.target_type,
            from: params.from,
            to: params.to,
        });
        if (data?.success) {
            ElMessage.success(t('logs.deletedCount', { count: data.deleted ?? 0 }));
            pagination.page = 1;
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
