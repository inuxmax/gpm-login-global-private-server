<template>
    <div class="page-card">
        <div class="page-card-title" style="justify-content: space-between; display: flex">
            <div style="display: flex; align-items: center; gap: 8px">
                <el-icon><User /></el-icon>
                {{ t('users.title') }}
            </div>
            <div style="display: flex; gap: 8px">
                <el-input
                    v-model="search"
                    :placeholder="t('common.searchPlaceholder')"
                    size="default"
                    style="width: 260px"
                    clearable
                    @input="onSearch"
                >
                    <template #prefix>
                        <el-icon><Search /></el-icon>
                    </template>
                </el-input>
                <el-button @click="fetchUsers">
                    <el-icon><Refresh /></el-icon>
                </el-button>
            </div>
        </div>

        <el-table
            v-loading="loading"
            :data="users"
            stripe
            border
            style="width: 100%"
            :empty-text="t('common.noData')"
        >
            <el-table-column prop="email" :label="t('users.email')" min-width="200" />
            <el-table-column prop="display_name" :label="t('users.displayName')" min-width="160" />
            <el-table-column prop="system_role" :label="t('users.systemRole')" width="110">
                <template #default="{ row }">
                    <el-tag
                        :type="roleTagType(row.system_role)"
                        size="small"
                        disable-transitions
                    >
                        {{ row.system_role }}
                    </el-tag>
                </template>
            </el-table-column>
            <el-table-column :label="t('users.status')" width="140">
                <template #default="{ row }">
                    <el-tag
                        :type="row.is_active ? 'success' : 'danger'"
                        size="small"
                        disable-transitions
                    >
                        <el-icon style="vertical-align: middle; margin-right: 3px">
                            <CircleCheck v-if="row.is_active" />
                            <CircleClose v-else />
                        </el-icon>
                        {{ row.is_active ? t('users.active') : t('users.inactive') }}
                    </el-tag>
                </template>
            </el-table-column>
            <el-table-column :label="t('common.actions')" width="280" fixed="right">
                <template #default="{ row }">
                    <el-button
                        size="small"
                        :type="row.is_active ? 'danger' : 'success'"
                        plain
                        :loading="row._toggling"
                        @click="toggleActive(row)"
                    >
                        {{ row.is_active ? t('users.deactivate') : t('users.activate') }}
                    </el-button>
                    <el-button
                        size="small"
                        type="warning"
                        plain
                        :loading="row._resetting"
                        @click="resetPassword(row)"
                    >
                        <el-icon style="margin-right: 3px"><Key /></el-icon>
                        {{ t('users.resetPassword') }}
                    </el-button>
                </template>
            </el-table-column>
        </el-table>

        <el-dialog
            v-model="pwDialog.visible"
            :title="t('users.newPasswordTitle')"
            width="420px"
            :close-on-click-modal="false"
        >
            <div style="color: #4b5563; margin-bottom: 10px">{{ pwDialog.email }}</div>
            <el-input
                v-model="pwDialog.password"
                readonly
                size="large"
                style="font-family: monospace; font-size: 16px"
            >
                <template #append>
                    <el-button @click="copyPassword">
                        <el-icon><CopyDocument /></el-icon>
                    </el-button>
                </template>
            </el-input>
            <el-alert
                type="warning"
                :closable="false"
                style="margin-top: 12px"
                :title="t('users.newPasswordHint')"
            />
            <template #footer>
                <el-button type="primary" @click="pwDialog.visible = false">
                    {{ t('common.close') }}
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

const { t } = useI18n();

const users = ref([]);
const loading = ref(false);
const search = ref('');
let searchTimer = null;

const pwDialog = reactive({
    visible: false,
    email: '',
    password: '',
});

function roleTagType(role) {
    switch (role) {
        case 'ADMIN': return 'danger';
        case 'MOD': return 'warning';
        default: return 'info';
    }
}

async function fetchUsers() {
    loading.value = true;
    try {
        const { data } = await http.get('/users', { params: { search: search.value } });
        if (data?.success) {
            users.value = (data.data || []).map((u) => ({ ...u, _toggling: false, _resetting: false }));
        }
    } catch (err) {
        ElMessage.error(err?.response?.data?.message || t('common.error'));
    } finally {
        loading.value = false;
    }
}

function onSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(fetchUsers, 300);
}

async function toggleActive(row) {
    row._toggling = true;
    try {
        const { data } = await http.post(`/users/${row.id}/toggle-active`);
        if (data?.success && data.data) {
            row.is_active = data.data.is_active;
            ElMessage.success(t('common.success'));
        } else {
            ElMessage.error(data?.message || t('common.error'));
        }
    } catch (err) {
        ElMessage.error(err?.response?.data?.message || t('common.error'));
    } finally {
        row._toggling = false;
    }
}

async function resetPassword(row) {
    try {
        await ElMessageBox.confirm(
            t('users.resetPasswordConfirm', { name: row.display_name || row.email }),
            t('common.confirm'),
            { type: 'warning' }
        );
    } catch {
        return;
    }
    row._resetting = true;
    try {
        const { data } = await http.post(`/users/${row.id}/reset-password`);
        if (data?.success) {
            pwDialog.email = row.email;
            pwDialog.password = data.newPassword;
            pwDialog.visible = true;
        } else {
            ElMessage.error(data?.message || t('common.error'));
        }
    } catch (err) {
        ElMessage.error(err?.response?.data?.message || t('common.error'));
    } finally {
        row._resetting = false;
    }
}

async function copyPassword() {
    try {
        await navigator.clipboard.writeText(pwDialog.password);
        ElMessage.success(t('common.copied'));
    } catch {
        ElMessage.error(t('common.error'));
    }
}

onMounted(fetchUsers);
</script>
