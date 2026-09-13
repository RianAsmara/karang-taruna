import type { ReactNode } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, type StyleProp, type ViewStyle } from 'react-native';

type Props = {
  children: ReactNode;
  contentContainerStyle?: StyleProp<ViewStyle>;
};

/**
 * The scroll container every form screen uses, so a focused input is never
 * left behind the keyboard — RN does not do this on its own, and a form
 * whose field is covered while typing is worse than one that scrolls oddly.
 *
 * `behavior` is iOS-only on purpose: Android's `adjustResize` (set in
 * app.json's `softwareKeyboardLayoutMode`) already resizes the window, and
 * adding padding on top of that double-counts the keyboard height.
 */
export function KeyboardAwareScroll({ children, contentContainerStyle }: Props) {
  return (
    <KeyboardAvoidingView style={styles.fill} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView
        contentContainerStyle={contentContainerStyle}
        keyboardShouldPersistTaps="handled"
        keyboardDismissMode="on-drag"
      >
        {children}
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  fill: { flex: 1 },
});
