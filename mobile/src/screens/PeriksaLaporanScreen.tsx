import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';

import { BottomSheet } from '@/components/BottomSheet';
import { Button } from '@/components/Button';
import { Dialog } from '@/components/Dialog';
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
import { useApproveReport, useCurrentOrganization, useReport, useRequestReportRevision, useTransactions } from '@/lib/queries';
import { formatDateShort, formatMonthYear } from '@/theme/format';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

export function PeriksaLaporanScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const { showToast } = useToast();

  const [approveConfirm, setApproveConfirm] = useState(false);
  const [reviseOpen, setReviseOpen] = useState(false);
  const [reviseReason, setReviseReason] = useState('');

  const organization = useCurrentOrganization();
  const report = useReport(id);
  const transactions = useTransactions();
  const approveReport = useApproveReport(id);
  const requestRevision = useRequestReportRevision(id);

  const isChair = organization.data?.membership.role === 'KETUA';

  // Same race as SusunLaporanScreen: `isChair` reads `organization.data`,
  // which is still `undefined` on a cold load — wait for it too, or the
  // real chair briefly sees "Menunggu persetujuan ketua" instead of the
  // approve/revise actions.
  if (report.isPending || organization.isPending) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Periksa laporan" onBack={() => router.back()} />
        <View style={{ padding: theme.layout.screenPadding }}>
          <Skeleton width="100%" height={160} />
        </View>
      </View>
    );
  }

  if (report.isError) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Periksa laporan" onBack={() => router.back()} />
        <ErrorState title="Gagal memuat laporan" body="Periksa koneksi internet Anda, lalu coba lagi." onRetry={() => report.refetch()} />
      </View>
    );
  }

  const r = report.data!.data;
  const flagged = (transactions.data?.data ?? []).filter((t) => {
    const inPeriod = t.transactionDate >= r.periodStart && t.transactionDate <= r.periodEnd;
    return inPeriod && t.status === 'APPROVED' && (!t.categoryName || !t.hasEvidence);
  });

  return (
    <View style={styles.root}>
      <ScreenHeader title="Periksa laporan" onBack={() => router.back()} />
      <ScrollView contentContainerStyle={styles.scroll}>
        <View style={styles.periodRow}>
          <Text style={styles.periodLabel}>{formatMonthYear(new Date(r.periodStart))}</Text>
          <Tag tone="outline" label={r.statusLabel} />
        </View>
        {r.submitterName ? (
          <Text style={styles.composedBy}>
            Disusun oleh {r.submitterName}
            {r.submittedAt ? ` · ${formatDateShort(r.submittedAt)}` : ''}
          </Text>
        ) : null}

        <ReportSummary opening={r.openingBalance} income={r.totalIncome} expense={r.totalExpense} closing={r.closingBalance} />

        {r.treasurerNote ? (
          <>
            <SectionHeader title="Catatan bendahara" />
            <View style={styles.section}>
              <Text style={styles.noteText}>{r.treasurerNote}</Text>
            </View>
          </>
        ) : null}

        <SectionHeader title="Perlu diperhatikan" />
        {flagged.length === 0 ? (
          <View style={styles.section}>
            <Text style={styles.sectionMeta}>Tidak ada yang perlu diperhatikan.</Text>
          </View>
        ) : (
          flagged.map((t, i) => (
            <View key={t.id} style={styles.flaggedRow}>
              <TransactionItem
                kind={t.transactionType === 'INCOME' ? 'in' : 'out'}
                title={t.description ?? t.categoryName ?? t.transactionTypeLabel}
                meta={!t.categoryName ? 'Kategori belum diisi' : 'Bukti belum diunggah'}
                amount={t.amount}
                isLast={i === flagged.length - 1}
              />
            </View>
          ))
        )}

        <View style={styles.allTransactionsAction}>
          <Button variant="ghost" label="Lihat semua transaksi" onPress={() => router.push(`/kas/report/${r.id}`)} />
        </View>

        {!isChair ? <PermissionNote body="Menunggu persetujuan ketua." roleHint="ketua" /> : null}
      </ScrollView>

      {isChair ? (
        <View style={styles.actionBar}>
          <Button variant="primary" label="Setujui" onPress={() => setApproveConfirm(true)} loading={approveReport.isPending} block />
          <View style={styles.secondaryAction}>
            <Button variant="secondary" label="Minta perbaikan" onPress={() => setReviseOpen(true)} block />
          </View>
        </View>
      ) : null}

      <Dialog
        visible={approveConfirm}
        title="Setujui laporan"
        body={`Setujui laporan ${formatMonthYear(new Date(r.periodStart))}?`}
        confirmLabel="Setujui"
        onCancel={() => setApproveConfirm(false)}
        onConfirm={() => {
          approveReport.mutate(undefined, {
            onSuccess: () => {
              setApproveConfirm(false);
              showToast('Laporan disetujui.');
              router.back();
            },
            onError: (err) => {
              setApproveConfirm(false);
              showToast(err instanceof ApiError ? err.message : 'Gagal menyetujui laporan.');
            },
          });
        }}
      />

      <BottomSheet visible={reviseOpen} title="Minta perbaikan" onClose={() => setReviseOpen(false)}>
        <View style={styles.sheetBody}>
          <FormField label="Alasan" value={reviseReason} onChangeText={setReviseReason} placeholder="2 transaksi belum punya bukti." multiline numberOfLines={3} />
          <Button
            variant="primary"
            label="Kirim"
            disabled={!reviseReason.trim()}
            loading={requestRevision.isPending}
            onPress={() => {
              requestRevision.mutate(reviseReason.trim(), {
                onSuccess: () => {
                  setReviseOpen(false);
                  showToast('Laporan dikirim kembali ke bendahara.');
                  router.back();
                },
                onError: (err) => showToast(err instanceof ApiError ? err.message : 'Gagal mengirim permintaan perbaikan.'),
              });
            }}
            block
          />
        </View>
      </BottomSheet>
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
    composedBy: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted, paddingHorizontal: theme.layout.screenPadding, marginTop: theme.space.xs, marginBottom: theme.space.md },
    section: { paddingHorizontal: theme.layout.screenPadding },
    sectionMeta: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted },
    noteText: { fontFamily: 'Archivo_400Regular', fontSize: 14, color: theme.color.text, lineHeight: 21 },
    flaggedRow: { borderLeftWidth: 4, borderLeftColor: theme.color.accent, marginHorizontal: theme.layout.screenPadding },
    allTransactionsAction: { alignItems: 'flex-start', paddingHorizontal: theme.layout.screenPadding, marginTop: theme.space.sm },
    actionBar: { paddingHorizontal: theme.layout.screenPadding, paddingVertical: theme.space.md, borderTopWidth: 2, borderTopColor: theme.color.text },
    secondaryAction: { marginTop: theme.space.sm },
    sheetBody: { paddingHorizontal: theme.layout.screenPadding, gap: theme.space.md, paddingBottom: theme.space.md },
  });
}
