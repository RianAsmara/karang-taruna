import { Pressable, StyleSheet, Text, View } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

type Props<T extends string> = {
  items: readonly T[];
  value: T;
  onChange: (value: T) => void;
};

export function Tabs<T extends string>({ items, value, onChange }: Props<T>) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);

  return (
    <View style={styles.row} accessibilityRole="tablist">
      {items.map((item) => {
        const active = item === value;
        return (
          <Pressable
            key={item}
            onPress={() => onChange(item)}
            hitSlop={theme.hitSlop}
            android_ripple={null}
            accessibilityRole="tab"
            accessibilityState={{ selected: active }}
            style={[styles.item, active && styles.itemActive]}
          >
            <Text style={[styles.label, active && styles.labelActive]}>{item}</Text>
          </Pressable>
        );
      })}
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    row: {
      flexDirection: 'row',
      gap: theme.space.xl - 4,
      paddingHorizontal: theme.layout.screenPadding,
      borderBottomWidth: 2,
      borderBottomColor: theme.color.rule,
    },
    item: { paddingVertical: 14 },
    itemActive: { borderBottomWidth: 2, borderBottomColor: theme.color.accent, marginBottom: -2 },
    label: { fontFamily: 'Archivo_600SemiBold', fontSize: 14, color: theme.color.textMuted },
    labelActive: { color: theme.color.text, fontFamily: 'Archivo_800ExtraBold' },
  });
}
