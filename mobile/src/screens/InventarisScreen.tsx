import { router } from 'expo-router';
import { useMemo, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';

import { Button } from '@/components/Button';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { FilterChip } from '@/components/FilterChip';
import { InventoryItem } from '@/components/InventoryItem';
import { ScreenHeader } from '@/components/ScreenHeader';
import { SectionHeader } from '@/components/SectionHeader';
import { Skeleton } from '@/components/Skeleton';
import { type ApiInventoryItem, useCurrentOrganization, useInventoryItems } from '@/lib/queries';
import { resolveInventoryAvailability } from '@/theme/vocab';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

const FILTERS = ['Semua', 'Tersedia', 'Dipinjam', 'Perlu perbaikan'] as const;
type Filter = (typeof FILTERS)[number];

const CATEGORY_ORDER = ['SOUND_SYSTEM', 'KURSI_MEJA', 'TENDA', 'OLAHRAGA', 'LAIN_LAIN'];

function matchesFilter(item: ApiInventoryItem, filter: Filter): boolean {
  if (filter === 'Semua') return true;
  if (filter === 'Perlu perbaikan') return item.condition === 'PERLU_PERBAIKAN';
  const availability = resolveInventoryAvailability(item);
  if (filter === 'Tersedia') return availability === 'available';
  return availability === 'borrowed';
}

export function InventarisScreen() {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const [filter, setFilter] = useState<Filter>('Semua');

  const organization = useCurrentOrganization();
  const items = useInventoryItems();

  const myRole = organization.data?.membership.role;
  const canAdd = myRole === 'KETUA' || myRole === 'SEKRETARIS';

  const groups = useMemo(() => {
    const filtered = (items.data?.data ?? []).filter((i) => matchesFilter(i, filter));
    const byCategory = new Map<string, ApiInventoryItem[]>();
    for (const item of filtered) {
      const list = byCategory.get(item.category) ?? [];
      list.push(item);
      byCategory.set(item.category, list);
    }
    return CATEGORY_ORDER.filter((c) => byCategory.has(c)).map((c) => [c, byCategory.get(c)!] as const);
  }, [items.data, filter]);

  const total = items.data?.data.length ?? 0;
  const borrowedCount = (items.data?.data ?? []).filter((i) => resolveInventoryAvailability(i) === 'borrowed').length;

  return (
    <View style={styles.root}>
      <ScreenHeader
        title="Inventaris"
        size="lg"
        right={
          <Text style={styles.searchAction} onPress={() => router.push({ pathname: '/pencarian', params: { scope: 'inventaris', filter } })}>
            ⌕ Cari
          </Text>
        }
      />

      {items.isPending ? (
        <View style={{ padding: theme.layout.screenPadding }}>
          <Skeleton width="100%" height={200} />
        </View>
      ) : items.isError ? (
        <ErrorState title="Gagal memuat inventaris" body="Periksa koneksi internet Anda, lalu coba lagi." onRetry={() => items.refetch()} />
      ) : (
        <ScrollView contentContainerStyle={styles.scroll}>
          <Text style={styles.countLine}>
            {total} jenis barang · {borrowedCount} dipinjam
          </Text>

          <View style={styles.chipRow}>
            {FILTERS.map((f) => (
              <FilterChip key={f} label={f} active={filter === f} onPress={() => setFilter(f)} />
            ))}
          </View>

          {groups.length === 0 ? (
            <EmptyState
              title={canAdd ? 'Belum ada barang.' : 'Belum ada barang yang dicatat.'}
              body=""
              actionLabel={canAdd ? 'Tambah barang' : undefined}
              onAction={canAdd ? () => router.push('/inventaris/tambah') : undefined}
            />
          ) : (
            groups.map(([category, groupItems]) => (
              <View key={category}>
                <SectionHeader title={groupItems[0]!.categoryLabel} />
                <View style={styles.list}>
                  {groupItems.map((item, i) => (
                    <InventoryItem
                      key={item.id}
                      name={item.name}
                      category={item.categoryLabel}
                      available={item.availableQuantity}
                      total={item.quantity}
                      condition={item.conditionLabel}
                      status={resolveInventoryAvailability(item)}
                      onPress={() => router.push(`/inventaris/${item.id}`)}
                      isLast={i === groupItems.length - 1}
                    />
                  ))}
                </View>
              </View>
            ))
          )}
        </ScrollView>
      )}

      {canAdd && total > 0 ? (
        <View style={styles.actionBar}>
          <Button variant="primary" label="Tambah barang" onPress={() => router.push('/inventaris/tambah')} block />
        </View>
      ) : null}
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { flex: 1, backgroundColor: theme.color.bg },
    searchAction: { fontFamily: 'Archivo_800ExtraBold', fontSize: 13, color: theme.color.accent },
    scroll: { paddingBottom: theme.space.xxxl },
    countLine: {
      fontFamily: 'Archivo_400Regular',
      fontSize: 13,
      color: theme.color.textMuted,
      paddingHorizontal: theme.layout.screenPadding,
      marginTop: theme.space.md,
    },
    chipRow: {
      flexDirection: 'row',
      flexWrap: 'wrap',
      gap: theme.space.sm,
      paddingHorizontal: theme.layout.screenPadding,
      marginTop: theme.space.sm,
      marginBottom: theme.space.sm,
    },
    list: { marginHorizontal: theme.layout.screenPadding },
    actionBar: { paddingHorizontal: theme.layout.screenPadding, paddingVertical: theme.space.md, borderTopWidth: 2, borderTopColor: theme.color.text },
  });
}
