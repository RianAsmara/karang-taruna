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

/** Screen-reader label, e.g. "Uang masuk, iuran anggota, lima ratus ribu rupiah, 24 Agustus" */
export function a11yAmount(kind: 'in' | 'out', title: string, v: number, dateLabel: string): string {
  const lead = kind === 'in' ? 'Uang masuk' : 'Uang keluar';
  return [lead, title, spellRupiah(v), dateLabel].join(', ');
}

/** Implement with an id-ID number-to-words helper. Required by the a11y criteria. */
export function spellRupiah(v: number): string {
  throw new Error('TODO: id-ID number to words');
}

export const FORMATS = {
  dateShort: '24 Agu',            // d MMM
  dateFull: 'Sabtu, 29 Agustus',  // EEEE, d MMMM
  timeRange: '08.00 \u2013 17.00', // dots for time, en dash for range
  metaSep: ' \u00b7 ',             // middot with spaces
};
