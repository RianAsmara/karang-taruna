import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';

import { Button } from '@/components/Button';
import { Dialog } from '@/components/Dialog';
import { ErrorState } from '@/components/ErrorState';
import { PermissionNote } from '@/components/PermissionNote';
import { ScreenHeader } from '@/components/ScreenHeader';
import { SectionHeader } from '@/components/SectionHeader';
import { Skeleton } from '@/components/Skeleton';
import { useToast } from '@/components/Toast';
import { VoteOption } from '@/components/VoteOption';
import { ApiError } from '@/lib/api';
import { useSubmitVoteResponse, useVote } from '@/lib/queries';
import { formatDateTimeShort } from '@/theme/format';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

export function VotingDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const { showToast } = useToast();

  const vote = useVote(id);
  const submit = useSubmitVoteResponse(id);

  const [editing, setEditing] = useState(false);
  const [selected, setSelected] = useState<string[]>([]);
  const [confirming, setConfirming] = useState(false);
  // Re-seed `selected` from the server once per vote, the first time it
  // loads — not in an effect (avoids an extra render) and not keyed on
  // `myOptionIds` itself (a new array reference on every background
  // refetch would otherwise wipe an in-progress selection while editing).
  const [seededFor, setSeededFor] = useState<string | null>(null);
  if (vote.data && seededFor !== vote.data.data.id) {
    setSeededFor(vote.data.data.id);
    setSelected(vote.data.data.myOptionIds);
  }

  if (vote.isPending) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Voting" onBack={() => router.back()} />
        <View style={{ padding: theme.layout.screenPadding }}>
          <Skeleton width="100%" height={160} />
        </View>
      </View>
    );
  }

  if (vote.isError) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Voting" onBack={() => router.back()} />
        <ErrorState title="Gagal memuat voting" body="Periksa koneksi internet Anda, lalu coba lagi." onRetry={() => vote.refetch()} />
      </View>
    );
  }

  const v = vote.data!.data;
  const editabilitySentence = v.editable ? 'Bisa diubah sampai voting ditutup.' : 'Tidak bisa diubah setelah dikirim.';
  const canSeeResults = !v.isOpen || v.hasResponded;
  const isRespondingNow = v.isOpen && v.isEligible && (!v.hasResponded || editing);

  const toggleOption = (optionId: string) => {
    setSelected((current) => {
      if (current.includes(optionId)) return current.filter((o) => o !== optionId);
      if (v.maxSelections === 1) return [optionId];
      if (current.length >= v.maxSelections) return current;
      return [...current, optionId];
    });
  };

  const confirmSubmit = () => {
    setConfirming(false);
    submit.mutate(selected, {
      onSuccess: () => {
        setEditing(false);
        showToast('Pilihan terkirim.');
      },
      onError: (err) => {
        if (err instanceof ApiError && err.status === 403) {
          // The vote closed between selecting and confirming — results
          // are viewable now regardless of what this screen's stale
          // `isOpen`/`hasResponded` still say.
          showToast('Voting sudah ditutup sebelum pilihan terkirim.');
          router.replace(`/voting/${id}/hasil`);
          return;
        }
        showToast(err instanceof ApiError ? err.message : 'Gagal mengirim pilihan.');
      },
    });
  };

  const selectedLabels = v.options
    .filter((o) => selected.includes(o.id))
    .map((o) => o.label)
    .join(', ');

  return (
    <View style={styles.root}>
      <ScreenHeader title="Voting" onBack={() => router.back()} />
      <ScrollView contentContainerStyle={styles.scroll}>
        <View style={styles.headerBlock}>
          <Text style={styles.question}>{v.question}</Text>
          {v.description ? <Text style={styles.description}>{v.description}</Text> : null}
        </View>

        <View style={styles.disclosure}>
          <Text style={styles.disclosureLine}>
            {v.anonymous ? 'Pilihan Anda tidak akan terlihat siapa pun.' : 'Pilihan Anda terlihat oleh pengurus.'}
          </Text>
          <Text style={styles.disclosureLine}>{editabilitySentence}</Text>
          <Text style={styles.disclosureLine}>Ditutup {formatDateTimeShort(v.endAt)}</Text>
        </View>

        {!v.isEligible ? (
          <PermissionNote body="Voting ini hanya untuk pengurus." roleHint="pengurus" />
        ) : isRespondingNow ? (
          <>
            <SectionHeader title="Pilihan" />
            {v.maxSelections > 1 ? <Text style={styles.maxSelections}>Pilih maksimal {v.maxSelections}</Text> : null}
            <View style={styles.optionsList}>
              {v.options.map((o, i) => (
                <VoteOption key={o.id} label={o.label} selected={selected.includes(o.id)} onPress={() => toggleOption(o.id)} isLast={i === v.options.length - 1} />
              ))}
            </View>
          </>
        ) : (
          <>
            <SectionHeader title="Pilihan" />
            <View style={styles.optionsList}>
              {v.options.map((o, i) => (
                <VoteOption
                  key={o.id}
                  label={o.label}
                  selected={v.myOptionIds.includes(o.id)}
                  disabled
                  onPress={() => {}}
                  isLast={i === v.options.length - 1}
                />
              ))}
            </View>
            {!v.isOpen ? (
              <View style={styles.closedNote}>
                <Text style={styles.closedNoteText}>Voting sudah ditutup.</Text>
              </View>
            ) : null}
          </>
        )}
      </ScrollView>

      {v.isEligible ? (
        <View style={styles.actionBar}>
          {isRespondingNow ? (
            <Button variant="primary" label="Kirim pilihan" onPress={() => setConfirming(true)} disabled={selected.length === 0} block />
          ) : v.isOpen && v.hasResponded && v.editable ? (
            <Button variant="secondary" label="Ubah pilihan" onPress={() => setEditing(true)} block />
          ) : canSeeResults ? (
            <Button variant="ghost" label="Lihat hasil" onPress={() => router.push(`/voting/${id}/hasil`)} block />
          ) : null}
        </View>
      ) : null}

      <Dialog
        visible={confirming}
        title="Kirim pilihan Anda?"
        body={`${selectedLabels}. ${editabilitySentence}`}
        confirmLabel="Kirim"
        onCancel={() => setConfirming(false)}
        onConfirm={confirmSubmit}
      />
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { flex: 1, backgroundColor: theme.color.bg },
    scroll: { paddingBottom: theme.space.xxxl },
    headerBlock: { paddingHorizontal: theme.layout.screenPadding, marginTop: theme.space.md, gap: theme.space.sm },
    question: { fontFamily: 'Archivo_800ExtraBold', fontSize: 26, color: theme.color.text },
    description: { fontFamily: 'Archivo_400Regular', fontSize: 15, color: theme.color.textMuted },
    disclosure: {
      marginHorizontal: theme.layout.screenPadding,
      marginTop: theme.space.md,
      padding: theme.space.md,
      backgroundColor: theme.color.surfaceAlt,
      borderLeftWidth: 2,
      borderLeftColor: theme.color.text,
      gap: 2,
    },
    disclosureLine: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.text },
    maxSelections: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted, paddingHorizontal: theme.layout.screenPadding, marginBottom: theme.space.xs },
    optionsList: { marginTop: theme.space.xs },
    closedNote: { paddingHorizontal: theme.layout.screenPadding, marginTop: theme.space.sm },
    closedNoteText: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted },
    actionBar: { paddingHorizontal: theme.layout.screenPadding, paddingVertical: theme.space.md, borderTopWidth: 2, borderTopColor: theme.color.text },
  });
}
