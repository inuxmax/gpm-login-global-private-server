import { createApp } from 'vue';
import { createPinia } from 'pinia';
import ElementPlus from 'element-plus';
import 'element-plus/dist/index.css';
import elEnLocale from 'element-plus/es/locale/lang/en';
import elViLocale from 'element-plus/es/locale/lang/vi';
import elZhLocale from 'element-plus/es/locale/lang/zh-cn';
import * as ElementPlusIconsVue from '@element-plus/icons-vue';

import App from './App.vue';
import router from './router';
import i18n from './i18n';

const app = createApp(App);

const savedLocale = localStorage.getItem('admin_locale') || 'vi';
const elLocaleMap = {
    en: elEnLocale,
    vi: elViLocale,
    zh: elZhLocale,
};

app.use(createPinia());
app.use(router);
app.use(i18n);
app.use(ElementPlus, { locale: elLocaleMap[savedLocale] || elViLocale });

for (const [name, component] of Object.entries(ElementPlusIconsVue)) {
    app.component(name, component);
}

app.mount('#app');
