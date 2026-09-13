import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

import { BottomSheet } from '@/components/BottomSheet';
import { Button } from '@/components/Button';
import { Dialog } from '@/components/Dialog';
import { ErrorState } from '@/components/ErrorState';
import { DateField } from '@/components/DateField';
import { FormField } from '@/components/FormSection';
import { ListItem } from '@/components/ListItem';
import { MemberItem } from '@/components/MemberItem';
import { ScreenHeader } from '@/components/ScreenHeader';
import { SectionHeader } from '@/components/SectionHeader';
import { Segmented } from '@/components/Segmented';
import { Tag } from '@/components/Tag';
import { useToast } from '@/components/Toast';
import { ApiError } from '@/lib/api';
import {
  type ApiEvent,
  useBorrowInventoryItem,
  useCurrentOrganization,
  useDeleteInventoryItem,
  useEvents,
  useInventoryItem,
  useReturnInventoryLoan,
} from '@/lib/queries';
import { formatDateShort } from '@/theme/format';
import { INVENTORY_AVAILABILITY_TAG, resolveInventoryAvailability } from '@/theme/vocab';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

const CONDITIONS = ['Baik', 'Perlu perbaikan', 'Rusak'] as const;
const CONDITION_VALUES: Record<(typeof CONDITIONS)[number], string> = {
  Baik: 'BAIK',
  'Perlu perbaikan': 'PERLU_PERBAIKAN',
  Rusak: 'RUSAK',
};

export function InventarisDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const { showToast } = useToast();

  const [borrowOpen, setBorrowOpen] = useState(false);
  const [returnLoanId, setReturnLoanId] = useState<string | null>(null);
  const [deleteConfirm, setDeleteConfirm] = useState(false);

  const organization = useCurrentOrganization();
  const item = useInventoryItem(id);
  const deleteItem = useDeleteInventoryItem();

  const myRole = organization.data?.membership.role;
  const myMembershipId = organization.data?.membership.id;
  const canManage = myRole === 'KETUA' || myRole === 'SEKRETARIS';

  if (item.isPending) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Detail barang" onBack={() => router.back()} />
      </View>
    );
  }

  if (item.isError) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Detail barang" onBack={() => router.back()} />
        <ErrorState title="Gagal memuat barang" body="Periksa koneksi internet Anda, lalu coba lagi." onRetry={() => item.refetch()} />
      </View>
    );
  }

  const it = item.data!.data;
  const availability = resolveInventoryAvailability(it);
  const tag = INVENTORY_AVAILABILITY_TAG[availability];
  const activeLoans = (it.loans ?? []).filter((l) => l.status === 'BORROWED');
  const myLoan = activeLoans.find((l) => l.borrower.id === myMembershipId);

  return (
    <View style={styles.root}>
      <ScreenHeader title={it.name} onBack={() => router.back()} />
      <ScrollView contentContainerStyle={styles.scroll}>
        <View style={styles.headerBlock}>
          <Text style={styles.name}>{it.name}</Text>
          <Text style={styles.meta}>{it.categoryLabel}</Text>
          <View style={styles.tagRow}>
            <Tag tone={tag.tone} label={tag.label} mutedFill={tag.mutedFill} />
          </View>
        </View>

        <View style={styles.quantityBlock}>
          <Text style={styles.quantityText}>
            Jumlah {it.quantity} · Tersedia {it.availableQuantity}
          </Text>
        </View>

        <SectionHeader title="Kondisi" />
        <View style={styles.section}>
          <Text style={styles.conditionText}>{it.conditionLabel}</Text>
          {it.lastCheckedAt ? <Text style={styles.conditionMeta}>Terakhir diperiksa {formatDateShort(it.lastCheckedAt)}</Text> : null}
          {it.notes ? <Text style={styles.conditionMeta}>{it.notes}</Text> : null}
        </View>

        <SectionHeader title="Penanggung jawab" />
        {it.responsible ? (
          <MemberItem initials={initials(it.responsible.name)} name={it.responsible.name} roles={[]} isLast />
        ) : (
          <View style={styles.section}>
            <Text style={styles.conditionMeta}>Belum ditentukan.</Text>
          </View>
        )}

        <SectionHeader title="Sedang dipinjam" />
        {activeLoans.length === 0 ? (
          <View style={styles.section}>
            <Text style={styles.conditionMeta}>Tidak sedang dipinjam.</Text>
          </View>
        ) : (
          activeLoans.map((loan, i) => (
            <ListItem
              key={loan.id}
              title={`${loan.borrower.name} · ${loan.quantity}`}
              subtitle={loan.isOverdue ? `Terlambat` : `Kembali ${formatDateShort(loan.dueDate)}`}
              titleTone={loan.isOverdue ? 'accent' : 'default'}
              trailing={loan.borrower.id === myMembershipId ? <Button variant="ghost" label="Kembalikan" onPress={() => setReturnLoanId(loan.id)} /> : undefined}
              isLast={i === activeLoans.length - 1}
            />
          ))
        )}

        <SectionHeader title="Riwayat" />
        {(it.loans ?? []).filter((l) => l.status === 'RETURNED').length === 0 ? (
          <View style={styles.section}>
            <Text style={styles.conditionMeta}>Belum ada riwayat.</Text>
          </View>
        ) : (
          (it.loans ?? [])
            .filter((l) => l.status === 'RETURNED')
            .map((loan, i, arr) => (
              <ListItem
                key={loan.id}
                title={`${loan.borrower.name} · ${loan.quantity}`}
                subtitle={`Dikembalikan ${loan.returnedAt ? formatDateShort(loan.returnedAt) : ''}`}
                isLast={i === arr.length - 1}
              />
            ))
        )}

        {canManage ? (
          <View style={styles.manageRow}>
            <Button variant="secondary" label="Ubah barang" onPress={() => router.push(`/inventaris/tambah?id=${it.id}`)} />
            <Button variant="ghost" label="Hapus barang" onPress={() => setDeleteConfirm(true)} />
          </View>
        ) : null}
      </ScrollView>

      <View style={styles.actionBar}>
        {myLoan ? (
          <Button variant="primary" label="Kembalikan" onPress={() => setReturnLoanId(myLoan.id)} block />
        ) : (
          <Button variant="primary" label="Pinjam" onPress={() => setBorrowOpen(true)} disabled={it.availableQuantity <= 0} block />
        )}
      </View>

      <BorrowSheet
        visible={borrowOpen}
        itemId={it.id}
        available={it.availableQuantity}
        onClose={() => setBorrowOpen(false)}
        onSuccess={() => {
          setBorrowOpen(false);
          showToast('Barang dipinjam.');
        }}
      />

      {returnLoanId ? (
        <ReturnSheet
          itemId={it.id}
          loanId={returnLoanId}
          maxQuantity={(it.loans ?? []).find((l) => l.id === returnLoanId)?.quantity ?? 1}
          currentCondition={it.condition}
          onClose={() => setReturnLoanId(null)}
          onSuccess={() => {
            setReturnLoanId(null);
            showToast('Barang dikembalikan.');
          }}
        />
      ) : null}

      <Dialog
        visible={deleteConfirm}
        title="Hapus barang"
        body={`Hapus "${it.name}" dari inventaris? Tindakan ini tidak bisa dibatalkan.`}
        confirmLabel="Hapus"
        onCancel={() => setDeleteConfirm(false)}
        onConfirm={() => {
          deleteItem.mutate(it.id, {
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

function initials(name: string): string {
  return name
    .split(' ')
    .map((w) => w[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();
}

function BorrowSheet({
  visible,
  itemId,
  available,
  onClose,
  onSuccess,
}: {
  visible: boolean;
  itemId: string;
  available: number;
  onClose: () => void;
  onSuccess: () => void;
}) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const { showToast } = useToast();
  const [quantity, setQuantity] = useState(1);
  const [dueDate, setDueDate] = useState('');
  const [purpose, setPurpose] = useState('');
  const [eventPickerOpen, setEventPickerOpen] = useState(false);
  const [eventId, setEventId] = useState<string | null>(null);
  const [eventTitle, setEventTitle] = useState<string | null>(null);

  const events = useEvents();
  const borrow = useBorrowInventoryItem(itemId);

  const submit = () => {
    if (!dueDate || quantity < 1) return;

    borrow.mutate(
      { quantity, due_date: dueDate, purpose: purpose || undefined, event_id: eventId ?? undefined },
      {
        onSuccess: () => {
          setQuantity(1);
          setDueDate('');
          setPurpose('');
          setEventId(null);
          setEventTitle(null);
          onSuccess();
        },
        onError: (err) => showToast(err instanceof ApiError ? err.message : 'Gagal meminjam barang.'),
      },
    );
  };

  return (
    <BottomSheet visible={visible} title="Pinjam barang" onClose={onClose}>
      <View style={styles.sheetBody}>
        <View style={styles.stepperRow}>
          <Text style={styles.fieldLabel}>Jumlah</Text>
          <View style={styles.stepper}>
            <Pressable onPress={() => setQuantity((q) => Math.max(1, q - 1))} hitSlop={theme.hitSlop} style={styles.stepperButton}>
              <Text style={styles.stepperGlyph}>−</Text>
            </Pressable>
            <Text style={styles.stepperValue}>{quantity}</Text>
            <Pressable onPress={() => setQuantity((q) => Math.min(available, q + 1))} hitSlop={theme.hitSlop} style={styles.stepperButton}>
              <Text style={styles.stepperGlyph}>+</Text>
            </Pressable>
          </View>
        </View>
        {quantity >= available ? <Text style={styles.stepperHint}>Hanya {available} tersedia.</Text> : null}

        <DateField label="Tanggal kembali" value={dueDate} onChange={setDueDate} placeholder="Pilih tanggal kembali" minimumDate={new Date()} />
        <FormField label="Keperluan (opsional)" value={purpose} onChangeText={setPurpose} placeholder="Kerja bakti RT 03" />

        <Pressable onPress={() => setEventPickerOpen(true)} style={styles.eventPicker}>
          <Text style={styles.fieldLabel}>Kegiatan (opsional)</Text>
          <Text style={styles.eventPickerValue}>{eventTitle ?? 'Tidak ada'}</Text>
        </Pressable>

        <View style={styles.sheetAction}>
          <Button variant="primary" label="Pinjam" onPress={submit} loading={borrow.isPending} disabled={!dueDate} block />
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

function ReturnSheet({
  itemId,
  loanId,
  maxQuantity,
  currentCondition,
  onClose,
  onSuccess,
}: {
  itemId: string;
  loanId: string;
  maxQuantity: number;
  currentCondition: string;
  onClose: () => void;
  onSuccess: () => void;
}) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const { showToast } = useToast();
  const [quantity, setQuantity] = useState(maxQuantity);
  const [condition, setCondition] = useState<(typeof CONDITIONS)[number]>('Baik');
  const [note, setNote] = useState('');

  const returnLoan = useReturnInventoryLoan(itemId);

  const submit = () => {
    returnLoan.mutate(
      { loanId, body: { quantity, condition: CONDITION_VALUES[condition], note: note || undefined } },
      {
        onSuccess,
        onError: (err) => showToast(err instanceof ApiError ? err.message : 'Gagal mengembalikan barang.'),
      },
    );
  };

  return (
    <BottomSheet visible title="Kembalikan barang" onClose={onClose}>
      <View style={styles.sheetBody}>
        <View style={styles.stepperRow}>
          <Text style={styles.fieldLabel}>Jumlah</Text>
          <View style={styles.stepper}>
            <Pressable onPress={() => setQuantity((q) => Math.max(1, q - 1))} hitSlop={theme.hitSlop} style={styles.stepperButton}>
              <Text style={styles.stepperGlyph}>−</Text>
            </Pressable>
            <Text style={styles.stepperValue}>{quantity}</Text>
            <Pressable onPress={() => setQuantity((q) => Math.min(maxQuantity, q + 1))} hitSlop={theme.hitSlop} style={styles.stepperButton}>
              <Text style={styles.stepperGlyph}>+</Text>
            </Pressable>
          </View>
        </View>

        <View style={styles.methodField}>
          <Text style={styles.fieldLabel}>Kondisi</Text>
          <Segmented options={CONDITIONS} value={condition} onChange={setCondition} />
        </View>

        <FormField
          label={condition === 'Baik' ? 'Catatan (opsional)' : 'Catatan (wajib — kondisi menurun)'}
          value={note}
          onChangeText={setNote}
          placeholder="Kaki tenda patah saat dipakai"
        />

        <View style={styles.sheetAction}>
          <Button variant="primary" label="Kembalikan" onPress={submit} loading={returnLoan.isPending} block />
        </View>
      </View>
    </BottomSheet>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { flex: 1, backgroundColor: theme.color.bg },
    scroll: { paddingBottom: theme.space.xxxl },
    headerBlock: { paddingHorizontal: theme.layout.screenPadding, marginTop: theme.space.md, gap: theme.space.xs },
    name: { fontFamily: 'Archivo_800ExtraBold', fontSize: 26, color: theme.color.text },
    meta: { fontFamily: 'Archivo_400Regular', fontSize: 14, color: theme.color.textMuted },
    tagRow: { flexDirection: 'row', marginTop: theme.space.xs },
    quantityBlock: {
      marginHorizontal: theme.layout.screenPadding,
      marginTop: theme.space.md,
      paddingVertical: theme.space.sm,
      borderTopWidth: 2,
      borderBottomWidth: 2,
      borderColor: theme.color.rule,
    },
    quantityText: { fontFamily: 'Archivo_800ExtraBold', fontSize: 15, color: theme.color.text },
    section: { paddingHorizontal: theme.layout.screenPadding, gap: 2 },
    conditionText: { fontFamily: 'Archivo_600SemiBold', fontSize: 15, color: theme.color.text },
    conditionMeta: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted },
    manageRow: { paddingHorizontal: theme.layout.screenPadding, marginTop: theme.space.lg, gap: theme.space.sm, flexDirection: 'row' },
    actionBar: { paddingHorizontal: theme.layout.screenPadding, paddingVertical: theme.space.md, borderTopWidth: 2, borderTopColor: theme.color.text },
    sheetBody: { paddingHorizontal: theme.layout.screenPadding, gap: theme.space.md, paddingBottom: theme.space.md },
    stepperRow: { gap: theme.space.xs },
    fieldLabel: { fontFamily: 'Archivo_800ExtraBold', fontSize: 11, letterSpacing: 1.0, textTransform: 'uppercase', color: theme.color.textMuted },
    stepper: { flexDirection: 'row', alignItems: 'center', gap: theme.space.lg },
    stepperButton: {
      width: 40,
      height: 40,
      borderWidth: 1,
      borderColor: theme.color.divider,
      alignItems: 'center',
      justifyContent: 'center',
    },
    stepperGlyph: { fontFamily: 'Archivo_800ExtraBold', fontSize: 18, color: theme.color.text },
    stepperValue: { fontFamily: 'Archivo_800ExtraBold', fontSize: 20, color: theme.color.text, minWidth: 32, textAlign: 'center' },
    stepperHint: { fontFamily: 'Archivo_400Regular', fontSize: 12, color: theme.color.accent700 },
    methodField: { gap: theme.space.xs },
    eventPicker: { gap: theme.space.xs },
    eventPickerValue: { fontFamily: 'Archivo_600SemiBold', fontSize: 15, color: theme.color.text },
    sheetAction: { marginTop: theme.space.sm },
    pickerScroll: { maxHeight: 400 },
  });
}
