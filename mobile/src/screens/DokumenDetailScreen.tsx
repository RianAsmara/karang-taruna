import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';

import { Button } from '@/components/Button';
import { Dialog } from '@/components/Dialog';
import { ErrorState } from '@/components/ErrorState';
import { ListItem } from '@/components/ListItem';
import { ScreenHeader } from '@/components/ScreenHeader';
import { SectionHeader } from '@/components/SectionHeader';
import { Skeleton } from '@/components/Skeleton';
import { Tag } from '@/components/Tag';
import { useToast } from '@/components/Toast';
import { ApiError } from '@/lib/api';
import { downloadAndShareDocument } from '@/lib/documentTransfer';
import { useCurrentOrganization, useDeleteDocument, useDocument } from '@/lib/queries';
import { formatDateShort, formatFileSize } from '@/theme/format';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

export function DokumenDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const { showToast } = useToast();

  const [deleteConfirm, setDeleteConfirm] = useState(false);
  const [transferring, setTransferring] = useState(false);

  const organization = useCurrentOrganization();
  const document = useDocument(id);
  const deleteDocument = useDeleteDocument();

  if (document.isPending) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Dokumen" onBack={() => router.back()} />
        <View style={{ padding: theme.layout.screenPadding }}>
          <Skeleton width="100%" height={160} />
        </View>
      </View>
    );
  }

  if (document.isError) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Dokumen" onBack={() => router.back()} />
        <ErrorState title="Dokumen gagal dimuat." body="Coba lagi." onRetry={() => document.refetch()} />
      </View>
    );
  }

  const d = document.data!.data;
  const myUserId = organization.data?.membership.id;
  const canDelete = organization.data?.membership.role === 'KETUA'
    || organization.data?.membership.role === 'SEKRETARIS'
    || organization.data?.membership.role === 'BENDAHARA'
    || d.uploader?.id === myUserId;

  const transfer = async () => {
    setTransferring(true);
    try {
      await downloadAndShareDocument(d.id, d.originalName);
    } catch {
      showToast('Gagal mengunduh dokumen.');
    } finally {
      setTransferring(false);
    }
  };

  return (
    <View style={styles.root}>
      <ScreenHeader title={d.title} onBack={() => router.back()} />
      <ScrollView contentContainerStyle={styles.scroll}>
        <Text style={styles.title}>{d.title}</Text>
        <View style={styles.metaRow}>
          <Tag tone="outline" label={d.categoryLabel} />
        </View>
        <Text style={styles.meta}>
          Diunggah {d.uploader?.name ?? ''} · {formatDateShort(d.createdAt)} · {formatFileSize(d.sizeBytes)}
        </Text>

        <View style={styles.preview}>
          <Text style={styles.previewKind}>{d.mimeType.split('/').pop()?.slice(0, 4).toUpperCase()}</Text>
          <Text style={styles.previewNote}>Tidak bisa dilihat di aplikasi</Text>
        </View>

        {d.event ? (
          <>
            <SectionHeader title="Terkait" />
            <ListItem title={d.event.title} onPress={() => router.push(`/kegiatan/event/${d.event!.id}`)} isLast />
          </>
        ) : null}

        {canDelete ? (
          <View style={styles.deleteRow}>
            <Button variant="ghost" label="Hapus dokumen" onPress={() => setDeleteConfirm(true)} />
          </View>
        ) : null}
      </ScrollView>

      <View style={styles.actionBar}>
        <Button variant="primary" label="Bagikan" onPress={transfer} loading={transferring} block />
        <Button variant="secondary" label="Unduh" onPress={transfer} loading={transferring} block />
      </View>

      <Dialog
        visible={deleteConfirm}
        title="Hapus dokumen"
        body={`Hapus "${d.title}"? Tindakan ini tidak bisa dibatalkan.`}
        confirmLabel="Hapus"
        onCancel={() => setDeleteConfirm(false)}
        onConfirm={() => {
          deleteDocument.mutate(d.id, {
            onSuccess: () => {
              setDeleteConfirm(false);
              router.replace('/dokumen');
            },
            onError: (err) => {
              setDeleteConfirm(false);
              showToast(err instanceof ApiError ? err.message : 'Gagal menghapus dokumen.');
            },
          });
        }}
      />
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { flex: 1, backgroundColor: theme.color.bg },
    scroll: { paddingBottom: theme.space.xxxl, paddingHorizontal: theme.layout.screenPadding },
    title: { fontFamily: 'Archivo_800ExtraBold', fontSize: 22, color: theme.color.text, marginTop: theme.space.md },
    metaRow: { flexDirection: 'row', marginTop: theme.space.sm },
    meta: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted, marginTop: theme.space.sm },
    preview: {
      marginTop: theme.space.lg,
      height: 200,
      borderWidth: 1,
      borderColor: theme.color.divider,
      alignItems: 'center',
      justifyContent: 'center',
      gap: theme.space.sm,
    },
    previewKind: { fontFamily: 'Archivo_800ExtraBold', fontSize: 22, color: theme.color.textMuted },
    previewNote: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textFaint },
    deleteRow: { alignItems: 'center', marginTop: theme.space.lg },
    actionBar: { paddingHorizontal: theme.layout.screenPadding, paddingVertical: theme.space.md, borderTopWidth: 2, borderTopColor: theme.color.text, gap: theme.space.sm },
  });
}
