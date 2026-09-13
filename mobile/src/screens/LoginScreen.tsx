import { router } from 'expo-router';
import { useRef, useState } from 'react';
import { Pressable, StyleSheet, Text, TextInput, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { KeyboardAwareScroll } from '@/components/KeyboardAwareScroll';
import { Avatar } from '@/components/Avatar';
import { Button } from '@/components/Button';
import { organization } from '@/data/mock';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

const OTP_LENGTH = 6;

export function LoginScreen() {
  const { theme } = useTheme();
  const insets = useSafeAreaInsets();
  const styles = makeStyles(theme);
  const [step, setStep] = useState<'phone' | 'otp'>('phone');
  const [phone, setPhone] = useState('');
  const [orgCode, setOrgCode] = useState('');
  const [phoneError, setPhoneError] = useState<string | null>(null);
  const [otp, setOtp] = useState(Array(OTP_LENGTH).fill(''));
  const otpRefs = useRef<(TextInput | null)[]>([]);

  const validatePhone = () => {
    if (phone.trim().length < 8) {
      setPhoneError('Nomor WhatsApp belum lengkap.');
      return false;
    }
    setPhoneError(null);
    return true;
  };

  const submitPhone = () => {
    if (validatePhone()) setStep('otp');
  };

  const setDigit = (index: number, value: string) => {
    const digit = value.replace(/[^0-9]/g, '').slice(-1);
    const next = otp.map((d, i) => (i === index ? digit : d));
    setOtp(next);

    if (digit) {
      if (index < OTP_LENGTH - 1) {
        otpRefs.current[index + 1]?.focus();
      }
      if (next.every(Boolean)) {
        router.replace('/home');
      }
    }
  };

  const handleOtpKeyPress = (index: number, key: string) => {
    if (key === 'Backspace' && !otp[index] && index > 0) {
      otpRefs.current[index - 1]?.focus();
    }
  };

  return (
    <View style={styles.root}>
      <KeyboardAwareScroll contentContainerStyle={styles.scroll}>
        <Pressable
          onPress={() => (step === 'otp' ? setStep('phone') : router.back())}
          hitSlop={theme.hitSlop}
          android_ripple={null}
          style={[styles.back, { marginTop: insets.top }]}
        >
          <Text style={styles.backLabel}>← Kembali</Text>
        </Pressable>

        {/* The OTP step "reuses this screen" (screens/02-Login.md) — same
            title/explainer copy, only the field area changes. No OTP-specific
            copy is given anywhere in the docs, so none is invented here. */}
        <Text style={styles.title}>Masuk pakai nomor HP</Text>
        <Text style={styles.explainer}>Kami kirim kode 6 angka lewat WhatsApp. Tidak perlu kata sandi.</Text>

        {step === 'phone' ? (
          <>
            <View style={styles.fields}>
              <View style={styles.field}>
                <Text style={styles.label}>Nomor WhatsApp</Text>
                <View style={styles.phoneBox}>
                  <View style={styles.phonePrefix}>
                    <Text style={styles.phonePrefixText}>+62</Text>
                  </View>
                  <TextInput
                    style={styles.phoneInput}
                    value={phone}
                    onChangeText={setPhone}
                    onBlur={validatePhone}
                    keyboardType="number-pad"
                    placeholder="812 3456 7890"
                    placeholderTextColor={theme.color.textFaint}
                  />
                </View>
                {phoneError ? <Text style={styles.error}>{phoneError}</Text> : null}
              </View>

              <View style={styles.field}>
                <Text style={styles.label}>Kode organisasi (opsional)</Text>
                <View style={styles.box}>
                  <TextInput
                    style={styles.boxInput}
                    value={orgCode}
                    onChangeText={setOrgCode}
                    placeholder="Contoh: PKB-2026"
                    placeholderTextColor={theme.color.textFaint}
                    autoCapitalize="characters"
                  />
                </View>
              </View>
            </View>

            <View style={styles.primaryAction}>
              <Button variant="primary" label="Kirim kode ke WhatsApp" onPress={submitPhone} block />
            </View>

            <Text style={styles.privacy}>
              Dengan masuk, Anda setuju pada aturan komunitas RukunMuda. Nomor Anda tidak dibagikan ke anggota lain.
            </Text>
          </>
        ) : (
          <>
            <View style={styles.otpRow}>
              {otp.map((digit, i) => (
                <TextInput
                  key={i}
                  ref={(el) => {
                    otpRefs.current[i] = el;
                  }}
                  style={styles.otpBox}
                  value={digit}
                  onChangeText={(v) => setDigit(i, v)}
                  onKeyPress={({ nativeEvent }) => handleOtpKeyPress(i, nativeEvent.key)}
                  keyboardType="number-pad"
                  maxLength={1}
                  autoFocus={i === 0}
                />
              ))}
            </View>
          </>
        )}
      </KeyboardAwareScroll>

      <View style={styles.detectedOrg}>
        <View style={styles.detectedOrgRule} />
        <View style={styles.detectedOrgRow}>
          <Avatar size={36} initials={organization.initials} />
          <View>
            <Text style={styles.detectedOrgName}>{organization.name}</Text>
            <Text style={styles.detectedOrgMeta}>
              {organization.region} · {organization.kelurahan} · {organization.memberCount} anggota
            </Text>
          </View>
        </View>
      </View>
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { flex: 1, backgroundColor: theme.color.bg },
    scroll: { padding: theme.layout.screenPadding, flexGrow: 1 },
    back: { paddingTop: theme.space.xl, alignSelf: 'flex-start' },
    backLabel: { fontFamily: 'Archivo_800ExtraBold', fontSize: 15, color: theme.color.text },
    title: { fontFamily: 'Archivo_800ExtraBold', fontSize: 34, letterSpacing: -0.8, color: theme.color.text, marginTop: theme.space.lg, maxWidth: '80%' },
    explainer: { fontFamily: 'Archivo_400Regular', fontSize: 14, color: theme.color.textMuted, marginTop: theme.space.md, maxWidth: '85%' },
    fields: { marginTop: theme.space.xl, gap: theme.space.lg },
    field: { gap: theme.space.xs },
    label: { fontFamily: 'Archivo_600SemiBold', fontSize: 11, letterSpacing: 0.7, color: theme.color.textMuted },
    phoneBox: {
      flexDirection: 'row',
      borderWidth: 1,
      borderColor: theme.color.divider,
      backgroundColor: theme.color.surface,
      minHeight: 48,
      alignItems: 'center',
    },
    phonePrefix: { paddingHorizontal: theme.space.md, borderRightWidth: 1, borderRightColor: theme.color.divider, height: '100%', justifyContent: 'center' },
    phonePrefixText: { fontFamily: 'Archivo_800ExtraBold', fontSize: 15, color: theme.color.text },
    phoneInput: { flex: 1, paddingHorizontal: theme.space.md, fontFamily: 'Archivo_800ExtraBold', fontSize: 15, color: theme.color.text },
    box: { borderWidth: 1, borderColor: theme.color.divider, backgroundColor: theme.color.surface, minHeight: 48, justifyContent: 'center' },
    boxInput: { paddingHorizontal: theme.space.md, fontFamily: 'Archivo_800ExtraBold', fontSize: 15, color: theme.color.text },
    error: { fontFamily: 'Archivo_400Regular', fontSize: 12.5, color: theme.color.accent700 },
    primaryAction: { marginTop: theme.space.xl },
    privacy: { fontFamily: 'Archivo_400Regular', fontSize: 12.5, color: theme.color.textMuted, marginTop: theme.space.lg },
    otpRow: { flexDirection: 'row', gap: theme.space.sm, marginTop: theme.space.xl },
    otpBox: {
      width: 48,
      height: 56,
      borderWidth: 1,
      borderColor: theme.color.divider,
      textAlign: 'center',
      fontFamily: 'Archivo_800ExtraBold',
      fontSize: 20,
      color: theme.color.text,
    },
    detectedOrg: {},
    detectedOrgRule: { height: 2, backgroundColor: theme.color.rule },
    detectedOrgRow: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: theme.space.md,
      padding: theme.layout.screenPadding,
    },
    detectedOrgName: { fontFamily: 'Archivo_800ExtraBold', fontSize: 14, color: theme.color.text },
    detectedOrgMeta: { fontFamily: 'Archivo_400Regular', fontSize: 12, color: theme.color.textMuted, marginTop: 2 },
  });
}
