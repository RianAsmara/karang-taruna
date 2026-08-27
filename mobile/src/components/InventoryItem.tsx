import { Pressable, StyleSheet, Text, View } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';
import { INVENTORY_AVAILABILITY_TAG, type InventoryAvailability } from '@/theme/vocab';
import { Tag } from './Tag';

type Props = {
  name: string;
  category: string;
  available: number;
  total: number;
  condition: string;
  status: InventoryAvailability;
  onPress?: () => void;
  isLast?: boolean;
};

/** Quantity fraction plus an availability Tag exceeds ListItem's single trailing slot — see mobile-components.md § New in this phase. */
export function InventoryItem({ name, category, available, total, condition, status, onPress, isLast }: Props) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const tag = INVENTORY_AVAILABILITY_TAG[status];
  // Quantity 1 reads as plain Tersedia/Dipinjam, no fraction (screen 18 edge case).
  const quantityLabel = total === 1 ? null : `${available}/${total}`;

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
          {category} · {condition}
        </Text>
      </View>
      <View style={styles.trailing}>
        {quantityLabel ? <Text style={styles.quantity}>{quantityLabel}</Text> : null}
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
    name: { fontFamily: 'Archivo_800ExtraBold', fontSize: 14, color: theme.color.text },
    meta: { fontFamily: 'Archivo_400Regular', fontSize: 12, color: theme.color.textMuted, marginTop: 2 },
    trailing: { marginLeft: theme.space.md, alignItems: 'flex-end', gap: 4 },
    quantity: { fontFamily: 'Archivo_800ExtraBold', fontSize: 15, lineHeight: 20, color: theme.color.text, fontVariant: ['tabular-nums'] },
  });
}
