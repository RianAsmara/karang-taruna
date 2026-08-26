import type { ReactNode } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

type Props = {
  title: string;
  /** 'lg' = 19/800, used by the four tab-root screens. 'sm' = 14/800, used by pushed/detail screens. */
  size?: 'lg' | 'sm';
  onBack?: () => void;
  /** Full label incl. glyph, e.g. "← Kembali". Defaults to a bare "←" with accessibilityLabel="Kembali". */
  backLabel?: string;
  right?: ReactNode;
};

export function ScreenHeader({ title, size = 'sm', onBack, backLabel, right }: Props) {
  const { theme } = useTheme();
  const insets = useSafeAreaInsets();
  const styles = makeStyles(theme);

  return (
    <View style={[styles.row, { height: theme.layout.headerHeight + insets.top, paddingTop: insets.top }]}>
      <View style={styles.left}>
        {onBack ? (
          <Pressable
            onPress={onBack}
            hitSlop={theme.hitSlop}
            android_ripple={null}
            accessibilityRole="button"
            accessibilityLabel="Kembali"
          >
            <Text style={styles.back}>{backLabel ?? '←'}</Text>
          </Pressable>
        ) : null}
        <Text style={[styles.title, size === 'lg' ? styles.titleLg : styles.titleSm]} numberOfLines={1}>
          {title}
        </Text>
      </View>
      {right ? <View style={styles.right}>{right}</View> : null}
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    row: {
      borderBottomWidth: 2,
      borderBottomColor: theme.color.rule,
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      paddingHorizontal: theme.layout.screenPadding,
    },
    left: { flexDirection: 'row', alignItems: 'center', gap: theme.space.md, flexShrink: 1 },
    back: { fontFamily: 'Archivo_800ExtraBold', fontSize: 15, color: theme.color.text },
    title: { fontFamily: 'Archivo_800ExtraBold', color: theme.color.text },
    titleLg: { fontSize: 19 },
    titleSm: { fontSize: 14 },
    right: { flexDirection: 'row', alignItems: 'center', gap: theme.space.md },
  });
}
