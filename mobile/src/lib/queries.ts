import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { apiFetch, apiUpload } from '@/lib/api';

export interface ApiAccount {
  id: string;
  name: string;
  balance: number;
}

export interface ApiTransaction {
  id: string;
  amount: number;
  transactionType: 'INCOME' | 'EXPENSE' | 'TRANSFER';
  transactionTypeLabel: string;
  status: 'DRAFT' | 'PENDING' | 'APPROVED' | 'REJECTED';
  statusLabel: string;
  description: string | null;
  transactionDate: string;
  accountName?: string;
  categoryName?: string | null;
  eventTitle?: string | null;
  creatorName?: string;
  hasEvidence?: boolean;
}

export interface ApiDue {
  id: string;
  membershipId?: string;
  memberName?: string;
  userId?: string;
  period: string;
  type: 'MONTHLY' | 'EVENT' | 'SPECIAL' | 'DONATION';
  typeLabel: string;
  amountDue: number;
  amountPaid: number;
  amountOutstanding: number;
  isPaid: boolean;
  isExempt: boolean;
  notifiedAt: string | null;
  isAwaitingConfirmation: boolean;
  isPartiallyPaid: boolean;
  lastPaymentAt: string | null;
  recordedByName: string | null;
  method: 'TUNAI' | 'TRANSFER' | null;
  methodLabel: string | null;
}

export interface ApiTransparencySummary {
  balance: number;
  monthIncome: number;
  monthExpense: number;
  monthSurplus: number;
  recentTransactions: {
    amount: number;
    transactionType: 'INCOME' | 'EXPENSE' | 'TRANSFER';
    description: string | null;
    transactionDate: string;
  }[];
  publishedReports: { id: string; title: string; periodStart: string; closingBalance: number }[];
}

export interface ApiEvent {
  id: string;
  title: string;
  category?: string | null;
  categoryLabel?: string | null;
  status: string;
  statusLabel: string;
  location: string | null;
  startAt: string;
  endAt: string | null;
  committeeCount?: number;
  participantCount?: number;
}

export interface ApiAnnouncement {
  id: string;
  title: string;
  body: string;
  publishedAt: string | null;
  authorName?: string;
}

export interface ApiTask {
  id: string;
  title: string;
  status: 'TODO' | 'IN_PROGRESS' | 'BLOCKED' | 'DONE';
  statusLabel: string;
  priority: 'LOW' | 'MEDIUM' | 'HIGH';
  priorityLabel: string;
  dueDate: string | null;
  event?: { id: string; title: string };
  assignee?: { id: string; name: string } | null;
}

export interface ApiCommittee {
  id: string;
  membershipId: string;
  name: string;
  roleTitle: string | null;
}

export interface ApiParticipant {
  id: string;
  membershipId: string;
  name: string;
  status: string;
  statusLabel: string;
}

export interface ApiEventDetail extends ApiEvent {
  description: string | null;
  pic?: { id: string; name: string } | null;
  sponsor?: { id: string; name: string } | null;
  budgetAmount?: number | null;
  tasks: ApiTask[];
  committees: ApiCommittee[];
  participants: ApiParticipant[];
}

export interface ApiEventCommitteeInput {
  membership_id: string;
  role_title?: string;
}

interface Collection<T> {
  data: T[];
}

export interface ApiOrganization {
  id: string;
  name: string;
  slug: string;
  requireTransactionApproval: boolean;
  publicTransparencyEnabled: boolean;
}

export interface ApiCurrentOrganizationResponse {
  data: ApiOrganization;
  /** The authenticated user's own membership id — not the User id — for matching against assignee/committee/participant ids elsewhere in the API. */
  membership: { id: string; role: string; roleLabel: string; participatedEventsCount: number };
}

export function useCurrentOrganization() {
  return useQuery({
    queryKey: ['organization', 'current'],
    queryFn: () => apiFetch<ApiCurrentOrganizationResponse>('/organizations/current'),
  });
}

export function useAccounts() {
  return useQuery({
    queryKey: ['accounts'],
    queryFn: () => apiFetch<Collection<ApiAccount>>('/finance/accounts'),
  });
}

export interface ApiFinancialCategory {
  id: string;
  name: string;
  transactionType: 'INCOME' | 'EXPENSE';
  transactionTypeLabel: string;
}

export function useFinancialCategories() {
  return useQuery({
    queryKey: ['financial-categories'],
    queryFn: () => apiFetch<Collection<ApiFinancialCategory>>('/finance/categories'),
  });
}

export function useTransactions(eventId?: string) {
  return useQuery({
    queryKey: ['transactions', eventId ?? null],
    queryFn: () => apiFetch<Collection<ApiTransaction>>(`/finance/transactions${eventId ? `?event_id=${eventId}` : ''}`),
  });
}

export function useDues() {
  return useQuery({
    queryKey: ['dues'],
    queryFn: () => apiFetch<Collection<ApiDue>>('/finance/dues'),
  });
}

export function useGenerateMonthlyDues() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (body: { period: string; amount_due: number }) =>
      apiFetch<Collection<ApiDue>>('/finance/dues/generate-monthly', { method: 'POST', body }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['dues'] }),
  });
}

export function useRecordDuePayment(dueId: string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (body: { amount: number; paid_at: string; financial_account_id: string; category_id: string; method?: string; note?: string }) =>
      apiFetch<{ data: ApiDue }>(`/finance/dues/${dueId}/payments`, { method: 'POST', body }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['dues'] });
      queryClient.invalidateQueries({ queryKey: ['transactions'] });
      queryClient.invalidateQueries({ queryKey: ['transparency'] });
    },
  });
}

export function useNotifyDuePayment(dueId: string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: () => apiFetch<{ data: ApiDue }>(`/finance/dues/${dueId}/notify`, { method: 'POST' }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['dues'] }),
  });
}

export function useTransparency() {
  return useQuery({
    queryKey: ['transparency'],
    queryFn: () => apiFetch<ApiTransparencySummary>('/transparency'),
  });
}

export function useEvents() {
  return useQuery({
    queryKey: ['events'],
    queryFn: () => apiFetch<Collection<ApiEvent>>('/events'),
  });
}

export interface ApiReportDetail {
  id: string;
  title: string;
  reportType?: string;
  reportTypeLabel: string;
  periodStart: string;
  periodEnd: string;
  status: 'DRAFT' | 'DIPERIKSA' | 'DISETUJUI' | 'PUBLISHED' | 'ARCHIVED';
  statusLabel: string;
  visibility?: string;
  visibilityLabel: string;
  openingBalance: number;
  totalIncome: number;
  totalExpense: number;
  closingBalance: number;
  publishedAt: string | null;
  publisherName: string | null;
  treasurerNote: string | null;
  revisionReason: string | null;
  submittedAt: string | null;
  submitterName: string | null;
  approvedAt: string | null;
  approverName: string | null;
  revisionCount: number;
  organizationName: string;
}

export interface ApiReportShowResponse {
  data: ApiReportDetail;
  meta: {
    canSubmit: boolean;
    canApprove: boolean;
    canRequestRevision: boolean;
    canPublish: boolean;
    canArchive: boolean;
    canRevise: boolean;
    canDelete: boolean;
    shareUrl: string;
    qrUrl: string;
    pdfUrl: string;
  };
  categoryBreakdown: {
    income: { label: string; amount: number }[];
    expense: { label: string; amount: number }[];
  };
}

export function useReports() {
  return useQuery({
    queryKey: ['reports'],
    queryFn: () => apiFetch<Collection<ApiReportDetail>>('/finance/reports'),
  });
}

export function useCreateReport() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (body: { title: string; report_type: string; period_start: string; period_end: string; visibility: string }) =>
      apiFetch<{ data: ApiReportDetail }>('/finance/reports', { method: 'POST', body }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['reports'] }),
  });
}

export function useSaveReportNote(id: string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (note: string) => apiFetch<{ data: ApiReportDetail }>(`/finance/reports/${id}/note`, { method: 'PATCH', body: { note } }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['reports'] });
      queryClient.invalidateQueries({ queryKey: ['report', id] });
    },
  });
}

export function useSubmitReport(id: string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (note?: string) => apiFetch<{ data: ApiReportDetail }>(`/finance/reports/${id}/submit`, { method: 'POST', body: { note } }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['reports'] });
      queryClient.invalidateQueries({ queryKey: ['report', id] });
    },
  });
}

export function useApproveReport(id: string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: () => apiFetch<{ data: ApiReportDetail }>(`/finance/reports/${id}/approve`, { method: 'POST' }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['reports'] });
      queryClient.invalidateQueries({ queryKey: ['report', id] });
    },
  });
}

export function useRequestReportRevision(id: string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (reason: string) =>
      apiFetch<{ data: ApiReportDetail }>(`/finance/reports/${id}/request-revision`, { method: 'POST', body: { reason } }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['reports'] });
      queryClient.invalidateQueries({ queryKey: ['report', id] });
    },
  });
}

export function usePublishReport(id: string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: () => apiFetch<{ data: ApiReportDetail }>(`/finance/reports/${id}/publish`, { method: 'POST' }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['reports'] });
      queryClient.invalidateQueries({ queryKey: ['report', id] });
      queryClient.invalidateQueries({ queryKey: ['transparency'] });
    },
  });
}

export function useReport(id: string) {
  return useQuery({
    queryKey: ['report', id],
    queryFn: () => apiFetch<ApiReportShowResponse>(`/finance/reports/${id}`),
    enabled: Boolean(id),
  });
}

export function useEvent(id: string) {
  return useQuery({
    queryKey: ['event', id],
    queryFn: () => apiFetch<{ data: ApiEventDetail }>(`/events/${id}`),
    enabled: Boolean(id),
  });
}

interface EventWizardBody {
  title: string;
  description?: string;
  category?: string;
  location?: string;
  start_at: string;
  end_at?: string;
  committees?: ApiEventCommitteeInput[];
  budget_amount?: number;
}

export function useCreateEvent() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (body: EventWizardBody) => apiFetch<{ data: ApiEventDetail }>('/events', { method: 'POST', body }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['events'] }),
  });
}

/** Takes the event id per call (not fixed at hook creation) so a caller can
 * PATCH an event it just created via useCreateEvent() in the same flow,
 * without racing a stale hook instance bound to the old (empty) id. */
export function useUpdateEvent() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ id, body }: { id: string; body: EventWizardBody & { status: string; lifecycle_stage: string } }) =>
      apiFetch<{ data: ApiEventDetail }>(`/events/${id}`, { method: 'PATCH', body }),
    onSuccess: (_data, { id }) => {
      queryClient.invalidateQueries({ queryKey: ['events'] });
      queryClient.invalidateQueries({ queryKey: ['event', id] });
    },
  });
}

export function useUpdateTaskStatus(eventId: string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ taskId, status }: { taskId: string; status: string }) =>
      apiFetch(`/events/${eventId}/tasks/${taskId}/status`, { method: 'PATCH', body: { status } }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['event', eventId] }),
  });
}

export interface ApiMember {
  id: string;
  name: string;
  email: string;
  phone: string | null;
  role: string;
  roleLabel: string;
  joinedAt: string;
  isChair: boolean;
  /** Present only within the 30-day "Keluar" retention window — absent for a live membership. */
  leftAt: string | null;
}

export interface ApiMemberResponsibility {
  id: string;
  roleTitle: string | null;
  event: { id: string; title: string; status: string; statusLabel: string };
}

export interface ApiAuditLogEntry {
  id: string;
  action: string;
  modelType: string;
  createdAt: string;
}

export interface ApiNotification {
  id: string;
  type: string;
  data: Record<string, string | number | null>;
  readAt: string | null;
  createdAt: string;
}

export function useNotifications() {
  return useQuery({
    queryKey: ['notifications'],
    queryFn: () => apiFetch<Collection<ApiNotification>>('/notifications'),
  });
}

export function useMarkNotificationRead() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (id: string) => apiFetch(`/notifications/${id}/read`, { method: 'POST' }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['notifications'] }),
  });
}

export function useMembers() {
  return useQuery({
    queryKey: ['members'],
    queryFn: () => apiFetch<Collection<ApiMember>>('/members'),
  });
}

export function useMember(id: string) {
  return useQuery({
    queryKey: ['member', id],
    queryFn: () => apiFetch<{ data: ApiMember }>(`/members/${id}`),
    enabled: Boolean(id),
  });
}

export function useMemberResponsibilities(id: string) {
  return useQuery({
    queryKey: ['member', id, 'responsibilities'],
    queryFn: () => apiFetch<Collection<ApiMemberResponsibility>>(`/members/${id}/responsibilities`),
    enabled: Boolean(id),
  });
}

export function useMemberActivity(id: string) {
  return useQuery({
    queryKey: ['member', id, 'activity'],
    queryFn: () => apiFetch<Collection<ApiAuditLogEntry>>(`/members/${id}/activity`),
    enabled: Boolean(id),
  });
}

export function useUpdateMemberRole(id: string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (role: string) => apiFetch<{ data: ApiMember }>(`/members/${id}`, { method: 'PATCH', body: { role } }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['members'] });
      queryClient.invalidateQueries({ queryKey: ['member', id] });
    },
  });
}

export function useTransferChair(id: string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: () => apiFetch<{ data: ApiMember }>(`/members/${id}/transfer-chair`, { method: 'POST' }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['members'] });
      queryClient.invalidateQueries({ queryKey: ['organization', 'current'] });
    },
  });
}

/**
 * Generic counterparts of useUpdateMemberRole/useTransferChair, for a
 * caller (the Peran & Izin member picker) that needs to target a
 * different member per tap rather than one fixed id per hook instance.
 */
export function useUpdateAnyMemberRole() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ memberId, role }: { memberId: string; role: string }) =>
      apiFetch<{ data: ApiMember }>(`/members/${memberId}`, { method: 'PATCH', body: { role } }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['members'] }),
  });
}

export function useTransferChairFor() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (memberId: string) => apiFetch<{ data: ApiMember }>(`/members/${memberId}/transfer-chair`, { method: 'POST' }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['members'] });
      queryClient.invalidateQueries({ queryKey: ['organization', 'current'] });
    },
  });
}

export function useRemoveMember(id: string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: () => apiFetch(`/members/${id}`, { method: 'DELETE' }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['members'] }),
  });
}

export function useUpdatePhoneVisibility() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (body: { phone: string | null; show_phone_to_members: boolean }) =>
      apiFetch('/profile/phone-visibility', { method: 'PATCH', body }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['members'] }),
  });
}

export function useAnnouncements() {
  return useQuery({
    queryKey: ['announcements'],
    queryFn: () => apiFetch<Collection<ApiAnnouncement>>('/announcements'),
  });
}

export function useMyTasks() {
  return useQuery({
    queryKey: ['my-tasks'],
    queryFn: () => apiFetch<Collection<ApiTask>>('/my/tasks'),
  });
}

export interface ApiInventoryLoan {
  id: string;
  borrower: { id: string; name: string };
  quantity: number;
  status: 'BORROWED' | 'RETURNED';
  statusLabel: string;
  isOverdue: boolean;
  purpose: string | null;
  borrowedAt: string;
  dueDate: string;
  returnedAt: string | null;
  returnedQuantity: number | null;
  returnedCondition: string | null;
  returnNote: string | null;
  event?: { id: string; title: string } | null;
}

export interface ApiInventoryItem {
  id: string;
  name: string;
  category: string;
  categoryLabel: string;
  quantity: number;
  availableQuantity: number;
  condition: string;
  conditionLabel: string;
  location: string | null;
  notes: string | null;
  lastCheckedAt: string | null;
  responsible?: { id: string; name: string } | null;
  loans?: ApiInventoryLoan[];
  createdAt: string;
}

export function useInventoryItems() {
  return useQuery({
    queryKey: ['inventory'],
    queryFn: () => apiFetch<Collection<ApiInventoryItem>>('/inventory'),
  });
}

export function useInventoryItem(id: string) {
  return useQuery({
    queryKey: ['inventory', id],
    queryFn: () => apiFetch<{ data: ApiInventoryItem }>(`/inventory/${id}`),
    enabled: Boolean(id),
  });
}

interface InventoryItemBody {
  name: string;
  category: string;
  quantity: number;
  condition: string;
  location?: string;
  notes?: string;
  responsible_membership_id?: string;
}

export function useCreateInventoryItem() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (body: InventoryItemBody) => apiFetch<{ data: ApiInventoryItem }>('/inventory', { method: 'POST', body }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['inventory'] }),
  });
}

export function useUpdateInventoryItem(id: string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (body: InventoryItemBody) => apiFetch<{ data: ApiInventoryItem }>(`/inventory/${id}`, { method: 'PATCH', body }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['inventory'] });
      queryClient.invalidateQueries({ queryKey: ['inventory', id] });
    },
  });
}

export function useDeleteInventoryItem() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (id: string) => apiFetch(`/inventory/${id}`, { method: 'DELETE' }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['inventory'] }),
  });
}

export function useBorrowInventoryItem(itemId: string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (body: { quantity: number; due_date: string; purpose?: string; event_id?: string }) =>
      apiFetch<{ data: ApiInventoryLoan }>(`/inventory/${itemId}/loans`, { method: 'POST', body }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['inventory'] });
      queryClient.invalidateQueries({ queryKey: ['inventory', itemId] });
    },
  });
}

export function useReturnInventoryLoan(itemId: string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ loanId, body }: { loanId: string; body: { quantity: number; condition: string; note?: string } }) =>
      apiFetch<{ data: ApiInventoryLoan }>(`/inventory/loans/${loanId}/return`, { method: 'POST', body }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['inventory'] });
      queryClient.invalidateQueries({ queryKey: ['inventory', itemId] });
    },
  });
}

export interface ApiDocument {
  id: string;
  title: string;
  category: string;
  categoryLabel: string;
  originalName: string;
  mimeType: string;
  sizeBytes: number;
  uploader?: { id: string; name: string };
  event?: { id: string; title: string } | null;
  isArchived: boolean;
  createdAt: string;
}

export function useDocuments() {
  return useQuery({
    queryKey: ['documents'],
    queryFn: () => apiFetch<Collection<ApiDocument>>('/documents'),
  });
}

export function useDocument(id: string) {
  return useQuery({
    queryKey: ['document', id],
    queryFn: () => apiFetch<{ data: ApiDocument }>(`/documents/${id}`),
    enabled: Boolean(id),
  });
}

export function useUploadDocument() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (form: FormData) => apiUpload<{ data: ApiDocument }>('/documents', form),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['documents'] }),
  });
}

export function useDeleteDocument() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (id: string) => apiFetch(`/documents/${id}`, { method: 'DELETE' }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['documents'] }),
  });
}

export interface ApiSponsorContribution {
  id: string;
  sponsor: { id: string; name: string; contactName: string | null; contactPhone: string | null };
  type: 'UANG' | 'BARANG' | 'JASA';
  typeLabel: string;
  status: 'DIAJUKAN' | 'SETUJU' | 'DITERIMA' | 'BATAL';
  statusLabel: string;
  amount: number | null;
  description: string | null;
  event?: { id: string; title: string } | null;
  notes: string | null;
  createdAt: string;
}

export function useSponsorContributions() {
  return useQuery({
    queryKey: ['sponsors'],
    queryFn: () => apiFetch<Collection<ApiSponsorContribution>>('/sponsors'),
  });
}

export function useSponsorContribution(id: string) {
  return useQuery({
    queryKey: ['sponsor', id],
    queryFn: () => apiFetch<{ data: ApiSponsorContribution }>(`/sponsors/${id}`),
    enabled: Boolean(id),
  });
}

interface SponsorContributionBody {
  name: string;
  type: 'UANG' | 'BARANG' | 'JASA';
  amount?: number;
  description?: string;
  event_id?: string;
  contact_name?: string;
  contact_phone?: string;
  notes?: string;
}

export function useCreateSponsorContribution() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (body: SponsorContributionBody) => apiFetch<{ data: ApiSponsorContribution }>('/sponsors', { method: 'POST', body }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['sponsors'] }),
  });
}

export function useUpdateSponsorContributionStatus(id: string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (body: { status: string; financial_account_id?: string; category_id?: string }) =>
      apiFetch<{ data: ApiSponsorContribution }>(`/sponsors/${id}/status`, { method: 'PATCH', body }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['sponsors'] });
      queryClient.invalidateQueries({ queryKey: ['sponsor', id] });
      queryClient.invalidateQueries({ queryKey: ['transactions'] });
    },
  });
}

export interface ApiVoteOption {
  id: string;
  label: string;
}

export interface ApiVote {
  id: string;
  question: string;
  description: string | null;
  anonymous: boolean;
  editable: boolean;
  maxSelections: number;
  startAt: string;
  endAt: string;
  isOpen: boolean;
  isEligible: boolean;
  hasResponded: boolean;
  participationCount: number;
  eligibleCount: number;
  options: ApiVoteOption[];
  myOptionIds: string[];
  event?: { id: string; title: string } | null;
}

export interface ApiVoteResultOption {
  id: string;
  label: string;
  count: number;
  percent: number | null;
}

export interface ApiVoteResult {
  id: string;
  question: string;
  anonymous: boolean;
  closedAt: string;
  participationCount: number;
  eligibleCount: number;
  options: ApiVoteResultOption[];
  winningOptionIds: string[];
  isTie: boolean;
  breakdown: { id: string; label: string; members: { id: string; name: string }[] }[] | null;
}

export function useVotes() {
  return useQuery({
    queryKey: ['votes'],
    queryFn: () => apiFetch<Collection<ApiVote>>('/votes'),
  });
}

export function useVote(id: string) {
  return useQuery({
    queryKey: ['vote', id],
    queryFn: () => apiFetch<{ data: ApiVote }>(`/votes/${id}`),
    enabled: Boolean(id),
  });
}

export function useVoteResults(id: string, enabled: boolean) {
  return useQuery({
    queryKey: ['vote', id, 'results'],
    queryFn: () => apiFetch<{ data: ApiVoteResult }>(`/votes/${id}/results`),
    enabled: Boolean(id) && enabled,
  });
}

export function useSubmitVoteResponse(id: string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (optionIds: string[]) => apiFetch<{ data: ApiVote }>(`/votes/${id}/responses`, { method: 'POST', body: { option_ids: optionIds } }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['votes'] });
      queryClient.invalidateQueries({ queryKey: ['vote', id] });
      queryClient.invalidateQueries({ queryKey: ['vote', id, 'results'] });
    },
  });
}
