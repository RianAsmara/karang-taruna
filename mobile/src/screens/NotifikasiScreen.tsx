import { router } from 'expo-router';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { ListItem } from '@/components/ListItem';
import { SectionHeader } from '@/components/SectionHeader';
import { Skeleton } from '@/components/Skeleton';
import { type ApiNotification, useMarkNotificationRead, useNotifications } from '@/lib/queries';
import { formatDateTimeShort, formatRupiah } from '@/theme/format';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

const STATUS_LABEL: Record<string, string> = { APPROVED: 'disetujui', REJECTED: 'ditolak' };

function describe(n: ApiNotification): { title: string; goTo: () => void } {
  switch (n.type) {
    case 'TransactionSubmittedForReview':
      return {
        title: `Transaksi ${formatRupiah(Number(n.data.amount))} menunggu persetujuan Anda.`,
        goTo: () => router.push('/kas'),
      };
    case 'TransactionReviewed':
      return {
        title: `Transaksi ${formatRupiah(Number(n.data.amount))} ${STATUS_LABEL[String(n.data.status)] ?? String(n.data.status).toLowerCase()}.`,
        goTo: () => router.push('/kas'),
      };
    case 'FinancialReportPublished':
      return {
        title: `Laporan "${n.data.title}" telah dipublikasikan.`,
        goTo: () => router.push(`/kas/report/${n.data.report_id}`),
      };
    case 'ReportSubmittedForReview':
      return {
        title: `Laporan "${n.data.title}" siap diperiksa.`,
        goTo: () => router.push(`/kas/periksa-laporan/${n.data.report_id}`),
      };
    case 'FinancialReportReviewed':
      return {
        title: n.data.approved
          ? `Laporan "${n.data.title}" disetujui ketua.`
          : `Laporan "${n.data.title}" dikembalikan: ${n.data.revision_reason ?? ''}`,
        goTo: () => router.push('/kas/susun-laporan'),
      };
    case 'MemberDueReminder':
      return {
        title: `Iuran Anda sebesar ${formatRupiah(Number(n.data.amount_outstanding))} belum dibayar.`,
        goTo: () => router.push('/kas'),
      };
    default:
      return { title: n.type, goTo: () => {} };
  }
}

export function NotifikasiScreen() {
  const { theme } = useTheme();
  const insets = useSafeAreaInsets();
  const styles = makeStyles(theme);

  const notifications = useNotifications();
  const markRead = useMarkNotificationRead();

  const unread = (notifications.data?.data ?? []).filter((n) => !n.readAt);
  const read = (notifications.data?.data ?? []).filter((n) => n.readAt);

  const markAllRead = () => {
    unread.forEach((n) => markRead.mutate(n.id));
  };

  return (
    <View style={styles.root}>
      <View style={[styles.header, { height: theme.layout.headerHeight + insets.top, paddingTop: insets.top }]}>
        <Text style={styles.headerTitle}>Notifikasi</Text>
        {unread.length > 0 && (
          <Pressable onPress={markAllRead} hitSlop={theme.hitSlop} android_ripple={null} accessibilityRole="button">
            <Text style={styles.markRead}>Tandai dibaca</Text>
          </Pressable>
        )}
      </View>

      {notifications.isPending ? (
        <View style={styles.scroll}>
          <Skeleton width="100%" height={80} />
        </View>
      ) : notifications.isError ? (
        <ErrorState title="Gagal memuat notifikasi" body="Periksa koneksi internet Anda, lalu coba lagi." onRetry={() => notifications.refetch()} />
      ) : (
        <ScrollView contentContainerStyle={styles.scroll}>
          <SectionHeader title={`Perlu tindakan · ${unread.length}`} accent />
          {unread.length === 0 ? (
            <View style={styles.emptyPad}>
              <EmptyState title="Tidak ada yang perlu ditindaklanjuti" body="Notifikasi baru akan muncul di sini." />
            </View>
          ) : (
            <View style={styles.pendingCard}>
              {unread.map((n, i) => {
                const { title, goTo } = describe(n);
                return (
                  <View key={n.id} style={[styles.pendingRow, i < unread.length - 1 && styles.pendingDivider]}>
                    <Pressable
                      onPress={() => {
                        markRead.mutate(n.id);
                        goTo();
                      }}
                      android_ripple={null}
                      style={styles.pendingTouch}
                    >
                      <Text style={styles.pendingTitle}>{title}</Text>
                      <Text style={styles.pendingMeta}>{formatDateTimeShort(n.createdAt)}</Text>
                    </Pressable>
                  </View>
                );
              })}
            </View>
          )}

          {read.length > 0 && (
            <>
              <SectionHeader title="Kabar lain" />
              <View>
                {read.map((n, i) => {
                  const { title } = describe(n);
                  return (
                    <ListItem
                      key={n.id}
                      title={title}
                      subtitle={formatDateTimeShort(n.createdAt)}
                      isLast={i === read.length - 1}
                    />
                  );
                })}
              </View>
            </>
          )}
        </ScrollView>
      )}
    </View>
  );
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
    headerTitle: { fontFamily: 'Archivo_800ExtraBold', fontSize: 19, color: theme.color.text },
    markRead: { fontFamily: 'Archivo_800ExtraBold', fontSize: 12.5, color: theme.color.accent },
    scroll: { paddingBottom: theme.space.xxxl },
    emptyPad: { paddingHorizontal: theme.layout.screenPadding },
    pendingCard: {
      marginHorizontal: theme.layout.screenPadding,
      borderLeftWidth: 4,
      borderLeftColor: theme.color.accent,
      backgroundColor: theme.color.surface,
    },
    pendingRow: {},
    pendingDivider: { borderBottomWidth: 1, borderBottomColor: theme.color.divider },
    pendingTouch: { padding: theme.space.md, gap: 3 },
    pendingTitle: { fontFamily: 'Archivo_800ExtraBold', fontSize: 15, color: theme.color.text },
    pendingMeta: { fontFamily: 'Archivo_400Regular', fontSize: 12.5, color: theme.color.textMuted },
  });
}
