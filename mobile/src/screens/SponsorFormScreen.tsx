import { router } from 'expo-router';
import { useMemo, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';

import { BottomSheet } from '@/components/BottomSheet';
import { Button } from '@/components/Button';
import { Dialog } from '@/components/Dialog';
import { FormField, FormSection } from '@/components/FormSection';
import { ListItem } from '@/components/ListItem';
import { ScreenHeader } from '@/components/ScreenHeader';
import { Segmented } from '@/components/Segmented';
import { useToast } from '@/components/Toast';
import { ApiError } from '@/lib/api';
import { type ApiEvent, useCreateSponsorContribution, useEvents, useSponsorContributions } from '@/lib/queries';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

const TYPES = ['Uang', 'Barang', 'Jasa'] as const;
const TYPE_VALUES: Record<(typeof TYPES)[number], 'UANG' | 'BARANG' | 'JASA'> = {
  Uang: 'UANG',
  Barang: 'BARANG',
  Jasa: 'JASA',
};

export function SponsorFormScreen() {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const { showToast } = useToast();

  const [name, setName] = useState('');
  const [type, setType] = useState<(typeof TYPES)[number]>('Uang');
  const [amount, setAmount] = useState('');
  const [description, setDescription] = useState('');
  const [eventId, setEventId] = useState<string | null>(null);
  const [eventTitle, setEventTitle] = useState<string | null>(null);
  const [eventPickerOpen, setEventPickerOpen] = useState(false);
  const [contactName, setContactName] = useState('');
  const [contactPhone, setContactPhone] = useState('');
  const [notes, setNotes] = useState('');
  const [duplicateConfirm, setDuplicateConfirm] = useState(false);

  const events = useEvents();
  const existing = useSponsorContributions();
  const create = useCreateSponsorContribution();

  const existingSponsorNames = useMemo(
    () => new Set((existing.data?.data ?? []).map((c) => c.sponsor.name.trim().toLowerCase())),
    [existing.data],
  );

  const canSave = name.trim() && (type !== 'Uang' ? description.trim() : Number(amount) > 0);

  const submit = () => {
    create.mutate(
      {
        name: name.trim(),
        type: TYPE_VALUES[type],
        amount: type === 'Uang' ? Number(amount) : undefined,
        description: type !== 'Uang' ? description.trim() : undefined,
        event_id: eventId ?? undefined,
        contact_name: contactName.trim() || undefined,
        contact_phone: contactPhone.trim() || undefined,
        notes: notes.trim() || undefined,
      },
      {
        onSuccess: () => {
          showToast('Sponsor ditambahkan.');
          router.back();
        },
        onError: (err) => showToast(err instanceof ApiError ? err.message : 'Gagal menambahkan sponsor.'),
      },
    );
  };

  const onSavePress = () => {
    if (existingSponsorNames.has(name.trim().toLowerCase())) {
      setDuplicateConfirm(true);
      return;
    }
    submit();
  };

  return (
    <View style={styles.root}>
      <ScreenHeader title="Tambah sponsor" onBack={() => router.back()} />
      <ScrollView contentContainerStyle={styles.scroll}>
        <FormSection title="Sponsor">
          <FormField label="Nama" value={name} onChangeText={setName} placeholder="Toko Maju Jaya" autoFocus />
          <View style={styles.field}>
            <Text style={styles.fieldLabel}>Jenis</Text>
            <Segmented options={TYPES} value={type} onChange={setType} />
          </View>
          {type === 'Uang' ? (
            <FormField label="Jumlah" value={amount} onChangeText={setAmount} placeholder="0" keyboardType="numeric" />
          ) : (
            <FormField label="Deskripsi" value={description} onChangeText={setDescription} placeholder="Deskripsi dukungan" multiline numberOfLines={2} />
          )}
        </FormSection>

        <FormSection title="Kegiatan">
          <ListItem title={eventTitle ?? 'Tidak ada'} subtitle="Ketuk untuk memilih (opsional)" onPress={() => setEventPickerOpen(true)} isLast />
        </FormSection>

        <FormSection title="Kontak">
          <FormField label="Nama (opsional)" value={contactName} onChangeText={setContactName} placeholder="Nama kontak" />
          <FormField label="Nomor WhatsApp (opsional)" value={contactPhone} onChangeText={setContactPhone} placeholder="0812..." keyboardType="phone-pad" />
        </FormSection>

        <FormSection title="Catatan">
          <FormField label="Catatan (opsional)" value={notes} onChangeText={setNotes} placeholder="Catatan tambahan" multiline numberOfLines={3} />
        </FormSection>
      </ScrollView>

      <View style={styles.actionBar}>
        <Button variant="primary" label="Simpan" onPress={onSavePress} loading={create.isPending} disabled={!canSave} block />
      </View>

      <BottomSheet visible={eventPickerOpen} title="Pilih kegiatan" onClose={() => setEventPickerOpen(false)}>
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

      <Dialog
        visible={duplicateConfirm}
        title="Sponsor sudah ada"
        body={`Sponsor ini sudah ada — tambahkan sebagai dukungan baru?`}
        confirmLabel="Tambahkan"
        onCancel={() => setDuplicateConfirm(false)}
        onConfirm={() => {
          setDuplicateConfirm(false);
          submit();
        }}
      />
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { flex: 1, backgroundColor: theme.color.bg },
    scroll: { paddingBottom: theme.space.xxxl },
    field: { paddingHorizontal: theme.layout.screenPadding, gap: theme.space.xs },
    fieldLabel: { fontFamily: 'Archivo_800ExtraBold', fontSize: 11, letterSpacing: 1.0, textTransform: 'uppercase', color: theme.color.textMuted },
    actionBar: { paddingHorizontal: theme.layout.screenPadding, paddingVertical: theme.space.md, borderTopWidth: 2, borderTopColor: theme.color.text },
    pickerScroll: { maxHeight: 400 },
  });
}
