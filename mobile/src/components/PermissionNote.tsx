import { StyleSheet, Text, View } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';
import { Button } from './Button';

type Props = {
  /** Full plain-language sentence naming who can act, e.g. "Hanya bendahara yang bisa mencatat transaksi." */
  body: string;
  /** The role named in `body` (e.g. "bendahara") — carried for accessibility context; never rendered as a second label (mobile-ux.md never shows the words "role"/"permission"). */
  roleHint: string;
  actionLabel?: string;
  onAction?: () => void;
};

/**
 * Replaces the anti-pattern of disabled controls — never a disabled button,
 * an alert, or a lock icon. See mobile-ux.md § Roles and authorization.
 */
export function PermissionNote({ body, roleHint, actionLabel, onAction }: Props) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);

  return (
    <View style={styles.root} accessibilityLabel={`${body} ${roleHint}`.trim()}>
      <Text style={styles.body}>{body}</Text>
      {actionLabel ? (
        <View style={styles.action}>
          <Button variant="ghost" label={actionLabel} onPress={onAction} />
        </View>
      ) : null}
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: {
      backgroundColor: theme.color.surfaceAlt,
      padding: theme.space.md,
      marginHorizontal: theme.layout.screenPadding,
    },
    body: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.text, lineHeight: 19 },
    action: { alignSelf: 'flex-start', marginTop: theme.space.sm },
  });
}
