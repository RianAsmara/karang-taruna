import { router } from 'expo-router';
import { useMemo, useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

import { Button } from '@/components/Button';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { EventItem, type EventItemState } from '@/components/EventItem';
import { InfoSheet } from '@/components/InfoSheet';
import { ScreenHeader } from '@/components/ScreenHeader';
import { SectionHeader } from '@/components/SectionHeader';
import { Segmented } from '@/components/Segmented';
import { Skeleton } from '@/components/Skeleton';
import { type ApiEvent, useEvents } from '@/lib/queries';
import { formatEventDateParts, formatTimeRange } from '@/theme/format';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

const SEGMENTS = ['Akan datang', 'Berjalan', 'Selesai'] as const;

const MONTH_LONG_UPPER = new Intl.DateTimeFormat('id-ID', { month: 'long' });

function groupLabel(startAt: string): string {
  const now = new Date();
  const eventDate = new Date(startAt);
  const daysAway = Math.ceil((eventDate.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));
  if (daysAway <= 7) return 'MINGGU INI';
  return MONTH_LONG_UPPER.format(eventDate).toUpperCase();
}

function segmentFilter(e: ApiEvent, segment: (typeof SEGMENTS)[number]): boolean {
  if (segment === 'Berjalan') return e.status === 'ONGOING';
  if (segment === 'Selesai') return e.status === 'COMPLETED';
  return e.status === 'DRAFT' || e.status === 'PLANNED';
}

export function KegiatanScreen() {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const [segment, setSegment] = useState<(typeof SEGMENTS)[number]>('Akan datang');
  const [newEventOpen, setNewEventOpen] = useState(false);
  const [searchOpen, setSearchOpen] = useState(false);

  const events = useEvents();

  const groups = useMemo(() => {
    const filtered = (events.data?.data ?? [])
      .filter((e) => segmentFilter(e, segment))
      .sort((a, b) => new Date(a.startAt).getTime() - new Date(b.startAt).getTime());

    const byGroup = new Map<string, ApiEvent[]>();
    for (const e of filtered) {
      const label = groupLabel(e.startAt);
      const list = byGroup.get(label) ?? [];
      list.push(e);
      byGroup.set(label, list);
    }
    return Array.from(byGroup.entries());
  }, [events.data, segment]);

  return (
    <View style={styles.root}>
      <ScreenHeader
        title="Kegiatan"
        size="lg"
        right={
          <Pressable onPress={() => setSearchOpen(true)} hitSlop={theme.hitSlop} android_ripple={null} accessibilityRole="button">
            <Text style={styles.searchAction}>⌕ Cari</Text>
          </Pressable>
        }
      />
      <View style={styles.segmentedWrap}>
        <Segmented options={SEGMENTS} value={segment} onChange={setSegment} />
      </View>
      <View style={styles.rule} />

      {events.isPending ? (
        <View style={styles.scroll}>
          <Skeleton width="100%" height={100} />
        </View>
      ) : events.isError ? (
        <ErrorState title="Gagal memuat kegiatan" body="Periksa koneksi internet Anda, lalu coba lagi." onRetry={() => events.refetch()} />
      ) : (
        <ScrollView contentContainerStyle={styles.scroll}>
          {groups.length === 0 ? (
            <EmptyState
              title="Belum ada kegiatan"
              body="Kegiatan yang dibuat pengurus akan muncul di sini."
              actionLabel="Buat kegiatan"
              onAction={() => setNewEventOpen(true)}
            />
          ) : (
            groups.map(([group, items], groupIndex) => (
              <View key={group}>
                <SectionHeader title={group} />
                <View style={styles.list}>
                  {items.map((e, i) => (
                    <EventItem
                      key={e.id}
                      {...formatEventDateParts(e.startAt)}
                      title={e.title}
                      time={formatTimeRange(e.startAt, e.endAt)}
                      place={e.location ?? undefined}
                      peopleCaption={
                        e.committeeCount != null && e.participantCount != null
                          ? `${e.committeeCount} panitia · ${e.participantCount} peserta`
                          : undefined
                      }
                      state={eventVisualState(e, groupIndex === 0 && i === 0)}
                      onPress={() => router.push(`/kegiatan/event/${e.id}`)}
                    />
                  ))}
                </View>
              </View>
            ))
          )}

          <View style={styles.addAction}>
            <Button variant="secondary" label="+ Buat kegiatan baru" onPress={() => setNewEventOpen(true)} block />
          </View>
        </ScrollView>
      )}

      <InfoSheet
        visible={newEventOpen}
        title="Buat kegiatan baru"
        body="Formulir kegiatan baru sedang disiapkan."
        onClose={() => setNewEventOpen(false)}
      />
      <InfoSheet
        visible={searchOpen}
        title="Cari kegiatan"
        body="Pencarian kegiatan sedang disiapkan."
        onClose={() => setSearchOpen(false)}
      />
    </View>
  );
}

function eventVisualState(e: ApiEvent, isNext: boolean): EventItemState {
  if (e.status === 'CANCELLED') return 'blocked';
  return isNext ? 'scheduled' : 'planned';
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { flex: 1, backgroundColor: theme.color.bg },
    searchAction: { fontFamily: 'Archivo_800ExtraBold', fontSize: 13, color: theme.color.accent },
    segmentedWrap: { paddingTop: theme.space.md },
    rule: { height: 2, backgroundColor: theme.color.rule, marginTop: theme.space.md },
    scroll: { paddingBottom: theme.space.xxxl },
    list: { marginHorizontal: theme.layout.screenPadding, gap: theme.space.md },
    addAction: { paddingHorizontal: theme.layout.screenPadding, marginTop: theme.space.lg },
  });
}
