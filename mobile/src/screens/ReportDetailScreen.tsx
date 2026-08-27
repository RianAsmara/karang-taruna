import { router, useLocalSearchParams } from 'expo-router';
import { useMemo, useState } from 'react';
import { Linking, ScrollView, Share, StyleSheet, Text, View } from 'react-native';

import { BottomSheet } from '@/components/BottomSheet';
import { Button } from '@/components/Button';
import { ErrorState } from '@/components/ErrorState';
import { ReportSummary } from '@/components/ReportSummary';
import { ScreenHeader } from '@/components/ScreenHeader';
import { SectionHeader } from '@/components/SectionHeader';
import { Skeleton } from '@/components/Skeleton';
import { Tag } from '@/components/Tag';
import { useToast } from '@/components/Toast';
import { TransactionItem } from '@/components/TransactionItem';
import { useReport, useTransactions } from '@/lib/queries';
import { formatDateShort, formatRupiah, formatSigned } from '@/theme/format';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

const STATUS_TAG: Record<string, { tone: 'solid' | 'outline' | 'tint'; label: string }> = {
  PUBLISHED: { tone: 'solid', label: '✓ TERBIT' },
  DRAFT: { tone: 'outline', label: 'DRAF' },
  DIPERIKSA: { tone: 'outline', label: 'DIPERIKSA' },
  DISETUJUI: { tone: 'tint', label: 'DISETUJUI' },
  ARCHIVED: { tone: 'outline', label: 'ARSIP' },
};

function Bar({ label, value, max, color }: { label: string; value: number; max: number; color: string }) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const pct = Math.max(4, max > 0 ? (value / max) * 100 : 4);

  return (
    <View style={styles.barRow}>
      <Text style={styles.barLabel} numberOfLines={1}>
        {label}
      </Text>
      <View style={styles.barTrack}>
        <View style={[styles.barFill, { width: `${pct}%`, backgroundColor: color }]} />
      </View>
      <Text style={styles.barValue}>{formatRupiah(value)}</Text>
    </View>
  );
}

export function ReportDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const { showToast } = useToast();
  const [shareOpen, setShareOpen] = useState(false);

  const report = useReport(id);
  const transactions = useTransactions();

  const records = useMemo(() => {
    if (!report.data) return [];
    const { periodStart, periodEnd } = report.data.data;
    return (transactions.data?.data ?? [])
      .filter(
        (t) =>
          t.status === 'APPROVED' &&
          t.transactionType !== 'TRANSFER' &&
          t.transactionDate >= periodStart &&
          t.transactionDate <= periodEnd,
      )
      .sort((a, b) => (a.transactionDate < b.transactionDate ? 1 : -1));
  }, [report.data, transactions.data]);

  if (report.isPending) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Laporan" onBack={() => router.back()} />
        <View style={{ padding: theme.layout.screenPadding }}>
          <Skeleton width="100%" height={160} />
        </View>
      </View>
    );
  }

  if (report.isError) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Laporan" onBack={() => router.back()} />
        <ErrorState title="Gagal memuat laporan" body="Periksa koneksi internet Anda, lalu coba lagi." onRetry={() => report.refetch()} />
      </View>
    );
  }

  const { data: r, meta, categoryBreakdown } = report.data!;
  const incomeCount = records.filter((t) => t.transactionType === 'INCOME').length;
  const expenseCount = records.filter((t) => t.transactionType === 'EXPENSE').length;
  const maxIncome = Math.max(1, ...categoryBreakdown.income.map((s) => s.amount));
  const maxExpense = Math.max(1, ...categoryBreakdown.expense.map((s) => s.amount));
  const statusTag = STATUS_TAG[r.status] ?? STATUS_TAG.DRAFT;

  const message = [
    `*${r.title}*`,
    r.organizationName,
    '',
    `Saldo awal: ${formatRupiah(r.openingBalance)}`,
    `Uang masuk: ${formatSigned(r.totalIncome, 'in')}`,
    `Uang keluar: ${formatSigned(r.totalExpense, 'out')}`,
    `*Saldo akhir: ${formatRupiah(r.closingBalance)}*`,
    '',
    `Rincian lengkap dan bukti:\n${meta.shareUrl}`,
  ].join('\n');

  const sendToWhatsapp = async () => {
    const url = `whatsapp://send?text=${encodeURIComponent(message)}`;
    const canOpen = await Linking.canOpenURL(url);
    if (canOpen) await Linking.openURL(url);
    else await Share.share({ message });
    setShareOpen(false);
    showToast(`${r.title} dikirim ke grup WhatsApp.`);
  };

  return (
    <View style={styles.root}>
      <ScreenHeader title={r.title} onBack={() => router.back()} right={<Tag tone={statusTag.tone} label={statusTag.label} />} />
      <ScrollView contentContainerStyle={styles.scroll}>
        <View style={styles.titleBlock}>
          <Text style={styles.title}>{r.title}</Text>
          <Text style={styles.byline}>{r.organizationName}</Text>
          {r.publisherName && r.publishedAt && (
            <Text style={styles.byline}>
              Diterbitkan oleh {r.publisherName} · {formatDateShort(r.publishedAt)}
            </Text>
          )}
        </View>

        <SectionHeader title="Ikhtisar" />
        <ReportSummary
          openingLabel="Saldo awal"
          opening={r.openingBalance}
          incomeLabel={`Uang masuk (${incomeCount} catatan)`}
          income={r.totalIncome}
          expenseLabel={`Uang keluar (${expenseCount} catatan)`}
          expense={r.totalExpense}
          closingLabel="Saldo akhir"
          closing={r.closingBalance}
        />

        {categoryBreakdown.income.length > 0 && (
          <>
            <SectionHeader title="Uang masuk menurut sumber" />
            <View style={styles.barGroup}>
              {categoryBreakdown.income.map((s) => (
                <Bar key={s.label} label={s.label} value={s.amount} max={maxIncome} color={theme.color.text} />
              ))}
            </View>
          </>
        )}

        {categoryBreakdown.expense.length > 0 && (
          <>
            <SectionHeader title="Uang keluar menurut pos" />
            <View style={styles.barGroup}>
              {categoryBreakdown.expense.map((s) => (
                <Bar key={s.label} label={s.label} value={s.amount} max={maxExpense} color={theme.color.expense} />
              ))}
            </View>
          </>
        )}

        <SectionHeader title={`Semua catatan · ${records.length}`} />
        {records.length === 0 ? (
          <Text style={styles.byline}>Belum ada transaksi pada periode ini.</Text>
        ) : (
          <View>
            {records.map((rec, i) => (
              <TransactionItem
                key={rec.id}
                kind={rec.transactionType === 'INCOME' ? 'in' : 'out'}
                title={rec.description ?? rec.categoryName ?? rec.transactionTypeLabel}
                meta={formatDateShort(rec.transactionDate)}
                amount={rec.amount}
                isLast={i === records.length - 1}
              />
            ))}
          </View>
        )}
      </ScrollView>

      {r.status === 'PUBLISHED' && (
        <View style={styles.actionBar}>
          <View style={styles.actionPrimary}>
            <Button variant="primary" label="Bagikan ke WhatsApp" onPress={() => setShareOpen(true)} block />
          </View>
          <Button variant="secondary" label="PDF" onPress={() => Linking.openURL(meta.pdfUrl)} />
        </View>
      )}

      <BottomSheet visible={shareOpen} title="Bagikan ke WhatsApp" onClose={() => setShareOpen(false)}>
        <View style={styles.sheetBody}>
          <View style={styles.messagePreview}>
            <Text style={styles.messagePreviewText}>{message}</Text>
          </View>
          <Text style={styles.sheetNote}>Tautan bisa dibuka siapa saja tanpa memasang aplikasi.</Text>
          <Button variant="primary" label="Kirim ke grup WhatsApp" onPress={sendToWhatsapp} block />
          <Button
            variant="secondary"
            label="Salin tautan saja"
            onPress={() => {
              setShareOpen(false);
              showToast('Tautan disalin.');
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
    titleBlock: { padding: theme.layout.screenPadding, gap: theme.space.xs },
    title: { fontFamily: 'Archivo_800ExtraBold', fontSize: 28, lineHeight: 32, letterSpacing: -0.7, color: theme.color.text },
    byline: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted },
    barGroup: { paddingHorizontal: theme.layout.screenPadding, gap: theme.space.sm },
    barRow: { flexDirection: 'row', alignItems: 'center', gap: theme.space.sm },
    barLabel: { width: 96, fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.text },
    barTrack: { flex: 1, height: 14, backgroundColor: theme.color.neutral300 },
    barFill: { height: 14 },
    barValue: { width: 88, textAlign: 'right', fontFamily: 'Archivo_800ExtraBold', fontSize: 12.5, color: theme.color.text, fontVariant: ['tabular-nums'] },
    actionBar: { borderTopWidth: 2, borderTopColor: theme.color.rule, padding: theme.space.md, paddingHorizontal: theme.layout.screenPadding, flexDirection: 'row', gap: theme.space.sm },
    actionPrimary: { flex: 1 },
    sheetBody: { paddingHorizontal: theme.layout.screenPadding, gap: theme.space.md },
    messagePreview: { borderLeftWidth: 4, borderLeftColor: theme.color.text, backgroundColor: theme.color.surface, padding: theme.space.md },
    messagePreviewText: { fontFamily: 'Archivo_400Regular', fontSize: 13.5, lineHeight: 21.6, color: theme.color.text },
    sheetNote: { fontFamily: 'Archivo_400Regular', fontSize: 12.5, color: theme.color.textMuted },
  });
}
