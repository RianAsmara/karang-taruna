import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';

import { BottomSheet } from '@/components/BottomSheet';
import { Button } from '@/components/Button';
import { Dialog } from '@/components/Dialog';
import { FilterChip } from '@/components/FilterChip';
import { FormField, FormSection } from '@/components/FormSection';
import { ListItem } from '@/components/ListItem';
import { MemberItem } from '@/components/MemberItem';
import { ScreenHeader } from '@/components/ScreenHeader';
import { Segmented } from '@/components/Segmented';
import { Skeleton } from '@/components/Skeleton';
import { useToast } from '@/components/Toast';
import { ApiError } from '@/lib/api';
import {
  type ApiInventoryItem,
  type ApiMember,
  useCreateInventoryItem,
  useDeleteInventoryItem,
  useInventoryItem,
  useMembers,
  useUpdateInventoryItem,
} from '@/lib/queries';
import { initialsOf } from '@/theme/format';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

const CATEGORIES = [
  { value: 'SOUND_SYSTEM', label: 'Sound system' },
  { value: 'KURSI_MEJA', label: 'Kursi & meja' },
  { value: 'TENDA', label: 'Tenda' },
  { value: 'OLAHRAGA', label: 'Olahraga' },
  { value: 'LAIN_LAIN', label: 'Lain-lain' },
];

const CONDITIONS = ['Baik', 'Perlu perbaikan', 'Rusak'] as const;
const CONDITION_VALUES: Record<(typeof CONDITIONS)[number], string> = {
  Baik: 'BAIK',
  'Perlu perbaikan': 'PERLU_PERBAIKAN',
  Rusak: 'RUSAK',
};
const CONDITION_LABELS: Record<string, (typeof CONDITIONS)[number]> = {
  BAIK: 'Baik',
  PERLU_PERBAIKAN: 'Perlu perbaikan',
  RUSAK: 'Rusak',
};

/** Route-level: waits for the existing item (when editing) before mounting the form, so the form's useState initial values never need to be synced in afterwards via an effect. */
export function BarangFormScreen() {
  const { id } = useLocalSearchParams<{ id?: string }>();
  const { theme } = useTheme();
  const styles = makeStyles(theme);

  const existing = useInventoryItem(id ?? '');

  if (id && existing.isPending) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Ubah barang" onBack={() => router.back()} />
        <View style={{ padding: theme.layout.screenPadding }}>
          <Skeleton width="100%" height={160} />
        </View>
      </View>
    );
  }

  return <BarangForm id={id} initial={id ? (existing.data?.data ?? null) : null} />;
}

function BarangForm({ id, initial }: { id?: string; initial: ApiInventoryItem | null }) {
  const isEditing = Boolean(id);
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const { showToast } = useToast();

  const members = useMembers();
  const createItem = useCreateInventoryItem();
  const updateItem = useUpdateInventoryItem(id ?? '');
  const deleteItem = useDeleteInventoryItem();

  const [name, setName] = useState(initial?.name ?? '');
  const [category, setCategory] = useState(initial?.category ?? 'SOUND_SYSTEM');
  const [quantity, setQuantity] = useState(String(initial?.quantity ?? 1));
  const [condition, setCondition] = useState<(typeof CONDITIONS)[number]>(
    initial ? (CONDITION_LABELS[initial.condition] ?? 'Baik') : 'Baik',
  );
  const [notes, setNotes] = useState(initial?.notes ?? '');
  const [responsibleId, setResponsibleId] = useState<string | null>(initial?.responsible?.id ?? null);
  const [responsibleName, setResponsibleName] = useState<string | null>(initial?.responsible?.name ?? null);
  const [pickerOpen, setPickerOpen] = useState(false);
  const [deleteConfirm, setDeleteConfirm] = useState(false);

  const saving = createItem.isPending || updateItem.isPending;
  const canSave = name.trim() && Number(quantity) >= 1;

  const submit = () => {
    const body = {
      name: name.trim(),
      category,
      quantity: Number(quantity),
      condition: CONDITION_VALUES[condition],
      notes: notes.trim() || undefined,
      responsible_membership_id: responsibleId ?? undefined,
    };

    const onSuccess = () => {
      showToast(isEditing ? 'Perubahan disimpan.' : 'Barang ditambahkan.');
      router.back();
    };
    const onError = (err: unknown) => showToast(err instanceof ApiError ? err.message : 'Gagal menyimpan barang.');

    if (isEditing) {
      updateItem.mutate(body, { onSuccess, onError });
    } else {
      createItem.mutate(body, { onSuccess, onError });
    }
  };

  return (
    <View style={styles.root}>
      <ScreenHeader title={isEditing ? 'Ubah barang' : 'Tambah barang'} onBack={() => router.back()} />
      <ScrollView contentContainerStyle={styles.scroll}>
        <FormSection title="Barang">
          <FormField label="Nama" value={name} onChangeText={setName} placeholder="Tenda Pleton" autoFocus={!isEditing} />
          <View style={styles.field}>
            <Text style={styles.fieldLabel}>Kategori</Text>
            <View style={styles.chipRow}>
              {CATEGORIES.map((c) => (
                <FilterChip key={c.value} label={c.label} active={category === c.value} onPress={() => setCategory(c.value)} />
              ))}
            </View>
          </View>
          <FormField label="Jumlah" value={quantity} onChangeText={setQuantity} placeholder="1" keyboardType="numeric" />
        </FormSection>

        <FormSection title="Kondisi">
          <View style={styles.field}>
            <Text style={styles.fieldLabel}>Kondisi</Text>
            <Segmented options={CONDITIONS} value={condition} onChange={setCondition} />
          </View>
          <FormField label="Catatan (opsional)" value={notes} onChangeText={setNotes} placeholder="Catatan kondisi barang" multiline numberOfLines={3} />
        </FormSection>

        <FormSection title="Penanggung jawab">
          <ListItem
            title={responsibleName ?? 'Belum ditentukan'}
            subtitle="Ketuk untuk memilih"
            onPress={() => setPickerOpen(true)}
            isLast
          />
        </FormSection>

        {isEditing ? (
          <View style={styles.deleteRow}>
            <Button variant="ghost" label="Hapus barang" onPress={() => setDeleteConfirm(true)} />
          </View>
        ) : null}
      </ScrollView>

      <View style={styles.actionBar}>
        <Button
          variant="primary"
          label={isEditing ? 'Simpan perubahan' : 'Simpan'}
          onPress={submit}
          loading={saving}
          disabled={!canSave}
          block
        />
      </View>

      <BottomSheet visible={pickerOpen} title="Pilih penanggung jawab" onClose={() => setPickerOpen(false)}>
        <ScrollView style={styles.pickerScroll}>
          {(members.data?.data ?? [])
            .filter((m) => !m.leftAt)
            .map((m: ApiMember, i, arr) => (
              <MemberItem
                key={m.id}
                initials={initialsOf(m.name)}
                name={m.name}
                roles={[m.roleLabel]}
                onPress={() => {
                  setResponsibleId(m.id);
                  setResponsibleName(m.name);
                  setPickerOpen(false);
                }}
                isLast={i === arr.length - 1}
              />
            ))}
        </ScrollView>
      </BottomSheet>

      <Dialog
        visible={deleteConfirm}
        title="Hapus barang"
        body={`Hapus "${name}" dari inventaris? Tindakan ini tidak bisa dibatalkan.`}
        confirmLabel="Hapus"
        onCancel={() => setDeleteConfirm(false)}
        onConfirm={() => {
          if (!id) return;
          deleteItem.mutate(id, {
            onSuccess: () => {
              setDeleteConfirm(false);
              router.replace('/inventaris');
            },
            onError: (err) => {
              setDeleteConfirm(false);
              showToast(err instanceof ApiError ? err.message : 'Gagal menghapus barang.');
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
    scroll: { paddingBottom: theme.space.xxxl },
    field: { paddingHorizontal: theme.layout.screenPadding, gap: theme.space.xs },
    fieldLabel: { fontFamily: 'Archivo_800ExtraBold', fontSize: 11, letterSpacing: 1.0, textTransform: 'uppercase', color: theme.color.textMuted },
    chipRow: { flexDirection: 'row', flexWrap: 'wrap', gap: theme.space.sm },
    deleteRow: { alignItems: 'center', marginTop: theme.space.md },
    actionBar: { paddingHorizontal: theme.layout.screenPadding, paddingVertical: theme.space.md, borderTopWidth: 2, borderTopColor: theme.color.text },
    pickerScroll: { maxHeight: 400 },
  });
}
