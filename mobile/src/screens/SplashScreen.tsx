import { Redirect, router } from 'expo-router';
import { StyleSheet, Text, View } from 'react-native';

import { Button } from '@/components/Button';
import { useAuth } from '@/store/useAuth';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

export function SplashScreen() {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const authStatus = useAuth((state) => state.status);

  if (authStatus === 'signedIn') {
    return <Redirect href="/(app)/home" />;
  }

  return (
    <View style={styles.root}>
      <View style={styles.center}>
        <View style={styles.mark} />
        <Text style={styles.wordmark}>Rukun{'\n'}Muda</Text>
        <View style={styles.rule} />
        <Text style={styles.promise}>Urus kegiatan dan kas kampung dengan rapi, transparan, dan mudah.</Text>
      </View>

      <View style={styles.buttons}>
        <Button
          variant="primary"
          onAccentBackground
          tall
          label="Masuk ke organisasi saya"
          onPress={() => router.push('/login')}
          block
        />
        <Button
          variant="secondary"
          onAccentBackground
          tall
          label="Buat organisasi baru"
          onPress={() => router.push('/register')}
          block
        />
        <Text style={styles.footnote}>Gratis untuk Karang Taruna dan pemuda kampung.</Text>
      </View>
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { flex: 1, backgroundColor: theme.color.accent, padding: theme.space.xxl, paddingHorizontal: theme.space.xl },
    center: { flex: 1, justifyContent: 'center' },
    mark: { width: 44, height: 44, backgroundColor: theme.color.bg, marginBottom: 28 },
    wordmark: { fontFamily: 'Archivo_800ExtraBold', fontSize: 40, lineHeight: 42, letterSpacing: -1.2, color: theme.color.onAccent },
    rule: { height: 2, width: 64, backgroundColor: theme.color.bg, marginVertical: theme.space.xl },
    promise: { fontFamily: 'Archivo_400Regular', fontSize: 17, lineHeight: 24.5, color: theme.color.onAccent, maxWidth: '75%' },
    buttons: { gap: 10 },
    footnote: { fontFamily: 'Archivo_400Regular', fontSize: 12, color: theme.color.onAccent, opacity: 0.8, marginTop: 6 },
  });
}
