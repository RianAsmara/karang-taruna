import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';

import { Button } from '@/components/Button';
import { ErrorState } from '@/components/ErrorState';
import { ListItem } from '@/components/ListItem';
import { ScreenHeader } from '@/components/ScreenHeader';
import { SectionHeader } from '@/components/SectionHeader';
import { SharePreview } from '@/components/SharePreview';
import { Skeleton } from '@/components/Skeleton';
import { VoteOption } from '@/components/VoteOption';
import { useVoteResults } from '@/lib/queries';
import { formatDateTimeShort } from '@/theme/format';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

export function VotingResultScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const [shareOpen, setShareOpen] = useState(false);

  const results = useVoteResults(id, true);

  if (results.isPending) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Hasil voting" onBack={() => router.back()} />
        <View style={{ padding: theme.layout.screenPadding }}>
          <Skeleton width="100%" height={160} />
        </View>
      </View>
    );
  }

  if (results.isError) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Hasil voting" onBack={() => router.back()} />
        <ErrorState title="Gagal memuat hasil" body="Periksa koneksi internet Anda, lalu coba lagi." onRetry={() => results.refetch()} />
      </View>
    );
  }

  const r = results.data!.data;
  const sortedOptions = [...r.options].sort((a, b) => b.count - a.count);
  const winners = sortedOptions.filter((o) => r.winningOptionIds.includes(o.id));
  const percentSuppressed = r.options.length > 0 && r.options.every((o) => o.percent === null) && r.participationCount > 0;

  const shareLines = [
    'RukunMuda',
    '',
    'HASIL VOTING',
    r.question,
    '',
    ...sortedOptions.map((o) => `${o.label}: ${o.count} suara${o.percent != null ? ` (${o.percent}%)` : ''}`),
    '',
    `${r.participationCount} dari ${r.eligibleCount} memilih · Ditutup ${formatDateTimeShort(r.closedAt)}`,
  ];

  return (
    <View style={styles.root}>
      <ScreenHeader title="Hasil voting" onBack={() => router.back()} />
      <ScrollView contentContainerStyle={styles.scroll}>
        <View style={styles.headerBlock}>
          <Text style={styles.question}>{r.question}</Text>
          <Text style={styles.statusLine}>
            Ditutup {formatDateTimeShort(r.closedAt)} · {r.participationCount} dari {r.eligibleCount} memilih
          </Text>
        </View>

        {winners.length > 0 ? (
          <View style={styles.winnerBlock}>
            <Text style={styles.winnerLabel}>{r.isTie ? winners.map((w) => w.label).join(' / ') : winners[0]!.label}</Text>
            <Text style={styles.winnerMeta}>
              {r.isTie ? 'Seri' : `${winners[0]!.count} suara${winners[0]!.percent != null ? ` · ${winners[0]!.percent}%` : ''}`}
            </Text>
          </View>
        ) : null}

        <View style={styles.rule} />

        <View style={styles.optionsList}>
          {sortedOptions.map((o, i) => (
            <VoteOption
              key={o.id}
              label={o.label}
              selected={false}
              disabled
              onPress={() => {}}
              result={{ count: o.count, percent: o.percent ?? undefined }}
              isLast={i === sortedOptions.length - 1}
            />
          ))}
        </View>

        <Text style={styles.anonymityNote}>
          {r.anonymous ? 'Voting ini anonim. Daftar pemilih tidak disimpan.' : 'Pengurus dapat melihat siapa memilih apa.'}
        </Text>
        {percentSuppressed ? (
          <Text style={styles.anonymityNote}>Terlalu sedikit suara untuk ditampilkan sebagai persen.</Text>
        ) : null}

        {r.breakdown ? (
          <>
            <SectionHeader title="Siapa memilih apa" />
            {r.breakdown.map((group) => (
              <View key={group.id}>
                <Text style={styles.breakdownGroupLabel}>{group.label}</Text>
                {group.members.length === 0 ? (
                  <Text style={styles.breakdownEmpty}>Belum ada yang memilih.</Text>
                ) : (
                  group.members.map((member, i) => (
                    <ListItem key={member.id} title={member.name} isLast={i === group.members.length - 1} />
                  ))
                )}
              </View>
            ))}
          </>
        ) : null}
      </ScrollView>

      <View style={styles.actionBar}>
        <Button variant="ghost" label="Bagikan hasil" onPress={() => setShareOpen(true)} block />
      </View>

      <SharePreview
        visible={shareOpen}
        onClose={() => setShareOpen(false)}
        title="Bagikan hasil"
        lines={shareLines}
        onShare={() => setShareOpen(false)}
        onCopy={() => setShareOpen(false)}
      />
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { flex: 1, backgroundColor: theme.color.bg },
    scroll: { paddingBottom: theme.space.xxxl },
    headerBlock: { paddingHorizontal: theme.layout.screenPadding, marginTop: theme.space.md, gap: theme.space.xs },
    question: { fontFamily: 'Archivo_800ExtraBold', fontSize: 26, color: theme.color.text },
    statusLine: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted },
    winnerBlock: { paddingHorizontal: theme.layout.screenPadding, marginTop: theme.space.md, gap: 2 },
    winnerLabel: { fontFamily: 'Archivo_800ExtraBold', fontSize: 22, color: theme.color.text },
    winnerMeta: { fontFamily: 'Archivo_800ExtraBold', fontSize: 15, color: theme.color.textMuted, fontVariant: ['tabular-nums'] },
    rule: { height: 2, backgroundColor: theme.color.rule, marginTop: theme.space.md },
    optionsList: {},
    anonymityNote: {
      fontFamily: 'Archivo_400Regular',
      fontSize: 13,
      color: theme.color.textFaint,
      paddingHorizontal: theme.layout.screenPadding,
      marginTop: theme.space.sm,
    },
    breakdownGroupLabel: {
      fontFamily: 'Archivo_800ExtraBold',
      fontSize: 14,
      color: theme.color.text,
      paddingHorizontal: theme.layout.screenPadding,
      marginTop: theme.space.sm,
    },
    breakdownEmpty: {
      fontFamily: 'Archivo_400Regular',
      fontSize: 13,
      color: theme.color.textMuted,
      paddingHorizontal: theme.layout.screenPadding,
      marginTop: 2,
    },
    actionBar: { paddingHorizontal: theme.layout.screenPadding, paddingVertical: theme.space.md, borderTopWidth: 2, borderTopColor: theme.color.text },
  });
}
