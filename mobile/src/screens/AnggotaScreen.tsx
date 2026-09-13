import { router, useLocalSearchParams } from 'expo-router';
import { useMemo, useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

import { Button } from '@/components/Button';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { FilterChip } from '@/components/FilterChip';
import { InviteSheet } from '@/components/InviteSheet';
import { MemberItem } from '@/components/MemberItem';
import { ScreenHeader } from '@/components/ScreenHeader';
import { SectionHeader } from '@/components/SectionHeader';
import { Skeleton } from '@/components/Skeleton';
import { type ApiMember, useCurrentOrganization, useDues, useMembers } from '@/lib/queries';
import { initialsOf } from '@/theme/format';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';
import { resolveDuesStatus, type DuesStatus } from '@/theme/vocab';

const FILTERS = ['Semua', 'Pengurus', 'Anggota', 'Baru'] as const;
type Filter = (typeof FILTERS)[number];

function currentMonthPeriod(): string {
  const now = new Date();
  return new Date(Date.UTC(now.getFullYear(), now.getMonth(), 1)).toISOString().slice(0, 10);
}

function isNew(joinedAt: string): boolean {
  return Date.now() - new Date(joinedAt).getTime() < 30 * 24 * 60 * 60 * 1000;
}

export function AnggotaScreen() {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const params = useLocalSearchParams<{ filter?: string }>();
  const [filter, setFilter] = useState<Filter>(
    (FILTERS as readonly string[]).includes(params.filter ?? '') ? (params.filter as Filter) : 'Semua',
  );
  const [inviteOpen, setInviteOpen] = useState(false);

  const organization = useCurrentOrganization();
  const members = useMembers();
  const dues = useDues();

  const myRole = organization.data?.membership.role;
  const canInvite = myRole === 'KETUA' || myRole === 'SEKRETARIS';
  const canSeeDues = myRole === 'KETUA' || myRole === 'BENDAHARA';
  const myMembershipId = organization.data?.membership.id;

  const duesStatusByMembership = useMemo(() => {
    const period = currentMonthPeriod();
    const map = new Map<string, DuesStatus>();
    for (const due of dues.data?.data ?? []) {
      if (due.type !== 'MONTHLY' || due.period !== period || !due.membershipId) continue;
      map.set(due.membershipId, resolveDuesStatus(due));
    }
    return map;
  }, [dues.data]);

  const { pengurus, anggota, pengurusCount } = useMemo(() => {
    const all = members.data?.data ?? [];
    const filtered = all.filter((m) => {
      if (filter === 'Pengurus') return m.role !== 'ANGGOTA';
      if (filter === 'Anggota') return m.role === 'ANGGOTA';
      if (filter === 'Baru') return isNew(m.joinedAt);
      return true;
    });

    const sortGroup = (list: ApiMember[]) =>
      [...list].sort((a, b) => {
        if (a.id === myMembershipId) return -1;
        if (b.id === myMembershipId) return 1;
        return a.name.localeCompare(b.name, 'id');
      });

    return {
      pengurus: sortGroup(filtered.filter((m) => m.role !== 'ANGGOTA')),
      anggota: sortGroup(filtered.filter((m) => m.role === 'ANGGOTA')),
      pengurusCount: all.filter((m) => m.role !== 'ANGGOTA' && !m.leftAt).length,
    };
  }, [members.data, filter, myMembershipId]);

  const totalCount = (members.data?.data ?? []).filter((m) => !m.leftAt).length;

  const renderRow = (member: ApiMember, isLast: boolean) => (
    <MemberItem
      key={member.id}
      initials={initialsOf(member.name)}
      name={member.id === myMembershipId ? `${member.name} (Anda)` : member.name}
      roles={[member.roleLabel]}
      meta={member.activityPoints > 0 ? `${member.activityPoints} poin` : undefined}
      duesStatus={canSeeDues && !member.leftAt ? (duesStatusByMembership.get(member.id) ?? 'unpaid') : undefined}
      hasLeft={Boolean(member.leftAt)}
      onPress={() => router.push(`/anggota/${member.id}`)}
      isLast={isLast}
    />
  );

  return (
    <View style={styles.root}>
      <ScreenHeader
        title="Anggota"
        onBack={() => router.back()}
        right={
          <>
            <Pressable onPress={() => router.push('/anggota/peran')} hitSlop={theme.hitSlop} android_ripple={null} accessibilityRole="button">
              <Text style={styles.peranAction}>Peran</Text>
            </Pressable>
            <Pressable
              onPress={() => router.push({ pathname: '/pencarian', params: { scope: 'anggota', ...(filter !== 'Semua' ? { filter } : {}) } })}
              hitSlop={theme.hitSlop}
              android_ripple={null}
              accessibilityRole="button"
            >
              <Text style={styles.searchAction}>⌕</Text>
            </Pressable>
          </>
        }
      />

      {members.isPending ? (
        <View style={styles.scroll}>
          <Skeleton width="100%" height={64} />
          <View style={styles.skeletonGap} />
          <Skeleton width="100%" height={64} />
          <View style={styles.skeletonGap} />
          <Skeleton width="100%" height={64} />
          <View style={styles.skeletonGap} />
          <Skeleton width="100%" height={64} />
          <View style={styles.skeletonGap} />
          <Skeleton width="100%" height={64} />
          <View style={styles.skeletonGap} />
          <Skeleton width="100%" height={64} />
        </View>
      ) : members.isError ? (
        <ErrorState title="Gagal memuat anggota" body="Periksa koneksi internet Anda, lalu coba lagi." onRetry={() => members.refetch()} />
      ) : (
        <>
          <ScrollView contentContainerStyle={styles.scroll}>
            <Text style={styles.countLine}>
              {totalCount} anggota · {pengurusCount} pengurus
            </Text>

            <View style={styles.chipRow}>
              {FILTERS.map((f) => (
                <FilterChip key={f} label={f} active={filter === f} onPress={() => setFilter(f)} />
              ))}
            </View>

            {pengurus.length === 0 && anggota.length === 0 ? (
              <EmptyState
                title="Belum ada anggota lain."
                body="Undang anggota untuk mulai mengelola organisasi bersama."
                actionLabel={canInvite ? 'Undang anggota' : undefined}
                onAction={canInvite ? () => setInviteOpen(true) : undefined}
              />
            ) : (
              <>
                {pengurus.length > 0 ? (
                  <>
                    <SectionHeader title="Pengurus" />
                    <View style={styles.list}>{pengurus.map((m, i) => renderRow(m, i === pengurus.length - 1))}</View>
                  </>
                ) : null}

                {pengurus.length > 0 && anggota.length > 0 ? <View style={styles.rule} /> : null}

                {anggota.length > 0 ? (
                  <>
                    <SectionHeader title="Anggota" />
                    <View style={styles.list}>{anggota.map((m, i) => renderRow(m, i === anggota.length - 1))}</View>
                  </>
                ) : null}
              </>
            )}
          </ScrollView>

          {canInvite && (pengurus.length > 0 || anggota.length > 0) ? (
            <View style={styles.actionBar}>
              <Button variant="primary" label="Undang anggota" onPress={() => setInviteOpen(true)} block />
            </View>
          ) : null}
        </>
      )}

      <InviteSheet visible={inviteOpen} onClose={() => setInviteOpen(false)} />
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { flex: 1, backgroundColor: theme.color.bg },
    peranAction: { fontFamily: 'Archivo_800ExtraBold', fontSize: 13, color: theme.color.accent },
    searchAction: { fontSize: 18, color: theme.color.accent },
    scroll: { paddingBottom: theme.space.xxxl },
    skeletonGap: { height: theme.space.sm },
    countLine: {
      fontFamily: 'Archivo_400Regular',
      fontSize: 13,
      color: theme.color.textMuted,
      paddingHorizontal: theme.layout.screenPadding,
      paddingTop: theme.space.md,
    },
    chipRow: {
      flexDirection: 'row',
      flexWrap: 'wrap',
      gap: theme.space.sm,
      paddingHorizontal: theme.layout.screenPadding,
      marginTop: theme.space.md,
    },
    list: {},
    rule: { height: 2, backgroundColor: theme.color.rule, marginTop: theme.space.md },
    actionBar: {
      borderTopWidth: 2,
      borderTopColor: theme.color.rule,
      padding: theme.space.md,
      paddingHorizontal: theme.layout.screenPadding,
    },
  });
}
