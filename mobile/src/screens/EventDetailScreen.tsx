import * as Haptics from 'expo-haptics';
import { router, useLocalSearchParams } from 'expo-router';
import { useMemo, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';

import { AvatarGroup } from '@/components/Avatar';
import { Button } from '@/components/Button';
import { ErrorState } from '@/components/ErrorState';
import { InfoSheet } from '@/components/InfoSheet';
import { Progress } from '@/components/Progress';
import { ScreenHeader } from '@/components/ScreenHeader';
import { SectionHeader } from '@/components/SectionHeader';
import { Skeleton } from '@/components/Skeleton';
import { Tabs } from '@/components/Tabs';
import { Tag } from '@/components/Tag';
import { TaskItem } from '@/components/TaskItem';
import { useToast } from '@/components/Toast';
import { TransactionItem } from '@/components/TransactionItem';
import { ApiError } from '@/lib/api';
import { useCurrentOrganization, useEvent, useTransactions, useUpdateTaskStatus } from '@/lib/queries';
import { formatDateShort, formatRupiah, formatTimeRange, initialsOf } from '@/theme/format';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

const TAB_ITEMS = ['Ringkasan', 'Tugas', 'Anggaran'] as const;

function countdownLabel(startAt: string, endAt: string | null): string {
  const now = Date.now();
  const start = new Date(startAt).getTime();
  const end = endAt ? new Date(endAt).getTime() : start;
  if (now > end) return 'SELESAI';
  if (now >= start) return 'BERLANGSUNG';
  const days = Math.ceil((start - now) / (1000 * 60 * 60 * 24));
  return days <= 1 ? 'BESOK' : `${days} HARI LAGI`;
}

const DATE_FULL = new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long' });

export function EventDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const { showToast } = useToast();
  const [tab, setTab] = useState<(typeof TAB_ITEMS)[number]>('Ringkasan');
  const [attendanceOpen, setAttendanceOpen] = useState(false);
  const organization = useCurrentOrganization();

  const event = useEvent(id);
  const transactions = useTransactions(id);
  const updateTaskStatus = useUpdateTaskStatus(id);

  const myMembershipId = organization.data?.membership.id;
  const myTaskCount = useMemo(
    () => event.data?.data.tasks.filter((t) => t.assignee?.id === myMembershipId).length ?? 0,
    [event.data, myMembershipId],
  );

  if (event.isPending) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Detail kegiatan" onBack={() => router.back()} />
        <View style={{ padding: theme.layout.screenPadding }}>
          <Skeleton width="100%" height={160} />
        </View>
      </View>
    );
  }

  if (event.isError) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Detail kegiatan" onBack={() => router.back()} />
        <ErrorState title="Gagal memuat kegiatan" body="Periksa koneksi internet Anda, lalu coba lagi." onRetry={() => event.refetch()} />
      </View>
    );
  }

  const e = event.data!.data;
  const doneCount = e.tasks.filter((t) => t.status === 'DONE').length;

  const toggleTask = (taskId: string, currentStatus: string) => {
    const nextStatus = currentStatus === 'DONE' ? 'TODO' : 'DONE';
    updateTaskStatus.mutate(
      { taskId, status: nextStatus },
      {
        onSuccess: () => {
          Haptics.selectionAsync();
          if (nextStatus === 'DONE') showToast('Tugas selesai.');
        },
        onError: (err) => {
          showToast(err instanceof ApiError && err.status === 403 ? 'Anda tidak bisa mengubah tugas ini.' : 'Gagal memperbarui tugas.');
        },
      },
    );
  };

  const eventTransactions = transactions.data?.data ?? [];
  const totalIncome = eventTransactions.filter((t) => t.transactionType === 'INCOME').reduce((s, t) => s + t.amount, 0);
  const totalExpense = eventTransactions.filter((t) => t.transactionType === 'EXPENSE').reduce((s, t) => s + t.amount, 0);

  return (
    <View style={styles.root}>
      <ScreenHeader title="Detail kegiatan" onBack={() => router.back()} right={<Text style={styles.shareAction}>Bagikan</Text>} />
      <ScrollView contentContainerStyle={styles.scroll}>
        <View style={styles.hero}>
          <Tag tone="accent" label={countdownLabel(e.startAt, e.endAt)} />
          <Text style={styles.title}>{e.title}</Text>

          <View style={styles.whenWhereRow}>
            <View style={styles.whenWhereCell}>
              <Text style={styles.whenWhereLabel}>KAPAN</Text>
              <Text style={styles.whenWhereValue}>{DATE_FULL.format(new Date(e.startAt))}</Text>
              <Text style={styles.whenWhereSub}>{formatTimeRange(e.startAt, e.endAt)}</Text>
            </View>
            <View style={styles.whenWhereDivider} />
            <View style={styles.whenWhereCell}>
              <Text style={styles.whenWhereLabel}>DI MANA</Text>
              <Text style={styles.whenWhereValue}>{e.location ?? 'Belum ditentukan'}</Text>
            </View>
          </View>
        </View>

        <Tabs items={TAB_ITEMS} value={tab} onChange={setTab} />

        {tab === 'Ringkasan' ? (
          <View style={styles.tabContent}>
            <Progress
              variant="segmented"
              value={doneCount}
              total={e.tasks.length || 1}
              label={`PERSIAPAN · ${doneCount} DARI ${e.tasks.length} · ${e.tasks.length ? Math.round((doneCount / e.tasks.length) * 100) : 0}%`}
            />

            <View style={styles.countsRow}>
              <View style={styles.countsCell}>
                <Text style={styles.countsValue}>{e.participants.length}</Text>
                <Text style={styles.countsLabel}>peserta</Text>
              </View>
              <View style={styles.countsCell}>
                <Text style={styles.countsValue}>{e.committees.length}</Text>
                <Text style={styles.countsLabel}>panitia</Text>
              </View>
            </View>

            {e.committees.length > 0 && (
              <AvatarGroup
                avatars={e.committees.map((c) => ({ initials: initialsOf(c.name) }))}
                size={28}
                max={e.committees.length}
              />
            )}

            <View style={styles.meCard}>
              <View style={styles.meCell}>
                <Text style={styles.meValue}>{myTaskCount}</Text>
                <Text style={styles.meLabel}>tugas saya</Text>
              </View>
              <View style={styles.meDivider} />
              <View style={styles.meCell}>
                <Tag tone="outline" label="BELUM HADIR" />
                <Text style={styles.meLabel}>kehadiran dikonfirmasi</Text>
              </View>
            </View>

            {e.description && (
              <>
                <SectionHeader title="Catatan panitia" />
                <Text style={styles.note}>{e.description}</Text>
              </>
            )}
          </View>
        ) : null}

        {tab === 'Tugas' ? (
          <View style={styles.tabContent}>
            <SectionHeader title={`${e.tasks.length} tugas persiapan`} />
            {e.tasks.length === 0 ? (
              <Text style={styles.footnote}>Belum ada tugas untuk kegiatan ini.</Text>
            ) : (
              <View>
                {e.tasks.map((t, i) => (
                  <TaskItem
                    key={t.id}
                    title={t.title}
                    meta={t.dueDate ? `Tenggat ${formatDateShort(t.dueDate)}` : 'Tanpa tenggat'}
                    done={t.status === 'DONE'}
                    priority={t.priority === 'HIGH'}
                    onToggle={() => toggleTask(t.id, t.status)}
                    isLast={i === e.tasks.length - 1}
                  />
                ))}
              </View>
            )}
            <Text style={styles.footnote}>Ketuk tugas untuk menandai selesai.</Text>
            <Text style={styles.doneCountHint}>
              {doneCount} dari {e.tasks.length} selesai
            </Text>
          </View>
        ) : null}

        {tab === 'Anggaran' ? (
          <View style={styles.tabContent}>
            <Text style={styles.budgetTotal}>{formatRupiah(totalIncome - totalExpense)}</Text>
            <Text style={styles.budgetMeta}>
              Pemasukan {formatRupiah(totalIncome)} · pengeluaran {formatRupiah(totalExpense)}
            </Text>
            {eventTransactions.length === 0 ? (
              <Text style={styles.footnote}>Belum ada transaksi untuk kegiatan ini.</Text>
            ) : (
              <View style={styles.budgetTransactions}>
                {eventTransactions.map((t, i) => (
                  <TransactionItem
                    key={t.id}
                    kind={t.transactionType === 'INCOME' ? 'in' : 'out'}
                    title={t.description ?? t.categoryName ?? t.transactionTypeLabel}
                    meta={formatDateShort(t.transactionDate)}
                    amount={t.amount}
                    status={t.status === 'PENDING' ? 'MENUNGGU' : t.status === 'REJECTED' ? 'DITOLAK' : undefined}
                    isLast={i === eventTransactions.length - 1}
                  />
                ))}
              </View>
            )}
            <View style={styles.budgetAction}>
              <Button variant="secondary" label="+ Catat pengeluaran kegiatan" onPress={() => router.push('/kas')} />
            </View>
          </View>
        ) : null}
      </ScrollView>

      <View style={styles.actionBar}>
        <Button variant="primary" label="Konfirmasi kehadiran saya" onPress={() => setAttendanceOpen(true)} block />
      </View>

      <InfoSheet
        visible={attendanceOpen}
        title="Konfirmasi kehadiran"
        body="Fitur presensi kegiatan sedang disiapkan."
        onClose={() => setAttendanceOpen(false)}
      />
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { flex: 1, backgroundColor: theme.color.bg },
    shareAction: { fontFamily: 'Archivo_800ExtraBold', fontSize: 13, color: theme.color.accent },
    scroll: { paddingBottom: theme.space.xxxl },
    hero: { paddingHorizontal: theme.layout.screenPadding, paddingTop: theme.space.lg },
    title: { fontFamily: 'Archivo_800ExtraBold', fontSize: 30, letterSpacing: -0.75, color: theme.color.text, marginTop: theme.space.md },
    whenWhereRow: { flexDirection: 'row', marginTop: theme.space.lg, paddingTop: theme.space.lg, borderTopWidth: 2, borderTopColor: theme.color.rule },
    whenWhereCell: { flex: 1, gap: 2 },
    whenWhereDivider: { width: 1, alignSelf: 'stretch', backgroundColor: theme.color.divider, marginHorizontal: theme.space.lg },
    whenWhereLabel: { fontFamily: 'Archivo_800ExtraBold', fontSize: 11, letterSpacing: 1, color: theme.color.textMuted },
    whenWhereValue: { fontFamily: 'Archivo_800ExtraBold', fontSize: 14, color: theme.color.text, marginTop: 4 },
    whenWhereSub: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted },
    tabContent: { paddingHorizontal: theme.layout.screenPadding, paddingTop: theme.space.lg, gap: theme.space.md },
    countsRow: { flexDirection: 'row', gap: theme.space.xl },
    countsCell: {},
    countsValue: { fontFamily: 'Archivo_800ExtraBold', fontSize: 26, color: theme.color.text },
    countsLabel: { fontFamily: 'Archivo_400Regular', fontSize: 12, color: theme.color.textMuted },
    meCard: {
      flexDirection: 'row',
      borderWidth: 1,
      borderColor: theme.color.divider,
    },
    meCell: { flex: 1, padding: theme.space.md, gap: 4 },
    meDivider: { width: 1, alignSelf: 'stretch', backgroundColor: theme.color.divider },
    meValue: { fontFamily: 'Archivo_800ExtraBold', fontSize: 20, color: theme.color.text },
    meLabel: { fontFamily: 'Archivo_400Regular', fontSize: 12, color: theme.color.textMuted },
    note: { fontFamily: 'Archivo_400Regular', fontSize: 13.5, color: theme.color.text, lineHeight: 20 },
    footnote: { fontFamily: 'Archivo_400Regular', fontSize: 12.5, color: theme.color.textMuted },
    doneCountHint: { fontFamily: 'Archivo_600SemiBold', fontSize: 11, color: theme.color.textMuted },
    budgetTotal: { fontFamily: 'Archivo_800ExtraBold', fontSize: 34, color: theme.color.text, fontVariant: ['tabular-nums'] },
    budgetMeta: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted },
    budgetTransactions: { marginTop: theme.space.sm },
    budgetAction: { alignItems: 'flex-start', marginTop: theme.space.sm },
    actionBar: { borderTopWidth: 2, borderTopColor: theme.color.rule, padding: theme.space.md, paddingHorizontal: theme.layout.screenPadding },
  });
}
