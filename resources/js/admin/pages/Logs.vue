<template>
    <div class="admin-page">
        <AdminPageHeader
            icon="Document"
            tone="amber"
            :title="t('logs.title')"
            :subtitle="t('logs.pageSubtitle')"
        />

        <div class="page-card admin-page-body">
        <AdminToolbar>
            <el-input
                v-model="filters.search"
                :placeholder="t('common.searchPlaceholder')"
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
                style="width: 340px"
                clearable
                @change="fetchLogs"
            />

            <el-button @click="fetchLogs">
                <el-icon><Refresh /></el-icon>
            </el-button>

            <div class="admin-toolbar__spacer" />

            <el-button type="danger" plain :disabled="!logs.length" @click="deleteAll">
                <el-icon style="margin-right: 4px"><Delete /></el-icon>
                {{ t('logs.deleteAll') }}
            </el-button>
        </AdminToolbar>

        <div v-if="filters.target_id" class="admin-filter-chip">
            <el-tag
                type="primary"
                closable
                disable-transitions
                @close="clearTargetIdFilter"
            >
                {{ t('logs.filteringByTarget') }}:
                <span class="cell-mono" style="margin-left: 4px">{{ filters.target_id }}</span>
            </el-tag>
        </div>

        <el-alert
            v-if="writeLogEnabled === false"
            type="warning"
            :closable="false"
            show-icon
            style="margin-bottom: 12px"
            :title="t('logs.disabledTitle')"
        >
            <template #default>
                <div>
                    {{ t('logs.disabledDesc') }}
                    <router-link to="/admin/app/system" style="margin-left: 4px">
                        {{ t('logs.goToSettings') }}
                    </router-link>
                </div>
            </template>
        </el-alert>

        <el-table
            v-loading="loading"
            :data="logs"
            class="admin-table"
            style="width: 100%"
            :empty-text="t('common.noData')"
        >
            <el-table-column prop="time" :label="t('logs.time')" width="180">
                <template #default="{ row }">
                    <span class="cell-time">{{ formatTime(row.time) }}</span>
                </template>
            </el-table-column>
            <el-table-column :label="t('logs.type')" width="110">
                <template #default="{ row }">
                    <el-tag
                        class="status-pill"
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
                    <el-tag v-if="row.target_type" class="badge-soft" size="small" disable-transitions>
                        {{ row.target_type }}
                    </el-tag>
                    <span v-else class="cell-muted">—</span>
                </template>
            </el-table-column>
            <el-table-column prop="target_id" :label="t('logs.targetId')" width="290">
                <template #default="{ row }">
                    <span class="cell-mono">{{ row.target_id || '—' }}</span>
                </template>
            </el-table-column>
            <el-table-column :label="t('logs.user')" width="230">
                <template #default="{ row }">
                    <span v-if="row.user" class="cell-secondary">{{ row.user.display_name || row.user.email }}</span>
                    <span v-else class="cell-muted">—</span>
                </template>
            </el-table-column>
            <el-table-column prop="message" :label="t('logs.message')" min-width="240">
                <template #default="{ row }">
                    <span class="cell-meta" style="font-size: 0.8125rem; color: var(--foreground)">{{ row.message }}</span>
                </template>
            </el-table-column>
            <el-table-column
                :label="t('common.actions')"
                :width="isMobile ? 56 : 120"
                fixed="right"
                align="center"
            >
                <template #default="{ row }">
                    <el-button
                        size="small"
                        type="danger"
                        plain
                        :loading="row._deleting"
                        @click="deleteOne(row)"
                    >
                        <el-icon :style="isMobile ? '' : 'margin-right: 3px'"><Delete /></el-icon>
                        <span v-if="!isMobile">{{ t('common.delete') }}</span>
                    </el-button>
                </template>
            </el-table-column>
        </el-table>

        <AdminPagination>
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
        </AdminPagination>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { ElMessage, ElMessageBox } from 'element-plus';
import { http } from '../api/http';
import AdminPageHeader from '../components/AdminPageHeader.vue';
import AdminToolbar from '../components/AdminToolbar.vue';
import AdminPagination from '../components/AdminPagination.vue';
import { useIsMobile } from '../composables/useIsMobile';

const route = useRoute();
const router = useRouter();

const { t } = useI18n();
const isMobile = useIsMobile();

const targetTypeOptions = computed(() => [
    { value: 'profile', label: t('logs.targetProfile') },
    { value: 'group', label: t('logs.targetGroup') },
    { value: 'proxy', label: t('logs.targetProxy') },
    { value: 'user', label: t('logs.targetUser') },
]);

const logs = ref([]);
const loading = ref(false);
const writeLogEnabled = ref(null); // null while unknown so banner doesn't flash
let searchTimer = null;

const filters = reactive({
    search: '',
    type: '',
    target_type: '',
    target_id: '',
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
        target_id: filters.target_id || undefined,
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
            writeLogEnabled.value = !!data.write_log_enabled;
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
            target_id: params.target_id,
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

function clearTargetIdFilter() {
    filters.target_id = '';
    // Drop the query param from the URL too so refreshing won't re-apply it.
    router.replace({ query: { ...route.query, target_id: undefined } });
    pagination.page = 1;
    fetchLogs();
}

// Pick up ?target_id=... from URL on entry (used by other pages linking
// here, e.g. the per-row "View logs" button on Profiles.vue).
watch(
    () => route.query.target_id,
    (id) => {
        const next = typeof id === 'string' ? id : '';
        if (next === filters.target_id) return;
        filters.target_id = next;
        pagination.page = 1;
        fetchLogs();
    },
    { immediate: false }
);

onMounted(() => {
    const initial = route.query.target_id;
    if (typeof initial === 'string' && initial !== '') {
        filters.target_id = initial;
    }
    fetchLogs();
});
</script>
