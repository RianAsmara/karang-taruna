import { router } from 'expo-router';
import { StyleSheet, View } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import { EmptyState } from './EmptyState';
import { ScreenHeader } from './ScreenHeader';

/**
 * Shared stub for the controls in the 11 designed screens that link to a
 * destination outside the 11-screen scope (Profil's org menu rows,
 * "+ Buat kegiatan baru", "Buat organisasi baru", IuranSaya). The header
 * always names the real destination so navigation reads honestly; the
 * body is per-destination so the tap still teaches something. Never a
 * fabricated version of the real, not-yet-designed screen.
 */
export function BelumTersedia({ destination, body }: { destination: string; body: string }) {
  const { theme } = useTheme();

  return (
    <View style={{ flex: 1, backgroundColor: theme.color.bg }}>
      <ScreenHeader title={destination} onBack={() => router.back()} />
      <View style={styles.body}>
        <EmptyState title="Bagian ini belum tersedia" body={body} actionLabel="Kembali" onAction={() => router.back()} />
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  body: { paddingTop: 8 },
});
