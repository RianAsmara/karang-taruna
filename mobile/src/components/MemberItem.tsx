import { Pressable, StyleSheet, Text, View } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';
import { DUES_TAG, ROLE_TONE, type DuesStatus } from '@/theme/vocab';
import { Avatar } from './Avatar';
import { Tag } from './Tag';

type Props = {
  initials: string;
  name: string;
  roles: string[];
  meta?: string;
  duesStatus?: DuesStatus;
  /** Shows a muted-fill "Keluar" tag alongside the role tags — screen 12 edge case: a departed member stays visible for 30 days. */
  hasLeft?: boolean;
  onPress?: () => void;
  isLast?: boolean;
};

/** ListItem geometry doesn't have room for two role tags plus a trailing status — see mobile-components.md § New in this phase. */
export function MemberItem({ initials, name, roles, meta, duesStatus, hasLeft, onPress, isLast }: Props) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const shown = roles.slice(0, 2);
  const overflow = roles.length - shown.length;
  const dues = duesStatus ? DUES_TAG[duesStatus] : null;

  return (
    <Pressable
      onPress={onPress}
      hitSlop={theme.hitSlop}
      android_ripple={null}
      accessibilityRole={onPress ? 'button' : undefined}
      style={({ pressed }) => [styles.row, !isLast && styles.divider, pressed && onPress && styles.pressed]}
    >
      <View style={styles.leading}>
        <Avatar size={32} initials={initials} />
      </View>
      <View style={styles.textCol}>
        <Text style={styles.name} numberOfLines={1}>
          {name}
        </Text>
        {meta ? (
          <Text style={styles.meta} numberOfLines={1}>
            {meta}
          </Text>
        ) : null}
        {shown.length > 0 || hasLeft ? (
          <View style={styles.tagRow}>
            {shown.map((role) => (
              <Tag key={role} tone={ROLE_TONE[role] ?? 'outline'} label={role.toUpperCase()} />
            ))}
            {overflow > 0 ? <Tag tone="outline" label={`+${overflow}`} /> : null}
            {hasLeft ? <Tag tone="outline" label="KELUAR" mutedFill /> : null}
          </View>
        ) : null}
      </View>
      {dues ? (
        <View style={styles.trailing}>
          <Tag tone={dues.tone} label={dues.label} mutedFill={dues.mutedFill} />
        </View>
      ) : null}
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
    name: { fontFamily: 'Archivo_800ExtraBold', fontSize: 15, color: theme.color.text },
    meta: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted, marginTop: 2 },
    tagRow: { flexDirection: 'row', gap: theme.space.xs, marginTop: theme.space.xs, flexWrap: 'wrap' },
    trailing: { marginLeft: theme.space.md },
  });
}
