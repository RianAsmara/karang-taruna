import { Pressable, StyleSheet, Text, View } from 'react-native';
import type { ReactNode } from 'react';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

type Props = {
  title: string;
  subtitle?: string;
  leading?: ReactNode;
  trailing?: ReactNode;
  onPress?: () => void;
  isLast?: boolean;
  /** 'accent' — used by destructive rows like "Keluar dari organisasi". */
  titleTone?: 'default' | 'accent';
};

export function ListItem({ title, subtitle, leading, trailing, onPress, isLast, titleTone = 'default' }: Props) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);

  return (
    <Pressable
      onPress={onPress}
      hitSlop={theme.hitSlop}
      android_ripple={null}
      accessibilityRole={onPress ? 'button' : undefined}
      style={({ pressed }) => [styles.row, !isLast && styles.divider, pressed && onPress && styles.pressed]}
    >
      {leading ? <View style={styles.leading}>{leading}</View> : null}
      <View style={styles.textCol}>
        <Text style={[styles.title, titleTone === 'accent' && styles.titleAccent]} numberOfLines={1}>
          {title}
        </Text>
        {subtitle ? (
          <Text style={styles.subtitle} numberOfLines={1}>
            {subtitle}
          </Text>
        ) : null}
      </View>
      {trailing ? <View style={styles.trailing}>{trailing}</View> : null}
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
    leading: { marginRight: theme.space.md },
    textCol: { flex: 1 },
    // 14/800 per screen 09 (Notifikasi "Kabar lain"), the one screen that specifies ListItem type explicitly.
    title: { fontFamily: 'Archivo_800ExtraBold', fontSize: 14, color: theme.color.text },
    titleAccent: { color: theme.color.accent },
    subtitle: { fontFamily: 'Archivo_400Regular', fontSize: 12, color: theme.color.textMuted, marginTop: 2 },
    trailing: { marginLeft: theme.space.md },
  });
}
