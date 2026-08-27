import { StyleSheet, Text, View } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';
import { BottomSheet } from './BottomSheet';
import { Button } from './Button';

type Props = {
  visible: boolean;
  onClose: () => void;
  /** The literal outgoing message, one array entry per line — rendered verbatim, no approximation. */
  lines: string[];
  /** The link line's display text (e.g. the shortened report URL), set apart at the foot of the preview block. Omit for a message that doesn't end in a link (e.g. "Beri tahu bendahara"). */
  linkLabel?: string;
  onShare: () => void;
  onCopy: () => void;
  title?: string;
};

/** Trust requirement: the user must read what leaves the app before it leaves — see mobile-components.md § New in this phase. */
export function SharePreview({ visible, onClose, lines, linkLabel, onShare, onCopy, title = 'Bagikan' }: Props) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);

  return (
    <BottomSheet visible={visible} title={title} onClose={onClose}>
      <View style={styles.body}>
        <View style={styles.preview}>
          {lines.map((line, i) => (
            <Text key={i} style={styles.line}>
              {line}
            </Text>
          ))}
          {linkLabel ? <Text style={styles.link}>{linkLabel}</Text> : null}
        </View>
        <Button variant="primary" label="Bagikan ke WhatsApp" onPress={onShare} block />
        <Button variant="ghost" label="Salin teks" onPress={onCopy} block />
      </View>
    </BottomSheet>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    body: { paddingHorizontal: theme.layout.screenPadding, gap: theme.space.md },
    preview: {
      backgroundColor: theme.color.surfaceAlt,
      borderLeftWidth: 2,
      borderLeftColor: theme.color.rule,
      padding: theme.space.md,
      gap: 2,
    },
    line: { fontFamily: 'Archivo_400Regular', fontSize: 13, lineHeight: 19, color: theme.color.text },
    link: { fontFamily: 'Archivo_400Regular', fontSize: 13, lineHeight: 19, color: theme.color.accent, marginTop: theme.space.xs },
  });
}
