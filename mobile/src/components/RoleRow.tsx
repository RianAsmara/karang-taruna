import { Pressable, StyleSheet, Text, View } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';
import { ROLE_TONE } from '@/theme/vocab';
import { Avatar } from './Avatar';
import { Tag } from './Tag';

export type RoleHolder = { initials: string };

type Props = {
  role: string;
  description: string;
  holders: RoleHolder[];
  onPress?: () => void;
  editable: boolean;
  isLast?: boolean;
};

/** Used only on 14 Peran & Izin — needs an avatar stack and a plain-language description, not a list row. */
export function RoleRow({ role, description, holders, onPress, editable, isLast }: Props) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);

  return (
    <Pressable
      onPress={editable ? onPress : undefined}
      hitSlop={theme.hitSlop}
      android_ripple={null}
      accessibilityRole={editable && onPress ? 'button' : undefined}
      style={({ pressed }) => [styles.row, !isLast && styles.divider, pressed && editable && onPress && styles.pressed]}
    >
      <View style={styles.headRow}>
        <Text style={styles.role}>{role}</Text>
        <Tag tone={ROLE_TONE[role] ?? 'outline'} label={role.toUpperCase()} />
      </View>
      <Text style={styles.description}>{description}</Text>
      {holders.length > 0 ? (
        <View style={styles.holderRow}>
          {holders.map((h, i) => (
            <View key={i} style={i > 0 ? styles.holderOverlap : undefined}>
              <Avatar size={24} initials={h.initials} />
            </View>
          ))}
        </View>
      ) : (
        <Text style={styles.empty}>Belum ada</Text>
      )}
    </Pressable>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    row: {
      paddingVertical: theme.space.md,
      paddingHorizontal: theme.layout.screenPadding,
      gap: theme.space.xs,
    },
    divider: { borderBottomWidth: 1, borderBottomColor: theme.color.divider },
    pressed: { backgroundColor: theme.color.surfaceAlt },
    headRow: { flexDirection: 'row', alignItems: 'center', gap: theme.space.sm },
    role: { fontFamily: 'Archivo_800ExtraBold', fontSize: 15, color: theme.color.text },
    description: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted },
    holderRow: { flexDirection: 'row', alignItems: 'center', marginTop: theme.space.xs },
    holderOverlap: { marginLeft: -theme.space.xs },
    empty: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textFaint, marginTop: theme.space.xs },
  });
}
