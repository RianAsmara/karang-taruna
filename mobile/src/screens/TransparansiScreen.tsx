import { router } from 'expo-router';
import { useState } from 'react';
import { Linking, Share, StyleSheet, Text, View } from 'react-native';

import { BottomSheet } from '@/components/BottomSheet';
import { Button } from '@/components/Button';
import { ErrorState } from '@/components/ErrorState';
import { ListItem } from '@/components/ListItem';
import { ReportSummary } from '@/components/ReportSummary';
import { ScreenHeader } from '@/components/ScreenHeader';
import { SectionHeader } from '@/components/SectionHeader';
import { Skeleton } from '@/components/Skeleton';
import { Tag } from '@/components/Tag';
import { useToast } from '@/components/Toast';
import { WEB_BASE_URL } from '@/lib/api';
import { useCurrentOrganization, useReports, useTransparency } from '@/lib/queries';
import { formatMonthYear, formatRupiah, formatSigned } from '@/theme/format';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

export function TransparansiScreen() {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const { showToast } = useToast();
  const [shareOpen, setShareOpen] = useState(false);

  const organization = useCurrentOrganization();
  const transparency = useTransparency();
  const reports = useReports();

  if (transparency.isPending) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Transparansi" onBack={() => router.back()} />
        <View style={{ padding: theme.layout.screenPadding }}>
          <Skeleton width="100%" height={140} />
        </View>
      </View>
    );
  }

  if (transparency.isError) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Transparansi" onBack={() => router.back()} />
        <ErrorState title="Gagal memuat data" body="Periksa koneksi internet Anda, lalu coba lagi." onRetry={() => transparency.refetch()} />
      </View>
    );
  }

  const summary = transparency.data!;
  const opening = summary.balance - summary.monthSurplus;
  const orgName = organization.data?.data.name ?? '';
  const latestReport = summary.publishedReports[0];

  const myRole = organization.data?.membership.role;
  const isTreasurer = myRole === 'KETUA' || myRole === 'BENDAHARA';
  const isChair = myRole === 'KETUA';
  const inProgress = (reports.data?.data ?? []).filter((r) => r.status === 'DRAFT' || r.status === 'DIPERIKSA' || r.status === 'DISETUJUI');
  const awaitingMyReview = isChair ? inProgress.find((r) => r.status === 'DIPERIKSA') : undefined;
  const myDraftToCompose = isTreasurer ? inProgress.find((r) => r.status === 'DRAFT') : undefined;

  const message = [
    `*Laporan Kas ${formatMonthYear()}*`,
    orgName,
    '',
    `Saldo awal: ${formatRupiah(opening)}`,
    `Uang masuk: ${formatSigned(summary.monthIncome, 'in')}`,
    `Uang keluar: ${formatSigned(summary.monthExpense, 'out')}`,
    `*Saldo akhir: ${formatRupiah(summary.balance)}*`,
    '',
    latestReport ? `Rincian lengkap dan bukti:\n${WEB_BASE_URL}/reports/${latestReport.id}` : '',
  ].join('\n');

  const sendToWhatsapp = async () => {
    const url = `whatsapp://send?text=${encodeURIComponent(message)}`;
    const canOpen = await Linking.canOpenURL(url);
    if (canOpen) {
      await Linking.openURL(url);
    } else {
      await Share.share({ message });
    }
    setShareOpen(false);
    showToast(`Laporan ${formatMonthYear()} dikirim ke grup WhatsApp.`);
  };

  return (
    <View style={styles.root}>
      <ScreenHeader title="Transparansi" onBack={() => router.back()} />
      <View style={{ flex: 1 }}>
        <View style={styles.poster}>
          <Text style={styles.kicker}>{formatMonthYear().toUpperCase()}</Text>
          <Text style={styles.amount}>{formatRupiah(summary.balance)}</Text>
          <Text style={styles.statement}>Uang organisasi kita jelas. Semua anggota melihat angka yang sama.</Text>
        </View>

        <View style={styles.spacer} />

        <SectionHeader title="Ringkasan bulan ini" />
        <ReportSummary opening={opening} income={summary.monthIncome} expense={summary.monthExpense} closing={summary.balance} />

        {isTreasurer || isChair ? (
          <>
            <SectionHeader title="Kelola laporan" />
            {isTreasurer ? (
              <ListItem
                title="Susun laporan"
                subtitle={myDraftToCompose ? formatMonthYear(new Date(myDraftToCompose.periodStart)) : 'Mulai laporan bulan ini'}
                onPress={() => router.push('/kas/susun-laporan')}
                isLast={!awaitingMyReview}
              />
            ) : null}
            {awaitingMyReview ? (
              <ListItem
                title="Periksa laporan"
                subtitle={formatMonthYear(new Date(awaitingMyReview.periodStart))}
                onPress={() => router.push(`/kas/periksa-laporan/${awaitingMyReview.id}`)}
                isLast
              />
            ) : null}
          </>
        ) : null}

        <SectionHeader title="Laporan" />
        {summary.publishedReports.length === 0 ? (
          <Text style={styles.footnote}>Belum ada laporan yang diterbitkan.</Text>
        ) : (
          summary.publishedReports.map((r, i) => (
            <View key={r.id} style={[styles.reportRow, i < summary.publishedReports.length - 1 && styles.reportRowDivider]}>
              <View style={styles.reportTextCol}>
                <Text style={styles.reportTitle}>{r.title}</Text>
                <Text style={styles.reportMeta}>
                  {formatMonthYear(new Date(r.periodStart))} · {formatRupiah(r.closingBalance)}
                </Text>
              </View>
              <Tag tone="solid" label="✓ TERBIT" />
            </View>
          ))
        )}

        <Text style={styles.footnote}>
          Setiap transaksi memiliki bukti dan nama pencatat. Anggota bisa mengajukan pertanyaan lewat tombol di
          halaman laporan.
        </Text>
      </View>

      <View style={styles.actionBar}>
        <View style={styles.actionPrimary}>
          <Button
            variant="primary"
            label={latestReport ? 'Bagikan ke WhatsApp' : 'Belum terbit — belum bisa dibagikan'}
            disabled={!latestReport}
            onPress={() => setShareOpen(true)}
            block
          />
        </View>
        <Button
          variant="secondary"
          label="PDF"
          onPress={() => {
            if (latestReport) {
              Linking.openURL(`${WEB_BASE_URL}/reports/${latestReport.id}/pdf`);
            } else {
              showToast('Belum ada laporan yang diterbitkan.');
            }
          }}
        />
      </View>

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
    poster: { backgroundColor: theme.color.accent, paddingVertical: theme.space.xl, paddingHorizontal: theme.layout.screenPadding },
    kicker: { fontFamily: 'Archivo_800ExtraBold', fontSize: 10, letterSpacing: 1.4, textTransform: 'uppercase', color: theme.color.onAccent, opacity: 0.9 },
    amount: { fontFamily: 'Archivo_800ExtraBold', fontSize: 40, lineHeight: 42, letterSpacing: -1.2, color: theme.color.onAccent, marginTop: theme.space.xs, fontVariant: ['tabular-nums'] },
    statement: { fontFamily: 'Archivo_400Regular', fontSize: 14, color: theme.color.onAccent, marginTop: theme.space.sm, maxWidth: '80%' },
    spacer: { height: theme.space.lg },
    reportRow: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      paddingVertical: theme.space.md,
      paddingHorizontal: theme.layout.screenPadding,
    },
    reportRowDivider: { borderBottomWidth: 1, borderBottomColor: theme.color.divider },
    reportTextCol: { flex: 1 },
    reportTitle: { fontFamily: 'Archivo_800ExtraBold', fontSize: 14, color: theme.color.text },
    reportMeta: { fontFamily: 'Archivo_400Regular', fontSize: 12, color: theme.color.textMuted, marginTop: 2 },
    footnote: { fontFamily: 'Archivo_400Regular', fontSize: 12.5, color: theme.color.textMuted, padding: theme.layout.screenPadding },
    actionBar: {
      borderTopWidth: 2,
      borderTopColor: theme.color.rule,
      padding: theme.space.md,
      paddingHorizontal: theme.layout.screenPadding,
      flexDirection: 'row',
      gap: theme.space.sm,
    },
    actionPrimary: { flex: 1 },
    sheetBody: { paddingHorizontal: theme.layout.screenPadding, gap: theme.space.md },
    messagePreview: { borderLeftWidth: 4, borderLeftColor: theme.color.text, backgroundColor: theme.color.surface, padding: theme.space.md },
    messagePreviewText: { fontFamily: 'Archivo_400Regular', fontSize: 13.5, lineHeight: 21.6, color: theme.color.text },
    sheetNote: { fontFamily: 'Archivo_400Regular', fontSize: 12.5, color: theme.color.textMuted },
  });
}
