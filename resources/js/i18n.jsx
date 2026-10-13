import { router, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

export function useTranslations() {
    const { locale, translations } = usePage().props;

    useEffect(() => {
        document.documentElement.lang = locale;
    }, [locale]);

    const translate = (key, replacements = {}) => {
        const value = key.split('.').reduce((current, segment) => current?.[segment], translations);
        const text = typeof value === 'string' ? value : key;

        return Object.entries(replacements).reduce(
            (translated, [name, replacement]) => translated.replaceAll(`:${name}`, String(replacement)),
            text,
        );
    };

    return { locale, t: translate };
}

export function LocaleSwitcher({ className = '' }) {
    const { locale, t } = useTranslations();

    return (
        <label className={`locale-switcher ${className}`.trim()}>
            <span className="sr-only">{t('language.label')}</span>
            <select
                aria-label={t('language.label')}
                value={locale}
                onChange={(event) => router.post('/locale', { locale: event.target.value }, { preserveScroll: true })}
            >
                <option value="my">{t('language.my')}</option>
                <option value="en">{t('language.en')}</option>
                <option value="th">{t('language.th')}</option>
            </select>
        </label>
    );
}
