import { router, useLocalSearchParams } from 'expo-router';
import { useEffect, useMemo, useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { EventItem, type EventItemState } from '@/components/EventItem';
import { FilterChip } from '@/components/FilterChip';
import { SearchField } from '@/components/FormSection';
import { ListItem } from '@/components/ListItem';
import { MemberItem } from '@/components/MemberItem';
import { SectionHeader } from '@/components/SectionHeader';
import { Skeleton } from '@/components/Skeleton';
import { addRecentSearch, clearRecentSearches, getRecentSearches } from '@/lib/recentSearches';
import { type ApiEvent, type ApiMember, useEvents, useMembers } from '@/lib/queries';
import { formatEventDateParts, formatTimeRange, initialsOf } from '@/theme/format';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

type Scope = 'anggota' | 'kegiatan';

const SCOPE_LABEL: Record<Scope, string> = {
  anggota: 'anggota',
  kegiatan: 'kegiatan',
};

const DEBOUNCE_MS = 250;
const MIN_QUERY_LENGTH = 2;

function matches(haystack: string, query: string): boolean {
  return haystack.toLowerCase().includes(query.toLowerCase());
}

function anggotaFilterPredicate(member: ApiMember, filter: string | undefined): boolean {
  if (!filter || filter === 'Semua') return true;
  if (filter === 'Pengurus') return member.role !== 'ANGGOTA';
  if (filter === 'Anggota') return member.role === 'ANGGOTA';
  if (filter === 'Baru') return Date.now() - new Date(member.joinedAt).getTime() < 30 * 24 * 60 * 60 * 1000;
  return true;
}

function kegiatanFilterPredicate(event: ApiEvent, filter: string | undefined): boolean {
  if (!filter) return true;
  if (filter === 'Berjalan') return event.status === 'ONGOING';
  if (filter === 'Selesai') return event.status === 'COMPLETED';
  if (filter === 'Akan datang') return event.status === 'DRAFT' || event.status === 'PLANNED';
  return true;
}

function eventState(event: ApiEvent): EventItemState {
  return event.status === 'CANCELLED' ? 'blocked' : 'planned';
}

export function PencarianScreen() {
  const params = useLocalSearchParams<{ scope: string; filter?: string }>();
  const scope: Scope = params.scope === 'kegiatan' ? 'kegiatan' : 'anggota';
  const { theme } = useTheme();
  const insets = useSafeAreaInsets();
  const styles = makeStyles(theme);

  const [query, setQuery] = useState('');
  const [debouncedQuery, setDebouncedQuery] = useState('');
  const [activeFilter, setActiveFilter] = useState(params.filter);
  const [recent, setRecent] = useState<string[]>([]);

  const members = useMembers();
  const events = useEvents();

  useEffect(() => {
    getRecentSearches(scope).then(setRecent);
  }, [scope]);

  useEffect(() => {
    const timer = setTimeout(() => setDebouncedQuery(query), DEBOUNCE_MS);
    return () => clearTimeout(timer);
  }, [query]);

  const isSearching = debouncedQuery.trim().length >= MIN_QUERY_LENGTH;

  const memberResults = useMemo(() => {
    if (scope !== 'anggota' || !isSearching) return [];
    return (members.data?.data ?? [])
      .filter((m) => !m.leftAt)
      .filter((m) => anggotaFilterPredicate(m, activeFilter))
      .filter((m) => matches(m.name, debouncedQuery));
  }, [scope, isSearching, members.data, activeFilter, debouncedQuery]);

  const eventResults = useMemo(() => {
    if (scope !== 'kegiatan' || !isSearching) return [];
    return (events.data?.data ?? [])
      .filter((e) => kegiatanFilterPredicate(e, activeFilter))
      .filter((e) => matches(e.title, debouncedQuery) || (e.location && matches(e.location, debouncedQuery)));
  }, [scope, isSearching, events.data, activeFilter, debouncedQuery]);

  const resultCount = scope === 'anggota' ? memberResults.length : eventResults.length;
  const isPending = scope === 'anggota' ? members.isPending : events.isPending;
  const isError = scope === 'anggota' ? members.isError : events.isError;

  useEffect(() => {
    if (isSearching) {
      addRecentSearch(scope, debouncedQuery).then(setRecent);
    }
  }, [isSearching, scope, debouncedQuery]);

  const runRecentSearch = (q: string) => setQuery(q);

  return (
    <View style={styles.root}>
      <View style={[styles.header, { height: theme.layout.headerHeight + insets.top, paddingTop: insets.top }]}>
        <Pressable onPress={() => router.back()} hitSlop={theme.hitSlop} android_ripple={null} accessibilityRole="button" accessibilityLabel="Kembali">
          <Text style={styles.back}>←</Text>
        </Pressable>
        <View style={styles.searchFieldWrap}>
          <SearchField value={query} onChangeText={setQuery} placeholder={`Cari ${SCOPE_LABEL[scope]}`} autoFocus />
        </View>
        {recent.length > 0 && !isSearching ? (
          <Pressable
            onPress={() => clearRecentSearches(scope).then(() => setRecent([]))}
            hitSlop={theme.hitSlop}
            android_ripple={null}
            accessibilityRole="button"
          >
            <Text style={styles.clearAction}>Hapus</Text>
          </Pressable>
        ) : null}
      </View>

      {activeFilter ? (
        <View style={styles.chipRow}>
          <FilterChip label={activeFilter} active onPress={() => setActiveFilter(undefined)} />
        </View>
      ) : null}

      {!isSearching ? (
        recent.length > 0 ? (
          <>
            <SectionHeader title="Pencarian terakhir" />
            <View>
              {recent.map((q, i) => (
                <ListItem key={q} title={q} onPress={() => runRecentSearch(q)} isLast={i === recent.length - 1} />
              ))}
            </View>
          </>
        ) : null
      ) : isPending ? (
        <ScrollView contentContainerStyle={styles.scroll}>
          <Skeleton width="100%" height={64} />
          <View style={styles.skeletonGap} />
          <Skeleton width="100%" height={64} />
          <View style={styles.skeletonGap} />
          <Skeleton width="100%" height={64} />
        </ScrollView>
      ) : isError ? (
        <ErrorState
          title="Gagal memuat data"
          body="Periksa koneksi internet Anda, lalu coba lagi."
          onRetry={() => (scope === 'anggota' ? members.refetch() : events.refetch())}
        />
      ) : resultCount === 0 ? (
        <EmptyState
          title={`Tidak ada hasil untuk "${debouncedQuery}".`}
          body={activeFilter ? '1 filter aktif — coba hapus filter.' : 'Coba kata kunci lain.'}
          actionLabel="Hapus pencarian"
          onAction={() => setQuery('')}
        />
      ) : (
        <ScrollView contentContainerStyle={styles.scroll}>
          <Text style={styles.resultCount}>{resultCount} hasil</Text>
          {scope === 'anggota'
            ? memberResults.map((m, i) => (
                <MemberItem
                  key={m.id}
                  initials={initialsOf(m.name)}
                  name={m.name}
                  roles={[m.roleLabel]}
                  onPress={() => router.push(`/anggota/${m.id}`)}
                  isLast={i === memberResults.length - 1}
                />
              ))
            : eventResults.map((e, i) => (
                <EventItem
                  key={e.id}
                  {...formatEventDateParts(e.startAt)}
                  title={e.title}
                  time={formatTimeRange(e.startAt, e.endAt)}
                  place={e.location ?? undefined}
                  state={eventState(e)}
                  onPress={() => router.push(`/kegiatan/event/${e.id}`)}
                />
              ))}
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
      paddingLeft: theme.space.md,
      paddingRight: theme.space.md,
      gap: theme.space.sm,
    },
    back: { fontFamily: 'Archivo_800ExtraBold', fontSize: 20, color: theme.color.text },
    searchFieldWrap: { flex: 1 },
    clearAction: { fontFamily: 'Archivo_800ExtraBold', fontSize: 13, color: theme.color.accent },
    chipRow: { flexDirection: 'row', paddingHorizontal: theme.layout.screenPadding, paddingTop: theme.space.md },
    scroll: { paddingBottom: theme.space.xxxl, paddingTop: theme.space.sm },
    skeletonGap: { height: theme.space.sm },
    resultCount: {
      fontFamily: 'Archivo_600SemiBold',
      fontSize: 11,
      letterSpacing: 0.7,
      color: theme.color.textMuted,
      paddingHorizontal: theme.layout.screenPadding,
      marginBottom: theme.space.sm,
    },
  });
}
