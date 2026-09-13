import { router } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';

import { KeyboardAwareScroll } from '@/components/KeyboardAwareScroll';
import { Button } from '@/components/Button';
import { Dialog } from '@/components/Dialog';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { FormField } from '@/components/FormSection';
import { PermissionNote } from '@/components/PermissionNote';
import { ReportSummary } from '@/components/ReportSummary';
import { ScreenHeader } from '@/components/ScreenHeader';
import { SectionHeader } from '@/components/SectionHeader';
import { Skeleton } from '@/components/Skeleton';
import { Tag } from '@/components/Tag';
import { useToast } from '@/components/Toast';
import { TransactionItem } from '@/components/TransactionItem';
import { ApiError } from '@/lib/api';
import {
  type ApiReportDetail,
  type ApiTransaction,
  useCreateReport,
  useCurrentOrganization,
  usePublishReport,
  useReport,
  useReports,
  useSaveReportNote,
  useSubmitReport,
  useTransactions,
} from '@/lib/queries';
import { formatMonthYear } from '@/theme/format';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

const STATUS_TAG: Record<string, { tone: 'solid' | 'outline' | 'tint'; label: string }> = {
  DRAFT: { tone: 'outline', label: 'Draf' },
  DIPERIKSA: { tone: 'outline', label: 'Diperiksa' },
  DISETUJUI: { tone: 'tint', label: 'Disetujui' },
};

function currentMonthBounds() {
  const now = new Date();
  const start = new Date(Date.UTC(now.getFullYear(), now.getMonth(), 1));
  const end = new Date(Date.UTC(now.getFullYear(), now.getMonth() + 1, 0));
  return { start: start.toISOString().slice(0, 10), end: end.toISOString().slice(0, 10) };
}

export function SusunLaporanScreen() {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const { showToast } = useToast();

  const [createdId, setCreatedId] = useState<string | null>(null);

  const organization = useCurrentOrganization();
  const reports = useReports();
  const createReport = useCreateReport();

  const isTreasurer = organization.data?.membership.role === 'KETUA' || organization.data?.membership.role === 'BENDAHARA';
  const existingDraft = (reports.data?.data ?? []).find((r) => {
    const bounds = currentMonthBounds();
    return r.status === 'DRAFT' && r.periodStart === bounds.start && r.periodEnd === bounds.end;
  });
  const reportId = createdId ?? existingDraft?.id ?? null;

  // `isTreasurer` reads `organization.data`, which is still `undefined`
  // on a cold load — must wait for that query to settle before trusting
  // a `false` reading, or a genuine treasurer/chair briefly sees the
  // permission note on every fresh app launch.
  if (organization.isPending || reports.isPending) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Susun laporan" onBack={() => router.back()} />
        <View style={{ padding: theme.layout.screenPadding }}>
          <Skeleton width="100%" height={160} />
        </View>
      </View>
    );
  }

  if (organization.isError) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Susun laporan" onBack={() => router.back()} />
        <ErrorState title="Gagal memuat organisasi" body="Periksa koneksi internet Anda, lalu coba lagi." onRetry={() => organization.refetch()} />
      </View>
    );
  }

  if (!isTreasurer) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Susun laporan" onBack={() => router.back()} />
        <PermissionNote body="Hanya bendahara yang menyusun laporan kas." roleHint="bendahara" />
      </View>
    );
  }

  if (!reportId) {
    const bounds = currentMonthBounds();
    return (
      <View style={styles.root}>
        <ScreenHeader title="Susun laporan" onBack={() => router.back()} />
        <EmptyState
          title="Belum ada laporan bulan ini."
          body="Buat laporan untuk mulai menyusun laporan kas bulan ini dari transaksi yang sudah disetujui."
          actionLabel="Mulai laporan"
          onAction={() => {
            createReport.mutate(
              {
                title: `Laporan Kas ${formatMonthYear()}`,
                report_type: 'MONTHLY',
                period_start: bounds.start,
                period_end: bounds.end,
                visibility: 'MEMBERS',
              },
              {
                onSuccess: (res) => setCreatedId(res.data.id),
                onError: (err) => showToast(err instanceof ApiError ? err.message : 'Gagal membuat laporan.'),
              },
            );
          }}
        />
      </View>
    );
  }

  return <SusunLaporanEditor reportId={reportId} />;
}

function SusunLaporanEditor({ reportId }: { reportId: string }) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const { showToast } = useToast();
  const [confirmSubmit, setConfirmSubmit] = useState(false);

  const report = useReport(reportId);
  const transactions = useTransactions();
  const saveNote = useSaveReportNote(reportId);
  const submitReport = useSubmitReport(reportId);
  const publishReport = usePublishReport(reportId);

  if (report.isPending) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Susun laporan" onBack={() => router.back()} />
        <View style={{ padding: theme.layout.screenPadding }}>
          <Skeleton width="100%" height={160} />
        </View>
      </View>
    );
  }

  if (report.isError) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Susun laporan" onBack={() => router.back()} />
        <ErrorState title="Gagal memuat laporan" body="Periksa koneksi internet Anda, lalu coba lagi." onRetry={() => report.refetch()} />
      </View>
    );
  }

  return (
    <SusunLaporanForm
      report={report.data!.data}
      canSubmit={report.data!.meta.canSubmit}
      canPublish={report.data!.meta.canPublish}
      transactions={transactions.data?.data ?? []}
      confirmSubmit={confirmSubmit}
      setConfirmSubmit={setConfirmSubmit}
      onSaveNote={(note) =>
        saveNote.mutate(note, {
          onSuccess: () => showToast('Draf disimpan.'),
          onError: (err) => showToast(err instanceof ApiError ? err.message : 'Gagal menyimpan draf.'),
        })
      }
      saving={saveNote.isPending}
      onSubmit={(note) => {
        submitReport.mutate(note, {
          onSuccess: () => {
            setConfirmSubmit(false);
            showToast('Laporan dikirim untuk diperiksa.');
            router.back();
          },
          onError: (err) => {
            setConfirmSubmit(false);
            showToast(err instanceof ApiError ? err.message : 'Gagal mengirim laporan.');
          },
        });
      }}
      submitting={submitReport.isPending}
      onPublish={() => {
        publishReport.mutate(undefined, {
          onSuccess: () => {
            setConfirmSubmit(false);
            showToast('Laporan disetujui dan diterbitkan.');
            router.back();
          },
          onError: (err) => {
            setConfirmSubmit(false);
            showToast(err instanceof ApiError ? err.message : 'Gagal menerbitkan laporan.');
          },
        });
      }}
      publishing={publishReport.isPending}
    />
  );
}

function SusunLaporanForm({
  report,
  canSubmit,
  canPublish,
  transactions,
  confirmSubmit,
  setConfirmSubmit,
  onSaveNote,
  saving,
  onSubmit,
  submitting,
  onPublish,
  publishing,
}: {
  report: ApiReportDetail;
  canSubmit: boolean;
  canPublish: boolean;
  transactions: ApiTransaction[];
  confirmSubmit: boolean;
  setConfirmSubmit: (v: boolean) => void;
  onSaveNote: (note: string) => void;
  saving: boolean;
  onSubmit: (note: string) => void;
  submitting: boolean;
  onPublish: () => void;
  publishing: boolean;
}) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const [note, setNote] = useState(report.treasurerNote ?? '');

  const incomplete = transactions.filter((t) => {
    const inPeriod = t.transactionDate >= report.periodStart && t.transactionDate <= report.periodEnd;
    return inPeriod && t.status === 'APPROVED' && (!t.categoryName || !t.hasEvidence);
  });

  const tag = STATUS_TAG[report.status] ?? STATUS_TAG.DRAFT!;
  // A DRAFT report's `canPublish` is only ever true for the chair
  // (publish requires isChairOf) — and a chair is always also a
  // treasurer in this role model, so `canSubmit` is true for them too.
  // The two are never mutually exclusive; `canPublish` alone is the
  // correct "I'm the chair composing this myself" signal (screen 30's
  // "approval step is skipped... becomes 'Setujui & terbitkan'").
  const isDualRoleShortcut = canPublish;

  return (
    <View style={styles.root}>
      <ScreenHeader title="Susun laporan" onBack={() => router.back()} />
      <KeyboardAwareScroll contentContainerStyle={styles.scroll}>
        <View style={styles.periodRow}>
          <Text style={styles.periodLabel}>{formatMonthYear(new Date(report.periodStart))}</Text>
          <Tag tone={tag.tone} label={tag.label} />
        </View>

        {report.revisionReason ? (
          <View style={styles.warningBlock}>
            <Text style={styles.warningText}>{report.revisionReason}</Text>
          </View>
        ) : null}

        <ReportSummary opening={report.openingBalance} income={report.totalIncome} expense={report.totalExpense} closing={report.closingBalance} />

        <View style={styles.note}>
          <Text style={styles.noteText}>Angka diambil langsung dari transaksi. Untuk mengubah angka, ubah transaksinya.</Text>
        </View>

        <SectionHeader title="Transaksi yang perlu dilengkapi" />
        {incomplete.length === 0 ? (
          <View style={styles.section}>
            <Text style={styles.sectionMeta}>Semua transaksi sudah lengkap.</Text>
          </View>
        ) : (
          incomplete.map((t, i) => (
            <TransactionItem
              key={t.id}
              kind={t.transactionType === 'INCOME' ? 'in' : 'out'}
              title={t.description ?? t.categoryName ?? t.transactionTypeLabel}
              meta={!t.categoryName ? 'Kategori belum diisi' : 'Bukti belum diunggah'}
              amount={t.amount}
              isLast={i === incomplete.length - 1}
            />
          ))
        )}

        <SectionHeader title="Catatan bendahara" />
        <View style={styles.field}>
          <FormField label="Catatan" value={note} onChangeText={setNote} placeholder="Catatan untuk ketua" multiline numberOfLines={4} />
        </View>
      </KeyboardAwareScroll>

      <View style={styles.actionBar}>
        {isDualRoleShortcut ? (
          <Button variant="primary" label="Setujui & terbitkan" onPress={() => setConfirmSubmit(true)} loading={publishing} block />
        ) : (
          <Button variant="primary" label="Kirim untuk diperiksa" onPress={() => setConfirmSubmit(true)} loading={submitting} disabled={!canSubmit} block />
        )}
        <View style={styles.ghostAction}>
          <Button variant="ghost" label="Simpan draf" onPress={() => onSaveNote(note)} loading={saving} />
        </View>
      </View>

      <Dialog
        visible={confirmSubmit}
        title={isDualRoleShortcut ? 'Terbitkan laporan' : 'Kirim untuk diperiksa'}
        body={
          isDualRoleShortcut
            ? `Terbitkan laporan ${formatMonthYear(new Date(report.periodStart))} sekarang?`
            : incomplete.length > 0
              ? `Kirim laporan ${formatMonthYear(new Date(report.periodStart))} ke ketua? ${incomplete.length} transaksi belum punya bukti.`
              : `Kirim laporan ${formatMonthYear(new Date(report.periodStart))} ke ketua?`
        }
        confirmLabel={isDualRoleShortcut ? 'Terbitkan' : 'Kirim'}
        onCancel={() => setConfirmSubmit(false)}
        onConfirm={() => (isDualRoleShortcut ? onPublish() : onSubmit(note))}
      />
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { flex: 1, backgroundColor: theme.color.bg },
    scroll: { paddingBottom: theme.space.xxxl },
    periodRow: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      paddingHorizontal: theme.layout.screenPadding,
      marginTop: theme.space.md,
    },
    periodLabel: { fontFamily: 'Archivo_800ExtraBold', fontSize: 22, color: theme.color.text },
    warningBlock: { backgroundColor: theme.color.accent200, marginHorizontal: theme.layout.screenPadding, padding: theme.space.md, marginTop: theme.space.sm },
    warningText: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.accent700 },
    note: { backgroundColor: theme.color.surfaceAlt, marginHorizontal: theme.layout.screenPadding, padding: theme.space.md, marginTop: theme.space.md },
    noteText: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted },
    section: { paddingHorizontal: theme.layout.screenPadding },
    sectionMeta: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted },
    field: { paddingHorizontal: theme.layout.screenPadding },
    actionBar: { paddingHorizontal: theme.layout.screenPadding, paddingVertical: theme.space.md, borderTopWidth: 2, borderTopColor: theme.color.text },
    ghostAction: { alignItems: 'center', marginTop: theme.space.sm },
  });
}
