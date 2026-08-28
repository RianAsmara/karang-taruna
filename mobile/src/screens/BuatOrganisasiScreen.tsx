import { router } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, Text, TextInput, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Button } from '@/components/Button';
import { useToast } from '@/components/Toast';
import { ApiError } from '@/lib/api';
import { useAuth } from '@/store/useAuth';
import { useCreateOrganization } from '@/lib/queries';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

/**
 * Minimal, name-only scope — mobile-screens.md §33 specs a 4-step flow
 * (type, location, invite, confirmation) this app can't support yet
 * (no org type/location columns, invite flow still deferred). See the
 * "Buat Organisasi" entry in docs/next-up.md for the full reasoning.
 */
export function BuatOrganisasiScreen() {
  const { theme } = useTheme();
  const insets = useSafeAreaInsets();
  const styles = makeStyles(theme);
  const { showToast } = useToast();
  const logout = useAuth((state) => state.logout);
  const createOrganization = useCreateOrganization();

  const [name, setName] = useState('');
  const [error, setError] = useState<string | null>(null);

  const submit = () => {
    setError(null);
    createOrganization.mutate(name.trim(), {
      onSuccess: () => router.replace('/(app)/home'),
      onError: (err) => {
        setError(err instanceof ApiError ? (err.errors?.name?.[0] ?? err.message) : 'Gagal membuat organisasi.');
      },
    });
  };

  return (
    <View style={[styles.root, { paddingTop: insets.top }]}>
      <View style={styles.content}>
        <Text style={styles.title}>Buat organisasi</Text>
        <Text style={styles.explainer}>Beri nama untuk organisasi Anda. Anda akan menjadi ketua secara otomatis.</Text>

        <View style={styles.field}>
          <Text style={styles.label}>Nama organisasi</Text>
          <View style={styles.box}>
            <TextInput
              style={styles.boxInput}
              value={name}
              onChangeText={setName}
              placeholder="Karang Taruna Melati"
              placeholderTextColor={theme.color.textFaint}
              autoFocus
            />
          </View>
          {error ? <Text style={styles.error}>{error}</Text> : null}
        </View>

        <Button
          variant="primary"
          label="Buat organisasi"
          onPress={submit}
          block
          loading={createOrganization.isPending}
          disabled={!name.trim()}
        />

        <Text
          style={styles.signOut}
          onPress={async () => {
            await logout();
            router.replace('/');
            showToast('Anda keluar dari akun.');
          }}
        >
          Keluar dari akun
        </Text>
      </View>
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { flex: 1, backgroundColor: theme.color.bg },
    content: { flex: 1, justifyContent: 'center', padding: theme.layout.screenPadding, gap: theme.space.lg },
    title: { fontFamily: 'Archivo_800ExtraBold', fontSize: 30, letterSpacing: -0.75, color: theme.color.text },
    explainer: { fontFamily: 'Archivo_400Regular', fontSize: 14, color: theme.color.textMuted },
    field: { gap: theme.space.xs },
    label: { fontFamily: 'Archivo_600SemiBold', fontSize: 11, letterSpacing: 0.7, color: theme.color.textMuted },
    box: { borderWidth: 1, borderColor: theme.color.divider, backgroundColor: theme.color.surface, minHeight: 48, justifyContent: 'center' },
    boxInput: { paddingHorizontal: theme.space.md, fontFamily: 'Archivo_800ExtraBold', fontSize: 15, color: theme.color.text },
    error: { fontFamily: 'Archivo_400Regular', fontSize: 12.5, color: theme.color.accent700 },
    signOut: { fontFamily: 'Archivo_600SemiBold', fontSize: 13, color: theme.color.textMuted, textAlign: 'center', marginTop: theme.space.md },
  });
}
