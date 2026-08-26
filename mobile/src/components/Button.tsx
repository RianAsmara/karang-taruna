import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

export type ButtonVariant = 'primary' | 'secondary' | 'ghost';

type Props = {
  variant: ButtonVariant;
  label: string;
  onPress?: () => void;
  disabled?: boolean;
  loading?: boolean;
  block?: boolean;
  /** 44 instead of the default 48 — used inside Dialog. */
  compact?: boolean;
  /** 52 instead of the default 48 — used on Splash. */
  tall?: boolean;
  /**
   * The button sits on a full-bleed color.accent field (e.g. Splash):
   * primary fills color.bg with color.text label instead of accent/onAccent;
   * secondary borders color.bg with a color.onAccent label instead of divider/text.
   */
  onAccentBackground?: boolean;
  accessibilityLabel?: string;
};

export function Button({
  variant,
  label,
  onPress,
  disabled,
  loading,
  block,
  compact,
  tall,
  onAccentBackground,
  accessibilityLabel,
}: Props) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const isDisabled = disabled || loading;
  const height = compact ? 44 : tall ? 52 : theme.layout.buttonHeight;

  const variantStyle = onAccentBackground
    ? variant === 'primary'
      ? styles.primaryOnAccent
      : variant === 'secondary'
        ? styles.secondaryOnAccent
        : styles.ghost
    : variant === 'primary'
      ? styles.primary
      : variant === 'secondary'
        ? styles.secondary
        : styles.ghost;

  const labelStyle = onAccentBackground
    ? variant === 'primary'
      ? styles.labelOnBg
      : styles.labelOnAccent
    : variant === 'primary'
      ? styles.labelOnAccent
      : styles.labelOnSurface;

  const spinnerColor = onAccentBackground
    ? variant === 'primary'
      ? theme.color.text
      : theme.color.onAccent
    : variant === 'primary'
      ? theme.color.onAccent
      : theme.color.text;

  return (
    <Pressable
      onPress={isDisabled ? undefined : onPress}
      disabled={isDisabled}
      hitSlop={theme.hitSlop}
      android_ripple={null}
      accessibilityRole="button"
      accessibilityLabel={accessibilityLabel ?? label}
      accessibilityState={{ disabled: isDisabled, busy: loading }}
      style={({ pressed }) => [
        styles.base,
        { height },
        variantStyle,
        block && styles.block,
        pressed && !isDisabled && !onAccentBackground && variant === 'primary' && styles.primaryPressed,
        pressed && !isDisabled && !onAccentBackground && variant !== 'primary' && styles.otherPressed,
        pressed && !isDisabled && onAccentBackground && styles.pressedOnAccent,
        isDisabled && styles.disabled,
      ]}
    >
      {loading ? (
        <View style={styles.loadingRow}>
          <ActivityIndicator color={spinnerColor} />
          <Text style={[styles.label, labelStyle]}>Menyimpan…</Text>
        </View>
      ) : (
        <Text style={[styles.label, labelStyle]} numberOfLines={1}>
          {label}
        </Text>
      )}
    </Pressable>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    base: {
      borderRadius: theme.radius,
      alignItems: 'center',
      justifyContent: 'center',
      paddingHorizontal: theme.space.lg,
    },
    block: {
      width: '100%',
      alignItems: 'flex-start',
      paddingHorizontal: theme.space.lg,
    },
    primary: { backgroundColor: theme.color.accent },
    primaryPressed: { backgroundColor: theme.color.accent700 },
    secondary: { backgroundColor: 'transparent', borderWidth: 1, borderColor: theme.color.divider },
    ghost: { backgroundColor: 'transparent' },
    otherPressed: { backgroundColor: theme.color.surfaceAlt },
    primaryOnAccent: { backgroundColor: theme.color.bg },
    secondaryOnAccent: { backgroundColor: 'transparent', borderWidth: 1, borderColor: theme.color.bg },
    pressedOnAccent: { opacity: 0.85 },
    disabled: { opacity: 0.45 },
    label: { fontFamily: 'Archivo_800ExtraBold', fontSize: 15 },
    labelOnAccent: { color: theme.color.onAccent },
    labelOnSurface: { color: theme.color.text },
    labelOnBg: { color: theme.color.text },
    loadingRow: { flexDirection: 'row', alignItems: 'center', gap: theme.space.sm },
  });
}
