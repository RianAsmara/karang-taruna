import { router, useLocalSearchParams } from 'expo-router';
import { useMemo, useState } from 'react';
import { Linking, ScrollView, StyleSheet, Text, View } from 'react-native';

import { Button } from '@/components/Button';
import { Dialog } from '@/components/Dialog';
import { ErrorState } from '@/components/ErrorState';
import { ListItem } from '@/components/ListItem';
import { PermissionNote } from '@/components/PermissionNote';
import { ScreenHeader } from '@/components/ScreenHeader';
import { SectionHeader } from '@/components/SectionHeader';
import { Skeleton } from '@/components/Skeleton';
import { Tag } from '@/components/Tag';
import { useToast } from '@/components/Toast';
import { ApiError } from '@/lib/api';
import {
  useAccounts,
  useCurrentOrganization,
  useFinancialCategories,
  useSponsorContribution,
  useSponsorContributions,
  useUpdateSponsorContributionStatus,
} from '@/lib/queries';
import { formatDateShort, formatRupiah } from '@/theme/format';
import { SPONSOR_STATUS_TAG, resolveSponsorStatus } from '@/theme/vocab';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

export function SponsorDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const { showToast } = useToast();

  const [confirmAction, setConfirmAction] = useState<'SETUJU' | 'DITERIMA' | 'BATAL' | null>(null);

  const organization = useCurrentOrganization();
  const contribution = useSponsorContribution(id);
  const allContributions = useSponsorContributions();
  const accounts = useAccounts();
  const categories = useFinancialCategories();
  const updateStatus = useUpdateSponsorContributionStatus(id);

  const canManage = organization.data?.membership.role === 'KETUA' || organization.data?.membership.role === 'BENDAHARA';

  const history = useMemo(() => {
    if (!contribution.data) return [];
    const sponsorId = contribution.data.data.sponsor.id;
    return (allContributions.data?.data ?? []).filter((c) => c.sponsor.id === sponsorId && c.id !== id);
  }, [allContributions.data, contribution.data, id]);

  // `canManage` reads `organization.data`, still `undefined` on a cold
  // load — wait for it too, or a genuine treasurer/chair briefly loses
  // access to the approve/receive actions (same race as SusunLaporanScreen).
  if (contribution.isPending || organization.isPending) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Detail sponsor" onBack={() => router.back()} />
        <View style={{ padding: theme.layout.screenPadding }}>
          <Skeleton width="100%" height={160} />
        </View>
      </View>
    );
  }

  if (contribution.isError) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Detail sponsor" onBack={() => router.back()} />
        <ErrorState title="Gagal memuat sponsor" body="Periksa koneksi internet Anda, lalu coba lagi." onRetry={() => contribution.refetch()} />
      </View>
    );
  }

  const c = contribution.data!.data;
  const tag = SPONSOR_STATUS_TAG[resolveSponsorStatus(c.status)];

  const confirm = () => {
    if (!confirmAction) return;

    const account = accounts.data?.data[0];
    const category = categories.data?.data.find((cat) => cat.transactionType === 'INCOME' && /sponsor/i.test(cat.name)) ?? categories.data?.data.find((cat) => cat.transactionType === 'INCOME');
    const recordTransaction = confirmAction === 'DITERIMA' && c.type === 'UANG' && account && category;

    updateStatus.mutate(
      {
        status: confirmAction,
        ...(recordTransaction ? { financial_account_id: account.id, category_id: category.id } : {}),
      },
      {
        onSuccess: () => {
          setConfirmAction(null);
          showToast('Status sponsor diperbarui.');
        },
        onError: (err) => {
          setConfirmAction(null);
          showToast(err instanceof ApiError ? err.message : 'Gagal memperbarui status.');
        },
      },
    );
  };

  return (
    <View style={styles.root}>
      <ScreenHeader title={c.sponsor.name} onBack={() => router.back()} />
      <ScrollView contentContainerStyle={styles.scroll}>
        <View style={styles.headerBlock}>
          <Text style={styles.name}>{c.sponsor.name}</Text>
          <View style={styles.tagRow}>
            <Tag tone="outline" label={c.typeLabel} />
            <Tag tone={tag.tone} label={tag.label} mutedFill={tag.mutedFill} />
          </View>
          {c.type === 'UANG' ? (
            <Text style={styles.amount}>{formatRupiah(c.amount ?? 0)}</Text>
          ) : (
            <Text style={styles.description}>{c.description}</Text>
          )}
        </View>

        <View style={styles.metaBlock}>
          <Text style={styles.metaLine}>Kegiatan · {c.event?.title ?? 'Tanpa kegiatan'}</Text>
          <Text style={styles.metaLine}>Dicatat · {formatDateShort(c.createdAt)}</Text>
        </View>

        {canManage ? (
          <>
            <SectionHeader title="Kontak" />
            {c.sponsor.contactName || c.sponsor.contactPhone ? (
              <View style={styles.section}>
                {c.sponsor.contactName ? <Text style={styles.conditionText}>{c.sponsor.contactName}</Text> : null}
                {c.sponsor.contactPhone ? (
                  <>
                    <Text style={styles.conditionMeta}>{c.sponsor.contactPhone}</Text>
                    <View style={styles.chatAction}>
                      <Button
                        variant="ghost"
                        label="Chat WhatsApp"
                        onPress={() => Linking.openURL(`https://wa.me/${c.sponsor.contactPhone!.replace(/\D/g, '')}`)}
                      />
                    </View>
                  </>
                ) : null}
              </View>
            ) : (
              <View style={styles.section}>
                <Text style={styles.conditionMeta}>Belum ada kontak.</Text>
              </View>
            )}
          </>
        ) : (
          <PermissionNote body="Hanya bendahara dan ketua yang mengelola sponsor." roleHint="bendahara" />
        )}

        {c.notes ? (
          <>
            <SectionHeader title="Catatan" />
            <View style={styles.section}>
              <Text style={styles.conditionMeta}>{c.notes}</Text>
            </View>
          </>
        ) : null}

        <SectionHeader title="Riwayat dukungan" />
        {history.length === 0 ? (
          <View style={styles.section}>
            <Text style={styles.conditionMeta}>Belum ada dukungan sebelumnya.</Text>
          </View>
        ) : (
          history.map((h, i) => (
            <ListItem
              key={h.id}
              title={h.event?.title ?? 'Tanpa kegiatan'}
              subtitle={`${h.amount != null ? formatRupiah(h.amount) : h.typeLabel} · ${SPONSOR_STATUS_TAG[resolveSponsorStatus(h.status)].label}`}
              isLast={i === history.length - 1}
            />
          ))
        )}
      </ScrollView>

      {canManage && c.status !== 'BATAL' ? (
        <View style={styles.actionBar}>
          {c.status === 'DIAJUKAN' ? (
            <Button variant="primary" label="Setujui" onPress={() => setConfirmAction('SETUJU')} block />
          ) : c.status === 'SETUJU' ? (
            <Button variant="primary" label="Tandai diterima" onPress={() => setConfirmAction('DITERIMA')} block />
          ) : null}
          <View style={styles.cancelAction}>
            <Button variant="ghost" label="Batalkan" onPress={() => setConfirmAction('BATAL')} />
          </View>
        </View>
      ) : null}

      <Dialog
        visible={confirmAction !== null}
        title={confirmAction === 'BATAL' ? 'Batalkan sponsor' : 'Perbarui status'}
        body={
          confirmAction === 'BATAL'
            ? `Batalkan dukungan dari "${c.sponsor.name}"?`
            : confirmAction === 'DITERIMA' && c.type === 'UANG'
              ? 'Menandai diterima akan mencatat transaksi pemasukan yang sesuai.'
              : `Tandai dukungan ini sebagai ${confirmAction === 'SETUJU' ? 'Setuju' : 'Diterima'}?`
        }
        confirmLabel="Lanjutkan"
        onCancel={() => setConfirmAction(null)}
        onConfirm={confirm}
      />
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { flex: 1, backgroundColor: theme.color.bg },
    scroll: { paddingBottom: theme.space.xxxl },
    headerBlock: { paddingHorizontal: theme.layout.screenPadding, marginTop: theme.space.md, gap: theme.space.sm },
    name: { fontFamily: 'Archivo_800ExtraBold', fontSize: 26, color: theme.color.text },
    tagRow: { flexDirection: 'row', gap: theme.space.sm },
    amount: { fontFamily: 'Archivo_800ExtraBold', fontSize: 32, color: theme.color.text, fontVariant: ['tabular-nums'] },
    description: { fontFamily: 'Archivo_600SemiBold', fontSize: 17, color: theme.color.text },
    metaBlock: {
      marginHorizontal: theme.layout.screenPadding,
      marginTop: theme.space.md,
      paddingVertical: theme.space.sm,
      borderTopWidth: 2,
      borderBottomWidth: 2,
      borderColor: theme.color.rule,
      gap: 2,
    },
    metaLine: { fontFamily: 'Archivo_600SemiBold', fontSize: 13, color: theme.color.text },
    section: { paddingHorizontal: theme.layout.screenPadding, gap: 2 },
    conditionText: { fontFamily: 'Archivo_600SemiBold', fontSize: 15, color: theme.color.text },
    conditionMeta: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted },
    chatAction: { alignSelf: 'flex-start', marginTop: theme.space.xs },
    actionBar: { paddingHorizontal: theme.layout.screenPadding, paddingVertical: theme.space.md, borderTopWidth: 2, borderTopColor: theme.color.text },
    cancelAction: { alignItems: 'center', marginTop: theme.space.sm },
  });
}
