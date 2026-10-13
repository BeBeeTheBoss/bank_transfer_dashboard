import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { LocaleSwitcher, useTranslations } from '../../i18n';

function Icon({ name, size = 17 }) {
    const paths = {
        bars: <path d="M5 20v-6M12 20V5M19 20V10" />,
        search: <><circle cx="11" cy="11" r="7" /><path d="m20 20-4-4" /></>,
        close: <path d="m6 6 12 12M18 6 6 18" />,
        image: <><rect x="3" y="4" width="18" height="16" rx="2" /><circle cx="9" cy="10" r="2" /><path d="m21 15-5-4L5 20" /></>,
        upload: <><path d="M12 16V4M8 8l4-4 4 4" /><path d="M5 14v5h14v-5" /></>,
        download: <><path d="M12 4v12M8 12l4 4 4-4" /><path d="M5 19h14" /></>,
        check: <path d="m5 12 4 4L19 6" />,
        trash: <><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13" /></>,
        logout: <><path d="M10 5H5v14h5M14 8l4 4-4 4M18 12H9" /></>,
    };

    return <svg className="icon" width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">{paths[name]}</svg>;
}

function formatDuration(value, t) {
    if (value === null || value === undefined || value === '') return t('time.waiting');
    const seconds = Math.max(0, Number(value));
    if (seconds < 60) return t('time.seconds', { count: Math.round(seconds) });
    if (seconds < 3600) return t('time.minutes_seconds', { minutes: Math.floor(seconds / 60), seconds: Math.round(seconds % 60) });
    return t('time.hours_minutes', { hours: Math.floor(seconds / 3600), minutes: Math.round((seconds % 3600) / 60) });
}

function isDelayed(value) {
    return value !== null && value !== undefined && value !== '' && Number(value) > 300;
}

function formatDateParts(value, locale) {
    if (!value) return { time: '—', date: '—' };
    const parts = String(value).match(/^(\d{4})-(\d{2})-(\d{2})[T\s](\d{2}):(\d{2})(?::(\d{2}))?/);

    if (!parts) return { time: '—', date: '—' };

    const [, year, month, day, hour, minute, second = '00'] = parts;
    const date = new Date(Date.UTC(Number(year), Number(month) - 1, Number(day), Number(hour), Number(minute), Number(second)));
    const language = locale === 'my' ? 'my-MM' : 'en-GB';
    const timeZone = 'UTC';

    return {
        time: new Intl.DateTimeFormat(language, { hour: '2-digit', minute: '2-digit', second: '2-digit', timeZone }).format(date),
        date: new Intl.DateTimeFormat(language, { day: '2-digit', month: 'short', year: 'numeric', timeZone }).format(date),
    };
}

function formatDate(value, locale) {
    const { time, date } = formatDateParts(value, locale);
    return time === '—' ? '—' : `${date}, ${time}`;
}

function formatNumber(value, locale) {
    return new Intl.NumberFormat(locale === 'my' ? 'my-MM' : 'en-US').format(value);
}

function formatMoney(value, locale) {
    if (value === null || value === undefined) return '—';
    const normalized = typeof value === 'string' ? value.trim().replace(/[^0-9.-]/g, '') : value;
    const amount = Number(normalized);

    if (normalized === '' || !Number.isFinite(amount)) return String(value).trim() || '—';

    return new Intl.NumberFormat(locale === 'my' ? 'my-MM' : 'en-US', { maximumFractionDigits: 2 }).format(amount);
}

function DataSkeleton({ className = '' }) {
    return <span className={`data-skeleton ${className}`.trim()} aria-hidden="true" />;
}

function TimeCell({ value, locale }) {
    const parts = formatDateParts(value, locale);
    return <div className="time-cell"><strong>{parts.time}</strong><span>{parts.date}</span></div>;
}

function StatusBadge({ status, labels }) {
    return <span className={`status-badge ${status}`}>{labels[status] || status}</span>;
}

function ResponseTime({ value, t }) {
    return <div className={`response-value ${isDelayed(value) ? 'slow' : ''}`}><strong>{formatDuration(value, t)}</strong>{isDelayed(value) && <span>{t('time.delayed')}</span>}</div>;
}

function SummaryMetric({ label, value, note, warning, loading }) {
    return <article className={`summary-metric ${warning ? 'warning' : ''}`}><span>{label}</span><strong>{loading ? <DataSkeleton className="summary-value-skeleton" /> : value}</strong><small>{note}</small></article>;
}

function TableRowsSkeleton({ showSelection }) {
    const columnCount = showSelection ? 11 : 10;

    return <tbody className="data-table-skeleton" aria-hidden="true">{Array.from({ length: 5 }, (_, row) => <tr key={row}>{Array.from({ length: columnCount }, (_, column) => <td key={column}>{showSelection && column === 0 ? null : <DataSkeleton className={`table-cell-skeleton cell-${column}`} />}</td>)}</tr>)}</tbody>;
}

async function sendManualResponse({ photo, remark, responseType, transactionNo, t }) {
    const body = new FormData();
    body.append('transaction_no', transactionNo);

    if (responseType === 'success') body.append('photo', photo);
    if (responseType === 'failed') body.append('remark', remark.trim());

    const response = await fetch(responseType === 'failed' ? '/manual-failed-responses' : '/manual-response-photo-imports', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        body,
    });
    const result = await response.json().catch(() => ({}));

    if (!response.ok) {
        const validationMessage = Object.values(result.errors || {}).flat()[0];
        throw new Error(validationMessage || result.message || t('manual_response.request_failed'));
    }

    return result.message || t(responseType === 'failed' ? 'manual_response.failed_success' : 'manual_response.success');
}

function useImagePaste(onImage, enabled = true) {
    const onImageRef = useRef(onImage);

    useEffect(() => {
        onImageRef.current = onImage;
    }, [onImage]);

    useEffect(() => {
        if (!enabled) return undefined;

        const handlePaste = (event) => {
            const clipboardItems = Array.from(event.clipboardData?.items || []);
            const imageItem = clipboardItems.find((item) => item.kind === 'file' && item.type.startsWith('image/'));
            const imageFile = imageItem?.getAsFile() || Array.from(event.clipboardData?.files || []).find((file) => file.type.startsWith('image/'));

            if (!imageFile) return;

            event.preventDefault();
            onImageRef.current([imageFile]);
        };

        window.addEventListener('paste', handlePaste);
        return () => window.removeEventListener('paste', handlePaste);
    }, [enabled]);
}

function PhotoUploadModal({ error, onAdd, onClose, onRemove, onSubmit, photo, processing, t }) {
    useImagePaste(onAdd, !processing);

    return (
        <div className="upload-modal-backdrop" onMouseDown={(event) => event.target === event.currentTarget && !processing && onClose()}>
            <section className="upload-modal" role="dialog" aria-modal="true" aria-labelledby="photo-upload-title">
                <header><div><span className="upload-modal-icon"><Icon name="upload" size={19} /></span><div><h2 id="photo-upload-title">{t('photo_upload.title')}</h2><p>{t('photo_upload.description')}</p></div></div><button type="button" className="square-button" onClick={onClose} disabled={processing} aria-label={t('detail.close')}><Icon name="close" /></button></header>
                <label className="photo-dropzone" onDragOver={(event) => event.preventDefault()} onDrop={(event) => { event.preventDefault(); onAdd(event.dataTransfer.files); }}>
                    <input type="file" accept="image/jpeg,image/png,image/webp" onChange={(event) => { onAdd(event.target.files); event.target.value = ''; }} />
                    <span><Icon name="image" size={24} /></span>
                    <strong>{t('photo_upload.drop_title')}</strong>
                    <small>{t('photo_upload.drop_hint')}</small>
                </label>
                {photo && <div className="selected-photos"><div className="selected-photos-head"><strong>{t('photo_upload.selected')}</strong></div><div className="photo-preview-grid"><article><img src={photo.url} alt="" /><div><strong>{photo.file.name}</strong><small>{Math.ceil(photo.file.size / 1024)} KB</small></div><button type="button" onClick={onRemove} disabled={processing} title={t('photo_upload.remove', { name: photo.file.name })} aria-label={t('photo_upload.remove', { name: photo.file.name })}><Icon name="trash" size={15} /></button></article></div></div>}
                {error && <p className="upload-message error" role="alert">{error}</p>}
                <footer><button type="button" className="reset-button" onClick={onClose} disabled={processing}>{t('photo_upload.cancel')}</button><button type="button" className="apply-button" onClick={onSubmit} disabled={processing || !photo}>{processing ? t('photo_upload.processing') : t('photo_upload.process')}</button></footer>
            </section>
        </div>
    );
}

function BulkResponseModal({ onClose, onPartialFailure, onSuccess, records, responseType, t }) {
    const [photo, setPhoto] = useState(null);
    const [remark, setRemark] = useState('');
    const [processing, setProcessing] = useState(false);
    const [completed, setCompleted] = useState(0);
    const [error, setError] = useState('');

    useEffect(() => () => {
        if (photo) URL.revokeObjectURL(photo.url);
    }, [photo]);

    const selectPhoto = (files) => {
        const selectedPhoto = Array.from(files)[0];

        if (!selectedPhoto) return;

        if (!['image/jpeg', 'image/png', 'image/webp'].includes(selectedPhoto.type)) {
            setError(t('manual_response.invalid_type'));
            return;
        }

        setError('');
        setPhoto({ file: selectedPhoto, url: URL.createObjectURL(selectedPhoto) });
    };

    useImagePaste(selectPhoto, responseType === 'success' && !processing);

    const submit = async () => {
        if (records.length === 0 || (responseType === 'success' && !photo) || (responseType === 'failed' && !remark.trim())) return;

        setProcessing(true);
        setCompleted(0);
        setError('');

        const successfulMessages = [];
        const failedRecords = [];

        for (let index = 0; index < records.length; index += 5) {
            const requestBatch = records.slice(index, index + 5);
            const results = await Promise.allSettled(requestBatch.map((record) => sendManualResponse({
                photo: photo?.file,
                remark,
                responseType,
                transactionNo: record.transaction_no,
                t,
            })));

            results.forEach((result, resultIndex) => {
                if (result.status === 'fulfilled') successfulMessages.push(result.value);
                if (result.status === 'rejected') failedRecords.push({ record: requestBatch[resultIndex], message: result.reason?.message || t('manual_response.request_failed') });
            });
            setCompleted(Math.min(index + requestBatch.length, records.length));
        }

        setProcessing(false);

        if (failedRecords.length > 0) {
            onPartialFailure({
                failedIds: failedRecords.map(({ record }) => record.id),
                message: t('bulk_response.partial_failed', {
                    failed: failedRecords.length,
                    total: records.length,
                    reason: failedRecords[0].message,
                }),
                successfulCount: successfulMessages.length,
            });
            return;
        }

        onSuccess(t('bulk_response.success', {
            count: records.length,
            message: successfulMessages[0] || t(responseType === 'failed' ? 'manual_response.failed_success' : 'manual_response.success'),
        }));
    };

    const title = t(responseType === 'failed' ? 'bulk_response.failed_title' : 'bulk_response.photo_title');

    return (
        <div className="upload-modal-backdrop" onMouseDown={(event) => event.target === event.currentTarget && !processing && onClose()}>
            <section className="upload-modal bulk-response-modal" role="dialog" aria-modal="true" aria-labelledby="bulk-response-title">
                <header><div><span className={`upload-modal-icon ${responseType === 'failed' ? 'failed' : ''}`}><Icon name={responseType === 'failed' ? 'close' : 'upload'} size={19} /></span><div><h2 id="bulk-response-title">{title}</h2><p>{t('bulk_response.description', { count: records.length })}</p></div></div><button type="button" className="square-button" onClick={onClose} disabled={processing} aria-label={t('detail.close')}><Icon name="close" /></button></header>
                <div className="bulk-transaction-list" aria-label={t('bulk_response.selected_transactions')}>
                    {records.slice(0, 6).map((record) => <span key={record.id}>{record.transaction_no}</span>)}
                    {records.length > 6 && <span>+{records.length - 6}</span>}
                </div>
                {responseType === 'success' ? <>
                    <label className="manual-photo-picker bulk-photo-picker">
                        <input type="file" accept="image/jpeg,image/png,image/webp" onChange={(event) => { selectPhoto(event.target.files); event.target.value = ''; }} />
                        <span><Icon name="upload" size={18} /></span>
                        <div><strong>{t(photo ? 'manual_response.replace' : 'manual_response.select')}</strong><small>{t('manual_response.hint')}</small></div>
                    </label>
                    {photo && <article className="manual-photo-preview"><img src={photo.url} alt="" /><div><strong>{photo.file.name}</strong><small>{Math.ceil(photo.file.size / 1024)} KB</small></div><button type="button" onClick={() => setPhoto(null)} disabled={processing} aria-label={t('manual_response.remove', { name: photo.file.name })}><Icon name="trash" size={15} /></button></article>}
                </> : <label className="manual-failure-remark bulk-failure-remark"><span>{t('manual_response.remark_label')}</span><textarea value={remark} onChange={(event) => setRemark(event.target.value)} maxLength={1000} rows={4} placeholder={t('manual_response.remark_placeholder')} disabled={processing} /><small>{t('manual_response.remark_hint')}</small></label>}
                {processing && <p className="bulk-response-progress">{t('bulk_response.processing', { completed, total: records.length })}</p>}
                {error && <p className="upload-message error" role="alert">{error}</p>}
                <footer><button type="button" className="reset-button" onClick={onClose} disabled={processing}>{t('photo_upload.cancel')}</button><button type="button" className={`apply-button ${responseType === 'failed' ? 'failed-action' : ''}`} onClick={submit} disabled={processing || (responseType === 'success' ? !photo : !remark.trim())}>{processing ? t('manual_response.sending') : t(responseType === 'failed' ? 'bulk_response.confirm_failed' : 'bulk_response.send_photo', { count: records.length })}</button></footer>
            </section>
        </div>
    );
}

function ManualResponseUpload({ onApiSuccess, record, t }) {
    const [responseType, setResponseType] = useState('success');
    const [photo, setPhoto] = useState(null);
    const [remark, setRemark] = useState('');
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => () => {
        if (photo) URL.revokeObjectURL(photo.url);
    }, [photo]);

    const selectPhoto = (files) => {
        const selected = Array.from(files)[0];

        if (!selected) return;

        if (!['image/jpeg', 'image/png', 'image/webp'].includes(selected.type)) {
            setError(t('manual_response.invalid_type'));
            return;
        }

        setError('');
        setPhoto({ file: selected, url: URL.createObjectURL(selected) });
    };

    useImagePaste(selectPhoto, responseType === 'success' && !processing);

    const submit = async () => {
        if (!record.transaction_no || (responseType === 'success' && !photo) || (responseType === 'failed' && !remark.trim())) return;

        setProcessing(true);
        setError('');

        try {
            const message = await sendManualResponse({ photo: photo?.file, remark, responseType, transactionNo: record.transaction_no, t });

            setPhoto(null);
            setRemark('');
            onApiSuccess(message);
        } catch (requestError) {
            setError(requestError.message || t('manual_response.request_failed'));
        } finally {
            setProcessing(false);
        }
    };

    return (
        <section className="manual-response-section">
            <div className="manual-response-head"><span>{t('manual_response.title')}</span><p>{t('manual_response.description', { transaction_no: record.transaction_no })}</p></div>
            <div className="manual-response-type" role="group" aria-label={t('manual_response.response_type')}>
                <button type="button" className={responseType === 'success' ? 'active' : ''} onClick={() => { setResponseType('success'); setError(''); }} disabled={processing}>{t('manual_response.with_photo')}</button>
                <button type="button" className={responseType === 'failed' ? 'active failed' : ''} onClick={() => { setResponseType('failed'); setError(''); }} disabled={processing}>{t('manual_response.mark_failed')}</button>
            </div>
            {responseType === 'success' ? <>
                <label className="manual-photo-picker">
                    <input type="file" accept="image/jpeg,image/png,image/webp" onChange={(event) => { selectPhoto(event.target.files); event.target.value = ''; }} />
                    <span><Icon name="upload" size={18} /></span>
                    <div><strong>{t(photo ? 'manual_response.replace' : 'manual_response.select')}</strong><small>{t('manual_response.hint')}</small></div>
                </label>
                {photo && <article className="manual-photo-preview"><img src={photo.url} alt="" /><div><strong>{photo.file.name}</strong><small>{Math.ceil(photo.file.size / 1024)} KB</small></div><button type="button" onClick={() => setPhoto(null)} disabled={processing} aria-label={t('manual_response.remove', { name: photo.file.name })}><Icon name="trash" size={15} /></button></article>}
            </> : <label className="manual-failure-remark"><span>{t('manual_response.remark_label')}</span><textarea value={remark} onChange={(event) => setRemark(event.target.value)} maxLength={1000} rows={4} placeholder={t('manual_response.remark_placeholder')} disabled={processing} /><small>{t('manual_response.remark_hint')}</small></label>}
            {error && <p className="upload-message error" role="alert">{error}</p>}
            <button type="button" className={`manual-response-submit ${responseType === 'failed' ? 'failed' : ''}`} onClick={submit} disabled={processing || (responseType === 'success' ? !photo : !remark.trim())}>{processing ? t('manual_response.sending') : t(responseType === 'failed' ? 'manual_response.confirm_failed' : 'manual_response.send')}</button>
        </section>
    );
}

function DetailDrawer({ canRespond, record, locale, labels, onApiSuccess, onClose, onImage, t }) {
    if (!record) return null;

    const rows = [
        [t('detail.transaction_no'), record.transaction_no || '—'],
        [t('report.branch'), record.branch || '—'],
        [t('detail.amount'), formatMoney(record.transfer_amount, locale)],
        [t('detail.requester_id'), record.requested_telegram_user_id || '—'],
        [t('detail.requested_at'), formatDate(record.requested_at, locale)],
        [t('detail.responder'), record.responded_username || '—'],
        [t('detail.responded_at'), formatDate(record.responded_at, locale)],
        [t('detail.response_time'), formatDuration(record.response_seconds, t)],
    ];

    const handleManualSuccess = (message) => {
        onClose();
        onApiSuccess(message);
    };

    return <div className="overlay" onMouseDown={(event) => event.target === event.currentTarget && onClose()}><aside className="drawer"><div className="drawer-head"><div><span>{t('detail.eyebrow')}</span><h2>{record.transaction_no || t('detail.record', { id: record.id })}</h2></div><button type="button" className="square-button" onClick={onClose} aria-label={t('detail.close')}><Icon name="close" /></button></div><StatusBadge status={record.status} labels={labels} /><dl className="detail-list">{rows.map(([term, detail]) => <div key={term}><dt>{term}</dt><dd>{detail}</dd></div>)}</dl><section className="drawer-section"><span>{t('detail.remark')}</span><p>{record.remark || t('detail.no_remark')}</p></section><div className="drawer-images"><button type="button" disabled={!record.requested_screenshot} onClick={() => onImage({ url: record.requested_screenshot, label: t('detail.requested_screenshot') })}><Icon name="image" />{t('detail.requested_screenshot')}</button><button type="button" disabled={!record.responded_screenshot} onClick={() => onImage({ url: record.responded_screenshot, label: t('detail.responded_screenshot') })}><Icon name="image" />{t('detail.responded_screenshot')}</button></div>{canRespond && record.transaction_no && <ManualResponseUpload onApiSuccess={handleManualSuccess} record={record} t={t} />}</aside></div>;
}

export default function ReportIndex({ records, summary, branches = [], filters, databaseError }) {
    const { auth, permissions } = usePage().props;
    const { locale, t } = useTranslations();
    const statusLabels = { pending: t('status.pending'), checking: t('status.checking'), 'phone-confirm': t('status.phone_confirm'), success: t('status.success'), failed: t('status.failed') };
    const canRespond = permissions?.respondToTransfers === true;
    const canExport = permissions?.exportTransferReports === true;
    const roleLabel = auth.user?.type === 'admin' ? t('report.admin_role') : auth.user?.type === 'viewer' ? t('report.viewer_role') : t('report.user_role');
    const emptyFilters = { search: '', branch: '', status: '', date_from: '', date_to: '', response_time: '', sort: 'newest', per_page: '15' };
    const [form, setForm] = useState({ ...emptyFilters, ...filters });
    const [selected, setSelected] = useState(null);
    const [selectedIds, setSelectedIds] = useState([]);
    const [bulkResponseType, setBulkResponseType] = useState(null);
    const [image, setImage] = useState(null);
    const [dataLoading, setDataLoading] = useState(false);
    const [photoUploadOpen, setPhotoUploadOpen] = useState(false);
    const [photo, setPhoto] = useState(null);
    const [uploadProcessing, setUploadProcessing] = useState(false);
    const [uploadError, setUploadError] = useState('');
    const [apiNotice, setApiNotice] = useState(null);
    const pendingDataRequests = useRef(0);
    const apiSuccessTimer = useRef(null);
    useEffect(() => () => window.clearTimeout(apiSuccessTimer.current), []);
    useEffect(() => {
        const currentPageIds = new Set(records.data.map((record) => record.id));
        setSelectedIds((currentIds) => {
            const visibleIds = currentIds.filter((id) => currentPageIds.has(id));
            return visibleIds.length === currentIds.length ? currentIds : visibleIds;
        });
    }, [records.data]);
    const startDataLoading = () => { pendingDataRequests.current += 1; setDataLoading(true); };
    const finishDataLoading = () => { pendingDataRequests.current = Math.max(0, pendingDataRequests.current - 1); if (pendingDataRequests.current === 0) setDataLoading(false); };
    const refreshReportData = () => router.reload({ only: ['records', 'summary'], onStart: startDataLoading, onFinish: finishDataLoading });
    const showApiNotice = (message, type = 'success') => {
        window.clearTimeout(apiSuccessTimer.current);
        setApiNotice({ message, type });
        apiSuccessTimer.current = window.setTimeout(() => setApiNotice(null), 4000);
    };
    const handleApiSuccess = (message) => {
        showApiNotice(message);
        refreshReportData();
    };
    const selectableRecords = records.data.filter((record) => record.transaction_no);
    const selectedRecords = selectableRecords.filter((record) => selectedIds.includes(record.id));
    const allPageRecordsSelected = selectableRecords.length > 0 && selectableRecords.every((record) => selectedIds.includes(record.id));
    const exportQuery = new URLSearchParams(Object.entries(filters || {}).filter(([key, value]) => key !== 'page' && key !== 'per_page' && value !== null && value !== undefined && value !== ''));
    const exportUrl = `/reports/export${exportQuery.size > 0 ? `?${exportQuery.toString()}` : ''}`;
    const toggleRecordSelection = (recordId) => setSelectedIds((currentIds) => currentIds.includes(recordId) ? currentIds.filter((id) => id !== recordId) : [...currentIds, recordId]);
    const togglePageSelection = () => setSelectedIds(allPageRecordsSelected ? [] : selectableRecords.map((record) => record.id));
    const handleBulkSuccess = (message) => {
        setSelectedIds([]);
        setBulkResponseType(null);
        handleApiSuccess(message);
    };
    const handleBulkPartialFailure = ({ failedIds, message, successfulCount }) => {
        setSelectedIds(failedIds);
        setBulkResponseType(null);
        showApiNotice(message, 'error');
        if (successfulCount > 0) refreshReportData();
    };
    const visitReport = (next, replace = true) => {
        const { page: _page, ...query } = next;
        setSelectedIds([]);
        router.get('/', query, { preserveState: true, preserveScroll: true, replace, onStart: startDataLoading, onFinish: finishDataLoading });
    };
    const submitFilters = (event) => { event.preventDefault(); visitReport(form); };
    const resetFilters = () => { const next = { ...emptyFilters, per_page: form.per_page }; setForm(next); visitReport(next); };
    const updatePerPage = (value) => { const next = { ...form, per_page: value }; setForm(next); visitReport(next); };
    const addPhoto = (files) => {
        const incoming = Array.from(files)[0];
        const supportedTypes = ['image/jpeg', 'image/png', 'image/webp'];

        if (!incoming) return;

        if (!supportedTypes.includes(incoming.type)) {
            setUploadError(t('photo_upload.invalid_type'));
            return;
        }

        if (photo) URL.revokeObjectURL(photo.url);
        setUploadError('');
        setPhoto({ file: incoming, url: URL.createObjectURL(incoming) });
    };
    const removePhoto = () => {
        if (photo) URL.revokeObjectURL(photo.url);
        setPhoto(null);
    };
    const closePhotoUpload = () => {
        if (uploadProcessing) return;
        if (photo) URL.revokeObjectURL(photo.url);
        setPhoto(null);
        setUploadError('');
        setPhotoUploadOpen(false);
    };
    const submitPhoto = async () => {
        if (!photo) return;

        const body = new FormData();
        body.append('photo', photo.file);
        setUploadProcessing(true);
        setUploadError('');

        try {
            const response = await fetch('/response-photo-imports', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body,
            });
            const result = await response.json().catch(() => ({}));

            if (!response.ok) {
                const validationMessage = Object.values(result.errors || {}).flat()[0];
                setUploadError(validationMessage || result.message || t('photo_upload.request_failed'));
                return;
            }

            URL.revokeObjectURL(photo.url);
            setPhoto(null);
            setPhotoUploadOpen(false);
            handleApiSuccess(result.message || t('photo_upload.success'));
        } catch {
            setUploadError(t('photo_upload.request_failed'));
        } finally {
            setUploadProcessing(false);
        }
    };

    return (
        <>
            <Head title={t('report.title')} />
            <div className="dashboard-shell">
                <header className="dashboard-header">
                    <Link href="/" className="dashboard-brand"><span><Icon name="bars" size={20} /></span>{t('report.dashboard_name')}</Link>
                    <nav className="dashboard-nav" aria-label="Primary"><Link href="/" className="active">{t('navigation.reports')}</Link>{auth.user?.type === 'admin' && <Link href="/admin/users">{t('navigation.users')}</Link>}</nav>
                    <div className="header-actions"><LocaleSwitcher /><div className="header-user"><span>{(auth.user?.name || 'A').charAt(0).toUpperCase()}</span><div><strong>{auth.user?.name}</strong><small>{roleLabel}</small></div></div><Link href="/logout" method="post" as="button" className="square-button" title={t('report.sign_out')}><Icon name="logout" /></Link></div>
                </header>

                <main className="dashboard-main">
                    <section className="report-heading"><div><h1>{t('report.heading')}</h1><p>{t('report.description')}</p></div>{canRespond && <div className="report-heading-actions"><button type="button" className="upload-photos-button" onClick={() => setPhotoUploadOpen(true)}><span className="upload-action-icon"><Icon name="upload" size={19} /></span><span className="upload-action-copy"><strong>{t('report.upload_photo_action')}</strong><small>{t('report.upload_photo_help')}</small></span></button></div>}</section>
                    {databaseError && <div className="database-alert"><strong>{t('report.database_needed')}</strong><span>{databaseError}</span></div>}

                    <form className="filter-card" onSubmit={submitFilters}>
                        <label className="filter-search"><span>{t('report.search')}</span><div><Icon name="search" size={14} /><input value={form.search || ''} onChange={(event) => setForm({ ...form, search: event.target.value })} placeholder={t('report.search_placeholder')} /></div></label>
                        <label><span>{t('report.branch')}</span><select value={form.branch || ''} onChange={(event) => setForm({ ...form, branch: event.target.value })}><option value="">{t('report.all_branches')}</option>{branches.map((branch) => <option key={branch} value={branch}>{branch}</option>)}</select></label>
                        <label><span>{t('report.status')}</span><select value={form.status || ''} onChange={(event) => setForm({ ...form, status: event.target.value })}><option value="">{t('report.all_statuses')}</option>{Object.entries(statusLabels).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></label>
                        <label><span>{t('report.response_time')}</span><select value={form.response_time || ''} onChange={(event) => setForm({ ...form, response_time: event.target.value })}><option value="">{t('report.any_response_time')}</option><option value="under_5m">{t('report.five_minutes_or_less')}</option><option value="over_5m">{t('report.over_five_minutes_filter')}</option></select></label>
                        <label><span>{t('report.from')}</span><input type="date" value={form.date_from || ''} onChange={(event) => setForm({ ...form, date_from: event.target.value })} /></label>
                        <label><span>{t('report.to')}</span><input type="date" value={form.date_to || ''} onChange={(event) => setForm({ ...form, date_to: event.target.value })} /></label>
                        <button type="submit" className="apply-button">{t('report.apply_filters')}</button><button type="button" className="reset-button" onClick={resetFilters}>{t('report.reset')}</button>
                    </form>

                    <section className="summary-card"><SummaryMetric label={t('report.average_response')} value={formatDuration(summary.avg_response_seconds, t)} note={t('report.responded_records', { count: formatNumber(summary.responded_count || 0, locale) })} loading={dataLoading} /><SummaryMetric warning label={t('report.over_five_minutes')} value={formatNumber(Number(summary.over_five_minutes_count || 0), locale)} note={t('report.delayed_records')} loading={dataLoading} /></section>

                    <section className="records-card">
                        <header className="records-header"><div><h2>{t('report.transfer_records')}</h2><span>{dataLoading ? <DataSkeleton className="records-count-skeleton" /> : t('report.matching_records', { count: formatNumber(records.total || 0, locale) })}</span></div><div className="records-header-actions">{canExport && <a className={`export-button ${records.total === 0 || dataLoading ? 'disabled' : ''}`} href={exportUrl} aria-disabled={records.total === 0 || dataLoading} onClick={(event) => (records.total === 0 || dataLoading) && event.preventDefault()}><Icon name="download" size={15} />{t('report.export_excel')}</a>}<label><span>{t('report.rows')}</span><select value={form.per_page || '15'} onChange={(event) => updatePerPage(event.target.value)}><option value="15">15</option><option value="25">25</option><option value="50">50</option></select></label></div></header>
                        {canRespond && <label className="mobile-select-all"><input type="checkbox" checked={allPageRecordsSelected} onChange={togglePageSelection} disabled={dataLoading || selectableRecords.length === 0} /><span>{t(allPageRecordsSelected ? 'bulk_response.clear_selection' : 'bulk_response.select_all')}</span></label>}
                        {canRespond && selectedRecords.length > 0 && <div className="bulk-action-bar"><div><strong>{t('bulk_response.selected_count', { count: formatNumber(selectedRecords.length, locale) })}</strong><button type="button" onClick={() => setSelectedIds([])}>{t('bulk_response.clear_selection')}</button></div><div><button type="button" className="bulk-photo-action" onClick={() => setBulkResponseType('success')}><Icon name="image" size={15} />{t('bulk_response.respond_selected')}</button><button type="button" className="bulk-failed-action" onClick={() => setBulkResponseType('failed')}><Icon name="close" size={15} />{t('bulk_response.mark_failed')}</button></div></div>}
                        <div className="records-table-wrap">
                            <table className={`records-table ${canRespond ? '' : 'read-only'}`.trim()}>
                                <thead><tr>{canRespond && <th className="selection-cell"><input type="checkbox" checked={allPageRecordsSelected} onChange={togglePageSelection} disabled={dataLoading || selectableRecords.length === 0} aria-label={t('bulk_response.select_all')} /></th>}<th>{t('report.transaction')}</th><th>{t('report.branch')}</th><th>{t('report.requester')}</th><th>{t('report.requested')}</th><th>{t('report.responded')}</th><th>{t('report.response_time')}</th><th>{t('report.amount')}</th><th>{t('report.status')}</th><th>{t('report.responder')}</th><th>{t('report.details')}</th></tr></thead>
                                {dataLoading ? <TableRowsSkeleton showSelection={canRespond} /> : (
                                    <tbody>{records.data.map((record) => (
                                        <tr key={record.id} className={`${isDelayed(record.response_seconds) ? 'delayed-row' : ''} ${selectedIds.includes(record.id) ? 'selected-row' : ''}`.trim()} onClick={() => setSelected(record)}>
                                            {canRespond && <td className="selection-cell" onClick={(event) => event.stopPropagation()}><input type="checkbox" checked={selectedIds.includes(record.id)} onChange={() => toggleRecordSelection(record.id)} disabled={!record.transaction_no} aria-label={t('bulk_response.select_record', { transaction_no: record.transaction_no || `#${record.id}` })} /></td>}
                                            <td data-label={t('report.transaction')}><strong className="transaction-number">{record.transaction_no || `#${record.id}`}</strong><span>#{record.id}</span></td>
                                            <td data-label={t('report.branch')}><strong>{record.branch || '—'}</strong></td>
                                            <td data-label={t('report.requester')}><strong>{record.requested_telegram_username || t('report.unknown')}</strong><span>{record.requested_telegram_user_id || '—'}</span></td>
                                            <td data-label={t('report.requested')}><TimeCell value={record.requested_at} locale={locale} /></td>
                                            <td data-label={t('report.responded')}><TimeCell value={record.responded_at} locale={locale} /></td>
                                            <td data-label={t('report.response_time')}><ResponseTime value={record.response_seconds} t={t} /></td>
                                            <td data-label={t('report.amount')}><strong>{formatMoney(record.transfer_amount, locale)}</strong><span>{t('report.currency')}</span></td>
                                            <td data-label={t('report.status')}><StatusBadge status={record.status} labels={statusLabels} /></td>
                                            <td data-label={t('report.responder')}><strong>{record.responded_username || '—'}</strong></td>
                                            <td data-label={t('report.details')}>
                                                <span className="record-remark">{record.remark || '—'}</span>
                                                <div className="image-links" onClick={(event) => event.stopPropagation()}>
                                                    {record.requested_screenshot && <button type="button" title={t('report.request_image')} aria-label={t('report.request_image')} onClick={() => setImage({ url: record.requested_screenshot, label: t('detail.requested_screenshot') })}><Icon name="image" size={16} /></button>}
                                                    {record.responded_screenshot && <button type="button" title={t('report.response_image')} aria-label={t('report.response_image')} onClick={() => setImage({ url: record.responded_screenshot, label: t('detail.responded_screenshot') })}><Icon name="image" size={16} /></button>}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}</tbody>
                                )}
                            </table>
                            {!dataLoading && records.data.length === 0 && <div className="empty-state"><Icon name="search" size={22} /><h3>{t('report.no_records')}</h3><p>{databaseError ? t('report.empty_database') : t('report.empty_filters')}</p></div>}
                        </div>
                        {records.total > 0 && <footer className="pagination-footer"><span>{t('report.showing', { from: formatNumber(records.from || 0, locale), to: formatNumber(records.to || 0, locale), total: formatNumber(records.total || 0, locale) })}</span><nav className="pagination" aria-label={t('report.pagination')}>{records.links?.map((link, index) => link.url ? <Link key={index} href={link.url} preserveScroll preserveState onStart={() => { setSelectedIds([]); startDataLoading(); }} onFinish={finishDataLoading} className={link.active ? 'active' : ''} dangerouslySetInnerHTML={{ __html: link.label }} /> : <span key={index} className="disabled" dangerouslySetInnerHTML={{ __html: link.label }} />)}</nav></footer>}
                    </section>
                </main>
            </div>

            {apiNotice && <div className={`api-success-toast ${apiNotice.type === 'error' ? 'error' : ''}`} role={apiNotice.type === 'error' ? 'alert' : 'status'} aria-live="polite"><span><Icon name={apiNotice.type === 'error' ? 'close' : 'check'} size={14} /></span><p>{apiNotice.message}</p></div>}
            {photoUploadOpen && <PhotoUploadModal error={uploadError} onAdd={addPhoto} onClose={closePhotoUpload} onRemove={removePhoto} onSubmit={submitPhoto} photo={photo} processing={uploadProcessing} t={t} />}
            {bulkResponseType && <BulkResponseModal key={bulkResponseType} onClose={() => setBulkResponseType(null)} onPartialFailure={handleBulkPartialFailure} onSuccess={handleBulkSuccess} records={selectedRecords} responseType={bulkResponseType} t={t} />}
            <DetailDrawer key={selected?.id || 'closed'} canRespond={canRespond} record={selected} locale={locale} labels={statusLabels} onApiSuccess={handleApiSuccess} onClose={() => setSelected(null)} onImage={setImage} t={t} />
            {image && <div className="image-lightbox" onClick={() => setImage(null)}><div><span>{image.label}</span><button type="button" className="square-button" onClick={() => setImage(null)} aria-label={t('detail.close')}><Icon name="close" /></button></div><img src={image.url} alt={image.label} onClick={(event) => event.stopPropagation()} /></div>}
        </>
    );
}
