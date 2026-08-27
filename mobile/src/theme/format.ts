// All money and dates go through here. Never format inline in a screen.
const RP = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });

/** 8450000 -> "Rp8.450.000" */
export function formatRupiah(v: number): string {
  return RP.format(Math.abs(v)).replace(/\s/g, '');
}

/** formatSigned(500000,'in') -> "+Rp500.000"; formatSigned(350000,'out') -> minus sign U+2212, not a hyphen */
export function formatSigned(v: number, kind: 'in' | 'out'): string {
  return (kind === 'in' ? '+' : '\u2212') + formatRupiah(v);
}

/** 245760 -> "240 KB"; 3145728 -> "3 MB" */
export function formatFileSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

const DATE_SHORT = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short' });
const MONTH_LONG = new Intl.DateTimeFormat('id-ID', { month: 'long' });

/** "2026-08-24" -> "24 Agu" */
export function formatDateShort(isoDate: string): string {
  return DATE_SHORT.format(new Date(isoDate));
}

/** "2026-08-24T19:40:00+07:00" -> "24 Agu, 19.40" */
export function formatDateTimeShort(isoDateTime: string): string {
  const d = new Date(isoDateTime);
  return `${DATE_SHORT.format(d)}, ${d.getHours().toString().padStart(2, '0')}.${d.getMinutes().toString().padStart(2, '0')}`;
}

/** new Date() -> "AGUSTUS" */
export function currentMonthLabel(): string {
  return MONTH_LONG.format(new Date()).toUpperCase();
}

/** new Date() -> "Agustus 2026" */
export function formatMonthYear(date: Date = new Date()): string {
  return new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(date);
}

/** new Date() -> "19.40" (24h, dot separator per FORMATS.timeRange) */
export function formatTimeShort(date: Date = new Date()): string {
  const hh = date.getHours().toString().padStart(2, '0');
  const mm = date.getMinutes().toString().padStart(2, '0');
  return `${hh}.${mm}`;
}

/** startAt, endAt ISO strings -> "08.00–17.00" (per FORMATS.timeRange) */
export function formatTimeRange(startIso: string, endIso: string | null): string {
  const fmt = (iso: string) => {
    const d = new Date(iso);
    return `${d.getHours().toString().padStart(2, '0')}.${d.getMinutes().toString().padStart(2, '0')}`;
  };
  return endIso ? `${fmt(startIso)}–${fmt(endIso)}` : fmt(startIso);
}

const DAY_SHORT = new Intl.DateTimeFormat('id-ID', { weekday: 'short' });

/** "2026-08-29T08:00:00Z" -> { day: "SAB", date: "29", month: "Agu" } */
export function formatEventDateParts(isoDate: string): { day: string; date: string; month: string } {
  const d = new Date(isoDate);
  return {
    day: DAY_SHORT.format(d).replace('.', '').toUpperCase(),
    date: String(d.getDate()),
    month: new Intl.DateTimeFormat('id-ID', { month: 'short' }).format(d).replace('.', ''),
  };
}

/** Screen-reader label, e.g. "Uang masuk, iuran anggota, lima ratus ribu rupiah, 24 Agustus" */
export function a11yAmount(kind: 'in' | 'out', title: string, v: number, dateLabel: string): string {
  const lead = kind === 'in' ? 'Uang masuk' : 'Uang keluar';
  return [lead, title, spellRupiah(v), dateLabel].join(', ');
}

/** Implement with an id-ID number-to-words helper. Required by the a11y criteria. */
export function spellRupiah(v: number): string {
  throw new Error('TODO: id-ID number to words');
}

/** "Rina Andriani" -> "RA" */
export function initialsOf(name: string): string {
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((w) => w[0]?.toUpperCase())
    .join('');
}

export const FORMATS = {
  dateShort: '24 Agu',            // d MMM
  dateFull: 'Sabtu, 29 Agustus',  // EEEE, d MMMM
  timeRange: '08.00 \u2013 17.00', // dots for time, en dash for range
  metaSep: ' \u00b7 ',             // middot with spaces
};
