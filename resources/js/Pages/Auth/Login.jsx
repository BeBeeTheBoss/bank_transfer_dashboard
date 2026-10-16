import { Head, useForm } from '@inertiajs/react';
import { LocaleSwitcher, useTranslations } from '../../i18n';

function Mark() {
    return (
        <div className="brand-mark" aria-hidden="true">
            <svg viewBox="0 0 32 32"><path d="M8 9.5h16M20 5l4 4.5-4 4.5M24 22.5H8M12 18l-4 4.5 4 4.5" /></svg>
        </div>
    );
}

export default function Login() {
    const { t } = useTranslations();
    const { data, setData, post, processing, errors } = useForm({
        emp_code: '',
        password: '',
        remember: false,
    });

    const submit = (event) => {
        event.preventDefault();
        post('/login', { preserveScroll: true });
    };

    return (
        <>
            <Head title={t('login.title')} />
            <main className="login-shell">
                <section className="login-card">
                    <div className="login-card-head">
                        <div className="login-brand"><Mark /><span>Bank Transfer Dashboard</span></div>
                        <LocaleSwitcher />
                    </div>
                    <div className="login-copy">
                        <span className="eyebrow">{t('login.eyebrow')}</span>
                        <h1>{t('login.heading')}</h1>
                        <p>{t('login.description')}</p>
                    </div>

                    <form onSubmit={submit} className="login-form">
                        <label>
                            <span>{t('login.emp_code')}</span>
                            <input
                                type="text"
                                value={data.emp_code}
                                onChange={(event) => setData('emp_code', event.target.value)}
                                placeholder={t('login.emp_code_placeholder')}
                                autoComplete="username"
                                autoFocus
                                required
                            />
                        </label>
                        <label>
                            <span>{t('login.password')}</span>
                            <input
                                type="password"
                                value={data.password}
                                onChange={(event) => setData('password', event.target.value)}
                                placeholder={t('login.password_placeholder')}
                                autoComplete="current-password"
                                required
                            />
                        </label>
                        {errors.emp_code && <p className="form-error" role="alert">{errors.emp_code}</p>}
                        <label className="remember-row">
                            <input type="checkbox" checked={data.remember} onChange={(event) => setData('remember', event.target.checked)} />
                            <span>{t('login.remember')}</span>
                        </label>
                        <button type="submit" className="primary-button" disabled={processing}>
                            {processing ? t('login.submitting') : t('login.submit')}
                            {!processing && <span aria-hidden="true">→</span>}
                        </button>
                    </form>

                    <p className="login-footnote">{t('login.footnote')}</p>
                </section>
            </main>
        </>
    );
}
