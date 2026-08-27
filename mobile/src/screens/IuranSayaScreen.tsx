import { router } from 'expo-router';
import { useMemo, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';

import { Button } from '@/components/Button';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { ListItem } from '@/components/ListItem';
import { ScreenHeader } from '@/components/ScreenHeader';
import { SectionHeader } from '@/components/SectionHeader';
import { SharePreview } from '@/components/SharePreview';
import { Skeleton } from '@/components/Skeleton';
import { Tag } from '@/components/Tag';
import { useToast } from '@/components/Toast';
import { ApiError } from '@/lib/api';
import { useAuth } from '@/store/useAuth';
import { useCurrentOrganization, useDues, useNotifyDuePayment } from '@/lib/queries';
import { formatDateShort, formatMonthYear, formatRupiah } from '@/theme/format';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';
import { DUES_TAG, resolveDuesStatus } from '@/theme/vocab';

export function IuranSayaScreen() {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const { showToast } = useToast();
  const [previewOpen, setPreviewOpen] = useState(false);

  const user = useAuth((state) => state.user);
  const organization = useCurrentOrganization();
  const dues = useDues();

  const myDues = useMemo(
    () =>
      (dues.data?.data ?? [])
        .filter((d) => d.userId === user?.id && d.type === 'MONTHLY')
        .sort((a, b) => a.period.localeCompare(b.period)),
    [dues.data, user?.id],
  );

  const oldestUnpaid = myDues.find((d) => !d.isPaid && !d.isExempt);
  const notify = useNotifyDuePayment(oldestUnpaid?.id ?? '');
  const unpaidCount = myDues.filter((d) => !d.isPaid && !d.isExempt).length;
  const unpaidTotal = myDues.filter((d) => !d.isPaid && !d.isExempt).reduce((sum, d) => sum + d.amountOutstanding, 0);

  if (dues.isPending) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Iuran saya" onBack={() => router.back()} />
        <View style={{ padding: theme.layout.screenPadding }}>
          <Skeleton width="100%" height={140} />
        </View>
      </View>
    );
  }

  if (dues.isError) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Iuran saya" onBack={() => router.back()} />
        <ErrorState title="Gagal memuat iuran" body="Periksa koneksi internet Anda, lalu coba lagi." onRetry={() => dues.refetch()} />
      </View>
    );
  }

  const orgName = organization.data?.data.name ?? '';

  return (
    <View style={styles.root}>
      <ScreenHeader title="Iuran saya" onBack={() => router.back()} />
      <ScrollView contentContainerStyle={styles.scroll}>
        {!oldestUnpaid ? (
          myDues.length === 0 ? (
            <EmptyState title="Belum ada catatan iuran." body="Iuran akan muncul di sini setelah bendahara mengaturnya." />
          ) : (
            <View style={styles.statusBlock}>
              <Text style={styles.period}>{formatMonthYear(new Date(myDues[myDues.length - 1].period))}</Text>
              <Text style={styles.amount}>{formatRupiah(myDues[myDues.length - 1].amountPaid)}</Text>
              <View style={styles.tagRow}>
                <Tag tone="solid" label="SUDAH BAYAR" />
              </View>
              <Text style={styles.meaning}>
                Sudah bayar
                {myDues[myDues.length - 1].lastPaymentAt ? ` · dicatat ${formatDateShort(myDues[myDues.length - 1].lastPaymentAt!)}` : ''}
                {myDues[myDues.length - 1].recordedByName ? ` oleh ${myDues[myDues.length - 1].recordedByName}` : ''}
              </Text>
            </View>
          )
        ) : (
          <View style={styles.statusBlock}>
            <Text style={styles.period}>
              {unpaidCount > 1 ? `${unpaidCount} periode belum dibayar` : formatMonthYear(new Date(oldestUnpaid.period))}
            </Text>
            <Text style={styles.amount}>{formatRupiah(unpaidCount > 1 ? unpaidTotal : oldestUnpaid.amountOutstanding)}</Text>
            <View style={styles.tagRow}>
              <Tag tone={DUES_TAG[resolveDuesStatus(oldestUnpaid)].tone} label={DUES_TAG[resolveDuesStatus(oldestUnpaid)].label.toUpperCase()} mutedFill={DUES_TAG[resolveDuesStatus(oldestUnpaid)].mutedFill} />
            </View>
            <Text style={styles.meaning}>
              {oldestUnpaid.isAwaitingConfirmation ? 'Menunggu konfirmasi bendahara.' : 'Belum dicatat oleh bendahara.'}
            </Text>

            <View style={styles.note}>
              <Text style={styles.noteText}>Bayar langsung ke bendahara. Aplikasi hanya mencatat.</Text>
            </View>

            {!oldestUnpaid.isAwaitingConfirmation ? (
              <View style={styles.actionRow}>
                <Button variant="primary" label="Beri tahu bendahara" onPress={() => setPreviewOpen(true)} block />
              </View>
            ) : null}
          </View>
        )}

        {myDues.length > 0 ? (
          <>
            <View style={styles.rule} />
            <SectionHeader title="Riwayat" />
            <View>
              {[...myDues].reverse().map((due, i) => (
                <ListItem
                  key={due.id}
                  title={formatMonthYear(new Date(due.period))}
                  subtitle={
                    due.isPaid
                      ? `${formatRupiah(due.amountPaid)}${due.lastPaymentAt ? ` · ${formatDateShort(due.lastPaymentAt)}` : ''}${due.recordedByName ? ` · ${due.recordedByName}` : ''}`
                      : DUES_TAG[resolveDuesStatus(due)].label
                  }
                  isLast={i === myDues.length - 1}
                />
              ))}
            </View>
          </>
        ) : null}
      </ScrollView>

      {oldestUnpaid ? (
        <SharePreview
          visible={previewOpen}
          onClose={() => setPreviewOpen(false)}
          title="Beri tahu bendahara"
          lines={[
            'RukunMuda',
            '',
            `Saya sudah bayar iuran ${formatMonthYear(new Date(oldestUnpaid.period))}${orgName ? ` — ${orgName}` : ''}.`,
            'Mohon dicatat, terima kasih.',
          ]}
          onShare={() => {
            notify.mutate(undefined, {
              onSuccess: () => {
                showToast('Bendahara diberi tahu.');
                setPreviewOpen(false);
              },
              onError: (err) => {
                showToast(err instanceof ApiError ? err.message : 'Gagal mengirim pemberitahuan.');
                setPreviewOpen(false);
              },
            });
          }}
          onCopy={() => setPreviewOpen(false)}
        />
      ) : null}
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { flex: 1, backgroundColor: theme.color.bg },
    scroll: { paddingBottom: theme.space.xxxl },
    statusBlock: { padding: theme.layout.screenPadding, gap: theme.space.sm },
    period: { fontFamily: 'Archivo_800ExtraBold', fontSize: 15, color: theme.color.text },
    amount: { fontFamily: 'Archivo_800ExtraBold', fontSize: 44, lineHeight: 46, color: theme.color.text, fontVariant: ['tabular-nums'] },
    tagRow: { flexDirection: 'row' },
    meaning: { fontFamily: 'Archivo_400Regular', fontSize: 14, color: theme.color.textMuted },
    note: { backgroundColor: theme.color.surfaceAlt, padding: theme.space.md, marginTop: theme.space.sm },
    noteText: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.text, lineHeight: 19 },
    actionRow: { marginTop: theme.space.md },
    rule: { height: 2, backgroundColor: theme.color.rule, marginTop: theme.space.lg },
  });
}
