import { Pressable, StyleSheet, Text, View } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';
import { Avatar, AvatarGroup } from './Avatar';

type ChartPerson = { initials: string; name: string };

type Props = {
  ketua: ChartPerson | null;
  /** Only KETUA is unique per org — Bendahara/Sekretaris can have 0, 1, or several holders. */
  bendahara: ChartPerson[];
  sekretaris: ChartPerson[];
  anggotaCount: number;
  anggotaAvatars: { initials: string }[];
  onPressAnggota?: () => void;
};

/**
 * The org's role hierarchy is flat — Ketua, then Bendahara/Sekretaris,
 * then everyone else — not a real reporting tree, so this is always
 * exactly three tiers. Bendahara/Sekretaris always render as two fixed
 * columns (a "Belum ada" placeholder when vacant) so the branch
 * connector geometry below never has to handle 0/1/2 columns.
 */
export function OrgChart({ ketua, bendahara, sekretaris, anggotaCount, anggotaAvatars, onPressAnggota }: Props) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);

  const renderSoloNode = (person: ChartPerson | null, roleLabel: string, size: number) => (
    <View style={styles.node}>
      {person ? (
        <Avatar size={size} initials={person.initials} />
      ) : (
        <View style={[styles.vacantAvatar, { width: size, height: size }]} />
      )}
      <Text style={styles.nodeRole}>{roleLabel.toUpperCase()}</Text>
      <Text style={person ? styles.nodeName : styles.nodeNameVacant} numberOfLines={1}>
        {person ? person.name : 'Belum ada'}
      </Text>
    </View>
  );

  const renderRoleNode = (holders: ChartPerson[], roleLabel: string, size: number) => {
    if (holders.length === 0) return renderSoloNode(null, roleLabel, size);
    if (holders.length === 1) return renderSoloNode(holders[0]!, roleLabel, size);

    return (
      <View style={styles.node}>
        <AvatarGroup avatars={holders} size={size} max={3} />
        <Text style={styles.nodeRole}>{roleLabel.toUpperCase()}</Text>
        <Text style={styles.nodeName}>{holders.length} orang</Text>
      </View>
    );
  };

  return (
    <View style={styles.root}>
      {renderSoloNode(ketua, 'Ketua', 44)}

      <View style={styles.stub} />

      <View style={styles.branchRow}>
        <View style={styles.branchCellLeft}>
          <View style={styles.branchSpacer} />
          <View style={styles.branchLine} />
        </View>
        <View style={styles.branchCellRight}>
          <View style={styles.branchLine} />
          <View style={styles.branchSpacer} />
        </View>
      </View>

      <View style={styles.stubRow}>
        <View style={styles.col}>
          <View style={styles.stub} />
        </View>
        <View style={styles.col}>
          <View style={styles.stub} />
        </View>
      </View>

      <View style={styles.stubRow}>
        <View style={styles.col}>{renderRoleNode(bendahara, 'Bendahara', 36)}</View>
        <View style={styles.col}>{renderRoleNode(sekretaris, 'Sekretaris', 36)}</View>
      </View>

      <View style={styles.stub} />

      <Pressable
        onPress={onPressAnggota}
        hitSlop={theme.hitSlop}
        android_ripple={null}
        accessibilityRole={onPressAnggota ? 'button' : undefined}
        style={({ pressed }) => [styles.anggotaNode, pressed && onPressAnggota ? styles.pressed : null]}
      >
        {anggotaAvatars.length > 0 ? (
          <AvatarGroup avatars={anggotaAvatars} size={28} max={5} total={anggotaCount} />
        ) : null}
        <Text style={styles.nodeRole}>ANGGOTA</Text>
        <Text style={styles.nodeName}>{anggotaCount} orang</Text>
      </Pressable>
    </View>
  );
}

const STUB_HEIGHT = 12;

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { width: '100%', alignItems: 'center', paddingHorizontal: theme.layout.screenPadding, paddingVertical: theme.space.md },
    stub: { width: 2, height: STUB_HEIGHT, backgroundColor: theme.color.rule },
    branchRow: { flexDirection: 'row', width: '100%' },
    branchCellLeft: { flex: 1, flexDirection: 'row' },
    branchCellRight: { flex: 1, flexDirection: 'row' },
    branchSpacer: { flex: 1 },
    branchLine: { flex: 1, height: 2, backgroundColor: theme.color.rule },
    stubRow: { flexDirection: 'row', width: '100%' },
    col: { flex: 1, alignItems: 'center' },
    node: { alignItems: 'center', gap: 2, maxWidth: '90%' },
    vacantAvatar: { borderWidth: 2, borderColor: theme.color.divider },
    nodeRole: { fontFamily: 'Archivo_800ExtraBold', fontSize: 10, letterSpacing: 0.8, color: theme.color.textFaint, marginTop: theme.space.xs },
    nodeName: { fontFamily: 'Archivo_600SemiBold', fontSize: 13, color: theme.color.text },
    nodeNameVacant: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textFaint },
    anggotaNode: { alignItems: 'center', gap: 2 },
    pressed: { opacity: 0.6 },
  });
}
