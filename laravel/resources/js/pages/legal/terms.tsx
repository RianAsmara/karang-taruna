import { LegalLayout } from './legal-layout';

export default function Terms() {
    return (
        <LegalLayout title="Syarat Penggunaan" updated="13 September 2026">
            <p>
                Dengan membuat akun RukunMuda, Anda menyetujui syarat berikut. Bacalah bersama <strong>Kebijakan Privasi</strong>.
            </p>

            <h2>Akun</h2>
            <ul>
                <li>Gunakan alamat email yang benar-benar Anda miliki. Akun harus diverifikasi melalui email sebelum dapat digunakan.</li>
                <li>Anda bertanggung jawab menjaga kerahasiaan kata sandi Anda dan atas aktivitas yang terjadi pada akun Anda.</li>
                <li>Satu akun untuk satu orang. Jangan membagikan akun kepada orang lain.</li>
            </ul>

            <h2>Penggunaan</h2>
            <ul>
                <li>Gunakan RukunMuda hanya untuk mengelola organisasi yang benar-benar Anda ikuti.</li>
                <li>Jangan mengunggah data pribadi orang lain tanpa izin mereka.</li>
                <li>Jangan mencoba mengakses data organisasi yang bukan milik Anda.</li>
                <li>Tautan undangan bersifat rahasia. Bagikan hanya kepada orang yang memang Anda undang.</li>
            </ul>

            <h2>Data keuangan</h2>
            <ul>
                <li>
                    Angka yang ditampilkan dihitung dari transaksi yang Anda dan pengurus catat sendiri. Ketepatannya bergantung pada ketepatan
                    pencatatan Anda.
                </li>
                <li>
                    Laporan yang sudah diterbitkan tidak dapat diubah diam-diam. Koreksi dibuat sebagai revisi baru, dan versi lama tetap tersimpan.
                </li>
                <li>RukunMuda adalah alat pencatatan, bukan lembaga keuangan. Kami tidak menyimpan, memindahkan, atau memproses uang Anda.</li>
            </ul>

            <h2>Ketersediaan layanan</h2>
            <p>
                Layanan disediakan sebagaimana adanya. Kami berupaya menjaga layanan tetap berjalan dan mencadangkan data secara berkala, namun tidak
                menjamin layanan bebas gangguan. Simpan salinan dokumen penting Anda sendiri.
            </p>

            <h2>Penghentian</h2>
            <p>
                Anda dapat berhenti menggunakan layanan dan menghapus akun kapan saja. Kami dapat menangguhkan akun yang melanggar syarat ini —
                terutama upaya mengakses data organisasi lain.
            </p>

            <h2>Perubahan</h2>
            <p>
                Bila syarat ini berubah secara berarti, kami akan memberi tahu melalui email atau pemberitahuan di dalam aplikasi sebelum perubahan
                berlaku.
            </p>

            <h2>Hubungi kami</h2>
            <p>
                Pertanyaan mengenai syarat ini dapat dikirim ke <strong>[isi alamat email penanggung jawab]</strong>.
            </p>
        </LegalLayout>
    );
}
