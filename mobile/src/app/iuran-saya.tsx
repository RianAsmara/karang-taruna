import { BelumTersedia } from '@/components/BelumTersedia';
import { belumTersediaCopy } from '@/data/mock';

export default function IuranSaya() {
  const copy = belumTersediaCopy['iuran-saya'];

  return <BelumTersedia destination={copy.destination} body={copy.body} />;
}
