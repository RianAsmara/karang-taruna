import { useState } from 'react';
import { ScrollView, StyleSheet, View } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';
import { BottomSheet } from './BottomSheet';
import { Button } from './Button';
import { FilterChip } from './FilterChip';
import { SectionHeader } from './SectionHeader';
import { Segmented } from './Segmented';

export type FilterOption = { value: string; label: string };

export type FilterSection =
  | { key: string; title: string; kind: 'chips'; multiple?: boolean; options: FilterOption[] }
  | { key: string; title: string; kind: 'segmented'; options: FilterOption[] };

/** One entry per section key, holding the selected option values (segmented sections hold at most one). */
export type FilterValue = Record<string, string[]>;

type Props = {
  visible: boolean;
  onClose: () => void;
  title?: string;
  sections: FilterSection[];
  value: FilterValue;
  onApply: (value: FilterValue) => void;
  onReset: () => void;
};

/** A composition of BottomSheet + chips, defined once so every domain filters identically — see mobile-components.md § New in this phase. */
export function FilterSheet({ visible, onClose, title = 'Filter', sections, value, onApply, onReset }: Props) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const [draft, setDraft] = useState<FilterValue>(value);
  // Reset the draft to the applied value whenever the sheet opens — a
  // render-time prop-change adjustment (React's documented alternative to
  // an effect for this), not a subscription to an external system.
  const [wasVisible, setWasVisible] = useState(visible);
  if (visible !== wasVisible) {
    setWasVisible(visible);
    if (visible) setDraft(value);
  }

  function toggleChip(section: FilterSection, optionValue: string) {
    setDraft((prev) => {
      const current = prev[section.key] ?? [];
      if (section.kind === 'segmented' || !('multiple' in section && section.multiple)) {
        return { ...prev, [section.key]: current.includes(optionValue) ? [] : [optionValue] };
      }
      const next = current.includes(optionValue) ? current.filter((v) => v !== optionValue) : [...current, optionValue];
      return { ...prev, [section.key]: next };
    });
  }

  return (
    <BottomSheet visible={visible} title={title} onClose={onClose} scrollable={false}>
      <ScrollView style={styles.scroll}>
        {sections.map((section) => {
          const selected = draft[section.key] ?? [];
          return (
            <View key={section.key}>
              <SectionHeader title={section.title} />
              {section.kind === 'segmented' ? (
                <View style={styles.segmentedWrap}>
                  <Segmented
                    options={section.options.map((o) => o.label)}
                    value={section.options.find((o) => selected.includes(o.value))?.label ?? section.options[0]?.label}
                    onChange={(label) => {
                      const option = section.options.find((o) => o.label === label);
                      if (option) toggleChip(section, option.value);
                    }}
                  />
                </View>
              ) : (
                <View style={styles.chipRow}>
                  {section.options.map((o) => (
                    <FilterChip key={o.value} label={o.label} active={selected.includes(o.value)} onPress={() => toggleChip(section, o.value)} />
                  ))}
                </View>
              )}
              <View style={styles.divider} />
            </View>
          );
        })}
      </ScrollView>
      <View style={styles.footer}>
        <Button variant="ghost" label="Reset" onPress={onReset} />
        <View style={styles.footerApply}>
          <Button variant="primary" label="Terapkan" onPress={() => onApply(draft)} block />
        </View>
      </View>
    </BottomSheet>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    scroll: { maxHeight: 420 },
    segmentedWrap: { paddingHorizontal: theme.layout.screenPadding, marginBottom: theme.space.md },
    chipRow: {
      flexDirection: 'row',
      flexWrap: 'wrap',
      gap: theme.space.sm,
      paddingHorizontal: theme.layout.screenPadding,
      marginBottom: theme.space.md,
    },
    divider: { height: 1, backgroundColor: theme.color.divider, marginHorizontal: theme.layout.screenPadding, marginBottom: theme.space.sm },
    footer: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: theme.space.md,
      paddingHorizontal: theme.layout.screenPadding,
      paddingTop: theme.space.md,
      borderTopWidth: 1,
      borderTopColor: theme.color.divider,
    },
    footerApply: { flex: 1 },
  });
}
