import { StyleSheet, Text, View } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

// Section 05's prop table lists 24|28|32|48|56 as the common sizes, but
// individual screens specify additional concrete values (Login's detected-
// org avatar is 36, Profil's org card avatar is 44) — kept as plain
// `number` so those per-screen literals aren't blocked.
export type AvatarSize = number;

type Props = {
  size: AvatarSize;
  initials: string;
  /** 'org' fills with accent, for organization marks. Default fills with text. */
  tone?: 'default' | 'org';
};

export function Avatar({ size, initials, tone = 'default' }: Props) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);

  return (
    <View
      style={[
        styles.base,
        { width: size, height: size, backgroundColor: tone === 'org' ? theme.color.accent : theme.color.text },
      ]}
    >
      <Text style={[styles.initials, { fontSize: size * 0.4 }]} numberOfLines={1}>
        {initials}
      </Text>
    </View>
  );
}

type GroupProps = {
  avatars: { initials: string }[];
  size: AvatarSize;
  max?: number;
  /** Real total the group represents, when it's larger than `avatars` itself (e.g. 3 avatars shown + "+5" for an 8-person committee). Defaults to avatars.length. */
  total?: number;
};

export function AvatarGroup({ avatars, size, max = 4, total }: GroupProps) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const shown = avatars.slice(0, max);
  const rest = (total ?? avatars.length) - shown.length;

  return (
    <View style={styles.group}>
      {shown.map((a, i) => (
        <View key={i} style={i > 0 ? styles.groupOverlap : undefined}>
          <Avatar size={size} initials={a.initials} />
        </View>
      ))}
      {rest > 0 ? (
        <View style={styles.groupOverlap}>
          <View style={[styles.base, styles.rest, { width: size, height: size, backgroundColor: theme.color.neutral300 }]}>
            <Text style={[styles.initials, styles.restLabel, { fontSize: size * 0.34 }]}>{`+${rest}`}</Text>
          </View>
        </View>
      ) : null}
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    base: {
      borderRadius: theme.radius,
      alignItems: 'center',
      justifyContent: 'center',
    },
    initials: {
      fontFamily: 'Archivo_800ExtraBold',
      color: theme.color.onInk,
    },
    rest: {},
    restLabel: { color: theme.color.text },
    group: { flexDirection: 'row', alignItems: 'center' },
    groupOverlap: { marginLeft: -theme.space.xs },
  });
}
