import { LegalLayout } from './legal-layout';

/**
 * Describes what the application actually stores and sends, verified
 * against the schema and the configured services — not a generic template.
 * Anything a real operator must decide (legal entity, contact address,
 * retention window) is marked and must be filled in before launch; see
 * docs/next-up.md.
 */
export default function Privacy() {
    return (
        <LegalLayout title="Kebijakan Privasi" updated="13 September 2026">
            <p>
                RukunMuda membantu organisasi pemuda mengelola anggota, kegiatan, dan kas secara transparan. Halaman ini menjelaskan data apa yang
                kami simpan, untuk apa, dan hak Anda atas data tersebut, sesuai UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi.
            </p>

            <h2>Data yang kami simpan</h2>
            <ul>
                <li>
                    <strong>Akun</strong>: nama, alamat email, dan kata sandi. Kata sandi disimpan dalam bentuk hash, tidak pernah dalam bentuk asli,
                    dan tidak dapat dibaca oleh siapa pun termasuk kami.
                </li>
                <li>
                    <strong>Profil</strong>: nomor telepon, bila Anda mengisinya. Anda mengatur sendiri apakah nomor itu terlihat oleh anggota lain
                    melalui pengaturan profil.
                </li>
                <li>
                    <strong>Keanggotaan</strong>: organisasi yang Anda ikuti dan peran Anda di dalamnya.
                </li>
                <li>
                    <strong>Aktivitas organisasi</strong>: kehadiran, tugas, suara dalam voting, iuran dan pembayaran, serta transaksi keuangan yang
                    Anda catat atau setujui.
                </li>
                <li>
                    <strong>Berkas yang Anda unggah</strong>: bukti transaksi, dokumen, dan logo organisasi.
                </li>
                <li>
                    <strong>Catatan audit</strong>: siapa mengubah apa dan kapan, untuk transaksi dan laporan keuangan. Catatan ini sengaja tidak
                    dapat dihapus — itulah yang membuat laporan keuangan dapat dipertanggungjawabkan.
                </li>
            </ul>

            <h2>Siapa yang dapat melihat data Anda</h2>
            <ul>
                <li>Data organisasi hanya dapat diakses oleh anggota organisasi tersebut. Organisasi lain tidak pernah dapat melihatnya.</li>
                <li>
                    Sebagian data keuangan dapat dipublikasikan oleh pengurus melalui laporan dengan visibilitas PUBLIK. Laporan publik berisi
                    ringkasan dan rincian transaksi, <strong>tidak</strong> berisi data pribadi anggota.
                </li>
                <li>Nomor telepon Anda hanya terlihat oleh anggota lain jika Anda mengaktifkannya sendiri.</li>
                <li>Suara Anda dalam voting anonim tidak pernah ditampilkan kepada siapa pun, termasuk ketua.</li>
            </ul>

            <h2>Pihak ketiga</h2>
            <p>Kami menggunakan layanan berikut untuk menjalankan aplikasi:</p>
            <ul>
                <li>Penyedia server dan basis data, tempat seluruh data disimpan.</li>
                <li>Penyimpanan berkas (S3) untuk bukti transaksi dan dokumen.</li>
                <li>Penyedia email untuk verifikasi akun, pengaturan ulang kata sandi, dan pemberitahuan.</li>
                <li>
                    Layanan pemantauan kesalahan. Laporan kesalahan berisi jejak teknis dan <strong>tidak</strong> menyertakan identitas atau isi
                    permintaan Anda.
                </li>
            </ul>
            <p>Kami tidak menjual data Anda, dan tidak menggunakannya untuk iklan.</p>

            <h2>Berbagi ke WhatsApp</h2>
            <p>
                Saat Anda membagikan laporan ke WhatsApp, aplikasi hanya menyiapkan teks dan tautan; Anda sendiri yang memilih grup tujuan dan menekan
                kirim. RukunMuda tidak pernah mengirim pesan WhatsApp secara otomatis atas nama Anda.
            </p>

            <h2>Hak Anda</h2>
            <ul>
                <li>Melihat dan memperbaiki data profil Anda kapan saja melalui halaman pengaturan.</li>
                <li>
                    Keluar dari organisasi. Permintaan keluar disetujui oleh ketua, dan data keanggotaan Anda disimpan 30 hari setelahnya sebelum
                    dihapus, agar riwayat keuangan yang sudah tercatat tetap utuh.
                </li>
                <li>
                    Menghapus akun Anda. Catatan keuangan yang sudah disetujui atau laporan yang sudah terbit tidak ikut terhapus, karena merupakan
                    catatan resmi organisasi — namun tidak lagi terhubung dengan profil Anda.
                </li>
                <li>Meminta salinan data Anda, atau mengajukan keberatan atas pemrosesan data Anda.</li>
            </ul>

            <h2>Keamanan</h2>
            <p>
                Seluruh lalu lintas dienkripsi (HTTPS). Berkas yang diunggah tidak dapat diakses melalui URL publik yang bisa ditebak. Kata sandi
                di-hash dengan bcrypt. Akses antar organisasi dipisahkan pada tingkat basis data dan diperiksa pada setiap permintaan.
            </p>

            <h2>Hubungi kami</h2>
            <p>
                Untuk pertanyaan atau permintaan terkait data pribadi Anda, hubungi <strong>[isi alamat email penanggung jawab]</strong>. Pengendali
                data untuk layanan ini adalah <strong>[isi nama badan hukum/penyelenggara]</strong>.
            </p>
        </LegalLayout>
    );
}
