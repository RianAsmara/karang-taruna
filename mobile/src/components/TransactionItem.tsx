import { StyleSheet, Text, View } from 'react-native';

import { formatSigned } from '@/theme/format';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';
import { Tag } from './Tag';

type Props = {
  kind: 'in' | 'out';
  title: string;
  meta: string;
  amount: number;
  /** e.g. "MENUNGGU" — rendered as a tint Tag inline in the meta row. Amount color never changes for this. */
  status?: string;
  isLast?: boolean;
};

export function TransactionItem({ kind, title, meta, amount, status, isLast }: Props) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const metaSuffix = kind === 'in' ? 'Masuk' : 'Keluar';

  return (
    <View
      style={[styles.row, !isLast && styles.divider]}
      accessibilityLabel={`${kind === 'in' ? 'Uang masuk' : 'Uang keluar'}, ${title}, ${meta}`}
    >
      <View style={styles.leading}>
        <Text style={styles.leadingGlyph}>{kind === 'in' ? '+' : '−'}</Text>
      </View>
      <View style={styles.textCol}>
        <Text style={styles.title} numberOfLines={1}>
          {title}
        </Text>
        <View style={styles.metaRow}>
          <Text style={styles.meta} numberOfLines={1}>
            {meta} · {metaSuffix}
          </Text>
          {status ? (
            <View style={styles.statusTag}>
              <Tag tone="tint" label={status} />
            </View>
          ) : null}
        </View>
      </View>
      <Text style={[styles.amount, { color: kind === 'in' ? theme.color.income : theme.color.expense }]}>
        {formatSigned(amount, kind)}
      </Text>
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    row: {
      flexDirection: 'row',
      alignItems: 'center',
      paddingVertical: theme.space.md,
      paddingHorizontal: theme.space.lg,
    },
    divider: { borderBottomWidth: 1, borderBottomColor: theme.color.divider },
    leading: {
      width: 28,
      height: 28,
      borderWidth: 1,
      borderColor: theme.color.divider,
      borderRadius: theme.radius,
      alignItems: 'center',
      justifyContent: 'center',
      marginRight: theme.space.md,
    },
    leadingGlyph: { fontFamily: 'Archivo_800ExtraBold', fontSize: 15, color: theme.color.text },
    textCol: { flex: 1 },
    title: { fontFamily: 'Archivo_800ExtraBold', fontSize: 14, color: theme.color.text },
    metaRow: { flexDirection: 'row', alignItems: 'center', marginTop: 2, gap: theme.space.xs },
    meta: { fontFamily: 'Archivo_400Regular', fontSize: 11.5, color: theme.color.textMuted },
    statusTag: {},
    amount: { fontFamily: 'Archivo_800ExtraBold', fontSize: 15, lineHeight: 20, fontVariant: ['tabular-nums'] },
  });
}
