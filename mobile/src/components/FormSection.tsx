import type { ReactNode } from 'react';
import { Pressable, StyleSheet, Text, TextInput, View, type KeyboardTypeOptions } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';
import { SectionHeader } from './SectionHeader';

type SectionProps = {
  title: string;
  hint?: string;
  children: ReactNode;
};

/** Groups fields consistently across the four new forms — see mobile-components.md § New in this phase. */
export function FormSection({ title, hint, children }: SectionProps) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);

  return (
    <View>
      <SectionHeader title={title} />
      {hint ? <Text style={styles.hint}>{hint}</Text> : null}
      <View style={styles.fields}>{children}</View>
      <View style={styles.rule} />
    </View>
  );
}

type FieldProps = {
  label: string;
  value: string;
  onChangeText: (value: string) => void;
  placeholder?: string;
  keyboardType?: KeyboardTypeOptions;
  multiline?: boolean;
  numberOfLines?: number;
  error?: string;
  autoFocus?: boolean;
};

/** The labeled text input FormSection's fields are built from. `SearchField` below is a documented variation of this, not a separate component. */
export function FormField({ label, value, onChangeText, placeholder, keyboardType, multiline, numberOfLines, error, autoFocus }: FieldProps) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);

  return (
    <View style={styles.field}>
      <Text style={styles.label}>{label}</Text>
      <TextInput
        value={value}
        onChangeText={onChangeText}
        placeholder={placeholder}
        placeholderTextColor={theme.color.textFaint}
        keyboardType={keyboardType}
        multiline={multiline}
        numberOfLines={numberOfLines}
        autoFocus={autoFocus}
        style={[
          styles.input,
          multiline ? styles.inputMultiline : styles.inputSingle,
          error && styles.inputError,
        ]}
      />
      {error ? <Text style={styles.error}>{error}</Text> : null}
    </View>
  );
}

type SearchFieldProps = {
  value: string;
  onChangeText: (value: string) => void;
  placeholder: string;
  autoFocus?: boolean;
};

/** Not a new component — FormSection's input at height 48 with a search glyph and a clear action (mobile-components.md). */
export function SearchField({ value, onChangeText, placeholder, autoFocus }: SearchFieldProps) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);

  return (
    <View style={[styles.input, styles.inputSingle, styles.searchRow]}>
      <Text style={styles.searchGlyph}>⌕</Text>
      <TextInput
        value={value}
        onChangeText={onChangeText}
        placeholder={placeholder}
        placeholderTextColor={theme.color.textFaint}
        autoFocus={autoFocus}
        style={styles.searchInput}
      />
      {value.length > 0 ? (
        <Pressable onPress={() => onChangeText('')} hitSlop={theme.hitSlop} accessibilityRole="button">
          <Text style={styles.clear}>Hapus</Text>
        </Pressable>
      ) : null}
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    hint: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted, paddingHorizontal: theme.layout.screenPadding, marginTop: -theme.space.xs, marginBottom: theme.space.sm },
    fields: { paddingHorizontal: theme.layout.screenPadding, gap: theme.space.md },
    field: { gap: theme.space.xs },
    label: {
      fontFamily: 'Archivo_800ExtraBold',
      fontSize: 11,
      lineHeight: 14,
      letterSpacing: 1.0,
      textTransform: 'uppercase',
      color: theme.color.textMuted,
    },
    input: {
      borderWidth: 1,
      borderColor: theme.color.divider,
      paddingHorizontal: theme.space.md,
      fontFamily: 'Archivo_400Regular',
      fontSize: 15,
      color: theme.color.text,
    },
    inputSingle: { height: theme.layout.buttonHeight },
    inputMultiline: { minHeight: theme.layout.buttonHeight, paddingVertical: theme.space.sm, textAlignVertical: 'top' },
    inputError: { borderColor: theme.color.accent },
    error: { fontFamily: 'Archivo_400Regular', fontSize: 12, color: theme.color.accent700 },
    searchRow: { flexDirection: 'row', alignItems: 'center', gap: theme.space.sm, marginHorizontal: theme.layout.screenPadding },
    searchGlyph: { fontSize: 16, color: theme.color.textFaint },
    searchInput: { flex: 1, fontFamily: 'Archivo_400Regular', fontSize: 15, color: theme.color.text, padding: 0 },
    clear: { fontFamily: 'Archivo_800ExtraBold', fontSize: 12, color: theme.color.accent },
    rule: { height: 2, backgroundColor: theme.color.rule, marginTop: theme.space.xl },
  });
}
