<template>
    <div class="page-card">
        <div class="page-card-title" style="justify-content: space-between; display: flex">
            <div style="display: flex; align-items: center; gap: 8px">
                <el-icon><UserFilled /></el-icon>
                {{ t('menu.profiles') }}
            </div>
        </div>

        <el-tabs v-model="tab" @tab-change="onTabChange" style="margin-bottom: 0">
            <el-tab-pane :label="t('profiles.tabActive')" name="active">
                <template #label>
                    <span style="display: inline-flex; align-items: center; gap: 4px">
                        <el-icon><Folder /></el-icon>
                        {{ t('profiles.tabActive') }}
                    </span>
                </template>
            </el-tab-pane>
            <el-tab-pane :label="t('profiles.tabTrash')" name="trash">
                <template #label>
                    <span style="display: inline-flex; align-items: center; gap: 4px">
                        <el-icon><Delete /></el-icon>
                        {{ t('profiles.tabTrash') }}
                    </span>
                </template>
            </el-tab-pane>
        </el-tabs>

        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin: 8px 0 16px; align-items: center">
            <el-input
                v-model="search"
                :placeholder="t('common.searchPlaceholder')"
                style="width: 260px"
                clearable
                @input="onSearchInput"
            >
                <template #prefix>
                    <el-icon><Search /></el-icon>
                </template>
            </el-input>

            <el-select
                v-model="groupId"
                :placeholder="t('profiles.allGroups')"
                style="width: 200px"
                clearable
                filterable
                @change="reload(true)"
            >
                <el-option
                    v-for="g in groups"
                    :key="g.id"
                    :label="g.name"
                    :value="g.id"
                />
            </el-select>

            <el-button @click="reload()">
                <el-icon><Refresh /></el-icon>
            </el-button>

            <div style="flex: 1"></div>

            <template v-if="selection.length > 0">
                <el-tag type="info" disable-transitions>
                    {{ t('profiles.selected', { count: selection.length }) }}
                </el-tag>
                <template v-if="tab === 'active'">
                    <el-button type="info" plain size="default" @click="openBulkShare">
                        <el-icon style="margin-right: 3px"><Share /></el-icon>
                        {{ t('share.title') }}
                    </el-button>
                    <el-button type="warning" plain size="default" :loading="bulkBusy" @click="bulkSoftDelete">
                        <el-icon style="margin-right: 3px"><Delete /></el-icon>
                        {{ t('profiles.softDelete') }}
                    </el-button>
                </template>
                <template v-else>
                    <el-button type="success" plain size="default" :loading="bulkBusy" @click="bulkRestore">
                        <el-icon style="margin-right: 3px"><RefreshRight /></el-icon>
                        {{ t('profiles.restore') }}
                    </el-button>
                    <el-button type="danger" plain size="default" :loading="bulkBusy" @click="bulkHardDelete">
                        <el-icon style="margin-right: 3px"><Remove /></el-icon>
                        {{ t('profiles.hardDelete') }}
                    </el-button>
                </template>
            </template>
        </div>

        <el-table
            v-loading="loading"
            :data="rows"
            stripe
            border
            style="width: 100%"
            :empty-text="tab === 'active' ? t('profiles.empty') : t('profiles.emptyTrash')"
            @selection-change="(v) => (selection = v)"
        >
            <el-table-column type="selection" width="44" />
            <el-table-column :label="t('profiles.name')" min-width="220">
                <template #default="{ row }">
                    <el-tooltip :content="row.id" placement="top" :show-after="400">
                        <div style="display: flex; flex-direction: column; line-height: 1.3">
                            <span style="font-weight: 500">{{ row.name }}</span>
                            <span
                                v-if="row.creator"
                                style="font-size: 11px; color: #9ca3af; display: inline-flex; align-items: center; gap: 3px"
                            >
                                <el-icon :size="11"><User /></el-icon>
                                {{ row.creator.display_name || row.creator.email }}
                            </span>
                        </div>
                    </el-tooltip>
                </template>
            </el-table-column>
            <el-table-column :label="t('profiles.group')" min-width="140">
                <template #default="{ row }">
                    <span>{{ row.group?.name || '—' }}</span>
                </template>
            </el-table-column>
            <el-table-column :label="t('profiles.status')" width="130">
                <template #default="{ row }">
                    <el-tag
                        v-if="row.status === 1"
                        type="success"
                        size="small"
                        disable-transitions
                    >
                        <el-icon style="vertical-align: middle; margin-right: 3px"><CircleCheck /></el-icon>
                        {{ t('profiles.statusReady') }}
                    </el-tag>
                    <el-tag v-else type="warning" size="small" disable-transitions>
                        <el-icon style="vertical-align: middle; margin-right: 3px"><Loading /></el-icon>
                        {{ t('profiles.statusInUse') }}
                    </el-tag>
                </template>
            </el-table-column>
            <el-table-column :label="t('profiles.usingBy')" min-width="170">
                <template #default="{ row }">
                    <div v-if="row.current_user">
                        <div style="font-weight: 500">{{ row.current_user.display_name || '—' }}</div>
                        <div style="font-size: 12px; color: #6b7280">{{ row.current_user.email }}</div>
                    </div>
                    <span v-else style="color: #9ca3af">—</span>
                </template>
            </el-table-column>
            <el-table-column :label="t('profiles.storagePath')" width="130" align="center">
                <template #default="{ row }">
                    <el-tooltip :content="row.storage_path || '—'" placement="top">
                        <el-tag size="small" :type="storageBadgeType(row)" disable-transitions>
                            {{ storageLabel(row) }}
                        </el-tag>
                    </el-tooltip>
                </template>
            </el-table-column>
            <el-table-column :label="t('profiles.size')" width="120" align="right">
                <template #default="{ row }">
                    <span v-if="sizes[row.id]">{{ formatBytes(sizes[row.id].bytes) }}</span>
                    <span v-else style="color: #9ca3af">—</span>
                </template>
            </el-table-column>
            <el-table-column
                :label="tab === 'trash' ? t('profiles.deletedAt') : t('profiles.createdAt')"
                width="170"
            >
                <template #default="{ row }">
                    <span style="font-size: 12px; color: #6b7280">
                        {{ formatTime(tab === 'trash' ? row.deleted_at : row.created_at) }}
                    </span>
                </template>
            </el-table-column>
            <el-table-column :label="t('common.actions')" width="240" fixed="right">
                <template #default="{ row }">
                    <template v-if="tab === 'active'">
                        <el-button size="small" type="info" plain @click="openShare(row)">
                            <el-icon><Share /></el-icon>
                        </el-button>
                        <el-button
                            size="small"
                            type="success"
                            plain
                            :disabled="row.status === 1"
                            :loading="row._busy"
                            @click="resetStatus(row)"
                        >
                            <el-icon style="margin-right: 3px"><RefreshRight /></el-icon>
                            {{ t('profiles.resetStatus') }}
                        </el-button>
                        <el-button
                            size="small"
                            type="warning"
                            plain
                            :loading="row._busy"
                            @click="softDelete(row)"
                        >
                            <el-icon><Delete /></el-icon>
                        </el-button>
                    </template>
                    <template v-else>
                        <el-button size="small" type="success" plain :loading="row._busy" @click="restore(row)">
                            <el-icon style="margin-right: 3px"><RefreshRight /></el-icon>
                            {{ t('profiles.restore') }}
                        </el-button>
                        <el-button size="small" type="danger" plain :loading="row._busy" @click="hardDelete(row)">
                            <el-icon><Remove /></el-icon>
                        </el-button>
                    </template>
                </template>
            </el-table-column>
        </el-table>

        <ShareDialog
            v-model="shareDialog.visible"
            type="profile"
            :ids="shareDialog.ids"
            :name="shareDialog.name"
        />

        <div style="display: flex; justify-content: flex-end; margin-top: 16px">
            <el-pagination
                v-model:current-page="page"
                v-model:page-size="perPage"
                :page-sizes="[10, 20, 30, 50, 100]"
                :total="total"
                layout="total, sizes, prev, pager, next, jumper"
                background
                @current-change="fetchList"
                @size-change="onSizeChange"
            />
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { ElMessage, ElMessageBox } from 'element-plus';
import { apiV1, http } from '../api/http';
import ShareDialog from '../components/ShareDialog.vue';

const { t } = useI18n();

const loading = ref(false);
const rows = ref([]);
const groups = ref([]);
const search = ref('');
const groupId = ref('');
const tab = ref('active');
const page = ref(1);
const perPage = ref(20);
const total = ref(0);
const selection = ref([]);
const sizes = ref({});
const bulkBusy = ref(false);
let searchTimer = null;

const shareDialog = ref({ visible: false, ids: [], name: '' });

function openShare(row) {
    shareDialog.value = { visible: true, ids: [row.id], name: row.name };
}

function openBulkShare() {
    const ids = selection.value.map((r) => r.id);
    if (ids.length === 0) return;
    shareDialog.value = { visible: true, ids, name: '' };
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

function formatBytes(bytes) {
    if (!bytes || bytes < 0) return '—';
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let i = 0;
    let v = bytes;
    while (v >= 1024 && i < units.length - 1) {
        v /= 1024;
        i += 1;
    }
    return `${v.toFixed(v < 10 && i > 0 ? 2 : 1)} ${units[i]}`;
}

function storageLabel(row) {
    if (row.storage_path && /^storage\/profiles\//i.test(row.storage_path)) return t('profiles.storageLocal');
    const type = (row.storage_type || '').toUpperCase();
    if (type === 'S3') return t('profiles.storageS3');
    if (type === 'GOOGLE_DRIVE') return t('profiles.storageGdrive');
    return type || '—';
}

function storageBadgeType(row) {
    if (row.storage_path && /^storage\/profiles\//i.test(row.storage_path)) return 'success';
    if ((row.storage_type || '').toUpperCase() === 'S3') return 'primary';
    return 'info';
}

async function fetchGroups() {
    try {
        const { data } = await apiV1.get('/groups', { params: { per_page: 500 } });
        if (data?.success) {
            groups.value = data.data?.data || [];
        }
    } catch {
        // non-fatal
    }
}

async function fetchList() {
    loading.value = true;
    selection.value = [];
    try {
        const { data } = await apiV1.get('/profiles', {
            params: {
                search: search.value,
                per_page: perPage.value,
                page: page.value,
                is_deleted: tab.value === 'trash' ? 1 : 0,
                group_id: groupId.value || undefined,
            },
        });
        if (data?.success) {
            const payload = data.data || {};
            rows.value = (payload.data || []).map((p) => ({ ...p, _busy: false }));
            total.value = payload.total ?? rows.value.length;
            page.value = payload.current_page ?? page.value;
            await fetchSizes(rows.value);
        }
    } catch (err) {
        ElMessage.error(err?.response?.data?.message || t('common.error'));
    } finally {
        loading.value = false;
    }
}

async function fetchSizes(items) {
    const ids = items
        .filter((p) => p.storage_path && /^storage\/profiles\//i.test(p.storage_path))
        .map((p) => p.id);
    if (ids.length === 0) {
        sizes.value = {};
        return;
    }
    try {
        const { data } = await http.get('/profiles/storage-sizes', { params: { ids } });
        if (data?.success) {
            sizes.value = data.data || {};
        }
    } catch {
        sizes.value = {};
    }
}

function reload(resetPage = false) {
    if (resetPage) page.value = 1;
    fetchList();
}

function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        page.value = 1;
        fetchList();
    }, 300);
}

function onSizeChange(size) {
    perPage.value = size;
    page.value = 1;
    fetchList();
}

function onTabChange() {
    page.value = 1;
    search.value = '';
    sizes.value = {};
    fetchList();
}

async function resetStatus(row) {
    try {
        await ElMessageBox.confirm(
            t('profiles.resetStatusConfirm', { name: row.name }),
            t('common.confirm'),
            { type: 'warning' }
        );
    } catch {
        return;
    }
    row._busy = true;
    try {
        const { data } = await apiV1.post(`/profiles/update-status/${row.id}`, { status: 1 });
        if (data?.success) {
            ElMessage.success(t('common.success'));
            row.status = 1;
            row.using_by = null;
            row.current_user = null;
        } else {
            ElMessage.error(data?.message || t('common.error'));
        }
    } catch (err) {
        ElMessage.error(err?.response?.data?.message || t('common.error'));
    } finally {
        row._busy = false;
    }
}

async function softDelete(row) {
    try {
        await ElMessageBox.confirm(
            t('profiles.softDeleteConfirm', { name: row.name }),
            t('common.confirm'),
            { type: 'warning' }
        );
    } catch {
        return;
    }
    row._busy = true;
    try {
        const { data } = await apiV1.post(`/profiles/delete/${row.id}`, { mode: 'soft' });
        if (data?.success) {
            ElMessage.success(t('common.success'));
            await fetchList();
        } else {
            ElMessage.error(data?.message || t('common.error'));
        }
    } catch (err) {
        ElMessage.error(err?.response?.data?.message || t('common.error'));
    } finally {
        row._busy = false;
    }
}

async function hardDelete(row) {
    try {
        await ElMessageBox.confirm(
            t('profiles.hardDeleteConfirm', { name: row.name }),
            t('common.confirm'),
            { type: 'error', confirmButtonClass: 'el-button--danger' }
        );
    } catch {
        return;
    }
    row._busy = true;
    try {
        const { data } = await apiV1.post(`/profiles/delete/${row.id}`, { mode: 'hard' });
        if (data?.success) {
            ElMessage.success(t('common.success'));
            await fetchList();
        } else {
            ElMessage.error(data?.message || t('common.error'));
        }
    } catch (err) {
        ElMessage.error(err?.response?.data?.message || t('common.error'));
    } finally {
        row._busy = false;
    }
}

async function restore(row) {
    try {
        await ElMessageBox.confirm(
            t('profiles.restoreConfirm', { name: row.name }),
            t('common.confirm'),
            { type: 'success' }
        );
    } catch {
        return;
    }
    row._busy = true;
    try {
        const { data } = await apiV1.post(`/profiles/restore/${row.id}`);
        if (data?.success) {
            ElMessage.success(t('common.success'));
            await fetchList();
        } else {
            ElMessage.error(data?.message || t('common.error'));
        }
    } catch (err) {
        ElMessage.error(err?.response?.data?.message || t('common.error'));
    } finally {
        row._busy = false;
    }
}

async function bulkSoftDelete() {
    const ids = selection.value.map((r) => r.id);
    if (ids.length === 0) return;
    try {
        await ElMessageBox.confirm(
            t('profiles.bulkSoftDeleteConfirm', { count: ids.length }),
            t('common.confirm'),
            { type: 'warning' }
        );
    } catch {
        return;
    }
    await bulkAction('/profiles/bulk-delete', { profile_ids: ids, mode: 'soft' });
}

async function bulkHardDelete() {
    const ids = selection.value.map((r) => r.id);
    if (ids.length === 0) return;
    try {
        await ElMessageBox.confirm(
            t('profiles.bulkHardDeleteConfirm', { count: ids.length }),
            t('common.confirm'),
            { type: 'error', confirmButtonClass: 'el-button--danger' }
        );
    } catch {
        return;
    }
    await bulkAction('/profiles/bulk-delete', { profile_ids: ids, mode: 'hard' });
}

async function bulkRestore() {
    const ids = selection.value.map((r) => r.id);
    if (ids.length === 0) return;
    try {
        await ElMessageBox.confirm(
            t('profiles.bulkRestoreConfirm', { count: ids.length }),
            t('common.confirm'),
            { type: 'success' }
        );
    } catch {
        return;
    }
    await bulkAction('/profiles/bulk-restore', { profile_ids: ids });
}

async function bulkAction(url, payload) {
    bulkBusy.value = true;
    try {
        const { data } = await apiV1.post(url, payload);
        if (data?.success) {
            ElMessage.success(t('common.success'));
            await fetchList();
        } else {
            ElMessage.error(data?.message || t('common.error'));
        }
    } catch (err) {
        ElMessage.error(err?.response?.data?.message || t('common.error'));
    } finally {
        bulkBusy.value = false;
    }
}

onMounted(async () => {
    await fetchGroups();
    await fetchList();
});
</script>
