import { router } from 'expo-router';
import { useMemo, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';

import { Avatar } from '@/components/Avatar';
import { Button } from '@/components/Button';
import { Dialog } from '@/components/Dialog';
import { ErrorState } from '@/components/ErrorState';
import { ListItem } from '@/components/ListItem';
import { OrgSwitcherSheet } from '@/components/OrgSwitcherSheet';
import { ScreenHeader } from '@/components/ScreenHeader';
import { SectionHeader } from '@/components/SectionHeader';
import { Skeleton } from '@/components/Skeleton';
import { Tag } from '@/components/Tag';
import { useCurrentOrganization, useDues, useMembers, useMyTasks } from '@/lib/queries';
import { useAuth } from '@/store/useAuth';
import { formatMonthYear, formatRupiah, initialsOf } from '@/theme/format';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

function currentMonthPeriod(): string {
  const now = new Date();
  return new Date(Date.UTC(now.getFullYear(), now.getMonth(), 1)).toISOString().slice(0, 10);
}

export function ProfilScreen() {
  const { theme, scheme, toggleScheme } = useTheme();
  const styles = makeStyles(theme);
  const [leaveOpen, setLeaveOpen] = useState(false);
  const [signOutOpen, setSignOutOpen] = useState(false);
  const [orgSwitcherOpen, setOrgSwitcherOpen] = useState(false);

  const user = useAuth((state) => state.user);
  const logout = useAuth((state) => state.logout);
  const organization = useCurrentOrganization();
  const members = useMembers();
  const dues = useDues();
  const tasks = useMyTasks();

  const myMembership = members.data?.data.find((m) => m.email === user?.email);

  const myDuesPaidTotal = useMemo(
    () => (dues.data?.data ?? []).reduce((sum, d) => sum + d.amountPaid, 0),
    [dues.data],
  );
  const activeTaskCount = tasks.data?.data.filter((t) => t.status !== 'DONE').length ?? 0;

  const myCurrentDue = (dues.data?.data ?? []).find((d) => d.type === 'MONTHLY' && d.period === currentMonthPeriod());

  if (organization.isPending) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Profil & organisasi" onBack={() => router.back()} />
        <View style={{ padding: theme.layout.screenPadding }}>
          <Skeleton width="100%" height={140} />
        </View>
      </View>
    );
  }

  if (organization.isError) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Profil & organisasi" onBack={() => router.back()} />
        <ErrorState title="Gagal memuat profil" body="Periksa koneksi internet Anda, lalu coba lagi." onRetry={() => organization.refetch()} />
        {/* Signing out only clears local state — it must never depend on
            the same API call that just failed, or a stale/invalid session
            becomes impossible to recover from without reinstalling. */}
        <View style={styles.errorSignOut}>
          <Button variant="ghost" label="Keluar akun" onPress={() => setSignOutOpen(true)} />
        </View>
        <Dialog
          visible={signOutOpen}
          title="Keluar akun?"
          body="Anda perlu masuk kembali dengan email dan kata sandi untuk mengakses RukunMuda."
          confirmLabel="Keluar"
          onCancel={() => setSignOutOpen(false)}
          onConfirm={() => {
            setSignOutOpen(false);
            logout().then(() => router.replace('/'));
          }}
        />
      </View>
    );
  }

  const org = organization.data!.data;
  const participatedEventsCount = organization.data!.membership.participatedEventsCount;

  return (
    <View style={styles.root}>
      <ScreenHeader title="Profil & organisasi" onBack={() => router.back()} />
      <ScrollView contentContainerStyle={styles.scroll}>
        <View style={styles.identity}>
          <Avatar size={56} initials={user ? initialsOf(user.name) : ''} />
          <Text style={styles.name}>{user?.name}</Text>
          {myMembership && (
            <Text style={styles.meta}>
              {myMembership.roleLabel} · anggota sejak {formatMonthYear(new Date(myMembership.joinedAt))}
            </Text>
          )}
          <View style={styles.tagRow}>
            {myMembership && <Tag tone="outline" label={myMembership.roleLabel.toUpperCase()} />}
            {myCurrentDue && (
              <Tag tone={myCurrentDue.isPaid ? 'solid' : 'outline'} label={myCurrentDue.isPaid ? '✓ IURAN LUNAS' : 'IURAN BELUM LUNAS'} />
            )}
          </View>
        </View>

        <View style={styles.statsRow}>
          <View style={styles.statsCell}>
            <Text style={styles.statsValue}>{formatRupiah(myDuesPaidTotal)}</Text>
            <Text style={styles.statsCaption}>iuran saya</Text>
          </View>
          <View style={[styles.statsCell, styles.statsCellBorder]}>
            <Text style={styles.statsValue}>{activeTaskCount}</Text>
            <Text style={styles.statsCaption}>tugas aktif</Text>
          </View>
          <View style={[styles.statsCell, styles.statsCellBorder]}>
            <Text style={styles.statsValue}>{participatedEventsCount}</Text>
            <Text style={styles.statsCaption}>kegiatan diikuti</Text>
          </View>
        </View>

        <View style={styles.orgCard}>
          <Avatar size={44} initials={initialsOf(org.name)} tone="org" />
          <View style={styles.orgTextCol}>
            <Text style={styles.orgName}>{org.name}</Text>
            <Text style={styles.orgMeta}>{members.data ? `${members.data.data.length} anggota` : ''}</Text>
          </View>
        </View>

        <SectionHeader title="Organisasi saya" />
        <View>
          <ListItem title="Ganti organisasi" onPress={() => setOrgSwitcherOpen(true)} />
          <ListItem
            title="Anggota & peran"
            trailing={<Text style={styles.trailingCount}>{members.data?.data.length ?? '–'}</Text>}
            onPress={() => router.push('/anggota')}
          />
          <ListItem title="Inventaris" onPress={() => router.push('/inventaris')} />
          <ListItem title="Dokumen" onPress={() => router.push('/dokumen')} />
          <ListItem title="Voting" onPress={() => router.push('/voting')} isLast />
        </View>

        <SectionHeader title="Akun" />
        <View>
          <ListItem
            title="Pengaturan & notifikasi"
            trailing={<Text style={styles.trailingArrow}>→</Text>}
            onPress={() => router.push({ pathname: '/belum-tersedia/[topic]', params: { topic: 'pengaturan' } })}
          />
          <ListItem
            title="Tema"
            trailing={<Text style={styles.trailingCount}>{scheme === 'dark' ? 'Gelap' : 'Terang'}</Text>}
            onPress={toggleScheme}
          />
          <ListItem title="Keluar dari organisasi" titleTone="accent" onPress={() => setLeaveOpen(true)} />
          <ListItem title="Keluar akun" titleTone="accent" onPress={() => setSignOutOpen(true)} isLast />
        </View>
      </ScrollView>

      <Dialog
        visible={leaveOpen}
        title="Keluar dari organisasi?"
        body={`Anda tidak lagi melihat kas, kegiatan, dan tugas ${org.name}. Riwayat iuran Anda tetap tersimpan di catatan organisasi.`}
        confirmLabel="Keluar"
        onCancel={() => setLeaveOpen(false)}
        onConfirm={() => setLeaveOpen(false)}
      />

      <Dialog
        visible={signOutOpen}
        title="Keluar akun?"
        body="Anda perlu masuk kembali dengan email dan kata sandi untuk mengakses RukunMuda."
        confirmLabel="Keluar"
        onCancel={() => setSignOutOpen(false)}
        onConfirm={() => {
          setSignOutOpen(false);
          logout().then(() => router.replace('/'));
        }}
      />

      <OrgSwitcherSheet visible={orgSwitcherOpen} currentOrganizationId={org.id} onClose={() => setOrgSwitcherOpen(false)} />
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
    tagRow: { flexDirection: 'row', gap: theme.space.xs, marginTop: 4 },
    statsRow: {
      flexDirection: 'row',
      borderTopWidth: 2,
      borderTopColor: theme.color.rule,
      borderBottomWidth: 2,
      borderBottomColor: theme.color.rule,
      marginHorizontal: theme.layout.screenPadding,
    },
    statsCell: { flex: 1, paddingVertical: theme.space.md, alignItems: 'flex-start' },
    statsCellBorder: { borderLeftWidth: 1, borderLeftColor: theme.color.divider, paddingLeft: theme.space.md },
    statsValue: { fontFamily: 'Archivo_800ExtraBold', fontSize: 24, color: theme.color.text },
    statsCaption: { fontFamily: 'Archivo_400Regular', fontSize: 12, color: theme.color.textMuted, marginTop: 2 },
    orgCard: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: theme.space.md,
      margin: theme.layout.screenPadding,
    },
    orgTextCol: { flex: 1 },
    orgName: { fontFamily: 'Archivo_800ExtraBold', fontSize: 16, color: theme.color.text },
    orgMeta: { fontFamily: 'Archivo_400Regular', fontSize: 12.5, color: theme.color.textMuted, marginTop: 2 },
    trailingCount: { fontFamily: 'Archivo_600SemiBold', fontSize: 12.5, color: theme.color.textMuted },
    trailingArrow: { fontFamily: 'Archivo_800ExtraBold', fontSize: 15, color: theme.color.textMuted },
    errorSignOut: { alignItems: 'center', marginTop: theme.space.lg },
  });
}
