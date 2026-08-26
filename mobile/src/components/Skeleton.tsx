import { useEffect, useState } from 'react';
import { Animated } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';

type Props = {
  width: number | `${number}%`;
  height: number;
};

export function Skeleton({ width, height }: Props) {
  const { theme } = useTheme();
  const [opacity] = useState(() => new Animated.Value(1));

  useEffect(() => {
    const loop = Animated.loop(
      Animated.sequence([
        Animated.timing(opacity, { toValue: 0.55, duration: 600, useNativeDriver: true }),
        Animated.timing(opacity, { toValue: 1, duration: 600, useNativeDriver: true }),
      ]),
    );
    loop.start();
    return () => loop.stop();
  }, [opacity]);

  return (
    <Animated.View
      style={{ width, height, backgroundColor: theme.color.skeleton, borderRadius: theme.radius, opacity }}
    />
  );
}
