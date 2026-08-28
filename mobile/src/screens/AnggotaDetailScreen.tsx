import { router, useLocalSearchParams } from 'expo-router';
import { useMemo, useState } from 'react';
import { Linking, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

import { Avatar } from '@/components/Avatar';
import { BottomSheet } from '@/components/BottomSheet';
import { Button } from '@/components/Button';
import { Dialog } from '@/components/Dialog';
import { ErrorState } from '@/components/ErrorState';
import { InfoSheet } from '@/components/InfoSheet';
import { ListItem } from '@/components/ListItem';
import { ScreenHeader } from '@/components/ScreenHeader';
import { SectionHeader } from '@/components/SectionHeader';
import { Skeleton } from '@/components/Skeleton';
import { Tag } from '@/components/Tag';
import { useToast } from '@/components/Toast';
import { ApiError } from '@/lib/api';
import {
  useCurrentOrganization,
  useDues,
  useMember,
  useMemberActivity,
  useMemberResponsibilities,
  useRemoveMember,
  useUpdateMemberRole,
} from '@/lib/queries';
import { formatDateShort, formatMonthYear, initialsOf } from '@/theme/format';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';
import { DUES_TAG, resolveDuesStatus, ROLE_TONE } from '@/theme/vocab';

const ASSIGNABLE_ROLES = ['Bendahara', 'Sekretaris', 'Anggota'] as const;
const ROLE_VALUE: Record<(typeof ASSIGNABLE_ROLES)[number], string> = {
  Bendahara: 'BENDAHARA',
  Sekretaris: 'SEKRETARIS',
  Anggota: 'ANGGOTA',
};

export function AnggotaDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const { showToast } = useToast();

  const [roleSheetOpen, setRoleSheetOpen] = useState(false);
  const [chairNoteOpen, setChairNoteOpen] = useState(false);
  const [removeOpen, setRemoveOpen] = useState(false);
  const [whatsappStubOpen, setWhatsappStubOpen] = useState(false);
  const [duesStubOpen, setDuesStubOpen] = useState(false);

  const organization = useCurrentOrganization();
  const member = useMember(id);
  const responsibilities = useMemberResponsibilities(id);
  const activity = useMemberActivity(id);
  const dues = useDues();
  const updateRole = useUpdateMemberRole(id);
  const removeMember = useRemoveMember(id);

  const myRole = organization.data?.membership.role;
  const isChairViewer = myRole === 'KETUA';
  const canSeeDues = myRole === 'KETUA' || myRole === 'BENDAHARA';
  const isSelf = organization.data?.membership.id === id;

  const memberDues = useMemo(() => {
    const rows = (dues.data?.data ?? []).filter((d) => d.membershipId === id && d.type === 'MONTHLY');
    return rows.sort((a, b) => b.period.localeCompare(a.period)).slice(0, 3);
  }, [dues.data, id]);

  if (member.isPending) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Anggota" onBack={() => router.back()} />
        <View style={{ padding: theme.layout.screenPadding }}>
          <Skeleton width="100%" height={140} />
        </View>
      </View>
    );
  }

  if (member.isError) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Anggota" onBack={() => router.back()} />
        <ErrorState title="Gagal memuat anggota" body="Periksa koneksi internet Anda, lalu coba lagi." onRetry={() => member.refetch()} />
      </View>
    );
  }

  const m = member.data!.data;
  const responsibilityRows = responsibilities.data?.data ?? [];
  const activityRows = activity.data?.data ?? [];

  const openRoleSheet = () => {
    if (m.role === 'KETUA') {
      setChairNoteOpen(true);
    } else {
      setRoleSheetOpen(true);
    }
  };

  const submitRole = (role: string) => {
    updateRole.mutate(role, {
      onSuccess: () => {
        showToast('Peran diperbarui.');
        setRoleSheetOpen(false);
      },
      onError: (err) => showToast(err instanceof ApiError ? err.message : 'Gagal memperbarui peran.'),
    });
  };

  const confirmRemove = () => {
    removeMember.mutate(undefined, {
      onSuccess: () => {
        setRemoveOpen(false);
        showToast(`${m.name} dikeluarkan dari organisasi.`);
        router.back();
      },
      onError: (err) => {
        setRemoveOpen(false);
        showToast(err instanceof ApiError ? err.message : 'Gagal mengeluarkan anggota.');
      },
    });
  };

  return (
    <View style={styles.root}>
      <ScreenHeader title={m.name} onBack={() => router.back()} />
      <ScrollView contentContainerStyle={styles.scroll}>
        <View style={styles.identity}>
          <Avatar size={56} initials={initialsOf(m.name)} />
          <Text style={styles.name}>{m.name}</Text>
          <Text style={styles.meta}>
            Anggota sejak {formatMonthYear(new Date(m.joinedAt))}
            {m.activityPoints > 0 ? ` · ${m.activityPoints} poin aktivitas` : ''}
          </Text>
          <View style={styles.tagRow}>
            <Tag tone={ROLE_TONE[m.roleLabel] ?? 'outline'} label={m.roleLabel.toUpperCase()} />
            {m.leftAt ? <Tag tone="outline" label="KELUAR" mutedFill /> : null}
          </View>
        </View>

        <View style={styles.rule} />

        {m.phone ? (
          <View style={styles.contactRow}>
            <Text style={styles.contactPhone}>{m.phone}</Text>
            <Button
              variant="ghost"
              label="Chat WhatsApp"
              onPress={() => {
                Linking.openURL(`https://wa.me/${m.phone!.replace(/\D/g, '')}`).catch(() => setWhatsappStubOpen(true));
              }}
            />
          </View>
        ) : null}

        <SectionHeader title="Tanggung jawab" />
        {responsibilityRows.length === 0 ? (
          <View style={styles.emptyInline}>
            <Text style={styles.emptyText}>Belum ada tanggung jawab aktif.</Text>
          </View>
        ) : (
          <View>
            {responsibilityRows.map((r, i) => (
              <ListItem
                key={r.id}
                title={r.roleTitle ? `${r.roleTitle} · ${r.event.title}` : r.event.title}
                subtitle={r.event.statusLabel}
                isLast={i === responsibilityRows.length - 1}
              />
            ))}
          </View>
        )}

        {canSeeDues && !isSelf ? (
          <>
            <SectionHeader title="Iuran" action="Lihat semua" onAction={() => setDuesStubOpen(true)} />
            {memberDues.length === 0 ? (
              <View style={styles.emptyInline}>
                <Text style={styles.emptyText}>Belum ada catatan iuran.</Text>
              </View>
            ) : (
              <View>
                {memberDues.map((due, i) => {
                  const tag = DUES_TAG[resolveDuesStatus(due)];
                  return (
                    <ListItem
                      key={due.id}
                      title={formatMonthYear(new Date(due.period))}
                      trailing={<Tag tone={tag.tone} label={tag.label} mutedFill={tag.mutedFill} />}
                      isLast={i === memberDues.length - 1}
                    />
                  );
                })}
              </View>
            )}
          </>
        ) : null}

        {isSelf && !canSeeDues ? (
          <>
            <SectionHeader title="Iuran" />
            <View style={styles.duesLinkRow}>
              <Button variant="ghost" label="Lihat iuran saya" onPress={() => setDuesStubOpen(true)} />
            </View>
          </>
        ) : null}

        <SectionHeader title="Aktivitas" />
        {activityRows.length === 0 ? (
          <View style={styles.emptyInline}>
            <Text style={styles.emptyText}>Belum ada aktivitas.</Text>
          </View>
        ) : (
          <View>
            {activityRows.map((a, i) => (
              <ListItem key={a.id} title={a.action} subtitle={formatDateShort(a.createdAt)} isLast={i === activityRows.length - 1} />
            ))}
          </View>
        )}

        {isChairViewer && !isSelf ? (
          <View style={styles.removeRow}>
            <Button variant="ghost" label="Keluarkan dari organisasi" onPress={() => setRemoveOpen(true)} />
          </View>
        ) : null}
      </ScrollView>

      {isChairViewer ? (
        <View style={styles.actionBar}>
          <Button variant="secondary" label="Ubah peran" onPress={openRoleSheet} block />
        </View>
      ) : null}

      <BottomSheet visible={roleSheetOpen} title="Ubah peran" onClose={() => setRoleSheetOpen(false)}>
        <View style={styles.sheetBody}>
          <Text style={styles.sheetHint}>Peran saat ini: {m.roleLabel}</Text>
          {ASSIGNABLE_ROLES.map((label) => (
            <Pressable
              key={label}
              onPress={() => submitRole(ROLE_VALUE[label])}
              hitSlop={theme.hitSlop}
              style={({ pressed }) => [styles.roleOption, pressed && styles.roleOptionPressed]}
            >
              <Text style={styles.roleOptionLabel}>{label}</Text>
              {m.roleLabel === label ? <Text style={styles.roleOptionCurrent}>Saat ini</Text> : null}
            </Pressable>
          ))}
        </View>
      </BottomSheet>

      <InfoSheet
        visible={chairNoteOpen}
        title="Ubah peran"
        body="Peran ketua hanya bisa dipindahkan, bukan dihapus. Buka Peran & Izin untuk memindahkan peran ketua ke anggota lain."
        onClose={() => setChairNoteOpen(false)}
      />

      <Dialog
        visible={removeOpen}
        title={`Keluarkan ${m.name}?`}
        body={`${m.name} tidak lagi bisa melihat kas, kegiatan, dan tugas organisasi ini. Riwayat iuran dan transaksi tetap tersimpan.`}
        confirmLabel="Keluarkan"
        onCancel={() => setRemoveOpen(false)}
        onConfirm={confirmRemove}
      />

      <InfoSheet
        visible={whatsappStubOpen}
        title="Chat WhatsApp"
        body="Tidak bisa membuka WhatsApp di perangkat ini."
        onClose={() => setWhatsappStubOpen(false)}
      />
      <InfoSheet
        visible={duesStubOpen}
        title="Iuran"
        body="Halaman iuran lengkap sedang disiapkan."
        onClose={() => setDuesStubOpen(false)}
      />
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { flex: 1, backgroundColor: theme.color.bg },
    scroll: { paddingBottom: theme.space.xxxl },
    identity: { alignItems: 'flex-start', padding: theme.layout.screenPadding, gap: 6 },
    name: { fontFamily: 'Archivo_800ExtraBold', fontSize: 22, color: theme.color.text, marginTop: theme.space.sm },
    meta: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted },
    tagRow: { flexDirection: 'row', gap: theme.space.xs, marginTop: 4, flexWrap: 'wrap' },
    rule: { height: 2, backgroundColor: theme.color.rule },
    contactRow: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      paddingHorizontal: theme.layout.screenPadding,
      paddingVertical: theme.space.md,
      borderBottomWidth: 1,
      borderBottomColor: theme.color.divider,
    },
    contactPhone: { fontFamily: 'Archivo_800ExtraBold', fontSize: 14, color: theme.color.text },
    emptyInline: { paddingHorizontal: theme.layout.screenPadding, paddingBottom: theme.space.md },
    emptyText: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textFaint },
    duesLinkRow: { paddingHorizontal: theme.layout.screenPadding, alignItems: 'flex-start' },
    removeRow: { paddingHorizontal: theme.layout.screenPadding, alignItems: 'flex-start', marginTop: theme.space.lg },
    actionBar: { borderTopWidth: 2, borderTopColor: theme.color.rule, padding: theme.space.md, paddingHorizontal: theme.layout.screenPadding },
    sheetBody: { paddingHorizontal: theme.layout.screenPadding, gap: theme.space.xs },
    sheetHint: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted, marginBottom: theme.space.sm },
    roleOption: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      height: theme.layout.buttonHeight,
      paddingHorizontal: theme.space.md,
      borderWidth: 1,
      borderColor: theme.color.divider,
    },
    roleOptionPressed: { backgroundColor: theme.color.surfaceAlt },
    roleOptionLabel: { fontFamily: 'Archivo_800ExtraBold', fontSize: 15, color: theme.color.text },
    roleOptionCurrent: { fontFamily: 'Archivo_600SemiBold', fontSize: 11, color: theme.color.textMuted, textTransform: 'uppercase' },
  });
}
