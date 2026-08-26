import { Pressable, StyleSheet, Text, View } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';
import { AvatarGroup } from './Avatar';
import { Progress } from './Progress';
import { Tag } from './Tag';

export type EventItemState = 'scheduled' | 'planned' | 'blocked';

type Props = {
  day: string;
  date: string;
  month: string;
  title: string;
  time?: string;
  place?: string;
  progress?: { value: number; total: number; label: string };
  people?: { initials: string }[];
  peopleCaption?: string;
  state: EventItemState;
  onPress?: () => void;
};

export function EventItem({ day, date, month, title, time, place, progress, people, peopleCaption, state, onPress }: Props) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const upcoming = state === 'scheduled';

  return (
    <Pressable
      onPress={onPress}
      hitSlop={theme.hitSlop}
      android_ripple={null}
      accessibilityRole={onPress ? 'button' : undefined}
      style={({ pressed }) => [styles.row, pressed && onPress ? styles.pressed : undefined]}
    >
      <View style={[styles.dateBlock, { borderRightColor: upcoming ? theme.color.accent : theme.color.divider }]}>
        <Text style={[styles.day, { color: upcoming ? theme.color.accent : theme.color.textMuted }]}>{day}</Text>
        <Text style={[styles.date, { color: upcoming ? theme.color.text : theme.color.textMuted }]}>{date}</Text>
        <Text style={[styles.month, { color: upcoming ? theme.color.text : theme.color.textMuted }]}>{month}</Text>
      </View>
      <View style={styles.body}>
        <Text style={styles.title} numberOfLines={2}>
          {title}
        </Text>
        {time || place ? (
          <Text style={styles.meta} numberOfLines={1}>
            {[time, place].filter(Boolean).join(' · ')}
          </Text>
        ) : null}
        {state === 'planned' ? (
          <View style={styles.tagRow}>
            <Tag tone="outline" label="PERENCANAAN" />
          </View>
        ) : null}
        {state === 'blocked' ? (
          <View style={styles.tagRow}>
            <Tag tone="tint" label="◷ MENUNGGU ANGGARAN" />
          </View>
        ) : null}
        {progress ? (
          <View style={styles.progressRow}>
            <Progress variant="bar" value={progress.value} total={progress.total} label={progress.label} compact />
          </View>
        ) : null}
        {people && people.length > 0 ? (
          <View style={styles.peopleRow}>
            <AvatarGroup avatars={people} size={24} max={4} />
            {peopleCaption ? <Text style={styles.peopleCaption}>{peopleCaption}</Text> : null}
          </View>
        ) : null}
      </View>
    </Pressable>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    row: { flexDirection: 'row' },
    pressed: { backgroundColor: theme.color.surfaceAlt },
    dateBlock: {
      width: 48,
      borderRightWidth: 2,
      alignItems: 'center',
      paddingRight: theme.space.sm,
      paddingVertical: theme.space.xs,
    },
    day: { fontFamily: 'Archivo_800ExtraBold', fontSize: 10, textTransform: 'uppercase' },
    date: { fontFamily: 'Archivo_800ExtraBold', fontSize: 26, lineHeight: 28 },
    month: { fontFamily: 'Archivo_600SemiBold', fontSize: 11 },
    body: { flex: 1, paddingLeft: theme.space.md, gap: 4 },
    title: { fontFamily: 'Archivo_800ExtraBold', fontSize: 15, color: theme.color.text },
    meta: { fontFamily: 'Archivo_400Regular', fontSize: 12.5, color: theme.color.textMuted },
    tagRow: { marginTop: 2 },
    progressRow: { marginTop: 4 },
    peopleRow: { marginTop: 4, flexDirection: 'row', alignItems: 'center', gap: 8 },
    peopleCaption: { fontFamily: 'Archivo_400Regular', fontSize: 12, color: theme.color.textMuted },
  });
}
