// Shared by RequestCalendar.vue and the pages that feed it, so both always agree on what "that day" means.
// A day key is 'YYYY-MM-DD' in the *browser's local time* (Naga is UTC+8, so slicing a UTC ISO string
// would put a 7 AM submission on the previous day).
const pad = (n) => String(n).padStart(2, '0');

export function dayKey(value = new Date()) {
    if (value === null || value === undefined || value === '') return null;
    // Plain dates have no time zone: keep them as written instead of letting Date() read them as UTC
    if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value)) return value;
    const d = value instanceof Date ? value : new Date(value);
    return Number.isNaN(d.getTime()) ? null : `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

// 'YYYY-MM-DD' -> Date at local midnight
export function fromKey(key) {
    const [y, m, d] = key.split('-').map(Number);
    return new Date(y, m - 1, d);
}

export const todayKey = () => dayKey(new Date());

// [{ status, created_at }] -> { 'YYYY-MM-DD': [status, ...] }
export function marksFrom(items = [], dateOf = (i) => i.created_at ?? i.updated_at, statusOf = (i) => i.status) {
    return items.reduce((acc, item) => {
        const k = dayKey(dateOf(item));
        if (k) (acc[k] ??= []).push(statusOf(item));
        return acc;
    }, {});
}