import { Pressable, StyleSheet, Text, View } from 'react-native';

import { formatRupiah } from '@/theme/format';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';
import { SPONSOR_STATUS_TAG, type SponsorStatus } from '@/theme/vocab';
import { Tag } from './Tag';

type Props = {
  name: string;
  /** Uang · Barang · Jasa. */
  type: string;
  /** Omit for in-kind sponsorships (Barang/Jasa) — the trailing slot shows "—". */
  amount?: number;
  status: SponsorStatus;
  eventName?: string;
  onPress?: () => void;
  isLast?: boolean;
};

/** Amount plus status Tag exceeds ListItem's trailing slot — same reason as InventoryItem. */
export function SponsorItem({ name, type, amount, status, eventName, onPress, isLast }: Props) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const tag = SPONSOR_STATUS_TAG[status];

  return (
    <Pressable
      onPress={onPress}
      hitSlop={theme.hitSlop}
      android_ripple={null}
      accessibilityRole={onPress ? 'button' : undefined}
      style={({ pressed }) => [styles.row, !isLast && styles.divider, pressed && onPress && styles.pressed]}
    >
      <View style={styles.textCol}>
        <Text style={styles.name} numberOfLines={1}>
          {name}
        </Text>
        <Text style={styles.meta} numberOfLines={1}>
          {[eventName, type].filter(Boolean).join(' · ')}
        </Text>
      </View>
      <View style={styles.trailing}>
        <Text style={styles.amount}>{amount != null ? formatRupiah(amount) : '—'}</Text>
        <Tag tone={tag.tone} label={tag.label} mutedFill={tag.mutedFill} />
      </View>
    </Pressable>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    row: {
      minHeight: theme.layout.rowMinHeight,
      paddingVertical: 13,
      paddingHorizontal: 14,
      flexDirection: 'row',
      alignItems: 'center',
    },
    divider: { borderBottomWidth: 1, borderBottomColor: theme.color.divider },
    pressed: { backgroundColor: theme.color.surfaceAlt },
    textCol: { flex: 1 },
    name: { fontFamily: 'Archivo_800ExtraBold', fontSize: 15, color: theme.color.text },
    meta: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted, marginTop: 2 },
    trailing: { marginLeft: theme.space.md, alignItems: 'flex-end', gap: 4 },
    amount: { fontFamily: 'Archivo_800ExtraBold', fontSize: 20, lineHeight: 24, color: theme.color.text, fontVariant: ['tabular-nums'] },
  });
}
