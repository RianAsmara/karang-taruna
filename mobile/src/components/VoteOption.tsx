import { Pressable, StyleSheet, Text, View } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';
import { Progress } from './Progress';

type Props = {
  label: string;
  description?: string;
  selected: boolean;
  disabled?: boolean;
  /** Present only in result mode (28 Voting — Hasil, or after closing). `percent` omitted when too few votes to protect anonymity. */
  result?: { count: number; percent?: number };
  onPress: () => void;
  isLast?: boolean;
};

/** TaskItem is a completion toggle with different semantics and no result state — see mobile-components.md § New in this phase. */
export function VoteOption({ label, description, selected, disabled, result, onPress, isLast }: Props) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);

  return (
    <Pressable
      onPress={disabled ? undefined : onPress}
      disabled={disabled}
      hitSlop={theme.hitSlop}
      android_ripple={null}
      accessibilityRole="radio"
      accessibilityState={{ checked: selected, disabled }}
      accessibilityLabel={label}
      style={({ pressed }) => [
        styles.row,
        !isLast && styles.divider,
        pressed && !disabled && styles.pressed,
        disabled && !selected && styles.disabled,
      ]}
    >
      <View style={styles.head}>
        <View style={[styles.selector, selected && styles.selectorFilled]}>
          {selected ? <Text style={styles.check}>✓</Text> : null}
        </View>
        <View style={styles.textCol}>
          <Text style={styles.label} numberOfLines={2}>
            {label}
          </Text>
          {description ? (
            <Text style={styles.description} numberOfLines={2}>
              {description}
            </Text>
          ) : null}
        </View>
      </View>
      {result ? (
        <View style={styles.resultBlock}>
          <Progress variant="bar" value={result.percent ?? 0} total={100} label={label} compact showLabel={false} />
          <Text style={styles.resultLabel}>
            {result.percent != null ? `${result.count} suara · ${result.percent}%` : `${result.count} suara`}
          </Text>
        </View>
      ) : null}
    </Pressable>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    row: {
      minHeight: 56,
      paddingVertical: theme.space.md,
      paddingHorizontal: theme.layout.screenPadding,
    },
    divider: { borderBottomWidth: 1, borderBottomColor: theme.color.divider },
    pressed: { backgroundColor: theme.color.surfaceAlt },
    disabled: { opacity: 0.45 },
    head: { flexDirection: 'row', alignItems: 'flex-start' },
    selector: {
      width: 20,
      height: 20,
      borderWidth: 1.5,
      borderColor: theme.color.divider,
      alignItems: 'center',
      justifyContent: 'center',
      marginRight: theme.space.md,
      marginTop: 1,
    },
    selectorFilled: { backgroundColor: theme.color.text, borderColor: theme.color.text },
    check: { color: theme.color.onInk, fontSize: 13, fontFamily: 'Archivo_800ExtraBold' },
    textCol: { flex: 1 },
    label: { fontFamily: 'Archivo_800ExtraBold', fontSize: 15, color: theme.color.text },
    description: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted, marginTop: 2 },
    resultBlock: { marginTop: theme.space.sm, marginLeft: 20 + theme.space.md, gap: 4 },
    resultLabel: {
      fontFamily: 'Archivo_800ExtraBold',
      fontSize: 15,
      lineHeight: 20,
      color: theme.color.text,
      fontVariant: ['tabular-nums'],
    },
  });
}
