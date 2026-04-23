<template>
    <div class="page-card">
        <div class="page-card-title" style="justify-content: space-between; display: flex">
            <div style="display: flex; align-items: center; gap: 8px">
                <el-icon><Connection /></el-icon>
                {{ t('menu.proxies') }}
            </div>
            <div style="display: flex; gap: 8px; align-items: center">
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
                <el-button @click="fetchList()">
                    <el-icon><Refresh /></el-icon>
                </el-button>
                <el-button type="primary" @click="openAdd">
                    <el-icon style="margin-right: 4px"><Plus /></el-icon>
                    {{ t('proxies.addBulk') }}
                </el-button>
            </div>
        </div>

        <div
            v-if="selection.length > 0"
            style="display: flex; gap: 8px; align-items: center; margin-bottom: 12px; padding: 8px 12px; background: #f3f4f6; border-radius: 6px"
        >
            <el-tag type="info" disable-transitions>
                {{ t('profiles.selected', { count: selection.length }) }}
            </el-tag>
            <el-button type="info" plain size="default" @click="openBulkShare">
                <el-icon style="margin-right: 3px"><Share /></el-icon>
                {{ t('share.title') }}
            </el-button>
            <el-button type="danger" plain size="default" :loading="bulkBusy" @click="bulkDelete">
                <el-icon style="margin-right: 3px"><Delete /></el-icon>
                {{ t('common.delete') }}
            </el-button>
        </div>

        <el-table
            v-loading="loading"
            :data="rows"
            stripe
            border
            style="width: 100%"
            :empty-text="t('proxies.empty')"
            @selection-change="(v) => (selection = v)"
        >
            <el-table-column type="selection" width="44" />
            <el-table-column :label="t('proxies.rawProxy')" min-width="320">
                <template #default="{ row }">
                    <div style="display: flex; flex-direction: column; line-height: 1.3">
                        <div style="display: flex; align-items: center; gap: 6px">
                            <el-tag
                                v-if="parseProxy(row.raw_proxy).protocol"
                                size="small"
                                :type="protocolTagType(parseProxy(row.raw_proxy).protocol)"
                                disable-transitions
                            >
                                {{ parseProxy(row.raw_proxy).protocol }}
                            </el-tag>
                            <span style="font-family: monospace; font-weight: 500">
                                {{ displayHostPort(row.raw_proxy) }}
                            </span>
                            <el-icon
                                v-if="parseProxy(row.raw_proxy).hasAuth"
                                style="color: #f59e0b"
                                :title="'Has authentication'"
                            >
                                <Lock />
                            </el-icon>
                        </div>
                        <span style="font-size: 11px; color: #9ca3af; font-family: monospace">
                            {{ row.raw_proxy }}
                        </span>
                    </div>
                </template>
            </el-table-column>
            <el-table-column :label="t('proxies.tags')" min-width="180">
                <template #default="{ row }">
                    <span v-if="!row.tags?.length" style="color: #9ca3af">—</span>
                    <el-tag
                        v-for="tag in row.tags"
                        :key="tag.id"
                        size="small"
                        :style="tagStyle(tag)"
                        disable-transitions
                        style="margin-right: 4px"
                    >
                        {{ tag.name }}
                    </el-tag>
                </template>
            </el-table-column>
            <el-table-column :label="t('proxies.createdAt')" width="170">
                <template #default="{ row }">
                    <span style="font-size: 12px; color: #6b7280">{{ formatTime(row.created_at) }}</span>
                </template>
            </el-table-column>
            <el-table-column :label="t('common.actions')" width="220" fixed="right">
                <template #default="{ row }">
                    <el-button size="small" type="info" plain @click="openShare(row)">
                        <el-icon><Share /></el-icon>
                    </el-button>
                    <el-button size="small" type="primary" plain @click="openEdit(row)">
                        <el-icon style="margin-right: 3px"><Edit /></el-icon>
                        {{ t('common.edit') }}
                    </el-button>
                    <el-button size="small" type="danger" plain :loading="row._busy" @click="deleteRow(row)">
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
                @current-change="fetchList"
                @size-change="onSizeChange"
            />
        </div>

        <ShareDialog
            v-model="shareDialog.visible"
            type="proxy"
            :ids="shareDialog.ids"
            :name="shareDialog.name"
        />

        <el-dialog
            v-model="addDialog.visible"
            :title="t('proxies.addBulk')"
            width="560px"
            :close-on-click-modal="false"
            append-to-body
        >
            <div style="padding: 4px 0">
                <label
                    style="display: block; font-size: 13px; color: #374151; margin-bottom: 6px; font-weight: 500"
                >
                    {{ t('proxies.rawProxy') }}
                </label>
                <el-input
                    v-model="addDialog.text"
                    type="textarea"
                    :rows="10"
                    :placeholder="t('proxies.bulkPlaceholder')"
                    resize="vertical"
                    input-style="font-family: monospace; font-size: 13px"
                />
            </div>
            <template #footer>
                <el-button @click="addDialog.visible = false">{{ t('common.cancel') }}</el-button>
                <el-button type="primary" :loading="addDialog.saving" @click="submitAdd">
                    {{ t('common.save') }}
                </el-button>
            </template>
        </el-dialog>

        <el-dialog
            v-model="editDialog.visible"
            :title="t('proxies.edit')"
            width="480px"
            :close-on-click-modal="false"
            append-to-body
        >
            <div style="padding: 4px 0">
                <label
                    style="display: block; font-size: 13px; color: #374151; margin-bottom: 6px; font-weight: 500"
                >
                    {{ t('proxies.rawProxy') }}
                </label>
                <el-input
                    v-model="editDialog.raw"
                    :placeholder="t('proxies.rawProxyPlaceholder')"
                    input-style="font-family: monospace"
                    clearable
                    autofocus
                />
            </div>
            <template #footer>
                <el-button @click="editDialog.visible = false">{{ t('common.cancel') }}</el-button>
                <el-button type="primary" :loading="editDialog.saving" @click="submitEdit">
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
import { http } from '../api/http';
import ShareDialog from '../components/ShareDialog.vue';

const { t } = useI18n();

const loading = ref(false);
const rows = ref([]);
const search = ref('');
const page = ref(1);
const perPage = ref(20);
const total = ref(0);
const selection = ref([]);
const bulkBusy = ref(false);
let searchTimer = null;

const addDialog = reactive({ visible: false, text: '', saving: false });
const editDialog = reactive({ visible: false, id: null, raw: '', saving: false });
const shareDialog = ref({ visible: false, ids: [], name: '' });

function openShare(row) {
    shareDialog.value = {
        visible: true,
        ids: [row.id],
        name: displayHostPort(row.raw_proxy),
    };
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

function parseProxy(raw) {
    const out = { protocol: null, hasAuth: false, host: null, port: null };
    if (!raw) return out;
    let s = String(raw).trim();
    const p = s.match(/^(\w+):\/\/(.+)$/);
    if (p) {
        out.protocol = p[1].toUpperCase();
        s = p[2];
    }
    const a = s.match(/^([^:@]+):([^@]+)@(.+)$/);
    if (a) {
        out.hasAuth = true;
        s = a[3];
    }
    const h = s.match(/^([^:]+):(\d+)$/);
    if (h) {
        out.host = h[1];
        out.port = h[2];
    }
    return out;
}

function displayHostPort(raw) {
    const p = parseProxy(raw);
    if (p.host && p.port) return `${p.host}:${p.port}`;
    return raw || '';
}

function protocolTagType(protocol) {
    switch ((protocol || '').toUpperCase()) {
        case 'HTTP': return 'info';
        case 'HTTPS': return 'success';
        case 'SOCKS4': return 'warning';
        case 'SOCKS5': return 'primary';
        default: return 'info';
    }
}

function tagStyle(tag) {
    if (!tag?.color) return {};
    return { backgroundColor: tag.color, color: '#fff', borderColor: tag.color };
}

async function fetchList() {
    loading.value = true;
    selection.value = [];
    try {
        const { data } = await http.get('/proxies', {
            params: { search: search.value, per_page: perPage.value, page: page.value },
        });
        if (data?.success) {
            const payload = data.data || {};
            rows.value = (payload.data || []).map((p) => ({ ...p, _busy: false }));
            total.value = payload.total ?? rows.value.length;
            page.value = payload.current_page ?? page.value;
        }
    } catch (err) {
        ElMessage.error(err?.response?.data?.message || t('common.error'));
    } finally {
        loading.value = false;
    }
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

function openAdd() {
    addDialog.text = '';
    addDialog.visible = true;
}

async function submitAdd() {
    const lines = addDialog.text
        .split(/\r?\n/)
        .map((x) => x.trim())
        .filter(Boolean);

    if (lines.length === 0) {
        ElMessage.warning(t('proxies.rawProxyRequired'));
        return;
    }

    addDialog.saving = true;
    try {
        const proxies = lines.map((raw) => ({ raw_proxy: raw }));
        const { data } = await http.post('/proxies/bulk-create', { proxies });
        if (data?.success) {
            ElMessage.success(t('proxies.addedCount', { count: lines.length }));
            addDialog.visible = false;
            await fetchList();
        } else {
            ElMessage.error(data?.message || t('common.error'));
        }
    } catch (err) {
        ElMessage.error(err?.response?.data?.message || t('common.error'));
    } finally {
        addDialog.saving = false;
    }
}

function openEdit(row) {
    editDialog.id = row.id;
    editDialog.raw = row.raw_proxy;
    editDialog.visible = true;
}

async function submitEdit() {
    const raw = editDialog.raw.trim();
    if (!raw) {
        ElMessage.warning(t('proxies.rawProxyRequired'));
        return;
    }
    editDialog.saving = true;
    try {
        const { data } = await http.post(`/proxies/update/${editDialog.id}`, { raw_proxy: raw });
        if (data?.success) {
            ElMessage.success(t('common.success'));
            editDialog.visible = false;
            await fetchList();
        } else {
            ElMessage.error(data?.message || t('common.error'));
        }
    } catch (err) {
        ElMessage.error(err?.response?.data?.message || t('common.error'));
    } finally {
        editDialog.saving = false;
    }
}

async function deleteRow(row) {
    try {
        await ElMessageBox.confirm(
            t('proxies.deleteConfirm', { raw: displayHostPort(row.raw_proxy) }),
            t('common.confirm'),
            { type: 'warning' }
        );
    } catch {
        return;
    }
    row._busy = true;
    try {
        const { data } = await http.post(`/proxies/delete/${row.id}`);
        if (data?.success) {
            ElMessage.success(t('common.success'));
            if (rows.value.length === 1 && page.value > 1) page.value -= 1;
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

async function bulkDelete() {
    const ids = selection.value.map((r) => r.id);
    if (ids.length === 0) return;
    try {
        await ElMessageBox.confirm(
            t('proxies.bulkDeleteConfirm', { count: ids.length }),
            t('common.confirm'),
            { type: 'warning' }
        );
    } catch {
        return;
    }
    bulkBusy.value = true;
    try {
        const { data } = await http.post('/proxies/bulk-delete', { proxy_ids: ids });
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

onMounted(fetchList);
</script>
