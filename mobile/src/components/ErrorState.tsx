import { StyleSheet, Text, View } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';
import { Button } from './Button';

type Props = {
  title: string;
  body: string;
  onRetry: () => void;
  /** e.g. "Data per 24 Agu, 19.40" — always states the last successful load. */
  staleAt?: string;
};

export function ErrorState({ title, body, onRetry, staleAt }: Props) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);

  return (
    <View style={styles.root}>
      <Text style={styles.title}>{title}</Text>
      <Text style={styles.body}>{body}</Text>
      {staleAt ? <Text style={styles.staleAt}>{staleAt}</Text> : null}
      <View style={styles.action}>
        <Button variant="secondary" label="Coba lagi" onPress={onRetry} />
      </View>
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: {
      borderLeftWidth: 4,
      borderLeftColor: theme.color.accent,
      padding: theme.layout.screenPadding,
      marginHorizontal: theme.layout.screenPadding,
      backgroundColor: theme.color.surface,
    },
    title: { fontFamily: 'Archivo_800ExtraBold', fontSize: 15, color: theme.color.accent700, marginBottom: 4 },
    body: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted },
    staleAt: { fontFamily: 'Archivo_400Regular', fontSize: 12, color: theme.color.textFaint, marginTop: theme.space.xs },
    action: { alignSelf: 'flex-start', marginTop: theme.space.md },
  });
}
