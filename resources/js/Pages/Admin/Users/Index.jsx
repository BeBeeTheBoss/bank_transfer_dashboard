import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { LocaleSwitcher, useTranslations } from '../../../i18n';

function Icon({ name, size = 17 }) {
    const paths = {
        bars: <path d="M5 20v-6M12 20V5M19 20V10" />,
        close: <path d="m6 6 12 12M18 6 6 18" />,
        edit: <><path d="m4 20 4.5-1 10-10a2.1 2.1 0 0 0-3-3l-10 10L4 20Z" /><path d="m14 7 3 3" /></>,
        logout: <><path d="M10 5H5v14h5M14 8l4 4-4 4M18 12H9" /></>,
        plus: <path d="M12 5v14M5 12h14" />,
        trash: <><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13" /></>,
        users: <><path d="M16 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 20v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" /></>,
    };

    return <svg className="icon" width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">{paths[name]}</svg>;
}

function UserTableSkeleton() {
    return <tbody className="data-table-skeleton" aria-hidden="true">{Array.from({ length: 5 }, (_, row) => <tr key={row}>{Array.from({ length: 5 }, (_, column) => <td key={column}><span className={`data-skeleton user-cell-skeleton cell-${column + 1}`} /></td>)}</tr>)}</tbody>;
}

function UserFormModal({ currentUserId, onClose, user, userTypes }) {
    const { t } = useTranslations();
    const isEditing = Boolean(user);
    const { data, setData, post, put, processing, errors } = useForm({
        name: user?.name || '',
        email: user?.email || '',
        type: user?.type || 'user',
        password: '',
        password_confirmation: '',
    });

    const submit = (event) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: onClose };

        if (isEditing) {
            put(`/admin/users/${user.id}`, options);
            return;
        }

        post('/admin/users', options);
    };

    return (
        <div className="user-modal-backdrop" onMouseDown={(event) => event.target === event.currentTarget && !processing && onClose()}>
            <section className="user-modal" role="dialog" aria-modal="true" aria-labelledby="user-form-title">
                <header><div><span className="user-modal-icon"><Icon name="users" size={20} /></span><div><h2 id="user-form-title">{t(isEditing ? 'users.edit_title' : 'users.create_title')}</h2><p>{t('users.form_description')}</p></div></div><button type="button" className="square-button" onClick={onClose} disabled={processing} aria-label={t('detail.close')}><Icon name="close" /></button></header>
                <form onSubmit={submit} className="user-form">
                    <label><span>{t('users.name')}</span><input value={data.name} onChange={(event) => setData('name', event.target.value)} autoComplete="name" autoFocus required />{errors.name && <small className="form-error">{errors.name}</small>}</label>
                    <label><span>{t('users.email')}</span><input type="email" value={data.email} onChange={(event) => setData('email', event.target.value)} autoComplete="email" required />{errors.email && <small className="form-error">{errors.email}</small>}</label>
                    <label><span>{t('users.type')}</span><select value={data.type} onChange={(event) => setData('type', event.target.value)}>{userTypes.map((type) => <option key={type} value={type} disabled={user?.id === currentUserId && type !== 'admin'}>{t(`users.role_${type}`)}</option>)}</select>{errors.type && <small className="form-error">{errors.type}</small>}</label>
                    <div className="user-form-grid">
                        <label><span>{t('users.password')}</span><input type="password" value={data.password} onChange={(event) => setData('password', event.target.value)} placeholder={t('users.password_placeholder')} autoComplete="new-password" required={!isEditing} />{errors.password && <small className="form-error">{errors.password}</small>}</label>
                        <label><span>{t('users.password_confirmation')}</span><input type="password" value={data.password_confirmation} onChange={(event) => setData('password_confirmation', event.target.value)} autoComplete="new-password" required={!isEditing} /></label>
                    </div>
                    {isEditing && <p className="user-form-hint">{t('users.password_edit_hint')}</p>}
                    <footer><button type="button" className="reset-button" onClick={onClose} disabled={processing}>{t('users.cancel')}</button><button type="submit" className="apply-button" disabled={processing}>{processing ? t('users.saving') : t(isEditing ? 'users.save' : 'users.create')}</button></footer>
                </form>
            </section>
        </div>
    );
}

function formatDate(value, locale) {
    return new Intl.DateTimeFormat({ my: 'my-MM', th: 'th-TH' }[locale] || 'en-GB', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value));
}

export default function UserIndex({ users, userTypes }) {
    const { auth, flash } = usePage().props;
    const { locale, t } = useTranslations();
    const [editingUser, setEditingUser] = useState(null);
    const [formOpen, setFormOpen] = useState(false);
    const [dataLoading, setDataLoading] = useState(false);
    const [deletingId, setDeletingId] = useState(null);

    const openCreate = () => { setEditingUser(null); setFormOpen(true); };
    const openEdit = (user) => { setEditingUser(user); setFormOpen(true); };
    const deleteUser = (user) => {
        if (!window.confirm(t('users.delete_confirm', { name: user.name }))) return;

        router.delete(`/admin/users/${user.id}`, {
            preserveScroll: true,
            onStart: () => setDeletingId(user.id),
            onFinish: () => setDeletingId(null),
        });
    };

    return (
        <>
            <Head title={t('users.title')} />
            <div className="dashboard-shell">
                <header className="dashboard-header">
                    <Link href="/" className="dashboard-brand"><span><Icon name="bars" size={20} /></span>{t('report.dashboard_name')}</Link>
                    <nav className="dashboard-nav" aria-label="Primary"><Link href="/">{t('navigation.reports')}</Link><Link href="/admin/users" className="active">{t('navigation.users')}</Link></nav>
                    <div className="header-actions"><LocaleSwitcher /><div className="header-user"><span>{(auth.user?.name || 'A').charAt(0).toUpperCase()}</span><div><strong>{auth.user?.name}</strong><small>{t('report.admin_role')}</small></div></div><Link href="/logout" method="post" as="button" className="square-button" title={t('report.sign_out')}><Icon name="logout" /></Link></div>
                </header>

                <main className="dashboard-main users-main">
                    <section className="users-heading"><div><h1>{t('users.heading')}</h1><p>{t('users.description')}</p></div><button type="button" className="apply-button add-user-button" onClick={openCreate}><Icon name="plus" />{t('users.add')}</button></section>
                    {flash?.success && <div className="flash-message success">{flash.success}</div>}
                    {flash?.error && <div className="flash-message error">{flash.error}</div>}

                    <section className="users-card">
                        <header><div><h2>{t('navigation.users')}</h2><span>{t('users.total', { count: users.total })}</span></div></header>
                        <div className="users-table-wrap">
                            <table className="users-table">
                                <thead><tr><th>{t('users.name')}</th><th>{t('users.email')}</th><th>{t('users.type')}</th><th>{t('users.joined')}</th><th>{t('users.actions')}</th></tr></thead>
                                {dataLoading ? <UserTableSkeleton /> : <tbody>{users.data.map((user) => <tr key={user.id}><td data-label={t('users.name')}><div className="managed-user"><span>{user.name.charAt(0).toUpperCase()}</span><div><strong>{user.name}</strong>{user.id === auth.user.id && <small>{t('users.your_account')}</small>}</div></div></td><td data-label={t('users.email')}>{user.email}</td><td data-label={t('users.type')}><span className={`user-type-badge ${user.type}`}>{t(`users.role_${user.type}`)}</span></td><td data-label={t('users.joined')}>{formatDate(user.created_at, locale)}</td><td data-label={t('users.actions')}><div className="user-row-actions"><button type="button" onClick={() => openEdit(user)} title={t('users.edit', { name: user.name })} aria-label={t('users.edit', { name: user.name })}><Icon name="edit" size={15} /></button><button type="button" className="danger" onClick={() => deleteUser(user)} disabled={user.id === auth.user.id || deletingId === user.id} title={t('users.delete', { name: user.name })} aria-label={t('users.delete', { name: user.name })}><Icon name="trash" size={15} /></button></div></td></tr>)}</tbody>}
                            </table>
                            {!dataLoading && users.data.length === 0 && <div className="users-empty"><Icon name="users" size={24} /><p>{t('users.empty')}</p></div>}
                        </div>
                        {users.total > 0 && <footer className="pagination-footer"><span>{t('users.showing', { from: users.from || 0, to: users.to || 0, total: users.total })}</span><nav className="pagination" aria-label={t('users.pagination')}>{users.links.map((link, index) => link.url ? <Link key={index} href={link.url} preserveScroll preserveState onStart={() => setDataLoading(true)} onFinish={() => setDataLoading(false)} className={link.active ? 'active' : ''} dangerouslySetInnerHTML={{ __html: link.label }} /> : <span key={index} className="disabled" dangerouslySetInnerHTML={{ __html: link.label }} />)}</nav></footer>}
                    </section>
                </main>
            </div>

            {formOpen && <UserFormModal key={editingUser?.id || 'create'} currentUserId={auth.user.id} onClose={() => setFormOpen(false)} user={editingUser} userTypes={userTypes} />}
        </>
    );
}
