<template>
    <div class="page-card">
        <div class="page-card-title" style="justify-content: space-between; display: flex">
            <div style="display: flex; align-items: center; gap: 8px">
                <el-icon><Collection /></el-icon>
                {{ t('menu.groups') }}
            </div>
            <div style="display: flex; gap: 8px; align-items: center">
                <el-input
                    v-model="search"
                    :placeholder="t('common.searchPlaceholder')"
                    style="width: 260px"
                    clearable
                    @input="onSearch"
                >
                    <template #prefix>
                        <el-icon><Search /></el-icon>
                    </template>
                </el-input>
                <el-button @click="fetchGroups">
                    <el-icon><Refresh /></el-icon>
                </el-button>
                <el-button type="primary" @click="openCreate">
                    <el-icon style="margin-right: 4px"><Plus /></el-icon>
                    {{ t('groups.create') }}
                </el-button>
            </div>
        </div>

        <el-table
            v-loading="loading"
            :data="rows"
            stripe
            border
            style="width: 100%"
            :empty-text="t('common.noData')"
        >
            <el-table-column :label="t('groups.name')" min-width="240">
                <template #default="{ row }">
                    <div style="display: flex; align-items: center; gap: 8px">
                        <span>{{ row.name }}</span>
                        <el-tag v-if="isDefaultGroup(row)" type="info" size="small" disable-transitions>
                            {{ t('groups.defaultBadge') }}
                        </el-tag>
                    </div>
                </template>
            </el-table-column>
            <el-table-column prop="sort_order" :label="t('groups.sortOrder')" width="120" align="center" />
            <el-table-column :label="t('groups.creator')" min-width="200">
                <template #default="{ row }">
                    <div v-if="row.creator" style="display: flex; flex-direction: column">
                        <span style="font-weight: 500">{{ row.creator.display_name || '—' }}</span>
                        <span style="font-size: 12px; color: #6b7280">{{ row.creator.email }}</span>
                    </div>
                    <span v-else style="color: #9ca3af">—</span>
                </template>
            </el-table-column>
            <el-table-column :label="t('groups.updatedAt')" width="170">
                <template #default="{ row }">
                    <span style="font-size: 12px; color: #6b7280">{{ formatTime(row.updated_at) }}</span>
                </template>
            </el-table-column>
            <el-table-column :label="t('common.actions')" width="280" fixed="right">
                <template #default="{ row }">
                    <el-button
                        size="small"
                        type="info"
                        plain
                        :disabled="isDefaultGroup(row)"
                        @click="openShare(row)"
                    >
                        <el-icon><Share /></el-icon>
                    </el-button>
                    <el-button
                        size="small"
                        type="primary"
                        plain
                        :disabled="isDefaultGroup(row)"
                        @click="openEdit(row)"
                    >
                        <el-icon style="margin-right: 3px"><Edit /></el-icon>
                        {{ t('common.edit') }}
                    </el-button>
                    <el-button
                        size="small"
                        type="danger"
                        plain
                        :disabled="isDefaultGroup(row)"
                        :loading="row._deleting"
                        @click="deleteGroup(row)"
                    >
                        <el-icon><Delete /></el-icon>
                    </el-button>
                </template>
            </el-table-column>
        </el-table>

        <div style="display: flex; justify-content: flex-end; margin-top: 16px">
            <el-pagination
                v-model:current-page="page"
                v-model:page-size="perPage"
                :page-sizes="[10, 20, 30, 50, 100]"
                :total="total"
                layout="total, sizes, prev, pager, next, jumper"
                background
                @current-change="fetchGroups"
                @size-change="onSizeChange"
            />
        </div>

        <ShareDialog
            v-model="shareDialog.visible"
            type="group"
            :ids="shareDialog.id"
            :name="shareDialog.name"
        />

        <el-dialog
            v-model="dialog.visible"
            :title="dialog.mode === 'create' ? t('groups.create') : t('groups.edit')"
            width="440px"
            :close-on-click-modal="false"
        >
            <el-form
                ref="formRef"
                :model="dialog.form"
                :rules="rules"
                label-position="top"
                @submit.prevent="submitDialog"
            >
                <el-form-item :label="t('groups.name')" prop="name">
                    <el-input v-model="dialog.form.name" maxlength="100" show-word-limit />
                </el-form-item>
                <el-form-item :label="t('groups.sortOrder')" prop="order">
                    <el-input-number v-model="dialog.form.order" :min="0" :max="9999" style="width: 100%" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="dialog.visible = false">{{ t('common.cancel') }}</el-button>
                <el-button type="primary" :loading="dialog.saving" @click="submitDialog">
                    {{ t('common.save') }}
                </el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { ElMessage, ElMessageBox } from 'element-plus';
import { apiV1 } from '../api/http';
import ShareDialog from '../components/ShareDialog.vue';

const { t } = useI18n();

const DEFAULT_GROUP_ID = '00000000-0000-0000-0000-000000000000';

const loading = ref(false);
const rows = ref([]);
const search = ref('');
const page = ref(1);
const perPage = ref(20);
const total = ref(0);
let searchTimer = null;

const formRef = ref(null);
const dialog = reactive({
    visible: false,
    mode: 'create',
    saving: false,
    form: { id: null, name: '', order: 0 },
});

const shareDialog = reactive({ visible: false, id: '', name: '' });

function openShare(row) {
    shareDialog.id = row.id;
    shareDialog.name = row.name;
    shareDialog.visible = true;
}

const rules = {
    name: [{ required: true, message: () => t('groups.nameRequired'), trigger: 'blur' }],
    order: [{ required: true, message: () => t('groups.orderRequired'), trigger: 'blur' }],
};

function isDefaultGroup(row) {
    return row?.id === DEFAULT_GROUP_ID;
}

function formatTime(value) {
    if (!value) return '—';
    try {
        const d = new Date(value);
        if (Number.isNaN(d.getTime())) return value;
        return d.toLocaleString();
    } catch {
        return value;
    }
}

async function fetchGroups() {
    loading.value = true;
    try {
        const { data } = await apiV1.get('/groups', {
            params: { search: search.value, per_page: perPage.value, page: page.value },
        });
        if (data?.success) {
            const payload = data.data || {};
            rows.value = (payload.data || []).map((g) => ({ ...g, _deleting: false }));
            total.value = payload.total ?? rows.value.length;
            page.value = payload.current_page ?? page.value;
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
        page.value = 1;
        fetchGroups();
    }, 300);
}

function onSizeChange(size) {
    perPage.value = size;
    page.value = 1;
    fetchGroups();
}

function openCreate() {
    dialog.mode = 'create';
    dialog.form = { id: null, name: '', order: 0 };
    dialog.visible = true;
}

function openEdit(row) {
    dialog.mode = 'edit';
    dialog.form = { id: row.id, name: row.name, order: row.sort_order ?? 0 };
    dialog.visible = true;
}

async function submitDialog() {
    const valid = await formRef.value?.validate().catch(() => false);
    if (!valid) return;

    dialog.saving = true;
    try {
        const payload = { name: dialog.form.name.trim(), order: dialog.form.order };
        const url = dialog.mode === 'create'
            ? '/groups/create'
            : `/groups/update/${dialog.form.id}`;
        const { data } = await apiV1.post(url, payload);
        if (data?.success) {
            ElMessage.success(t('common.success'));
            dialog.visible = false;
            await fetchGroups();
        } else {
            ElMessage.error(data?.message || t('common.error'));
        }
    } catch (err) {
        ElMessage.error(err?.response?.data?.message || t('common.error'));
    } finally {
        dialog.saving = false;
    }
}

async function deleteGroup(row) {
    try {
        await ElMessageBox.confirm(
            t('groups.deleteConfirm', { name: row.name }),
            t('common.confirm'),
            { type: 'warning' }
        );
    } catch {
        return;
    }

    row._deleting = true;
    try {
        const { data } = await apiV1.post(`/groups/delete/${row.id}`);
        if (data?.success) {
            ElMessage.success(t('common.success'));
            if (rows.value.length === 1 && page.value > 1) page.value -= 1;
            await fetchGroups();
        } else {
            ElMessage.error(mapDeleteError(data?.message));
        }
    } catch (err) {
        ElMessage.error(err?.response?.data?.message || t('common.error'));
    } finally {
        row._deleting = false;
    }
}

function mapDeleteError(code) {
    const map = {
        cannot_delete_all_group: t('groups.cannotDeleteDefault'),
        cannot_delete_group_with_profiles: t('groups.cannotDeleteWithProfiles'),
        group_not_found: t('groups.notFound'),
        not_have_permission: t('common.error'),
    };
    return map[code] || code || t('common.error');
}

onMounted(fetchGroups);
</script>
