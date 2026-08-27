import { router } from 'expo-router';
import { ScrollView, StyleSheet, Text, View } from 'react-native';

import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { ListItem } from '@/components/ListItem';
import { ScreenHeader } from '@/components/ScreenHeader';
import { SectionHeader } from '@/components/SectionHeader';
import { Skeleton } from '@/components/Skeleton';
import { Tag } from '@/components/Tag';
import { useVotes } from '@/lib/queries';
import { formatDateTimeShort } from '@/theme/format';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

export function VotingScreen() {
  const { theme } = useTheme();
  const styles = makeStyles(theme);

  const votes = useVotes();
  // Backend already sorts open-first (soonest-closing on top), then closed.
  const all = votes.data?.data ?? [];
  const open = all.filter((v) => v.isOpen);
  const closed = all.filter((v) => !v.isOpen);

  return (
    <View style={styles.root}>
      <ScreenHeader title="Voting" onBack={() => router.back()} />

      {votes.isPending ? (
        <View style={{ padding: theme.layout.screenPadding }}>
          <Skeleton width="100%" height={200} />
        </View>
      ) : votes.isError ? (
        <ErrorState title="Gagal memuat voting" body="Periksa koneksi internet Anda, lalu coba lagi." onRetry={() => votes.refetch()} />
      ) : all.length === 0 ? (
        <EmptyState title="Belum ada voting." body="" />
      ) : (
        <ScrollView contentContainerStyle={styles.scroll}>
          <SectionHeader title="Sedang berjalan" />
          {open.length === 0 ? (
            <View style={styles.emptyGroup}>
              <Text style={styles.emptyGroupText}>Tidak ada voting yang berjalan.</Text>
            </View>
          ) : (
            open.map((v, i) => (
              <ListItem
                key={v.id}
                title={v.question}
                subtitle={`Tutup ${formatDateTimeShort(v.endAt)} · ${v.participationCount} dari ${v.eligibleCount} sudah memilih`}
                trailing={<Tag tone="accent" label="Berjalan" />}
                onPress={() => router.push(`/voting/${v.id}`)}
                isLast={i === open.length - 1}
              />
            ))
          )}

          {closed.length > 0 ? (
            <>
              <SectionHeader title="Sudah ditutup" />
              {closed.map((v, i) => (
                <ListItem
                  key={v.id}
                  title={v.question}
                  subtitle={`Ditutup ${formatDateTimeShort(v.endAt)}`}
                  trailing={<Tag tone="outline" label="Ditutup" mutedFill />}
                  onPress={() => router.push(`/voting/${v.id}`)}
                  isLast={i === closed.length - 1}
                />
              ))}
            </>
          ) : null}
        </ScrollView>
      )}
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { flex: 1, backgroundColor: theme.color.bg },
    scroll: { paddingBottom: theme.space.xxxl },
    emptyGroup: { paddingHorizontal: theme.layout.screenPadding, paddingVertical: theme.space.sm },
    emptyGroupText: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted },
  });
}
