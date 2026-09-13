import DateTimePicker, { type DateTimePickerEvent } from '@react-native-community/datetimepicker';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

type Mode = 'date' | 'time';

type Props = {
  label: string;
  /** `YYYY-MM-DD` for mode="date", `HH:mm` for mode="time" — the same wire
   *  format the screens already send to the API, so only the input
   *  mechanism changes, not what gets submitted. */
  value: string;
  onChange: (value: string) => void;
  mode?: Mode;
  placeholder?: string;
  error?: string;
  /** Earliest selectable date — used to keep an event's end after its start. */
  minimumDate?: Date;
};

function pad(n: number): string {
  return n < 10 ? `0${n}` : String(n);
}

function toWireValue(date: Date, mode: Mode): string {
  return mode === 'date'
    ? `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
    : `${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

/** Parses the field's own wire format back into a Date to seed the picker.
 *  Falls back to "now" for an empty or half-typed value left over from the
 *  free-text fields this component replaced. */
function fromWireValue(value: string, mode: Mode): Date {
  const now = new Date();

  if (mode === 'date') {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
    return match ? new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3])) : now;
  }

  const match = /^(\d{1,2}):(\d{2})$/.exec(value);
  if (!match) return now;

  const seeded = new Date();
  seeded.setHours(Number(match[1]), Number(match[2]), 0, 0);
  return seeded;
}

/** Renders the value the way Indonesian users read it, while the value the
 *  form holds stays in the API's format. */
function displayValue(value: string, mode: Mode): string | null {
  if (!value) return null;

  if (mode === 'time') return /^(\d{1,2}):(\d{2})$/.test(value) ? value.replace(':', '.') : value;

  const parsed = fromWireValue(value, 'date');
  return new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(parsed);
}

/**
 * A tap-to-pick replacement for the free-text `YYYY-MM-DD` / `HH:mm` fields.
 * Uses the platform's own date/time dialog rather than a custom calendar, so
 * it matches what users already know from every other app on the device.
 */
export function DateField({ label, value, onChange, mode = 'date', placeholder, error, minimumDate }: Props) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const [open, setOpen] = useState(false);

  const shown = displayValue(value, mode);

  const handleChange = (event: DateTimePickerEvent, picked?: Date) => {
    // Android fires this for the dismiss too — only 'set' is a real choice.
    setOpen(false);

    if (event.type === 'set' && picked) {
      onChange(toWireValue(picked, mode));
    }
  };

  return (
    <View style={styles.field}>
      <Text style={styles.label}>{label}</Text>
      <Pressable
        onPress={() => setOpen(true)}
        accessibilityRole="button"
        accessibilityLabel={`${label}${shown ? `: ${shown}` : ''}`}
        style={[styles.input, error ? styles.inputError : null]}
      >
        <Text style={shown ? styles.value : styles.placeholder}>{shown ?? placeholder ?? 'Pilih'}</Text>
      </Pressable>
      {error ? <Text style={styles.error}>{error}</Text> : null}
      {open ? (
        <DateTimePicker
          value={fromWireValue(value, mode)}
          mode={mode}
          minimumDate={minimumDate}
          is24Hour
          onChange={handleChange}
        />
      ) : null}
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
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
      height: theme.layout.buttonHeight,
      justifyContent: 'center',
    },
    inputError: { borderColor: theme.color.accent },
    value: { fontFamily: 'Archivo_400Regular', fontSize: 15, color: theme.color.text },
    placeholder: { fontFamily: 'Archivo_400Regular', fontSize: 15, color: theme.color.textFaint },
    error: { fontFamily: 'Archivo_400Regular', fontSize: 12, color: theme.color.accent700 },
  });
}
