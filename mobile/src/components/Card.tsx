import { Pressable, StyleSheet, View, type ViewStyle } from 'react-native';
import type { ReactNode } from 'react';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

type Props = {
  children: ReactNode;
  onPress?: () => void;
  inverted?: boolean;
  style?: ViewStyle;
};

export function Card({ children, onPress, inverted, style }: Props) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);

  const content = (
    <View style={[styles.base, inverted ? styles.inverted : styles.normal, style]}>{children}</View>
  );

  if (!onPress) return content;

  return (
    <Pressable
      onPress={onPress}
      hitSlop={theme.hitSlop}
      android_ripple={null}
      style={({ pressed }) => [pressed && !inverted && styles.pressed]}
    >
      {content}
    </Pressable>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    base: { borderRadius: theme.radius },
    normal: { backgroundColor: theme.color.surface, marginHorizontal: theme.space.lg },
    inverted: { backgroundColor: theme.color.accent, width: '100%' },
    pressed: { backgroundColor: theme.color.surfaceAlt },
  });
}
