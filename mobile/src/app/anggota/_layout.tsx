import { Stack } from 'expo-router';

export default function AnggotaStackLayout() {
  return (
    <Stack screenOptions={{ headerShown: false }}>
      <Stack.Screen name="index" />
      <Stack.Screen name="[id]" />
      <Stack.Screen name="peran" />
    </Stack>
  );
}
