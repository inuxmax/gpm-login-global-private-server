<template>
    <el-dialog
        v-model="visible"
        :title="dialogTitle"
        width="900px"
        :close-on-click-modal="false"
        append-to-body
        destroy-on-close
        @open="onOpen"
    >
        <div v-if="targetId" style="margin-bottom: 8px; font-size: 12px; color: #6b7280">
            <span style="color: #4b5563; margin-right: 4px">target_id:</span>
            <span style="font-family: monospace">{{ targetId }}</span>
        </div>

        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 12px">
            <el-select
                v-model="filters.type"
                :placeholder="t('logs.typeAll')"
                size="default"
                style="width: 140px"
                clearable
                @change="reload(true)"
            >
                <el-option :label="t('logs.typeInfo')" value="info" />
                <el-option :label="t('logs.typeWarn')" value="warn" />
                <el-option :label="t('logs.typeError')" value="error" />
            </el-select>

            <el-button @click="fetchLogs">
                <el-icon><Refresh /></el-icon>
            </el-button>

            <div style="flex: 1"></div>

            <el-button type="primary" plain size="small" @click="openFullPage">
                <el-icon style="margin-right: 4px"><Document /></el-icon>
                {{ t('logs.openFullPage') }}
            </el-button>
        </div>

        <el-table
            v-loading="loading"
            :data="logs"
            stripe
            border
            size="small"
            style="width: 100%"
            max-height="440"
            :empty-text="t('common.noData')"
        >
            <el-table-column prop="time" :label="t('logs.time')" width="170">
                <template #default="{ row }">
                    <span style="font-size: 12px">{{ formatTime(row.time) }}</span>
                </template>
            </el-table-column>
            <el-table-column :label="t('logs.type')" width="90">
                <template #default="{ row }">
                    <el-tag :type="typeTagType(row.type)" size="small" disable-transitions>
                        {{ row.type }}
                    </el-tag>
                </template>
            </el-table-column>
            <el-table-column :label="t('logs.user')" width="160">
                <template #default="{ row }">
                    <span v-if="row.user">{{ row.user.display_name || row.user.email }}</span>
                    <span v-else style="color: #9ca3af">-</span>
                </template>
            </el-table-column>
            <el-table-column prop="message" :label="t('logs.message')" min-width="240" show-overflow-tooltip />
        </el-table>

        <div style="margin-top: 12px; display: flex; justify-content: flex-end">
            <el-pagination
                v-model:current-page="pagination.page"
                v-model:page-size="pagination.perPage"
                :page-sizes="[20, 50, 100]"
                :total="pagination.total"
                layout="total, sizes, prev, pager, next"
                background
                small
                @current-change="fetchLogs"
                @size-change="onPageSizeChange"
            />
        </div>

        <template #footer>
            <el-button @click="visible = false">{{ t('common.close') }}</el-button>
        </template>
    </el-dialog>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { ElMessage } from 'element-plus';
import { http } from '../api/http';

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    targetId: { type: String, default: '' },
    targetName: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const { t } = useI18n();
const router = useRouter();

const visible = computed({
    get: () => props.modelValue,
    set: (v) => emit('update:modelValue', v),
});

const dialogTitle = computed(() =>
    props.targetName
        ? t('logs.titleForTarget', { name: props.targetName })
        : t('logs.title')
);

const logs = ref([]);
const loading = ref(false);

const filters = reactive({
    type: '',
});

const pagination = reactive({
    page: 1,
    perPage: 20,
    total: 0,
});

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
    if (!props.targetId) return;
    loading.value = true;
    try {
        const { data } = await http.get('/logs', {
            params: {
                target_id: props.targetId,
                type: filters.type || undefined,
                page: pagination.page,
                per_page: pagination.perPage,
            },
        });
        if (data?.success) {
            const payload = data.data || {};
            logs.value = payload.data || [];
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

function reload(resetPage = false) {
    if (resetPage) pagination.page = 1;
    fetchLogs();
}

function onPageSizeChange(size) {
    pagination.perPage = size;
    pagination.page = 1;
    fetchLogs();
}

function onOpen() {
    pagination.page = 1;
    filters.type = '';
    logs.value = [];
    fetchLogs();
}

function openFullPage() {
    visible.value = false;
    router.push({ path: '/admin/app/logs', query: { target_id: props.targetId } });
}

// Refetch when targetId changes while dialog stays open (rare but safe).
watch(() => props.targetId, (id) => {
    if (visible.value && id) {
        pagination.page = 1;
        fetchLogs();
    }
});
</script>
