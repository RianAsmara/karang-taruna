import { router } from 'expo-router';
import { useMemo, useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

import { BalanceDisplay } from '@/components/BalanceDisplay';
import { Card } from '@/components/Card';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { InfoSheet } from '@/components/InfoSheet';
import { Progress } from '@/components/Progress';
import { ScreenHeader } from '@/components/ScreenHeader';
import { SectionHeader } from '@/components/SectionHeader';
import { Segmented } from '@/components/Segmented';
import { Skeleton } from '@/components/Skeleton';
import { TransactionItem } from '@/components/TransactionItem';
import { useAccounts, useCurrentOrganization, useDues, useTransactions, useTransparency } from '@/lib/queries';
import { currentMonthLabel, formatDateShort, formatRupiah } from '@/theme/format';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

const RANGES = ['Bulan ini', '3 bulan', 'Semua'] as const;

function currentMonthPeriod(): string {
  const now = new Date();
  return new Date(Date.UTC(now.getFullYear(), now.getMonth(), 1)).toISOString().slice(0, 10);
}

export function KasScreen() {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const [range, setRange] = useState<(typeof RANGES)[number]>('Bulan ini');
  // "+ Catat" opens a transaction-entry form, whose fields/validation are
  // not specified anywhere in the design docs — treated as the same kind
  // of stub as the destinations named in the BelumTersedia policy.
  const [catatOpen, setCatatOpen] = useState(false);

  const transparency = useTransparency();
  const transactions = useTransactions();
  const dues = useDues();
  const accounts = useAccounts();
  const organization = useCurrentOrganization();

  const monthlyDues = useMemo(() => {
    const period = currentMonthPeriod();
    const rows = (dues.data?.data ?? []).filter((d) => d.type === 'MONTHLY' && d.period === period);
    return {
      paid: rows.filter((d) => d.isPaid).length,
      total: rows.length,
      amount: rows.reduce((sum, d) => sum + d.amountPaid, 0),
    };
  }, [dues.data]);

  if (transparency.isPending || transactions.isPending) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Kas" size="lg" />
        <View style={styles.scroll}>
          <Skeleton width="100%" height={140} />
        </View>
      </View>
    );
  }

  if (transparency.isError || transactions.isError) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Kas" size="lg" />
        <ErrorState
          title="Gagal memuat data kas"
          body="Periksa koneksi internet Anda, lalu coba lagi."
          onRetry={() => {
            transparency.refetch();
            transactions.refetch();
          }}
        />
      </View>
    );
  }

  const summary = transparency.data!;
  const transactionList = transactions.data!.data;

  return (
    <View style={styles.root}>
      <ScreenHeader
        title="Kas"
        size="lg"
        right={
          <Pressable onPress={() => setCatatOpen(true)} hitSlop={theme.hitSlop} android_ripple={null} accessibilityRole="button">
            <Text style={styles.catatAction}>+ Catat</Text>
          </Pressable>
        }
      />
      <ScrollView contentContainerStyle={styles.scroll}>
        <BalanceDisplay
          label={organization.data ? `KAS ${organization.data.data.name.toUpperCase()}` : 'KAS'}
          amount={summary.balance}
          meta={(accounts.data?.data ?? []).map((a) => `${a.name} ${formatRupiah(a.balance)}`).join(' · ')}
          income={summary.monthIncome}
          expense={summary.monthExpense}
          variant="cells"
          monthLabel={currentMonthLabel()}
        />

        <View style={styles.spacer} />

        <Card inverted onPress={() => router.push('/kas/transparansi')} style={styles.teaserCard}>
          <View style={styles.teaserRow}>
            <View style={styles.teaserTextCol}>
              <Text style={styles.teaserTitle}>Transparansi kas</Text>
              <Text style={styles.teaserSub}>Lihat laporan dan riwayat transaksi lengkap</Text>
            </View>
            <Text style={styles.teaserArrow}>→</Text>
          </View>
        </Card>

        <View style={styles.spacer} />

        <Segmented options={RANGES} value={range} onChange={setRange} />

        <View style={styles.spacer} />

        <SectionHeader title="Aktivitas terbaru" />
        {transactionList.length === 0 ? (
          <EmptyState title="Belum ada transaksi" body="Transaksi kas akan muncul di sini." />
        ) : (
          <Card>
            {transactionList.map((t, i) => (
              <TransactionItem
                key={t.id}
                kind={t.transactionType === 'INCOME' ? 'in' : 'out'}
                title={t.description ?? t.categoryName ?? t.transactionTypeLabel}
                meta={`${formatDateShort(t.transactionDate)} · ${t.creatorName ?? ''}`}
                amount={t.amount}
                status={t.status === 'PENDING' ? 'MENUNGGU' : t.status === 'REJECTED' ? 'DITOLAK' : undefined}
                isLast={i === transactionList.length - 1}
              />
            ))}
          </Card>
        )}

        <SectionHeader title={`Iuran ${currentMonthLabel()}`} />
        <View style={styles.duesBlock}>
          <View style={styles.duesCaptionRow}>
            <Text style={styles.duesCaption}>
              {monthlyDues.paid} DARI {monthlyDues.total} ANGGOTA
            </Text>
            <Text style={styles.duesAmount}>{formatRupiah(monthlyDues.amount)}</Text>
          </View>
          <Progress
            variant="bar"
            tone="accent"
            value={monthlyDues.paid}
            total={monthlyDues.total || 1}
            label={`${monthlyDues.paid} dari ${monthlyDues.total} anggota sudah membayar iuran`}
            showLabel={false}
          />
          <Text style={styles.duesLink}>Lihat status iuran per anggota →</Text>
        </View>
      </ScrollView>

      <InfoSheet
        visible={catatOpen}
        title="Catat transaksi"
        body="Formulir pencatatan transaksi sedang disiapkan."
        onClose={() => setCatatOpen(false)}
      />
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { flex: 1, backgroundColor: theme.color.bg },
    catatAction: { fontFamily: 'Archivo_800ExtraBold', fontSize: 13, color: theme.color.accent },
    scroll: { paddingBottom: theme.space.xxxl },
    spacer: { height: theme.space.lg },
    teaserCard: { padding: theme.layout.screenPadding },
    teaserRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
    teaserTextCol: { flex: 1 },
    teaserTitle: { fontFamily: 'Archivo_800ExtraBold', fontSize: 17, color: theme.color.onAccent },
    teaserSub: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.onAccent, marginTop: 2 },
    teaserArrow: { fontFamily: 'Archivo_800ExtraBold', fontSize: 18, color: theme.color.onAccent },
    duesBlock: { paddingHorizontal: theme.layout.screenPadding, gap: theme.space.sm },
    duesCaptionRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'baseline' },
    duesCaption: { fontFamily: 'Archivo_600SemiBold', fontSize: 11, letterSpacing: 0.7, color: theme.color.textMuted },
    duesAmount: { fontFamily: 'Archivo_800ExtraBold', fontSize: 20, color: theme.color.text, fontVariant: ['tabular-nums'] },
    duesLink: { fontFamily: 'Archivo_800ExtraBold', fontSize: 13, color: theme.color.accent, marginTop: theme.space.xs },
  });
}
