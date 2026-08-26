import { create } from 'zustand';

import { eventDetail } from '@/data/mock';

/**
 * The one shared source of truth for task-done state, so toggling a task
 * in EventDetail's Tugas tab is reflected on Home's and Kegiatan's prep
 * progress bars too — an explicit requirement (screens/05-Event-Detail.md
 * "Behavior"; not just this one screen's local state.
 */
type Store = {
  taskDone: Record<string, boolean>;
  toggleTask: (id: string) => void;
};

export const useEventProgressStore = create<Store>((set) => ({
  taskDone: Object.fromEntries(eventDetail.tasks.map((t) => [t.id, t.done])),
  toggleTask: (id) => set((s) => ({ taskDone: { ...s.taskDone, [id]: !s.taskDone[id] } })),
}));

/**
 * Prep progress for the one event whose tasks are modeled (`turnamen-voli`).
 * Returns the raw value/total/pct — each screen formats its own label
 * ("PERSIAPAN 75%" compact on Home/Kegiatan vs "PERSIAPAN · 3 DARI 4 · 75%"
 * on EventDetail's Ringkasan tab).
 */
export function useTurnamenVoliProgress() {
  const taskDone = useEventProgressStore((s) => s.taskDone);
  const total = eventDetail.tasks.length;
  const value = eventDetail.tasks.filter((t) => taskDone[t.id]).length;
  const pct = total > 0 ? Math.round((value / total) * 100) : 0;
  return { value, total, pct };
}
