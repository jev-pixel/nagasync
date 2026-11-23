// One place for peso formatting, so Counter, Payments, Treasury and the KPI cards all agree.
const full = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 2 });
const whole = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 0 });
const compact = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', notation: 'compact', maximumFractionDigits: 1 });

const num = (n) => (Number.isFinite(Number(n)) ? Number(n) : 0);

/** peso(1250.5) -> "₱1,250.50" */
export const peso = (n) => full.format(num(n));

/** pesoWhole(1250.5) -> "₱1,251" (tables where centavos are noise) */
export const pesoWhole = (n) => whole.format(num(n));

/** pesoCompact(1250000) -> "₱1.3M" (KPI cards) */
export const pesoCompact = (n) => compact.format(num(n));
