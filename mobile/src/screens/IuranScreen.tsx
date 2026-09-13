import { router } from 'expo-router';
import { useMemo, useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

import { BottomSheet } from '@/components/BottomSheet';
import { Button } from '@/components/Button';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { FilterChip } from '@/components/FilterChip';
import { FormField } from '@/components/FormSection';
import { MemberItem } from '@/components/MemberItem';
import { PermissionNote } from '@/components/PermissionNote';
import { Progress } from '@/components/Progress';
import { ScreenHeader } from '@/components/ScreenHeader';
import { Segmented } from '@/components/Segmented';
import { Skeleton } from '@/components/Skeleton';
import { SharePreview } from '@/components/SharePreview';
import { useToast } from '@/components/Toast';
import { ApiError } from '@/lib/api';
import {
  type ApiDue,
  type ApiMember,
  useAccounts,
  useCurrentOrganization,
  useDues,
  useFinancialCategories,
  useGenerateMonthlyDues,
  useMembers,
  useRecordDuePayment,
} from '@/lib/queries';
import { formatMonthYear, formatRupiah, initialsOf } from '@/theme/format';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';
import { resolveDuesStatus } from '@/theme/vocab';

const FILTERS = ['Semua', 'Belum bayar', 'Sudah bayar'] as const;
type Filter = (typeof FILTERS)[number];
const METHODS = ['Tunai', 'Transfer'] as const;

function periodKey(date: Date): string {
  return new Date(Date.UTC(date.getFullYear(), date.getMonth(), 1)).toISOString().slice(0, 10);
}

export function IuranScreen() {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const { showToast } = useToast();

  const [monthOffset, setMonthOffset] = useState(0);
  const [filter, setFilter] = useState<Filter>('Belum bayar');
  const [sheetMember, setSheetMember] = useState<ApiMember | null>(null);
  const [pickerOpen, setPickerOpen] = useState(false);
  const [reminderOpen, setReminderOpen] = useState(false);
  const [setupOpen, setSetupOpen] = useState(false);

  const organization = useCurrentOrganization();
  const members = useMembers();
  const dues = useDues();
  const generateMonthly = useGenerateMonthlyDues();

  const myRole = organization.data?.membership.role;
  const canRecord = myRole === 'KETUA' || myRole === 'BENDAHARA';

  const period = useMemo(() => {
    const d = new Date();
    d.setMonth(d.getMonth() + monthOffset);
    return d;
  }, [monthOffset]);
  const periodValue = periodKey(period);

  const periodDues = useMemo(
    () => (dues.data?.data ?? []).filter((d) => d.type === 'MONTHLY' && d.period === periodValue),
    [dues.data, periodValue],
  );

  const duesByMembership = useMemo(() => {
    const map = new Map<string, ApiDue>();
    for (const due of periodDues) if (due.membershipId) map.set(due.membershipId, due);
    return map;
  }, [periodDues]);

  const eligible = periodDues.filter((d) => !d.isExempt);
  const paidCount = eligible.filter((d) => d.isPaid).length;
  const totalCollected = eligible.reduce((sum, d) => sum + d.amountPaid, 0);
  const totalDue = eligible.reduce((sum, d) => sum + d.amountDue, 0);

  const membersWithDues = useMemo(() => {
    const all = members.data?.data ?? [];
    return all
      .filter((m) => !m.leftAt)
      .map((m) => ({ member: m, due: duesByMembership.get(m.id) }))
      .filter(({ due }) => {
        if (filter === 'Semua') return true;
        if (!due) return filter === 'Belum bayar';
        if (filter === 'Sudah bayar') return due.isPaid;
        return !due.isPaid;
      })
      .sort((a, b) => a.member.name.localeCompare(b.member.name, 'id'));
  }, [members.data, duesByMembership, filter]);

  const unpaidNames = (members.data?.data ?? [])
    .filter((m) => !m.leftAt)
    .filter((m) => {
      const due = duesByMembership.get(m.id);
      return !due || (!due.isPaid && !due.isExempt);
    })
    .map((m) => m.name);

  // `canRecord` reads `organization.data`, still `undefined` on a cold
  // load — wait for it too, or a genuine treasurer/chair briefly loses
  // the record-payment action (same race as SusunLaporanScreen).
  if (members.isPending || dues.isPending || organization.isPending) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Iuran" onBack={() => router.back()} />
        <View style={{ padding: theme.layout.screenPadding }}>
          <Skeleton width="100%" height={140} />
        </View>
      </View>
    );
  }

  if (members.isError || dues.isError) {
    return (
      <View style={styles.root}>
        <ScreenHeader title="Iuran" onBack={() => router.back()} />
        <ErrorState
          title="Gagal memuat iuran"
          body="Periksa koneksi internet Anda, lalu coba lagi."
          onRetry={() => {
            members.refetch();
            dues.refetch();
          }}
        />
      </View>
    );
  }

  const onMemberPress = (member: ApiMember) => {
    if (canRecord) {
      setSheetMember(member);
    } else {
      router.push(`/anggota/${member.id}`);
    }
  };

  return (
    <View style={styles.root}>
      <ScreenHeader title="Iuran" onBack={() => router.back()} />
      <ScrollView contentContainerStyle={styles.scroll}>
        <View style={styles.monthRow}>
          <Pressable onPress={() => setMonthOffset((o) => o - 1)} hitSlop={theme.hitSlop} accessibilityRole="button" accessibilityLabel="Bulan sebelumnya">
            <Text style={styles.monthArrow}>‹</Text>
          </Pressable>
          <Text style={styles.monthLabel}>{formatMonthYear(period)}</Text>
          <Pressable onPress={() => setMonthOffset((o) => o + 1)} hitSlop={theme.hitSlop} accessibilityRole="button" accessibilityLabel="Bulan berikutnya">
            <Text style={styles.monthArrow}>›</Text>
          </Pressable>
        </View>

        {eligible.length === 0 ? (
          <EmptyState
            title="Belum ada iuran yang diatur."
            body="Atur iuran bulanan untuk mulai mencatat pembayaran anggota."
            actionLabel={canRecord ? 'Atur iuran bulanan' : undefined}
            onAction={canRecord ? () => setSetupOpen(true) : undefined}
          />
        ) : (
          <>
            <View style={styles.summary}>
              <Text style={styles.summaryLabel}>TERKUMPUL</Text>
              <Text style={styles.summaryAmount}>{formatRupiah(totalCollected)}</Text>
              <Text style={styles.summaryMeta}>
                {formatRupiah(totalCollected)} dari {formatRupiah(totalDue)}
              </Text>
            </View>
            <View style={styles.progressWrap}>
              <Progress
                variant="bar"
                tone="accent"
                value={paidCount}
                total={eligible.length || 1}
                label={`${paidCount} dari ${eligible.length} anggota`}
              />
            </View>

            <View style={styles.note}>
              <Text style={styles.noteText}>
                Aplikasi mencatat pembayaran yang sudah diterima. Pembayaran tetap dilakukan langsung ke bendahara.
              </Text>
            </View>

            <View style={styles.rule} />

            {!canRecord ? (
              <PermissionNote body="Hanya bendahara yang bisa mencatat pembayaran iuran." roleHint="bendahara" />
            ) : null}

            <View style={styles.chipRow}>
              {FILTERS.map((f) => (
                <FilterChip key={f} label={f} active={filter === f} onPress={() => setFilter(f)} />
              ))}
            </View>

            {canRecord && unpaidNames.length > 0 ? (
              <View style={styles.reminderRow}>
                <Button variant="ghost" label="Ingatkan yang belum bayar" onPress={() => setReminderOpen(true)} />
              </View>
            ) : null}

            <View>
              {membersWithDues.map(({ member, due }, i) => (
                <MemberItem
                  key={member.id}
                  initials={initialsOf(member.name)}
                  name={member.name}
                  roles={[member.roleLabel]}
                  duesStatus={due ? resolveDuesStatus(due) : 'unpaid'}
                  onPress={() => onMemberPress(member)}
                  isLast={i === membersWithDues.length - 1}
                />
              ))}
            </View>
          </>
        )}
      </ScrollView>

      {canRecord && eligible.length > 0 ? (
        <View style={styles.actionBar}>
          <Button variant="primary" label="Catat pembayaran" onPress={() => setPickerOpen(true)} block />
        </View>
      ) : null}

      <MemberPickerSheet
        visible={pickerOpen}
        members={(members.data?.data ?? []).filter((m) => !m.leftAt)}
        onClose={() => setPickerOpen(false)}
        onPick={(m) => {
          setPickerOpen(false);
          setSheetMember(m);
        }}
      />

      {sheetMember ? (
        <RecordPaymentSheet
          member={sheetMember}
          due={duesByMembership.get(sheetMember.id)}
          onClose={() => setSheetMember(null)}
          onSuccess={() => {
            showToast('Pembayaran dicatat.');
            setSheetMember(null);
          }}
        />
      ) : null}

      <SharePreview
        visible={reminderOpen}
        onClose={() => setReminderOpen(false)}
        title="Bagikan pengingat"
        lines={[
          'RukunMuda',
          '',
          `Pengingat Iuran ${formatMonthYear(period)}`,
          '',
          'Belum membayar:',
          ...unpaidNames.map((n) => `• ${n}`),
          '',
          'Mohon segera membayar iuran ke bendahara.',
        ]}
        onShare={() => setReminderOpen(false)}
        onCopy={() => setReminderOpen(false)}
      />

      <SetupMonthlyDuesSheet
        visible={setupOpen}
        onClose={() => setSetupOpen(false)}
        onSubmit={(amount) => {
          generateMonthly.mutate(
            { period: periodValue, amount_due: amount },
            {
              onSuccess: () => {
                showToast('Iuran bulanan diatur.');
                setSetupOpen(false);
              },
              onError: (err) => showToast(err instanceof ApiError ? err.message : 'Gagal mengatur iuran.'),
            },
          );
        }}
      />
    </View>
  );
}

function MemberPickerSheet({
  visible,
  members,
  onClose,
  onPick,
}: {
  visible: boolean;
  members: ApiMember[];
  onClose: () => void;
  onPick: (member: ApiMember) => void;
}) {
  const styles = makeStyles(useTheme().theme);

  return (
    <BottomSheet visible={visible} title="Pilih anggota" onClose={onClose} scrollable={false}>
      <ScrollView style={styles.pickerScroll}>
        {members.map((m, i) => (
          <MemberItem
            key={m.id}
            initials={initialsOf(m.name)}
            name={m.name}
            roles={[m.roleLabel]}
            onPress={() => onPick(m)}
            isLast={i === members.length - 1}
          />
        ))}
      </ScrollView>
    </BottomSheet>
  );
}

function RecordPaymentSheet({
  member,
  due,
  onClose,
  onSuccess,
}: {
  member: ApiMember;
  due?: ApiDue;
  onClose: () => void;
  onSuccess: () => void;
}) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const accounts = useAccounts();
  const categories = useFinancialCategories();
  const recordPayment = useRecordDuePayment(due?.id ?? '');

  const [amount, setAmount] = useState(String(due?.amountOutstanding ?? due?.amountDue ?? 0));
  const [method, setMethod] = useState<(typeof METHODS)[number]>('Tunai');
  const [note, setNote] = useState('');

  if (!due) {
    return (
      <BottomSheet visible title="Catat pembayaran" onClose={onClose}>
        <View style={styles.sheetBody}>
          <Text style={styles.emptyDueText}>Anggota ini belum memiliki tagihan iuran periode ini.</Text>
        </View>
      </BottomSheet>
    );
  }

  const account = accounts.data?.data[0];
  const category = categories.data?.data.find((c) => c.transactionType === 'INCOME' && /iuran/i.test(c.name)) ?? categories.data?.data.find((c) => c.transactionType === 'INCOME');

  const submit = () => {
    if (!account || !category) return;

    recordPayment.mutate(
      {
        amount: Number(amount),
        paid_at: new Date().toISOString().slice(0, 10),
        financial_account_id: account.id,
        category_id: category.id,
        method: method === 'Tunai' ? 'TUNAI' : 'TRANSFER',
        note: note || undefined,
      },
      { onSuccess },
    );
  };

  return (
    <BottomSheet visible title="Catat pembayaran" onClose={onClose}>
      <View style={styles.sheetBody}>
        <Text style={styles.sheetHint}>{member.name}</Text>
        <FormField label="Jumlah" value={amount} onChangeText={setAmount} keyboardType="numeric" placeholder="0" />
        <View style={styles.methodField}>
          <Text style={styles.fieldLabel}>Metode</Text>
          <Segmented options={METHODS} value={method} onChange={setMethod} />
        </View>
        <FormField label="Catatan (opsional)" value={note} onChangeText={setNote} placeholder="Catatan tambahan" />
        <View style={styles.sheetAction}>
          <Button
            variant="primary"
            label="Simpan"
            onPress={submit}
            loading={recordPayment.isPending}
            disabled={!account || !category || Number(amount) <= 0}
            block
          />
        </View>
      </View>
    </BottomSheet>
  );
}

function SetupMonthlyDuesSheet({
  visible,
  onClose,
  onSubmit,
}: {
  visible: boolean;
  onClose: () => void;
  onSubmit: (amount: number) => void;
}) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const [amount, setAmount] = useState('25000');

  return (
    <BottomSheet visible={visible} title="Atur iuran bulanan" onClose={onClose}>
      <View style={styles.sheetBody}>
        <FormField label="Besaran iuran per anggota" value={amount} onChangeText={setAmount} keyboardType="numeric" placeholder="0" />
        <View style={styles.sheetAction}>
          <Button variant="primary" label="Simpan" onPress={() => onSubmit(Number(amount))} disabled={Number(amount) <= 0} block />
        </View>
      </View>
    </BottomSheet>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { flex: 1, backgroundColor: theme.color.bg },
    scroll: { paddingBottom: theme.space.xxxl },
    monthRow: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'center',
      gap: theme.space.lg,
      paddingVertical: theme.space.md,
    },
    monthArrow: { fontFamily: 'Archivo_800ExtraBold', fontSize: 20, color: theme.color.text, paddingHorizontal: theme.space.md },
    monthLabel: { fontFamily: 'Archivo_800ExtraBold', fontSize: 15, color: theme.color.text },
    summary: { paddingHorizontal: theme.layout.screenPadding, gap: 4 },
    summaryLabel: {
      fontFamily: 'Archivo_800ExtraBold',
      fontSize: 11,
      lineHeight: 14,
      letterSpacing: 1.0,
      textTransform: 'uppercase',
      color: theme.color.textMuted,
    },
    summaryAmount: { fontFamily: 'Archivo_800ExtraBold', fontSize: 44, lineHeight: 46, color: theme.color.text, fontVariant: ['tabular-nums'] },
    summaryMeta: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted },
    progressWrap: { paddingHorizontal: theme.layout.screenPadding, marginTop: theme.space.md },
    note: { backgroundColor: theme.color.surfaceAlt, margin: theme.layout.screenPadding, padding: theme.space.md },
    noteText: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.text, lineHeight: 19 },
    rule: { height: 2, backgroundColor: theme.color.rule },
    chipRow: {
      flexDirection: 'row',
      gap: theme.space.sm,
      paddingHorizontal: theme.layout.screenPadding,
      marginTop: theme.space.md,
      marginBottom: theme.space.sm,
    },
    reminderRow: { paddingHorizontal: theme.layout.screenPadding, alignItems: 'flex-start' },
    actionBar: { borderTopWidth: 2, borderTopColor: theme.color.rule, padding: theme.space.md, paddingHorizontal: theme.layout.screenPadding },
    pickerScroll: { maxHeight: 420 },
    sheetBody: { paddingHorizontal: theme.layout.screenPadding, gap: theme.space.md },
    sheetHint: { fontFamily: 'Archivo_800ExtraBold', fontSize: 15, color: theme.color.text },
    methodField: { gap: theme.space.xs },
    fieldLabel: {
      fontFamily: 'Archivo_800ExtraBold',
      fontSize: 11,
      lineHeight: 14,
      letterSpacing: 1.0,
      textTransform: 'uppercase',
      color: theme.color.textMuted,
    },
    sheetAction: { marginTop: theme.space.sm },
    emptyDueText: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted },
  });
}
