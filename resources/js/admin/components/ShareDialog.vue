<template>
    <el-dialog
        v-model="visible"
        :title="dialogTitle"
        width="520px"
        :close-on-click-modal="false"
        append-to-body
        @close="onClose"
    >
        <!-- Add new share -->
        <div style="padding: 4px 0 12px">
            <div style="font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 8px">
                {{ t('share.addShare') }}
            </div>
            <div style="display: flex; gap: 8px; align-items: center">
                <el-select
                    v-model="form.userId"
                    :placeholder="t('share.pickUser')"
                    filterable
                    remote
                    reserve-keyword
                    :remote-method="searchUsers"
                    :loading="userSearching"
                    style="flex: 1"
                    clearable
                >
                    <el-option
                        v-for="u in userOptions"
                        :key="u.id"
                        :value="u.id"
                        :label="u.display_name ? `${u.display_name} (${u.email})` : u.email"
                    />
                </el-select>
                <el-select v-model="form.role" style="width: 130px">
                    <el-option :label="t('share.roleFull')" value="FULL" />
                    <el-option :label="t('share.roleEdit')" value="EDIT" />
                    <el-option :label="t('share.roleView')" value="VIEW" />
                </el-select>
                <el-button type="primary" :loading="adding" @click="addShare">
                    <el-icon style="margin-right: 3px"><Plus /></el-icon>
                    {{ t('share.addBtn') }}
                </el-button>
            </div>
        </div>

        <!-- Current shares (only in single mode) -->
        <template v-if="isSingle">
            <el-divider style="margin: 8px 0 14px" />
            <div style="font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 8px">
                {{ t('share.currentShares') }}
            </div>
            <div v-loading="loadingShares" style="min-height: 80px">
                <el-empty
                    v-if="!loadingShares && shares.length === 0"
                    :description="t('share.noShares')"
                    :image-size="60"
                />
                <div v-else style="display: flex; flex-direction: column; gap: 6px">
                    <div
                        v-for="s in shares"
                        :key="s.id"
                        style="display: flex; align-items: center; gap: 10px; padding: 6px 10px; background: #f9fafb; border-radius: 6px"
                    >
                        <div style="flex: 1">
                            <div style="font-weight: 500; font-size: 13px">
                                {{ s.display_name || s.email }}
                            </div>
                            <div style="font-size: 11px; color: #6b7280">{{ s.email }}</div>
                        </div>
                        <el-tag size="small" :type="roleTagType(s.role)" disable-transitions>
                            {{ roleLabel(s.role) }}
                        </el-tag>
                        <el-button
                            size="small"
                            type="danger"
                            plain
                            :loading="s._busy"
                            @click="removeShare(s)"
                        >
                            <el-icon><Delete /></el-icon>
                        </el-button>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <el-button @click="visible = false">{{ t('common.close') }}</el-button>
        </template>
    </el-dialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { ElMessage, ElMessageBox } from 'element-plus';
import { http } from '../api/http';

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    // 'group' | 'profile' | 'proxy'
    type: { type: String, required: true },
    // Single id OR array of ids
    ids: { type: [Array, String], required: true },
    // Display label: a name for single, or we compute count for bulk
    name: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue', 'changed']);

const { t } = useI18n();

const visible = computed({
    get: () => props.modelValue,
    set: (v) => emit('update:modelValue', v),
});

const idList = computed(() => (Array.isArray(props.ids) ? props.ids : [props.ids]));
const isSingle = computed(() => idList.value.length === 1);

const dialogTitle = computed(() =>
    isSingle.value
        ? t('share.titleSingle', { name: props.name || '' })
        : t('share.titleBulk', { count: idList.value.length })
);

const form = ref({ userId: '', role: 'VIEW' });
const shares = ref([]);
const loadingShares = ref(false);
const userOptions = ref([]);
const userSearching = ref(false);
const adding = ref(false);
let userSearchTimer = null;

function roleLabel(role) {
    if (role === 'FULL') return t('share.roleFull');
    if (role === 'EDIT') return t('share.roleEdit');
    return t('share.roleView');
}

function roleTagType(role) {
    if (role === 'FULL') return 'danger';
    if (role === 'EDIT') return 'warning';
    return 'info';
}

const PREFIX = { group: 'groups', profile: 'profiles', proxy: 'proxies' };

function endpointList() {
    return `/${PREFIX[props.type]}/get-share-users/${idList.value[0]}`;
}

async function loadShares() {
    if (!isSingle.value) return;
    loadingShares.value = true;
    try {
        const { data } = await http.get(endpointList());
        if (data?.success) {
            const payload = data.data;
            // Laravel paginator or plain array — normalize
            const list = Array.isArray(payload) ? payload : (payload?.data || []);
            shares.value = list.map((s) => ({ ...s, _busy: false }));
        }
    } catch {
        // silent
    } finally {
        loadingShares.value = false;
    }
}

function searchUsers(query) {
    clearTimeout(userSearchTimer);
    userSearchTimer = setTimeout(async () => {
        userSearching.value = true;
        try {
            const { data } = await http.get('/user-search', { params: { search: query || '', per_page: 20 } });
            if (data?.success) {
                const payload = data.data;
                const list = Array.isArray(payload) ? payload : (payload?.data || []);
                // Hide users that already have a share (single mode only) + ADMIN users
                const existing = new Set(shares.value.map((s) => s.id));
                userOptions.value = list.filter((u) => !existing.has(u.id));
            }
        } catch {
            userOptions.value = [];
        } finally {
            userSearching.value = false;
        }
    }, 250);
}

async function addShare() {
    if (!form.value.userId) {
        ElMessage.warning(t('share.pickUserFirst'));
        return;
    }

    adding.value = true;
    try {
        const url = buildShareUrl();
        const payload = buildSharePayload();
        const { data } = await http.post(url, payload);

        if (data?.success) {
            ElMessage.success(t('common.success'));
            form.value.userId = '';
            userOptions.value = [];
            await loadShares();
            emit('changed');
        } else {
            ElMessage.error(mapErrorMessage(data?.message));
        }
    } catch (err) {
        ElMessage.error(mapErrorMessage(err?.response?.data?.message));
    } finally {
        adding.value = false;
    }
}

function buildShareUrl() {
    // Groups: single share only (no bulk endpoint)
    if (props.type === 'group') {
        return `/groups/share/${idList.value[0]}`;
    }
    // Profile: single or bulk
    if (props.type === 'profile') {
        return isSingle.value
            ? `/profiles/share/${idList.value[0]}`
            : '/profiles/bulk-share';
    }
    // Proxy: always bulk-share (no single endpoint)
    return '/proxies/bulk-share';
}

function buildSharePayload() {
    const base = { user_id: form.value.userId, role: form.value.role };
    if (props.type === 'profile' && !isSingle.value) {
        return { ...base, profile_ids: idList.value };
    }
    if (props.type === 'proxy') {
        return { ...base, proxy_ids: idList.value };
    }
    return base;
}

async function removeShare(share) {
    const name = share.display_name || share.email;
    const msg = isSingle.value
        ? t('share.removeConfirm', { name })
        : t('share.bulkRemoveConfirm', { name, count: idList.value.length });

    try {
        await ElMessageBox.confirm(msg, t('common.confirm'), { type: 'warning' });
    } catch {
        return;
    }

    share._busy = true;
    try {
        const url = buildRemoveUrl();
        const payload = buildRemovePayload(share.id);
        const { data } = await http.post(url, payload);

        if (data?.success) {
            ElMessage.success(t('common.success'));
            await loadShares();
            emit('changed');
        } else {
            ElMessage.error(mapErrorMessage(data?.message));
        }
    } catch (err) {
        ElMessage.error(mapErrorMessage(err?.response?.data?.message));
    } finally {
        share._busy = false;
    }
}

function buildRemoveUrl() {
    // All single-row removes use the /{prefix}/remove-share/{id} pattern
    return `/${PREFIX[props.type]}/remove-share/${idList.value[0]}`;
}

function buildRemovePayload(userId) {
    return { user_id: userId };
}

function mapErrorMessage(code) {
    const map = {
        cannot_share_all_group: t('share.cannotShareDefault'),
        no_need_set_admin_permission: t('share.cannotShareAdmin'),
        user_not_found: t('common.error'),
        not_have_permission: t('common.error'),
    };
    return map[code] || code || t('common.error');
}

function onClose() {
    form.value.userId = '';
    form.value.role = 'VIEW';
    userOptions.value = [];
    shares.value = [];
}

watch(
    () => props.modelValue,
    (v) => {
        if (v) {
            shares.value = [];
            userOptions.value = [];
            form.value = { userId: '', role: 'VIEW' };
            if (isSingle.value) loadShares();
            searchUsers('');
        }
    }
);
</script>
