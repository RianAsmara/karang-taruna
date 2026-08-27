import { router } from 'expo-router';
import { useMemo, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';

import { BottomSheet } from '@/components/BottomSheet';
import { Dialog } from '@/components/Dialog';
import { ErrorState } from '@/components/ErrorState';
import { ListItem } from '@/components/ListItem';
import { MemberItem } from '@/components/MemberItem';
import { OrgChart } from '@/components/OrgChart';
import { PermissionNote } from '@/components/PermissionNote';
import { RoleRow } from '@/components/RoleRow';
import { ScreenHeader } from '@/components/ScreenHeader';
import { SectionHeader } from '@/components/SectionHeader';
import { Skeleton } from '@/components/Skeleton';
import { useToast } from '@/components/Toast';
import { ApiError } from '@/lib/api';
import { type ApiMember, useCurrentOrganization, useEvents, useMembers, useTransferChairFor, useUpdateAnyMemberRole } from '@/lib/queries';
import { initialsOf } from '@/theme/format';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

const ROLES = [
  { label: 'Ketua', value: 'KETUA', description: 'Menyetujui dan menerbitkan laporan kas, mengatur anggota dan peran.' },
  { label: 'Bendahara', value: 'BENDAHARA', description: 'Mencatat uang masuk dan keluar, mengelola iuran, menyusun laporan.' },
  { label: 'Sekretaris', value: 'SEKRETARIS', description: 'Mengelola dokumen, notulen, dan pengumuman.' },
  { label: 'Anggota', value: 'ANGGOTA', description: 'Melihat seluruh kas, laporan, dan kegiatan. Ikut voting. Mengerjakan tugas yang diberikan.' },
] as const;

export function PeranScreen() {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const { showToast } = useToast();

  const [assignRole, setAssignRole] = useState<(typeof ROLES)[number] | null>(null);
  const [transferTarget, setTransferTarget] = useState<ApiMember | null>(null);

  const organization = useCurrentOrganization();
  const members = useMembers();
  const events = useEvents();
  const updateRole = useUpdateAnyMemberRole();
  const transferChair = useTransferChairFor();

  const isChairViewer = organization.data?.membership.role === 'KETUA';

  const holdersByRole = useMemo(() => {
    const map = new Map<string, ApiMember[]>();
    for (const m of members.data?.data ?? []) {
      if (m.leftAt) continue;
      const list = map.get(m.role) ?? [];
      list.push(m);
      map.set(m.role, list);
    }
    return map;
  }, [members.data]);

  const activeEvents = (events.data?.data ?? []).filter((e) => e.status === 'PLANNED' || e.status === 'ONGOING');

  const asChartPerson = (m: ApiMember) => ({ initials: initialsOf(m.name), name: m.name });
  const ketuaHolder = holdersByRole.get('KETUA')?.[0];
  const bendaharaHolders = (holdersByRole.get('BENDAHARA') ?? []).map(asChartPerson);
  const sekretarisHolders = (holdersByRole.get('SEKRETARIS') ?? []).map(asChartPerson);
  const anggotaHolders = holdersByRole.get('ANGGOTA') ?? [];

  // `isChairViewer` reads `organization.data`, still `undefined` on a
  // cold load — wait for it too, or the real chair briefly loses the
  // edit controls (same race as SusunLaporanScreen).
  if (members.isPending || organization.isPending) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Peran" onBack={() => router.back()} />
        <View style={{ padding: theme.layout.screenPadding }}>
          <Skeleton width="100%" height={220} />
        </View>
      </View>
    );
  }

  if (members.isError) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Peran" onBack={() => router.back()} />
        <ErrorState title="Gagal memuat peran" body="Periksa koneksi internet Anda, lalu coba lagi." onRetry={() => members.refetch()} />
      </View>
    );
  }

  const assignToRole = (member: ApiMember) => {
    if (!assignRole) return;

    if (assignRole.value === 'KETUA') {
      setTransferTarget(member);
      return;
    }

    updateRole.mutate(
      { memberId: member.id, role: assignRole.value },
      {
        onSuccess: () => {
          showToast(`${member.name} sekarang ${assignRole.label}.`);
          setAssignRole(null);
        },
        onError: (err) => showToast(err instanceof ApiError ? err.message : 'Gagal memperbarui peran.'),
      },
    );
  };

  const confirmTransfer = () => {
    if (!transferTarget) return;

    transferChair.mutate(transferTarget.id, {
      onSuccess: () => {
        showToast(`Peran ketua dipindahkan ke ${transferTarget.name}.`);
        setTransferTarget(null);
        setAssignRole(null);
      },
      onError: (err) => {
        showToast(err instanceof ApiError ? err.message : 'Gagal memindahkan peran ketua.');
        setTransferTarget(null);
      },
    });
  };

  return (
    <View style={styles.root}>
      <ScreenHeader title="Peran" onBack={() => router.back()} />
      <ScrollView contentContainerStyle={styles.scroll}>
        <Text style={styles.intro}>
          Peran menentukan siapa yang bisa mengubah data. Semua anggota tetap bisa melihat seluruh laporan kas.
        </Text>

        {!isChairViewer ? (
          <PermissionNote body="Hanya ketua yang bisa mengubah peran." roleHint="ketua" />
        ) : null}

        <SectionHeader title="Struktur organisasi" />
        <OrgChart
          ketua={ketuaHolder ? asChartPerson(ketuaHolder) : null}
          bendahara={bendaharaHolders}
          sekretaris={sekretarisHolders}
          anggotaCount={anggotaHolders.length}
          anggotaAvatars={anggotaHolders.map((m) => ({ initials: initialsOf(m.name) }))}
          onPressAnggota={() => router.push({ pathname: '/anggota', params: { filter: 'Anggota' } })}
        />

        <View style={styles.rule} />

        <View>
          {ROLES.map((role, i) => (
            <RoleRow
              key={role.value}
              role={role.label}
              description={role.description}
              holders={(holdersByRole.get(role.value) ?? []).map((m) => ({ initials: initialsOf(m.name) }))}
              editable={isChairViewer}
              onPress={() => setAssignRole(role)}
              isLast={i === ROLES.length - 1}
            />
          ))}
        </View>

        <View style={styles.rule} />

        <SectionHeader title="Panitia kegiatan" />
        <Text style={styles.panitiaIntro}>
          Panitia adalah tugas sementara untuk satu kegiatan, bukan peran organisasi tetap.
        </Text>
        {activeEvents.length === 0 ? (
          <View style={styles.emptyInline}>
            <Text style={styles.emptyText}>Belum ada kegiatan yang berjalan.</Text>
          </View>
        ) : (
          <View>
            {activeEvents.map((e, i) => (
              <ListItem
                key={e.id}
                title={e.title}
                subtitle={e.statusLabel}
                onPress={() => router.push(`/kegiatan/event/${e.id}`)}
                isLast={i === activeEvents.length - 1}
              />
            ))}
          </View>
        )}
      </ScrollView>

      <BottomSheet visible={assignRole !== null} title={`Tetapkan ${assignRole?.label ?? ''}`} onClose={() => setAssignRole(null)}>
        <ScrollView style={styles.pickerScroll}>
          {(members.data?.data ?? [])
            .filter((m) => !m.leftAt)
            .map((m, i, arr) => (
              <MemberItem
                key={m.id}
                initials={initialsOf(m.name)}
                name={m.name}
                roles={[m.roleLabel]}
                onPress={() => assignToRole(m)}
                isLast={i === arr.length - 1}
              />
            ))}
        </ScrollView>
      </BottomSheet>

      <Dialog
        visible={transferTarget !== null}
        title="Pindahkan peran ketua?"
        body={`Peran ketua akan dipindahkan ke ${transferTarget?.name ?? ''}. Peran ketua Anda akan berubah menjadi Anggota.`}
        confirmLabel="Pindahkan"
        onCancel={() => setTransferTarget(null)}
        onConfirm={confirmTransfer}
      />
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { flex: 1, backgroundColor: theme.color.bg },
    scroll: { paddingBottom: theme.space.xxxl },
    intro: {
      fontFamily: 'Archivo_400Regular',
      fontSize: 13,
      color: theme.color.textMuted,
      paddingHorizontal: theme.layout.screenPadding,
      paddingTop: theme.space.lg,
      lineHeight: 19,
    },
    rule: { height: 2, backgroundColor: theme.color.rule, marginTop: theme.space.lg },
    panitiaIntro: {
      fontFamily: 'Archivo_400Regular',
      fontSize: 13,
      color: theme.color.textMuted,
      paddingHorizontal: theme.layout.screenPadding,
      marginBottom: theme.space.sm,
      lineHeight: 19,
    },
    emptyInline: { paddingHorizontal: theme.layout.screenPadding },
    emptyText: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textFaint },
    pickerScroll: { maxHeight: 420 },
  });
}
