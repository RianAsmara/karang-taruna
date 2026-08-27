import { router } from 'expo-router';
import { useMemo, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';

import { Button } from '@/components/Button';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { FilterChip } from '@/components/FilterChip';
import { ScreenHeader } from '@/components/ScreenHeader';
import { SectionHeader } from '@/components/SectionHeader';
import { Skeleton } from '@/components/Skeleton';
import { SponsorItem } from '@/components/SponsorItem';
import { type ApiSponsorContribution, useCurrentOrganization, useSponsorContributions } from '@/lib/queries';
import { formatRupiah } from '@/theme/format';
import { resolveSponsorStatus } from '@/theme/vocab';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

const FILTERS = ['Semua', 'Diajukan', 'Setuju', 'Diterima'] as const;
type Filter = (typeof FILTERS)[number];
const FILTER_STATUS: Record<Exclude<Filter, 'Semua'>, string> = {
  Diajukan: 'DIAJUKAN',
  Setuju: 'SETUJU',
  Diterima: 'DITERIMA',
};

const NO_EVENT_GROUP = 'Tanpa kegiatan';

export function SponsorScreen() {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const [filter, setFilter] = useState<Filter>('Semua');

  const organization = useCurrentOrganization();
  const contributions = useSponsorContributions();

  const myRole = organization.data?.membership.role;
  const canAdd = myRole === 'KETUA' || myRole === 'BENDAHARA';

  const totalThisYear = useMemo(() => {
    const year = new Date().getFullYear();
    return (contributions.data?.data ?? [])
      .filter((c) => c.status !== 'DIAJUKAN' && c.status !== 'BATAL' && new Date(c.createdAt).getFullYear() === year)
      .reduce((sum, c) => sum + (c.amount ?? 0), 0);
  }, [contributions.data]);

  const groups = useMemo(() => {
    const filtered = (contributions.data?.data ?? []).filter(
      (c) => filter === 'Semua' || c.status === FILTER_STATUS[filter as Exclude<Filter, 'Semua'>],
    );
    const byEvent = new Map<string, ApiSponsorContribution[]>();
    for (const c of filtered) {
      const key = c.event?.title ?? NO_EVENT_GROUP;
      const list = byEvent.get(key) ?? [];
      list.push(c);
      byEvent.set(key, list);
    }
    const entries = Array.from(byEvent.entries());
    entries.sort((a, b) => {
      if (a[0] === NO_EVENT_GROUP) return 1;
      if (b[0] === NO_EVENT_GROUP) return -1;
      return b[1][0]!.createdAt.localeCompare(a[1][0]!.createdAt);
    });
    return entries;
  }, [contributions.data, filter]);

  return (
    <View style={styles.root}>
      <ScreenHeader title="Sponsor" onBack={() => router.back()} />

      {contributions.isPending ? (
        <View style={{ padding: theme.layout.screenPadding }}>
          <Skeleton width="100%" height={200} />
        </View>
      ) : contributions.isError ? (
        <ErrorState title="Gagal memuat sponsor" body="Periksa koneksi internet Anda, lalu coba lagi." onRetry={() => contributions.refetch()} />
      ) : (
        <ScrollView contentContainerStyle={styles.scroll}>
          <Text style={styles.summaryLabel}>TOTAL DUKUNGAN TAHUN INI</Text>
          <Text style={styles.summaryAmount}>{formatRupiah(totalThisYear)}</Text>

          <View style={styles.chipRow}>
            {FILTERS.map((f) => (
              <FilterChip key={f} label={f} active={filter === f} onPress={() => setFilter(f)} />
            ))}
          </View>

          {groups.length === 0 ? (
            <EmptyState
              title="Belum ada sponsor."
              body=""
              actionLabel={canAdd ? 'Tambah sponsor' : undefined}
              onAction={canAdd ? () => router.push('/sponsor/tambah') : undefined}
            />
          ) : (
            groups.map(([eventTitle, items]) => (
              <View key={eventTitle}>
                <SectionHeader title={eventTitle} />
                <View style={styles.list}>
                  {items.map((c, i) => (
                    <SponsorItem
                      key={c.id}
                      name={c.sponsor.name}
                      type={c.typeLabel}
                      amount={c.amount ?? undefined}
                      status={resolveSponsorStatus(c.status)}
                      eventName={c.event?.title}
                      onPress={() => router.push(`/sponsor/${c.id}`)}
                      isLast={i === items.length - 1}
                    />
                  ))}
                </View>
              </View>
            ))
          )}
        </ScrollView>
      )}

      {canAdd ? (
        <View style={styles.actionBar}>
          <Button variant="primary" label="Tambah sponsor" onPress={() => router.push('/sponsor/tambah')} block />
        </View>
      ) : null}
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { flex: 1, backgroundColor: theme.color.bg },
    scroll: { paddingBottom: theme.space.xxxl },
    summaryLabel: {
      fontFamily: 'Archivo_600SemiBold',
      fontSize: 11,
      letterSpacing: 0.7,
      color: theme.color.textMuted,
      paddingHorizontal: theme.layout.screenPadding,
      marginTop: theme.space.md,
    },
    summaryAmount: {
      fontFamily: 'Archivo_800ExtraBold',
      fontSize: 22,
      color: theme.color.text,
      paddingHorizontal: theme.layout.screenPadding,
      fontVariant: ['tabular-nums'],
    },
    chipRow: {
      flexDirection: 'row',
      flexWrap: 'wrap',
      gap: theme.space.sm,
      paddingHorizontal: theme.layout.screenPadding,
      marginTop: theme.space.md,
      marginBottom: theme.space.sm,
    },
    list: { marginHorizontal: theme.layout.screenPadding },
    actionBar: { paddingHorizontal: theme.layout.screenPadding, paddingVertical: theme.space.md, borderTopWidth: 2, borderTopColor: theme.color.text },
  });
}
