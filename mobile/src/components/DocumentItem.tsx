import { Pressable, StyleSheet, Text, View } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

type Props = {
  title: string;
  category: string;
  uploader: string;
  date: string;
  sizeLabel: string;
  /** 2–4 letter type mark, e.g. "PDF", "DOC", "JPG" — no file-type icons, no per-type colors. */
  kind: string;
  onPress?: () => void;
  isLast?: boolean;
};

/** The type mark is a documented pattern, not a generic leading slot — see mobile-components.md § New in this phase. */
export function DocumentItem({ title, category, uploader, date, sizeLabel, kind, onPress, isLast }: Props) {
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
      <View style={styles.leading}>
        <Text style={styles.kind} numberOfLines={1}>
          {kind.slice(0, 4).toUpperCase()}
        </Text>
      </View>
      <View style={styles.textCol}>
        <Text style={styles.title} numberOfLines={1}>
          {title}
        </Text>
        <Text style={styles.meta} numberOfLines={1}>
          {category} · {uploader} · {date} · {sizeLabel}
        </Text>
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
    leading: {
      width: 28,
      height: 28,
      borderWidth: 1,
      borderColor: theme.color.divider,
      alignItems: 'center',
      justifyContent: 'center',
      marginRight: theme.space.md,
    },
    kind: { fontFamily: 'Archivo_800ExtraBold', fontSize: 9, letterSpacing: 0.3, color: theme.color.text },
    textCol: { flex: 1 },
    title: { fontFamily: 'Archivo_800ExtraBold', fontSize: 14, color: theme.color.text },
    meta: { fontFamily: 'Archivo_400Regular', fontSize: 12, color: theme.color.textMuted, marginTop: 2 },
  });
}
