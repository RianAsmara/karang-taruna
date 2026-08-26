import { StyleSheet, Text, View } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';
import { Button } from './Button';

type Props = {
  title: string;
  body: string;
  actionLabel?: string;
  onAction?: () => void;
};

export function EmptyState({ title, body, actionLabel, onAction }: Props) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);

  return (
    <View style={styles.root}>
      <View style={styles.mark} />
      <Text style={styles.title}>{title}</Text>
      <Text style={styles.body}>{body}</Text>
      {actionLabel ? (
        <View style={styles.action}>
          <Button variant="secondary" label={actionLabel} onPress={onAction} />
        </View>
      ) : null}
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { alignItems: 'flex-start', padding: theme.layout.screenPadding },
    mark: {
      width: 28,
      height: 28,
      borderWidth: 2,
      borderColor: theme.color.text,
      marginBottom: theme.space.md,
    },
    title: { fontFamily: 'Archivo_800ExtraBold', fontSize: 15, color: theme.color.text, marginBottom: 4 },
    body: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted, marginBottom: theme.space.lg },
    action: { alignSelf: 'flex-start' },
  });
}
