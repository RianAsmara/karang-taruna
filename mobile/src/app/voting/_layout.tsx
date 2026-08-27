import { Stack } from 'expo-router';

export default function VotingStackLayout() {
  return (
    <Stack screenOptions={{ headerShown: false }}>
      <Stack.Screen name="index" />
      <Stack.Screen name="[id]/index" />
      <Stack.Screen name="[id]/hasil" />
    </Stack>
  );
}
