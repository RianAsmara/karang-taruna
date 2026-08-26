import { StyleSheet, Text, View } from 'react-native';

import { formatRupiah, formatSigned } from '@/theme/format';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

type Props = {
  openingLabel?: string;
  opening: number;
  incomeLabel?: string;
  income: number;
  expenseLabel?: string;
  expense: number;
  closingLabel?: string;
  closing: number;
};

export function ReportSummary({
  openingLabel = 'Saldo awal',
  opening,
  incomeLabel = 'Uang masuk',
  income,
  expenseLabel = 'Uang keluar',
  expense,
  closingLabel = 'Saldo akhir',
  closing,
}: Props) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);

  return (
    <View style={styles.frame}>
      <View style={styles.row}>
        <Text style={styles.rowLabel}>{openingLabel}</Text>
        <Text style={styles.rowValue}>{formatRupiah(opening)}</Text>
      </View>
      <View style={styles.row}>
        <Text style={styles.rowLabel}>{incomeLabel}</Text>
        <Text style={styles.rowValue}>{formatSigned(income, 'in')}</Text>
      </View>
      <View style={[styles.row, styles.lastRow]}>
        <Text style={styles.rowLabel}>{expenseLabel}</Text>
        <Text style={styles.rowValue}>{formatSigned(expense, 'out')}</Text>
      </View>
      <View style={styles.closingRow}>
        <Text style={styles.closingLabel}>{closingLabel}</Text>
        <Text style={styles.closingValue}>{formatRupiah(closing)}</Text>
      </View>
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    frame: { borderWidth: 2, borderColor: theme.color.text, marginHorizontal: theme.layout.screenPadding },
    row: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      paddingVertical: theme.space.sm + 2,
      paddingHorizontal: theme.space.lg,
      borderBottomWidth: 1,
      borderBottomColor: theme.color.divider,
    },
    lastRow: { borderBottomWidth: 2, borderBottomColor: theme.color.rule },
    rowLabel: { fontFamily: 'Archivo_400Regular', fontSize: 14, color: theme.color.text },
    rowValue: { fontFamily: 'Archivo_800ExtraBold', fontSize: 14, color: theme.color.text, fontVariant: ['tabular-nums'] },
    closingRow: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
      paddingVertical: theme.space.md,
      paddingHorizontal: theme.space.lg,
    },
    closingLabel: { fontFamily: 'Archivo_800ExtraBold', fontSize: 15, color: theme.color.text },
    closingValue: { fontFamily: 'Archivo_800ExtraBold', fontSize: 26, color: theme.color.text, fontVariant: ['tabular-nums'] },
  });
}
