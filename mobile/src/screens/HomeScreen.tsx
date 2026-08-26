import { router } from 'expo-router';
import { useMemo, useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Avatar } from '@/components/Avatar';
import { BalanceDisplay } from '@/components/BalanceDisplay';
import { Card } from '@/components/Card';
import { EventItem } from '@/components/EventItem';
import { InfoSheet } from '@/components/InfoSheet';
import { SectionHeader } from '@/components/SectionHeader';
import { Skeleton } from '@/components/Skeleton';
import { TaskItem } from '@/components/TaskItem';
import { TransactionItem } from '@/components/TransactionItem';
import { useAnnouncements, useCurrentOrganization, useDues, useEvents, useMyTasks, useTransparency } from '@/lib/queries';
import { useAuth } from '@/store/useAuth';
import { currentMonthLabel, formatDateShort, formatEventDateParts, formatRupiah, formatTimeRange, initialsOf } from '@/theme/format';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

function currentMonthPeriod(): string {
  const now = new Date();
  return new Date(Date.UTC(now.getFullYear(), now.getMonth(), 1)).toISOString().slice(0, 10);
}

export function HomeScreen() {
  const { theme } = useTheme();
  const insets = useSafeAreaInsets();
  const styles = makeStyles(theme);
  const [orgSwitcherOpen, setOrgSwitcherOpen] = useState(false);

  const user = useAuth((state) => state.user);
  const organization = useCurrentOrganization();
  const transparency = useTransparency();
  const events = useEvents();
  const tasks = useMyTasks();
  const announcements = useAnnouncements();
  const dues = useDues();

  const nearestEvent = useMemo(() => {
    const now = new Date().getTime();
    return (events.data?.data ?? [])
      .filter((e) => new Date(e.startAt).getTime() >= now)
      .sort((a, b) => new Date(a.startAt).getTime() - new Date(b.startAt).getTime())[0];
  }, [events.data]);

  const myPendingDue = useMemo(() => {
    const period = currentMonthPeriod();
    return (dues.data?.data ?? []).find((d) => d.type === 'MONTHLY' && d.period === period && d.userId === user?.id && !d.isPaid);
  }, [dues.data, user?.id]);

  const latestAnnouncement = announcements.data?.data[0];

  if (transparency.isPending || events.isPending) {
    return (
      <View style={styles.root}>
        <View style={[styles.header, { height: theme.layout.headerHeight + insets.top, paddingTop: insets.top }]} />
        <View style={styles.scroll}>
          <Skeleton width="100%" height={160} />
        </View>
      </View>
    );
  }

  const summary = transparency.data!;

  return (
    <View style={styles.root}>
      <View style={[styles.header, { height: theme.layout.headerHeight + insets.top, paddingTop: insets.top }]}>
        <View style={styles.headerLeft}>
          <View style={styles.mark} />
          <Pressable
            onPress={() => setOrgSwitcherOpen(true)}
            hitSlop={theme.hitSlop}
            android_ripple={null}
            accessibilityRole="button"
            accessibilityLabel="Ganti organisasi"
          >
            <Text style={styles.orgName} numberOfLines={1}>
              {organization.data?.data.name ?? ''}
            </Text>
            <Text style={styles.orgMeta} numberOfLines={1}>
              ▾
            </Text>
          </Pressable>
        </View>
        <Pressable
          onPress={() => router.push('/profil')}
          hitSlop={theme.hitSlop}
          android_ripple={null}
          accessibilityRole="button"
          accessibilityLabel="Profil"
        >
          <Avatar size={32} initials={user ? initialsOf(user.name) : ''} />
        </Pressable>
      </View>

      <ScrollView contentContainerStyle={styles.scroll}>
        <Text style={styles.greeting}>Halo, {user?.name.split(' ')[0] ?? ''}.</Text>

        <BalanceDisplay
          label="KAS KITA SEKARANG"
          amount={summary.balance}
          meta={formatMonthYearNow()}
          income={summary.monthIncome}
          expense={summary.monthExpense}
          onPress={() => router.push('/kas')}
        />

        {myPendingDue ? (
          <>
            <View style={styles.spacer} />
            <Card inverted onPress={() => router.push('/iuran-saya')} style={styles.pendingCard}>
              <SectionHeader title="PERLU TINDAKAN" onAccentBackground />
              <View style={styles.pendingBody}>
                <Text style={styles.pendingTitle}>Iuran {currentMonthLabel()} belum dibayar</Text>
                <Text style={styles.pendingSub}>{formatRupiah(myPendingDue.amountOutstanding)} · ketuk untuk bayar.</Text>
              </View>
            </Card>
          </>
        ) : null}

        <View style={styles.spacer} />

        <SectionHeader title="Kegiatan terdekat" action="Semua" onAction={() => router.push('/kegiatan')} />
        <Card>
          {nearestEvent ? (
            <EventItem
              {...formatEventDateParts(nearestEvent.startAt)}
              title={nearestEvent.title}
              time={formatTimeRange(nearestEvent.startAt, nearestEvent.endAt)}
              place={nearestEvent.location ?? undefined}
              state="scheduled"
              onPress={() => router.push(`/home/event/${nearestEvent.id}`)}
            />
          ) : (
            <View style={styles.emptyRow}>
              <Text style={styles.emptyText}>Belum ada kegiatan mendatang.</Text>
            </View>
          )}
        </Card>
        <View style={styles.rule} />

        <SectionHeader title={`Tugas saya · ${tasks.data?.data.length ?? 0}`} />
        <Card>
          {(tasks.data?.data.length ?? 0) === 0 ? (
            <View style={styles.emptyRow}>
              <Text style={styles.emptyText}>Tidak ada tugas untuk Anda saat ini.</Text>
            </View>
          ) : (
            tasks.data!.data.map((t, i) => (
              <TaskItem
                key={t.id}
                title={t.title}
                meta={t.dueDate ? `Tenggat ${formatDateShort(t.dueDate)}` : 'Tanpa tenggat'}
                done={t.status === 'DONE'}
                priority={t.priority === 'HIGH'}
                isLast={i === tasks.data!.data.length - 1}
              />
            ))
          )}
        </Card>
        <View style={styles.rule} />

        {latestAnnouncement ? (
          <>
            <SectionHeader title="Pengumuman" />
            <View style={styles.announcement}>
              <Text style={styles.announcementTitle}>{latestAnnouncement.title}</Text>
              <Text style={styles.announcementBody}>{latestAnnouncement.body}</Text>
              {latestAnnouncement.publishedAt && (
                <Text style={styles.announcementByline}>{formatDateShort(latestAnnouncement.publishedAt)}</Text>
              )}
            </View>
            <View style={styles.rule} />
          </>
        ) : null}

        <SectionHeader title="Aktivitas terbaru" action="Lihat kas" onAction={() => router.push('/kas')} />
        <Card>
          {summary.recentTransactions.length === 0 ? (
            <View style={styles.emptyRow}>
              <Text style={styles.emptyText}>Belum ada transaksi.</Text>
            </View>
          ) : (
            summary.recentTransactions.map((t, i) => (
              <TransactionItem
                key={i}
                kind={t.transactionType === 'INCOME' ? 'in' : 'out'}
                title={t.description ?? 'Transaksi'}
                meta={formatDateShort(t.transactionDate)}
                amount={t.amount}
                isLast={i === summary.recentTransactions.length - 1}
              />
            ))
          )}
        </Card>
      </ScrollView>

      <InfoSheet
        visible={orgSwitcherOpen}
        title="Ganti organisasi"
        body="Ganti organisasi akan tersedia setelah Anda bergabung di lebih dari satu organisasi."
        onClose={() => setOrgSwitcherOpen(false)}
      />
    </View>
  );
}

function formatMonthYearNow(): string {
  return new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(new Date());
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { flex: 1, backgroundColor: theme.color.bg },
    header: {
      borderBottomWidth: 2,
      borderBottomColor: theme.color.rule,
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      paddingHorizontal: theme.layout.screenPadding,
    },
    headerLeft: { flexDirection: 'row', alignItems: 'center', gap: theme.space.sm, flexShrink: 1 },
    mark: { width: 26, height: 26, backgroundColor: theme.color.accent },
    orgName: { fontFamily: 'Archivo_800ExtraBold', fontSize: 15, color: theme.color.text },
    orgMeta: { fontFamily: 'Archivo_400Regular', fontSize: 11, color: theme.color.textMuted },
    scroll: { paddingBottom: theme.space.xxxl },
    greeting: { fontFamily: 'Archivo_400Regular', fontSize: 14, color: theme.color.textMuted, padding: theme.layout.screenPadding, paddingBottom: 0 },
    spacer: { height: theme.space.md },
    pendingCard: { padding: 0 },
    pendingBody: { paddingHorizontal: theme.layout.screenPadding, paddingBottom: theme.space.lg, gap: 4 },
    pendingTitle: { fontFamily: 'Archivo_800ExtraBold', fontSize: 19, color: theme.color.onAccent },
    pendingSub: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.onAccent, opacity: 0.9 },
    rule: { height: 2, backgroundColor: theme.color.rule, marginTop: theme.space.lg },
    emptyRow: { padding: theme.layout.screenPadding },
    emptyText: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted },
    announcement: {
      borderLeftWidth: 4,
      borderLeftColor: theme.color.text,
      paddingLeft: theme.space.md,
      marginHorizontal: theme.layout.screenPadding,
    },
    announcementTitle: { fontFamily: 'Archivo_800ExtraBold', fontSize: 15, color: theme.color.text },
    announcementBody: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.text, marginTop: 2 },
    announcementByline: { fontFamily: 'Archivo_400Regular', fontSize: 11, color: theme.color.textMuted, marginTop: 4 },
  });
}
