import { PixelRatio, Pressable, StyleSheet, Text, View } from 'react-native';

import { formatRupiah, formatSigned } from '@/theme/format';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';
import { SectionHeader } from './SectionHeader';

type Props = {
  label: string;
  amount: number;
  meta: string;
  income: number;
  expense: number;
  onPress?: () => void;
  /**
   * 'inline' (default) — Home: a small 13px "+Rp... masuk / −Rp... keluar" row.
   * 'cells' — Kas: a two-cell "MASUK · {monthLabel} / KELUAR · {monthLabel}"
   * block at type.numLg, per section 05's BalanceDisplay prop table.
   */
  variant?: 'inline' | 'cells';
  /** Required when variant="cells", e.g. "AGUSTUS". */
  monthLabel?: string;
};

export function BalanceDisplay({ label, amount, meta, income, expense, onPress, variant = 'inline', monthLabel }: Props) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const stacked = PixelRatio.getFontScale() > 1.3;

  return (
    <View>
      <SectionHeader title={label} />
      <Pressable
        onPress={onPress}
        hitSlop={theme.hitSlop}
        android_ripple={null}
        accessibilityRole={onPress ? 'button' : undefined}
        style={styles.amountTouch}
      >
        <Text
          style={[styles.amount, { color: theme.color.balance }]}
          accessibilityLabel={`Saldo, ${formatRupiah(amount)}`}
        >
          {formatRupiah(amount)}
        </Text>
      </Pressable>

      {variant === 'inline' ? (
        <View style={[styles.flowRow, stacked && styles.flowRowStacked]}>
          <View style={[styles.flowCell, stacked && styles.flowCellStacked]}>
            <Text style={[styles.flowAmount, { color: theme.color.income }]}>{formatSigned(income, 'in')}</Text>
            <Text style={styles.flowLabel}>masuk</Text>
          </View>
          {!stacked ? <View style={styles.flowDivider} /> : null}
          <View style={[styles.flowCell, stacked && styles.flowCellStacked]}>
            <Text style={[styles.flowAmount, { color: theme.color.expense }]}>{formatSigned(expense, 'out')}</Text>
            <Text style={styles.flowLabel}>keluar</Text>
          </View>
        </View>
      ) : (
        <View style={styles.cellsRow}>
          <View style={styles.cell}>
            <Text style={styles.cellLabel}>MASUK · {monthLabel}</Text>
            <Text style={[styles.cellAmount, { color: theme.color.income }]}>{formatSigned(income, 'in')}</Text>
          </View>
          <View style={[styles.cell, styles.cellBorder]}>
            <Text style={styles.cellLabel}>KELUAR · {monthLabel}</Text>
            <Text style={[styles.cellAmount, { color: theme.color.expense }]}>{formatSigned(expense, 'out')}</Text>
          </View>
        </View>
      )}

      <Text style={styles.meta}>{meta}</Text>
      <View style={styles.rule} />
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    amountTouch: { paddingHorizontal: theme.layout.screenPadding },
    amount: { fontFamily: 'Archivo_800ExtraBold', fontSize: 44, lineHeight: 46, letterSpacing: -1.3, fontVariant: ['tabular-nums'] },
    flowRow: {
      flexDirection: 'row',
      alignItems: 'center',
      paddingHorizontal: theme.layout.screenPadding,
      marginTop: theme.space.md,
      borderTopWidth: 2,
      borderTopColor: theme.color.rule,
      paddingTop: theme.space.md,
    },
    flowRowStacked: { flexDirection: 'column', alignItems: 'flex-start', gap: theme.space.xs },
    flowCell: { flex: 1, flexDirection: 'row', alignItems: 'baseline', gap: theme.space.xs },
    flowCellStacked: { flex: undefined },
    flowDivider: { width: 1, height: 16, backgroundColor: theme.color.divider, marginHorizontal: theme.space.md },
    flowAmount: { fontFamily: 'Archivo_800ExtraBold', fontSize: 13, fontVariant: ['tabular-nums'] },
    flowLabel: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted },
    cellsRow: {
      flexDirection: 'row',
      marginTop: theme.space.md,
      borderTopWidth: 2,
      borderTopColor: theme.color.rule,
    },
    cell: { flex: 1, paddingHorizontal: theme.layout.screenPadding, paddingTop: theme.space.md, gap: 4 },
    cellBorder: { borderLeftWidth: 1, borderLeftColor: theme.color.divider },
    cellLabel: { fontFamily: 'Archivo_800ExtraBold', fontSize: 11, letterSpacing: 1, color: theme.color.textMuted },
    cellAmount: { fontFamily: 'Archivo_800ExtraBold', fontSize: 20, lineHeight: 24, fontVariant: ['tabular-nums'] },
    meta: {
      fontFamily: 'Archivo_400Regular',
      fontSize: 12,
      color: theme.color.textMuted,
      paddingHorizontal: theme.layout.screenPadding,
      marginTop: theme.space.sm,
    },
    rule: { height: 2, backgroundColor: theme.color.rule, marginTop: theme.space.lg },
  });
}
