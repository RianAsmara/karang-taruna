import { router } from 'expo-router';
import { useMemo, useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

import { KeyboardAwareScroll } from '@/components/KeyboardAwareScroll';
import { Avatar } from '@/components/Avatar';
import { Button } from '@/components/Button';
import { Dialog } from '@/components/Dialog';
import { DateField } from '@/components/DateField';
import { FormField, FormSection } from '@/components/FormSection';
import { MemberItem } from '@/components/MemberItem';
import { PermissionNote } from '@/components/PermissionNote';
import { ScreenHeader } from '@/components/ScreenHeader';
import { Segmented } from '@/components/Segmented';
import { Skeleton } from '@/components/Skeleton';
import { StepIndicator } from '@/components/StepIndicator';
import { BottomSheet } from '@/components/BottomSheet';
import { useToast } from '@/components/Toast';
import { ApiError } from '@/lib/api';
import {
  type ApiMember,
  useCreateEvent,
  useCurrentOrganization,
  useMembers,
  useTransparency,
  useUpdateEvent,
} from '@/lib/queries';
import { formatRupiah, initialsOf } from '@/theme/format';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

const STEPS = ['Kegiatan', 'Waktu & tempat', 'Panitia & anggaran'];
const CATEGORIES = ['Kerja bakti', 'Olahraga', 'Sosial', 'Lain-lain'] as const;
const CATEGORY_VALUES: Record<(typeof CATEGORIES)[number], string> = {
  'Kerja bakti': 'KERJA_BAKTI',
  Olahraga: 'OLAHRAGA',
  Sosial: 'SOSIAL',
  'Lain-lain': 'LAINNYA',
};

type CommitteePick = { membershipId: string; name: string; roleTitle: string };

export function BuatKegiatanScreen() {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const { showToast } = useToast();

  const organization = useCurrentOrganization();
  const members = useMembers();
  const transparency = useTransparency();

  const [step, setStep] = useState(0);
  const [eventId, setEventId] = useState<string | null>(null);

  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [category, setCategory] = useState<(typeof CATEGORIES)[number]>('Kerja bakti');

  const [dateText, setDateText] = useState('');
  const [startTimeText, setStartTimeText] = useState('');
  const [endTimeText, setEndTimeText] = useState('');
  const [location, setLocation] = useState('');

  const [committees, setCommittees] = useState<CommitteePick[]>([]);
  const [pickerOpen, setPickerOpen] = useState(false);
  const [budgetAmount, setBudgetAmount] = useState('');

  const [pastDateConfirm, setPastDateConfirm] = useState<{ startAt: string } | null>(null);

  const createEvent = useCreateEvent();
  const updateEvent = useUpdateEvent();

  const myRole = organization.data?.membership.role;
  const myMembershipId = organization.data?.membership.id;
  const canCreate = myRole !== 'ANGGOTA';

  const startAt = useMemo(() => combineDateTime(dateText, startTimeText), [dateText, startTimeText]);
  const endAt = useMemo(() => (endTimeText ? combineDateTime(dateText, endTimeText) : null), [dateText, endTimeText]);

  const pickableMembers = (members.data?.data ?? []).filter(
    (m) => !m.leftAt && !committees.some((c) => c.membershipId === m.id),
  );

  // `canCreate` reads `organization.data`, still `undefined` on a cold
  // load — `undefined !== 'ANGGOTA'` is true, so without this guard an
  // ANGGOTA briefly sees the create form before the query settles and
  // flips it closed (same race as SusunLaporanScreen, fail-open here).
  if (organization.isPending) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Buat kegiatan" onBack={() => router.back()} />
        <View style={{ padding: theme.layout.screenPadding }}>
          <Skeleton width="100%" height={160} />
        </View>
      </View>
    );
  }

  if (!canCreate) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Buat kegiatan" onBack={() => router.back()} />
        <PermissionNote body="Hanya pengurus yang bisa membuat kegiatan." roleHint="pengurus" />
      </View>
    );
  }

  const buildPayload = () => {
    // The committees list a PATCH sends replaces the roster wholesale —
    // always keep the organizer on it, or an edit that touches step 3
    // without re-picking them would silently drop them from their own event.
    const picked = committees.map((c) => ({ membership_id: c.membershipId, role_title: c.roleTitle || undefined }));
    const withSelf =
      myMembershipId && !picked.some((c) => c.membership_id === myMembershipId)
        ? [...picked, { membership_id: myMembershipId, role_title: undefined }]
        : picked;

    return {
      title: title.trim(),
      description: description.trim() || undefined,
      category: CATEGORY_VALUES[category],
      location: location.trim() || undefined,
      start_at: startAt!,
      end_at: endAt ?? undefined,
      committees: withSelf,
      budget_amount: budgetAmount ? Number(budgetAmount) : undefined,
    };
  };

  const saveDraft = () => {
    if (!title.trim() || !startAt) {
      showToast('Judul dan tanggal wajib diisi untuk menyimpan draf.');
      return;
    }

    const onSuccess = () => {
      showToast('Draf disimpan.');
      router.replace('/kegiatan');
    };
    const onError = (err: unknown) => showToast(err instanceof ApiError ? err.message : 'Gagal menyimpan draf.');

    if (eventId) {
      updateEvent.mutate(
        { id: eventId, body: { ...buildPayload(), status: 'DRAFT', lifecycle_stage: 'IDEA' } },
        { onSuccess, onError },
      );
    } else {
      createEvent.mutate(buildPayload(), {
        onSuccess: (res) => {
          setEventId(res.data.id);
          onSuccess();
        },
        onError,
      });
    }
  };

  const publish = () => {
    if (!startAt) return;

    if (new Date(startAt) < new Date()) {
      setPastDateConfirm({ startAt });
      return;
    }

    doPublish();
  };

  const doPublish = () => {
    const onSuccess = (id: string) => {
      showToast('Kegiatan diterbitkan.');
      router.replace(`/kegiatan/event/${id}`);
    };
    const onError = (err: unknown) => showToast(err instanceof ApiError ? err.message : 'Gagal menerbitkan kegiatan.');

    if (eventId) {
      updateEvent.mutate(
        { id: eventId, body: { ...buildPayload(), status: 'PLANNED', lifecycle_stage: 'PREPARATION' } },
        { onSuccess: () => onSuccess(eventId), onError },
      );
    } else {
      createEvent.mutate(buildPayload(), {
        onSuccess: (res) => {
          setEventId(res.data.id);
          updateEvent.mutate(
            { id: res.data.id, body: { ...buildPayload(), status: 'PLANNED', lifecycle_stage: 'PREPARATION' } },
            { onSuccess: () => onSuccess(res.data.id), onError },
          );
        },
        onError,
      });
    }
  };

  const saving = createEvent.isPending || updateEvent.isPending;

  return (
    <View style={styles.root}>
      <ScreenHeader title="Buat kegiatan" onBack={() => (step === 0 ? router.back() : setStep((s) => s - 1))} />
      <StepIndicator steps={STEPS} current={step} />

      <KeyboardAwareScroll contentContainerStyle={styles.scroll}>
        {step === 0 ? (
          <FormSection title="Kegiatan">
            <FormField label="Judul" value={title} onChangeText={setTitle} placeholder="Nama kegiatan" autoFocus />
            <FormField label="Deskripsi (opsional)" value={description} onChangeText={setDescription} placeholder="Ceritakan kegiatan ini" multiline numberOfLines={3} />
            <View style={styles.segmentField}>
              <Text style={styles.fieldLabel}>Kategori</Text>
              <Segmented options={CATEGORIES} value={category} onChange={setCategory} />
            </View>
          </FormSection>
        ) : null}

        {step === 1 ? (
          <FormSection title="Waktu & tempat">
            <DateField label="Tanggal" value={dateText} onChange={setDateText} placeholder="Pilih tanggal" />
            <DateField label="Jam mulai" value={startTimeText} onChange={setStartTimeText} mode="time" placeholder="Pilih jam mulai" />
            <DateField label="Jam selesai (opsional)" value={endTimeText} onChange={setEndTimeText} mode="time" placeholder="Pilih jam selesai" />
            <FormField label="Lokasi (opsional)" value={location} onChangeText={setLocation} placeholder="Balai warga" />
          </FormSection>
        ) : null}

        {step === 2 ? (
          <>
            <FormSection title="Panitia" hint="Ketuk untuk menambah anggota panitia.">
              <View style={styles.committeeList}>
                {committees.map((c) => (
                  <View key={c.membershipId} style={styles.committeeRow}>
                    <Avatar size={32} initials={initialsOf(c.name)} />
                    <Text style={styles.committeeName} numberOfLines={1}>
                      {c.name}
                    </Text>
                    <Pressable
                      onPress={() => setCommittees((cs) => cs.filter((x) => x.membershipId !== c.membershipId))}
                      hitSlop={theme.hitSlop}
                      accessibilityRole="button"
                      accessibilityLabel={`Hapus ${c.name} dari panitia`}
                    >
                      <Text style={styles.committeeRemove}>Hapus</Text>
                    </Pressable>
                  </View>
                ))}
                <Pressable onPress={() => setPickerOpen(true)} style={styles.addCommittee} accessibilityRole="button">
                  <Text style={styles.addCommitteeLabel}>+ Tambah panitia</Text>
                </Pressable>
              </View>
            </FormSection>
            <FormSection title="Anggaran" hint={`Saldo kas saat ini ${formatRupiah(transparency.data?.balance ?? 0)}`}>
              <FormField label="Anggaran (opsional)" value={budgetAmount} onChangeText={setBudgetAmount} placeholder="0" keyboardType="numeric" />
            </FormSection>
          </>
        ) : null}
      </KeyboardAwareScroll>

      <View style={styles.actionBar}>
        {step < 2 ? (
          <>
            <Button variant="primary" label="Lanjut" onPress={() => setStep((s) => s + 1)} disabled={!title.trim()} block />
            {!title.trim() ? <Text style={styles.disabledReason}>Isi judul untuk melanjutkan.</Text> : null}
            <View style={styles.ghostAction}>
              <Button variant="ghost" label="Simpan draf" onPress={saveDraft} loading={saving} />
            </View>
          </>
        ) : (
          <>
            <Button variant="primary" label="Terbitkan kegiatan" onPress={publish} disabled={!startAt} loading={saving} block />
            {!startAt ? <Text style={styles.disabledReason}>Isi tanggal untuk menerbitkan.</Text> : null}
            <View style={styles.ghostAction}>
              <Button variant="ghost" label="Simpan sebagai draf" onPress={saveDraft} loading={saving} />
            </View>
          </>
        )}
      </View>

      <BottomSheet visible={pickerOpen} title="Pilih panitia" onClose={() => setPickerOpen(false)}>
        <ScrollView style={styles.pickerScroll}>
          {pickableMembers.map((m: ApiMember, i) => (
            <MemberItem
              key={m.id}
              initials={initialsOf(m.name)}
              name={m.name}
              roles={[m.roleLabel]}
              onPress={() => {
                setCommittees((cs) => [...cs, { membershipId: m.id, name: m.name, roleTitle: '' }]);
                setPickerOpen(false);
              }}
              isLast={i === pickableMembers.length - 1}
            />
          ))}
        </ScrollView>
      </BottomSheet>

      <Dialog
        visible={pastDateConfirm !== null}
        title="Tanggal sudah lewat"
        body="Kegiatan akan langsung ditandai selesai. Tetap buat?"
        confirmLabel="Tetap buat"
        onConfirm={() => {
          setPastDateConfirm(null);
          doPublish();
        }}
        onCancel={() => setPastDateConfirm(null)}
      />
    </View>
  );
}

function combineDateTime(dateText: string, timeText: string): string | null {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(dateText)) return null;
  const time = /^\d{2}:\d{2}$/.test(timeText) ? timeText : '00:00';
  const parsed = new Date(`${dateText}T${time}:00`);
  return Number.isNaN(parsed.getTime()) ? null : parsed.toISOString();
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { flex: 1, backgroundColor: theme.color.bg },
    scroll: { paddingBottom: theme.space.xxxl },
    segmentField: { paddingHorizontal: theme.layout.screenPadding, gap: theme.space.xs, marginTop: theme.space.md },
    fieldLabel: {
      fontFamily: 'Archivo_800ExtraBold',
      fontSize: 11,
      letterSpacing: 1.0,
      textTransform: 'uppercase',
      color: theme.color.textMuted,
    },
    committeeList: { paddingHorizontal: theme.layout.screenPadding, gap: theme.space.sm },
    committeeRow: { flexDirection: 'row', alignItems: 'center', gap: theme.space.sm },
    committeeName: { flex: 1, fontFamily: 'Archivo_600SemiBold', fontSize: 14, color: theme.color.text },
    committeeRemove: { fontFamily: 'Archivo_800ExtraBold', fontSize: 12, color: theme.color.accent },
    addCommittee: { paddingVertical: theme.space.sm },
    addCommitteeLabel: { fontFamily: 'Archivo_800ExtraBold', fontSize: 13, color: theme.color.accent },
    actionBar: { paddingHorizontal: theme.layout.screenPadding, paddingVertical: theme.space.md, borderTopWidth: 2, borderTopColor: theme.color.text },
    disabledReason: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textFaint, textAlign: 'center', marginTop: theme.space.xs },
    ghostAction: { alignItems: 'center', marginTop: theme.space.sm },
    pickerScroll: { maxHeight: 400, paddingHorizontal: theme.layout.screenPadding },
  });
}
