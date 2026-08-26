import { useLocalSearchParams } from 'expo-router';

import { BelumTersedia } from '@/components/BelumTersedia';
import { belumTersediaCopy } from '@/data/mock';

export default function BelumTersediaRoute() {
  const { topic } = useLocalSearchParams<{ topic: string }>();
  const copy = belumTersediaCopy[topic ?? ''] ?? { destination: 'Belum tersedia', body: 'Bagian ini sedang disiapkan.' };

  return <BelumTersedia destination={copy.destination} body={copy.body} />;
}
