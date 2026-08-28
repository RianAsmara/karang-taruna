import { router } from 'expo-router';
import { ScrollView, StyleSheet, Text, View } from 'react-native';

import { useToast } from '@/components/Toast';
import { ApiError } from '@/lib/api';
import { useMyOrganizations, useSwitchOrganization } from '@/lib/queries';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';
import { BottomSheet } from './BottomSheet';
import { ListItem } from './ListItem';
import { Tag } from './Tag';

export function OrgSwitcherSheet({ visible, currentOrganizationId, onClose }: { visible: boolean; currentOrganizationId?: string; onClose: () => void }) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const { showToast } = useToast();
  const organizations = useMyOrganizations();
  const switchOrganization = useSwitchOrganization();

  const list = organizations.data?.organizations ?? [];

  const pick = (organizationId: string) => {
    if (organizationId === currentOrganizationId) {
      onClose();
      return;
    }

    switchOrganization.mutate(organizationId, {
      onSuccess: () => {
        onClose();
        router.replace('/(app)/home');
      },
      onError: (err) => {
        showToast(err instanceof ApiError ? err.message : 'Gagal berpindah organisasi.');
      },
    });
  };

  return (
    <BottomSheet visible={visible} title="Ganti organisasi" onClose={onClose}>
      {list.length <= 1 ? (
        <Text style={styles.empty}>Anda hanya tergabung di satu organisasi.</Text>
      ) : (
        <ScrollView style={styles.scroll}>
          {list.map((org, i) => (
            <ListItem
              key={org.id}
              title={org.name}
              subtitle={org.roleLabel}
              trailing={org.id === currentOrganizationId ? <Tag tone="accent" label="Aktif" /> : undefined}
              onPress={() => pick(org.id)}
              isLast={i === list.length - 1}
            />
          ))}
        </ScrollView>
      )}
      <View style={styles.createRow}>
        <Text
          style={styles.createLink}
          onPress={() => {
            onClose();
            router.push('/buat-organisasi');
          }}
        >
          + Buat organisasi baru
        </Text>
      </View>
    </BottomSheet>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    empty: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted, paddingHorizontal: theme.layout.screenPadding, paddingVertical: theme.space.md },
    scroll: { maxHeight: 320 },
    createRow: { padding: theme.layout.screenPadding, borderTopWidth: 1, borderTopColor: theme.color.divider },
    createLink: { fontFamily: 'Archivo_800ExtraBold', fontSize: 13, color: theme.color.accent },
  });
}
