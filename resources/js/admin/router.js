import { createRouter, createWebHistory } from 'vue-router';
import MainLayout from './layouts/MainLayout.vue';
import SystemSettings from './pages/SystemSettings.vue';
import Users from './pages/Users.vue';
import Groups from './pages/Groups.vue';
import Placeholder from './pages/Placeholder.vue';

const routes = [
    {
        path: '/admin/app',
        component: MainLayout,
        children: [
            { path: '', redirect: '/admin/app/system' },
            {
                path: 'system',
                name: 'system',
                component: SystemSettings,
                meta: { titleKey: 'menu.systemSettings', icon: 'Setting' },
            },
            {
                path: 'users',
                name: 'users',
                component: Users,
                meta: { titleKey: 'menu.users', icon: 'User' },
            },
            {
                path: 'groups',
                name: 'groups',
                component: Groups,
                meta: { titleKey: 'menu.groups', icon: 'Collection' },
            },
            {
                path: 'profiles',
                name: 'profiles',
                component: Placeholder,
                meta: { titleKey: 'menu.profiles', icon: 'User', phase: 3 },
            },
            {
                path: 'proxies',
                name: 'proxies',
                component: Placeholder,
                meta: { titleKey: 'menu.proxies', icon: 'Connection', phase: 4 },
            },
        ],
    },
    { path: '/:pathMatch(.*)*', redirect: '/admin/app' },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;
