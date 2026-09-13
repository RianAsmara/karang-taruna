import * as DocumentPicker from 'expo-document-picker';
import { router } from 'expo-router';
import { useMemo, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';

import { BottomSheet } from '@/components/BottomSheet';
import { Button } from '@/components/Button';
import { DocumentItem } from '@/components/DocumentItem';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { FilterChip } from '@/components/FilterChip';
import { FormField, FormSection } from '@/components/FormSection';
import { ListItem } from '@/components/ListItem';
import { ScreenHeader } from '@/components/ScreenHeader';
import { SectionHeader } from '@/components/SectionHeader';
import { Skeleton } from '@/components/Skeleton';
import { useToast } from '@/components/Toast';
import { ApiError } from '@/lib/api';
import { type ApiDocument, type ApiEvent, useCurrentOrganization, useDocuments, useEvents, useUploadDocument } from '@/lib/queries';
import { formatFileSize } from '@/theme/format';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

const CATEGORIES = [
  { value: 'PROPOSAL', label: 'Proposal' },
  { value: 'NOTULEN', label: 'Notulen' },
  { value: 'LAPORAN', label: 'Laporan' },
  { value: 'SURAT', label: 'Surat' },
  { value: 'ORGANISASI', label: 'Dokumen organisasi' },
];

const FILTERS = ['Semua', ...CATEGORIES.map((c) => c.label)] as const;

const MONTH_YEAR_UPPER = new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' });

function kindOf(doc: ApiDocument): string {
  if (doc.mimeType.includes('pdf')) return 'PDF';
  if (doc.mimeType.startsWith('image/')) return doc.mimeType.split('/')[1] ?? 'IMG';
  if (doc.originalName.includes('.')) return doc.originalName.split('.').pop()!.toUpperCase();
  return 'FILE';
}

export function DokumenScreen() {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const { showToast } = useToast();
  const [filter, setFilter] = useState<(typeof FILTERS)[number]>('Semua');
  const [uploadOpen, setUploadOpen] = useState(false);

  const organization = useCurrentOrganization();
  const documents = useDocuments();

  const myRole = organization.data?.membership.role;
  const canUpload = myRole === 'KETUA' || myRole === 'SEKRETARIS' || myRole === 'BENDAHARA';

  const groups = useMemo(() => {
    const filtered = (documents.data?.data ?? []).filter(
      (d) => filter === 'Semua' || d.categoryLabel === filter,
    );
    const byMonth = new Map<string, ApiDocument[]>();
    for (const doc of filtered) {
      const label = MONTH_YEAR_UPPER.format(new Date(doc.createdAt));
      const list = byMonth.get(label) ?? [];
      list.push(doc);
      byMonth.set(label, list);
    }
    return Array.from(byMonth.entries());
  }, [documents.data, filter]);

  return (
    <View style={styles.root}>
      <ScreenHeader
        title="Dokumen"
        size="lg"
        right={
          <Text style={styles.searchAction} onPress={() => router.push({ pathname: '/pencarian', params: { scope: 'dokumen', filter } })}>
            ⌕ Cari
          </Text>
        }
      />

      {documents.isPending ? (
        <View style={{ padding: theme.layout.screenPadding }}>
          <Skeleton width="100%" height={200} />
        </View>
      ) : documents.isError ? (
        <ErrorState title="Gagal memuat dokumen" body="Periksa koneksi internet Anda, lalu coba lagi." onRetry={() => documents.refetch()} />
      ) : (
        <ScrollView contentContainerStyle={styles.scroll}>
          <View style={styles.chipRow}>
            {FILTERS.map((f) => (
              <FilterChip key={f} label={f} active={filter === f} onPress={() => setFilter(f)} />
            ))}
          </View>

          {groups.length === 0 ? (
            <EmptyState title={`Belum ada ${filter === 'Semua' ? 'dokumen' : filter.toLowerCase()}.`} body="" />
          ) : (
            groups.map(([month, docs]) => (
              <View key={month}>
                <SectionHeader title={month} />
                <View style={styles.list}>
                  {docs.map((doc, i) => (
                    <DocumentItem
                      key={doc.id}
                      title={doc.title}
                      category={doc.categoryLabel}
                      uploader={doc.uploader?.name ?? ''}
                      date={new Date(doc.createdAt).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })}
                      sizeLabel={formatFileSize(doc.sizeBytes)}
                      kind={kindOf(doc)}
                      onPress={() => router.push(`/dokumen/${doc.id}`)}
                      isLast={i === docs.length - 1}
                    />
                  ))}
                </View>
              </View>
            ))
          )}
        </ScrollView>
      )}

      {canUpload ? (
        <View style={styles.actionBar}>
          <Button variant="primary" label="Unggah dokumen" onPress={() => setUploadOpen(true)} block />
        </View>
      ) : null}

      <UploadSheet
        visible={uploadOpen}
        isTreasurerOnly={myRole === 'BENDAHARA'}
        onClose={() => setUploadOpen(false)}
        onSuccess={() => {
          setUploadOpen(false);
          showToast('Dokumen diunggah.');
        }}
      />
    </View>
  );
}

function UploadSheet({
  visible,
  isTreasurerOnly,
  onClose,
  onSuccess,
}: {
  visible: boolean;
  isTreasurerOnly: boolean;
  onClose: () => void;
  onSuccess: () => void;
}) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const { showToast } = useToast();

  const availableCategories = isTreasurerOnly ? CATEGORIES.filter((c) => c.value === 'LAPORAN') : CATEGORIES;

  const [title, setTitle] = useState('');
  const [category, setCategory] = useState(availableCategories[0]!.value);
  const [eventId, setEventId] = useState<string | null>(null);
  const [eventTitle, setEventTitle] = useState<string | null>(null);
  const [eventPickerOpen, setEventPickerOpen] = useState(false);
  const [picked, setPicked] = useState<DocumentPicker.DocumentPickerAsset | null>(null);

  const events = useEvents();
  const upload = useUploadDocument();

  const pickFile = async () => {
    const result = await DocumentPicker.getDocumentAsync({
      type: ['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
    });
    if (!result.canceled) {
      setPicked(result.assets[0]!);
      if (!title) setTitle(result.assets[0]!.name.replace(/\.[^.]+$/, ''));
    }
  };

  const submit = () => {
    if (!picked) return;

    const form = new FormData();
    form.append('title', title.trim());
    form.append('category', category);
    if (eventId) form.append('event_id', eventId);
    form.append('file', {
      uri: picked.uri,
      name: picked.name,
      type: picked.mimeType ?? 'application/octet-stream',
    } as unknown as Blob);

    upload.mutate(form, {
      onSuccess: () => {
        setTitle('');
        setPicked(null);
        setEventId(null);
        setEventTitle(null);
        onSuccess();
      },
      onError: (err) => showToast(err instanceof ApiError ? err.message : 'Gagal mengunggah dokumen.'),
    });
  };

  return (
    <BottomSheet visible={visible} title="Unggah dokumen" onClose={onClose}>
      <View style={styles.sheetBody}>
        <FormSection title="Dokumen">
          <FormField label="Judul" value={title} onChangeText={setTitle} placeholder="Notulen Rapat Agustus" />
          <View style={styles.field}>
            <Text style={styles.fieldLabel}>Kategori</Text>
            <View style={styles.chipRow}>
              {availableCategories.map((c) => (
                <FilterChip key={c.value} label={c.label} active={category === c.value} onPress={() => setCategory(c.value)} />
              ))}
            </View>
          </View>
          <ListItem title={eventTitle ?? 'Tidak ada'} subtitle="Kegiatan terkait (opsional)" onPress={() => setEventPickerOpen(true)} isLast />
          <View style={styles.filePicker}>
            <Button variant="secondary" label={picked ? picked.name : 'Pilih file'} onPress={pickFile} />
          </View>
        </FormSection>
        <View style={styles.sheetAction}>
          <Button variant="primary" label="Unggah" onPress={submit} loading={upload.isPending} disabled={!picked || !title.trim()} block />
        </View>
      </View>

      <BottomSheet visible={eventPickerOpen} title="Pilih kegiatan" onClose={() => setEventPickerOpen(false)} scrollable={false}>
        <ScrollView style={styles.pickerScroll}>
          <ListItem
            title="Tidak ada"
            onPress={() => {
              setEventId(null);
              setEventTitle(null);
              setEventPickerOpen(false);
            }}
          />
          {(events.data?.data ?? []).map((e: ApiEvent, i, arr) => (
            <ListItem
              key={e.id}
              title={e.title}
              onPress={() => {
                setEventId(e.id);
                setEventTitle(e.title);
                setEventPickerOpen(false);
              }}
              isLast={i === arr.length - 1}
            />
          ))}
        </ScrollView>
      </BottomSheet>
    </BottomSheet>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { flex: 1, backgroundColor: theme.color.bg },
    searchAction: { fontFamily: 'Archivo_800ExtraBold', fontSize: 13, color: theme.color.accent },
    scroll: { paddingBottom: theme.space.xxxl },
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
    sheetBody: { gap: theme.space.md, paddingBottom: theme.space.md },
    field: { paddingHorizontal: theme.layout.screenPadding, gap: theme.space.xs },
    fieldLabel: { fontFamily: 'Archivo_800ExtraBold', fontSize: 11, letterSpacing: 1.0, textTransform: 'uppercase', color: theme.color.textMuted },
    filePicker: { paddingHorizontal: theme.layout.screenPadding },
    sheetAction: { paddingHorizontal: theme.layout.screenPadding },
    pickerScroll: { maxHeight: 400 },
  });
}
