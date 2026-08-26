// Static mock data for the design-fidelity build. Every figure here is
// taken verbatim from docs/design/screens/*.md or, where the table left a
// value unspecified, from docs/design/shots/*.png (never invented).

export const organization = {
  name: 'Pemuda Karya Bakti',
  region: 'RW 04 Sukamaju',
  kelurahan: 'Kelurahan Sukamaju',
  memberCount: 24,
  code: 'PKB-2026',
  initials: 'PK',
};

export const currentUser = {
  name: 'Arif Ramadhan',
  firstName: 'Arif',
  initials: 'AR',
  role: 'Ketua',
  memberSince: 'Maret 2024',
};

export const homeData = {
  balance: 8_450_000,
  monthIncome: 2_350_000,
  monthExpense: 1_850_000,
  balanceMeta: 'Agustus 2026 · diperbarui 19.40',
  pendingAction: {
    count: 3,
    title: 'Iuran Agustus belum dibayar',
    sub: 'Rp100.000 · jatuh tempo 31 Agustus. Ketuk untuk bayar.',
  },
  nearestEvent: {
    id: 'turnamen-voli',
    day: 'SAB',
    date: '29',
    month: 'Agu',
    title: 'Turnamen Voli Antar RT',
    time: '08.00–17.00',
    place: 'Lapangan Kampung',
    // Live value comes from useTurnamenVoliProgress() (src/store/useEventProgress.ts) — this event's prep progress is shared across Home, Kegiatan, and EventDetail, per the explicit "one shared source of truth" behavior requirement.
  },
  tasks: [
    { id: 't1', title: 'Dokumentasi turnamen', meta: 'Tenggat 28 Agu', done: false, priority: true },
    { id: 't2', title: 'Kumpulkan hadiah dari sponsor', meta: 'Tenggat 27 Agu', done: false, priority: false },
  ],
  announcement: {
    title: 'Rapat panitia Jumat malam',
    body: 'Seluruh panitia turnamen diharap hadir di Balai RW pukul 19.30 untuk gladi bersih.',
    byline: 'Ketua · 23 Agu',
  },
  recentTransactions: [
    { id: 'tx1', kind: 'in' as const, title: 'Iuran anggota', meta: '24 Agu · Rina', amount: 500_000 },
    { id: 'tx2', kind: 'out' as const, title: 'Konsumsi kegiatan', meta: '23 Agu · Dwi', amount: 350_000 },
  ],
};

export const events = [
  {
    id: 'turnamen-voli',
    group: 'MINGGU INI',
    day: 'SAB',
    date: '29',
    month: 'Agu',
    title: 'Turnamen Voli Antar RT',
    time: '08.00–17.00',
    place: 'Lapangan Kampung',
    state: 'scheduled' as const,
    people: [{ initials: 'AR' }, { initials: 'SP' }, { initials: 'SN' }],
    peopleCaption: '8 panitia · 24 peserta',
  },
  {
    id: 'kerja-bakti',
    group: 'SEPTEMBER',
    day: 'MIN',
    date: '07',
    month: 'Sep',
    title: 'Kerja Bakti Saluran Air',
    time: '06.00–10.00',
    place: 'Gang 3',
    state: 'planned' as const,
  },
  {
    id: 'malam-keakraban',
    group: 'SEPTEMBER',
    day: 'SAB',
    date: '20',
    month: 'Sep',
    title: 'Malam Keakraban Pemuda',
    time: '19.00–23.00',
    place: 'Balai RW',
    state: 'blocked' as const,
  },
];

export const eventDetail = {
  id: 'turnamen-voli',
  countdown: '4 HARI LAGI',
  title: 'Turnamen Voli Antar RT',
  dateFull: 'Sab, 29 Agustus',
  time: '08.00 – 17.00',
  place: 'Lapangan Kampung',
  address: 'Gang 2, RW 04',
  participantCount: 24,
  committeeCount: 8,
  people: [{ initials: 'AR' }, { initials: 'SP' }, { initials: 'SN' }],
  myTaskCount: 2,
  attendanceConfirmed: true,
  note: 'Panitia mohon hadir 30 menit lebih awal untuk persiapan lapangan dan pembagian tugas terakhir.',
  // NOTE (flagged for review): only "Dokumentasi" has textual grounding —
  // it's the literal example in screens/05-Event-Detail.md's toast copy
  // ('Tugas "Dokumentasi" selesai.'). "Booking wasit" and "Siapkan sound
  // system" are NOT specified anywhere in the docs; there is no screenshot
  // of this tab's actual content to check against either. Placeholder
  // titles, invented only to reach the documented "4 tugas persiapan" /
  // "3 DARI 4" count — ask before treating these as final copy.
  tasks: [
    { id: 'et1', title: 'Dokumentasi', meta: 'Agus · selesai 20 Agu', done: true, priority: true },
    { id: 'et2', title: 'Kumpulkan hadiah dari sponsor', meta: 'Tenggat 27 Agu', done: false, priority: false },
    { id: 'et3', title: 'Booking wasit', meta: 'Agus · selesai 22 Agu', done: true, priority: false },
    { id: 'et4', title: 'Siapkan sound system', meta: 'Rina · selesai 23 Agu', done: true, priority: false },
  ],
  budget: {
    total: 3_200_000,
    used: 1_850_000,
    remaining: 1_350_000,
    transactions: [
      { id: 'ebx1', kind: 'out' as const, title: 'Sewa lapangan', meta: '20 Agu · Arif', amount: 800_000 },
      { id: 'ebx2', kind: 'out' as const, title: 'Konsumsi panitia', meta: '23 Agu · Dwi', amount: 350_000 },
      { id: 'ebx3', kind: 'in' as const, title: 'Sponsor turnamen', meta: '22 Agu · Ketua', amount: 2_000_000, status: 'MENUNGGU' },
    ],
  },
};

export const kasData = {
  balance: 8_450_000,
  wallets: 'Tunai Rp1.200.000 · Rekening BRI Rp7.250.000',
  monthIncome: 2_350_000,
  monthExpense: 1_850_000,
  transparansiTeaser: 'Laporan Agustus siap dibagikan ke grup',
  transactions: [
    { id: 'tx1', kind: 'in' as const, title: 'Iuran anggota', meta: '24 Agu · Rina', amount: 500_000 },
    { id: 'tx2', kind: 'out' as const, title: 'Konsumsi kegiatan', meta: '23 Agu · Dwi', amount: 350_000 },
    { id: 'tx3', kind: 'in' as const, title: 'Sponsor turnamen', meta: '22 Agu · Ketua', amount: 2_000_000, status: 'MENUNGGU' },
    { id: 'tx4', kind: 'out' as const, title: 'Cetak spanduk', meta: '21 Agu · Agus', amount: 450_000 },
  ],
  dues: { paid: 18, total: 24, amount: 1_800_000 },
};

export const transparansi = {
  period: 'AGUSTUS 2026',
  closing: 8_450_000,
  opening: 7_950_000,
  income: 2_350_000,
  expense: 1_850_000,
  reports: [
    { id: 'agustus-2026', title: 'Laporan kas Agustus 2026', meta: 'Disusun Rina · 24 Agu', status: 'published' as const },
    { id: 'turnamen-voli', title: 'Laporan Turnamen Voli', meta: 'Menunggu kegiatan selesai', status: 'draft' as const },
  ],
};

export const reportDetail = {
  id: 'agustus-2026',
  title: 'Laporan kas\nAgustus 2026',
  byline: 'Pemuda Karya Bakti · RW 04 Sukamaju',
  approval: 'Disusun Rina Andriani (Bendahara), disetujui Arif Ramadhan (Ketua)',
  openingLabel: 'Saldo awal 1 Agu',
  opening: 7_950_000,
  incomeCount: 7,
  income: 2_350_000,
  expenseCount: 11,
  expense: 1_850_000,
  closingLabel: 'Saldo akhir 31 Agu',
  closing: 8_450_000,
  incomeBySource: [
    { label: 'Iuran anggota', value: 1_800_000 },
    { label: 'Sponsor', value: 400_000 },
    { label: 'Donasi warga', value: 150_000 },
  ],
  expenseByCategory: [
    { label: 'Kegiatan', value: 1_500_000 },
    { label: 'Konsumsi', value: 350_000 },
  ],
  records: [
    { id: 'r1', kind: 'in' as const, title: 'Iuran anggota', meta: '24 Agu · bukti terlampir', amount: 500_000 },
    { id: 'r2', kind: 'out' as const, title: 'Konsumsi kegiatan', meta: '23 Agu · bukti terlampir', amount: 350_000 },
    { id: 'r3', kind: 'in' as const, title: 'Sponsor turnamen', meta: '22 Agu · tanpa bukti', amount: 2_000_000 },
  ],
};

export const notifications = {
  pending: [
    { id: 'n1', title: 'Anda ditugaskan sebagai PIC konsumsi', meta: 'Turnamen Voli Antar RT · 2 jam lalu', destination: 'event' as const },
    { id: 'n2', title: 'Iuran Agustus belum dibayar', meta: 'Rp100.000 · jatuh tempo 31 Agu', destination: 'iuran' as const },
    { id: 'n3', title: 'Transaksi Rp2.000.000 menunggu approval', meta: 'Sponsor turnamen · dicatat Ketua · 1 hari lalu', destination: 'kas' as const },
  ],
  voting: {
    title: 'Voting: kaos turnamen warna apa?',
    meta: 'Berakhir 2 jam lagi · 17 dari 24 sudah memilih',
    progress: { value: 17, total: 24 },
  },
  other: [
    { id: 'o1', title: 'Laporan kas Agustus sudah terbit', meta: 'Rina · 24 Agu' },
    { id: 'o2', title: 'Rapat panitia Jumat malam', meta: 'Ketua · 23 Agu' },
  ],
};

export const profil = {
  stats: [
    { value: 'Rp1,2jt', caption: 'iuran saya, 12 bulan' },
    { value: '2', caption: 'tugas aktif' },
    { value: '14', caption: 'kegiatan diikuti' },
  ],
  orgMenu: [
    { key: 'anggota-peran', label: 'Anggota & peran', trailing: '24' },
    { key: 'iuran-tarif', label: 'Iuran & tarif', trailing: 'Rp100rb/bln' },
    { key: 'inventaris', label: 'Inventaris', trailing: '31 barang' },
    { key: 'dokumen', label: 'Dokumen', trailing: '9 berkas' },
    { key: 'sponsor', label: 'Sponsor', trailing: '4 mitra' },
  ],
};

export const belumTersediaCopy: Record<string, { destination: string; body: string }> = {
  'anggota-peran': {
    destination: 'Anggota & peran',
    body: 'Daftar anggota dan pengaturan peran akan muncul di sini.',
  },
  'iuran-tarif': {
    destination: 'Iuran & tarif',
    body: 'Pengaturan besaran dan jadwal iuran akan muncul di sini.',
  },
  inventaris: {
    destination: 'Inventaris',
    body: 'Daftar barang milik organisasi akan muncul di sini.',
  },
  dokumen: {
    destination: 'Dokumen',
    body: 'Berkas dan dokumen organisasi akan muncul di sini.',
  },
  sponsor: {
    destination: 'Sponsor',
    body: 'Daftar mitra dan sponsor akan muncul di sini.',
  },
  pengaturan: {
    destination: 'Pengaturan & notifikasi',
    body: 'Pengaturan akun dan notifikasi sedang disiapkan.',
  },
  'iuran-saya': {
    destination: 'Iuran saya',
    body: 'Rincian iuran Anda per bulan akan muncul di sini.',
  },
  'kegiatan-baru': {
    destination: 'Buat kegiatan baru',
    body: 'Formulir kegiatan baru sedang disiapkan.',
  },
  'organisasi-baru': {
    destination: 'Buat organisasi baru',
    body: 'Pembuatan organisasi baru sedang disiapkan. Sementara ini, minta kode organisasi dari pengurus.',
  },
};
