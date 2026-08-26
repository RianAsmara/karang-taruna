import { Pressable, StyleSheet, Text, View } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';
import { Tag } from './Tag';

type Props = {
  title: string;
  meta: string;
  done: boolean;
  priority?: boolean;
  onToggle?: () => void;
  isLast?: boolean;
};

export function TaskItem({ title, meta, done, priority, onToggle, isLast }: Props) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);

  return (
    <View style={[styles.row, !isLast && styles.divider]}>
      <Pressable
        onPress={onToggle}
        hitSlop={theme.hitSlop}
        android_ripple={null}
        accessibilityRole="checkbox"
        accessibilityState={{ checked: done }}
        accessibilityLabel={title}
        style={styles.checkboxTouch}
      >
        <View style={[styles.checkbox, done ? styles.checkboxDone : styles.checkboxOpen]}>
          {done ? <Text style={styles.checkmark}>✓</Text> : null}
        </View>
      </Pressable>
      <View style={styles.textCol}>
        <Text style={[styles.title, done && styles.titleDone]} numberOfLines={2}>
          {title}
        </Text>
        <View style={styles.metaRow}>
          <Text style={styles.meta} numberOfLines={1}>
            {meta}
          </Text>
          {priority && !done ? (
            <View style={styles.priorityTag}>
              <Tag tone="accent" label="PENTING" />
            </View>
          ) : null}
        </View>
      </View>
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    row: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      paddingVertical: theme.space.md,
      paddingHorizontal: theme.space.lg,
    },
    divider: { borderBottomWidth: 1, borderBottomColor: theme.color.divider },
    checkboxTouch: { marginRight: theme.space.md, alignItems: 'center', justifyContent: 'center' },
    checkbox: {
      width: 20,
      height: 20,
      borderRadius: theme.radius,
      alignItems: 'center',
      justifyContent: 'center',
    },
    checkboxOpen: { borderWidth: 1.5, borderColor: theme.color.divider },
    checkboxDone: { backgroundColor: theme.color.text },
    checkmark: { color: theme.color.onInk, fontSize: 13, fontFamily: 'Archivo_800ExtraBold' },
    textCol: { flex: 1 },
    title: { fontFamily: 'Archivo_800ExtraBold', fontSize: 14.5, color: theme.color.text },
    titleDone: { opacity: 0.5, textDecorationLine: 'line-through' },
    metaRow: { flexDirection: 'row', alignItems: 'center', marginTop: 3, gap: theme.space.xs },
    meta: { fontFamily: 'Archivo_400Regular', fontSize: 12, color: theme.color.textMuted },
    priorityTag: {},
  });
}
