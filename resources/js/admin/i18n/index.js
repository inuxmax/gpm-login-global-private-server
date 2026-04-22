import { createI18n } from 'vue-i18n';
import en from './en';
import vi from './vi';
import zh from './zh';

const saved = localStorage.getItem('admin_locale') || 'vi';

const i18n = createI18n({
    legacy: false,
    locale: saved,
    fallbackLocale: 'en',
    messages: { en, vi, zh },
});

export default i18n;
