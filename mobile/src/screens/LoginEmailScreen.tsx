import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Button } from '@/components/Button';
import { useToast } from '@/components/Toast';
import { ApiError } from '@/lib/api';
import { useAuth } from '@/store/useAuth';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

/**
 * Temporary E2E-testing login: the design's WhatsApp+OTP flow
 * (`LoginScreen.tsx`) has no backing endpoint yet (see
 * docs/mobile-e2e-backlog.md §1) — this screen calls the real
 * `POST /api/v1/auth/login` with email + password instead. Kept as a
 * separate file so the design-verified OTP screen stays intact and
 * unwired.
 */
export function LoginEmailScreen() {
  const { theme } = useTheme();
  const insets = useSafeAreaInsets();
  const styles = makeStyles(theme);
  const { showToast } = useToast();
  const login = useAuth((state) => state.login);

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const submit = async () => {
    setError(null);
    setSubmitting(true);
    try {
      await login(email.trim(), password);
      router.replace('/(app)/home');
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.errors?.email?.[0] ?? err.message);
      } else {
        showToast('Tidak bisa terhubung ke server.');
      }
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <View style={styles.root}>
      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
        <Pressable
          onPress={() => router.back()}
          hitSlop={theme.hitSlop}
          android_ripple={null}
          style={[styles.back, { marginTop: insets.top }]}
        >
          <Text style={styles.backLabel}>← Kembali</Text>
        </Pressable>

        <Text style={styles.title}>Masuk</Text>
        <Text style={styles.explainer}>Masuk dengan email dan kata sandi akun RukunMuda Anda.</Text>

        <View style={styles.fields}>
          <View style={styles.field}>
            <Text style={styles.label}>Email</Text>
            <View style={styles.box}>
              <TextInput
                style={styles.boxInput}
                value={email}
                onChangeText={setEmail}
                keyboardType="email-address"
                autoCapitalize="none"
                autoCorrect={false}
                placeholder="nama@email.com"
                placeholderTextColor={theme.color.textFaint}
              />
            </View>
          </View>

          <View style={styles.field}>
            <Text style={styles.label}>Kata sandi</Text>
            <View style={[styles.box, styles.passwordBox]}>
              <TextInput
                style={[styles.boxInput, styles.passwordInput]}
                value={password}
                onChangeText={setPassword}
                secureTextEntry={!showPassword}
                placeholder="••••••••"
                placeholderTextColor={theme.color.textFaint}
              />
              <Pressable
                onPress={() => setShowPassword((v) => !v)}
                hitSlop={theme.hitSlop}
                android_ripple={null}
                accessibilityRole="button"
                accessibilityLabel={showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
              >
                <Text style={styles.toggle}>{showPassword ? 'Sembunyikan' : 'Tampilkan'}</Text>
              </Pressable>
            </View>
          </View>

          {error ? <Text style={styles.error}>{error}</Text> : null}
        </View>

        <View style={styles.primaryAction}>
          <Button
            variant="primary"
            label="Masuk"
            onPress={submit}
            block
            loading={submitting}
            disabled={!email || !password}
          />
        </View>
      </ScrollView>
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { flex: 1, backgroundColor: theme.color.bg },
    scroll: { padding: theme.layout.screenPadding, flexGrow: 1 },
    back: { paddingTop: theme.space.xl, alignSelf: 'flex-start' },
    backLabel: { fontFamily: 'Archivo_800ExtraBold', fontSize: 15, color: theme.color.text },
    title: { fontFamily: 'Archivo_800ExtraBold', fontSize: 34, letterSpacing: -0.8, color: theme.color.text, marginTop: theme.space.lg },
    explainer: { fontFamily: 'Archivo_400Regular', fontSize: 14, color: theme.color.textMuted, marginTop: theme.space.md, maxWidth: '85%' },
    fields: { marginTop: theme.space.xl, gap: theme.space.lg },
    field: { gap: theme.space.xs },
    label: { fontFamily: 'Archivo_600SemiBold', fontSize: 11, letterSpacing: 0.7, color: theme.color.textMuted },
    box: { borderWidth: 1, borderColor: theme.color.divider, backgroundColor: theme.color.surface, minHeight: 48, justifyContent: 'center' },
    boxInput: { paddingHorizontal: theme.space.md, fontFamily: 'Archivo_800ExtraBold', fontSize: 15, color: theme.color.text },
    passwordBox: { flexDirection: 'row', alignItems: 'center' },
    passwordInput: { flex: 1 },
    toggle: { fontFamily: 'Archivo_800ExtraBold', fontSize: 12, color: theme.color.accent, paddingHorizontal: theme.space.md },
    error: { fontFamily: 'Archivo_400Regular', fontSize: 12.5, color: theme.color.accent700 },
    primaryAction: { marginTop: theme.space.xl },
  });
}
