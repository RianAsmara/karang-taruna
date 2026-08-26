import { Pressable, StyleSheet, Text, View } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

type Props<T extends string> = {
  options: readonly T[];
  value: T;
  onChange: (value: T) => void;
};

export function Segmented<T extends string>({ options, value, onChange }: Props<T>) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);

  return (
    <View style={styles.row} accessibilityRole="tablist">
      {options.map((option, i) => {
        const active = option === value;
        return (
          <Pressable
            key={option}
            onPress={() => onChange(option)}
            android_ripple={null}
            accessibilityRole="tab"
            accessibilityState={{ selected: active }}
            style={[styles.option, i > 0 && styles.optionBorder, active && styles.optionActive]}
          >
            <Text style={[styles.label, active && styles.labelActive]} numberOfLines={1}>
              {option}
            </Text>
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
      height: 44,
      borderWidth: 1,
      borderColor: theme.color.divider,
      marginHorizontal: theme.layout.screenPadding,
    },
    option: { flex: 1, alignItems: 'center', justifyContent: 'center' },
    optionBorder: { borderLeftWidth: 1, borderLeftColor: theme.color.divider },
    optionActive: { backgroundColor: theme.color.text },
    label: { fontFamily: 'Archivo_600SemiBold', fontSize: 13, color: theme.color.text },
    labelActive: { color: theme.color.onInk, fontFamily: 'Archivo_800ExtraBold' },
  });
}
